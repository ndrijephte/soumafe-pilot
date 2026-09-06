<?php
namespace App\Http\Controllers;

use App\Models\{Objectif, Projet, ResultatCle, Tache, User};
use App\Services\LecteurXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ObjectifController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $moisMenu = Objectif::moisDisponibles();
        $periode = $r->get('periode', now()->format('Y-m'));
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();
        $hasType = Schema::hasColumn('objectifs', 'type');

        $hasKR = Schema::hasTable('resultats_cles');
        $hasHist = Schema::hasTable('historique_avancement');
        $hasPivot = Schema::hasTable('objectif_tache');
        $hasParent = Schema::hasColumn('objectifs', 'parent_id');

        // Eager-load relations v2
        $eagerLoad = ['user', 'projet'];
        if ($hasKR) $eagerLoad[] = 'resultats';
        if ($hasHist) $eagerLoad[] = 'historique';
        if ($hasPivot) $eagerLoad[] = 'tachesLiees';
        if ($hasParent) { $eagerLoad[] = 'enfants'; $eagerLoad[] = 'parent'; }

        // Toutes les tâches actives pour le formulaire liaison
        $tachesActives = collect();
        if ($hasPivot) {
            $tachesActives = Tache::whereIn('statut', ['a_faire','en_cours','en_revue'])
                ->orderBy('intitule')->get(['id','intitule','statut','projet_id']);
        }

        // Objectifs parents possibles pour cascade
        $objectifsParents = collect();
        if ($hasParent) {
            $objectifsParents = Objectif::where('periode', $periode)
                ->whereNull('parent_id')
                ->orderBy('libelle')->get(['id','libelle','type']);
        }

        // Pilote (charge_operations, direction, admin) = vue complète
        if ($user->estPilote()) {
            $filtreUser = $r->get('user_id');
            $filtreProjet = $r->get('projet_id');
            $collaborateurs = User::where('actif', true)
                ->whereNotIn('role', ['invite', 'direction'])
                ->orderBy('name')->get();

            // Tous les objectifs (individuel + projet) — vue unifiée par collaborateur
            $q = Objectif::with($eagerLoad)->where('periode', $periode);
            if ($filtreUser) $q->where('user_id', $filtreUser);
            if ($filtreProjet) $q->where('projet_id', $filtreProjet);
            $tousObjectifs = $q->orderBy('user_id')->orderByDesc('ponderation')->get();
            $parCollab = $tousObjectifs->groupBy('user_id');

            $scoreEquipe = Objectif::scoreGroupe($tousObjectifs);

            // Score personnel du pilote (s'il a des objectifs assignés)
            $mesObjPilote = $tousObjectifs->where('user_id', $user->id);
            $monScore = $mesObjPilote->isNotEmpty() ? Objectif::scoreGroupe($mesObjPilote) : null;

            // Alertes agrégées
            $alertesObj = [];
            foreach ($tousObjectifs as $obj) {
                foreach ($obj->alertes() as $a) {
                    $alertesObj[] = array_merge($a, ['objectif' => $obj->libelle, 'user' => $obj->user?->nomComplet()]);
                }
            }

            return view('objectifs.index', compact(
                'tousObjectifs', 'parCollab',
                'collaborateurs', 'projets', 'moisMenu', 'periode',
                'filtreUser', 'filtreProjet', 'scoreEquipe',
                'monScore', 'mesObjPilote',
                'tachesActives', 'objectifsParents', 'alertesObj',
                'hasKR', 'hasHist', 'hasPivot', 'hasParent'
            ));
        }

        // Collaborateur — vue unifiée (individuel + projet)
        $mesObjectifs = Objectif::with($eagerLoad)
            ->where('user_id', $user->id)
            ->where('periode', $periode)
            ->orderByDesc('ponderation')->get();

        return view('objectifs.index', compact(
            'mesObjectifs', 'projets', 'moisMenu', 'periode',
            'hasKR', 'hasHist', 'hasPivot', 'hasParent'
        ));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $type = $r->input('type', 'individuel');

        $hasDateDebut = Schema::hasColumn('objectifs', 'date_debut');
        $hasDateEcheance = Schema::hasColumn('objectifs', 'date_echeance');
        $hasType = Schema::hasColumn('objectifs', 'type');

        $rules = [
            'libelle' => 'required|string|max:255',
            'ponderation' => 'required|integer|min:0|max:100',
            'periode' => 'required|string|max:20',
        ];
        if ($hasType) $rules['type'] = 'required|in:individuel,projet';
        if ($hasDateDebut) $rules['date_debut'] = 'required|date';
        if ($hasDateEcheance) $rules['date_echeance'] = 'required|date' . ($hasDateDebut ? '|after_or_equal:date_debut' : '');

        $rules['user_id'] = 'required|exists:users,id';
        if ($type === 'individuel') {
            $rules['projet_id'] = 'nullable|exists:projets,id';
        } else {
            $rules['projet_id'] = 'required|exists:projets,id';
        }
        $r->validate($rules);

        if ($type === 'individuel') {
            $q = Objectif::where('user_id', $r->user_id)->where('periode', $r->periode);
            if ($hasType) $q->where('type', 'individuel');
            $somme = $q->sum('ponderation');
            if ($somme + $r->ponderation > 100) {
                return back()->with('erreur', "Pondération individuelle dépasserait 100% (actuel : {$somme}%).")->withInput();
            }
        }

        $data = [
            'user_id' => $r->user_id,
            'projet_id' => $r->projet_id ?: null,
            'source' => 'pilote',
            'libelle' => $r->libelle,
            'ponderation' => $r->ponderation,
            'periode' => $r->periode,
        ];
        if ($hasType) $data['type'] = $type;
        if ($hasDateDebut) $data['date_debut'] = $r->date_debut;
        if ($hasDateEcheance) $data['date_echeance'] = $r->date_echeance;
        if (Schema::hasColumn('objectifs', 'parent_id') && $r->parent_id) $data['parent_id'] = $r->parent_id;
        if (Schema::hasColumn('objectifs', 'priorite') && $r->priorite) $data['priorite'] = $r->priorite;

        try {
            Objectif::create($data);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('erreur', 'Erreur base de données — exécutez "php artisan migrate" puis réessayez. Détail : ' . $e->getMessage())->withInput();
        }

        return back()->with('succes', 'Objectif ajouté.');
    }

    public function update(Request $r, Objectif $objectif)
    {
        $user = $r->user();
        $hasEval = Schema::hasColumn('objectifs', 'evaluation');

        // Auto-évaluation collaborateur
        if (!$user->estPilote() && $r->has('note_auto')) {
            abort_unless((int) $objectif->user_id === (int) $user->id, 403);
            $r->validate(['note_auto' => 'required|integer|min:1|max:5', 'commentaire_auto' => 'nullable|string|max:500']);
            $upd = ['note_auto' => $r->note_auto];
            if (Schema::hasColumn('objectifs', 'commentaire_auto')) $upd['commentaire_auto'] = $r->commentaire_auto;
            $objectif->update($upd);
            return back()->with('succes', 'Auto-évaluation enregistrée.');
        }

        if (!$user->estPilote()) {
            abort_unless((int) $objectif->user_id === (int) $user->id, 403);
            $r->validate(['avancement' => 'required|integer|min:0|max:100']);
            $avanc = (int) $r->avancement;
            $objectif->update(['avancement' => $avanc]);
            $objectif->snapshotHistorique('manuel');
            if ($hasEval) {
                $objectif->refresh();
                $objectif->update(['evaluation' => $objectif->evaluerAuto()]);
            }
            return back()->with('succes', 'Avancement mis à jour.');
        }

        $hasDateDebut = Schema::hasColumn('objectifs', 'date_debut');
        $hasDateEcheance = Schema::hasColumn('objectifs', 'date_echeance');
        $hasType = Schema::hasColumn('objectifs', 'type');

        $rules = [
            'libelle' => 'required|string|max:255',
            'projet_id' => 'nullable|exists:projets,id',
            'ponderation' => 'required|integer|min:0|max:100',
            'avancement' => 'nullable|integer|min:0|max:100',
            'statut_validation' => 'nullable|in:en_attente,valide,rejete',
            'commentaire_pilote' => 'nullable|string|max:500',
        ];
        if ($hasDateDebut) $rules['date_debut'] = 'nullable|date';
        if ($hasDateEcheance) $rules['date_echeance'] = 'nullable|date';
        if ($hasEval) $rules['evaluation'] = 'nullable|in:en_cours,atteint,partiel,non_atteint';
        $r->validate($rules);

        if ($hasType && $objectif->type === 'individuel') {
            $somme = Objectif::where('user_id', $objectif->user_id)
                ->where('periode', $objectif->periode)->where('type', 'individuel')
                ->where('id', '!=', $objectif->id)->sum('ponderation');
            if ($somme + $r->ponderation > 100) {
                return back()->with('erreur', "Pondération dépasserait 100% (autres : {$somme}%).")->withInput();
            }
        }

        $cols = ['libelle', 'projet_id', 'ponderation', 'avancement', 'statut_validation', 'commentaire_pilote'];
        if ($hasDateDebut) $cols[] = 'date_debut';
        if ($hasDateEcheance) $cols[] = 'date_echeance';
        if (Schema::hasColumn('objectifs', 'priorite')) $cols[] = 'priorite';
        if (Schema::hasColumn('objectifs', 'parent_id')) $cols[] = 'parent_id';
        if (Schema::hasColumn('objectifs', 'note_pilote')) $cols[] = 'note_pilote';
        $data = $r->only($cols);
        $data['projet_id'] = $data['projet_id'] ?: null;
        $data['parent_id'] = $data['parent_id'] ?? null ?: null;
        if ($hasEval) $data['evaluation'] = $r->evaluation ?: $objectif->evaluerAuto();
        if (Schema::hasColumn('objectifs', 'date_evaluation') && $r->note_pilote) {
            $data['date_evaluation'] = now()->toDateString();
        }

        $ancienAvanc = $objectif->avancement;

        try {
            $objectif->update($data);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('erreur', 'Erreur base de données — exécutez "php artisan migrate". Détail : ' . $e->getMessage())->withInput();
        }

        // Snapshot historique si avancement a changé
        if (isset($data['avancement']) && (int)$data['avancement'] !== $ancienAvanc) {
            $objectif->snapshotHistorique('manuel');
        }

        return back()->with('succes', 'Objectif mis à jour.');
    }

    public function evaluer(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        if (!Schema::hasColumn('objectifs', 'evaluation')) {
            return back()->with('erreur', 'Colonne evaluation absente — exécutez php artisan migrate.');
        }
        $r->validate(['periode' => 'required|string|max:20']);

        $nb = 0;
        foreach (Objectif::where('periode', $r->periode)->get() as $obj) {
            $eval = $obj->evaluerAuto();
            if ($obj->evaluation !== $eval) { $obj->update(['evaluation' => $eval]); $nb++; }
        }
        return back()->with('succes', "{$nb} objectif(s) évalué(s).");
    }

    public function valider(Request $r, Objectif $objectif)
    {
        abort_unless($r->user()->estPilote(), 403);
        $objectif->update(['statut_validation' => 'valide']);
        return back()->with('succes', 'Objectif validé.');
    }

    public function destroy(Request $r, Objectif $objectif)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $objectif->delete();
        return back()->with('succes', 'Objectif supprimé.');
    }

    public function importer(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'fichier' => 'required|file',
            'periode_import' => 'required|string|max:20',
        ]);

        $file = $r->file('fichier');
        $ext = strtolower($file->getClientOriginalExtension());

        if ($ext !== 'xlsx') {
            return back()->with('erreur', 'Format invalide. Seuls les fichiers .xlsx sont acceptés.')->withInput();
        }

        try {
            $lecteur = new LecteurXlsx($file->getRealPath());
            $brut = $lecteur->lire(1);
        } catch (\Exception $e) {
            return back()->with('erreur', 'Fichier illisible ou corrompu : ' . $e->getMessage())->withInput();
        }

        if (empty($brut)) {
            return back()->with('erreur', 'Le fichier est vide.')->withInput();
        }

        // Détecter l'en-tête
        $ligneEntete = 1;
        $enteteDetectee = false;
        foreach ($brut as $i => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && preg_match('/objectif|libell|collabor/i', $cell)) {
                    $ligneEntete = $i + 1;
                    $enteteDetectee = true;
                    break 2;
                }
            }
        }

        if (!$enteteDetectee) {
            return back()->with('erreur', 'En-tête introuvable. Le fichier doit contenir au minimum les colonnes "Collaborateur" et "Objectif".')->withInput();
        }

        $data = $lecteur->lire(1, $ligneEntete);
        if (empty($data)) {
            return back()->with('erreur', 'Aucune ligne de données après l\'en-tête.')->withInput();
        }

        $importees = 0; $ignores = 0; $erreurs = [];

        foreach ($data as $idx => $row) {
            $numLigne = $ligneEntete + $idx + 1;
            $collabStr = trim($this->chercher($row, ['collaborateur', 'assigné', 'assigne', 'nom']) ?? '');
            $libelle = trim($this->chercher($row, ['objectif', 'libellé', 'libelle', 'intitulé', 'intitule']) ?? '');
            $pond = trim($this->chercher($row, ['pondération', 'ponderation', 'poids', '%']) ?? '');
            $projetStr = trim($this->chercher($row, ['projet']) ?? '');
            $echStr = trim($this->chercher($row, ['échéance', 'echeance', 'date_echeance', 'date_fin', 'deadline']) ?? '');
            $debStr = trim($this->chercher($row, ['date_debut', 'début', 'debut']) ?? '');

            if (empty($libelle)) { $ignores++; continue; }

            $user = null;
            if ($collabStr) {
                $parts = preg_split('/\s+/', $collabStr, 2);
                $user = User::whereRaw('LOWER(name) = ?', [mb_strtolower($parts[0])])->first();
                if (!$user && isset($parts[1])) {
                    $user = User::whereRaw('LOWER(prenom) = ?', [mb_strtolower($parts[1])])->first();
                }
            }
            if (!$user) {
                $erreurs[] = "Ligne {$numLigne} : collaborateur \"{$collabStr}\" introuvable.";
                $ignores++; continue;
            }

            $projet = null;
            if ($projetStr) {
                $projet = Projet::whereRaw('LOWER(libelle) = ?', [mb_strtolower($projetStr)])->first();
                if (!$projet) $erreurs[] = "Ligne {$numLigne} : projet \"{$projetStr}\" introuvable, ignoré.";
            }

            $pondVal = is_numeric($pond) ? min((int)$pond, 100) : 20;
            $qPond = Objectif::where('user_id', $user->id)->where('periode', $r->periode_import);
            if (Schema::hasColumn('objectifs', 'type')) $qPond->where('type', 'individuel');
            $somme = $qPond->sum('ponderation');
            if ($somme + $pondVal > 100) {
                $pondVal = max(0, 100 - $somme);
                if ($pondVal === 0) { $erreurs[] = "Ligne {$numLigne} : pondération max atteinte pour {$collabStr}."; $ignores++; continue; }
            }

            $dateEch = ($echStr && strtotime($echStr)) ? date('Y-m-d', strtotime($echStr)) : null;
            $dateDeb = ($debStr && strtotime($debStr)) ? date('Y-m-d', strtotime($debStr)) : null;

            $dataImport = [
                'user_id' => $user->id,
                'projet_id' => $projet?->id,
                'source' => 'import',
                'libelle' => $libelle,
                'ponderation' => $pondVal,
                'periode' => $r->periode_import,
            ];
            if (Schema::hasColumn('objectifs', 'type')) $dataImport['type'] = 'individuel';
            if (Schema::hasColumn('objectifs', 'date_debut')) $dataImport['date_debut'] = $dateDeb;
            if (Schema::hasColumn('objectifs', 'date_echeance')) $dataImport['date_echeance'] = $dateEch;

            try {
                Objectif::create($dataImport);
                $importees++;
            } catch (\Illuminate\Database\QueryException $e) {
                $erreurs[] = "Ligne {$numLigne} : erreur base de données — " . $e->getMessage();
                $ignores++;
            }
        }

        $msg = "Import : {$importees} objectif(s) importé(s), {$ignores} ignoré(s).";
        if (!empty($erreurs)) {
            $msg .= ' Détails : ' . implode(' | ', array_slice($erreurs, 0, 5));
            if (count($erreurs) > 5) $msg .= ' (et ' . (count($erreurs) - 5) . ' autre(s))';
        }

        return back()->with($importees > 0 ? 'succes' : 'erreur', $msg);
    }

    // ==================== RÉSULTATS CLÉS ====================

    public function storeKR(Request $r, Objectif $objectif)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'libelle' => 'required|string|max:255',
            'type_mesure' => 'required|in:pourcentage,nombre,binaire,monnaie',
            'valeur_cible' => 'required|numeric|min:0',
            'unite' => 'nullable|string|max:30',
            'poids' => 'required|integer|min:1|max:100',
        ]);

        $ordre = $objectif->resultats()->count();

        try {
            $objectif->resultats()->create([
                'libelle' => $r->libelle,
                'type_mesure' => $r->type_mesure,
                'valeur_cible' => $r->valeur_cible,
                'unite' => $r->unite,
                'poids' => $r->poids,
                'ordre' => $ordre,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('erreur', 'Erreur — exécutez php artisan migrate. ' . $e->getMessage());
        }

        return back()->with('succes', 'Résultat clé ajouté.');
    }

    public function updateKR(Request $r, ResultatCle $kr)
    {
        abort_unless($r->user()->estPilote() || (int) $kr->objectif->user_id === (int) $r->user()->id, 403);

        // Collaborateur : ne peut mettre à jour que la valeur actuelle
        if (!$r->user()->estPilote()) {
            $r->validate(['valeur_actuelle' => 'required|numeric|min:0']);
            $kr->update(['valeur_actuelle' => $r->valeur_actuelle]);
            $kr->objectif->syncAvancementKR();
            return back()->with('succes', 'Progression mise à jour.');
        }

        $r->validate([
            'libelle' => 'required|string|max:255',
            'type_mesure' => 'required|in:pourcentage,nombre,binaire,monnaie',
            'valeur_cible' => 'required|numeric|min:0',
            'valeur_actuelle' => 'required|numeric|min:0',
            'unite' => 'nullable|string|max:30',
            'poids' => 'required|integer|min:1|max:100',
        ]);

        $kr->update($r->only(['libelle', 'type_mesure', 'valeur_cible', 'valeur_actuelle', 'unite', 'poids']));
        $kr->objectif->syncAvancementKR();

        return back()->with('succes', 'Résultat clé mis à jour.');
    }

    public function destroyKR(Request $r, ResultatCle $kr)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $objectif = $kr->objectif;
        $kr->delete();
        $objectif->syncAvancementKR();
        return back()->with('succes', 'Résultat clé supprimé.');
    }

    // ==================== LIAISON TÂCHES ====================

    public function lierTache(Request $r, Objectif $objectif)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['tache_id' => 'required|exists:taches,id']);

        if ($objectif->tachesLiees()->where('tache_id', $r->tache_id)->exists()) {
            return back()->with('erreur', 'Cette tâche est déjà liée à cet objectif.');
        }

        $objectif->tachesLiees()->attach($r->tache_id);
        return back()->with('succes', 'Tâche liée à l\'objectif.');
    }

    public function delierTache(Request $r, Objectif $objectif, Tache $tache)
    {
        abort_unless($r->user()->estPilote(), 403);
        $objectif->tachesLiees()->detach($tache->id);
        return back()->with('succes', 'Tâche retirée de l\'objectif.');
    }

    // ==================== VALIDATION INDICATEURS KPI ====================

    public function validerIndicateur(Request $r, ResultatCle $kr)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['valide' => 'required|in:0,1']);

        $kr->update([
            'valide' => (bool) $r->valide,
            'date_validation' => now()->toDateString(),
        ]);

        // Recalculer le verdict de l'objectif parent
        $objectif = $kr->objectif;
        $verdict = $objectif->verdictDepuisIndicateurs();
        if ($verdict !== 'en_cours' && $objectif->evaluation !== $verdict) {
            $objectif->update(['evaluation' => $verdict]);
        }
        // Sync avancement pondéré
        $objectif->syncAvancementKR();

        $label = $r->valide ? 'atteint' : 'non atteint';
        return back()->with('succes', "Indicateur marqué « {$label} ».");
    }

    public function resetIndicateur(Request $r, ResultatCle $kr)
    {
        abort_unless($r->user()->estPilote(), 403);
        $kr->update(['valide' => null, 'date_validation' => null]);

        $objectif = $kr->objectif;
        $objectif->update(['evaluation' => $objectif->verdictDepuisIndicateurs()]);

        return back()->with('succes', 'Validation de l\'indicateur réinitialisée.');
    }

    private function chercher(array $row, array $cles): ?string
    {
        // Recherche exacte
        foreach ($cles as $cle) {
            if (isset($row[$cle]) && $row[$cle] !== null) return (string) $row[$cle];
        }
        // Recherche sans accents (les en-têtes xlsx peuvent contenir des accents)
        $sansAccent = function (string $s): string {
            $map = ['é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ù'=>'u','û'=>'u','ô'=>'o','î'=>'i','ï'=>'i','ç'=>'c','É'=>'e','È'=>'e','Ê'=>'e','À'=>'a','Ô'=>'o','Î'=>'i','Ç'=>'c'];
            return strtr(mb_strtolower($s), $map);
        };
        $clesNorm = array_map($sansAccent, $cles);
        foreach ($row as $key => $val) {
            if ($val === null) continue;
            $keyNorm = $sansAccent((string) $key);
            foreach ($clesNorm as $cle) {
                if ($keyNorm === $cle || str_contains($keyNorm, $cle)) return (string) $val;
            }
        }
        return null;
    }
}
