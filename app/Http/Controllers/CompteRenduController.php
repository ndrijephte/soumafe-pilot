<?php
namespace App\Http\Controllers;

use App\Models\{CompteRendu, NotificationApp, Projet, Sprint, Tache, User, Blocage, Evenement};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CompteRenduController extends Controller
{
    /* ===== LISTE ===== */
    public function index(Request $r)
    {
        $user = $r->user();
        $query = CompteRendu::with(['redacteur', 'president', 'sprint'])->orderByDesc('date_reunion');

        if (!$user->estPilote() && !$user->aRole('direction')) {
            $query->where('redacteur_id', $user->id);
        }
        if ($type = $r->get('type')) $query->where('type', $type);

        $crs = $query->paginate(15)->withQueryString();
        return view('comptes-rendus.index', compact('crs', 'user'));
    }

    /* ===== CRÉATION (désignation) ===== */
    public function create(Request $r)
    {
        $user = $r->user();
        abort_unless($user->estPilote(), 403);

        $type = $r->get('type', 'reunion_ordinaire');
        $sprintId = $r->get('sprint_id');
        $collaborateurs = User::where('actif', true)->where('role', '!=', 'direction')->orderBy('name')->get();
        $tousUsers = User::where('actif', true)->orderBy('name')->get();
        $sprints = Sprint::orderByDesc('date_debut')->get();

        return view('comptes-rendus.create', compact('type', 'collaborateurs', 'tousUsers', 'sprints', 'sprintId', 'user'));
    }

    public function designer(Request $r)
    {
        $user = $r->user();
        abort_unless($user->estPilote(), 403);

        $v = $r->validate([
            'type'          => 'required|in:reunion_ordinaire,reunion_extraordinaire,demo_sprint',
            'redacteur_id'  => 'required|exists:users,id',
            'president_id'  => 'nullable|exists:users,id',
            'date_reunion'  => 'required|date',
            'heure_debut'   => 'nullable|string|max:10',
            'heure_fin'     => 'nullable|string|max:10',
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
            'president_id'  => $v['president_id'] ?? null,
            'date_reunion'  => $v['date_reunion'],
            'heure_debut'   => $v['heure_debut'] ?? null,
            'heure_fin'     => $v['heure_fin'] ?? null,
            'lieu'          => $v['lieu'] ?? null,
            'statut'        => 'brouillon',
        ]);

        // Pré-remplir selon le type
        $donnees = $cr->estReunion() ? $this->preRemplirReunion() : ($cr->sprint_id ? $this->preRemplirDemo($cr->sprint_id) : []);
        $cr->setDonnees($donnees);
        $cr->save();

        // Notification rédacteur
        if (Schema::hasTable('notification_apps')) {
            NotificationApp::create([
                'user_id' => $v['redacteur_id'],
                'type'    => 'designation_cr',
                'message' => "Vous êtes désigné(e) rédacteur du {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y'),
                'lien'    => route('comptes-rendus.edit', $cr),
            ]);
        }

        return redirect()->route('comptes-rendus.edit', $cr)->with('ok', 'Rédacteur désigné. Le formulaire est pré-rempli.');
    }

    /* ===== ÉDITION ===== */
    public function edit(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($user->estPilote() || $cr->redacteur_id === $user->id, 403);

        $collaborateurs = User::where('actif', true)->orderBy('name')->get();
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();
        $donnees = $cr->getDonnees();
        $cr->load(['redacteur', 'president', 'sprint']);

        return view('comptes-rendus.edit', compact('cr', 'collaborateurs', 'projets', 'donnees', 'user'));
    }

    public function update(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($user->estPilote() || $cr->redacteur_id === $user->id, 403);

        $cr->setDonnees($r->input('donnees', []));
        $cr->titre = $r->input('titre', $cr->titre);
        $cr->lieu = $r->input('lieu', $cr->lieu);
        $cr->heure_debut = $r->input('heure_debut', $cr->heure_debut);
        $cr->heure_fin = $r->input('heure_fin', $cr->heure_fin);
        $cr->save();

        return redirect()->route('comptes-rendus.edit', $cr)->with('ok', 'Brouillon enregistré.');
    }

    /* ===== CONFIRMATION ===== */
    public function confirmer(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($user->estPilote() || $cr->redacteur_id === $user->id, 403);

        $cr->statut = 'confirme';
        // Regénérer le DOCX à la confirmation
        $cr->fichier_path = null;
        $cr->save();
        $this->genererDocx($cr);

        if ($cr->designe_par !== $user->id && Schema::hasTable('notification_apps')) {
            NotificationApp::create([
                'user_id' => $cr->designe_par,
                'type'    => 'cr_confirme',
                'message' => "{$user->nomComplet()} a confirmé le {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y'),
                'lien'    => route('comptes-rendus.show', $cr),
            ]);
        }

        return redirect()->route('comptes-rendus.show', $cr)->with('ok', 'Compte rendu confirmé et DOCX généré.');
    }

    /* ===== CONSULTATION ===== */
    public function show(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        $donnees = $cr->getDonnees();
        $cr->load(['redacteur', 'designePar', 'president', 'sprint']);
        return view('comptes-rendus.show', compact('cr', 'donnees', 'user'));
    }

    /* ===== TÉLÉCHARGEMENT ===== */
    public function telecharger(CompteRendu $cr)
    {
        if (!$cr->fichier_path || !file_exists(storage_path('app/' . $cr->fichier_path))) {
            $this->genererDocx($cr);
        }
        $path = storage_path('app/' . $cr->fichier_path);
        $nom = 'CR_' . ($cr->estReunion() ? 'Reunion' : 'Demo') . '_' . $cr->date_reunion->format('d_m_Y') . '.docx';
        return response()->download($path, $nom);
    }

    /* ===================================================================
     *  PRÉ-REMPLISSAGE
     * =================================================================== */

    private function preRemplirReunion(): array
    {
        $semaine = Carbon::now()->subWeek();
        $projets = Projet::where('statut', 'actif')->with('responsable')->orderBy('libelle')->get();

        $diligences = [];
        foreach ($projets as $p) {
            $tachesTerminees = Tache::where('projet_id', $p->id)->where('statut', 'termine')->where('updated_at', '>=', $semaine)->count();
            $tachesBloquees = Schema::hasTable('blocages') ? Blocage::whereHas('tache', fn($q) => $q->where('projet_id', $p->id))->whereNull('date_levee')->count() : 0;
            $evenements = Schema::hasTable('evenements') ? Evenement::where('projet_id', $p->id)->where('created_at', '>=', $semaine)->pluck('description')->toArray() : [];

            $diligences[] = [
                'projet' => $p->libelle, 'responsable' => $p->responsable?->nomComplet() ?? '—',
                'taches_terminees' => $tachesTerminees, 'taches_bloquees' => $tachesBloquees,
                'evenements' => $evenements, 'observations' => '',
            ];
        }

        $presences = User::where('actif', true)->orderBy('name')->get()
            ->map(fn($u) => ['nom' => $u->nomComplet(), 'present' => true])->toArray();

        return [
            'presences'     => $presences,
            'contexte'      => '',
            'ordre_du_jour' => [['point' => '', 'contenu' => '']],
            'diligences'    => $diligences,
            'decisions'     => [],
            'conclusion'    => '',
        ];
    }

    private function preRemplirDemo(int $sprintId): array
    {
        $sprint = Sprint::with('projet')->findOrFail($sprintId);
        $taches = Tache::where('sprint_id', $sprintId)->with('user')->orderBy('type')->orderBy('intitule')->get();

        $lignes = [];
        $totalPoints = 0;
        $pointsRealises = 0;

        foreach ($taches as $t) {
            $pts = $t->points ?? 1;
            $totalPoints += $pts;
            $termine = in_array($t->statut, ['termine', 'clos']);
            if ($termine) $pointsRealises += $pts;
            $lignes[] = [
                'tache' => $t->intitule, 'points' => $pts,
                'attribution' => $t->user?->nomComplet() ?? '—', 'statut' => $t->statut,
                'termine' => $termine, 'observation' => $t->qualification ?? '',
            ];
        }

        $parDev = [];
        foreach ($taches->groupBy('user_id') as $group) {
            $u = $group->first()->user;
            $devTotal = $group->sum(fn($t) => $t->points ?? 1);
            $devFait = $group->filter(fn($t) => in_array($t->statut, ['termine', 'clos']))->sum(fn($t) => $t->points ?? 1);
            $parDev[] = ['nom' => $u?->nomComplet() ?? '—', 'total' => $devTotal, 'realise' => $devFait, 'pourcent' => $devTotal > 0 ? round($devFait / $devTotal * 100) : 0];
        }

        return [
            'sprint' => $sprint->libelle ?? "Sprint #{$sprint->id}",
            'projet' => $sprint->projet?->libelle ?? '—',
            'date_debut' => $sprint->date_debut?->format('d/m/Y'),
            'date_fin' => $sprint->date_fin?->format('d/m/Y'),
            'taches' => $lignes,
            'total_points' => $totalPoints, 'points_realises' => $pointsRealises,
            'score_pourcent' => $totalPoints > 0 ? round($pointsRealises / $totalPoints * 100) : 0,
            'par_dev' => $parDev, 'actions_a_mener' => [], 'note_sprint' => '',
        ];
    }

    /* ===================================================================
     *  GÉNÉRATION DOCX — Times New Roman 12pt, interligne 1,5, logo
     * =================================================================== */

    private function genererDocx(CompteRendu $cr): void
    {
        $donnees = $cr->getDonnees();
        $dir = storage_path('app/comptes-rendus');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'cr_' . $cr->id . '_' . $cr->date_reunion->format('Ymd') . '.docx';
        $path = $dir . '/' . $filename;

        if ($cr->estReunion()) {
            $this->genererDocxReunion($cr, $donnees, $path);
        } else {
            $this->genererDocxDemo($cr, $donnees, $path);
        }

        $cr->fichier_path = 'comptes-rendus/' . $filename;
        $cr->save();
    }

    private function genererDocxReunion(CompteRendu $cr, array $d, string $path): void
    {
        $logoPath = public_path('images/logo_soumafe.png');
        $hasLogo = file_exists($logoPath);

        $xml = $this->docxHeader();

        // Logo centré
        if ($hasLogo) {
            $xml .= $this->docxImage($logoPath, 1200000, 840000); // ~1.2cm x 0.84cm proportionnel
        }

        // En-tête
        $typeLabel = $cr->type === 'reunion_ordinaire' ? 'RÉUNION ORDINAIRE (HEBDOMADAIRE)' : 'RÉUNION EXTRAORDINAIRE (STRATÉGIQUE)';
        $xml .= $this->p("COMPTE RENDU DE {$typeLabel}", true, 14, 'center');
        $xml .= $this->p('', false, 6);

        // Tableau résumé
        $president = $cr->president?->nomComplet() ?? '—';
        $redacteur = $cr->redacteur?->nomComplet() ?? '—';
        $infos = [
            ['Date', $cr->date_reunion->format('d/m/Y')],
            ['Heure début', $cr->heure_debut ?? '—'],
            ['Heure fin', $cr->heure_fin ?? '—'],
            ['Lieu', $cr->lieu ?? '—'],
            ['Président', $president],
            ['Rédacteur', $redacteur],
        ];
        $xml .= $this->docxInfoTable($infos);
        $xml .= $this->p('');

        // Présences
        if (!empty($d['presences'])) {
            $xml .= $this->p("Liste de présence", true, 12, 'left', true);
            $xml .= $this->docxTable2Col('Collaborateur', 'Présent',
                array_map(fn($p) => [$p['nom'], ($p['present'] ?? false) ? 'Présent' : 'Absent'], $d['presences'])
            );
            $xml .= $this->p('');
        }

        // 1. Contexte et objectifs
        $xml .= $this->p("1. Contexte et objectifs de la réunion", true, 12, 'left', true);
        $xml .= $this->p($d['contexte'] ?? '(Non renseigné)');
        $xml .= $this->p('');

        // Ordre du jour
        if (!empty($d['ordre_du_jour'])) {
            $xml .= $this->p("Ordre du jour :", true, 12);
            $num = 1;
            foreach ($d['ordre_du_jour'] as $odj) {
                if (!empty($odj['point'])) {
                    $xml .= $this->p("   {$num}. {$odj['point']}");
                    $num++;
                }
            }
            $xml .= $this->p('');
        }

        // 2. Déroulement
        $xml .= $this->p("2. Déroulement de la réunion", true, 12, 'left', true);

        // Points de l'ordre du jour avec contenu
        if (!empty($d['ordre_du_jour'])) {
            $sub = 1;
            foreach ($d['ordre_du_jour'] as $odj) {
                if (!empty($odj['point'])) {
                    $xml .= $this->p("2.{$sub}. {$odj['point']}", true, 12);
                    $xml .= $this->p(!empty($odj['contenu']) ? $odj['contenu'] : '(Non renseigné)');
                    $xml .= $this->p('');
                    $sub++;
                }
            }
        }

        // Diligences par projet (si données pré-remplies)
        if (!empty($d['diligences'])) {
            $xml .= $this->p("Diligences par projet :", true, 12);
            foreach ($d['diligences'] as $dil) {
                $xml .= $this->p("• {$dil['projet']} (resp. : {$dil['responsable']})", true, 12);
                $xml .= $this->p("   Tâches terminées : {$dil['taches_terminees']} — Blocages actifs : {$dil['taches_bloquees']}");
                if (!empty($dil['evenements'])) $xml .= $this->p("   Événements : " . implode(', ', (array)$dil['evenements']));
                if (!empty($dil['observations'])) $xml .= $this->p("   Observations : {$dil['observations']}");
            }
            $xml .= $this->p('');
        }

        // 3. Décisions
        $xml .= $this->p("3. Décisions prises / Actions à mener", true, 12, 'left', true);
        if (!empty($d['decisions'])) {
            $xml .= $this->docxTable3Col('Décision / Action', 'Responsable', 'Échéance',
                array_map(fn($dec) => [$dec['action'] ?? '', $dec['responsable'] ?? '', $dec['echeance'] ?? ''], $d['decisions'])
            );
        } else {
            $xml .= $this->p("(Aucune décision enregistrée)");
        }
        $xml .= $this->p('');

        // 4. Conclusion
        $xml .= $this->p("4. Conclusion", true, 12, 'left', true);
        $xml .= $this->p(!empty($d['conclusion']) ? $d['conclusion'] : '(Non renseignée)');

        $xml .= $this->docxFooter();
        $this->ecrireDocx($path, $xml, $hasLogo ? $logoPath : null);
    }

    private function genererDocxDemo(CompteRendu $cr, array $d, string $path): void
    {
        $logoPath = public_path('images/logo_soumafe.png');
        $hasLogo = file_exists($logoPath);

        $xml = $this->docxHeader();

        if ($hasLogo) $xml .= $this->docxImage($logoPath, 1200000, 840000);

        $xml .= $this->p("COMPTE RENDU DE DÉMONSTRATION SPRINT", true, 14, 'center');
        $xml .= $this->p("Sprint : " . ($d['sprint'] ?? '—') . " — Projet : " . ($d['projet'] ?? '—'), false, 11, 'center');
        $xml .= $this->p("Période : " . ($d['date_debut'] ?? '') . " au " . ($d['date_fin'] ?? ''), false, 11, 'center');
        $xml .= $this->p("Date démonstration : " . $cr->date_reunion->format('d/m/Y'), false, 11, 'center');
        $xml .= $this->p('');

        // Tableau tâches
        $xml .= $this->p("1. Résultats par tâche", true, 12, 'left', true);
        $rows = array_map(fn($t) => [$t['tache'], (string)$t['points'], $t['attribution'], ($t['termine'] ?? false) ? '✓' : '✗', $t['observation'] ?? ''], $d['taches'] ?? []);
        $xml .= $this->docxTableMulti(['Tâche', 'Pts', 'Attribution', 'État', 'Observation'], $rows);
        $xml .= $this->p('');

        // Score
        $xml .= $this->p("2. Évaluation du sprint", true, 12, 'left', true);
        $xml .= $this->p("Score global : {$d['points_realises']}/{$d['total_points']} points ({$d['score_pourcent']}%)", true, 12);
        if (!empty($d['par_dev'])) {
            $xml .= $this->p('');
            $xml .= $this->p("Score par développeur :", true, 12);
            $devRows = array_map(fn($dev) => [$dev['nom'], (string)$dev['realise'], (string)$dev['total'], $dev['pourcent'] . '%'], $d['par_dev']);
            $xml .= $this->docxTableMulti(['Développeur', 'Réalisé', 'Total', '%'], $devRows);
        }
        $xml .= $this->p('');

        // Actions
        $xml .= $this->p("3. Actions à mener", true, 12, 'left', true);
        if (!empty($d['actions_a_mener'])) {
            $actRows = array_map(fn($a) => [$a['action'] ?? '', $a['responsable'] ?? '', $a['echeance'] ?? ''], $d['actions_a_mener']);
            $xml .= $this->docxTable3Col('Action', 'Responsable', 'Échéance', $actRows);
        } else {
            $xml .= $this->p("(À compléter)");
        }

        if (!empty($d['note_sprint'])) {
            $xml .= $this->p('');
            $xml .= $this->p("4. Note pour le sprint suivant", true, 12, 'left', true);
            $xml .= $this->p($d['note_sprint']);
        }

        $xml .= $this->docxFooter();
        $this->ecrireDocx($path, $xml, $hasLogo ? $logoPath : null);
    }

    /* ===================================================================
     *  HELPERS DOCX — Times New Roman 12pt, interligne 1,5
     * =================================================================== */

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
            . 'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" '
            . 'mc:Ignorable="w14 wp14">'
            . '<w:body>';
    }

    private function docxFooter(): string { return '</w:body></w:document>'; }

    /** Paragraphe Times New Roman avec interligne 1,5 */
    private function p(string $text, bool $bold = false, int $size = 12, string $align = 'left', bool $underline = false): string
    {
        $sz = $size * 2;
        $jc = match ($align) { 'center' => 'center', 'right' => 'right', default => 'left' };
        $bTag = $bold ? '<w:b/>' : '';
        $uTag = $underline ? '<w:u w:val="single"/>' : '';
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        // Interligne 1,5 = 360 twips (240 * 1.5)
        $spacing = '<w:spacing w:line="360" w:lineRule="auto"/>';

        return "<w:p><w:pPr><w:jc w:val=\"{$jc}\"/>{$spacing}</w:pPr>"
            . "<w:r><w:rPr><w:rFonts w:ascii=\"Times New Roman\" w:hAnsi=\"Times New Roman\" w:cs=\"Times New Roman\"/>"
            . "<w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/>{$bTag}{$uTag}</w:rPr>"
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    private function docxInfoTable(array $rows): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>'
            . $this->borders() . '</w:tblBorders></w:tblPr>';
        foreach ($rows as $r) {
            $xml .= '<w:tr>'
                . $this->cell($r[0], true, '1F4E79', 'FFFFFF', 2500)
                . $this->cell($r[1], false, null, null, 6500)
                . '</w:tr>';
        }
        return $xml . '</w:tbl>';
    }

    private function docxTable2Col(string $h1, string $h2, array $rows): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>' . $this->borders() . '</w:tblBorders></w:tblPr>';
        $xml .= '<w:tr>' . $this->cell($h1, true, '1F4E79', 'FFFFFF') . $this->cell($h2, true, '1F4E79', 'FFFFFF', 2000) . '</w:tr>';
        foreach ($rows as $r) $xml .= '<w:tr>' . $this->cell($r[0]) . $this->cell($r[1], false, null, null, 2000) . '</w:tr>';
        return $xml . '</w:tbl>';
    }

    private function docxTable3Col(string $h1, string $h2, string $h3, array $rows): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>' . $this->borders() . '</w:tblBorders></w:tblPr>';
        $xml .= '<w:tr>' . $this->cell($h1, true, '1F4E79', 'FFFFFF', 4000) . $this->cell($h2, true, '1F4E79', 'FFFFFF', 2500) . $this->cell($h3, true, '1F4E79', 'FFFFFF', 2500) . '</w:tr>';
        foreach ($rows as $r) $xml .= '<w:tr>' . $this->cell($r[0], false, null, null, 4000) . $this->cell($r[1], false, null, null, 2500) . $this->cell($r[2], false, null, null, 2500) . '</w:tr>';
        return $xml . '</w:tbl>';
    }

    private function docxTableMulti(array $headers, array $rows): string
    {
        $n = count($headers);
        $w = intdiv(9000, $n);
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>' . $this->borders() . '</w:tblBorders></w:tblPr>';
        $xml .= '<w:tr>';
        foreach ($headers as $h) $xml .= $this->cell($h, true, '1F4E79', 'FFFFFF', $w);
        $xml .= '</w:tr>';
        foreach ($rows as $r) {
            $xml .= '<w:tr>';
            foreach ($r as $c) $xml .= $this->cell((string)$c, false, null, null, $w);
            $xml .= '</w:tr>';
        }
        return $xml . '</w:tbl>';
    }

    private function cell(string $text, bool $bold = false, ?string $bg = null, ?string $fg = null, ?int $width = null): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        $bTag = $bold ? '<w:b/>' : '';
        $colorTag = $fg ? "<w:color w:val=\"{$fg}\"/>" : '';
        $shdTag = $bg ? "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bg}\"/>" : '';
        $wTag = $width ? "<w:tcW w:w=\"{$width}\" w:type=\"dxa\"/>" : '';

        return "<w:tc><w:tcPr>{$wTag}{$shdTag}</w:tcPr><w:p><w:pPr><w:spacing w:line=\"360\" w:lineRule=\"auto\"/></w:pPr>"
            . "<w:r><w:rPr><w:rFonts w:ascii=\"Times New Roman\" w:hAnsi=\"Times New Roman\" w:cs=\"Times New Roman\"/>"
            . "<w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/>{$bTag}{$colorTag}</w:rPr>"
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p></w:tc>";
    }

    private function borders(): string
    {
        $b = '<w:val="single" w:sz="4" w:space="0" w:color="999999"/>';
        // Fix: les attributs doivent être sur l'élément, pas avec < fermant
        return '<w:top w:val="single" w:sz="4" w:space="0" w:color="999999"/>'
            . '<w:left w:val="single" w:sz="4" w:space="0" w:color="999999"/>'
            . '<w:bottom w:val="single" w:sz="4" w:space="0" w:color="999999"/>'
            . '<w:right w:val="single" w:sz="4" w:space="0" w:color="999999"/>'
            . '<w:insideH w:val="single" w:sz="4" w:space="0" w:color="999999"/>'
            . '<w:insideV w:val="single" w:sz="4" w:space="0" w:color="999999"/>';
    }

    private function docxImage(string $path, int $cx, int $cy): string
    {
        return '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:drawing>'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
            . "<wp:extent cx=\"{$cx}\" cy=\"{$cy}\"/>"
            . '<wp:docPr id="1" name="Logo"/>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic><pic:nvPicPr><pic:cNvPr id="1" name="logo.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="rId10"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline>'
            . '</w:drawing></w:r></w:p>';
    }

    private function ecrireDocx(string $path, string $documentXml, ?string $logoPath = null): void
    {
        if (file_exists($path)) unlink($path);
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);

        $imgContentType = '';
        if ($logoPath && file_exists($logoPath)) {
            $zip->addFile($logoPath, 'word/media/logo.png');
            $imgContentType = '<Default Extension="png" ContentType="image/png"/>';
        }

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $imgContentType
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>');

        $zip->addFromString('_rels/.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');

        $imgRel = ($logoPath && file_exists($logoPath))
            ? '<Relationship Id="rId10" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/logo.png"/>'
            : '';

        $zip->addFromString('word/_rels/document.xml.rels',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $imgRel
            . '</Relationships>');

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();
    }
}
