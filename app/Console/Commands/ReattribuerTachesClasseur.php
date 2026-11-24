<?php
namespace App\Console\Commands;

use App\Models\{AuditLog, Projet, ProjetCollaborateur, Sprint, Tache, User};
use App\Services\ImportConsolide;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Réattribue les tâches des sprints d'un projet d'après les plannings d'un classeur Scrum consolidé.
 *
 * Corrige les imports faits quand l'import consolidé ne reconnaissait que les noms de famille : « Rodrigue » (prénom)
 * n'était pas reconnu et la tâche revenait à la personne qui importait ; « Koffi » était pris pour le nom de KOFFI
 * au lieu du prénom de KOUDJODJI Koffi ; « RODRIGUE ET KOFFI » ne donnait qu'une tâche.
 *
 * Pour chaque tâche du classeur (même intitulé dans le même sprint) :
 *  - déjà attribuée aux bonnes personnes : inchangée ;
 *  - attribuée à une autre personne : réattribuée ;
 *  - plusieurs personnes : une tâche par personne (la copie reprend statut, dates, story, points, qualification).
 * Les noms inconnus du classeur ne sont pas traités (signalés). Sans --appliquer, simple simulation.
 */
class ReattribuerTachesClasseur extends Command
{
    protected $signature = 'scrum:reattribuer-taches
        {projet : Code du projet (ex. CROU)}
        {fichier : Chemin du classeur Scrum consolidé (.xlsx)}
        {--sprints= : Numéros des sprints à traiter, séparés par des virgules (par défaut : les sprints clôturés du classeur)}
        {--appliquer : Enregistre les corrections (sinon simulation, rien n\'est modifié)}';
    protected $description = 'Réattribue les tâches des sprints d\'après les plannings d\'un classeur Scrum consolidé';

    public function handle(): int
    {
        $appliquer = (bool) $this->option('appliquer');
        $this->line($appliquer ? '<fg=yellow>CORRECTION</> (les modifications seront enregistrées)' : '<fg=cyan>SIMULATION</> (rien n\'est enregistré ; relancez avec --appliquer)');
        $projet = Projet::where('code', $this->argument('projet'))->first();
        if (!$projet) return $this->stop("Projet de code « {$this->argument('projet')} » introuvable. Codes : " . Projet::pluck('code')->join(', '));
        $fichier = $this->argument('fichier');
        if (!is_file($fichier)) return $this->stop("Classeur introuvable : {$fichier}");

        $lu = (new ImportConsolide($projet))->attributionsPlanning($fichier);
        $filtre = $this->option('sprints') ? array_map('intval', explode(',', $this->option('sprints'))) : null;
        $noms = User::get(['id', 'name', 'prenom'])->mapWithKeys(fn($u) => [$u->id => $u->nomComplet()]);
        if ($lu['tranches']) $this->warn('Noms à plusieurs personnes possibles (tranchés) : ' . collect($lu['tranches'])->map(fn($u, $n) => "« {$n} » → {$u}")->join(', '));
        if ($lu['inconnus']) $this->warn('Noms non reconnus (tâches laissées telles quelles) : ' . implode(', ', $lu['inconnus']));

        $bilan = ['inchangées' => 0, 'réattribuées' => 0, 'créées (une par personne)' => 0, 'absentes de l\'application' => 0, 'sans personne reconnue' => 0, 'en trop (laissées)' => 0];
        $lignes = [];
        $nouveauxMembres = [];
        $parSprint = [];

        DB::beginTransaction();
        try {
            foreach ($lu['sprints'] as $num => $taches) {
                if ($filtre && !in_array($num, $filtre, true)) continue;
                $sprint = Sprint::where('projet_id', $projet->id)->where('numero', $num)->first();
                if (!$sprint) { $this->warn("Sprint {$num} absent de l'application : ignoré."); continue; }
                // Un sprint ouvert se pilote dans l'application (son planning a pu évoluer depuis le classeur) : seulement sur demande
                if ($sprint->statut !== 'termine' && !$filtre) { $this->line("Sprint {$num} ({$sprint->statut}) : non traité (sprint ouvert ; désignez-le avec --sprints={$num} pour le corriger)."); continue; }
                $existantes = $sprint->taches()->orderBy('id')->get()->groupBy(fn($t) => ImportConsolide::cleTache($t->intitule));
                $avant = $sprint->taches()->with('user:id,name')->get()->countBy(fn($t) => $t->user->name ?? '?')->sortKeys();

                foreach ($taches as $cle => $info) {
                    $voulus = $info['users'];
                    $actuelles = $existantes->get($cle, collect())->values();
                    if ($actuelles->isEmpty()) { $bilan['absentes de l\'application']++; continue; }
                    if (!$voulus) { $bilan['sans personne reconnue']++; continue; }
                    // Comparaison avec multiplicité : une tâche inscrite deux fois pour la même personne vaut deux tâches
                    $a = $actuelles->pluck('user_id')->map(fn($x) => (int) $x)->sort()->values()->all();
                    $v = collect($voulus)->sort()->values()->all();
                    if ($a === $v) { $bilan['inchangées'] += $actuelles->count(); continue; }

                    // Tâches déjà à une bonne personne : gardées (une par occurrence attendue) ; les autres comblent les manquants
                    $reste = $voulus;
                    $aReattribuer = collect();
                    foreach ($actuelles as $t) {
                        $i = array_search((int) $t->user_id, $reste, true);
                        if ($i !== false) { unset($reste[$i]); $bilan['inchangées']++; }
                        else $aReattribuer->push($t);
                    }
                    $manquants = array_values($reste);
                    $modele = $actuelles->first();
                    $faits = [];
                    foreach ($manquants as $i => $uid) {
                        if (isset($aReattribuer[$i])) {
                            $t = $aReattribuer[$i];
                            $faits[] = ($noms[$t->user_id] ?? '?') . ' -> ' . $noms[$uid];
                            $t->update(['user_id' => $uid]);
                            $bilan['réattribuées']++;
                        } else {
                            // Ligne à plusieurs personnes : une tâche chacune ; les points restent sur la première (pas de points multipliés)
                            $copie = $modele->replicate();
                            $copie->user_id = $uid;
                            $copie->points = null;
                            $copie->save();
                            $faits[] = '+ ' . $noms[$uid];
                            $bilan['créées (une par personne)']++;
                        }
                        $nouveauxMembres[$uid] = min($nouveauxMembres[$uid] ?? $sprint->date_debut->toDateString(), $sprint->date_debut->toDateString());
                    }
                    $bilan['en trop (laissées)'] += max(0, $aReattribuer->count() - count($manquants));
                    $lignes[] = ["S{$num}", mb_strimwidth($info['intitule'], 0, 52, '…'), implode(' / ', array_unique($info['textes'])), implode(', ', $faits)];
                }
                $apres = $sprint->taches()->with('user:id,name')->get()->countBy(fn($t) => $t->user->name ?? '?')->sortKeys();
                $parSprint[] = ["Sprint {$num}", $avant->map(fn($n, $k) => "{$k} {$n}")->join(', '), $apres->map(fn($n, $k) => "{$k} {$n}")->join(', ')];
            }

            // Les personnes du classeur rejoignent l'équipe du projet (à partir de leur premier sprint)
            $ajoutes = [];
            foreach ($nouveauxMembres as $uid => $depuis) {
                $m = ProjetCollaborateur::where('projet_id', $projet->id)->where('user_id', $uid)->first();
                if (!$m) {
                    // Collaborateur parti depuis : membre de l'historique, retiré à sa date de départ
                    $u = User::find($uid);
                    $parti = !$u->actif && $u->date_depart;
                    ProjetCollaborateur::create(['projet_id' => $projet->id, 'user_id' => $uid, 'role_projet' => 'developpeur', 'date_ajout' => $depuis,
                        'actif' => !$parti, 'date_retrait' => $parti ? $u->date_depart : null]);
                    $ajoutes[] = $noms[$uid];
                } elseif ($m->date_ajout && $m->date_ajout->toDateString() > $depuis) {
                    $m->update(['date_ajout' => $depuis]);
                }
            }

            if ($lignes) $this->table(['Sprint', 'Tâche', 'Classeur', 'Correction'], $lignes);
            $this->table(['Sprint', 'Avant', 'Après'], $parSprint);
            $this->table(['Bilan', 'Tâches'], collect($bilan)->map(fn($n, $k) => [$k, $n])->values()->all());
            if ($ajoutes) $this->line('Ajoutés à l\'équipe du projet : ' . implode(', ', $ajoutes));
            // Membres de l'équipe sans plus aucune tâche dans le projet (souvent ajoutés par l'ancienne erreur)
            $sansTache = ProjetCollaborateur::where('projet_id', $projet->id)->where('actif', true)->get()
                ->filter(fn($m) => !Tache::where('projet_id', $projet->id)->where('user_id', $m->user_id)->exists())
                ->map(fn($m) => $noms[$m->user_id] ?? $m->user_id);
            if ($sansTache->isNotEmpty()) $this->warn('Membres de l\'équipe du projet sans aucune tâche (à retirer de l\'équipe si leur présence vient d\'une erreur d\'import) : ' . $sansTache->join(', '));

            $total = $bilan['réattribuées'] + $bilan['créées (une par personne)'];
            if (!$total) { DB::rollBack(); $this->info('Aucune correction nécessaire : les attributions correspondent au classeur.'); return self::SUCCESS; }
            if (!$appliquer) { DB::rollBack(); $this->line('<fg=cyan>SIMULATION terminée : rien n\'a été enregistré.</> Si le résultat est celui attendu, relancez avec --appliquer.'); return self::SUCCESS; }
            AuditLog::log('reattribution_taches', 'taches', $projet->code,
                'Attributions corrigées d\'après le classeur ' . basename($fichier) . " : {$bilan['réattribuées']} tâche(s) réattribuée(s), {$bilan['créées (une par personne)']} créée(s) (une par personne)"
                . ($ajoutes ? ', ajoutés à l\'équipe : ' . implode(', ', $ajoutes) : '') . '. ' . collect($parSprint)->map(fn($l) => "{$l[0]} : {$l[2]}")->join(' ; '));
            DB::commit();
            $this->info('Corrections enregistrées (journal d\'audit : « reattribution_taches »).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->stop('Erreur, rien n\'a été modifié : ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
        }
    }

    private function stop(string $message): int
    {
        $this->error($message);
        return self::FAILURE;
    }
}
