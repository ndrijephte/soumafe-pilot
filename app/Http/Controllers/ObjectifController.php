<?php
namespace App\Http\Controllers;

use App\Models\{Objectif, Projet, User};
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

        // Pilote (charge_operations, direction, admin) = vue complète
        if ($user->estPilote()) {
            $filtreUser = $r->get('user_id');
            $filtreProjet = $r->get('projet_id');
            $collaborateurs = User::where('actif', true)
                ->whereNotIn('role', ['invite', 'direction'])
                ->orderBy('name')->get();

            // Individuels
            $qI = Objectif::with(['user', 'projet'])->where('periode', $periode);
            if ($hasType) $qI->where('type', 'individuel');
            if ($filtreUser) $qI->where('user_id', $filtreUser);
            if ($filtreProjet) $qI->where('projet_id', $filtreProjet);
            $objectifsIndiv = $qI->orderBy('user_id')->orderByDesc('ponderation')->get();
            $parCollab = $objectifsIndiv->groupBy('user_id');

            // Projet
            $objectifsProjet = collect();
            $parProjet = collect();
            if ($hasType) {
                $qP = Objectif::with('projet')->where('periode', $periode)->where('type', 'projet');
                if ($filtreProjet) $qP->where('projet_id', $filtreProjet);
                $objectifsProjet = $qP->orderBy('projet_id')->orderByDesc('ponderation')->get();
                $parProjet = $objectifsProjet->groupBy('projet_id');
            }

            $scoreEquipe = Objectif::scoreGroupe($objectifsIndiv->merge($objectifsProjet));

            return view('objectifs.index', compact(
                'objectifsIndiv', 'objectifsProjet', 'parCollab', 'parProjet',
                'collaborateurs', 'projets', 'moisMenu', 'periode',
                'filtreUser', 'filtreProjet', 'scoreEquipe'
            ));
        }

        // Collaborateur
        $qMes = Objectif::with('projet')->where('user_id', $user->id)->where('periode', $periode);
        if ($hasType) $qMes->where('type', 'individuel');
        $mesIndiv = $qMes->orderByDesc('ponderation')->get();

        $objProjets = collect();
        if ($hasType) {
            $mesProjetsIds = $user->taches()->distinct()->pluck('projet_id')->filter();
            $objProjets = Objectif::with('projet')->where('type', 'projet')
                ->where('periode', $periode)->whereIn('projet_id', $mesProjetsIds)
                ->orderByDesc('ponderation')->get();
        }

        return view('objectifs.index', compact('mesIndiv', 'objProjets', 'projets', 'moisMenu', 'periode'));
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

        if ($type === 'individuel') {
            $rules['user_id'] = 'required|exists:users,id';
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
            'user_id' => $type === 'individuel' ? $r->user_id : $r->user()->id,
            'projet_id' => $r->projet_id ?: null,
            'source' => 'pilote',
            'libelle' => $r->libelle,
            'ponderation' => $r->ponderation,
            'periode' => $r->periode,
        ];
        if ($hasType) $data['type'] = $type;
        if ($hasDateDebut) $data['date_debut'] = $r->date_debut;
        if ($hasDateEcheance) $data['date_echeance'] = $r->date_echeance;

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

        if (!$user->estPilote()) {
            abort_unless((int) $objectif->user_id === (int) $user->id, 403);
            $r->validate(['avancement' => 'required|integer|min:0|max:100']);
            $avanc = (int) $r->avancement;
            $objectif->update(['avancement' => $avanc]);
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
        $data = $r->only($cols);
        $data['projet_id'] = $data['projet_id'] ?: null;
        if ($hasEval) $data['evaluation'] = $r->evaluation ?: $objectif->evaluerAuto();

        try {
            $objectif->update($data);
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('erreur', 'Erreur base de données — exécutez "php artisan migrate". Détail : ' . $e->getMessage())->withInput();
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
