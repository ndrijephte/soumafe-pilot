<?php
namespace App\Console\Commands;

use App\Models\{AuditLog, Projet, Sprint, Tache, UserStory};
use App\Services\AvancementScrum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Réparation ponctuelle du Sprint 10 de CROU Connect.
 *
 * Le planning du sprint numérotait ses stories « US 1.1 » à « US 1.13 » ; l'import, qui reconnaissait alors une story
 * par son seul numéro, a rattaché les tâches aux stories US 1.1 à 1.13 de l'EPIC 1 (cités, bâtiments) au lieu des
 * stories US 8.1 à 8.13 de l'EPIC 8 (campagne d'admission), qui portent les story points du sprint.
 *
 * La commande :
 *  - déplace les tâches du sprint de US 1.k vers US 8.k et retire US 1.1 à 1.13 du Sprint Backlog ;
 *  - rattache les tâches restées sans story (US 18.1, 18.2, 8.13), d'après le planning ;
 *  - archive les doublons US 1.12 / 1.13 (intitulés identiques à 8.12 / 8.13) devenus vides ;
 *  - remet « à faire » US 1.1, terminée uniquement par les tâches du sprint 10 ;
 *  - recalcule les statuts. Tout est retrouvé par numéro et intitulé (jamais par identifiant) ; à la moindre
 *    situation inattendue, rien n'est modifié. Sans --appliquer, simple simulation.
 */
class ReparerSprintCrou10 extends Command
{
    protected $signature = 'scrum:reparer-crou-sprint10 {--appliquer : Enregistre la réparation (sinon simulation, rien n\'est modifié)}';
    protected $description = 'Répare les rattachements de tâches du Sprint 10 de CROU Connect (US 1.x -> US 8.x)';

    /** Tâches importées sans story : début d'intitulé => numéro de la story du planning. */
    private const ORPHELINES = [
        "Créer l'interface d'importation de PV" => 'US 18.1',
        "Faire des tests à grandeur nature sur l'importation de la liste des étudiants" => 'US 18.2',
        'Adapter ou corriger les éventuelles observations' => 'US 18.2',
        'Permettre la consultation des dossiers hors connexion' => 'US 8.13',
    ];

    public function handle(): int
    {
        $appliquer = (bool) $this->option('appliquer');
        $this->line($appliquer ? '<fg=yellow>RÉPARATION</> (les modifications seront enregistrées)' : '<fg=cyan>SIMULATION</> (rien n\'est enregistré ; relancez avec --appliquer)');

        $projet = Projet::where('code', 'CROU')->first();
        if (!$projet) return $this->stop('Projet de code « CROU » introuvable.');
        $sprint = Sprint::where('projet_id', $projet->id)->where('numero', 10)->first();
        if (!$sprint) return $this->stop("Sprint 10 introuvable dans {$projet->libelle}.");
        $this->info("{$projet->libelle} — Sprint 10 ({$sprint->statut}, {$sprint->date_debut->format('d/m/Y')} -> {$sprint->date_fin->format('d/m/Y')})");

        $us = fn(string $numero) => UserStory::where('projet_id', $projet->id)->where('numero', $numero)->first();
        $tachesDe = fn(?UserStory $s) => $s ? Tache::where('sprint_id', $sprint->id)->where('user_story_id', $s->id)->get() : collect();
        $dansSprint = fn(?UserStory $s) => $s && $sprint->userStories()->whereKey($s->id)->exists();

        // ── 1. Diagnostic des paires US 1.k -> US 8.k
        $paires = [];
        $lignes = [];
        for ($k = 1; $k <= 13; $k++) {
            $ancienne = $us("US 1.$k");
            $nouvelle = $us("US 8.$k");
            $tA = $tachesDe($ancienne);
            $tN = $tachesDe($nouvelle);
            $etat = match (true) {
                !$ancienne || !$nouvelle => 'introuvable',
                !$dansSprint($nouvelle) => 'US 8.' . $k . ' hors sprint',
                $tA->isEmpty() && $tN->isNotEmpty() => 'déjà réparée',
                $tA->isEmpty() => 'sans tâche',
                $tN->isNotEmpty() => 'tâches des deux côtés',
                default => 'à déplacer',
            };
            if ($etat === 'à déplacer') $paires[] = [$ancienne, $nouvelle, $tA];
            $lignes[] = ["US 1.$k -> US 8.$k", $etat, $tA->count(), $nouvelle?->effort_estime ?? '-',
                mb_strimwidth((string) $nouvelle?->intitule, 0, 55, '…'), mb_strimwidth((string) $tA->first()?->intitule, 0, 45, '…')];
        }
        $this->table(['Paire', 'État', 'Tâches', 'Pts 8.k', 'Story cible (EPIC 8)', '1re tâche déplacée'], $lignes);

        $bloquants = collect($lignes)->filter(fn($l) => in_array($l[1], ['introuvable', 'tâches des deux côtés'], true) || str_ends_with($l[1], 'hors sprint'));
        if ($bloquants->isNotEmpty()) {
            return $this->stop('Situation différente de celle constatée en local (' . $bloquants->map(fn($l) => "{$l[0]} : {$l[1]}")->join(' ; ') . '). Aucune modification : transmettez cette sortie.');
        }

        $avant = $this->points($sprint);
        DB::beginTransaction();
        try {
            // ── 2. Déplacement des tâches et retrait des stories EPIC 1 du sprint
            $deplacees = 0;
            foreach ($paires as [$ancienne, $nouvelle, $taches]) {
                $deplacees += Tache::whereIn('id', $taches->pluck('id'))->update(['user_story_id' => $nouvelle->id]);
            }
            $retirees = [];
            for ($k = 1; $k <= 13; $k++) {
                $ancienne = $us("US 1.$k");
                if ($dansSprint($ancienne) && $tachesDe($ancienne)->isEmpty()) {
                    $sprint->userStories()->detach($ancienne->id);
                    $retirees[] = $ancienne->numero;
                }
            }

            // ── 3. Tâches sans story
            $rattachees = 0;
            foreach (self::ORPHELINES as $debut => $numero) {
                $cible = $us($numero);
                if (!$cible) { $this->warn("{$numero} introuvable : tâches « {$debut}… » laissées sans story."); continue; }
                $n = Tache::where('sprint_id', $sprint->id)->whereNull('user_story_id')->where('intitule', 'like', addcslashes($debut, '%_') . '%')->update(['user_story_id' => $cible->id]);
                if ($n && !$dansSprint($cible)) $sprint->userStories()->attach($cible->id, ['statut_sprint' => 'a_faire']);
                $rattachees += $n;
            }

            // ── 4. Doublons US 1.12 / 1.13 : archivés s'ils sont identiques à 8.12 / 8.13 et vides
            $archivees = [];
            foreach (['US 1.12' => 'US 8.12', 'US 1.13' => 'US 8.13'] as $num => $garde) {
                $d = $us($num);
                $g = $us($garde);
                if (!$d || !$g || $d->estArchivee()) continue;
                if (!UserStory::memeIntitule($d->intitule, $g->intitule)) { $this->warn("{$num} : intitulé différent de {$garde}, non archivée."); continue; }
                if ($d->sprints()->exists() || Tache::where('user_story_id', $d->id)->exists()) { $this->warn("{$num} : encore engagée ou avec des tâches, non archivée."); continue; }
                $d->updateQuietly(['archivee_le' => now(), 'archive_motif' => "Doublon de {$garde} créé par l'import du planning du Sprint 10", 'statut' => 'a_faire']);
                $archivees[] = $num;
            }

            // ── 5. Statuts recalculés (EPIC 1 d'après leurs sprints d'origine, EPIC 8 et 18 d'après le sprint 10)
            $ids = collect(range(1, 13))->flatMap(fn($k) => [$us("US 1.$k")?->id, $us("US 8.$k")?->id])
                ->merge([$us('US 18.1')?->id, $us('US 18.2')?->id])->filter()->unique();
            foreach ($ids as $id) AvancementScrum::synchroniser($id);
            // US 1.1 n'était « terminée » que par les tâches du sprint 10 : sans tâche ni niveau déclaré, elle revient « à faire »
            $u11 = $us('US 1.1');
            $u11Remise = false;
            if ($u11 && in_array('US 1.1', array_map(fn($p) => $p[0]->numero, $paires), true)
                && $u11->statut === 'terminee' && $u11->avancement === null && Tache::where('user_story_id', $u11->id)->doesntExist()) {
                $u11->updateQuietly(['statut' => 'a_faire']);
                $u11Remise = true;
            }

            $sprint->unsetRelation('userStories');
            $apres = $this->points(Sprint::find($sprint->id));
            $resteSansStory = Tache::where('sprint_id', $sprint->id)->whereNull('user_story_id')->count();

            $this->newLine();
            $this->table(['Opération', 'Résultat'], [
                ['Tâches déplacées US 1.x -> US 8.x', $deplacees],
                ['Stories retirées du Sprint Backlog', implode(', ', $retirees) ?: '—'],
                ['Tâches sans story rattachées', $rattachees . ($resteSansStory ? " (reste {$resteSansStory} sans story)" : '')],
                ['Doublons archivés', implode(', ', $archivees) ?: '—'],
                ['US 1.1 remise « à faire »', $u11Remise ? 'oui' : 'non'],
                ['Story points (engagés · livrés · réalisés · non estimées)', "{$avant}  ->  {$apres}"],
            ]);

            if (!$deplacees && !$rattachees && !$archivees && !$retirees) {
                DB::rollBack();
                $this->info('Rien à réparer : le sprint 10 est déjà dans l\'état attendu.');
                return self::SUCCESS;
            }
            if (!$appliquer) {
                DB::rollBack();
                $this->line('<fg=cyan>SIMULATION terminée : rien n\'a été enregistré.</> Si le résultat est celui attendu, relancez avec --appliquer.');
                return self::SUCCESS;
            }
            AuditLog::log('reparation_sprint', 'sprints', "{$projet->libelle} - Sprint 10",
                "Tâches du planning rattachées par erreur aux stories US 1.1-1.13 (numérotation du fichier) : {$deplacees} déplacée(s) vers US 8.1-8.13, "
                . "{$rattachees} tâche(s) sans story rattachée(s) (US 18.1, 18.2, 8.13), retirées du sprint : " . (implode(', ', $retirees) ?: 'aucune')
                . ', doublons archivés : ' . (implode(', ', $archivees) ?: 'aucun') . ($u11Remise ? ', US 1.1 remise à faire' : '') . ". Points : {$avant} -> {$apres}");
            DB::commit();
            $this->info('Réparation enregistrée (journal d\'audit : « reparation_sprint »).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->stop('Erreur, rien n\'a été modifié : ' . $e->getMessage());
        }
    }

    private function points(Sprint $s): string
    {
        return "{$s->pointsEngages()} · {$s->pointsLivres()} · {$s->pointsRealises()} · {$s->storiesNonEstimees()}";
    }

    private function stop(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }
}
