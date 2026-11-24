<?php
namespace App\Services;

use App\Models\{AuditLog, Projet, Sprint};
use Illuminate\Support\Facades\DB;

/**
 * Restauration de la structure du Sprint Planning (fonctionnalité → user story → tâches) d'après le classeur Scrum,
 * y compris pour un sprint clôturé, sans toucher à l'exécution : chaque tâche reçoit sa user story (si elle n'en a pas)
 * et sa fonctionnalité d'origine (champ « activité »), par écriture directe. Statuts des tâches et des stories,
 * Sprint Backlog, instantanés de clôture, story points et score restent tels quels ; c'est vérifié avant/après.
 */
final class RestaurationStructure
{
    /**
     * @param int[]|null $numeros sprints à traiter (par défaut : tous ceux du classeur présents dans l'application)
     * @return array{lignes: array, total: array, controle: array, modifie: bool}
     */
    public static function executer(Projet $projet, string $chemin, ?array $numeros = null, bool $appliquer = false): array
    {
        $structure = (new ImportConsolide($projet))->structurePlanning($chemin);
        $lignes = [];
        $total = ['stories' => 0, 'fonctionnalites' => 0, 'non_trouvees' => 0];
        $controle = [];

        DB::beginTransaction();
        try {
            foreach ($structure as $num => $taches) {
                if ($numeros && !in_array($num, $numeros, true)) continue;
                $sprint = Sprint::where('projet_id', $projet->id)->where('numero', $num)->first();
                if (!$sprint) continue;
                $avant = self::mesure($sprint);
                $existantes = $sprint->taches()->get(['id', 'intitule', 'user_id', 'user_story_id', 'activite'])->groupBy(fn($t) => ImportConsolide::cleTache($t->intitule));
                $nS = $nF = $absentes = 0;
                foreach ($taches as $cle => $info) {
                    $lot = $existantes->get($cle);
                    if (!$lot) { $absentes++; continue; }
                    foreach ($lot as $t) {
                        $maj = [];
                        if (!$t->user_story_id && $info['story']) $maj['user_story_id'] = $info['story'];
                        if (trim((string) $t->activite) === '' && $info['groupe']) $maj['activite'] = $info['groupe'];
                        if (!$maj) continue;
                        // Écriture directe : ni recalcul des statuts de story, ni répartition des points
                        DB::table('taches')->where('id', $t->id)->update($maj);
                        if (isset($maj['user_story_id'])) $nS++;
                        if (isset($maj['activite'])) $nF++;
                    }
                }
                $apres = self::mesure(Sprint::find($sprint->id));
                if ($nS || $nF || $absentes) $lignes[] = ["Sprint {$num}", $sprint->statut === 'termine' ? 'clôturé' : $sprint->statut, $nS, $nF, $absentes, $avant === $apres ? 'inchangés' : "MODIFIÉS : {$avant} -> {$apres}"];
                if ($avant !== $apres) $controle[] = "Sprint {$num}";
                $total['stories'] += $nS;
                $total['fonctionnalites'] += $nF;
                $total['non_trouvees'] += $absentes;
            }
            $modifie = $total['stories'] + $total['fonctionnalites'] > 0;
            // Garde-fou : la restauration ne doit jamais changer l'avancement ni le score
            if ($controle || !$appliquer || !$modifie) {
                DB::rollBack();
            } else {
                AuditLog::log('restauration_structure', 'sprints', $projet->code,
                    'Structure du Sprint Planning restaurée d\'après ' . basename($chemin) . " : {$total['stories']} tâche(s) rattachée(s) à leur user story, {$total['fonctionnalites']} à leur fonctionnalité ; avancement et story points inchangés.");
                DB::commit();
            }
            return compact('lignes', 'total', 'controle', 'modifie');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** Empreinte de l'exécution d'un sprint : tâches terminées, points engagés / livrés / réalisés. */
    private static function mesure(Sprint $s): string
    {
        $t = $s->taches()->get(['id', 'statut']);
        return $t->where('statut', 'termine')->count() . '/' . $t->count() . ' tâches · ' . $s->pointsEngages() . '/' . $s->pointsLivres() . '/' . $s->pointsRealises() . ' pts';
    }
}
