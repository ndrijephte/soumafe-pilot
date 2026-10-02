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
        if ($cr->estConfirme()) {
            abort_unless($user->estAdmin(), 403);
        } else {
            abort_unless($user->estPilote() || $cr->redacteur_id === $user->id, 403);
        }

        $collaborateurs = User::where('actif', true)->orderBy('name')->get();
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();
        $donnees = $cr->getDonnees();

        // Garantir que la liste de présence existe pour les réunions
        if ($cr->estReunion() && empty($donnees['presences'])) {
            $donnees['presences'] = User::where('actif', true)->orderBy('name')->get()
                ->map(fn($u) => ['nom' => $u->nomComplet(), 'fonction' => $u->roleLibelle(), 'present' => true])->toArray();
            $cr->setDonnees($donnees);
            $cr->save();
        }

        $cr->load(['redacteur', 'president', 'sprint']);

        return view('comptes-rendus.edit', compact('cr', 'collaborateurs', 'projets', 'donnees', 'user'));
    }

    public function update(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        // Brouillon : pilote ou rédacteur. Confirmé : admin uniquement.
        if ($cr->estConfirme()) {
            abort_unless($user->estAdmin(), 403);
        } else {
            abort_unless($user->estPilote() || $cr->redacteur_id === $user->id, 403);
        }

        /* ---- Demo sprint : bloc editor JSON ---- */
        if (!$cr->estReunion() && $r->filled('bloc_data')) {
            $donnees = json_decode($r->input('bloc_data'), true) ?: [];
            // Sanitiser le HTML dans les blocs texte et reference
            if (!empty($donnees['modules'])) {
                foreach ($donnees['modules'] as &$mod) {
                    foreach ($mod['sections'] ?? [] as &$sec) {
                        foreach ($sec['blocs'] ?? [] as &$blc) {
                            if (in_array($blc['type'] ?? '', ['texte', 'reference']) && isset($blc['contenu'])) {
                                $blc['contenu'] = $this->sanitizeHtml($blc['contenu']);
                            }
                        }
                    }
                }
                unset($mod, $sec, $blc);
            }
            $score = $this->calculerScoreDemo($donnees['taches'] ?? []);
            if (!empty($donnees['score_manuel'])) {
                $pts = max(0, (int) ($donnees['points_realises'] ?? 0));
                $tot = max(0, (int) ($donnees['total_points'] ?? 0));
                $score['points_realises'] = $pts;
                $score['total_points'] = $tot;
                $score['score_pourcent'] = $tot > 0 ? (int) round($pts / $tot * 100) : 0;
            }
            $donnees = array_merge($donnees, $score, ['score_manuel' => !empty($donnees['score_manuel'])]);
        } else {
            $donnees = $r->input('donnees', []);
            // Sanitiser les champs HTML rich-text (reunion)
            $htmlFields = ['contexte', 'conclusion', 'note_sprint'];
            foreach ($htmlFields as $f) {
                if (isset($donnees[$f])) $donnees[$f] = $this->sanitizeHtml($donnees[$f]);
            }
            if (!empty($donnees['ordre_du_jour'])) {
                foreach ($donnees['ordre_du_jour'] as &$odj) {
                    if (isset($odj['contenu'])) $odj['contenu'] = $this->sanitizeHtml($odj['contenu']);
                }
                unset($odj);
                $donnees['ordre_du_jour'] = array_values(array_filter($donnees['ordre_du_jour'], fn($o) => trim($o['point'] ?? '') !== ''));
            }
            if (!empty($donnees['annotations'])) {
                $donnees['annotations'] = array_values(array_filter($donnees['annotations'], fn($a) => !empty($a['contenu']) && !empty($a['type'])));
            }
        }
        $cr->setDonnees($donnees);
        $cr->titre = $r->input('titre', $cr->titre);
        $cr->lieu = $r->input('lieu', $cr->lieu);
        $cr->heure_debut = $r->input('heure_debut', $cr->heure_debut);
        $cr->heure_fin = $r->input('heure_fin', $cr->heure_fin);
        $cr->save();

        // Régénérer le DOCX si le CR est déjà confirmé
        if ($cr->estConfirme()) {
            $this->genererDocx($cr);
            return redirect()->route('comptes-rendus.show', $cr)->with('ok', 'Compte rendu modifié et DOCX régénéré.');
        }

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
        $r->validate(['motif_suppression' => 'required|string|min:5']);

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

        $presences = User::where('actif', true)->orderBy('name')->get()
            ->map(fn($u) => ['nom' => $u->nomComplet(), 'fonction' => $u->roleLibelle(), 'present' => true])->toArray();

        return [
            'presences'     => $presences,
            'contexte'      => '',
            'ordre_du_jour' => [['point' => '', 'contenu' => '']],
            'decisions'     => [],
            'conclusion'    => '',
        ];
    }

    private function calculerScoreDemo(array $taches): array
    {
        $total = 0; $realise = 0; $parDev = [];
        foreach ($taches as $t) {
            $pts = (int) ($t['points'] ?? 1);
            $fait = filter_var($t['termine'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $nom = $t['attribution'] ?? '—';
            $total += $pts;
            $parDev[$nom] ??= ['nom' => $nom, 'total' => 0, 'realise' => 0];
            $parDev[$nom]['total'] += $pts;
            if ($fait) { $realise += $pts; $parDev[$nom]['realise'] += $pts; }
        }
        foreach ($parDev as &$dev) {
            $dev['pourcent'] = $dev['total'] > 0 ? (int) round($dev['realise'] / $dev['total'] * 100) : 0;
        }
        unset($dev);

        return [
            'total_points' => $total,
            'points_realises' => $realise,
            'score_pourcent' => $total > 0 ? (int) round($realise / $total * 100) : 0,
            'par_dev' => array_values($parDev),
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

        $scorePct = $totalPoints > 0 ? round($pointsRealises / $totalPoints * 100) : 0;

        return [
            'format' => 'blocs_v1',
            'sprint' => "Sprint {$sprint->numero}",
            'projet' => $sprint->projet?->libelle ?? '—',
            'date_debut' => $sprint->date_debut?->format('d/m/Y'),
            'date_fin' => $sprint->date_fin?->format('d/m/Y'),
            'taches' => $lignes,
            'total_points' => $totalPoints, 'points_realises' => $pointsRealises,
            'score_pourcent' => $scorePct,
            'par_dev' => $parDev,
            'modules' => [
                [
                    'id' => 'mod_1', 'titre' => 'Resultats du Sprint',
                    'objectif' => 'Presenter les livrables et le score du sprint',
                    'sections' => [
                        [
                            'id' => 'sec_1', 'titre' => 'Taches realisees',
                            'blocs' => [
                                ['id' => 'blc_1', 'type' => 'tableau_taches'],
                                ['id' => 'blc_2', 'type' => 'score'],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'mod_2', 'titre' => 'Analyse et prochaines etapes',
                    'objectif' => 'Evaluer la performance et planifier la suite',
                    'sections' => [
                        [
                            'id' => 'sec_2', 'titre' => 'Actions a mener',
                            'blocs' => [
                                ['id' => 'blc_3', 'type' => 'actions', 'items' => []],
                            ],
                        ],
                        [
                            'id' => 'sec_3', 'titre' => 'Note pour le sprint suivant',
                            'blocs' => [
                                ['id' => 'blc_4', 'type' => 'texte', 'contenu' => ''],
                            ],
                        ],
                    ],
                ],
            ],
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
        $cr->load(['redacteur', 'president', 'sprint.projet']);

        $redacteur = $cr->redacteur;
        $redNom = $redacteur?->nomComplet() ?? '—';
        $redTitre = $redacteur?->titre_poste ?? '';
        $projetNom = $cr->sprint?->projet?->libelle ?? 'SOUMAFÊ';
        $typeLabel = $cr->type === 'reunion_ordinaire' ? 'Réunion ordinaire' : 'Réunion extraordinaire';
        $headerLine = "SOUMAFÊ — {$projetNom} | Compte-rendu de {$typeLabel}";

        $xml = $this->docxHeader();

        // Logo centré (plus grand)
        if ($hasLogo) $xml .= $this->docxImage($logoPath, 1500000, 1050000);

        // Titre dans bloc bleu
        $typeTitre = $cr->type === 'reunion_ordinaire' ? 'RÉUNION ORDINAIRE' : 'RÉUNION EXTRAORDINAIRE';
        $xml .= $this->titreBloc("COMPTE RENDU DE {$typeTitre}");
        $xml .= $this->p('');

        // Encadré rédacteur
        $redLigne = "Rédigé par : {$redNom}";
        if ($redTitre) $redLigne .= " — {$redTitre}, SOUMAFÊ";
        $xml .= $this->encadre($redLigne);
        $xml .= $this->p('');

        // Tableau d'information
        $president = $cr->president?->nomComplet() ?? '—';
        $xml .= $this->docxInfoTable([
            ['Date', $cr->date_reunion->format('d/m/Y')],
            ['Heure début', $cr->heure_debut ?? '—'],
            ['Heure fin', $cr->heure_fin ?? '—'],
            ['Lieu', $cr->lieu ?? '—'],
            ['Président de séance', $president],
            ['Rédacteur', $redNom],
        ]);
        $xml .= $this->p('');

        // --- SECTION 1: CONTEXTE ET OBJECTIFS ---
        $xml .= $this->titreSection('1', 'CONTEXTE ET OBJECTIFS');
        $xml .= $this->sousTitre('1.1', 'Contexte');
        if (!empty($d['contexte'])) {
            $xml .= $this->htmlToOoxml($d['contexte'], '1.');
        } else {
            $xml .= $this->encadre('(Non renseigné)');
        }
        $xml .= $this->p('');

        if (!empty($d['ordre_du_jour'])) {
            $xml .= $this->sousTitre('1.2', 'Ordre du jour');
            $num = 1;
            foreach ($d['ordre_du_jour'] as $odj) {
                if (!empty($odj['point'])) {
                    $xml .= $this->listItemParagraph("{$num}.", $odj['point']);
                    $num++;
                }
            }
            $xml .= $this->p('');
        }

        // --- SECTION 2: LISTE DE PRÉSENCE ---
        if (!empty($d['presences'])) {
            $xml .= $this->titreSection('2', 'LISTE DE PRÉSENCE');
            $presRows = array_map(fn($p) => [$p['nom'], ($p['fonction'] ?? '') ?: '—', ($p['present'] ?? false) ? 'Présent' : 'Absent'], $d['presences']);
            $xml .= $this->docxTableMultiAlt(['Collaborateur', 'Fonction', 'Statut'], $presRows, [3800, 3800, 2000]);
            $xml .= $this->p('');
        }

        // --- SECTION 3: DÉROULEMENT ---
        $xml .= $this->titreSection('3', 'DÉROULEMENT DE LA RÉUNION');
        if (!empty($d['ordre_du_jour'])) {
            $sub = 1;
            foreach ($d['ordre_du_jour'] as $odj) {
                if (!empty($odj['point'])) {
                    $xml .= $this->sousTitre("3.{$sub}", $odj['point']);
                    $xml .= $this->htmlToOoxml(!empty($odj['contenu']) ? $odj['contenu'] : '(Non renseigné)', "3.{$sub}.");
                    $xml .= $this->p('');
                    $sub++;
                }
            }
        }

        // --- SECTION 4: DÉCISIONS ET ACTIONS ---
        $xml .= $this->titreSection('4', 'DÉCISIONS ET ACTIONS À MENER');
        if (!empty($d['decisions'])) {
            $decRows = [];
            foreach ($d['decisions'] as $i => $dec) {
                $rDec = $dec['responsables'] ?? (isset($dec['responsable']) && $dec['responsable'] ? [$dec['responsable']] : []);
                $prio = $dec['priorite'] ?? '—';
                $decRows[] = [(string)($i + 1), $dec['action'] ?? '', implode(', ', $rDec), $prio, $dec['echeance'] ?? ''];
            }
            $xml .= $this->docxTableMultiAlt(
                ['N°', 'Décision / Action', 'Responsable(s)', 'Priorité', 'Échéance'],
                $decRows, [600, 4200, 1800, 1200, 1800]
            );
        } else {
            $xml .= $this->p('(Aucune décision enregistrée)');
        }
        $xml .= $this->p('');

        // --- SECTION 5: CONCLUSION ---
        $xml .= $this->titreSection('5', 'CONCLUSION');
        if (!empty($d['conclusion'])) {
            $xml .= $this->htmlToOoxml($d['conclusion'], '5.');
        } else {
            $xml .= $this->encadre('(Non renseignée)');
        }

        // Signature
        $xml .= $this->signatureBloc($redNom, $redTitre, $cr->date_reunion->format('d/m/Y'));

        $xml .= $this->docxSectPr();
        $this->ecrireDocx($path, $xml, $hasLogo ? $logoPath : null, $headerLine);
    }

    private function genererDocxDemo(CompteRendu $cr, array $d, string $path): void
    {
        $logoPath = public_path('images/logo_soumafe.png');
        $hasLogo = file_exists($logoPath);
        $cr->load(['redacteur', 'president', 'sprint.projet']);

        $redacteur = $cr->redacteur;
        $redNom = $redacteur?->nomComplet() ?? '—';
        $redTitre = $redacteur?->titre_poste ?? '';
        $projetNom = $cr->sprint?->projet?->libelle ?? $d['projet'] ?? 'SOUMAFÊ';
        $sprintLabel = $d['sprint'] ?? 'Sprint';
        $headerLine = "SOUMAFÊ — {$projetNom} | Compte-rendu de démonstration — {$sprintLabel}";

        $xml = $this->docxHeader();

        if ($hasLogo) $xml .= $this->docxImage($logoPath, 1500000, 1050000);

        // Titre dans bloc bleu
        $xml .= $this->titreBloc('COMPTE RENDU DE DÉMONSTRATION');
        $xml .= $this->titreBloc($sprintLabel, 22);
        $xml .= $this->p('');

        // Encadré rédacteur
        $redLigne = "Rédigé par : {$redNom}";
        if ($redTitre) $redLigne .= " — {$redTitre}, SOUMAFÊ";
        $xml .= $this->encadre($redLigne);
        $xml .= $this->p('');

        // Tableau résumé projet
        $xml .= $this->docxInfoTable([
            ['Projet', $projetNom],
            ['Sprint', $sprintLabel],
            ['Période', ($d['date_debut'] ?? '') . ' au ' . ($d['date_fin'] ?? '')],
            ['Date démonstration', $cr->date_reunion->format('d/m/Y')],
            ['Score global', ($d['points_realises'] ?? 0) . '/' . ($d['total_points'] ?? 0) . ' pts (' . ($d['score_pourcent'] ?? 0) . '%)'],
        ]);
        $xml .= $this->p('');

        // --- Génération selon le format ---
        if (($d['format'] ?? '') === 'blocs_v1' && !empty($d['modules'])) {
            $xml .= $this->genererDocxDemoBlocs($d);
        } else {
            $xml .= $this->genererDocxDemoLegacy($d);
        }

        // Signature
        $xml .= $this->signatureBloc($redNom, $redTitre, $cr->date_reunion->format('d/m/Y'));

        $xml .= $this->docxSectPr();
        $this->ecrireDocx($path, $xml, $hasLogo ? $logoPath : null, $headerLine);
    }

    /** Génération DOCX blocs_v1 : modules > sections > blocs */
    private function genererDocxDemoBlocs(array $d): string
    {
        $xml = '';
        $taches = $d['taches'] ?? [];

        foreach ($d['modules'] as $mi => $mod) {
            $modNum = $mi + 1;
            $modTitre = mb_strtoupper($mod['titre'] ?? "Module {$modNum}");
            $xml .= $this->titreSection((string)$modNum, $modTitre);

            // Objectif du module
            if (!empty($mod['objectif'])) {
                $xml .= $this->encadre($mod['objectif']);
                $xml .= $this->p('');
            }

            foreach ($mod['sections'] ?? [] as $si => $sec) {
                $secNum = "{$modNum}." . ($si + 1);
                if (!empty($sec['titre'])) {
                    $xml .= $this->sousTitre($secNum, $sec['titre']);
                }

                foreach ($sec['blocs'] ?? [] as $bloc) {
                    $type = $bloc['type'] ?? '';

                    if ($type === 'texte' && !empty($bloc['contenu'])) {
                        $xml .= $this->htmlToOoxml($bloc['contenu'], "{$secNum}.");
                        $xml .= $this->p('');

                    } elseif ($type === 'tableau_taches' && !empty($taches)) {
                        $taskRows = [];
                        foreach ($taches as $ti => $t) {
                            $termine = !empty($t['termine']);
                            $statut = $termine ? 'Terminé' : ucfirst($t['statut'] ?? 'En cours');
                            $taskRows[] = [
                                (string)($ti + 1),
                                $t['tache'] ?? '',
                                (string)($t['points'] ?? 1),
                                $t['attribution'] ?? '—',
                                $statut,
                                $t['observation'] ?? '',
                            ];
                        }
                        $xml .= $this->docxTableMultiAlt(
                            ['N°', 'Tâche', 'PTS', 'Attribution', 'Statut', 'Observation'],
                            $taskRows, [500, 3200, 600, 1600, 1200, 2500]
                        );
                        $xml .= $this->p('');

                    } elseif ($type === 'score') {
                        $scorePct = $d['score_pourcent'] ?? 0;
                        $appreciation = $scorePct >= 90 ? 'Excellent' : ($scorePct >= 75 ? 'Bon' : ($scorePct >= 50 ? 'Acceptable' : 'Insuffisant'));
                        $xml .= $this->bilanBloc(
                            'Résultat global',
                            "Score : " . ($d['points_realises'] ?? 0) . "/" . ($d['total_points'] ?? 0) . " pts ({$scorePct}%) — {$appreciation}",
                            $scorePct >= 75 ? 'F0F7ED' : ($scorePct >= 50 ? 'FFF8E1' : 'FEECEC'),
                            $scorePct >= 75 ? '2E8B57' : ($scorePct >= 50 ? 'E6A817' : 'CC3333')
                        );
                        // Score par développeur si disponible
                        if (!empty($d['par_dev'])) {
                            $devScoreRows = array_map(fn($dev) => [
                                $dev['nom'], (string)$dev['realise'], (string)$dev['total'], $dev['pourcent'] . '%',
                            ], $d['par_dev']);
                            $xml .= $this->docxTableMultiAlt(
                                ['Développeur', 'Réalisé', 'Total', '%'],
                                $devScoreRows, [3600, 2000, 2000, 2000]
                            );
                        }
                        $xml .= $this->p('');

                    } elseif ($type === 'actions' && !empty($bloc['items'])) {
                        $actRows = [];
                        foreach ($bloc['items'] as $ai => $act) {
                            $rAct = $act['responsables'] ?? [];
                            $actRows[] = [
                                (string)($ai + 1),
                                $act['action'] ?? '',
                                implode(', ', $rAct) ?: '—',
                                $act['echeance'] ?? '—',
                            ];
                        }
                        $xml .= $this->docxTableMultiAlt(
                            ['N°', 'Action', 'Responsable(s)', 'Échéance'],
                            $actRows, [500, 4500, 2600, 2000]
                        );
                        $xml .= $this->p('');

                    } elseif ($type === 'reference' && !empty($bloc['contenu'])) {
                        $refType = $bloc['ref_type'] ?? 'remarque';
                        $xml .= $this->blocAnnote($refType, strip_tags($bloc['contenu']));
                        $xml .= $this->p('');
                    }
                }
            }
        }

        return $xml;
    }

    /** Génération DOCX ancien format (backward compat) */
    private function genererDocxDemoLegacy(array $d): string
    {
        $xml = '';
        $annotations = $d['annotations'] ?? [];

        // === SECTION 1: CONTEXTE ===
        $xml .= $this->titreSection('1', 'CONTEXTE DE LA DÉMONSTRATION');

        $xml .= $this->sousTitre('1.1', 'Sprint Goal');
        if (!empty($d['note_sprint'])) {
            $xml .= $this->encadre(strip_tags($d['note_sprint']));
        } else {
            $xml .= $this->encadre('(À renseigner)');
        }
        $xml .= $this->p('');

        $xml .= $this->sousTitre('1.2', 'Indicateurs clés');
        $totalTaches = count($d['taches'] ?? []);
        $tachesTerminees = count(array_filter($d['taches'] ?? [], fn($t) => !empty($t['termine'])));
        $tachesEnCours = $totalTaches - $tachesTerminees;
        $xml .= $this->docxTableMultiAlt(
            ['Tâches totales', 'Terminées', 'En cours', 'Points totaux', 'Score'],
            [[(string)$totalTaches, (string)$tachesTerminees, (string)$tachesEnCours, (string)($d['total_points'] ?? 0), ($d['score_pourcent'] ?? 0) . '%']],
            [1920, 1920, 1920, 1920, 1920]
        );
        $xml .= $this->p('');
        $xml .= $this->insererAnnotations($annotations, '1');

        // === SECTION 2: RÉSULTATS PAR TÂCHE ===
        $xml .= $this->titreSection('2', 'RÉSULTATS PAR TÂCHE');
        $parDev = [];
        foreach ($d['taches'] ?? [] as $t) {
            $dev = $t['attribution'] ?? '—';
            $parDev[$dev][] = $t;
        }
        $subNum = 1;
        foreach ($parDev as $devNom => $tachesDev) {
            $xml .= $this->sousTitre("2.{$subNum}", $devNom);
            $devRows = [];
            $devTotal = 0;
            $devRealise = 0;
            foreach ($tachesDev as $i => $t) {
                $pts = $t['points'] ?? 1;
                $devTotal += $pts;
                $termine = !empty($t['termine']);
                if ($termine) $devRealise += $pts;
                $statut = $termine ? 'Terminé' : ucfirst($t['statut'] ?? 'En cours');
                $devRows[] = [(string)($i + 1), $t['tache'] ?? '', $devNom, (string)$pts, $statut, $t['observation'] ?? ''];
            }
            $xml .= $this->docxTableMultiAlt(
                ['N°', 'Tâche', 'Attribution', 'PTS', 'Statut', 'Observation'],
                $devRows, [500, 3200, 1600, 600, 1200, 2500]
            );
            $devPct = $devTotal > 0 ? round($devRealise / $devTotal * 100) : 0;
            $xml .= $this->bilanBloc(
                "Bilan — {$devNom}",
                "Score : {$devRealise}/{$devTotal} pts ({$devPct}%)",
                $devPct >= 80 ? 'F0F7ED' : ($devPct >= 50 ? 'FFF8E1' : 'FEECEC'),
                $devPct >= 80 ? '2E8B57' : ($devPct >= 50 ? 'E6A817' : 'CC3333')
            );
            $xml .= $this->p('');
            $subNum++;
        }
        $xml .= $this->insererAnnotations($annotations, '2');

        // === SECTION 3: ÉVALUATION GLOBALE ===
        $xml .= $this->titreSection('3', 'ÉVALUATION GLOBALE DU SPRINT');
        $scorePct = $d['score_pourcent'] ?? 0;
        $appreciation = $scorePct >= 90 ? 'Excellent' : ($scorePct >= 75 ? 'Bon' : ($scorePct >= 50 ? 'Acceptable' : 'Insuffisant'));
        $xml .= $this->bilanBloc(
            'Résultat global',
            "Score : " . ($d['points_realises'] ?? 0) . "/" . ($d['total_points'] ?? 0) . " pts ({$scorePct}%) — Appréciation : {$appreciation}",
            $scorePct >= 75 ? 'F0F7ED' : ($scorePct >= 50 ? 'FFF8E1' : 'FEECEC'),
            $scorePct >= 75 ? '2E8B57' : ($scorePct >= 50 ? 'E6A817' : 'CC3333')
        );
        if (!empty($d['par_dev'])) {
            $xml .= $this->p('');
            $xml .= $this->sousTitre('3.1', 'Score par développeur');
            $devScoreRows = array_map(fn($dev) => [
                $dev['nom'], (string)$dev['realise'], (string)$dev['total'], $dev['pourcent'] . '%',
            ], $d['par_dev']);
            $xml .= $this->docxTableMultiAlt(
                ['Développeur', 'Réalisé', 'Total', '%'],
                $devScoreRows, [3600, 2000, 2000, 2000]
            );
        }
        $xml .= $this->p('');
        $xml .= $this->insererAnnotations($annotations, '3');

        // === SECTION 4: PROCHAINES ÉTAPES ===
        $xml .= $this->titreSection('4', 'PROCHAINES ÉTAPES');
        if (!empty($d['prochaines_etapes'])) {
            foreach ($d['prochaines_etapes'] as $i => $etape) {
                $num = $i + 1;
                $label = is_array($etape) ? ($etape['titre'] ?? $etape['action'] ?? '') : (string)$etape;
                $desc = is_array($etape) ? ($etape['description'] ?? '') : '';
                $xml .= $this->p("{$num}. {$label}", true);
                if ($desc) $xml .= $this->p("   {$desc}");
            }
        } else {
            $xml .= $this->p('(À définir)');
        }
        $xml .= $this->p('');
        $xml .= $this->insererAnnotations($annotations, '4');

        // === SECTION 5: PLAN D'ACTIONS ===
        $xml .= $this->titreSection('5', "PLAN D'ACTIONS");
        if (!empty($d['actions_a_mener'])) {
            $actRows = [];
            foreach ($d['actions_a_mener'] as $i => $a) {
                $rAct = $a['responsables'] ?? (isset($a['responsable']) && $a['responsable'] ? [$a['responsable']] : []);
                $prio = $a['priorite'] ?? '—';
                $actRows[] = [(string)($i + 1), $a['action'] ?? '', implode(', ', $rAct), $prio, $a['echeance'] ?? ''];
            }
            $xml .= $this->docxTableMultiAlt(
                ['N°', 'Action', 'Responsable(s)', 'Priorité', 'Échéance'],
                $actRows, [500, 3800, 2000, 1200, 2100]
            );
        } else {
            $xml .= $this->p('(À compléter)');
        }
        $xml .= $this->p('');
        $xml .= $this->insererAnnotations($annotations, '5');

        // === SECTION 6: CONCLUSION ===
        $xml .= $this->titreSection('6', 'CONCLUSION');
        if (!empty($d['conclusion'])) {
            $xml .= $this->htmlToOoxml($d['conclusion'], '6.');
        } else {
            $xml .= $this->encadre('(À renseigner)');
        }
        $xml .= $this->insererAnnotations($annotations, '6');

        return $xml;
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

    /** Section properties : A4, marges réduites, références header/footer */
    private function docxSectPr(): string
    {
        // A4 (11906 x 16838), marges 0.8" L/R (1152tw) 0.6" T/B (864tw)
        return '<w:sectPr>'
            . '<w:headerReference w:type="default" r:id="rId20"/>'
            . '<w:footerReference w:type="default" r:id="rId21"/>'
            . '<w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="864" w:right="1152" w:bottom="864" w:left="1152" w:header="432" w:footer="432" w:gutter="0"/>'
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

    /** Titre de section numéroté — bleu SOUMAFÊ */
    private function titreSection(string $num, string $text): string
    {
        $escaped = htmlspecialchars("{$num}. " . mb_strtoupper($text, 'UTF-8'), ENT_XML1, 'UTF-8');
        return '<w:p><w:pPr>'
            . '<w:spacing w:before="240" w:after="120" w:line="360" w:lineRule="auto"/>'
            . '<w:jc w:val="left"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:b/><w:caps/><w:color w:val="1F4E79"/>'
            . '<w:sz w:val="26"/><w:szCs w:val="26"/>'
            . '<w:u w:val="single"/>'
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    /** Sous-titre de section (ex: 2.1 Backend) */
    private function sousTitre(string $num, string $text): string
    {
        $escaped = htmlspecialchars("{$num} " . mb_strtoupper($text, 'UTF-8'), ENT_XML1, 'UTF-8');
        return '<w:p><w:pPr>'
            . '<w:spacing w:before="180" w:after="80" w:line="360" w:lineRule="auto"/>'
            . '<w:jc w:val="left"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:b/><w:caps/><w:color w:val="2E74B5"/>'
            . '<w:sz w:val="24"/><w:szCs w:val="24"/>'
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    /** Encadré — bloc avec fond coloré et bordure gauche épaisse */
    private function encadre(string $text, string $bgColor = 'E8EEF4', string $borderColor = '1F4E79'): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        return '<w:p><w:pPr>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bgColor}\"/>"
            . '<w:pBdr>'
            . "<w:left w:val=\"single\" w:sz=\"18\" w:space=\"4\" w:color=\"{$borderColor}\"/>"
            . '</w:pBdr>'
            . '<w:spacing w:before="60" w:after="60" w:line="360" w:lineRule="auto"/>'
            . '<w:ind w:left="120" w:right="120"/>'
            . '<w:jc w:val="both"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="22"/><w:szCs w:val="22"/>'
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    /** Bloc titre plein (fond bleu SOUMAFÊ, texte blanc, centré) */
    private function titreBloc(string $text, int $size = 28): string
    {
        $escaped = htmlspecialchars(mb_strtoupper($text, 'UTF-8'), ENT_XML1, 'UTF-8');
        return '<w:p><w:pPr>'
            . '<w:shd w:val="clear" w:color="auto" w:fill="1F4E79"/>'
            . '<w:spacing w:before="60" w:after="60" w:line="360" w:lineRule="auto"/>'
            . '<w:jc w:val="center"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:b/><w:caps/><w:color w:val="FFFFFF"/>'
            . "<w:sz w:val=\"{$size}\"/><w:szCs w:val=\"{$size}\"/>"
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    /** Bloc de bilan stylé (fond pastel + bordure gauche colorée) */
    private function bilanBloc(string $label, string $content, string $bgColor = 'F0F7ED', string $borderColor = '2E8B57'): string
    {
        $xml = '<w:p><w:pPr>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bgColor}\"/>"
            . '<w:pBdr>'
            . "<w:left w:val=\"single\" w:sz=\"18\" w:space=\"4\" w:color=\"{$borderColor}\"/>"
            . '</w:pBdr>'
            . '<w:spacing w:before="80" w:after="40" w:line="300" w:lineRule="auto"/>'
            . '<w:ind w:left="120" w:right="120"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . "<w:b/><w:color w:val=\"{$borderColor}\"/>"
            . '<w:sz w:val="22"/><w:szCs w:val="22"/>'
            . '</w:rPr>'
            . '<w:t xml:space="preserve">' . htmlspecialchars($label, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
        $xml .= '<w:p><w:pPr>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bgColor}\"/>"
            . '<w:pBdr>'
            . "<w:left w:val=\"single\" w:sz=\"18\" w:space=\"4\" w:color=\"{$borderColor}\"/>"
            . '</w:pBdr>'
            . '<w:spacing w:before="0" w:after="80" w:line="300" w:lineRule="auto"/>'
            . '<w:ind w:left="120" w:right="120"/>'
            . '<w:jc w:val="both"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="22"/><w:szCs w:val="22"/>'
            . '</w:rPr>'
            . '<w:t xml:space="preserve">' . htmlspecialchars($content, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
        return $xml;
    }

    /** Bloc de signature en fin de document */
    private function signatureBloc(string $nom, string $titre, string $date): string
    {
        $xml = $this->p('') . $this->p('');
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
        // Trait
        $xml .= '<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:line="360" w:lineRule="auto"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/><w:color w:val=\"1F4E79\"/></w:rPr>"
            . '<w:t xml:space="preserve">________________________</w:t></w:r></w:p>';
        // Nom
        $xml .= '<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:after="0" w:line="276" w:lineRule="auto"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:b/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/><w:color w:val=\"1F4E79\"/></w:rPr>"
            . '<w:t xml:space="preserve">' . htmlspecialchars($nom, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
        if ($titre) {
            $xml .= '<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:after="0" w:line="276" w:lineRule="auto"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:i/><w:sz w:val=\"20\"/><w:szCs w:val=\"20\"/><w:color w:val=\"666666\"/></w:rPr>"
                . '<w:t xml:space="preserve">' . htmlspecialchars($titre, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
        }
        $xml .= '<w:p><w:pPr><w:jc w:val="right"/><w:spacing w:after="0" w:line="276" w:lineRule="auto"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:sz w:val=\"20\"/><w:szCs w:val=\"20\"/><w:color w:val=\"666666\"/></w:rPr>"
            . '<w:t xml:space="preserve">' . htmlspecialchars($date, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
        return $xml;
    }

    /** Bloc d'annotation/référence stylé */
    private function blocAnnote(string $type, string $contenu): string
    {
        $types = [
            'remarque'        => ['label' => 'Remarque',        'icon' => "\xE2\x84\xB9", 'bg' => 'E8EEF4', 'border' => '2E74B5'],
            'attention'       => ['label' => 'Attention',       'icon' => "\xE2\x9A\xA0", 'bg' => 'FFF3E0', 'border' => 'E6A817'],
            'retenir'         => ['label' => "\xC3\x80 retenir",'icon' => "\xE2\x98\x85", 'bg' => 'F0F7ED', 'border' => '2E8B57'],
            'blocage'         => ['label' => 'Blocage',         'icon' => "\xE2\x9B\x94", 'bg' => 'FEECEC', 'border' => 'CC3333'],
            'recommandation'  => ['label' => 'Recommandation',  'icon' => "\xE2\x9E\xA4", 'bg' => 'F3E8F9', 'border' => '7B2D8E'],
            'bonne_pratique'  => ['label' => 'Bonne pratique',  'icon' => "\xE2\x9C\x93", 'bg' => 'E0F2FE', 'border' => '0288D1'],
            'rappel'          => ['label' => 'Rappel',          'icon' => "\xF0\x9F\x94\x94", 'bg' => 'E8F5E9', 'border' => '00897B'],
            'fondamental'     => ['label' => 'Fondamental',     'icon' => "\xE2\x98\x85", 'bg' => 'FFF8E1', 'border' => 'F9A825'],
            'methode'         => ['label' => 'Methode',         'icon' => "\xE2\x9A\x99", 'bg' => 'F3E8F9', 'border' => '7B2D8E'],
            'conseil'         => ['label' => 'Conseil',         'icon' => "\xF0\x9F\x92\xA1", 'bg' => 'FFF8E1', 'border' => 'EF6C00'],
            'definition'      => ['label' => 'Definition',      'icon' => "\xF0\x9F\x93\x96", 'bg' => 'E3F2FD', 'border' => '1565C0'],
            'exemple'         => ['label' => 'Exemple',         'icon' => "\xE2\x9C\xA6", 'bg' => 'F1F8E9', 'border' => '558B2F'],
        ];
        $cfg = $types[$type] ?? $types['remarque'];
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';

        // Ligne label (icône + titre en gras coloré)
        $xml = '<w:p><w:pPr>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$cfg['bg']}\"/>"
            . '<w:pBdr>'
            . "<w:left w:val=\"single\" w:sz=\"24\" w:space=\"6\" w:color=\"{$cfg['border']}\"/>"
            . "<w:top w:val=\"single\" w:sz=\"4\" w:space=\"1\" w:color=\"{$cfg['border']}\"/>"
            . "<w:bottom w:val=\"single\" w:sz=\"4\" w:space=\"1\" w:color=\"{$cfg['border']}\"/>"
            . "<w:right w:val=\"single\" w:sz=\"4\" w:space=\"1\" w:color=\"{$cfg['border']}\"/>"
            . '</w:pBdr>'
            . '<w:spacing w:before="120" w:after="40" w:line="300" w:lineRule="auto"/>'
            . '<w:ind w:left="180" w:right="120"/>'
            . '</w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
            . '<w:t xml:space="preserve">' . $cfg['icon'] . '  </w:t></w:r>'
            . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"{$cfg['border']}\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
            . '<w:t xml:space="preserve">' . htmlspecialchars($cfg['label'], ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';

        // Ligne contenu
        $xml .= '<w:p><w:pPr>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$cfg['bg']}\"/>"
            . '<w:pBdr>'
            . "<w:left w:val=\"single\" w:sz=\"24\" w:space=\"6\" w:color=\"{$cfg['border']}\"/>"
            . "<w:top w:val=\"none\" w:sz=\"0\" w:space=\"0\" w:color=\"auto\"/>"
            . "<w:bottom w:val=\"single\" w:sz=\"4\" w:space=\"1\" w:color=\"{$cfg['border']}\"/>"
            . "<w:right w:val=\"single\" w:sz=\"4\" w:space=\"1\" w:color=\"{$cfg['border']}\"/>"
            . '</w:pBdr>'
            . '<w:spacing w:before="0" w:after="120" w:line="300" w:lineRule="auto"/>'
            . '<w:ind w:left="180" w:right="120"/>'
            . '<w:jc w:val="both"/>'
            . '</w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:sz w:val=\"21\"/><w:szCs w:val=\"21\"/></w:rPr>"
            . '<w:t xml:space="preserve">' . htmlspecialchars($contenu, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';

        return $xml;
    }

    /** Insérer les annotations ciblant une section donnée */
    private function insererAnnotations(array $annotations, string $section): string
    {
        $xml = '';
        foreach ($annotations as $a) {
            if (($a['section'] ?? '') === $section && !empty($a['contenu'])) {
                $xml .= $this->blocAnnote($a['type'] ?? 'remarque', $a['contenu']);
            }
        }
        return $xml;
    }

    /** Table avec lignes alternées et largeurs personnalisables */
    private function docxTableMultiAlt(array $headers, array $rows, ?array $widths = null): string
    {
        $n = count($headers);
        $totalW = 9600;
        $defaultW = intdiv($totalW, $n);
        $xml = "<w:tbl><w:tblPr><w:tblW w:w=\"{$totalW}\" w:type=\"dxa\"/><w:tblBorders>" . $this->borders() . '</w:tblBorders></w:tblPr>';
        $xml .= '<w:tr>';
        foreach ($headers as $i => $h) $xml .= $this->cell($h, true, '1F4E79', 'FFFFFF', $widths[$i] ?? $defaultW);
        $xml .= '</w:tr>';
        foreach ($rows as $ri => $r) {
            $bg = ($ri % 2 === 1) ? 'F4F7FB' : null;
            $xml .= '<w:tr>';
            foreach ($r as $ci => $c) $xml .= $this->cell((string)$c, false, $bg, null, $widths[$ci] ?? $defaultW);
            $xml .= '</w:tr>';
        }
        return $xml . '</w:tbl>';
    }

    /** Générer le XML du header (en-tête de page) */
    private function genDocxHeaderXml(string $line): string
    {
        $escaped = htmlspecialchars($line, ENT_XML1, 'UTF-8');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:p><w:pPr>'
            . '<w:pBdr><w:bottom w:val="single" w:sz="4" w:space="1" w:color="1F4E79"/></w:pBdr>'
            . '<w:jc w:val="center"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="16"/><w:szCs w:val="16"/>'
            . '<w:i/><w:color w:val="1F4E79"/>'
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>"
            . '</w:hdr>';
    }

    /** Générer le XML du footer (pied de page avec numéro) */
    private function genDocxFooterXml(string $line = 'Confidentiel — Usage interne'): string
    {
        $escaped = htmlspecialchars($line, ENT_XML1, 'UTF-8');
        $rpr = '<w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="16"/><w:szCs w:val="16"/>'
            . '<w:i/><w:color w:val="999999"/>'
            . '</w:rPr>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:p><w:pPr>'
            . '<w:pBdr><w:top w:val="single" w:sz="4" w:space="1" w:color="1F4E79"/></w:pBdr>'
            . '<w:jc w:val="center"/>'
            . '</w:pPr>'
            . "<w:r>{$rpr}<w:t xml:space=\"preserve\">{$escaped} — Page </w:t></w:r>"
            . "<w:r>{$rpr}<w:fldChar w:fldCharType=\"begin\"/></w:r>"
            . "<w:r>{$rpr}<w:instrText xml:space=\"preserve\"> PAGE </w:instrText></w:r>"
            . "<w:r>{$rpr}<w:fldChar w:fldCharType=\"end\"/></w:r>"
            . '</w:p></w:ftr>';
    }

    /** Nettoyer le HTML provenant du contenteditable (garder uniquement b, u, i, ol, ul, li, br, div) */
    private function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, '<b><u><i><strong><em><ol><ul><li><br><div><p><blockquote>');
        // Marque la liste hiérarchique avant de retirer les attributs (style, class… collés)
        $html = preg_replace('/<ol\b[^>]*\bclass="[^"]*\bhier\b[^"]*"[^>]*>/i', '<ol data-hier>', $html);
        $html = preg_replace('/<(\/?)(b|u|i|strong|em|ul|li|br|div|p|blockquote)\b[^>]*>/i', '<$1$2>', $html);
        $html = preg_replace('/<ol\b(?![^>]*data-hier)[^>]*>/i', '<ol>', $html);
        return str_replace('<ol data-hier>', '<ol class="hier">', $html);
    }

    /** Convertir HTML (du contenteditable) en paragraphes OOXML Times New Roman 12pt */
    /** $prefixe : numéro de la section parente (ex. "3.2.") préfixant les listes hiérarchiques */
    private function htmlToOoxml(string $html, string $prefixe = ''): string
    {
        if (!str_contains($html, '<')) {
            return $this->p($html);
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $racine = $dom->getElementsByTagName('div')->item(0);
        if (!$racine) return $this->p(strip_tags($html));

        $xml = '';
        $segs = [];
        $this->ooxmlBlocs($racine, 0, $prefixe, $segs, $xml);
        $xml .= $this->ooxmlPara($segs, 0);

        return $xml ?: $this->p('(Non renseigné)');
    }

    private function ooxmlBlocs(\DOMNode $node, int $niveau, string $prefixe, array &$segs, string &$xml): void
    {
        foreach ($node->childNodes as $c) {
            $tag = $c instanceof \DOMElement ? strtolower($c->tagName) : null;
            if ($tag === null || in_array($tag, ['b', 'strong', 'i', 'em', 'u', 'span', 'a'])) {
                $this->ooxmlInline($c, [], $segs);
            } elseif ($tag === 'br') {
                $xml .= $this->ooxmlPara($segs, $niveau);
            } elseif (in_array($tag, ['ul', 'ol'])) {
                $xml .= $this->ooxmlPara($segs, $niveau);
                $xml .= $this->ooxmlListe($c, $niveau, false, '', $prefixe);
            } elseif ($tag === 'blockquote') {
                $xml .= $this->ooxmlPara($segs, $niveau);
                $this->ooxmlBlocs($c, $niveau + 1, $prefixe, $segs, $xml);
                $xml .= $this->ooxmlPara($segs, $niveau + 1);
            } else {
                $xml .= $this->ooxmlPara($segs, $niveau);
                $this->ooxmlBlocs($c, $niveau, $prefixe, $segs, $xml);
                $xml .= $this->ooxmlPara($segs, $niveau);
            }
        }
    }

    private function ooxmlListe(\DOMElement $liste, int $niveau, bool $hier, string $chemin, string $prefixe): string
    {
        $ordonnee = strtolower($liste->tagName) === 'ol';
        $hier = $hier || ($ordonnee && str_contains(' ' . $liste->getAttribute('class') . ' ', ' hier '));
        $xml = '';
        $n = 0;
        foreach ($liste->childNodes as $c) {
            if (!$c instanceof \DOMElement) continue;
            $tag = strtolower($c->tagName);
            if (in_array($tag, ['ul', 'ol'])) {
                // Liste imbriquée directement dans la liste (produite par l'indentation du navigateur)
                $xml .= $this->ooxmlListe($c, $niveau + 1, $hier, $chemin . max($n, 1) . '.', $prefixe);
                continue;
            }
            $n++;
            $label = !$ordonnee ? "\xE2\x80\xA2" : ($hier ? $prefixe . $chemin . $n . '.' : $n . '.');
            $segs = [];
            $sousListes = '';
            foreach ($c->childNodes as $lc) {
                if ($lc instanceof \DOMElement && in_array(strtolower($lc->tagName), ['ul', 'ol'])) {
                    $sousListes .= $this->ooxmlListe($lc, $niveau + 1, $hier, $chemin . $n . '.', $prefixe);
                } else {
                    $this->ooxmlInline($lc, [], $segs);
                }
            }
            $xml .= $this->ooxmlPara($segs, $niveau, $label) . $sousListes;
        }
        return $xml;
    }

    private function ooxmlInline(\DOMNode $node, array $fmt, array &$segs): void
    {
        if ($node instanceof \DOMText) {
            $segs[] = ['text' => $node->wholeText, 'fmt' => $fmt];
            return;
        }
        if (!$node instanceof \DOMElement) return;
        $tag = strtolower($node->tagName);
        if (in_array($tag, ['br', 'p', 'div'])) $segs[] = ['text' => ' ', 'fmt' => $fmt];
        if (in_array($tag, ['b', 'strong'])) $fmt['b'] = true;
        if (in_array($tag, ['i', 'em'])) $fmt['i'] = true;
        if ($tag === 'u') $fmt['u'] = true;
        foreach ($node->childNodes as $c) $this->ooxmlInline($c, $fmt, $segs);
    }

    /** Vide $segs dans un paragraphe ; avec $label, paragraphe de liste (puce ou numéro) */
    private function ooxmlPara(array &$segs, int $niveau, ?string $label = null): string
    {
        $texte = '';
        foreach ($segs as $s) $texte .= $s['text'];
        if ($label === null && trim($texte) === '') { $segs = []; return ''; }

        $rpr = fn(array $f) => '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="24"/><w:szCs w:val="24"/>'
            . (!empty($f['b']) ? '<w:b/>' : '') . (!empty($f['u']) ? '<w:u w:val="single"/>' : '') . (!empty($f['i']) ? '<w:i/>' : '')
            . '</w:rPr>';

        $runs = '';
        $debut = true;
        foreach ($segs as $s) {
            $t = preg_replace('/\s+/u', ' ', $s['text']);
            if ($debut) { $t = ltrim($t); if ($t !== '') $debut = false; }
            if ($t === '') continue;
            $runs .= '<w:r>' . $rpr($s['fmt']) . '<w:t xml:space="preserve">' . htmlspecialchars($t, ENT_XML1, 'UTF-8') . '</w:t></w:r>';
        }
        $segs = [];

        $pPr = '<w:jc w:val="both"/><w:spacing w:line="360" w:lineRule="auto"/>';
        if ($label !== null) {
            $suspendu = mb_strlen($label) > 3 ? 720 : 360;
            $gauche = 360 + ($niveau + 1) * 360 + $suspendu;
            $pPr .= "<w:ind w:left=\"{$gauche}\" w:hanging=\"{$suspendu}\"/><w:tabs><w:tab w:val=\"left\" w:pos=\"{$gauche}\"/></w:tabs>";
            $runs = '<w:r>' . $rpr([]) . '<w:t xml:space="preserve">' . htmlspecialchars($label, ENT_XML1, 'UTF-8') . '</w:t></w:r><w:r><w:tab/></w:r>' . $runs;
        } elseif ($niveau > 0) {
            $pPr .= '<w:ind w:left="' . ($niveau * 720) . '"/>';
        }

        return "<w:p><w:pPr>{$pPr}</w:pPr>{$runs}</w:p>";
    }

    /** Convertir une ligne HTML avec <b>, <u>, <i> en un paragraphe OOXML avec runs formatés */
    private function richParagraph(string $html, bool $isListItem = false): string
    {
        $spacing = '<w:spacing w:line="360" w:lineRule="auto"/>';
        $indent = $isListItem ? '<w:ind w:left="720" w:hanging="360"/>' : '';
        $p = "<w:p><w:pPr><w:jc w:val=\"both\"/>{$spacing}{$indent}</w:pPr>";

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

    /** Paragraphe de liste (puce/numéro + texte) avec indentation OOXML propre */
    private function listItemParagraph(string $bullet, string $text): string
    {
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
             . '<w:sz w:val="24"/><w:szCs w:val="24"/>';
        $spacing = '<w:spacing w:line="360" w:lineRule="auto"/>';
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        $bulletEsc = htmlspecialchars($bullet, ENT_XML1, 'UTF-8');

        return '<w:p><w:pPr>'
            . '<w:jc w:val="both"/>'
            . $spacing
            . '<w:ind w:left="720" w:hanging="360"/>'
            . '<w:tabs><w:tab w:val="left" w:pos="720"/></w:tabs>'
            . '</w:pPr>'
            // Run 1 : puce/numéro
            . "<w:r><w:rPr>{$rpr}</w:rPr>"
            . "<w:t xml:space=\"preserve\">{$bulletEsc}</w:t></w:r>"
            // Tab entre puce et texte
            . '<w:r><w:tab/></w:r>'
            // Run 2 : texte
            . "<w:r><w:rPr>{$rpr}</w:rPr>"
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r>"
            . '</w:p>';
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

    private function ecrireDocx(string $path, string $documentXml, ?string $logoPath = null, string $headerLine = '', string $footerLine = 'Confidentiel — Usage interne'): void
    {
        if (file_exists($path)) unlink($path);
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);

        $imgContentType = '';
        if ($logoPath && file_exists($logoPath)) {
            $zip->addFile($logoPath, 'word/media/logo.png');
            $imgContentType = '<Default Extension="png" ContentType="image/png"/>';
        }

        // Header & Footer XML parts
        $zip->addFromString('word/header1.xml', $this->genDocxHeaderXml($headerLine));
        $zip->addFromString('word/footer1.xml', $this->genDocxFooterXml($footerLine));

        $zip->addFromString('[Content_Types].xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . $imgContentType
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>'
            . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
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
            . '<Relationship Id="rId20" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>'
            . '<Relationship Id="rId21" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
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
