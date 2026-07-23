<?php
namespace App\Http\Controllers;

use App\Models\{Objectif, Projet, User};
use App\Services\LecteurXlsx;
use Illuminate\Http\Request;

class ObjectifController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $periodesMenu = Objectif::periodesDisponibles();
        $periode = $r->get('periode', now()->format('Y-m'));
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();
        $filtreType = $r->get('type', 'tous'); // tous, individuel, projet

        if ($user->estPilote()) {
            $filtreUser = $r->get('user_id');
            $filtreProjet = $r->get('projet_id');
            $collaborateurs = User::where('actif', true)
                ->whereNotIn('role', ['direction', 'invite'])
                ->orderBy('name')->get();

            // Objectifs individuels
            $queryIndiv = Objectif::with(['user', 'projet'])->where('periode', $periode)->where('type', 'individuel');
            if ($filtreUser) $queryIndiv->where('user_id', $filtreUser);
            if ($filtreProjet) $queryIndiv->where('projet_id', $filtreProjet);
            $objectifsIndiv = $queryIndiv->orderBy('user_id')->orderByDesc('ponderation')->get();
            $parCollab = $objectifsIndiv->groupBy('user_id');

            // Objectifs projet (équipe)
            $queryProjet = Objectif::with('projet')->where('periode', $periode)->where('type', 'projet');
            if ($filtreProjet) $queryProjet->where('projet_id', $filtreProjet);
            $objectifsProjet = $queryProjet->orderBy('projet_id')->orderByDesc('ponderation')->get();
            $parProjet = $objectifsProjet->groupBy('projet_id');

            // Score équipe global
            $scoreEquipe = Objectif::scoreGroupe($objectifsIndiv->merge($objectifsProjet));

            return view('objectifs.index', compact(
                'objectifsIndiv', 'objectifsProjet', 'parCollab', 'parProjet',
                'collaborateurs', 'projets', 'periodesMenu', 'periode',
                'filtreUser', 'filtreProjet', 'filtreType', 'scoreEquipe'
            ));
        }

        // Vue collaborateur : voit ses objectifs individuels + objectifs projet de ses projets
        $mesIndiv = Objectif::with('projet')->where('user_id', $user->id)
            ->where('periode', $periode)->where('type', 'individuel')
            ->orderByDesc('ponderation')->get();

        $mesProjetsIds = $user->taches()->distinct()->pluck('projet_id')->filter();
        $objProjets = Objectif::with('projet')->where('type', 'projet')
            ->where('periode', $periode)->whereIn('projet_id', $mesProjetsIds)
            ->orderByDesc('ponderation')->get();

        return view('objectifs.index', compact('mesIndiv', 'objProjets', 'projets', 'periodesMenu', 'periode'));
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $type = $r->input('type', 'individuel');

        $rules = [
            'type' => 'required|in:individuel,projet',
            'projet_id' => 'nullable|exists:projets,id',
            'libelle' => 'required|string|max:255',
            'ponderation' => 'required|integer|min:0|max:100',
            'periode' => 'required|string|max:20',
            'date_echeance' => 'nullable|date',
        ];
        if ($type === 'individuel') {
            $rules['user_id'] = 'required|exists:users,id';
        } else {
            $rules['projet_id'] = 'required|exists:projets,id';
        }
        $r->validate($rules);

        // Vérifier somme pondération
        if ($type === 'individuel') {
            $somme = Objectif::where('user_id', $r->user_id)->where('periode', $r->periode)
                ->where('type', 'individuel')->sum('ponderation');
            if ($somme + $r->ponderation > 100) {
                return back()->with('erreur', "Pondération individuelle dépasserait 100% (actuel : {$somme}%).")->withInput();
            }
        }

        Objectif::create([
            'type' => $type,
            'user_id' => $type === 'individuel' ? $r->user_id : $r->user()->id,
            'projet_id' => $r->projet_id,
            'source' => 'pilote',
            'libelle' => $r->libelle,
            'ponderation' => $r->ponderation,
            'periode' => $r->periode,
            'date_echeance' => $r->date_echeance,
        ]);

        return back()->with('succes', 'Objectif ajouté.');
    }

    public function update(Request $r, Objectif $objectif)
    {
        $user = $r->user();

        // Collaborateur : peut seulement mettre à jour son avancement
        if (!$user->estPilote()) {
            abort_unless($objectif->user_id === $user->id, 403);
            $r->validate(['avancement' => 'required|integer|min:0|max:100']);
            $objectif->update([
                'avancement' => $r->avancement,
                'evaluation' => $objectif->evaluerAuto(),
            ]);
            // Recalculer l'évaluation après mise à jour avancement
            $objectif->refresh();
            $objectif->update(['evaluation' => $objectif->evaluerAuto()]);
            return back()->with('succes', 'Avancement mis à jour.');
        }

        // Pilote : modification complète
        $r->validate([
            'libelle' => 'required|string|max:255',
            'projet_id' => 'nullable|exists:projets,id',
            'ponderation' => 'required|integer|min:0|max:100',
            'avancement' => 'nullable|integer|min:0|max:100',
            'date_echeance' => 'nullable|date',
            'evaluation' => 'nullable|in:en_cours,atteint,partiel,non_atteint',
            'statut_validation' => 'nullable|in:en_attente,valide,rejete',
            'commentaire_pilote' => 'nullable|string|max:500',
        ]);

        if ($objectif->type === 'individuel') {
            $somme = Objectif::where('user_id', $objectif->user_id)
                ->where('periode', $objectif->periode)
                ->where('type', 'individuel')
                ->where('id', '!=', $objectif->id)
                ->sum('ponderation');
            if ($somme + $r->ponderation > 100) {
                return back()->with('erreur', "Pondération dépasserait 100% (autres : {$somme}%).")->withInput();
            }
        }

        $data = $r->only(['libelle', 'projet_id', 'ponderation', 'avancement', 'date_echeance', 'statut_validation', 'commentaire_pilote']);
        // Évaluation : manuelle si fournie, sinon auto
        $data['evaluation'] = $r->evaluation ?: $objectif->evaluerAuto();
        $objectif->update($data);

        return back()->with('succes', 'Objectif mis à jour.');
    }

    /** Évaluer en lot les objectifs d'une période (fin de mois). */
    public function evaluer(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate(['periode' => 'required|string|max:20']);

        $objectifs = Objectif::where('periode', $r->periode)->get();
        $nb = 0;
        foreach ($objectifs as $obj) {
            $eval = $obj->evaluerAuto();
            if ($obj->evaluation !== $eval) {
                $obj->update(['evaluation' => $eval]);
                $nb++;
            }
        }

        return back()->with('succes', "{$nb} objectif(s) évalué(s) automatiquement.");
    }

    public function destroy(Request $r, Objectif $objectif)
    {
        abort_unless($r->user()->estPilote(), 403);
        $objectif->delete();
        return back()->with('succes', 'Objectif supprimé.');
    }

    /** Import xlsx : Collaborateur, Objectif, Pondération, Projet, Échéance (optionnels) */
    public function importer(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $r->validate([
            'fichier' => 'required|file|mimes:xlsx',
            'periode_import' => 'required|string|max:20',
        ]);

        $lecteur = new LecteurXlsx($r->file('fichier')->getRealPath());
        $brut = $lecteur->lire(1);

        $ligneEntete = 1;
        foreach ($brut as $i => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && preg_match('/objectif|libell|collabor/i', $cell)) {
                    $ligneEntete = $i + 1;
                    break 2;
                }
            }
        }

        $data = $lecteur->lire(1, $ligneEntete);
        $importees = 0; $ignores = 0;

        foreach ($data as $row) {
            $collabStr = trim($this->chercher($row, ['collaborateur', 'assigné', 'assigne', 'nom']) ?? '');
            $libelle = trim($this->chercher($row, ['objectif', 'libellé', 'libelle', 'intitulé', 'intitule']) ?? '');
            $pond = trim($this->chercher($row, ['pondération', 'ponderation', 'poids', '%']) ?? '');
            $projetStr = trim($this->chercher($row, ['projet']) ?? '');
            $echeance = trim($this->chercher($row, ['échéance', 'echeance', 'date_echeance', 'deadline']) ?? '');

            if (empty($libelle)) { $ignores++; continue; }

            $user = null;
            if ($collabStr) {
                $parts = preg_split('/\s+/', $collabStr, 2);
                $user = User::whereRaw('LOWER(name) = ?', [mb_strtolower($parts[0])])->first();
                if (!$user && isset($parts[1])) {
                    $user = User::whereRaw('LOWER(prenom) = ?', [mb_strtolower($parts[1])])->first();
                }
            }
            if (!$user) { $ignores++; continue; }

            $projet = null;
            if ($projetStr) {
                $projet = Projet::whereRaw('LOWER(libelle) = ?', [mb_strtolower($projetStr)])->first();
            }

            $pondVal = is_numeric($pond) ? min((int)$pond, 100) : 20;

            $sommeActuelle = Objectif::where('user_id', $user->id)->where('periode', $r->periode_import)
                ->where('type', 'individuel')->sum('ponderation');
            if ($sommeActuelle + $pondVal > 100) {
                $pondVal = max(0, 100 - $sommeActuelle);
                if ($pondVal === 0) { $ignores++; continue; }
            }

            $dateEch = null;
            if ($echeance && strtotime($echeance)) {
                $dateEch = date('Y-m-d', strtotime($echeance));
            }

            Objectif::create([
                'type' => 'individuel',
                'user_id' => $user->id,
                'projet_id' => $projet?->id,
                'source' => 'import',
                'libelle' => $libelle,
                'ponderation' => $pondVal,
                'periode' => $r->periode_import,
                'date_echeance' => $dateEch,
            ]);
            $importees++;
        }

        return back()->with('succes', "Import : {$importees} objectif(s) importé(s), {$ignores} ignoré(s).");
    }

    private function chercher(array $row, array $cles): ?string
    {
        foreach ($cles as $cle) {
            if (isset($row[$cle]) && $row[$cle] !== null) return (string) $row[$cle];
        }
        return null;
    }
}
