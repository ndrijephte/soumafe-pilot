<?php
namespace App\Http\Controllers;

use App\Models\{CompteRendu, NotificationApp, Projet, Sprint, Tache, User, Blocage, Evenement};
use App\Services\MailService;
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
            $query->where(fn($q) => $q->where('redacteur_id', $user->id)->orWhere('statut', 'confirme'));
        }
        if ($type = $r->get('type')) $query->where('type', $type);
        if ($redacteur = $r->get('redacteur_id')) $query->where('redacteur_id', $redacteur);
        if ($du = $r->get('date_du')) $query->whereDate('date_reunion', '>=', $du);
        if ($au = $r->get('date_au')) $query->whereDate('date_reunion', '<=', $au);

        $crs = $query->paginate(15)->withQueryString();
        $redacteurs = User::where('actif', true)->orderBy('name')->get();
        return view('comptes-rendus.index', compact('crs', 'user', 'redacteurs'));
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
        $sprints = Sprint::with('projet')->orderByDesc('date_debut')->get();
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();

        return view('comptes-rendus.create', compact('type', 'collaborateurs', 'tousUsers', 'sprints', 'projets', 'sprintId', 'user'));
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

        try {
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

            // E-mail au rédacteur
            $redacteur = User::find($v['redacteur_id']);
            if ($redacteur) {
                MailService::notifier(
                    $redacteur,
                    'Désignation rédacteur',
                    "Vous êtes désigné(e) rédacteur du {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y') . " par " . $user->nomComplet() . ".\nMerci de compléter le compte rendu dans l'application.",
                    route('comptes-rendus.edit', $cr)
                );
            }

            return redirect()->route('comptes-rendus.edit', $cr)->with('ok', 'Rédacteur désigné. Le formulaire est pré-rempli.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors(['erreur' => $e->getMessage()]);
        }
    }

    /* ===== ÉDITION ===== */
    public function edit(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($user->estPilote() || $cr->redacteur_id === $user->id, 403);

        $collaborateurs = User::where('actif', true)->orderBy('name')->get();
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();
        $donnees = $cr->getDonnees();

        // Garantir que la liste de présence existe pour les réunions
        if ($cr->estReunion() && empty($donnees['presences'])) {
            $donnees['presences'] = User::where('actif', true)->orderBy('name')->get()
                ->map(fn($u) => ['nom' => $u->nomComplet(), 'present' => true])->toArray();
            $cr->setDonnees($donnees);
            $cr->save();
        }

        $cr->load(['redacteur', 'president', 'sprint']);

        return view('comptes-rendus.edit', compact('cr', 'collaborateurs', 'projets', 'donnees', 'user'));
    }

    public function update(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($user->estPilote() || $cr->redacteur_id === $user->id, 403);

        $donnees = $r->input('donnees', []);
        // Sanitiser les champs HTML rich-text
        $htmlFields = ['contexte', 'conclusion', 'note_sprint'];
        foreach ($htmlFields as $f) {
            if (isset($donnees[$f])) $donnees[$f] = $this->sanitizeHtml($donnees[$f]);
        }
        if (!empty($donnees['ordre_du_jour'])) {
            foreach ($donnees['ordre_du_jour'] as &$odj) {
                if (isset($odj['contenu'])) $odj['contenu'] = $this->sanitizeHtml($odj['contenu']);
            }
            unset($odj);
        }
        if (!empty($donnees['diligences'])) {
            foreach ($donnees['diligences'] as &$dil) {
                if (isset($dil['observations'])) $dil['observations'] = $this->sanitizeHtml($dil['observations']);
            }
            unset($dil);
        }

        $cr->setDonnees($donnees);
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

        try {
            $cr->statut = 'confirme';
            $cr->fichier_path = null;
            $cr->save();
            $this->genererDocx($cr);

            if (Schema::hasTable('notification_apps')) {
                // Notifier le désignateur
                if ((int)$cr->designe_par !== (int)$user->id) {
                    NotificationApp::create([
                        'user_id' => $cr->designe_par,
                        'type'    => 'cr_confirme',
                        'message' => "{$user->nomComplet()} a confirmé le {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y'),
                        'lien'    => route('comptes-rendus.show', $cr),
                    ]);
                }

                // Notifier chaque collaborateur cité dans les décisions/actions
                $donnees = $cr->getDonnees();
                $nomsResp = collect();
                foreach (['decisions', 'actions_a_mener'] as $section) {
                    foreach ($donnees[$section] ?? [] as $item) {
                        $noms = $item['responsables'] ?? (isset($item['responsable']) && $item['responsable'] ? [$item['responsable']] : []);
                        $nomsResp = $nomsResp->merge($noms);
                    }
                }
                $nomsResp = $nomsResp->unique()->filter();
                if ($nomsResp->isNotEmpty()) {
                    $allUsers = User::where('actif', true)->get();
                    foreach ($nomsResp as $nom) {
                        $target = $allUsers->first(fn($u) => $u->nomComplet() === $nom);
                        if ($target && (int)$target->id !== (int)$user->id) {
                            NotificationApp::create([
                                'user_id' => $target->id,
                                'type'    => 'cr_action',
                                'message' => "Une action vous concerne dans le {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y'),
                                'lien'    => route('comptes-rendus.show', $cr),
                            ]);
                            // E-mail
                            MailService::notifier(
                                $target,
                                'Action à mener',
                                "Une action vous concerne dans le {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y') . ".\nMerci de consulter le compte rendu pour les détails.",
                                route('comptes-rendus.show', $cr)
                            );
                        }
                    }
                }
            }

            return redirect()->route('comptes-rendus.show', $cr)->with('ok', 'Compte rendu confirmé et DOCX généré.');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['erreur' => 'Erreur DOCX : ' . $e->getMessage()]);
        }
    }

    /* ===== SUPPRESSION ===== */
    public function destroy(Request $r, CompteRendu $cr)
    {
        abort_unless($r->user()->estPilote(), 403);

        // Supprimer le fichier DOCX associé
        if ($cr->fichier_path && file_exists(storage_path('app/' . $cr->fichier_path))) {
            @unlink(storage_path('app/' . $cr->fichier_path));
        }

        $titre = $cr->titre;
        $cr->delete();
        return redirect()->route('comptes-rendus.index')->with('ok', "Compte rendu « {$titre} » supprimé.");
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

    /* ===== IMPRESSION PDF (via navigateur) ===== */
    public function imprimer(CompteRendu $cr)
    {
        $cr->load(['redacteur', 'designePar', 'president', 'sprint.projet']);
        return view('comptes-rendus.pdf', compact('cr'));
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
            $evenements = (Schema::hasTable('evenements') && Schema::hasColumn('evenements', 'projet_id')) ? Evenement::where('projet_id', $p->id)->where('created_at', '>=', $semaine)->pluck('description')->toArray() : [];

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
            'sprint' => "Sprint {$sprint->numero}",
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
        $xml .= $this->htmlToOoxml($d['contexte'] ?? '(Non renseigné)');
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
                    $xml .= $this->htmlToOoxml(!empty($odj['contenu']) ? $odj['contenu'] : '(Non renseigné)');
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
                if (!empty($dil['observations'])) { $xml .= $this->p("   Observations :", true, 12); $xml .= $this->htmlToOoxml($dil['observations']); }
            }
            $xml .= $this->p('');
        }

        // 3. Décisions
        $xml .= $this->p("3. Décisions prises / Actions à mener", true, 12, 'left', true);
        if (!empty($d['decisions'])) {
            $decRows = [];
            foreach ($d['decisions'] as $i => $dec) {
                $rDec = $dec['responsables'] ?? (isset($dec['responsable']) && $dec['responsable'] ? [$dec['responsable']] : []);
                $decRows[] = [(string)($i + 1), $dec['action'] ?? '', implode(', ', $rDec), $dec['echeance'] ?? ''];
            }
            $xml .= $this->docxTableMulti(['N°', 'Décision / Action', 'Responsable(s)', 'Échéance'], $decRows);
        } else {
            $xml .= $this->p("(Aucune décision enregistrée)");
        }
        $xml .= $this->p('');

        // 4. Conclusion
        $xml .= $this->p("4. Conclusion", true, 12, 'left', true);
        $xml .= $this->htmlToOoxml(!empty($d['conclusion']) ? $d['conclusion'] : '(Non renseignée)');

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
        $rows = [];
        foreach (($d['taches'] ?? []) as $i => $t) {
            $rows[] = [(string)($i + 1), $t['tache'], (string)$t['points'], $t['attribution'], ($t['termine'] ?? false) ? '✓' : '✗', $t['observation'] ?? ''];
        }
        $xml .= $this->docxTableMulti(['N°', 'Tâche', 'Pts', 'Attribution', 'État', 'Observation'], $rows);
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
            $actRows = [];
            foreach ($d['actions_a_mener'] as $i => $a) {
                $rAct = $a['responsables'] ?? (isset($a['responsable']) && $a['responsable'] ? [$a['responsable']] : []);
                $actRows[] = [(string)($i + 1), $a['action'] ?? '', implode(', ', $rAct), $a['echeance'] ?? ''];
            }
            $xml .= $this->docxTableMulti(['N°', 'Action', 'Responsable(s)', 'Échéance'], $actRows);
        } else {
            $xml .= $this->p("(À compléter)");
        }

        if (!empty($d['note_sprint'])) {
            $xml .= $this->p('');
            $xml .= $this->p("4. Note pour le sprint suivant", true, 12, 'left', true);
            $xml .= $this->htmlToOoxml($d['note_sprint']);
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
            . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            . 'xmlns:w10="urn:schemas-microsoft-com:office:word" '
            . 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            . 'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" '
            . 'xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" '
            . 'xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" '
            . 'xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" '
            . 'mc:Ignorable="w14 wp14">'
            . '<w:body>';
    }

    private function docxFooter(): string
    {
        // sectPr : A4 (11906 x 16838 twips), marges 1440 twips (1 pouce = 2,54 cm)
        return '<w:sectPr>'
            . '<w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="720" w:footer="720" w:gutter="0"/>'
            . '<w:cols w:space="720"/>'
            . '<w:docGrid w:linePitch="360"/>'
            . '</w:sectPr>'
            . '</w:body></w:document>';
    }

    /** Paragraphe Times New Roman avec interligne 1,5 */
    private function p(string $text, bool $bold = false, int $size = 12, string $align = 'both', bool $underline = false): string
    {
        $sz = $size * 2;
        $jc = match ($align) { 'center' => 'center', 'right' => 'right', 'left' => 'left', default => 'both' };
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
        $b = 'w:val="single" w:sz="4" w:space="0" w:color="999999"';
        return "<w:top {$b}/><w:left {$b}/><w:bottom {$b}/><w:right {$b}/><w:insideH {$b}/><w:insideV {$b}/>";
    }

    private function docxImage(string $path, int $cx, int $cy): string
    {
        return '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:drawing>'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
            . "<wp:extent cx=\"{$cx}\" cy=\"{$cy}\"/>"
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="1" name="Logo"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic><pic:nvPicPr><pic:cNvPr id="1" name="logo.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="rId10"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . "<pic:spPr><a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext cx=\"{$cx}\" cy=\"{$cy}\"/></a:xfrm>"
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline>'
            . '</w:drawing></w:r></w:p>';
    }

    /** Nettoyer le HTML provenant du contenteditable (garder uniquement b, u, i, ol, ul, li, br, div) */
    private function sanitizeHtml(string $html): string
    {
        return strip_tags($html, '<b><u><i><strong><em><ol><ul><li><br><div><p>');
    }

    /** Convertir HTML (du contenteditable) en paragraphes OOXML Times New Roman 12pt */
    private function htmlToOoxml(string $html): string
    {
        // Si pas de HTML, traiter comme texte brut
        if ($html === strip_tags($html) && !str_contains($html, '<')) {
            return $this->p($html);
        }

        $xml = '';
        // Remplacer les <div> et <p> par des sauts pour unifier
        $html = str_replace(['<div>', '<p>'], "\n", $html);
        $html = str_replace(['</div>', '</p>'], '', $html);
        $html = str_replace(['<br>', '<br/>', '<br />'], "\n", $html);

        // Traiter les listes
        $html = preg_replace_callback('/<(ol|ul)>(.*?)<\/\1>/si', function ($m) use (&$xml) {
            $type = $m[1]; // ol ou ul
            $items = [];
            preg_match_all('/<li>(.*?)<\/li>/si', $m[2], $lis);
            foreach ($lis[1] as $i => $li) {
                $prefix = ($type === 'ol') ? ($i + 1) . '. ' : '• ';
                $text = strip_tags($li, '<b><u><i><strong><em>');
                $xml .= $this->richParagraph($prefix . $text);
            }
            return ''; // Retirer le HTML traité
        }, $html);

        // Traiter le reste ligne par ligne
        $lines = preg_split('/\n+/', $html);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (str_contains($line, '<li>')) continue; // Déjà traité
            $xml .= $this->richParagraph($line);
        }

        return $xml ?: $this->p('(Non renseigné)');
    }

    /** Convertir une ligne HTML avec <b>, <u>, <i> en un paragraphe OOXML avec runs formatés */
    private function richParagraph(string $html): string
    {
        $spacing = '<w:spacing w:line="360" w:lineRule="auto"/>';
        $p = "<w:p><w:pPr><w:jc w:val=\"both\"/>{$spacing}</w:pPr>";

        // Parser les segments avec balisage inline
        $segments = $this->parseInlineHtml($html);
        foreach ($segments as $seg) {
            $escaped = htmlspecialchars($seg['text'], ENT_XML1, 'UTF-8');
            if ($escaped === '') continue;
            $bTag = $seg['bold'] ? '<w:b/>' : '';
            $uTag = $seg['underline'] ? '<w:u w:val="single"/>' : '';
            $iTag = $seg['italic'] ? '<w:i/>' : '';
            $p .= '<w:r><w:rPr>'
                . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
                . '<w:sz w:val="24"/><w:szCs w:val="24"/>'
                . "{$bTag}{$uTag}{$iTag}"
                . '</w:rPr>'
                . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r>";
        }

        return $p . '</w:p>';
    }

    /** Parser le HTML inline (b, u, i, strong, em) en segments avec attributs */
    private function parseInlineHtml(string $html): array
    {
        $segments = [];
        // Approche : strip tags et détecter formatting par position
        // Utiliser une approche simplifiée mais fiable
        $text = strip_tags($html);
        if ($text === '') return [];

        // Chercher les balises de formatage
        $bold = (bool)preg_match('/<(b|strong)\b/i', $html);
        $underline = (bool)preg_match('/<u\b/i', $html);
        $italic = (bool)preg_match('/<(i|em)\b/i', $html);

        // Si tout le texte est uniformément formaté, un seul segment suffit
        // Pour un parsing plus fin (partie gras, partie non), on découpe par balises
        if (!preg_match('/<(b|strong|u|i|em)\b/i', $html)) {
            return [['text' => $text, 'bold' => false, 'underline' => false, 'italic' => false]];
        }

        // Parser nœud par nœud via regex
        $parts = preg_split('/(<\/?(?:b|strong|u|i|em)\b[^>]*>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $currentBold = false;
        $currentUnderline = false;
        $currentItalic = false;

        foreach ($parts as $part) {
            if (preg_match('/^<(b|strong)\b/i', $part)) { $currentBold = true; continue; }
            if (preg_match('/^<\/(b|strong)>/i', $part)) { $currentBold = false; continue; }
            if (preg_match('/^<u\b/i', $part)) { $currentUnderline = true; continue; }
            if (preg_match('/^<\/u>/i', $part)) { $currentUnderline = false; continue; }
            if (preg_match('/^<(i|em)\b/i', $part)) { $currentItalic = true; continue; }
            if (preg_match('/^<\/(i|em)>/i', $part)) { $currentItalic = false; continue; }

            $t = strip_tags($part);
            if ($t !== '') {
                $segments[] = ['text' => $t, 'bold' => $currentBold, 'underline' => $currentUnderline, 'italic' => $currentItalic];
            }
        }

        return $segments;
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
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
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
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . $imgRel
            . '</Relationships>');

        // styles.xml — Normal : Times New Roman 12pt, justifié, interligne 1.5
        $zip->addFromString('word/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:docDefaults>'
            .   '<w:rPrDefault><w:rPr>'
            .     '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
            .     '<w:sz w:val="24"/><w:szCs w:val="24"/>'
            .     '<w:lang w:val="fr-FR" w:eastAsia="fr-FR" w:bidi="ar-SA"/>'
            .   '</w:rPr></w:rPrDefault>'
            .   '<w:pPrDefault><w:pPr>'
            .     '<w:spacing w:line="360" w:lineRule="auto"/>'
            .     '<w:jc w:val="both"/>'
            .   '</w:pPr></w:pPrDefault>'
            . '</w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal">'
            .   '<w:name w:val="Normal"/>'
            .   '<w:qFormat/>'
            .   '<w:pPr><w:spacing w:line="360" w:lineRule="auto"/><w:jc w:val="both"/></w:pPr>'
            .   '<w:rPr>'
            .     '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:eastAsia="Times New Roman" w:cs="Times New Roman"/>'
            .     '<w:sz w:val="24"/><w:szCs w:val="24"/>'
            .   '</w:rPr>'
            . '</w:style>'
            . '<w:style w:type="table" w:default="1" w:styleId="TableNormal">'
            .   '<w:name w:val="Normal Table"/>'
            .   '<w:tblPr><w:tblInd w:w="0" w:type="dxa"/><w:tblCellMar>'
            .     '<w:top w:w="0" w:type="dxa"/><w:left w:w="108" w:type="dxa"/>'
            .     '<w:bottom w:w="0" w:type="dxa"/><w:right w:w="108" w:type="dxa"/>'
            .   '</w:tblCellMar></w:tblPr>'
            . '</w:style>'
            . '</w:styles>');

        $zip->addFromString('word/document.xml', $documentXml);
        $zip->close();
    }
}
