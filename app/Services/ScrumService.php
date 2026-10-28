<?php
namespace App\Services;

use App\Models\{AffectationReunion, Blocage, DailyScrum, DailyScrumEntree, Sprint, SprintEquipe, Tache, TypeReunion, User, UserStory};
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Règles du module Scrum : Daily auto-rempli, rotation des rôles, regroupements Planning / Review. */
class ScrumService
{
    /**
     * Équipe du sprint, calculée dynamiquement :
     * membres du projet présents pendant le sprint (ajoutés avant sa fin, non retirés avant son début)
     * + collaborateurs ayant une tâche dans le sprint + ajouts propres au sprint − retraits propres au sprint.
     */
    public static function membres(Sprint $sprint): Collection
    {
        $ajust = SprintEquipe::where('sprint_id', $sprint->id)->pluck('statut', 'user_id');
        $ids = $sprint->projet->collaborateurs()
            ->whereDate('date_ajout', '<=', $sprint->date_fin)
            ->where(fn($q) => $q->where('actif', true)->orWhereDate('date_retrait', '>=', $sprint->date_debut))
            ->pluck('user_id')
            ->merge($sprint->taches()->whereNotNull('user_id')->pluck('user_id'))
            ->merge($ajust->filter(fn($s) => $s === 'ajoute')->keys())
            ->unique()
            ->reject(fn($id) => ($ajust[$id] ?? null) === 'retire');
        return User::whereIn('id', $ids)->orderBy('name')->get();
    }

    /** Jours du sprint où le Daily est programmé (paramètres du projet). */
    public static function joursProgrammes(Sprint $sprint): Collection
    {
        $jours = $sprint->projet->configDaily()['jours'];
        $res = collect();
        for ($j = $sprint->date_debut->copy()->startOfDay(); $j->lte($sprint->date_fin); $j->addDay()) {
            if (in_array($j->dayOfWeekIso, $jours, true)) $res->push($j->copy());
        }
        return $res;
    }

    public static function estProgramme(Sprint $sprint, Carbon $date): bool
    {
        return in_array($date->dayOfWeekIso, $sprint->projet->configDaily()['jours'], true)
            && $date->between($sprint->date_debut->copy()->startOfDay(), $sprint->date_fin->copy()->endOfDay());
    }

    /** Numéro du Daily = rang du jour programmé dans le sprint (Daily 1 = premier jour programmé). */
    public static function numeroJour(Sprint $sprint, Carbon $date): int
    {
        return max(1, self::joursProgrammes($sprint)->filter(fn($j) => $j->lte($date->copy()->startOfDay()))->count());
    }

    /** Crée et pré-remplit le Daily d'une date (rôles du planning / rotation, horaire programmé). */
    public static function creerDaily(Sprint $sprint, Carbon $date, ?int $creePar = null, ?string $debut = null, ?string $fin = null): DailyScrum
    {
        $config = $sprint->projet->configDaily();
        $numero = self::numeroJour($sprint, $date);
        $d = $sprint->dailyScrums()->create([
            'numero_jour' => $numero, 'date_reunion' => $date->copy()->startOfDay(),
            'heure_debut' => $debut ??= $config['heure_debut'],
            // Fin = début + durée programmée, sauf heure de fin saisie
            'heure_fin' => $fin ?? Carbon::createFromFormat('H:i', substr($debut, 0, 5))->addMinutes((int) $config['duree'])->format('H:i'),
            'roles' => self::roles($sprint, $date, $numero), 'cree_par' => $creePar,
        ]);
        self::remplir($d);
        return $d;
    }

    /** Création automatique du Daily du jour (si activée dans la programmation du projet). */
    public static function creationAutomatique(Sprint $sprint, ?Carbon $date = null): ?DailyScrum
    {
        $date ??= today();
        if ($sprint->statut !== 'en_cours' || !$sprint->projet->configDaily()['creation_auto'] || !self::estProgramme($sprint, $date)) return null;
        if ($sprint->dailyScrums()->whereDate('date_reunion', $date)->exists()) return null;
        return \Illuminate\Support\Facades\DB::transaction(fn() => self::creerDaily($sprint, $date));
    }

    /**
     * Rôles du Daily : d'abord le planning des réunions (affectations Daily Scrum du jour),
     * puis rotation sur les membres du sprint pour les rôles non couverts.
     */
    public static function roles(Sprint $sprint, Carbon $date, int $numero, ?Collection $membres = null): array
    {
        $membres ??= self::membres($sprint);
        $roles = [];

        $type = TypeReunion::where('code', 'daily_scrum')->first();
        if ($type) {
            $affectations = AffectationReunion::with('role')->where('type_reunion_id', $type->id)
                ->whereDate('date_reunion', $date)
                ->where(fn($q) => $q->whereNull('projet_id')->orWhere('projet_id', $sprint->projet_id))
                ->orderByRaw('projet_id is null')->get();
            foreach ($affectations as $a) {
                $code = $a->role?->code;
                if (!$code || !isset(DailyScrum::ROLES[$code]) || isset($roles[$code])) continue;
                $uid = $a->statut === 'remplace' && $a->remplacant_id ? $a->remplacant_id : $a->user_id;
                if ($a->statut !== 'absent' && $uid) $roles[$code] = (int) $uid;
            }
        }

        // Le scribe du jour (planning des réunions, toutes réunions confondues) est le rédacteur des rapports
        if (!isset($roles['scribe']) && ($scribe = RedacteurDuJour::id($date, $sprint->projet_id))) $roles['scribe'] = $scribe;

        // Rotation : chaque jour, les rôles avancent d'un cran dans la liste des membres
        $ids = $membres->pluck('id')->values();
        $n = $ids->count();
        if ($n) {
            foreach (array_keys(DailyScrum::ROLES) as $k => $code) {
                if (isset($roles[$code])) continue;
                // éviter qu'une même personne cumule deux rôles quand l'équipe est assez grande
                for ($essai = 0; $essai < $n; $essai++) {
                    $uid = $ids[($numero - 1 + $k + $essai) % $n];
                    if ($n < count(DailyScrum::ROLES) || !in_array($uid, $roles, true)) break;
                }
                $roles[$code] = $uid;
            }
        }
        return $roles;
    }

    /** Tâches du jour d'un membre : tâches du sprint non terminées et déjà commencées (ou sans date). */
    /** Nombre maximal de tâches listées par case du Daily (au-delà : « … et N autre(s) »). */
    public const MAX_TACHES_DAILY = 5;

    /**
     * Tâches du jour d'un membre : tâches commencées (en cours, à valider, bloquées)
     * et tâches prévues ce jour-là (date de début passée, échéance non dépassée).
     * Les tâches en retard non commencées sont signalées à part dans les défis.
     */
    public static function tachesDuJour(Sprint $sprint, int $userId, Carbon $date): Collection
    {
        return $sprint->taches()->where('user_id', $userId)
            ->whereNotIn('statut', ['termine', 'reporte'])
            ->where(fn($q) => $q->whereIn('statut', ['en_cours', 'a_valider', 'bloque'])
                ->orWhere(fn($p) => $p->whereDate('date_debut', '<=', $date)
                    ->where(fn($e) => $e->whereNull('echeance')->orWhereDate('echeance', '>=', $date))))
            ->orderByRaw("statut = 'en_cours' desc")->orderBy('echeance')->orderBy('id')->get();
    }

    /** Tâches à faire dont l'échéance est dépassée sans avoir été commencées. */
    public static function tachesEnRetard(Sprint $sprint, int $userId, Carbon $date): Collection
    {
        return $sprint->taches()->where('user_id', $userId)->where('statut', 'a_faire')
            ->whereNotNull('echeance')->whereDate('echeance', '<', $date)->get(['id', 'intitule']);
    }

    /** Durée restante = durée prévue − jours écoulés, soit les jours ouvrés jusqu'à l'échéance la plus lointaine. */
    public static function dureeRestante(Sprint $sprint, Collection $taches, Carbon $date): string
    {
        if ($taches->isEmpty()) return '0 j';
        $restes = $taches->map(function ($t) use ($sprint, $date) {
            $fin = $t->echeance ?? $sprint->date_fin;
            return $fin->lt($date) ? -Tache::joursOuvres($fin->copy()->addDay(), $date) : Tache::joursOuvres($date, $fin);
        });
        $max = $restes->max();
        if ($max >= 0) return $max . ' j';
        return 'Retard ' . abs($restes->min()) . ' j';
    }

    /** Liste à puces (HTML du cadre de rédaction), limitée à $max éléments. */
    public static function puces(Collection $lignes, int $max = self::MAX_TACHES_DAILY): string
    {
        $lignes = $lignes->filter()->values();
        $reste = $lignes->count() - $max;
        $liste = $lignes->take($max);
        if ($reste > 0) $liste->push("… et {$reste} autre(s)");
        return TexteRiche::liste($liste) ?? '';
    }

    /** Calcule les champs automatiques d'une ligne du Daily. */
    public static function calculerEntree(DailyScrum $daily, int $userId): array
    {
        $sprint = $daily->sprint;
        $date = $daily->date_reunion->copy()->startOfDay();

        // Hier = tâches terminées depuis le Daily précédent (à défaut, depuis le jour ouvré précédent)
        $precedent = DailyScrum::where('sprint_id', $sprint->id)->where('numero_jour', '<', $daily->numero_jour)->orderByDesc('numero_jour')->first();
        $depuis = $precedent?->date_reunion ?? $date->copy()->subWeekday();
        $hier = self::puces($sprint->taches()->where('user_id', $userId)->where('statut', 'termine')
            ->whereNotNull('date_fin_reelle')->whereDate('date_fin_reelle', '>=', $depuis)->whereDate('date_fin_reelle', '<', $date)
            ->orderBy('date_fin_reelle')->pluck('intitule'));
        // Rien de terminé : on reprend le « à faire » prévu au Daily précédent
        if ($hier === '' && $precedent) $hier = DailyScrumEntree::where('daily_scrum_id', $precedent->id)->where('user_id', $userId)->value('taches_jour');

        $taches = self::tachesDuJour($sprint, $userId, $date);
        $retard = self::tachesEnRetard($sprint, $userId, $date);
        $blocages = Blocage::whereNull('date_levee')->whereIn('tache_id', $sprint->taches()->where('user_id', $userId)->pluck('id'))->pluck('motif');
        $defis = $blocages->map(fn($m) => 'Blocage : ' . $m);
        if ($retard->isNotEmpty()) $defis->push($retard->count() . ' tâche(s) en retard non commencée(s)');

        return [
            'taches_hier' => $hier ?: null,
            'taches_jour' => self::puces($taches->pluck('intitule')) ?: null,
            'duree_restante' => self::dureeRestante($sprint, $taches, $date),
            'defis' => self::puces($defis) ?: null,
        ];
    }

    /**
     * Crée / complète les lignes du Daily pour chaque membre.
     * $ecraser : recalcule aussi les lignes existantes (les défis saisis sont conservés).
     */
    public static function remplir(DailyScrum $daily, bool $ecraser = false): int
    {
        $daily->loadMissing('sprint.projet');
        $n = 0;
        foreach (self::membres($daily->sprint) as $m) {
            $e = DailyScrumEntree::firstOrNew(['daily_scrum_id' => $daily->id, 'user_id' => $m->id]);
            if ($e->exists && !$ecraser) continue;
            $calc = self::calculerEntree($daily, $m->id);
            if ($e->exists && TexteRiche::texte($e->defis) !== '') unset($calc['defis']);
            $e->fill($calc)->save();
            $n++;
        }
        return $n;
    }

    /* ─── Sprint Planning / Review ─── */

    /**
     * Regroupe les tâches du sprint façon LEVRY : module → user story → tâches.
     * @return array<int, array{module:string, stories: array<int, array{story:?UserStory, taches:Collection}>}>
     */
    public static function planning(Sprint $sprint): array
    {
        $sprint->loadMissing(['taches.user', 'taches.userStory', 'userStories']);
        // Les stories du Sprint Backlog (avec leur avancement dans le sprint) priment sur celles rattachées par une tâche
        $stories = UserStory::trierParNumero($sprint->userStories->keyBy('id')->union($sprint->taches->pluck('userStory')->filter()->keyBy('id')))->values();
        $parModule = [];
        foreach ($stories as $us) {
            $parModule[$us->module ?: 'Sans module'][] = ['story' => $us, 'taches' => $sprint->taches->where('user_story_id', $us->id)->sortBy([['date_debut', 'asc'], ['id', 'asc']])->values()];
        }
        $hors = $sprint->taches->whereNull('user_story_id')->sortBy([['date_debut', 'asc'], ['id', 'asc']])->values();
        if ($hors->isNotEmpty()) $parModule['Tâches hors user story'][] = ['story' => null, 'taches' => $hors];

        $res = [];
        foreach ($parModule as $module => $groupes) $res[] = ['module' => $module, 'stories' => $groupes];
        return $res;
    }

    /** Statut de Review (vocabulaire LEVRY). */
    public static function statutReview(Tache $t): array
    {
        return match ($t->statut) {
            'termine' => $t->teste ? ['Réalisé et testé', 'ok'] : ['Réalisé', 'ok'],
            'en_cours' => ['En cours', 'cours'],
            'bloque' => ['Bloqué', 'ko'],
            'reporte' => ['Reporté', 'attente'],
            default => ['Non réalisé', 'ko'],
        };
    }

    /** Indicateurs de fin de sprint (Review / Rétrospective). */
    public static function indicateurs(Sprint $sprint): array
    {
        $taches = $sprint->taches;
        $total = $taches->count();
        $faites = $taches->where('statut', 'termine');
        $retard = $faites->filter(fn($t) => $t->echeance && $t->date_fin_reelle && $t->date_fin_reelle->gt($t->echeance))->count()
            + $taches->where('statut', '!=', 'termine')->filter(fn($t) => $t->echeance && $t->echeance->lt(today()))->count();
        $blocages = Blocage::whereIn('tache_id', $taches->pluck('id'));
        $pts = $sprint->pointsEngages();
        $ptsFaits = $sprint->pointsLivres();
        $stories = $sprint->userStories;
        return [
            'taches' => $total,
            'realisees' => $faites->count(),
            'testees' => $faites->where('teste', true)->count(),
            'taux' => $total ? (int) round($faites->count() / $total * 100) : 0,
            'points' => $pts,
            'points_faits' => $ptsFaits,
            'en_retard' => $retard,
            'blocages' => (clone $blocages)->count(),
            'blocages_ouverts' => (clone $blocages)->whereNull('date_levee')->count(),
            'stories' => $stories->count(),
            'stories_terminees' => $stories->where('pivot.statut_sprint', 'termine')->count(),
            'dailies' => $sprint->dailyScrums()->count(),
            'jours' => $sprint->dureeJours(),
        ];
    }
}
