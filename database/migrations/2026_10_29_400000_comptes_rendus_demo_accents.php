<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

/** CR Démo : titres par défaut des modules et sections enregistrés sans accents. */
return new class extends Migration {
    private const TEXTES = [
        'Resultats du Sprint' => 'Résultats du sprint',
        'Presenter les livrables et le score du sprint' => 'Présenter les livrables et le score du sprint',
        'Taches realisees' => 'Tâches réalisées',
        'Analyse et prochaines etapes' => 'Analyse et prochaines étapes',
        'Evaluer la performance et planifier la suite' => 'Évaluer la performance et planifier la suite',
        'Actions a mener' => 'Actions à mener',
    ];

    public function up(): void
    {
        if (!Schema::hasTable('comptes_rendus')) return;
        $remplacements = [];
        foreach (self::TEXTES as $avant => $apres) {
            // Valeur JSON exacte (entre guillemets), pour ne toucher qu'aux titres par défaut
            $remplacements['"' . $avant . '"'] = json_encode($apres, JSON_UNESCAPED_UNICODE);
        }
        DB::table('comptes_rendus')->where(function ($q) {
            foreach (array_keys(self::TEXTES) as $t) $q->orWhere('donnees_json', 'like', '%' . $t . '%');
        })->orderBy('id')->each(function ($cr) use ($remplacements) {
            $json = strtr($cr->donnees_json, $remplacements);
            if ($json !== $cr->donnees_json && json_decode($json) !== null) {
                DB::table('comptes_rendus')->where('id', $cr->id)->update(['donnees_json' => $json]);
            }
        });
    }

    public function down(): void {}
};
