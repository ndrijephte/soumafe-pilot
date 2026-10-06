<?php
namespace App\Console\Commands;

use App\Models\Chauffeur;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * Remplace, dans les saisies du parc, les noms de chauffeur qui ne correspondent pas au formulaire Chauffeurs
 * (ex. nom seul « BERTE » enregistré par l'ancienne liste « Autres chauffeurs ») par le nom complet « Prénom NOM ».
 * Seules les correspondances sans ambiguïté sont corrigées ; le reste est listé pour correction manuelle.
 */
class CorrigerNomsChauffeurs extends Command
{
    protected $signature = 'parc:corriger-noms-chauffeurs {--appliquer : Enregistre les corrections (sinon simple aperçu)}';
    protected $description = 'Aligne les noms de chauffeur des recettes, pénalités et recouvrements sur le formulaire Chauffeurs';

    public function handle(): int
    {
        $norm = fn(?string $s) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $s)));
        $chauffeurs = Chauffeur::all();
        $valides = $chauffeurs->map->nomComplet()->all();

        // Variantes reconnues pour chaque chauffeur : nom seul, prénom seul, « NOM Prénom »
        $variantes = [];
        foreach ($chauffeurs as $c) {
            foreach ([$c->nomComplet(), $c->nom, $c->prenom, trim($c->nom . ' ' . $c->prenom)] as $v) {
                if ($v === null || trim($v) === '') continue;
                $variantes[$norm($v)][] = $c->nomComplet();
            }
        }
        $variantes = array_map(fn($l) => array_values(array_unique($l)), $variantes);

        $cibles = [['recettes_vehicules', 'chauffeur'], ['penalites', 'chauffeur'], ['recouvrements', 'chauffeur_nom']];
        $aCorriger = 0; $ambigus = 0;
        foreach ($cibles as [$table, $col]) {
            if (!Schema::hasTable($table)) continue;
            $noms = DB::table($table)->whereNotNull($col)->where($col, '<>', '')->whereNotIn($col, $valides)
                ->select($col . ' as nom', DB::raw('COUNT(*) as nb'))->groupBy($col)->get();
            foreach ($noms as $n) {
                $cands = $variantes[$norm($n->nom)] ?? [];
                if (count($cands) === 1) {
                    $aCorriger += $n->nb;
                    $this->line("{$table}.{$col} : « {$n->nom} » ({$n->nb}) → « {$cands[0]} »");
                    if ($this->option('appliquer')) DB::table($table)->where($col, $n->nom)->update([$col => $cands[0]]);
                } else {
                    $ambigus += $n->nb;
                    $this->warn("{$table}.{$col} : « {$n->nom} » ({$n->nb}) → " . ($cands ? 'ambigu : ' . implode(' / ', $cands) : 'aucun chauffeur correspondant') . ', à corriger à la main');
                }
            }
        }

        if (!$aCorriger && !$ambigus) { $this->info('Tous les noms de chauffeur correspondent au formulaire Chauffeurs.'); return self::SUCCESS; }
        $this->info($this->option('appliquer')
            ? "{$aCorriger} saisie(s) corrigée(s)." . ($ambigus ? " {$ambigus} restent à corriger manuellement." : '')
            : "Aperçu : {$aCorriger} saisie(s) corrigeable(s). Relancer avec --appliquer pour enregistrer.");
        return self::SUCCESS;
    }
}
