<?php
namespace App\Http\Controllers;

use App\Models\{CompteRendu, NotificationApp, Projet, Sprint, Tache, User, Blocage, Evenement, ConfirmationTache};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CompteRenduController extends Controller
{
    /**
     * Liste des comptes rendus (Reporting > Comptes rendus)
     * Pilotes : tous les CR  |  Collaborateur : ses CR (rédacteur)  |  DG : consultation seule
     */
    public function index(Request $r)
    {
        $user = $r->user();
        $query = CompteRendu::with(['redacteur', 'sprint'])->orderByDesc('date_reunion');

        if (!$user->estPilote() && !$user->aRole('direction')) {
            $query->where('redacteur_id', $user->id);
        }

        if ($type = $r->get('type')) {
            $query->where('type', $type);
        }

        $crs = $query->paginate(15)->withQueryString();

        return view('comptes-rendus.index', compact('crs', 'user'));
    }

    /**
     * Formulaire de création — pré-remplissage selon le type
     */
    public function create(Request $r)
    {
        $user = $r->user();
        abort_unless($user->estPilote(), 403);

        $type = $r->get('type', 'reunion_hebdo');
        $sprintId = $r->get('sprint_id');

        $collaborateurs = User::where('actif', true)
            ->where('role', '!=', 'direction')
            ->orderBy('name')->get();

        $sprints = Sprint::orderByDesc('date_debut')->get();
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();

        // Pré-remplissage des données selon le type
        $donnees = [];
        if ($type === 'reunion_hebdo') {
            $donnees = $this->preRemplirReunion();
        } elseif ($type === 'demo_sprint' && $sprintId) {
            $donnees = $this->preRemplirDemo($sprintId);
        }

        return view('comptes-rendus.create', compact(
            'type', 'collaborateurs', 'sprints', 'projets', 'donnees', 'sprintId', 'user'
        ));
    }

    /**
     * Désigner un rédacteur (pilote uniquement)
     */
    public function designer(Request $r)
    {
        $user = $r->user();
        abort_unless($user->estPilote(), 403);

        $v = $r->validate([
            'type'          => 'required|in:reunion_hebdo,demo_sprint',
            'redacteur_id'  => 'required|exists:users,id',
            'date_reunion'  => 'required|date',
            'titre'         => 'required|string|max:255',
            'lieu'          => 'nullable|string|max:255',
            'sprint_id'     => 'nullable|exists:sprints,id',
        ]);

        $cr = CompteRendu::create([
            'type'          => $v['type'],
            'titre'         => $v['titre'],
            'sprint_id'     => $v['sprint_id'] ?? null,
            'redacteur_id'  => $v['redacteur_id'],
            'designe_par'   => $user->id,
            'date_reunion'  => $v['date_reunion'],
            'lieu'          => $v['lieu'] ?? null,
            'statut'        => 'brouillon',
        ]);

        // Notification au rédacteur
        if (Schema::hasTable('notification_apps')) {
            NotificationApp::create([
                'user_id' => $v['redacteur_id'],
                'type'    => 'designation_cr',
                'message' => "Vous avez été désigné(e) rédacteur du {$cr->typeLibelle()} du " . $cr->date_reunion->format('d/m/Y'),
                'lien'    => route('comptes-rendus.edit', $cr),
            ]);
        }

        return redirect()->route('comptes-rendus.edit', $cr)
            ->with('ok', 'Rédacteur désigné. Le formulaire est pré-rempli.');
    }

    /**
     * Formulaire d'édition (rédacteur ou pilote)
     */
    public function edit(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless(
            $user->estPilote() || $cr->redacteur_id === $user->id,
            403
        );

        $collaborateurs = User::where('actif', true)->orderBy('name')->get();
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();

        // Si données vides, pré-remplir
        $donnees = $cr->getDonnees();
        if (empty($donnees)) {
            $donnees = $cr->type === 'reunion_hebdo'
                ? $this->preRemplirReunion()
                : ($cr->sprint_id ? $this->preRemplirDemo($cr->sprint_id) : []);
            $cr->setDonnees($donnees);
            $cr->save();
        }

        return view('comptes-rendus.edit', compact('cr', 'collaborateurs', 'projets', 'donnees', 'user'));
    }

    /**
     * Sauvegarder le contenu (brouillon)
     */
    public function update(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless(
            $user->estPilote() || $cr->redacteur_id === $user->id,
            403
        );

        $cr->setDonnees($r->input('donnees', []));
        $cr->titre = $r->input('titre', $cr->titre);
        $cr->lieu = $r->input('lieu', $cr->lieu);
        $cr->save();

        return redirect()->route('comptes-rendus.edit', $cr)
            ->with('ok', 'Brouillon enregistré.');
    }

    /**
     * Confirmer le CR (finaliser)
     */
    public function confirmer(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless(
            $user->estPilote() || $cr->redacteur_id === $user->id,
            403
        );

        $cr->statut = 'confirme';
        $cr->save();

        // Notifier le pilote qui a désigné
        if ($cr->designe_par !== $user->id && Schema::hasTable('notification_apps')) {
            NotificationApp::create([
                'user_id' => $cr->designe_par,
                'type'    => 'cr_confirme',
                'message' => "{$user->nomComplet()} a confirmé le {$cr->typeLibelle()} du " . $cr->date_reunion->format('d/m/Y'),
                'lien'    => route('comptes-rendus.show', $cr),
            ]);
        }

        return redirect()->route('comptes-rendus.show', $cr)
            ->with('ok', 'Compte rendu confirmé.');
    }

    /**
     * Voir un CR
     */
    public function show(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        $donnees = $cr->getDonnees();
        $cr->load(['redacteur', 'designePar', 'sprint']);

        return view('comptes-rendus.show', compact('cr', 'donnees', 'user'));
    }

    /**
     * Télécharger le DOCX généré
     */
    public function telecharger(CompteRendu $cr)
    {
        // Générer le DOCX à la volée si pas encore fait
        if (!$cr->fichier_path || !file_exists(storage_path('app/' . $cr->fichier_path))) {
            $this->genererDocx($cr);
        }

        $path = storage_path('app/' . $cr->fichier_path);
        $nom = 'CR_' . ($cr->type === 'reunion_hebdo' ? 'Reunion' : 'Demo')
             . '_' . $cr->date_reunion->format('d_m_Y') . '.docx';

        return response()->download($path, $nom);
    }

    /* ===================================================================
     *  PRÉ-REMPLISSAGE AUTOMATIQUE
     * =================================================================== */

    private function preRemplirReunion(): array
    {
        $semaine = Carbon::now()->subWeek();
        $projets = Projet::where('statut', 'actif')->with('responsable')->orderBy('libelle')->get();

        $diligences = [];
        foreach ($projets as $p) {
            $tachesTerminees = Tache::where('projet_id', $p->id)
                ->where('statut', 'termine')
                ->where('updated_at', '>=', $semaine)
                ->count();

            $tachesBloquees = 0;
            if (Schema::hasTable('blocages')) {
                $tachesBloquees = Blocage::whereHas('tache', fn($q) => $q->where('projet_id', $p->id))
                    ->whereNull('date_levee')
                    ->count();
            }

            $evenements = [];
            if (Schema::hasTable('evenements')) {
                $evenements = Evenement::where('projet_id', $p->id)
                    ->where('created_at', '>=', $semaine)
                    ->get()->map(fn($e) => $e->description)->toArray();
            }

            $diligences[] = [
                'projet'           => $p->libelle,
                'responsable'      => $p->responsable?->nomComplet() ?? '—',
                'taches_terminees' => $tachesTerminees,
                'taches_bloquees'  => $tachesBloquees,
                'evenements'       => $evenements,
                'observations'     => '',
            ];
        }

        $presences = User::where('actif', true)->orderBy('name')->get()
            ->map(fn($u) => ['nom' => $u->nomComplet(), 'present' => true])
            ->toArray();

        return [
            'presences'    => $presences,
            'diligences'   => $diligences,
            'decisions'    => [],
            'observations' => '',
        ];
    }

    private function preRemplirDemo(int $sprintId): array
    {
        $sprint = Sprint::with('projet')->findOrFail($sprintId);

        $taches = Tache::where('sprint_id', $sprintId)
            ->with('user')
            ->orderBy('type')->orderBy('intitule')
            ->get();

        $lignes = [];
        $totalPoints = 0;
        $pointsRealises = 0;

        foreach ($taches as $t) {
            $pts = $t->points ?? 1;
            $totalPoints += $pts;
            $termine = in_array($t->statut, ['termine', 'clos']);
            if ($termine) $pointsRealises += $pts;

            $lignes[] = [
                'tache'       => $t->intitule,
                'points'      => $pts,
                'attribution' => $t->user?->nomComplet() ?? '—',
                'statut'      => $t->statut,
                'termine'     => $termine,
                'observation' => $t->qualification ?? '',
            ];
        }

        // Score par développeur
        $parDev = [];
        foreach ($taches->groupBy('user_id') as $userId => $group) {
            $u = $group->first()->user;
            $devTotal = $group->sum(fn($t) => $t->points ?? 1);
            $devFait = $group->filter(fn($t) => in_array($t->statut, ['termine', 'clos']))
                ->sum(fn($t) => $t->points ?? 1);
            $parDev[] = [
                'nom'       => $u?->nomComplet() ?? '—',
                'total'     => $devTotal,
                'realise'   => $devFait,
                'pourcent'  => $devTotal > 0 ? round($devFait / $devTotal * 100) : 0,
            ];
        }

        return [
            'sprint'          => $sprint->libelle ?? "Sprint #{$sprint->id}",
            'projet'          => $sprint->projet?->libelle ?? '—',
            'date_debut'      => $sprint->date_debut?->format('d/m/Y'),
            'date_fin'        => $sprint->date_fin?->format('d/m/Y'),
            'taches'          => $lignes,
            'total_points'    => $totalPoints,
            'points_realises' => $pointsRealises,
            'score_pourcent'  => $totalPoints > 0 ? round($pointsRealises / $totalPoints * 100) : 0,
            'par_dev'         => $parDev,
            'actions_a_mener' => [],
            'note_sprint'     => '',
        ];
    }

    /* ===================================================================
     *  GÉNÉRATION DOCX (native ZipArchive + SimpleXML)
     * =================================================================== */

    private function genererDocx(CompteRendu $cr): void
    {
        $donnees = $cr->getDonnees();
        $dir = storage_path('app/comptes-rendus');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'cr_' . $cr->id . '_' . $cr->date_reunion->format('Ymd') . '.docx';
        $path = $dir . '/' . $filename;

        if ($cr->type === 'reunion_hebdo') {
            $this->genererDocxReunion($cr, $donnees, $path);
        } else {
            $this->genererDocxDemo($cr, $donnees, $path);
        }

        $cr->fichier_path = 'comptes-rendus/' . $filename;
        $cr->save();
    }

    private function genererDocxReunion(CompteRendu $cr, array $d, string $path): void
    {
        $xml = $this->docxHeader();
        $xml .= $this->docxPara("COMPTE RENDU DE RÉUNION HEBDOMADAIRE", true, 28, 'center');
        $xml .= $this->docxPara("Date : " . $cr->date_reunion->format('d/m/Y') . ($cr->lieu ? " — Lieu : {$cr->lieu}" : ''), false, 12, 'center');
        $xml .= $this->docxPara("Rédacteur : " . $cr->redacteur?->nomComplet(), false, 12, 'center');
        $xml .= $this->docxPara('');

        // Présences
        $xml .= $this->docxPara("1. LISTE DE PRÉSENCE", true, 16);
        if (!empty($d['presences'])) {
            $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>'
                . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '</w:tblBorders></w:tblPr>';
            $xml .= $this->docxTableRow(['Collaborateur', 'Présent'], true);
            foreach ($d['presences'] as $p) {
                $xml .= $this->docxTableRow([$p['nom'], $p['present'] ? 'Oui' : 'Non']);
            }
            $xml .= '</w:tbl>';
        }
        $xml .= $this->docxPara('');

        // Diligences par projet
        $xml .= $this->docxPara("2. DILIGENCES PAR PROJET", true, 16);
        $i = 1;
        foreach ($d['diligences'] ?? [] as $dil) {
            $xml .= $this->docxPara("2.{$i}. {$dil['projet']} (resp. : {$dil['responsable']})", true, 13);
            $xml .= $this->docxPara("Tâches terminées cette semaine : {$dil['taches_terminees']}");
            $xml .= $this->docxPara("Blocages actifs : {$dil['taches_bloquees']}");
            if (!empty($dil['evenements'])) {
                $xml .= $this->docxPara("Événements : " . implode(', ', $dil['evenements']));
            }
            if (!empty($dil['observations'])) {
                $xml .= $this->docxPara("Observations : {$dil['observations']}");
            }
            $xml .= $this->docxPara('');
            $i++;
        }

        // Décisions
        $xml .= $this->docxPara("3. DÉCISIONS ET ACTIONS", true, 16);
        if (!empty($d['decisions'])) {
            $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>'
                . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '</w:tblBorders></w:tblPr>';
            $xml .= $this->docxTableRow(['Décision / Action', 'Responsable', 'Échéance'], true);
            foreach ($d['decisions'] as $dec) {
                $xml .= $this->docxTableRow([$dec['action'] ?? '', $dec['responsable'] ?? '', $dec['echeance'] ?? '']);
            }
            $xml .= '</w:tbl>';
        } else {
            $xml .= $this->docxPara("(À compléter par le rédacteur)");
        }

        if (!empty($d['observations'])) {
            $xml .= $this->docxPara('');
            $xml .= $this->docxPara("4. OBSERVATIONS GÉNÉRALES", true, 16);
            $xml .= $this->docxPara($d['observations']);
        }

        $xml .= $this->docxFooter();
        $this->ecrireDocx($path, $xml);
    }

    private function genererDocxDemo(CompteRendu $cr, array $d, string $path): void
    {
        $xml = $this->docxHeader();
        $xml .= $this->docxPara("COMPTE RENDU DE DÉMONSTRATION SPRINT", true, 28, 'center');
        $xml .= $this->docxPara("Sprint : " . ($d['sprint'] ?? '—') . " — Projet : " . ($d['projet'] ?? '—'), false, 12, 'center');
        $xml .= $this->docxPara("Période : " . ($d['date_debut'] ?? '') . " au " . ($d['date_fin'] ?? ''), false, 12, 'center');
        $xml .= $this->docxPara("Date démonstration : " . $cr->date_reunion->format('d/m/Y'), false, 12, 'center');
        $xml .= $this->docxPara('');

        // Tableau des tâches
        $xml .= $this->docxPara("1. RÉSULTATS PAR TÂCHE", true, 16);
        $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>'
            . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
            . '</w:tblBorders></w:tblPr>';
        $xml .= $this->docxTableRow(['Tâche', 'Pts', 'Attribution', 'État', 'Observation'], true);
        foreach ($d['taches'] ?? [] as $t) {
            $etat = $t['termine'] ? '✓' : '✗';
            $xml .= $this->docxTableRow([$t['tache'], (string)$t['points'], $t['attribution'], $etat, $t['observation']]);
        }
        $xml .= '</w:tbl>';
        $xml .= $this->docxPara('');

        // Score global
        $xml .= $this->docxPara("2. ÉVALUATION DU SPRINT", true, 16);
        $xml .= $this->docxPara("Score global : {$d['points_realises']}/{$d['total_points']} points ({$d['score_pourcent']}%)");
        $xml .= $this->docxPara('');

        // Par développeur
        if (!empty($d['par_dev'])) {
            $xml .= $this->docxPara("Score par développeur :", true, 13);
            $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>'
                . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '</w:tblBorders></w:tblPr>';
            $xml .= $this->docxTableRow(['Développeur', 'Réalisé', 'Total', '%'], true);
            foreach ($d['par_dev'] as $dev) {
                $xml .= $this->docxTableRow([$dev['nom'], (string)$dev['realise'], (string)$dev['total'], $dev['pourcent'] . '%']);
            }
            $xml .= '</w:tbl>';
        }
        $xml .= $this->docxPara('');

        // Actions
        $xml .= $this->docxPara("3. ACTIONS À MENER", true, 16);
        if (!empty($d['actions_a_mener'])) {
            $xml .= '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>'
                . '<w:top w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:left w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:right w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="000000"/>'
                . '</w:tblBorders></w:tblPr>';
            $xml .= $this->docxTableRow(['Action', 'Responsable', 'Échéance'], true);
            foreach ($d['actions_a_mener'] as $a) {
                $xml .= $this->docxTableRow([$a['action'] ?? '', $a['responsable'] ?? '', $a['echeance'] ?? '']);
            }
            $xml .= '</w:tbl>';
        } else {
            $xml .= $this->docxPara("(À compléter)");
        }

        if (!empty($d['note_sprint'])) {
            $xml .= $this->docxPara('');
            $xml .= $this->docxPara("4. NOTE POUR LE SPRINT SUIVANT", true, 16);
            $xml .= $this->docxPara($d['note_sprint']);
        }

        $xml .= $this->docxFooter();
        $this->ecrireDocx($path, $xml);
    }

    /* --- Helpers DOCX natif (ZipArchive) --- */

    private function docxHeader(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" '
            . 'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" '
            . 'xmlns:o="urn:schemas-microsoft-com:office:office" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            . 'xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" '
            . 'xmlns:v="urn:schemas-microsoft-com:vml" '
            . 'xmlns:wp="http://schemas.openxmlformats.org/drawingDocument/2006/wordprocessingDrawing" '
            . 'xmlns:w10="urn:schemas-microsoft-com:office:word" '
            . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            . 'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
            . 'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
            . 'xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" '
            . 'xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" '
            . 'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
            . 'mc:Ignorable="w14 wp14">'
            . '<w:body>';
    }

    private function docxFooter(): string
    {
        return '</w:body></w:document>';
    }

    private function docxPara(string $text, bool $bold = false, int $size = 11, string $align = 'left'): string
    {
        $sz = $size * 2;
        $jc = match ($align) { 'center' => 'center', 'right' => 'right', default => 'left' };
        $bTag = $bold ? '<w:b/>' : '';
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');

        return "<w:p><w:pPr><w:jc w:val=\"{$jc}\"/></w:pPr>"
            . "<w:r><w:rPr><w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/>{$bTag}</w:rPr>"
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    private function docxTableRow(array $cells, bool $header = false): string
    {
        $xml = '<w:tr>';
        foreach ($cells as $cell) {
            $escaped = htmlspecialchars((string)$cell, ENT_XML1, 'UTF-8');
            $bTag = $header ? '<w:b/>' : '';
            $shd = $header ? '<w:shd w:val="clear" w:color="auto" w:fill="1F4E79"/>' : '';
            $color = $header ? '<w:color w:val="FFFFFF"/>' : '';
            $xml .= "<w:tc><w:tcPr>{$shd}</w:tcPr><w:p><w:r><w:rPr><w:sz w:val=\"20\"/><w:szCs w:val=\"20\"/>{$bTag}{$color}</w:rPr>"
                . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p></w:tc>";
        }
        $xml .= '</w:tr>';
        return $xml;
    }

    private function ecrireDocx(string $path, string $documentXml): void
    {
        if (file_exists($path)) unlink($path);
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>');

        // _rels/.rels
        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');

        // word/_rels/document.xml.rels
        $zip->addFromString('word/_rels/document.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '</Relationships>');

        // word/document.xml
        $zip->addFromString('word/document.xml', $documentXml);

        $zip->close();
    }
}
