<?php
namespace App\Services;

use App\Models\{AffectationReunion, Blocage, ConfirmationTache, DailyScrum, DailyScrumEntree, Evenement, Sprint, SprintEquipe, Tache, TypeReunion, User, UserStory};
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

    /** Jours du calendrier du sprint : tous les jours travaillés (selon les horaires en vigueur). */
    public static function joursProgrammes(Sprint $sprint): Collection
    {
        $res = collect();
        for ($j = $sprint->date_debut->copy()->startOfDay(); $j->lte($sprint->date_fin); $j->addDay()) {
            if (\App\Services\CalendrierTravail::estJourTravaille($j)) $res->push($j->copy());
        }
        return $res;
    }

    /** Un Daily de sprint se tient un jour ouvré compris dans le sprint. */
    public static function estProgramme(Sprint $sprint, Carbon $date): bool
    {
        return \App\Services\CalendrierTravail::estJourTravaille($date) && $date->between($sprint->date_debut->copy()->startOfDay(), $sprint->date_fin->copy()->endOfDay());
    }

    /** Numéro du Daily = rang du jour ouvré dans le sprint (Daily 1 = premier jour du sprint). */
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
            'projet_id' => $sprint->projet_id,
            'numero_jour' => $numero, 'date_reunion' => $date->copy()->startOfDay(),
            'heure_debut' => $debut ??= $config['heure_debut'],
            // Fin = début + durée programmée, sauf heure de fin saisie
            'heure_fin' => $fin ?? Carbon::createFromFormat('H:i', substr($debut, 0, 5))->addMinutes((int) $config['duree'])->format('H:i'),
            'roles' => self::roles($sprint, $date, $numero), 'cree_par' => $creePar,
        ]);
        self::remplir($d);
        return $d;
    }

    /**
     * Crée et pré-remplit un Daily hors sprint, rattaché à un projet ou transversal,
     * pour les participants choisis (tâches du module Projets / Sprints et hors sprint).
     */
    public static function creerDailyHorsSprint(?int $projetId, Carbon $date, Collection $participants, ?int $creePar, ?string $objet = null, ?string $debut = null, ?string $fin = null): DailyScrum
    {
        $config = ($projetId ? \App\Models\Projet::find($projetId) : new \App\Models\Projet())->configDaily();
        $numero = DailyScrum::whereNull('sprint_id')->where('projet_id', $projetId)->whereDate('date_reunion', '<', $date)->count() + 1;
        $debut ??= $config['heure_debut'];
        $d = DailyScrum::create([
            'sprint_id' => null, 'projet_id' => $projetId, 'objet' => $objet,
            'numero_jour' => $numero, 'date_reunion' => $date->copy()->startOfDay(),
            'heure_debut' => $debut,
            'heure_fin' => $fin ?? Carbon::createFromFormat('H:i', substr($debut, 0, 5))->addMinutes((int) $config['duree'])->format('H:i'),
            'roles' => self::rolesPour($projetId, $date, $numero, $participants), 'cree_par' => $creePar,
        ]);
        foreach ($participants as $u) {
            DailyScrumEntree::create(['daily_scrum_id' => $d->id, 'user_id' => $u->id] + self::calculerEntree($d, $u->id));
        }
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
        return self::rolesPour($sprint->projet_id, $date, $numero, $membres ?? self::membres($sprint));
    }

    /** Rôles du Daily pour un projet (ou transversal) : planning des réunions, scribe du jour, puis rotation. */
    public static function rolesPour(?int $projetId, Carbon $date, int $numero, Collection $membres): array
    {
        $roles = [];

        $type = TypeReunion::where('code', 'daily_scrum')->first();
        if ($type) {
            $affectations = AffectationReunion::with('role')->where('type_reunion_id', $type->id)
                ->whereDate('date_reunion', $date)
                ->where(fn($q) => $q->whereNull('projet_id')->when($projetId, fn($p) => $p->orWhere('projet_id', $projetId)))
                ->orderByRaw('projet_id is null')->get();
            foreach ($affectations as $a) {
                $code = $a->role?->code;
                if (!$code || !isset(DailyScrum::ROLES[$code]) || isset($roles[$code])) continue;
                $uid = $a->statut === 'remplace' && $a->remplacant_id ? $a->remplacant_id : $a->user_id;
                if ($a->statut !== 'absent' && $uid) $roles[$code] = (int) $uid;
            }
        }

        // Le scribe du jour (planning des réunions, toutes réunions confondues) est le rédacteur des rapports
        if (!isset($roles['scribe']) && ($scribe = RedacteurDuJour::id($date, $projetId))) $roles['scribe'] = $scribe;

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

    /** Nombre maximal de tâches listées par case du Daily (au-delà : « … et N autre(s) »). */
    public const MAX_TACHES_DAILY = 5;

    /**
     * Tâches du jour d'un membre : tâches commencées (en cours, à valider, bloquées)
     * et tâches prévues ce jour-là (date de début passée, échéance non dépassée).
     * Les tâches en retard non commencées sont signalées à part dans les défis.
     */
    public static function tachesDuJour(Sprint $sprint, int $userId, Carbon $date): Collection
    {
        return self::actives($sprint->taches()->where('user_id', $userId), $date)->get();
    }

    /** Filtre « tâches du jour » : commencées, ou prévues à la date ; les urgences T3 restent visibles tant qu'actives. */
    private static function actives($q, Carbon $date)
    {
        return $q->whereNotIn('statut', ['termine', 'reporte'])
            ->where(fn($w) => $w->where('type', 'T3')->orWhereIn('statut', ['en_cours', 'a_valider', 'bloque'])
                ->orWhere(fn($p) => $p->whereDate('date_debut', '<=', $date)
                    ->where(fn($e) => $e->whereNull('echeance')->orWhereDate('echeance', '>=', $date))))
            ->orderByRaw("type = 'T3' desc")->orderByRaw("statut = 'en_cours' desc")->orderBy('echeance')->orderBy('id');
    }

    /**
     * Périmètre des tâches d'un Daily.
     * Sprint : tâches du sprint + tâches hors sprint. Hors sprint : tâches du module Projets / Sprints
     * (limitées au projet s'il y en a un) + tâches hors sprint ; $enCours ne garde que les sprints en cours.
     */
    public static function perimetre(DailyScrum $daily, bool $enCours = false): \Closure
    {
        if ($daily->sprint_id) return fn($q) => $q->where(fn($w) => $w->where('sprint_id', $daily->sprint_id)->orWhereNull('sprint_id'));
        return fn($q) => $q->where(fn($w) => $w->whereNull('sprint_id')->orWhere(fn($t) => $t->whereNotNull('sprint_id')
            ->when($daily->projet_id, fn($p) => $p->where('projet_id', $daily->projet_id))
            ->when($enCours, fn($p) => $p->whereHas('sprint', fn($sp) => $sp->where('statut', 'en_cours')))));
    }

    /** Daily précédent dans le même contexte (même sprint, ou même projet hors sprint). */
    public static function dailyPrecedent(DailyScrum $daily): ?DailyScrum
    {
        return DailyScrum::where('id', '!=', $daily->id)->whereDate('date_reunion', '<', $daily->date_reunion)
            ->when($daily->sprint_id, fn($q) => $q->where('sprint_id', $daily->sprint_id),
                fn($q) => $q->whereNull('sprint_id')->where('projet_id', $daily->projet_id))
            ->orderByDesc('date_reunion')->first();
    }


    /**
     * Tâches hors sprint du membre (T2 planifiées, T3 urgentes), selon les mêmes règles que la saisie quotidienne :
     * prévues à cette date ou commencées ; les urgences T3 restent visibles tant qu'elles sont actives.
     */
    public static function tachesHorsSprint(int $userId, Carbon $date): Collection
    {
        return Tache::where('user_id', $userId)->whereNull('sprint_id')
            ->whereIn('statut', ['a_faire', 'en_cours', 'a_valider', 'bloque'])
            ->where(fn($q) => $q->where('type', 'T3')->orWhereIn('statut', ['en_cours', 'a_valider', 'bloque'])
                ->orWhere(fn($p) => $p->whereDate('date_debut', '<=', $date)
                    ->where(fn($e) => $e->whereNull('echeance')->orWhereDate('echeance', '>=', $date))))
            ->orderByRaw("type = 'T3' desc")->orderByRaw("FIELD(priorite,'haute','moyenne','basse')")->orderBy('echeance')->get();
    }

    /** Tâches à faire du périmètre dont l'échéance est dépassée sans avoir été commencées. */
    public static function tachesEnRetard(\Closure $perimetre, int $userId, Carbon $date): Collection
    {
        return Tache::where('user_id', $userId)->where('statut', 'a_faire')->where($perimetre)
            ->whereNotNull('echeance')->whereDate('echeance', '<', $date)->get(['id', 'intitule']);
    }

    /** Absences déclarées (saisie quotidienne) couvrant la date, par collaborateur. */
    public static function absences(Carbon $date, $userIds): Collection
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('absences')) return collect();
        return \App\Models\Absence::whereIn('user_id', $userIds)->whereDate('date_debut', '<=', $date)->whereDate('date_fin', '>=', $date)->get()->keyBy('user_id');
    }

    /**
     * Libellé d'une tâche dans le Daily. Dans un Daily de sprint, les tâches hors sprint sont signalées ;
     * dans un Daily hors sprint, les tâches de sprint indiquent leur sprint (et leur projet si le Daily est transversal).
     */
    public static function libelleTache(Tache $t, ?DailyScrum $daily = null): string
    {
        if (!$t->sprint_id) return $t->intitule . ' (hors sprint' . ($t->type === 'T3' ? ', urgence' : '') . ')';
        if (!$daily || $daily->sprint_id) return $t->intitule;
        $t->loadMissing(['sprint:id,numero', 'projet:id,code']);
        return $t->intitule . ' (' . ($daily->projet_id ? '' : ($t->projet?->code ?? '') . ' ') . 'Sprint ' . $t->sprint?->numero . ')';
    }

    /** Durée restante = durée prévue − jours écoulés, soit les jours ouvrés jusqu'à l'échéance la plus lointaine. */
    public static function dureeRestante(?Sprint $sprint, Collection $taches, Carbon $date): string
    {
        $restes = $taches->map(function ($t) use ($sprint, $date) {
            $fin = $t->echeance ?? $t->sprint?->date_fin ?? $sprint?->date_fin;
            if (!$fin) return null;
            return $fin->lt($date) ? -Tache::joursOuvres($fin->copy()->addDay(), $date) : Tache::joursOuvres($date, $fin);
        })->filter(fn($v) => $v !== null);
        if ($restes->isEmpty()) return '0 j';
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

        // Période couverte : depuis le Daily précédent (à défaut, depuis le jour ouvré précédent)
        $precedent = self::dailyPrecedent($daily);
        $depuis = $precedent?->date_reunion ?? $date->copy()->subWeekday();
        $dansSprintOuHors = self::perimetre($daily);

        // Hier = tâches terminées sur la période, sprint et hors sprint (dont les confirmations de la saisie quotidienne)
        $faites = Tache::where('user_id', $userId)->where('statut', 'termine')->where($dansSprintOuHors)
            ->whereNotNull('date_fin_reelle')->whereDate('date_fin_reelle', '>=', $depuis)->whereDate('date_fin_reelle', '<', $date)
            ->orderBy('date_fin_reelle')->get();
        $hier = self::puces($faites->map(fn($t) => self::libelleTache($t, $daily)));
        // Rien de terminé : on reprend le « à faire » prévu au Daily précédent
        if ($hier === '' && $precedent) $hier = DailyScrumEntree::where('daily_scrum_id', $precedent->id)->where('user_id', $userId)->value('taches_jour');

        // Ce jour : Daily de sprint = tâches du sprint puis hors sprint ; Daily hors sprint = tâches des sprints en cours et hors sprint
        $taches = $sprint
            ? self::tachesDuJour($sprint, $userId, $date)->concat(self::tachesHorsSprint($userId, $date))
            : self::actives(Tache::with('sprint:id,numero,date_fin')->where('user_id', $userId)->where(self::perimetre($daily, true)), $date)->get();
        $retard = self::tachesEnRetard(self::perimetre($daily, true), $userId, $date);

        // Défis = blocages ouverts, saisies manuelles de la saisie quotidienne (tâches non réalisées, événements), retards
        $ids = Tache::where('user_id', $userId)->where($dansSprintOuHors)->pluck('id');
        $defis = Blocage::whereNull('date_levee')->whereIn('tache_id', $ids)->pluck('motif')->map(fn($m) => 'Blocage : ' . $m);
        foreach (ConfirmationTache::with('tache:id,intitule')->where('user_id', $userId)->where('statut', 'infirmee')
            ->whereDate('date', '>=', $depuis)->whereDate('date', '<=', $date)->orderBy('date')->get() as $c) {
            $defis->push('Non réalisé : ' . ($c->tache?->intitule ?? 'tâche') . ($c->motif ? ' - ' . $c->motif : '') . ($c->plan_action ? ' (plan : ' . $c->plan_action . ')' : ''));
        }
        foreach (Evenement::where('user_id', $userId)->whereDate('date', '>=', $depuis)->whereDate('date', '<=', $date)->orderBy('date')->get() as $ev) {
            $defis->push($ev->labelType() . ' : ' . $ev->description);
        }
        if ($retard->isNotEmpty()) $defis->push($retard->count() . ' tâche(s) en retard non commencée(s)');

        return [
            'taches_hier' => $hier ?: null,
            'taches_jour' => self::puces($taches->map(fn($t) => self::libelleTache($t, $daily))) ?: null,
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
        // Daily hors sprint : les participants sont ceux déjà inscrits au Daily
        $participants = $daily->sprint ? self::membres($daily->sprint) : User::whereIn('id', $daily->entrees()->pluck('user_id'))->get();
        foreach ($participants as $m) {
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
            // Tâches hors sprint (T2 / urgences T3) réalisées par l'équipe pendant le sprint : capacité absorbée hors sprint
            'hors_sprint' => Tache::whereNull('sprint_id')->where('statut', 'termine')->whereIn('user_id', self::membres($sprint)->pluck('id'))
                ->whereBetween('date_fin_reelle', [$sprint->date_debut->toDateString(), $sprint->date_fin->toDateString()])->count(),
        ];
    }
}
