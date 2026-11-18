<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, CompteRendu, NotificationApp, Projet, Sprint, Tache, User, UserStory, Blocage, Evenement};
use App\Services\{MailService, RapportReview, ScrumService};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class CompteRenduController extends Controller
{
    /* ===== LISTE ===== */
    public function index(Request $r)
    {
        $user = $r->user();
        $query = CompteRendu::with(['redacteur', 'president', 'sprint.projet'])->orderByDesc('date_reunion');

        if (!$user->estPilote() && !$user->aRole('direction')) {
            $query->where(fn($q) => $q->where('redacteur_id', $user->id)->orWhere('statut', 'confirme'));
            $query->where(fn($q) => $q->whereNotIn('type', ['review_sprint', 'retrospective'])
                ->orWhereHas('sprint.projet', fn($p) => $p->where('responsable_projet_id', $user->id)
                    ->orWhereHas('collaborateurs', fn($c) => $c->where('user_id', $user->id)->where('actif', true))));
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

        $scribe = \App\Services\RedacteurDuJour::id(today());
        return view('comptes-rendus.create', compact('type', 'collaborateurs', 'tousUsers', 'sprints', 'projets', 'sprintId', 'user', 'scribe'));
    }

    /** Scribe du jour au planning des réunions : rédacteur proposé pour les rapports de cette date. */
    public function scribe(Request $r)
    {
        $r->validate(['date' => 'required|date']);
        $u = \App\Services\RedacteurDuJour::user($r->date);
        return response()->json(['id' => $u?->id, 'nom' => $u?->nomComplet()]);
    }

    public function designer(Request $r)
    {
        $user = $r->user();
        abort_unless($user->estPilote(), 403);

        $v = $r->validate([
            'type'          => 'required|in:reunion_ordinaire,reunion_extraordinaire,demo_sprint',
            'redacteur_id'  => 'required_unless:type,demo_sprint|nullable|exists:users,id',
            'president_id'  => 'nullable|exists:users,id',
            'date_reunion'  => 'required|date',
            'heure_debut'   => 'nullable|string|max:10',
            'heure_fin'     => 'nullable|string|max:10',
            'titre'         => 'required|string|max:255',
            'lieu'          => 'nullable|string|max:255',
            'sprint_id'     => 'required_if:type,demo_sprint|nullable|exists:sprints,id',
        ], ['sprint_id.required_if' => 'Choisissez le sprint du rapport de review : ses user stories et ses tâches en sont reprises.']);

        // Rapport de review : le pilote en est le rédacteur (il peut ensuite passer la main au responsable du projet)
        if ($v['type'] === 'demo_sprint') $v['redacteur_id'] = $user->id;

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

            // Notification rédacteur (sauf s'il s'est désigné lui-même)
            if (Schema::hasTable('notifications_app') && (int) $v['redacteur_id'] !== (int) $user->id) {
                NotificationApp::create([
                    'user_id' => $v['redacteur_id'],
                    'type'    => 'designation_cr',
                    'lu' => false, 'canal' => 'in_app',
                    'contenu' => "Vous êtes désigné(e) rédacteur du {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y'),
                    'lien'    => route('comptes-rendus.edit', $cr),
                ]);
            }

            // E-mail au rédacteur
            $redacteur = (int) $v['redacteur_id'] !== (int) $user->id ? User::find($v['redacteur_id']) : null;
            if ($redacteur) {
                MailService::notifier(
                    $redacteur,
                    'Désignation rédacteur',
                    "Vous êtes désigné(e) rédacteur du {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y') . " par " . $user->nomComplet() . ".\nMerci de compléter le compte rendu dans l'application.",
                    route('comptes-rendus.edit', $cr)
                );
            }

            // Rapport de review : les développeurs alimentent l'observation de leurs tâches par leurs commentaires et blocages
            if ($cr->type === 'demo_sprint' && $cr->sprint && Schema::hasTable('notifications_app')) {
                foreach (ScrumService::membres($cr->sprint)->reject(fn($m) => $m->estPilote()) as $m) {
                    NotificationApp::create(['user_id' => $m->id, 'type' => 'designation_cr', 'lu' => false, 'canal' => 'in_app',
                        'contenu' => "Rapport de review du sprint {$cr->sprint->numero} en préparation : vos commentaires et blocages sur vos tâches y sont repris. Complétez-les si besoin.",
                        'lien' => route('taches.index')]);
                }
            }
            return redirect()->route('comptes-rendus.edit', $cr)->with('ok', $cr->type === 'demo_sprint'
                ? 'Rapport préparé : tableaux et observations des tâches pré-remplis.' : 'Rédacteur désigné. Le formulaire est pré-rempli.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->withErrors(['erreur' => $e->getMessage()]);
        }
    }

    /* ===== ÉDITION ===== */
    /**
     * Le pilote garde la main sur toutes les rubriques, à toutes les étapes (confirmé : le DOCX est régénéré).
     * Brouillon : aussi le rédacteur (pour le rapport de review, le responsable du projet si le pilote lui a passé la main).
     * Rapport de review en relecture : aussi le relecteur.
     */
    private function peutModifier(User $u, CompteRendu $cr): bool
    {
        if ($u->estPilote()) return true;
        if ($cr->estConfirme()) return false;
        if ($cr->type === 'demo_sprint' && $cr->estEnRevision()) return RapportReview::estRelecteur($u, $cr);
        return (int) $cr->redacteur_id === (int) $u->id;
    }

    public function edit(Request $r, CompteRendu $cr)
    {
        if ($cr->estReviewV1()) return redirect()->route('comptes-rendus.review-sprint.edit', $cr);
        if ($cr->estScrum() && $cr->lienScrum()) return redirect($cr->lienScrum());
        $user = $r->user();
        if (!$this->peutModifier($user, $cr) && $cr->type === 'demo_sprint' && (int) $cr->redacteur_id === (int) $user->id) {
            return redirect()->route('comptes-rendus.show', $cr)->with('ok', $cr->estConfirme() ? 'Rapport validé.' : 'Rapport transmis au pilote : il le valide ou vous le renvoie.');
        }
        abort_unless($this->peutModifier($user, $cr), 403);

        $collaborateurs = User::where('actif', true)
            ->orWhereIn('id', array_filter([$cr->redacteur_id, $cr->president_id]))
            ->orderBy('name')->get();
        $projets = Projet::where('statut', 'actif')->orderBy('libelle')->get();
        // Rapport sans sprint (créé avant que le sprint soit obligatoire) : le pilote peut en rattacher un
        $sprintsRattachables = $cr->type === 'demo_sprint' && !$cr->sprint_id && $user->estPilote()
            ? Sprint::with('projet')->whereIn('statut', ['en_cours', 'termine'])->orderByDesc('date_debut')->get() : collect();
        $donnees = $cr->getDonnees();

        // Garantir que la liste de présence existe pour les réunions
        if ($cr->estReunion() && empty($donnees['presences'])) {
            $donnees['presences'] = User::where('actif', true)->orderBy('name')->get()
                ->map(fn($u) => ['nom' => $u->nomComplet(), 'fonction' => $u->roleLibelle(), 'present' => true])->toArray();
            $cr->setDonnees($donnees);
            $cr->save();
        }

        $cr->load(['redacteur', 'president', 'sprint']);
        if ($cr->type === 'demo_sprint') $donnees = $this->libellesDemo($donnees);

        $peutConfirmer = $cr->type === 'demo_sprint' ? $user->estPilote() : ($user->estPilote() || $cr->redacteur_id === $user->id);
        // Rapport de review : circuit (rédaction, relecture, validation), main du rédacteur, indicateurs
        $circuit = $cr->type === 'demo_sprint' ? [
            'relecteurs' => RapportReview::relecteurs($cr),
            'relecteur' => RapportReview::estRelecteur($user, $cr),
            'responsable' => RapportReview::responsable($cr),
            'mainResponsable' => RapportReview::mainAuResponsable($cr),
            'indicateurs' => RapportReview::indicateurs($donnees, $cr->sprint),
            'verdicts' => RapportReview::VERDICTS,
        ] : null;
        return view('comptes-rendus.edit', compact('cr', 'collaborateurs', 'projets', 'donnees', 'user', 'peutConfirmer', 'sprintsRattachables', 'circuit'));
    }

    /**
     * Rapport de review : le pilote passe la main au responsable du projet pour la rédaction, ou la reprend.
     * Le pilote garde ses droits sur toutes les rubriques ; la validation lui reste réservée.
     */
    public function passerMain(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($cr->type === 'demo_sprint' && $user->estPilote(), 403);
        if (!$cr->estBrouillon()) return back()->with('erreur', 'La main ne se passe que pendant la rédaction du rapport.');
        $v = $r->validate(['a' => 'required|in:responsable,pilote']);
        $d = $cr->getDonnees();
        $sprint = $cr->sprint;
        if ($v['a'] === 'responsable') {
            $resp = RapportReview::responsable($cr);
            if (!$resp) return back()->with('erreur', "Le projet n'a pas de responsable (hors pilotes) à qui passer la main.");
            $cr->redacteur_id = $resp->id;
            $d['main'] = ['a' => $resp->nomComplet(), 'par' => $user->nomComplet(), 'le' => now()->format('d/m/Y H:i')];
            RapportReview::notifier($resp->id, "{$user->nomComplet()} vous passe la main pour rédiger le rapport de review du sprint {$sprint?->numero}.", route('comptes-rendus.edit', $cr));
            MailService::notifier($resp, 'Rapport de review à rédiger',
                "{$user->nomComplet()} vous confie la rédaction du rapport de review du sprint {$sprint?->numero} ({$sprint?->projet?->libelle}).\nLe pilote garde la main et valide le rapport.",
                route('comptes-rendus.edit', $cr));
            $message = "Main passée à {$resp->nomComplet()} : il rédige le rapport, vous gardez la main et la validation.";
        } else {
            $ancien = $cr->redacteur_id;
            $cr->redacteur_id = $user->id;
            unset($d['main']);
            if ($ancien && (int) $ancien !== (int) $user->id) RapportReview::notifier($ancien, "{$user->nomComplet()} a repris la rédaction du rapport de review du sprint {$sprint?->numero}.", route('comptes-rendus.show', $cr));
            $message = 'Vous avez repris la main sur la rédaction du rapport.';
        }
        $cr->setDonnees($d);
        $cr->save();
        AuditLog::log('main_rapport_review', 'comptes_rendus', $cr->titre, $message);
        return back()->with('ok', $message);
    }

    /** CR Démo : état actuel du sprint (stories, tâches) pour actualiser les tableaux de l'éditeur. */
    public function sprintActuel(Request $r, CompteRendu $cr)
    {
        abort_unless($cr->type === 'demo_sprint' && $cr->sprint_id, 404);
        abort_unless($r->user()->estPilote() || (int) $cr->redacteur_id === (int) $r->user()->id, 403);
        $frais = $this->preRemplirDemo($cr->sprint_id);
        return response()->json(['stories' => $frais['stories'], 'taches' => $frais['taches']]);
    }

    public function update(Request $r, CompteRendu $cr)
    {
        if ($cr->estReviewV1()) return redirect()->route('comptes-rendus.review-sprint.edit', $cr);
        if ($cr->estScrum()) return redirect($cr->lienScrum() ?? route('comptes-rendus.index'));
        $user = $r->user();
        abort_unless($this->peutModifier($user, $cr), 403);

        /* ---- Demo sprint : bloc editor JSON ---- */
        if (!$cr->estReunion() && $r->filled('bloc_data')) {
            $donnees = json_decode($r->input('bloc_data'), true) ?: [];
            // Sanitiser le HTML dans les blocs texte et reference
            if (!empty($donnees['modules'])) {
                // Parcours par référence : sans « ?? » (qui parcourrait une copie et perdrait le nettoyage)
                foreach ($donnees['modules'] as &$mod) {
                    $mod['sections'] = is_array($mod['sections'] ?? null) ? $mod['sections'] : [];
                    foreach ($mod['sections'] as &$sec) {
                        $sec['blocs'] = is_array($sec['blocs'] ?? null) ? $sec['blocs'] : [];
                        foreach ($sec['blocs'] as &$blc) {
                            if (in_array($blc['type'] ?? '', ['texte', 'reference']) && isset($blc['contenu'])) {
                                $blc['contenu'] = $this->sanitizeHtml($blc['contenu']);
                            }
                            // Couleur, largeur, fonctionnalité ; sous-titre et commentaire en texte simple
                            $blc['couleur'] = array_key_exists($blc['couleur'] ?? '', self::BLOC_COULEURS) ? $blc['couleur'] : null;
                            $blc['largeur'] = ($blc['largeur'] ?? '') === 'demi' ? 'demi' : null;
                            $blc['module'] = isset($blc['module']) ? (mb_substr(trim(strip_tags((string) $blc['module'])), 0, 150) ?: null) : null;
                            $court = fn($v, $max = 300) => mb_substr(trim(strip_tags((string) ($v ?? ''))), 0, $max);
                            if (($blc['type'] ?? '') === 'verdict') {
                                $blc['verdict'] = array_key_exists($blc['verdict'] ?? '', RapportReview::VERDICTS) ? $blc['verdict'] : '';
                                $blc['contenu'] = $this->sanitizeHtml((string) ($blc['contenu'] ?? ''));
                            }
                            if (($blc['type'] ?? '') === 'tableau_backlog') {
                                $blc['items'] = array_values(array_filter(array_map(fn($it) => [
                                    'user_story_id' => (int) ($it['user_story_id'] ?? 0) ?: null,
                                    'numero' => $court($it['numero'] ?? '', 30), 'module' => $court($it['module'] ?? '', 150), 'intitule' => $court($it['intitule'] ?? '', 2000),
                                    'moscow' => array_key_exists($it['moscow'] ?? '', UserStory::MOSCOW) ? $it['moscow'] : '',
                                    'points' => is_numeric($it['points'] ?? null) ? (int) $it['points'] : null,
                                ], array_filter(is_array($blc['items'] ?? null) ? $blc['items'] : [], 'is_array')), fn($it) => $it['intitule'] !== ''));
                            }
                            if (($blc['type'] ?? '') === 'validation') {
                                $blc['items'] = array_values(array_filter(array_map(fn($it) => [
                                    'role' => $court($it['role'] ?? '', 100), 'nom' => $court($it['nom'] ?? '', 150),
                                    'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($it['date'] ?? '')) ? $it['date'] : '',
                                ], array_filter(is_array($blc['items'] ?? null) ? $blc['items'] : [], 'is_array')), fn($it) => $it['role'] . $it['nom'] !== ''));
                            }
                            if (($blc['type'] ?? '') === 'sous_titre') $blc['texte'] = mb_substr(trim(strip_tags((string) ($blc['texte'] ?? ''))), 0, 200);
                            if (($blc['type'] ?? '') === 'commentaire') {
                                $blc['contenu'] = mb_substr(trim(strip_tags((string) ($blc['contenu'] ?? ''))), 0, 3000);
                                $blc['auteur'] = mb_substr(trim(strip_tags((string) ($blc['auteur'] ?? ''))), 0, 150) ?: $user->nomComplet();
                                $blc['date'] = preg_match('#^\d{2}/\d{2}/\d{4}$#', (string) ($blc['date'] ?? '')) ? $blc['date'] : now()->format('d/m/Y');
                            }
                        }
                    }
                }
                unset($mod, $sec, $blc);
            }
            // Tableaux : rien n'est figé, les lignes saisies (ou actualisées depuis le sprint) sont reprises nettoyées
            $txt = fn($v, $max = 500) => mb_substr(trim(strip_tags((string) ($v ?? ''))), 0, $max);
            $donnees['stories'] = array_values(array_map(fn($x) => [
                'id' => (int) ($x['id'] ?? 0) ?: null, 'numero' => $txt($x['numero'] ?? '', 30), 'intitule' => $txt($x['intitule'] ?? '', 2000),
                'module' => $txt($x['module'] ?? '', 150), 'epic' => $txt($x['epic'] ?? '', 150) ?: null,
                'niveau' => max(0, min(100, (int) ($x['niveau'] ?? 0))), 'origine' => $txt($x['origine'] ?? '', 20),
                'points' => is_numeric($x['points'] ?? null) ? (int) $x['points'] : null, 'statut' => $txt($x['statut'] ?? '', 40),
                'taches' => (int) ($x['taches'] ?? 0), 'taches_terminees' => (int) ($x['taches_terminees'] ?? 0),
            ], array_filter(is_array($donnees['stories'] ?? null) ? $donnees['stories'] : [], 'is_array')));
            $donnees['taches'] = array_values(array_map(fn($t) => [
                'tache_id' => (int) ($t['tache_id'] ?? 0) ?: null, 'user_id' => (int) ($t['user_id'] ?? 0) ?: null,
                'tache' => $txt($t['tache'] ?? '', 1000), 'points' => max(0, (int) ($t['points'] ?? 1)), 'attribution' => $txt($t['attribution'] ?? '', 150),
                'statut' => $txt($t['statut'] ?? '', 30), 'termine' => filter_var($t['termine'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'observation' => $txt($t['observation'] ?? '', RapportReview::OBSERVATION_MAX), 'observation_auto' => $txt($t['observation_auto'] ?? '', RapportReview::OBSERVATION_MAX), 'us' => $txt($t['us'] ?? '', 30) ?: null, 'us_contexte' => $txt($t['us_contexte'] ?? '', 300) ?: null,
                'module' => $txt($t['module'] ?? '', 150) ?: null,
            ], array_filter(is_array($donnees['taches'] ?? null) ? $donnees['taches'] : [], fn($t) => is_array($t) && trim($t['tache'] ?? '') !== '')));
            $donnees['objectif'] = $cr->getDonnees()['objectif'] ?? $cr->sprint?->objectif;
            // Libellés des fonctionnalités importées du backlog : reformulés par le pilote, pour ce rapport
            $anciens = $cr->getDonnees()['libelles_modules'] ?? [];
            if ($user->estPilote()) {
                $connus = collect($donnees['stories'] ?? [])->pluck('module')->merge(collect($donnees['taches'] ?? [])->map(fn($t) => self::moduleTache($t)))->filter()->unique()->all();
                $libs = [];
                foreach (is_array($donnees['libelles_modules'] ?? null) ? $donnees['libelles_modules'] : [] as $orig => $lib) {
                    $lib = mb_substr(trim(strip_tags((string) $lib)), 0, 150);
                    if (in_array($orig, $connus, true) && $lib !== '' && $lib !== $orig) $libs[$orig] = $lib;
                }
                $donnees['libelles_modules'] = $libs;
            } else {
                $donnees['libelles_modules'] = $anciens;
            }
            // Tenus par le serveur : relecture, main du rédacteur, tâches de suivi déjà créées (et anciennes contributions)
            foreach (['contributions', 'relecture', 'suivi', 'main'] as $k) {
                if (isset($cr->getDonnees()[$k])) $donnees[$k] = $cr->getDonnees()[$k]; else unset($donnees[$k]);
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
        $palette = implode(',', array_column(self::COULEURS, 1));
        $mef = $r->validate([
            'mise_en_forme' => 'nullable|array',
            'mise_en_forme.titre' => 'nullable|numeric|between:8,36', 'mise_en_forme.section' => 'nullable|numeric|between:8,36',
            'mise_en_forme.sous_titre' => 'nullable|numeric|between:8,36', 'mise_en_forme.texte' => 'nullable|numeric|between:8,36',
            'mise_en_forme.couleur_section' => "nullable|in:{$palette}", 'mise_en_forme.couleur_sous_titre' => "nullable|in:{$palette}",
            'mise_en_forme.police' => 'nullable|in:' . implode(',', array_keys(self::POLICES)),
            'mise_en_forme.entete' => 'nullable|string|max:150', 'mise_en_forme.pied' => 'nullable|string|max:150',
            'mise_en_forme.num_page' => 'nullable|boolean', 'mise_en_forme.logo' => 'nullable|boolean',
        ])['mise_en_forme'] ?? null;
        if (is_array($mef)) {
            $donnees['mise_en_forme'] = array_map(fn($v) => $v === null || $v === '' ? null : (float) $v,
                array_intersect_key($mef, array_flip(['titre', 'section', 'sous_titre', 'texte'])))
                + array_map(fn($v) => $v ?: null, array_intersect_key($mef, array_flip(['couleur_section', 'couleur_sous_titre', 'police'])))
                // En-tête et pied de page : textes libres (vides = textes par défaut), numéro de page et logo affichés par défaut
                + array_map(fn($v) => trim((string) $v) ?: null, array_intersect_key($mef, array_flip(['entete', 'pied'])))
                + array_map(fn($v) => (bool) $v, array_intersect_key($mef, array_flip(['num_page', 'logo'])));
        } elseif (isset($cr->getDonnees()['mise_en_forme'])) {
            $donnees['mise_en_forme'] = $cr->getDonnees()['mise_en_forme'];
        }
        $cr->setDonnees($donnees);
        // Rattachement d'un sprint à un rapport qui n'en avait pas : tableaux, score et Sprint Goal repris du sprint
        if ($cr->type === 'demo_sprint' && !$cr->sprint_id && $user->estPilote() && $r->filled('sprint_id')) {
            $sprintId = (int) $r->validate(['sprint_id' => 'exists:sprints,id'])['sprint_id'];
            $cr->sprint_id = $sprintId;
            $frais = $this->preRemplirDemo($sprintId);
            $d = $cr->getDonnees();
            foreach (['sprint', 'objectif', 'projet', 'date_debut', 'date_fin', 'taches', 'stories', 'total_points', 'points_realises', 'score_pourcent', 'par_dev'] as $k) $d[$k] = $frais[$k];
            $d['score_manuel'] = false;
            $aDesTableaux = collect($d['modules'] ?? [])->flatMap(fn($m) => collect($m['sections'] ?? [])->flatMap(fn($s) => collect($s['blocs'] ?? [])->pluck('type')))
                ->intersect(['tableau_stories', 'tableau_taches'])->isNotEmpty();
            if (!$aDesTableaux) $d['modules'] = $frais['modules'];
            $d['format'] = 'blocs_v1';
            $cr->setDonnees($d);
        }
        $cr->titre = $r->input('titre', $cr->titre);
        $cr->lieu = $r->input('lieu', $cr->lieu);
        $cr->heure_debut = $r->input('heure_debut', $cr->heure_debut);
        $cr->heure_fin = $r->input('heure_fin', $cr->heure_fin);

        // Champs de programmation (date, type, président, rédacteur) : réservés aux pilotes
        $ancienRedacteur = $cr->redacteur_id;
        if ($user->estPilote() && $r->has('date_reunion')) {
            $v = $r->validate([
                'date_reunion' => 'required|date',
                'type'         => $cr->estReunion() ? 'required|in:reunion_ordinaire,reunion_extraordinaire' : 'prohibited',
                'president_id' => 'nullable|exists:users,id',
                // Rapport de review : le rédacteur ne se choisit pas ici (le pilote passe la main au responsable du projet)
                'redacteur_id' => $cr->type === 'demo_sprint' ? 'prohibited' : 'required|exists:users,id',
            ]);
            $cr->date_reunion = $v['date_reunion'];
            if ($cr->type !== 'demo_sprint') $cr->redacteur_id = $v['redacteur_id'];
            if ($cr->estReunion()) {
                $cr->type = $v['type'];
                $cr->president_id = $v['president_id'] ?? null;
            }
        }
        $cr->save();

        if ((int) $cr->redacteur_id !== (int) $ancienRedacteur && ($redacteur = User::find($cr->redacteur_id))) {
            $msg = "Vous êtes désigné(e) rédacteur du {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y');
            if (Schema::hasTable('notifications_app')) {
                NotificationApp::create(['user_id' => $redacteur->id, 'type' => 'designation_cr', 'contenu' => $msg, 'lu' => false, 'canal' => 'in_app', 'lien' => route('comptes-rendus.edit', $cr)]);
            }
            MailService::notifier($redacteur, 'Désignation rédacteur', $msg . " par " . $user->nomComplet() . ".\nMerci de compléter le compte rendu dans l'application.", route('comptes-rendus.edit', $cr));
        }

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
        if ($cr->estReviewV1()) return redirect()->route('comptes-rendus.review-sprint.edit', $cr);
        if ($cr->estScrum()) return redirect($cr->lienScrum() ?? route('comptes-rendus.index'));
        $user = $r->user();
        // Le rapport de review est validé par un pilote (directement s'il l'a rédigé) ; les autres comptes rendus, par un pilote ou leur rédacteur
        abort_unless($user->estPilote() || ($cr->type !== 'demo_sprint' && $cr->redacteur_id === $user->id), 403);
        if ($cr->estConfirme()) return redirect()->route('comptes-rendus.show', $cr)->with('ok', 'Compte rendu déjà validé.');

        $suites = '';
        try {
            if ($cr->type === 'demo_sprint') {
                // Validation & clôture : rédacteur, relecteur et pilote qui valide inscrits s'ils ne l'étaient pas
                $d = $cr->getDonnees();
                $relu = $d['relecture']['par'] ?? null;
                foreach ($d['modules'] ?? [] as $mi => $m) foreach ($m['sections'] ?? [] as $si => $sec) foreach ($sec['blocs'] ?? [] as $bi => $b) {
                    if (($b['type'] ?? '') !== 'validation') continue;
                    foreach ($b['items'] ?? [] as $ii => $it) {
                        $role = mb_strtolower($it['role'] ?? '');
                        $remplir = match (true) {
                            str_contains($role, 'valid') => [$user->nomComplet(), today()->toDateString()],
                            str_contains($role, 'relu') && $relu => [$relu, $d['relecture']['le'] ?? today()->toDateString()],
                            str_contains($role, 'rédig') => [$cr->redacteur?->nomComplet(), $cr->date_reunion->toDateString()],
                            default => null,
                        };
                        if ($remplir && trim($it['nom'] ?? '') === '') {
                            $d['modules'][$mi]['sections'][$si]['blocs'][$bi]['items'][$ii]['nom'] = $remplir[0] ?? '';
                            $d['modules'][$mi]['sections'][$si]['blocs'][$bi]['items'][$ii]['date'] = $remplir[1];
                        }
                    }
                }
                // Suites : nouvelles user stories au Product Backlog, décisions en tâches suivies (une seule fois, tout ou rien)
                [$us, $taches] = \Illuminate\Support\Facades\DB::transaction(function () use ($cr, &$d) {
                    $us = RapportReview::versBacklog($cr, $d);
                    $taches = RapportReview::versSuivi($cr, $d);
                    $cr->setDonnees($d);
                    $cr->statut = 'confirme';
                    $cr->fichier_path = null;
                    $cr->save();
                    return [$us, $taches];
                });
                $suites = ($us ? " {$us} user story(s) ajoutée(s) au Product Backlog." : '') . ($taches ? " {$taches} tâche(s) de suivi créée(s) pour les décisions." : '');
                AuditLog::log('validation_rapport_review', 'sprint', ($cr->sprint?->projet?->code ?? '') . ' S' . ($cr->sprint?->numero ?? ''), trim($suites) ?: 'Rapport de review validé');
                // La Review des développeurs s'ouvre : chaque membre du sprint est prévenu
                if ($cr->sprint) {
                    foreach (ScrumService::membres($cr->sprint)->reject(fn($m) => (int) $m->id === (int) $user->id) as $m) {
                        RapportReview::notifier($m->id, "Rapport de review du sprint {$cr->sprint->numero} validé : la Review des développeurs est ouverte.", route('scrum.review', $cr->sprint));
                    }
                }
            }
            if (!$cr->estConfirme()) {
                $cr->statut = 'confirme';
                $cr->fichier_path = null;
                $cr->save();
            }
            $this->genererDocx($cr);

            if (Schema::hasTable('notifications_app')) {
                // Notifier le désignateur
                if ((int)$cr->designe_par !== (int)$user->id) {
                    NotificationApp::create([
                        'user_id' => $cr->designe_par,
                        'type'    => 'cr_confirme',
                        'lu' => false, 'canal' => 'in_app',
                        'contenu' => "{$user->nomComplet()} a confirmé le {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y'),
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
                                'lu' => false, 'canal' => 'in_app',
                                'contenu' => "Une action vous concerne dans le {$cr->typeCourt()} du " . $cr->date_reunion->format('d/m/Y'),
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

            return redirect()->route('comptes-rendus.show', $cr)->with('ok', ($cr->type === 'demo_sprint' ? 'Rapport de review validé et DOCX généré.' : 'Compte rendu confirmé et DOCX généré.') . $suites);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors(['erreur' => 'Erreur DOCX : ' . $e->getMessage()]);
        }
    }

    /** Rapport de review rédigé par le responsable du projet (main passée) : il le transmet au pilote pour validation. */
    public function transmettre(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($cr->type === 'demo_sprint' && $cr->estBrouillon(), 404);
        abort_unless((int) $cr->redacteur_id === (int) $user->id && !$user->estPilote(), 403);
        $d = $cr->getDonnees();
        $d['relecture'] = ['transmis_par' => $user->nomComplet(), 'transmis_le' => now()->format('d/m/Y H:i')];
        $cr->setDonnees($d);
        $cr->statut = 'en_revision';
        $cr->save();
        $relecteurs = RapportReview::relecteurs($cr);
        foreach ($relecteurs->reject(fn($u) => (int) $u->id === (int) $user->id) as $u) {
            RapportReview::notifier($u->id, "Rapport de review du sprint {$cr->sprint?->numero} transmis par {$user->nomComplet()} : à valider.", route('comptes-rendus.edit', $cr));
        }
        return redirect()->route('comptes-rendus.show', $cr)->with('ok', 'Rapport transmis au pilote pour validation.');
    }

    /** Rapport transmis : le pilote le renvoie au rédacteur avec un motif (ou, pour un ancien rapport, consigne sa relecture). */
    public function relire(Request $r, CompteRendu $cr)
    {
        $user = $r->user();
        abort_unless($cr->type === 'demo_sprint' && $cr->estEnRevision(), 404);
        abort_unless($user->estPilote() || RapportReview::estRelecteur($user, $cr), 403);
        $v = $r->validate(['decision' => 'required|in:relu,renvoi', 'motif' => 'required_if:decision,renvoi|nullable|string|max:2000'],
            ['motif.required_if' => "Indiquez au rédacteur ce qu'il faut reprendre."]);
        $d = $cr->getDonnees();
        if ($v['decision'] === 'renvoi') {
            $d['relecture'] = ['renvoi' => ['par' => $user->nomComplet(), 'le' => now()->format('d/m/Y H:i'), 'motif' => trim($v['motif'])]];
            $cr->setDonnees($d);
            $cr->statut = 'brouillon';
            $cr->save();
            if ((int) $cr->redacteur_id !== (int) $user->id) {
                RapportReview::notifier($cr->redacteur_id, "Rapport de review du sprint {$cr->sprint?->numero} renvoyé par {$user->nomComplet()} : {$v['motif']}", route('comptes-rendus.edit', $cr));
            }
            return redirect()->route('comptes-rendus.edit', $cr)->with('ok', 'Rapport renvoyé au rédacteur.');
        }
        $d['relecture']['par'] = $user->nomComplet();
        $d['relecture']['le'] = today()->toDateString();
        $cr->setDonnees($d);
        $cr->save();
        foreach (User::where('actif', true)->get()->filter(fn($u) => $u->estPilote() && (int) $u->id !== (int) $user->id) as $p) {
            RapportReview::notifier($p->id, "Rapport de review du sprint {$cr->sprint?->numero} relu par {$user->nomComplet()} : prêt à valider.", route('comptes-rendus.edit', $cr));
        }
        return redirect()->route('comptes-rendus.edit', $cr)->with('ok', 'Relecture consignée. Le pilote peut valider le rapport.');
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
        AuditLog::log('suppression_cr', 'comptes_rendus', $titre, $cr->typeCourt() . ' du ' . $cr->date_reunion?->format('d/m/Y') . ' - Motif : ' . $r->motif_suppression);
        $cr->delete();
        return redirect()->route('comptes-rendus.index')->with('ok', "Compte rendu « {$titre} » supprimé.");
    }

    /* ===== CONSULTATION ===== */
    public function show(Request $r, CompteRendu $cr)
    {
        if ($cr->estReviewV1()) return $this->editReviewSprint($r, $cr);
        if ($cr->estScrum() && $cr->lienScrum()) return redirect($cr->lienScrum());
        $user = $r->user();
        $donnees = $cr->getDonnees();
        $cr->load(['redacteur', 'designePar', 'president', 'sprint']);
        if ($cr->type === 'demo_sprint') $donnees = $this->libellesDemo($donnees);
        return view('comptes-rendus.show', compact('cr', 'donnees', 'user'));
    }

    /* ===== TÉLÉCHARGEMENT ===== */
    public function telecharger(CompteRendu $cr)
    {
        if ($cr->estScrum() && !$cr->estReviewV1() && $cr->lienScrum()) return redirect($cr->lienScrum());
        if ($cr->estReviewV1()) abort_unless(auth()->user() && $this->droitsReview(auth()->user(), $cr), 403);
        if (!$cr->fichier_path || !file_exists(storage_path('app/' . $cr->fichier_path))) {
            $this->genererDocx($cr);
        }
        $path = storage_path('app/' . $cr->fichier_path);
        $typeNom = $cr->estReunion() ? 'Reunion' : ($cr->estReviewV1() ? 'Review_Sprint' : 'Demo');
        $nom = 'CR_' . $typeNom . '_' . $cr->date_reunion->format('d_m_Y') . '.docx';
        return response()->download($path, $nom);
    }

    /* ===== IMPRESSION PDF (via navigateur) ===== */
    public function imprimer(CompteRendu $cr)
    {
        if ($cr->estScrum() && $cr->lienScrum()) return redirect($cr->lienScrum());
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

    /**
     * Structure par défaut du rapport de Review Sprint, en 8 parties ; tout reste modifiable et déplaçable dans l'éditeur.
     * $backlog : user stories issues de la Review de l'équipe (ajustements du Product Backlog).
     */
    /**
     * Plan du rapport de review. Chaque fonctionnalité démontrée porte le verdict du client et ses retours ;
     * les indicateurs se calculent depuis le rapport ; les user stories et les décisions passent au suivi à la validation.
     */
    private static function structureRapport(array $stories, array $taches, ?Sprint $sprint = null): array
    {
        $fonct = collect($stories)->pluck('module')->merge(collect($taches)->map(fn($t) => self::moduleTache($t)))
            ->filter()->unique()->sortBy(fn($m) => $m === self::HORS_US ? 1 : 0)->values();
        $demontres = $fonct->map(fn($m, $i) => ['id' => "sec_d{$i}", 'titre' => $m, 'blocs' => array_values(array_filter([
            $m !== self::HORS_US ? ['id' => "blc_dv{$i}", 'type' => 'verdict', 'verdict' => '', 'contenu' => ''] : null,
            $m !== self::HORS_US ? ['id' => "blc_ds{$i}", 'type' => 'tableau_stories', 'module' => $m] : null,
            ['id' => "blc_dt{$i}", 'type' => 'tableau_taches', 'module' => $m],
        ]))])->all();
        $num = $sprint?->numero;
        $txt = fn($id, $c = '') => ['id' => $id, 'type' => 'texte', 'contenu' => $c];
        return [
            ['id' => 'mod_1', 'titre' => 'Objectifs de la Sprint Review', 'objectif' => '', 'sections' => [
                ['id' => 'sec_1b', 'titre' => '', 'blocs' => [$txt('blc_1b', '<p>Présenter au client les fonctionnalités réalisées' . ($num ? " au cours du sprint {$num}" : '') . ', recueillir son appréciation et ajuster le Product Backlog en conséquence.</p>')]],
            ]],
            ['id' => 'mod_2', 'titre' => 'Fonctionnalités démontrées & appréciation du client', 'objectif' => '', 'sections' => $demontres ?: [
                ['id' => 'sec_d0', 'titre' => 'Tâches réalisées', 'blocs' => [['id' => 'blc_dt0', 'type' => 'tableau_taches']]],
            ]],
            ['id' => 'mod_3', 'titre' => 'Indicateurs de performance du sprint', 'objectif' => '', 'sections' => [
                ['id' => 'sec_3', 'titre' => '', 'blocs' => [['id' => 'blc_3', 'type' => 'indicateurs'], $txt('blc_3b')]],
            ]],
            ['id' => 'mod_4', 'titre' => 'User stories issues de la Sprint Review', 'objectif' => 'Nouvelles user stories demandées par le client : elles rejoignent le Product Backlog à la validation du rapport.', 'sections' => [
                ['id' => 'sec_4', 'titre' => '', 'blocs' => [['id' => 'blc_4', 'type' => 'tableau_backlog', 'items' => []]]],
            ]],
            ['id' => 'mod_5', 'titre' => 'Décisions & prochaines étapes', 'objectif' => '', 'sections' => [
                ['id' => 'sec_5a', 'titre' => 'Décisions', 'blocs' => [['id' => 'blc_5a', 'type' => 'actions', 'items' => []]]],
                ['id' => 'sec_5b', 'titre' => 'Prochaines étapes', 'blocs' => [$txt('blc_5b')]],
            ]],
            ['id' => 'mod_6', 'titre' => 'Validation & clôture officielle', 'objectif' => '', 'sections' => [
                ['id' => 'sec_6', 'titre' => '', 'blocs' => [['id' => 'blc_6', 'type' => 'validation', 'items' => [
                    ['role' => 'Rédigé par', 'nom' => '', 'date' => ''],
                    ['role' => 'Relu par (responsable du projet)', 'nom' => '', 'date' => ''],
                    ['role' => 'Validé par (pilote)', 'nom' => '', 'date' => ''],
                    ['role' => 'Représentant du client', 'nom' => '', 'date' => ''],
                ]]]],
            ]],
        ];
    }

    private function preRemplirDemo(int $sprintId): array
    {
        $sprint = Sprint::with('projet')->findOrFail($sprintId);
        $stories = \App\Services\AvancementScrum::lignesSprint($sprint);
        $rang = collect($stories)->pluck('id')->flip();
        // Tâches dans l'ordre de la hiérarchie (Module > Epic > User story), les tâches hors story à la fin
        $taches = Tache::where('sprint_id', $sprintId)->with(['user', 'userStory'])->orderBy('date_debut')->orderBy('intitule')->get()
            ->sortBy(fn($t) => $t->user_story_id !== null && isset($rang[$t->user_story_id]) ? $rang[$t->user_story_id] : PHP_INT_MAX)->values();

        $lignes = [];
        $totalPoints = 0;
        $pointsRealises = 0;
        $observations = RapportReview::observationsTaches($sprint);

        foreach ($taches as $t) {
            $pts = $t->points ?? 1;
            $totalPoints += $pts;
            $termine = in_array($t->statut, ['termine', 'clos']);
            if ($termine) $pointsRealises += $pts;
            $lignes[] = [
                'tache_id' => $t->id, 'user_id' => $t->user_id,
                'tache' => $t->intitule, 'points' => $pts,
                'attribution' => $t->user?->nomComplet() ?? '-', 'statut' => $t->statut,
                'termine' => $termine,
                // Repris de la tâche (qualification, blocages, commentaires du développeur) ; « observation_auto » garde la version
                // pré-remplie : à l'actualisation, une observation que le pilote n'a pas retouchée suit la tâche
                'observation' => $observations[$t->id] ?? '', 'observation_auto' => $observations[$t->id] ?? '',
                'us' => $t->userStory?->numero,
                'us_contexte' => $t->userStory ? trim(($t->userStory->module ?: '') . ($t->userStory->epic ? ' › ' . $t->userStory->epic : '')) : null,
                'module' => $t->userStory ? (trim((string) $t->userStory->module) ?: 'Sans module') : self::HORS_US,
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
            'objectif' => $sprint->objectif,
            'projet' => $sprint->projet?->libelle ?? '—',
            'date_debut' => $sprint->date_debut?->format('d/m/Y'),
            'date_fin' => $sprint->date_fin?->format('d/m/Y'),
            'taches' => $lignes,
            'stories' => $stories,
            'total_points' => $totalPoints, 'points_realises' => $pointsRealises,
            'score_pourcent' => $scorePct,
            'par_dev' => $parDev,
            'modules' => self::structureRapport($stories, $lignes, $sprint),
        ];
    }

    /* ===================================================================
     *  CR REVIEW SPRINT - WIZARD REVIEW_V1 (document formel)
     *  Brouillon (rédacteur) → En révision (responsable du projet) → Confirmé (DOCX).
     *  La Review du module Scrum reste la saisie de référence (statuts, observations) : le CR formel
     *  la reprend et la complète pour le client (retours, critères d'acceptation, décisions, signatures).
     * =================================================================== */

    /** Droit sur un CR Review Sprint : « edition », « lecture » ou null. */
    private function droitsReview(User $u, CompteRendu $cr): ?string
    {
        $projet = $cr->sprint?->projet;
        $gestionnaire = $projet && $u->peutGererProjet($projet->id);
        $redacteur = (int) $cr->redacteur_id === (int) $u->id;
        if ($cr->estConfirme()) {
            if ($u->estPilote()) return 'edition';
            return $u->estPilote() || $gestionnaire || $redacteur || ($projet && $projet->estVisiblePour($u)) ? 'lecture' : null;
        }
        if ($cr->estEnRevision()) {
            if ($u->estPilote() || $gestionnaire) return 'edition';
            return $u->estPilote() || $redacteur ? 'lecture' : null;
        }
        if ($u->estPilote() || $gestionnaire || $redacteur) return 'edition';
        return $u->estPilote() ? 'lecture' : null;
    }

    /** Valider (relecture) puis confirmer : responsable du projet ou pilote. */
    private function peutValiderReview(User $u, CompteRendu $cr): bool
    {
        return !$cr->estConfirme() && ($u->estPilote() || ($cr->sprint && $u->peutGererProjet($cr->sprint->projet_id)));
    }

    /** Édition (ou consultation) d'un CR Review Sprint existant (format d'origine). */
    public function editReviewSprint(Request $r, CompteRendu $cr)
    {
        abort_unless($cr->estReviewV1(), 404);
        $user = $r->user();
        $droit = $this->droitsReview($user, $cr);
        abort_unless($droit, 403, "Ce compte rendu n'est pas encore confirmé.");

        $cr->load(['redacteur', 'sprint.projet']);
        $donnees = $cr->getDonnees();
        $sprint = $cr->sprint;
        $membres = $sprint ? ScrumService::membres($sprint) : collect();
        $tousUsers = User::where('actif', true)->orderBy('name')->get();
        // Indicateurs : valeurs du jour tant que le CR n'est pas confirmé, figées ensuite
        $kpis = $cr->estConfirme() ? ($donnees['kpis'] ?? []) : ($sprint ? ScrumService::indicateurs($sprint) : []);
        $estLecture = $droit === 'lecture';
        $peutValider = $this->peutValiderReview($user, $cr);

        return view('comptes-rendus.review-sprint', compact('cr', 'donnees', 'sprint', 'membres', 'tousUsers', 'kpis', 'user', 'estLecture', 'peutValider'));
    }

    /**
     * Enregistrement du CR, suivi éventuellement d'une étape du circuit :
     * transmettre (brouillon → en révision), renvoyer (en révision → brouillon), confirmer (→ confirmé + DOCX).
     */
    public function updateReviewSprint(Request $r, CompteRendu $cr)
    {
        abort_unless($cr->estReviewV1(), 404);
        $user = $r->user();
        abort_unless($this->droitsReview($user, $cr) === 'edition', 403);
        $v = $r->validate([
            'titre' => 'required|string|max:255',
            'date_reunion' => 'required|date',
            'lieu' => 'nullable|string|max:255',
            'heure_debut' => 'nullable|date_format:H:i',
            'heure_fin' => 'nullable|date_format:H:i|after:heure_debut',
            'redacteur_id' => 'nullable|exists:users,id',
            'donnees_json' => 'required|json',
            'feedback_client' => 'nullable|string|max:30000',
            'conclusion' => 'nullable|string|max:30000',
            'apres' => 'nullable|in:enregistrer,transmettre,renvoyer,confirmer',
        ], ['heure_fin.after' => "L'heure de fin doit suivre l'heure de début."]);
        $apres = $v['apres'] ?? 'enregistrer';
        if ($apres === 'transmettre') abort_unless($cr->estBrouillon(), 403);
        if (in_array($apres, ['renvoyer', 'confirmer'], true)) abort_unless($this->peutValiderReview($user, $cr), 403);
        if ($apres === 'renvoyer') abort_unless($cr->estEnRevision(), 403);

        $ancien = $cr->getDonnees();
        $d = $this->normaliserReview(json_decode($v['donnees_json'], true) ?: []);
        $d['feedback']['client'] = \App\Services\TexteRiche::nettoyer($v['feedback_client'] ?? null);
        $d['conclusion'] = \App\Services\TexteRiche::nettoyer($v['conclusion'] ?? null);
        $d['objectif'] = $ancien['objectif'] ?? $cr->sprint?->objectif;
        // Indicateurs figés à la confirmation ; recalculés à chaque enregistrement avant
        $d['kpis'] = $cr->estConfirme() ? ($ancien['kpis'] ?? []) : ($cr->sprint ? ScrumService::indicateurs($cr->sprint) : []);
        $d['format'] = 'review_v1';
        $cr->setDonnees($d);
        $cr->fill([
            'titre' => $v['titre'], 'date_reunion' => $v['date_reunion'], 'lieu' => $v['lieu'] ?? null,
            'heure_debut' => $v['heure_debut'] ?? null, 'heure_fin' => $v['heure_fin'] ?? null,
        ]);
        if (($user->estPilote() || ($cr->sprint && $user->peutGererProjet($cr->sprint->projet_id))) && !empty($v['redacteur_id'])) {
            $cr->redacteur_id = (int) $v['redacteur_id'];
        }

        $message = 'Brouillon enregistré.';
        if ($apres === 'transmettre') {
            $cr->statut = 'en_revision';
            $this->notifierRelecteursReview($cr, $user);
            AuditLog::log('transmission_cr', 'comptes_rendus', $cr->titre, 'CR Review Sprint transmis pour relecture');
            $message = 'CR transmis au responsable du projet pour relecture.';
        } elseif ($apres === 'renvoyer') {
            $cr->statut = 'brouillon';
            if ($cr->redacteur_id && $cr->redacteur_id !== $user->id) {
                NotificationApp::create(['user_id' => $cr->redacteur_id, 'type' => 'cr_review', 'contenu' => "Le {$cr->titre} vous est renvoyé pour reprise.", 'lien' => route('comptes-rendus.review-sprint.edit', $cr), 'lu' => false]);
            }
            AuditLog::log('renvoi_cr', 'comptes_rendus', $cr->titre, 'CR Review Sprint renvoyé en brouillon');
            $message = 'CR renvoyé en brouillon au rédacteur.';
        } elseif ($apres === 'confirmer') {
            $cr->statut = 'confirme';
            $cr->fichier_path = null;
            $d['kpis'] = $cr->sprint ? ScrumService::indicateurs($cr->sprint) : [];
            $d['kpis_figes_le'] = now()->format('d/m/Y H:i');
            $cr->setDonnees($d);
            $message = 'CR Review Sprint confirmé et DOCX généré.';
        } elseif ($cr->estConfirme()) {
            $cr->fichier_path = null;
            $message = 'Modifications enregistrées et DOCX régénéré.';
        } elseif ($cr->estEnRevision()) {
            $message = 'Relecture enregistrée.';
        }
        $cr->save();

        if ($cr->estConfirme()) {
            // Le backlog ne reçoit les critères d'acceptation et la priorité MoSCoW qu'une fois le CR confirmé
            if ($apres === 'confirmer') {
                $this->syncUserStoriesBdd($d['user_stories_bdd'] ?? []);
                AuditLog::log('confirmation_cr', 'comptes_rendus', $cr->titre, 'CR Review Sprint confirmé - Sprint ' . ($cr->sprint?->numero ?? '?'));
            }
            try {
                $this->genererDocx($cr);
            } catch (\Throwable $e) {
                return redirect()->route('comptes-rendus.review-sprint.edit', $cr)->with('erreur', 'CR enregistré, mais le DOCX n\'a pas pu être généré : ' . $e->getMessage());
            }
        }
        return redirect()->route('comptes-rendus.review-sprint.edit', $cr)->with('ok', $message);
    }

    /** Ancienne route de confirmation : la confirmation passe par l'enregistrement (apres = confirmer). */
    public function confirmerReviewSprint(Request $r, CompteRendu $cr)
    {
        abort_unless($cr->estReviewV1(), 404);
        return redirect()->route('comptes-rendus.review-sprint.edit', $cr);
    }

    /** Notifie le responsable du projet (et les pilotes à défaut) qu'un CR attend sa relecture. */
    private function notifierRelecteursReview(CompteRendu $cr, User $auteur): void
    {
        $ids = collect([$cr->sprint?->projet?->responsable_projet_id])->filter();
        if ($ids->isEmpty()) $ids = User::where('actif', true)->whereIn('role', ['administrateur', 'charge_operations'])->pluck('id');
        foreach ($ids->unique()->reject(fn($id) => (int) $id === (int) $auteur->id) as $id) {
            NotificationApp::create(['user_id' => $id, 'type' => 'cr_review', 'contenu' => "{$auteur->nomComplet()} vous transmet le {$cr->titre} pour relecture.", 'lien' => route('comptes-rendus.review-sprint.edit', $cr), 'lu' => false]);
        }
    }

    /** Ne garde que la structure attendue des données saisies dans l'assistant. */
    private function normaliserReview(array $d): array
    {
        $txt = fn($v, $max = 2000) => mb_substr(trim(strip_tags((string) ($v ?? ''))), 0, $max);
        $date = fn($v) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v) ? $v : '';
        $liste = fn($v) => is_array($v) ? array_values($v) : [];
        return [
            'presences' => array_map(fn($p) => ['nom' => $txt($p['nom'] ?? '', 150), 'fonction' => $txt($p['fonction'] ?? '', 150), 'present' => (bool) ($p['present'] ?? false)],
                array_filter($liste($d['presences'] ?? []), fn($p) => trim($p['nom'] ?? '') !== '')),
            'modules_livres' => array_map(fn($m) => ['nom' => $txt($m['nom'] ?? '', 200), 'statut' => in_array($m['statut'] ?? '', ['livre', 'partiel', 'non_livre'], true) ? $m['statut'] : 'non_livre', 'observation' => $txt($m['observation'] ?? '')],
                array_filter($liste($d['modules_livres'] ?? []), fn($m) => trim($m['nom'] ?? '') !== '')),
            'user_stories_bdd' => array_map(fn($us) => [
                'us_id' => (int) ($us['us_id'] ?? 0) ?: null, 'numero' => $txt($us['numero'] ?? '', 30), 'intitule' => $txt($us['intitule'] ?? '', 2000),
                'module' => $txt($us['module'] ?? '', 150),
                'moscow' => array_key_exists($us['moscow'] ?? '', UserStory::MOSCOW) ? $us['moscow'] : 'should',
                'verdict' => in_array($us['verdict'] ?? '', ['acceptee', 'partielle', 'refusee'], true) ? $us['verdict'] : 'partielle',
                'observation' => $txt($us['observation'] ?? ''),
                'niveau' => isset($us['niveau']) ? max(0, min(100, (int) $us['niveau'])) : null,
                'niveau_origine' => ($us['niveau_origine'] ?? '') === 'declare' ? 'declare' : 'calcule',
                'epic' => $txt($us['epic'] ?? '', 150),
                'criteres' => array_map(fn($c) => ['given' => $txt($c['given'] ?? '', 500), 'when' => $txt($c['when'] ?? '', 500), 'then' => $txt($c['then'] ?? '', 500), 'statut' => in_array($c['statut'] ?? '', ['valide', 'echoue', 'na'], true) ? $c['statut'] : 'na'],
                    array_filter($liste($us['criteres'] ?? []), fn($c) => trim(($c['given'] ?? '') . ($c['when'] ?? '') . ($c['then'] ?? '')) !== '')),
            ], $liste($d['user_stories_bdd'] ?? [])),
            'feedback' => [
                'positifs' => array_values(array_filter(array_map(fn($x) => $txt($x, 500), $liste($d['feedback']['positifs'] ?? [])))),
                'ameliorations' => array_values(array_filter(array_map(fn($x) => $txt($x, 500), $liste($d['feedback']['ameliorations'] ?? [])))),
            ],
            'decisions' => array_map(fn($x) => [
                'action' => $txt($x['action'] ?? ''), 'responsables' => array_values(array_filter(array_map(fn($p) => $txt($p, 150), is_array($x['responsables'] ?? null) ? $x['responsables'] : explode(',', (string) ($x['responsables'] ?? ''))))),
                'priorite' => in_array($x['priorite'] ?? '', ['haute', 'moyenne', 'basse'], true) ? $x['priorite'] : '', 'echeance' => $date($x['echeance'] ?? ''),
            ], array_filter($liste($d['decisions'] ?? []), fn($x) => trim($x['action'] ?? '') !== '')),
            'signatures' => array_map(fn($s) => ['nom' => $txt($s['nom'] ?? '', 150), 'role' => $txt($s['role'] ?? '', 150), 'date' => $date($s['date'] ?? '')],
                array_filter($liste($d['signatures'] ?? []), fn($s) => trim($s['nom'] ?? '') !== '')),
        ];
    }

    /** À la confirmation : critères d'acceptation et priorité MoSCoW reportés sur les user stories du backlog. */
    private function syncUserStoriesBdd(array $usBdd): void
    {
        foreach ($usBdd as $item) {
            if (empty($item['us_id']) || !($us = UserStory::find($item['us_id']))) continue;
            $us->update([
                'criteres_acceptation' => $item['criteres'] ?: null,
                'priorite_moscow'      => $item['moscow'] ?? null,
            ]);
            // Verdict de la Review : une story terminée et acceptée change de statut
            if (($item['verdict'] ?? '') === 'acceptee' && $us->statut === 'terminee') $us->update(['statut' => 'acceptee']);
        }
    }

    /* ===================================================================
     *  GÉNÉRATION DOCX - Times New Roman 12pt, interligne 1,5, logo
     * =================================================================== */

    private function genererDocx(CompteRendu $cr): void
    {
        $donnees = $cr->getDonnees();
        $this->miseEnForme = $donnees['mise_en_forme'] ?? [];
        $this->sprintDocx = $cr->sprint;
        $dir = storage_path('app/comptes-rendus');
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'cr_' . $cr->id . '_' . $cr->date_reunion->format('Ymd') . '.docx';
        $path = $dir . '/' . $filename;

        if ($cr->estReunion()) {
            $this->genererDocxReunion($cr, $donnees, $path);
        } elseif ($cr->estReviewV1()) {
            $this->genererDocxReviewSprint($cr, $donnees, $path);
        } else {
            $this->genererDocxDemo($cr, $donnees, $path);
        }

        $cr->fichier_path = 'comptes-rendus/' . $filename;
        $cr->save();
    }

    private function genererDocxReunion(CompteRendu $cr, array $d, string $path): void
    {
        $logoPath = public_path('images/logo_soumafe.png');
        $hasLogo = file_exists($logoPath) && ($this->miseEnForme['logo'] ?? true);
        $cr->load(['redacteur', 'president', 'sprint.projet']);

        $redacteur = $cr->redacteur;
        $redNom = $redacteur?->nomComplet() ?? '—';
        $redTitre = $redacteur?->titre_poste ?? '';
        $projetNom = $cr->sprint?->projet?->libelle ?? 'SOUMAFÊ';
        $typeLabel = $cr->type === 'reunion_ordinaire' ? 'Réunion ordinaire' : 'Réunion extraordinaire';
        $headerLine = "SOUMAFÊ - {$projetNom} | Compte-rendu de {$typeLabel}";

        $xml = $this->docxHeader();

        // Logo centré (plus grand)
        if ($hasLogo) $xml .= $this->docxImage($logoPath, 1500000, 1050000);

        // Titre dans bloc bleu
        $typeTitre = $cr->type === 'reunion_ordinaire' ? 'RÉUNION ORDINAIRE' : 'RÉUNION EXTRAORDINAIRE';
        $xml .= $this->titreBloc("COMPTE RENDU DE {$typeTitre}");
        $xml .= $this->p('');

        // Encadré rédacteur
        $redLigne = "Rédigé par : {$redNom}";
        if ($redTitre) $redLigne .= " - {$redTitre}, SOUMAFÊ";
        $xml .= $this->encadre($redLigne);
        $xml .= $this->p('');

        // Tableau d'information
        $president = $cr->president?->nomComplet() ?? '—';
        $xml .= $this->docxInfoTable([
            ['Date', $cr->date_reunion->format('d/m/Y')],
            ['Heure début', $cr->heure_debut ?? '—'],
            ['Heure fin', $cr->heure_fin ?? '—'],
            ['Lieu', $cr->lieu ?? '—'],
            ['Présidée par', $president],
            ['Rédigé par', $redNom],
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

    /** CR Démo : qualifications des tâches en libellés (« en_retard » → « Réalisée en retard »). */
    private function libellesDemo(array $d): array
    {
        foreach ($d['taches'] ?? [] as $i => $t) $d['taches'][$i]['observation'] = Tache::libelleQualification($t['observation'] ?? '');
        return $d;
    }

    private function genererDocxDemo(CompteRendu $cr, array $d, string $path): void
    {
        $d = $this->libellesDemo($d);
        $logoPath = public_path('images/logo_soumafe.png');
        $hasLogo = file_exists($logoPath) && ($this->miseEnForme['logo'] ?? true);
        $cr->load(['redacteur', 'president', 'sprint.projet']);

        $redacteur = $cr->redacteur;
        $redNom = $redacteur?->nomComplet() ?? '—';
        $redTitre = $redacteur?->titre_poste ?? '';
        $projetNom = $cr->sprint?->projet?->libelle ?? $d['projet'] ?? 'SOUMAFÊ';
        $sprintLabel = ($d['sprint'] ?? null) ?: ($cr->sprint ? "Sprint {$cr->sprint->numero}" : 'Sprint');
        $d['date_debut'] = ($d['date_debut'] ?? null) ?: $cr->sprint?->date_debut?->format('d/m/Y');
        $d['date_fin'] = ($d['date_fin'] ?? null) ?: $cr->sprint?->date_fin?->format('d/m/Y');
        $headerLine = "SOUMAFÊ  —  {$projetNom}  —  Rapport de Review Sprint\t{$sprintLabel}  |  " . $cr->date_reunion->format('d F Y');
        $footerLine = "Confidentiel  —  Usage interne";

        $xml = $this->docxHeader();

        // Bannière professionnelle avec logo intégré + auteur/date dans la cellule droite
        $redLigne = "Rédigé par : {$redNom}";
        if ($redTitre) $redLigne .= " — {$redTitre}, SOUMAFÊ";
        $xml .= $this->docxBannerTable(
            "Opérations & Projets — SOUMAFÊ",
            "RAPPORT DE REVIEW SPRINT",
            "{$sprintLabel} — {$projetNom}",
            $redLigne,
            "Date : " . $cr->date_reunion->format('d F Y')
        );
        $xml .= $this->p('');

        // Table résumé (Volet / Résultat) avec en-tête bleu
        $periode = $d['date_debut'] && $d['date_fin'] ? "Du {$d['date_debut']} au {$d['date_fin']}" : '—';
        $scoreTxt = ($d['points_realises'] ?? 0) . '/' . ($d['total_points'] ?? 0) . ' pts (' . ($d['score_pourcent'] ?? 0) . '%)';
        $xml .= $this->docxResumeTable(['Volet', 'Résultat'], [
            ['Projet', $projetNom],
            ['Sprint', $sprintLabel],
            ['Période', $periode],
            ['Date de la review', $cr->date_reunion->format('d/m/Y')],
            ['Score global', $scoreTxt],
            ...(($goal = trim((string) ($d['objectif'] ?? $cr->sprint?->objectif ?? ''))) ? [['Sprint Goal', $goal]] : []),
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

    /** Génération DOCX blocs_v1 : modules > sections > blocs — rendu élégant */
    private function genererDocxDemoBlocs(array $d): string
    {
        $xml = '';
        $taches = $d['taches'] ?? [];

        // Palette de couleurs par module (rotation)
        $palettes = [
            ['titre' => '2E75B6', 'sousTitre' => '1F3864', 'header' => '1F3864', 'encadreBg' => 'D5E8F0', 'encadreBorder' => '2E75B6', 'bilanBg' => 'E2EFDA', 'bilanBorder' => '375623', 'kpiBg1' => 'D5E8F0', 'kpiBg2' => 'E2EFDA'],
            ['titre' => '0D6E6E', 'sousTitre' => '0D6E6E', 'header' => '0D6E6E', 'encadreBg' => 'E0F2F2', 'encadreBorder' => '0D6E6E', 'bilanBg' => 'E0F2F2', 'bilanBorder' => '0D6E6E', 'kpiBg1' => 'E0F2F2', 'kpiBg2' => 'E2EFDA'],
            ['titre' => '7B2D8E', 'sousTitre' => '5B1F6E', 'header' => '5B1F6E', 'encadreBg' => 'F0E6F6', 'encadreBorder' => '7B2D8E', 'bilanBg' => 'F0E6F6', 'bilanBorder' => '7B2D8E', 'kpiBg1' => 'F0E6F6', 'kpiBg2' => 'E2EFDA'],
            ['titre' => 'C55A11', 'sousTitre' => 'C55A11', 'header' => 'C55A11', 'encadreBg' => 'FCE4D6', 'encadreBorder' => 'C55A11', 'bilanBg' => 'FCE4D6', 'bilanBorder' => 'C55A11', 'kpiBg1' => 'FCE4D6', 'kpiBg2' => 'FFF2CC'],
        ];

        $indRapport = RapportReview::indicateurs($d, $this->sprintDocx);
        foreach (RapportReview::modulesVisibles($d) as $mi => $mod) {
            $modNum = $mi + 1;
            $pal = $palettes[$mi % count($palettes)];
            $modTitre = $mod['titre'] ?? "Module {$modNum}";

            // Titre de module avec couleur spécifique
            $xml .= $this->demoTitre((string)$modNum, $modTitre);

            // Objectif du module en encadré table coloré
            if (!empty($mod['objectif'])) {
                $xml .= $this->docxEncadreTable($mod['objectif'], $pal['encadreBg'], $pal['encadreBorder'], $pal['sousTitre']);
                $xml .= $this->p('');
            }

            foreach ($mod['sections'] ?? [] as $si => $sec) {
                $secNum = "{$modNum}." . ($si + 1);
                if (!empty($sec['titre'])) {
                    $xml .= $this->demoSousTitre($secNum, $sec['titre'], $this->miseEnForme['couleur_sous_titre'] ?? $pal['sousTitre'], 'FFFFFF', null, 'section');
                }

                $k = 0;
                foreach ($sec['blocs'] ?? [] as $bloc) {
                    $type = $bloc['type'] ?? '';
                    [, $cBg, $cBd] = self::BLOC_COULEURS[$bloc['couleur'] ?? ''] ?? [null, 'F8FAFC', $pal['encadreBorder']];

                    if ($type === 'verdict') {
                        if ([$vLib, $vC, $vB] = RapportReview::VERDICTS[$bloc['verdict'] ?? ''] ?? null) {
                            $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
                            $xml .= '<w:p><w:pPr><w:keepNext/><w:spacing w:before="60" w:after="100"/></w:pPr>'
                                . "<w:r><w:rPr>{$rpr}<w:b/><w:caps/><w:color w:val=\"1F4E79\"/><w:sz w:val=\"18\"/></w:rPr><w:t xml:space=\"preserve\">Verdict du client   </w:t></w:r>"
                                . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"{$vC}\"/><w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$vB}\"/><w:sz w:val=\"22\"/></w:rPr><w:t xml:space=\"preserve\"> " . htmlspecialchars($vLib, ENT_XML1, 'UTF-8') . " </w:t></w:r></w:p>";
                        }
                        if (trim(strip_tags($bloc['contenu'] ?? '')) !== '') $xml .= $this->htmlToOoxml($bloc['contenu'], "{$secNum}.");
                        $xml .= $this->p('');

                    } elseif ($type === 'indicateurs') {
                        $xml .= $this->docxKpiCards(array_map(fn($c) => ['valeur' => $c[0], 'label' => $c[1], 'detail' => $c[2], 'bg' => 'EAF2FB', 'couleur' => '1F4E79', 'sz' => 32],
                            RapportReview::cartes($indRapport)));
                        $xml .= $this->p('');
                        if (count($indRapport['par_dev']) > 1) {
                            $xml .= $this->docxTableMultiAlt(['Développeur', 'Réalisé', 'Engagé', '%'], array_map(fn($dv) => [
                                $dv['nom'], $dv['realise'] . ' pts', $dv['total'] . ' pts', $dv['pourcent'] . ' %',
                            ], $indRapport['par_dev']), [4200, 1800, 1800, 1800], $pal['header'], 'F2F2F2');
                            $xml .= $this->p('');
                        }

                    } elseif ($type === 'sous_titre' && trim($bloc['texte'] ?? '') !== '') {
                        $k++;
                        $xml .= $this->demoSousTitre("{$secNum}.{$k}", $bloc['texte'], !empty($bloc['couleur']) ? $cBd : $pal['sousTitre'], 'FFFFFF', max(18, $this->mefSz('sous_titre', 24) - 2), 'sous_titre');

                    } elseif ($type === 'commentaire' && trim($bloc['contenu'] ?? '') !== '') {
                        $xml .= $this->docxEncadreTable($bloc['contenu'], $cBg, $cBd, $cBd, 'Commentaire - ' . ($bloc['auteur'] ?? '') . ', ' . ($bloc['date'] ?? ''));
                        $xml .= $this->p('');

                    } elseif ($type === 'texte' && !empty($bloc['contenu']) && !empty($bloc['couleur'])) {
                        // Texte coloré : encadré de la couleur choisie
                        $xml .= $this->docxEncadreTable(trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</div>', '</li>'], "\n", $bloc['contenu'])), ENT_QUOTES, 'UTF-8')), $cBg, $cBd, $cBd);
                        $xml .= $this->p('');

                    } elseif ($type === 'texte' && !empty($bloc['contenu'])) {
                        $xml .= $this->htmlToOoxml($bloc['contenu'], "{$secNum}.");
                        $xml .= $this->p('');

                    } elseif ($type === 'tableau_stories' && ($lignesSt = self::lignesModule($d['stories'] ?? [], $bloc['module'] ?? null, false))) {
                        $rows = [];
                        $groupe = null;
                        foreach ($lignesSt as $st) {
                            $ctx = self::libelleModule($d, $st['module']) . ($st['epic'] ? ' > ' . $st['epic'] : '');
                            $rows[] = [$ctx !== $groupe ? $ctx : '', ($st['numero'] ?? '') . ' - ' . ($st['intitule'] ?? ''), ($st['niveau'] ?? 0) . ' %' . (($st['origine'] ?? '') === 'declare' ? ' (declare)' : ''),
                                ($st['taches_terminees'] ?? 0) . '/' . ($st['taches'] ?? 0), (string) ($st['points'] ?? '-'), $st['statut'] ?? ''];
                            $groupe = $ctx;
                        }
                        $xml .= $this->docxTableMultiAlt(['Module > Epic', 'User story', 'Execution', 'Taches', 'Pts', 'Statut'], $rows, [2000, 3600, 1200, 900, 600, 1300], $pal['header'], 'F2F2F2');
                        $xml .= $this->p('');

                    } elseif ($type === 'tableau_taches' && ($lignesT = self::lignesModule($taches, $bloc['module'] ?? null, true))) {
                        foreach (self::groupesTaches($lignesT, $bloc['module'] ?? null) as $grpNom => $grpLignes) {
                        if ($grpNom !== '') {
                            $xml .= '<w:p><w:pPr><w:keepNext/><w:pBdr><w:bottom w:val="dotted" w:sz="4" w:space="2" w:color="9FBFE0"/></w:pBdr><w:spacing w:before="160" w:after="80"/></w:pPr>'
                                . '<w:r><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/><w:b/><w:color w:val="2E75B6"/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>'
                                . '<w:t xml:space="preserve">' . htmlspecialchars(self::libelleModule($d, (string) $grpNom), ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
                        }
                        $taskRows = [];
                        foreach ($grpLignes as $ti => $t) {
                            $termine = !empty($t['termine']);
                            $statut = $termine ? 'Termine' : (($t['statut'] ?? '') ? ([ 'a_faire' => 'A faire', 'en_cours' => 'En cours', 'bloque' => 'Bloque', 'a_valider' => 'A valider', 'termine' => 'Termine', 'reporte' => 'Reporte' ][$t['statut']] ?? ucfirst(str_replace('_', ' ', $t['statut']))) : 'En cours');
                            $taskRows[] = [
                                (string)($ti + 1),
                                (!empty($t['us']) ? $t['us'] . ' - ' : '') . ($t['tache'] ?? ''),
                                (string)($t['points'] ?? 1),
                                $t['attribution'] ?? '—',
                                $statut,
                                $t['observation'] ?? '',
                            ];
                        }
                        $xml .= $this->docxTableMultiAlt(
                            ['N', 'Tache', 'PTS', 'Attribution', 'Statut', 'Observation'],
                            $taskRows, [500, 3200, 600, 1600, 1200, 2500], $pal['header'], 'F2F2F2'
                        );
                        $xml .= $this->p('');
                        }

                    } elseif ($type === 'score') {
                        $scorePct = $d['score_pourcent'] ?? 0;
                        $appreciation = $scorePct >= 90 ? 'Excellent' : ($scorePct >= 75 ? 'Bon' : ($scorePct >= 50 ? 'Acceptable' : 'Insuffisant'));

                        // KPI cards pour le score
                        $xml .= $this->docxKpiCards([
                            ['valeur' => ($d['points_realises'] ?? 0) . '/' . ($d['total_points'] ?? 0), 'label' => 'Points realises', 'bg' => $pal['kpiBg1'], 'couleur' => $pal['sousTitre']],
                            ['valeur' => "{$scorePct}%", 'label' => 'Score global', 'bg' => $scorePct >= 75 ? 'E2EFDA' : ($scorePct >= 50 ? 'FFF2CC' : 'FFDAD6'), 'couleur' => $scorePct >= 75 ? '375623' : ($scorePct >= 50 ? 'C55A11' : 'C00000')],
                            ['valeur' => $appreciation, 'label' => 'Appreciation', 'bg' => $pal['kpiBg2'], 'couleur' => $pal['sousTitre']],
                        ]);
                        $xml .= $this->p('');

                        $bilanBg = $scorePct >= 75 ? 'E2EFDA' : ($scorePct >= 50 ? 'FFF2CC' : 'FFDAD6');
                        $bilanClr = $scorePct >= 75 ? '375623' : ($scorePct >= 50 ? 'C55A11' : 'C00000');
                        $xml .= $this->docxEncadreTable(
                            "Score : " . ($d['points_realises'] ?? 0) . "/" . ($d['total_points'] ?? 0) . " pts ({$scorePct}%) — {$appreciation}",
                            $bilanBg, $bilanClr, $bilanClr, 'Resultat global'
                        );

                        // Score par développeur
                        if (!empty($d['par_dev'])) {
                            $devScoreRows = array_map(fn($dev) => [
                                $dev['nom'], (string)$dev['realise'], (string)$dev['total'], $dev['pourcent'] . '%',
                            ], $d['par_dev']);
                            $xml .= $this->docxTableMultiAlt(
                                ['Collaborateur', 'Realise', 'Total', '%'],
                                $devScoreRows, [3600, 2000, 2000, 2000], '2E75B6', 'F2F2F2'
                            );
                        }
                        $xml .= $this->p('');

                    } elseif ($type === 'actions' && !empty($bloc['items'])) {
                        $actRows = [];
                        foreach ($bloc['items'] as $ai => $act) {
                            $rAct = $act['responsables'] ?? [];
                            $prio = $act['priorite'] ?? '—';
                            $actRows[] = [
                                (string)($ai + 1),
                                $act['action'] ?? '',
                                implode(', ', $rAct) ?: '—',
                                $prio,
                                !empty($act['echeance']) && strtotime($act['echeance']) ? Carbon::parse($act['echeance'])->format('d/m/Y') : '—',
                            ];
                        }
                        $xml .= $this->docxTableMultiAlt(
                            ['N', 'Action', 'Responsable(s)', 'Priorite', 'Echeance'],
                            $actRows, [500, 3600, 2000, 1200, 2300], '1F3864', 'F2F2F2'
                        );
                        $xml .= $this->p('');

                    } elseif ($type === 'reference' && !empty($bloc['contenu'])) {
                        $refType = $bloc['ref_type'] ?? 'remarque';
                        $xml .= $this->blocAnnote($refType, strip_tags($bloc['contenu']));
                        $xml .= $this->p('');

                    } elseif ($type === 'tableau_backlog' && !empty($bloc['items'])) {
                        $xml .= $this->docxTableMultiAlt(['N°', 'Module', 'User story', 'Priorité', 'Pts'], array_map(fn($it) => [
                            $it['numero'] ?: '-', $it['module'] ?: '-', $it['intitule'], UserStory::MOSCOW[$it['moscow'] ?? ''] ?? '-', (string) ($it['points'] ?? '-'),
                        ], $bloc['items']), [900, 1800, 4500, 1400, 600], $pal['header'], 'F2F2F2');
                        $xml .= $this->p('');

                    } elseif ($type === 'validation' && !empty($bloc['items'])) {
                        $xml .= $this->docxTableMultiAlt(['Qualité', 'Nom', 'Date', 'Signature'], array_map(fn($it) => [
                            $it['role'], $it['nom'] ?: '', $it['date'] ? Carbon::parse($it['date'])->format('d/m/Y') : '', '',
                        ], $bloc['items']), [2600, 3000, 1600, 2000], $pal['header'], 'F2F2F2');
                        $xml .= $this->p('');
                    }
                }
            }
        }

        return $xml;
    }

    /** Génération DOCX ancien format — rendu élégant fidèle au modèle de référence */
    private function genererDocxDemoLegacy(array $d): string
    {
        $xml = '';
        $annotations = $d['annotations'] ?? [];
        $taches = $d['taches'] ?? [];
        $totalTaches = count($taches);
        $tachesTerminees = count(array_filter($taches, fn($t) => !empty($t['termine'])));
        $tachesEnCours = $totalTaches - $tachesTerminees;
        $scorePct = $d['score_pourcent'] ?? 0;

        // ═══════ SECTION 1 : CONTEXTE DE LA DÉMONSTRATION ═══════
        $xml .= $this->demoTitre('1', 'Contexte de la démonstration');

        // 1.1 Sprint Goal
        $xml .= $this->demoSousTitre('1.1', 'Sprint Goal', '1F3864', 'D5E8F0');
        if (!empty($d['note_sprint'])) {
            $xml .= $this->docxEncadreTable(strip_tags($d['note_sprint']), 'D5E8F0', '2E75B6', '1F3864', 'Objectif du sprint');
        } else {
            $xml .= $this->docxEncadreTable('(A renseigner)', 'D5E8F0', '2E75B6', '1F3864', 'Objectif du sprint');
        }
        $xml .= $this->p('');

        // 1.2 Structure du sprint
        $xml .= $this->demoSousTitre('1.2', 'Structure du sprint', '1F3864', 'D5E8F0');
        $structureInfo = "Ce sprint comporte {$totalTaches} tâches pour un total de " . ($d['total_points'] ?? 0) . " points.";
        if (!empty($d['date_debut']) && !empty($d['date_fin'])) {
            $structureInfo .= " Période : du {$d['date_debut']} au {$d['date_fin']}.";
        }
        $xml .= $this->docxEncadreTable($structureInfo, 'F2F2F2', '1F3864', '1F3864');
        $xml .= $this->p('');

        // KPI cards indicateurs clés
        $xml .= $this->docxKpiCards([
            ['valeur' => (string)$totalTaches, 'label' => 'Tâches totales', 'bg' => 'D5E8F0', 'couleur' => '1F3864'],
            ['valeur' => "{$scorePct}%", 'label' => 'Taux de réalisation', 'bg' => 'E2EFDA', 'couleur' => '375623'],
            ['valeur' => (string)$tachesTerminees, 'label' => 'Terminées', 'bg' => 'D5E8F0', 'couleur' => '1F3864'],
            ['valeur' => $tachesEnCours > 0 ? (string)$tachesEnCours : '0', 'label' => 'En cours / restantes', 'bg' => $tachesEnCours > 0 ? 'FFF2CC' : 'E2EFDA', 'couleur' => $tachesEnCours > 0 ? 'C55A11' : '375623'],
        ]);
        $xml .= $this->p('');
        $xml .= $this->insererAnnotations($annotations, '1');

        // ═══════ SECTION 2 : RÉSULTATS DU SPRINT ═══════
        $xml .= $this->demoTitre('2', 'Résultats du sprint');

        // Encadré intro phase (table 1x1)
        $xml .= $this->docxEncadreTable("Détail des résultats par collaborateur — {$tachesTerminees} tâches terminées sur {$totalTaches}.", 'F2F2F2', '2E75B6', '2E75B6');
        $xml .= $this->p('');

        // Regrouper par développeur
        $parDev = [];
        foreach ($taches as $t) {
            $dev = $t['attribution'] ?? '—';
            $parDev[$dev][] = $t;
        }
        $subNum = 1;
        foreach ($parDev as $devNom => $tachesDev) {
            $xml .= $this->demoSousTitre("2.{$subNum}", $devNom, '1F3864', 'D5E8F0');
            $devRows = [];
            $devTotal = 0;
            $devRealise = 0;
            foreach ($tachesDev as $i => $t) {
                $pts = $t['points'] ?? 1;
                $devTotal += $pts;
                $termine = !empty($t['termine']);
                if ($termine) $devRealise += $pts;
                $statut = $termine ? 'Termine' : (($t['statut'] ?? '') ? ([ 'a_faire' => 'A faire', 'en_cours' => 'En cours', 'bloque' => 'Bloque', 'a_valider' => 'A valider', 'termine' => 'Termine', 'reporte' => 'Reporte' ][$t['statut']] ?? ucfirst(str_replace('_', ' ', $t['statut']))) : 'En cours');
                $devRows[] = [(string)($i + 1), $t['tache'] ?? '', (!empty($t['us']) ? $t['us'] : '—'), (string)$pts, $statut, $t['observation'] ?? ''];
            }
            $xml .= $this->docxTableMultiAlt(
                ['N', 'Tache', 'User Story', 'PTS', 'Statut', 'Observation'],
                $devRows, [500, 3000, 1600, 600, 1200, 2700], '1F3864', 'F4F7FB'
            );
            $devPct = $devTotal > 0 ? round($devRealise / $devTotal * 100) : 0;
            $devBilanBg = $devPct >= 80 ? 'E2EFDA' : ($devPct >= 50 ? 'FFF2CC' : 'FFDAD6');
            $devBilanClr = $devPct >= 80 ? '375623' : ($devPct >= 50 ? 'C55A11' : 'C00000');
            $xml .= $this->docxEncadreTable(
                "Score : {$devRealise}/{$devTotal} pts ({$devPct}%)",
                $devBilanBg, $devBilanClr, $devBilanClr, "Bilan — {$devNom}"
            );
            $xml .= $this->p('');
            $subNum++;
        }
        $xml .= $this->insererAnnotations($annotations, '2');

        // ═══════ SECTION 3 : ÉVALUATION GLOBALE ═══════
        $xml .= $this->demoTitre('3', 'Evaluation globale du sprint');

        $appreciation = $scorePct >= 90 ? 'Excellent' : ($scorePct >= 75 ? 'Bon' : ($scorePct >= 50 ? 'Acceptable' : 'Insuffisant'));

        // Points forts (vert — table encadré)
        $pointsForts = [];
        if ($scorePct >= 75) $pointsForts[] = "Taux de realisation eleve : {$scorePct}%";
        if ($tachesTerminees > 0) $pointsForts[] = "{$tachesTerminees} tache(s) terminee(s) avec succes";
        $xml .= $this->docxEncadreTable(
            !empty($pointsForts) ? implode(' | ', $pointsForts) : "Appreciation globale : {$appreciation}",
            'E2EFDA', '375623', '375623', 'Points forts'
        );
        $xml .= $this->p('');

        // Points à corriger (orange — table encadré)
        if ($scorePct < 100) {
            $aCorrection = [];
            if ($tachesEnCours > 0) $aCorrection[] = "{$tachesEnCours} tache(s) non terminee(s)";
            if ($scorePct < 75) $aCorrection[] = "Score sous le seuil acceptable ({$scorePct}%)";
            if (!empty($aCorrection)) {
                $xml .= $this->docxEncadreTable(
                    implode(' | ', $aCorrection),
                    'FCE4D6', 'C55A11', 'C55A11', 'Points a corriger'
                );
                $xml .= $this->p('');
            }
        }

        // Score critique (rouge — table encadré)
        if ($scorePct < 50) {
            $xml .= $this->docxEncadreTable(
                "Le score de {$scorePct}% est insuffisant. Des mesures correctives urgentes sont necessaires.",
                'FFDAD6', 'C00000', 'C00000', 'Observation critique'
            );
            $xml .= $this->p('');
        }

        // Score par développeur
        if (!empty($d['par_dev'])) {
            $xml .= $this->demoSousTitre('3.1', 'Score par collaborateur', '1F3864', 'D5E8F0');
            $devScoreRows = array_map(fn($dev) => [
                $dev['nom'], (string)$dev['realise'], (string)$dev['total'], $dev['pourcent'] . '%',
            ], $d['par_dev']);
            $xml .= $this->docxTableMultiAlt(
                ['Collaborateur', 'Realise', 'Total', '%'],
                $devScoreRows, [3600, 2000, 2000, 2000], '2E75B6', 'F2F2F2'
            );
            $xml .= $this->p('');
        }

        // Rappel méthodologique (table encadré)
        $xml .= $this->docxEncadreTable(
            "La demonstration de sprint est une ceremonie Scrum visant a presenter les increments realises au Product Owner et aux parties prenantes. Les fonctionnalites non terminees sont reportees au sprint suivant.",
            'F2F2F2', '1F3864', '1F3864', 'Rappel'
        );
        $xml .= $this->p('');
        $xml .= $this->insererAnnotations($annotations, '3');

        // ═══════ SECTION 4 : PROCHAINES ÉTAPES ═══════
        $xml .= $this->demoTitre('4', 'Prochaines etapes');

        if (!empty($d['prochaines_etapes'])) {
            // Couleurs alternées pour les étapes numérotées
            $etapeColors = [
                ['numBg' => '375623', 'numColor' => 'FFFFFF', 'bodyBg' => 'E2EFDA', 'bodyColor' => '375623'],
                ['numBg' => 'C00000', 'numColor' => 'FFFFFF', 'bodyBg' => 'FFDAD6', 'bodyColor' => 'C00000'],
                ['numBg' => 'C55A11', 'numColor' => 'FFFFFF', 'bodyBg' => 'FCE4D6', 'bodyColor' => 'C55A11'],
                ['numBg' => '2E75B6', 'numColor' => 'FFFFFF', 'bodyBg' => 'D5E8F0', 'bodyColor' => '2E75B6'],
                ['numBg' => '1F3864', 'numColor' => 'FFFFFF', 'bodyBg' => 'D5E8F0', 'bodyColor' => '1F3864'],
            ];
            foreach ($d['prochaines_etapes'] as $i => $etape) {
                $num = $i + 1;
                $label = is_array($etape) ? ($etape['titre'] ?? $etape['action'] ?? '') : (string)$etape;
                $desc = is_array($etape) ? ($etape['description'] ?? '') : '';
                $prio = is_array($etape) ? ($etape['priorite'] ?? '') : '';
                // Couleur par priorité si définie, sinon rotation
                $colors = $prio ? $this->etapeCouleurs($prio) : $etapeColors[$i % count($etapeColors)];
                $xml .= $this->docxEtapeCard($num, $label, $desc, $colors['numBg'], $colors['numColor'], $colors['bodyBg'], $colors['bodyColor']);
                $xml .= $this->p('');
            }
        } else {
            $xml .= $this->p('(A definir)');
        }
        $xml .= $this->insererAnnotations($annotations, '4');

        // ═══════ SECTION 5 : PLAN D'ACTIONS ═══════
        $xml .= $this->demoTitre('5', "Plan d'actions");

        if (!empty($d['actions_a_mener'])) {
            $xml .= $this->docxEncadreTable("Actions identifiees lors de la demonstration — " . count($d['actions_a_mener']) . " action(s).", 'F2F2F2', '1F3864', '1F3864');
            $xml .= $this->p('');
            $actRows = [];
            foreach ($d['actions_a_mener'] as $i => $a) {
                $rAct = $a['responsables'] ?? (isset($a['responsable']) && $a['responsable'] ? [$a['responsable']] : []);
                $prio = $a['priorite'] ?? '—';
                $actRows[] = [(string)($i + 1), $a['action'] ?? '', implode(', ', $rAct), $prio, $a['echeance'] ?? ''];
            }
            $xml .= $this->docxTableMultiAlt(
                ['N', 'Action', 'Responsable(s)', 'Priorite', 'Echeance'],
                $actRows, [500, 3800, 2000, 1200, 2100], '1F3864', 'F2F2F2'
            );
        } else {
            $xml .= $this->p('(A completer)');
        }
        $xml .= $this->p('');
        $xml .= $this->insererAnnotations($annotations, '5');

        // ═══════ SECTION 6 : CONCLUSION ═══════
        $xml .= $this->demoTitre('6', 'Conclusion');
        if (!empty($d['conclusion'])) {
            $xml .= $this->htmlToOoxml($d['conclusion'], '6.');
        } else {
            $xml .= $this->docxEncadreTable('(A renseigner)', 'F2F2F2', '1F3864', '1F3864');
        }
        $xml .= $this->insererAnnotations($annotations, '6');

        return $xml;
    }

    /* ===================================================================
     *  DOCX REVIEW SPRINT (format review_v1) - Document formel
     * =================================================================== */

    private function genererDocxReviewSprint(CompteRendu $cr, array $d, string $path): void
    {
        $logoPath = public_path('images/logo_soumafe.png');
        $hasLogo = file_exists($logoPath) && ($this->miseEnForme['logo'] ?? true);
        $cr->load(['redacteur', 'sprint.projet']);

        $redacteur = $cr->redacteur;
        $redNom = $redacteur?->nomComplet() ?? "—";
        $redTitre = $redacteur?->titre_poste ?? '';
        $sprint = $cr->sprint;
        $projetNom = $sprint?->projet?->libelle ?? 'SOUMAFÊ';
        $sprintLabel = $sprint ? "Sprint {$sprint->numero}" : 'Sprint';
        $headerLine = "SOUMAFÊ - {$projetNom} | Review Sprint - {$sprintLabel}";

        $xml = $this->docxHeader();

        if ($hasLogo) $xml .= $this->docxImage($logoPath, 1500000, 1050000);

        // Titre principal
        $xml .= $this->titreBloc('COMPTE RENDU DE REVIEW SPRINT');
        $xml .= $this->titreBloc($sprintLabel, 22);
        $xml .= $this->p('');

        // Encadré rédacteur
        $redLigne = "Rédigé par : {$redNom}";
        if ($redTitre) $redLigne .= " - {$redTitre}, SOUMAFÊ";
        $xml .= $this->encadre($redLigne);
        $xml .= $this->p('');

        // Tableau d'information
        $xml .= $this->docxInfoTable([
            ['Projet', $projetNom],
            ['Sprint', $sprintLabel],
            ['Période', ($sprint?->date_debut?->format('d/m/Y') ?? '') . ' au ' . ($sprint?->date_fin?->format('d/m/Y') ?? '')],
            ['Date review', $cr->date_reunion->format('d/m/Y')],
            ['Lieu', $cr->lieu ?? "—"],
            ['Horaire', $cr->heure_debut ? substr($cr->heure_debut, 0, 5) . ($cr->heure_fin ? ' - ' . substr($cr->heure_fin, 0, 5) : '') : '-'],
            ['Rédacteur', $redNom],
        ]);
        $xml .= $this->p('');

        // Objectif du sprint (rappel)
        if ($objectif = trim((string) ($d['objectif'] ?? $sprint?->objectif ?? ''))) {
            $xml .= $this->encadre('Objectif du sprint : ' . $objectif);
            $xml .= $this->p('');
        }

        // === SECTION 1 : PARTICIPANTS ===
        if (!empty($d['presences'])) {
            $xml .= $this->titreSection('1', 'PARTICIPANTS');
            $presRows = array_map(fn($p) => [
                $p['nom'] ?? '', ($p['fonction'] ?? '') ?: "—",
                ($p['present'] ?? false) ? 'Présent' : 'Absent',
            ], $d['presences']);
            $xml .= $this->docxTableMultiAlt(['Collaborateur', 'Fonction / Poste', 'Statut'], $presRows, [3800, 3600, 2200]);
            $xml .= $this->p('');
        }

        // === SECTION 2 : MODULES LIVRÉS ===
        $xml .= $this->titreSection('2', 'MODULES LIVRÉS');
        if (!empty($d['modules_livres'])) {
            $modRows = array_map(fn($m, $i) => [
                (string)($i + 1),
                $m['nom'] ?? '',
                match ($m['statut'] ?? '') { 'livre' => 'Livré', 'partiel' => 'Partiel', default => 'Non livré' },
                $m['observation'] ?? '',
            ], $d['modules_livres'], array_keys($d['modules_livres']));
            $xml .= $this->docxTableMultiAlt(
                ['N°', 'Module', 'Statut', 'Observation'],
                $modRows, [600, 3600, 1800, 3600]
            );
        } else {
            $xml .= $this->p('(Aucun module renseigné)');
        }
        $xml .= $this->p('');

        // === SECTION 3 : USER STORIES (BDD) ===
        $xml .= $this->titreSection('3', 'USER STORIES - CRITÈRES D\'ACCEPTATION');
        $usBdd = $d['user_stories_bdd'] ?? [];
        if (!empty($usBdd)) {
            foreach ($usBdd as $ui => $us) {
                $moscowLabel = \App\Models\UserStory::MOSCOW[$us['moscow'] ?? 'should'] ?? $us['moscow'];
                $verdictLabel = match ($us['verdict'] ?? '') { 'acceptee' => 'Acceptée', 'refusee' => 'Refusée', default => 'Partielle' };
                $verdictColor = match ($us['verdict'] ?? '') { 'acceptee' => '2E8B57', 'refusee' => 'CC3333', default => 'E6A817' };
                $verdictBg = match ($us['verdict'] ?? '') { 'acceptee' => 'F0F7ED', 'refusee' => 'FEECEC', default => 'FFF8E1' };

                $xml .= $this->sousTitre('3.' . ($ui + 1), ($us['numero'] ?? '') . ' - ' . ($us['intitule'] ?? ''));

                // Priorité MoSCoW
                $xml .= $this->p("Priorité : {$moscowLabel}" . (isset($us['niveau']) && $us['niveau'] !== null ? " · Niveau d'exécution : {$us['niveau']} %" . (($us['niveau_origine'] ?? '') === 'declare' ? ' (déclaré par l\'équipe)' : '') : '') . (!empty($us['epic']) ? " · Epic : {$us['epic']}" : ''), true, 11);

                // Critères BDD (Given/When/Then)
                $criteres = $us['criteres'] ?? [];
                if (!empty($criteres)) {
                    $critRows = [];
                    foreach ($criteres as $ci => $c) {
                        $statutCrit = match ($c['statut'] ?? 'na') { 'valide' => 'Validé', 'echoue' => 'Échoué', default => 'N/A' };
                        $critRows[] = [
                            (string)($ci + 1),
                            'Given: ' . ($c['given'] ?? ''),
                            'When: ' . ($c['when'] ?? ''),
                            'Then: ' . ($c['then'] ?? ''),
                            $statutCrit,
                        ];
                    }
                    $xml .= $this->docxTableMultiAlt(
                        ['N°', 'Given (Contexte)', 'When (Action)', 'Then (Résultat)', 'Statut'],
                        $critRows, [500, 2400, 2400, 2400, 1900]
                    );
                }

                // Verdict
                $xml .= $this->bilanBloc("Verdict : {$verdictLabel}", $us['observation'] ?? '', $verdictBg, $verdictColor);
                $xml .= $this->p('');
            }
        } else {
            $xml .= $this->p('(Aucune user story renseignée)');
        }

        // === SECTION 4 : RETOURS ET FEEDBACK ===
        $xml .= $this->titreSection('4', 'RETOURS ET FEEDBACK');
        $feedback = $d['feedback'] ?? [];
        if (!empty($feedback['client'])) {
            $xml .= $this->sousTitre('4.1', 'Retour client / PO');
            $xml .= $this->htmlToOoxml($feedback['client'], '4.1.');
            $xml .= $this->p('');
        }
        if (!empty($feedback['positifs'])) {
            $positifs = array_filter($feedback['positifs'], fn($p) => trim($p) !== '');
            if ($positifs) {
                $xml .= $this->sousTitre('4.2', 'Points positifs');
                foreach ($positifs as $i => $p) {
                    $xml .= $this->listItemParagraph(($i + 1) . '.', $p);
                }
                $xml .= $this->p('');
            }
        }
        if (!empty($feedback['ameliorations'])) {
            $ameliorations = array_filter($feedback['ameliorations'], fn($a) => trim($a) !== '');
            if ($ameliorations) {
                $xml .= $this->sousTitre('4.3', 'Axes d\'amélioration');
                foreach ($ameliorations as $i => $a) {
                    $xml .= $this->listItemParagraph(($i + 1) . '.', $a);
                }
                $xml .= $this->p('');
            }
        }

        // === SECTION 5 : KPIs SPRINT ===
        $xml .= $this->titreSection('5', 'INDICATEURS CLÉS DU SPRINT');
        $kpis = $d['kpis'] ?? [];
        if (!empty($kpis)) {
            $taux = $kpis['taux'] ?? 0;
            $appreciation = $taux >= 90 ? 'Excellent' : ($taux >= 75 ? 'Bon' : ($taux >= 50 ? 'Acceptable' : 'Insuffisant'));
            // Sprint non clôturé : bilan provisoire, sans appréciation
            $enCours = $sprint && $sprint->statut !== 'termine';
            $xml .= $this->docxTableMultiAlt(
                ['Indicateur', 'Valeur'],
                [
                    ['Tâches totales', (string)($kpis['taches'] ?? 0)],
                    ['Tâches réalisées', (string)($kpis['realisees'] ?? 0)],
                    ['Taux de réalisation', $taux . '%'],
                    ['Story points engagés', (string)($kpis['points'] ?? 0)],
                    ['Story points livrés', (string)($kpis['points_faits'] ?? 0)],
                    ['User stories', ($kpis['stories_terminees'] ?? 0) . '/' . ($kpis['stories'] ?? 0) . ' terminées'],
                    ['Tâches en retard', (string)($kpis['en_retard'] ?? 0)],
                    ['Blocages ouverts', (string)($kpis['blocages_ouverts'] ?? 0)],
                    ['Dailies tenus', (string)($kpis['dailies'] ?? 0)],
                    ['Tâches hors sprint', (string)($kpis['hors_sprint'] ?? 0)],
                ],
                [4800, 4800]
            );
            $xml .= $this->p('');
            $detail = "Taux de réalisation : {$taux}% - " . ($kpis['points_faits'] ?? 0) . '/' . ($kpis['points'] ?? 0) . ' story points livrés'
                . (!empty($d['kpis_figes_le']) ? " (valeurs au {$d['kpis_figes_le']})" : '');
            $xml .= $enCours
                ? $this->bilanBloc('Bilan provisoire : sprint en cours', $detail, 'EFF4FB', '2E74B5')
                : $this->bilanBloc(
                    "Appréciation globale : {$appreciation}", $detail,
                    $taux >= 75 ? 'F0F7ED' : ($taux >= 50 ? 'FFF8E1' : 'FEECEC'),
                    $taux >= 75 ? '2E8B57' : ($taux >= 50 ? 'E6A817' : 'CC3333')
                );
        } else {
            $xml .= $this->p('(KPIs non disponibles)');
        }
        $xml .= $this->p('');

        // === SECTION 6 : DÉCISIONS ET ACTIONS ===
        $xml .= $this->titreSection('6', 'DÉCISIONS ET ACTIONS À MENER');
        $decisions = $d['decisions'] ?? [];
        if (!empty($decisions)) {
            $decRows = [];
            foreach ($decisions as $i => $dec) {
                $resp = is_array($dec['responsables'] ?? null) ? implode(', ', $dec['responsables']) : ($dec['responsables'] ?? '');
                $decRows[] = [(string)($i + 1), $dec['action'] ?? '', $resp ?: '-', ucfirst($dec['priorite'] ?? '') ?: '-',
                    !empty($dec['echeance']) ? Carbon::parse($dec['echeance'])->format('d/m/Y') : '-'];
            }
            $xml .= $this->docxTableMultiAlt(
                ['N°', 'Décision / Action', 'Responsable(s)', 'Priorité', 'Échéance'],
                $decRows, [600, 4000, 2000, 1200, 1800]
            );
        } else {
            $xml .= $this->p('(Aucune décision enregistrée)');
        }
        $xml .= $this->p('');

        // === SECTION 7 : CONCLUSION ===
        if (!empty($d['conclusion'])) {
            $xml .= $this->titreSection('7', 'CONCLUSION');
            $xml .= $this->htmlToOoxml($d['conclusion'], '7.');
            $xml .= $this->p('');
        }

        // === SECTION 8 : SIGNATURES ===
        $signatures = $d['signatures'] ?? [];
        if (!empty($signatures)) {
            $sectionNum = !empty($d['conclusion']) ? '8' : '7';
            $xml .= $this->titreSection($sectionNum, 'SIGNATURES');
            $sigRows = array_map(fn($s) => [$s['nom'] ?? '', $s['role'] ?? '', !empty($s['date']) ? Carbon::parse($s['date'])->format('d/m/Y') : ''], $signatures);
            $xml .= $this->docxTableMultiAlt(['Nom', 'Rôle', 'Date'], $sigRows, [3600, 3600, 2400]);
            $xml .= $this->p('');
        }

        // Signature rédacteur
        $xml .= $this->signatureBloc($redNom, $redTitre, $cr->date_reunion->format('d/m/Y'));

        $xml .= $this->docxSectPr();
        $this->ecrireDocx($path, $xml, $hasLogo ? $logoPath : null, $headerLine, 'Confidentiel - Usage interne');
    }

    /* ===================================================================
     *  HELPERS DOCX - Times New Roman 12pt, interligne 1,5
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
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>' . $this->borders() . '</w:tblBorders><w:tblCellMar><w:top w:w="50" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="50" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr>';
        $xml .= '<w:tr>' . $this->cell($h1, true, '1F4E79', 'FFFFFF') . $this->cell($h2, true, '1F4E79', 'FFFFFF', 2000) . '</w:tr>';
        foreach ($rows as $r) $xml .= '<w:tr>' . $this->cell($r[0]) . $this->cell($r[1], false, null, null, 2000) . '</w:tr>';
        return $xml . '</w:tbl>';
    }

    private function docxTable3Col(string $h1, string $h2, string $h3, array $rows): string
    {
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>' . $this->borders() . '</w:tblBorders><w:tblCellMar><w:top w:w="50" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="50" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr>';
        $xml .= '<w:tr>' . $this->cell($h1, true, '1F4E79', 'FFFFFF', 4000) . $this->cell($h2, true, '1F4E79', 'FFFFFF', 2500) . $this->cell($h3, true, '1F4E79', 'FFFFFF', 2500) . '</w:tr>';
        foreach ($rows as $r) $xml .= '<w:tr>' . $this->cell($r[0], false, null, null, 4000) . $this->cell($r[1], false, null, null, 2500) . $this->cell($r[2], false, null, null, 2500) . '</w:tr>';
        return $xml . '</w:tbl>';
    }

    private function docxTableMulti(array $headers, array $rows): string
    {
        $n = count($headers);
        $w = intdiv(9000, $n);
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9000" w:type="dxa"/><w:tblBorders>' . $this->borders() . '</w:tblBorders><w:tblCellMar><w:top w:w="50" w:type="dxa"/><w:left w:w="100" w:type="dxa"/><w:bottom w:w="50" w:type="dxa"/><w:right w:w="100" w:type="dxa"/></w:tblCellMar></w:tblPr>';
        $xml .= '<w:tr><w:trPr><w:tblHeader/><w:cantSplit/></w:trPr>';
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
        $szCell = (int) round($this->mefSz('texte', 24) * 22 / 24);
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        $bTag = $bold ? '<w:b/>' : '';
        $colorTag = $fg ? "<w:color w:val=\"{$fg}\"/>" : '';
        $shdTag = $bg ? "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bg}\"/>" : '';
        $wTag = $width ? "<w:tcW w:w=\"{$width}\" w:type=\"dxa\"/>" : '';

        // Texte des tableaux : aligné à gauche (la justification étire les mots en colonne étroite), gris anthracite
        if (!$fg) $colorTag = '<w:color w:val="1F2937"/>';
        return "<w:tc><w:tcPr>{$wTag}{$shdTag}<w:vAlign w:val=\"center\"/></w:tcPr><w:p><w:pPr><w:spacing w:before=\"20\" w:after=\"20\" w:line=\"264\" w:lineRule=\"auto\"/><w:jc w:val=\"left\"/></w:pPr>"
            . "<w:r><w:rPr><w:rFonts w:ascii=\"Times New Roman\" w:hAnsi=\"Times New Roman\" w:cs=\"Times New Roman\"/>"
            . "<w:sz w:val=\"{$szCell}\"/><w:szCs w:val=\"{$szCell}\"/>{$bTag}{$colorTag}</w:rPr>"
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p></w:tc>";
    }

    private function borders(): string
    {
        $b = 'w:val="single" w:sz="4" w:space="0" w:color="A6B1C2"';
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

    /** Titre de section numéroté - bleu SOUMAFÊ */
    private function titreSection(string $num, string $text): string
    {
        $escaped = htmlspecialchars("{$num}. " . mb_strtoupper($text, 'UTF-8'), ENT_XML1, 'UTF-8');
        return '<w:p><w:pPr>'
            . '<w:pBdr><w:bottom w:val="single" w:sz="12" w:space="2" w:color="' . ($this->miseEnForme['couleur_section'] ?? '2E74B5') . '"/></w:pBdr>'
            . '<w:shd w:val="clear" w:color="auto" w:fill="E3EEF9"/>'
            . '<w:spacing w:before="240" w:after="120" w:line="360" w:lineRule="auto"/>'
            . '<w:ind w:left="60" w:right="60"/>'
            . '<w:jc w:val="left"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:b/><w:caps/><w:color w:val="' . ($this->miseEnForme['couleur_section'] ?? '1F4E79') . '"/>'
            . '<w:sz w:val="' . $this->mefSz('section', 26) . '"/><w:szCs w:val="' . $this->mefSz('section', 26) . '"/>'
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
            . '<w:sz w:val="' . $this->mefSz('sous_titre', 24) . '"/><w:szCs w:val="' . $this->mefSz('sous_titre', 24) . '"/>'
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    /** Encadré - bloc avec fond coloré et bordure gauche épaisse */
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
        if ($size === 28) $size = $this->mefSz('titre', 28);
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
            'methode'         => ['label' => 'Méthode',         'icon' => "\xE2\x9A\x99", 'bg' => 'F3E8F9', 'border' => '7B2D8E'],
            'conseil'         => ['label' => 'Conseil',         'icon' => "\xF0\x9F\x92\xA1", 'bg' => 'FFF8E1', 'border' => 'EF6C00'],
            'definition'      => ['label' => 'Définition',      'icon' => "\xF0\x9F\x93\x96", 'bg' => 'E3F2FD', 'border' => '1565C0'],
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

    // ──────────── Helpers spécifiques rapport de démonstration élégant ────────────

    /** Titre de section du rapport de review : fond bleu clair, trait bas bleu */
    private function demoTitre(string $num, string $text): string
    {
        $numE = htmlspecialchars("{$num}. ", ENT_XML1, 'UTF-8');
        $txtE = htmlspecialchars(mb_strtoupper($text, 'UTF-8'), ENT_XML1, 'UTF-8');
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
        return '<w:p><w:pPr>'
            . '<w:pBdr><w:bottom w:val="single" w:sz="12" w:space="2" w:color="' . ($this->miseEnForme['couleur_section'] ?? '2E74B5') . '"/></w:pBdr>'
            . '<w:shd w:val="clear" w:color="auto" w:fill="E3EEF9"/>'
            . '<w:spacing w:before="300" w:after="120" w:line="360" w:lineRule="auto"/>'
            . '<w:ind w:left="60" w:right="60"/>'
            . '</w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"" . ($this->miseEnForme['couleur_section'] ?? '1F4E79') . "\"/><w:sz w:val=\"{$this->mefSz('section', 32)}\"/><w:szCs w:val=\"{$this->mefSz('section', 32)}\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$numE}</w:t></w:r>"
            . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"" . ($this->miseEnForme['couleur_section'] ?? '1F4E79') . "\"/><w:sz w:val=\"{$this->mefSz('section', 32)}\"/><w:szCs w:val=\"{$this->mefSz('section', 32)}\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$txtE}</w:t></w:r></w:p>";
    }

    /** Sous-titre style rapport démo (12pt bold, couleur + fond paramétrable) */
    private function demoSousTitre(string $num, string $text, string $color = '1F3864', string $bg = 'D5E8F0', ?int $sz = null, string $souligne = ''): string
    {
        $escaped = htmlspecialchars("{$num} {$text}", ENT_XML1, 'UTF-8');
        return '<w:p><w:pPr>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bg}\"/>"
            . ($souligne === 'section' ? '<w:pBdr><w:bottom w:val="single" w:sz="6" w:space="2" w:color="9FBFE0"/></w:pBdr>' : '')
            . ($souligne === 'sous_titre' ? '<w:pBdr><w:bottom w:val="dotted" w:sz="4" w:space="2" w:color="9FBFE0"/></w:pBdr>' : '')
            . '<w:spacing w:before="200" w:after="80" w:line="360" w:lineRule="auto"/>'
            . '</w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . "<w:b/><w:color w:val=\"{$color}\"/>"
            . '<w:sz w:val="' . ($sz ?? $this->mefSz('sous_titre', 24)) . '"/><w:szCs w:val="' . ($sz ?? $this->mefSz('sous_titre', 24)) . '"/>'
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    /** Bannière en-tête fidèle au document référence : logo gauche + infos droite */
    private function docxBannerTable(string $dept, string $titre, string $sousTitre, string $auteur = '', string $date = ''): string
    {
        $e = fn($s) => htmlspecialchars($s, ENT_XML1, 'UTF-8');
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
        $noBorders = '<w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9600" w:type="dxa"/>'
            . "<w:tblBorders>{$noBorders}</w:tblBorders></w:tblPr>";
        // Cellule gauche : fond bleu foncé + LOGO embarqué
        $logoCx = 1200000; $logoCy = 840000;
        $logoDrawing = '<w:drawing>'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
            . "<wp:extent cx=\"{$logoCx}\" cy=\"{$logoCy}\"/>"
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="2" name="BannerLogo"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic><pic:nvPicPr><pic:cNvPr id="2" name="logo.png"/><pic:cNvPicPr/></pic:nvPicPr>'
            . '<pic:blipFill><a:blip r:embed="rId10"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            . "<pic:spPr><a:xfrm><a:off x=\"0\" y=\"0\"/><a:ext cx=\"{$logoCx}\" cy=\"{$logoCy}\"/></a:xfrm>"
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            . '</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing>';

        $xml .= '<w:tr><w:trPr><w:trHeight w:val="1400" w:hRule="atLeast"/></w:trPr>'
            . '<w:tc><w:tcPr><w:tcW w:w="2400" w:type="dxa"/>'
            . '<w:shd w:val="clear" w:color="auto" w:fill="1F3864"/>'
            . '<w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="0"/></w:pPr>'
            . (($this->miseEnForme['logo'] ?? true) ? "<w:r>{$logoDrawing}</w:r>" : '') . '</w:p></w:tc>';
        // Cellule droite : fond bleu vif + dept, titre, sprint, auteur, date
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="7200" w:type="dxa"/>'
            . '<w:shd w:val="clear" w:color="auto" w:fill="0070C0"/>'
            . '<w:vAlign w:val="center"/></w:tcPr>';
        // Ligne département
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="0"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:color w:val=\"D6E4F0\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$e($dept)}</w:t></w:r></w:p>";
        // Ligne titre principal (36pt bold blanc)
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="0"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"FFFFFF\"/><w:sz w:val=\"{$this->mefSz('titre', 36)}\"/><w:szCs w:val=\"{$this->mefSz('titre', 36)}\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$e($titre)}</w:t></w:r></w:p>";
        // Ligne sous-titre sprint (22pt bold blanc)
        $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="0"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"FFFFFF\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$e($sousTitre)}</w:t></w:r></w:p>";
        // Ligne auteur (D6E4F0)
        if ($auteur) {
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="0"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:color w:val=\"D6E4F0\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
                . "<w:t xml:space=\"preserve\">{$e($auteur)}</w:t></w:r></w:p>";
        }
        // Ligne date (D6E4F0)
        if ($date) {
            $xml .= '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="0"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:color w:val=\"D6E4F0\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
                . "<w:t xml:space=\"preserve\">{$e($date)}</w:t></w:r></w:p>";
        }
        $xml .= '</w:tc></w:tr></w:tbl>';
        return $xml;
    }

    /** Ligne de KPI cards (tableau 1 ligne, N colonnes colorées, grand chiffre + label) */
    private function docxKpiCards(array $cards): string
    {
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
        $n = count($cards);
        if ($n === 0) return '';
        $w = intdiv(9600, $n);
        $noBorders = '<w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideV w:val="single" w:sz="8" w:space="0" w:color="FFFFFF"/>';
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9600" w:type="dxa"/>'
            . "<w:tblBorders>{$noBorders}</w:tblBorders></w:tblPr><w:tr>";
        foreach ($cards as $c) {
            $val = htmlspecialchars((string)($c['valeur'] ?? ''), ENT_XML1, 'UTF-8');
            $lab = htmlspecialchars((string)($c['label'] ?? ''), ENT_XML1, 'UTF-8');
            $bg = $c['bg'] ?? 'D5E8F0';
            $color = $c['couleur'] ?? '1F3864';
            $sz = (int) ($c['sz'] ?? 48);
            $det = trim((string) ($c['detail'] ?? ''));
            $detXml = $det === '' ? '' : '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="60" w:before="0"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:color w:val=\"64748B\"/><w:sz w:val=\"16\"/><w:szCs w:val=\"16\"/></w:rPr>"
                . '<w:t xml:space="preserve">' . htmlspecialchars($det, ENT_XML1, 'UTF-8') . '</w:t></w:r></w:p>';
            $xml .= "<w:tc><w:tcPr><w:tcW w:w=\"{$w}\" w:type=\"dxa\"/>"
                . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bg}\"/>"
                . '<w:vAlign w:val="center"/></w:tcPr>'
                . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="60"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"{$color}\"/><w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/></w:rPr>"
                . "<w:t xml:space=\"preserve\">{$val}</w:t></w:r></w:p>"
                . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="' . ($det === '' ? 60 : 0) . '" w:before="0"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"2E74B5\"/><w:sz w:val=\"16\"/><w:szCs w:val=\"16\"/></w:rPr>"
                . "<w:t xml:space=\"preserve\">{$lab}</w:t></w:r></w:p>{$detXml}</w:tc>";
        }
        return $xml . '</w:tr></w:tbl>';
    }

    /** Card étape numérotée (numéro coloré à gauche + titre + description à droite) */
    private function docxEtapeCard(int $num, string $titre, string $desc, string $numBg, string $numColor, string $bodyBg, string $bodyColor): string
    {
        $e = fn($s) => htmlspecialchars($s, ENT_XML1, 'UTF-8');
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
        $noBorders = '<w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:left w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9600" w:type="dxa"/>'
            . "<w:tblBorders>{$noBorders}</w:tblBorders></w:tblPr>"
            . '<w:tr><w:tc><w:tcPr><w:tcW w:w="800" w:type="dxa"/>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$numBg}\"/>"
            . '<w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0" w:before="0"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"{$numColor}\"/><w:sz w:val=\"32\"/><w:szCs w:val=\"32\"/></w:rPr>"
            . "<w:t>{$num}</w:t></w:r></w:p></w:tc>";
        $xml .= '<w:tc><w:tcPr><w:tcW w:w="8800" w:type="dxa"/>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bodyBg}\"/>"
            . '<w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p><w:pPr><w:spacing w:after="0" w:before="60"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"{$bodyColor}\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$e($titre)}</w:t></w:r></w:p>";
        if ($desc) {
            $xml .= '<w:p><w:pPr><w:spacing w:after="60" w:before="0"/><w:jc w:val="both"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:color w:val=\"404040\"/><w:sz w:val=\"20\"/><w:szCs w:val=\"20\"/></w:rPr>"
                . "<w:t xml:space=\"preserve\">{$e($desc)}</w:t></w:r></w:p>";
        }
        $xml .= '</w:tc></w:tr></w:tbl>';
        return $xml;
    }

    /** Table résumé avec en-tête coloré (ex: Volet/Résultat) */
    private function docxResumeTable(array $headers, array $rows, string $headerBg = '2E75B6'): string
    {
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
        $n = count($headers);
        if ($n === 0) return '';
        $w = intdiv(9600, $n);
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9600" w:type="dxa"/><w:tblBorders>' . $this->borders() . '</w:tblBorders></w:tblPr>';
        // En-tête
        $xml .= '<w:tr><w:trPr><w:tblHeader/></w:trPr>';
        foreach ($headers as $h) {
            $e = htmlspecialchars($h, ENT_XML1, 'UTF-8');
            $xml .= "<w:tc><w:tcPr><w:tcW w:w=\"{$w}\" w:type=\"dxa\"/>"
                . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$headerBg}\"/></w:tcPr>"
                . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"FFFFFF\"/><w:sz w:val=\"20\"/><w:szCs w:val=\"20\"/></w:rPr>"
                . "<w:t xml:space=\"preserve\">{$e}</w:t></w:r></w:p></w:tc>";
        }
        $xml .= '</w:tr>';
        // Lignes alternées
        foreach ($rows as $ri => $row) {
            $bg = ($ri % 2 === 1) ? 'F2F2F2' : 'FFFFFF';
            $xml .= '<w:tr>';
            foreach ($row as $val) {
                $e = htmlspecialchars((string)$val, ENT_XML1, 'UTF-8');
                $xml .= "<w:tc><w:tcPr><w:tcW w:w=\"{$w}\" w:type=\"dxa\"/>"
                    . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bg}\"/></w:tcPr>"
                    . '<w:p><w:pPr><w:jc w:val="left"/></w:pPr>'
                    . "<w:r><w:rPr>{$rpr}<w:sz w:val=\"20\"/><w:szCs w:val=\"20\"/><w:color w:val=\"404040\"/></w:rPr>"
                    . "<w:t xml:space=\"preserve\">{$e}</w:t></w:r></w:p></w:tc>";
            }
            $xml .= '</w:tr>';
        }
        return $xml . '</w:tbl>';
    }

    /** Encadré table 1x1 avec bordure gauche épaisse (fidèle au document référence) */
    private function docxEncadreTable(string $text, string $bgColor = 'F2F2F2', string $borderColor = '1F3864', string $labelColor = '1F3864', string $label = ''): string
    {
        $rpr = '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>';
        $noBorders = '<w:top w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:bottom w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:right w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>';
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="9600" w:type="dxa"/><w:tblBorders>'
            . '<w:top w:val="single" w:sz="4" w:space="0" w:color="' . self::teinte($borderColor) . '"/>'
            . "<w:left w:val=\"single\" w:sz=\"4\" w:space=\"0\" w:color=\"" . self::teinte($borderColor) . "\"/>"
            . "<w:bottom w:val=\"single\" w:sz=\"4\" w:space=\"0\" w:color=\"" . self::teinte($borderColor) . "\"/>"
            . "<w:right w:val=\"single\" w:sz=\"4\" w:space=\"0\" w:color=\"" . self::teinte($borderColor) . "\"/>"
            . '<w:insideH w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '<w:insideV w:val="none" w:sz="0" w:space="0" w:color="auto"/>'
            . '</w:tblBorders></w:tblPr>';
        $xml .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="9600" w:type="dxa"/>'
            . "<w:shd w:val=\"clear\" w:color=\"auto\" w:fill=\"{$bgColor}\"/></w:tcPr>";
        if ($label) {
            $labelE = htmlspecialchars($label, ENT_XML1, 'UTF-8');
            $xml .= '<w:p><w:pPr><w:spacing w:after="40" w:before="60"/></w:pPr>'
                . "<w:r><w:rPr>{$rpr}<w:b/><w:color w:val=\"{$labelColor}\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
                . "<w:t xml:space=\"preserve\">{$labelE}</w:t></w:r></w:p>";
        }
        $textE = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        $xml .= '<w:p><w:pPr><w:spacing w:after="60" w:before="40"/><w:jc w:val="both"/></w:pPr>'
            . "<w:r><w:rPr>{$rpr}<w:color w:val=\"404040\"/><w:sz w:val=\"22\"/><w:szCs w:val=\"22\"/></w:rPr>"
            . "<w:t xml:space=\"preserve\">{$textE}</w:t></w:r></w:p>";
        $xml .= '</w:tc></w:tr></w:tbl>';
        return $xml;
    }

    /** Paragraphe auteur/date style rapport démo (11pt gris, justifié) */
    private function demoAuteur(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
        return '<w:p><w:pPr><w:jc w:val="both"/><w:spacing w:before="40" w:after="40"/></w:pPr>'
            . '<w:r><w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:color w:val="404040"/><w:sz w:val="22"/><w:szCs w:val="22"/>'
            . '</w:rPr>'
            . "<w:t xml:space=\"preserve\">{$escaped}</w:t></w:r></w:p>";
    }

    /** Couleurs par priorité pour les cards d'étapes */
    private function etapeCouleurs(string $priorite): array
    {
        return match (mb_strtolower($priorite)) {
            'critique', 'urgente' => ['numBg' => 'C00000', 'numColor' => 'FFFFFF', 'bodyBg' => 'FFDAD6', 'bodyColor' => 'C00000'],
            'haute'               => ['numBg' => 'C55A11', 'numColor' => 'FFFFFF', 'bodyBg' => 'FCE4D6', 'bodyColor' => 'C55A11'],
            'moyenne'             => ['numBg' => '2E75B6', 'numColor' => 'FFFFFF', 'bodyBg' => 'D5E8F0', 'bodyColor' => '2E75B6'],
            'basse'               => ['numBg' => '375623', 'numColor' => 'FFFFFF', 'bodyBg' => 'E2EFDA', 'bodyColor' => '375623'],
            default               => ['numBg' => '1F3864', 'numColor' => 'FFFFFF', 'bodyBg' => 'D5E8F0', 'bodyColor' => '1F3864'],
        };
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
    private function docxTableMultiAlt(array $headers, array $rows, ?array $widths = null, string $headerBg = '1F4E79', string $altBg = 'F4F7FB'): string
    {
        $n = count($headers);
        $totalW = 9600;
        $defaultW = intdiv($totalW, $n);
        $xml = "<w:tbl><w:tblPr><w:tblW w:w=\"{$totalW}\" w:type=\"dxa\"/><w:tblBorders>" . $this->borders() . '</w:tblBorders></w:tblPr>';
        $xml .= '<w:tr><w:trPr><w:tblHeader/><w:cantSplit/></w:trPr>';
        foreach ($headers as $i => $h) $xml .= $this->cell($h, true, $headerBg, 'FFFFFF', $widths[$i] ?? $defaultW);
        $xml .= '</w:tr>';
        foreach ($rows as $ri => $r) {
            $bg = ($ri % 2 === 1) ? $altBg : null;
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
    private function genDocxFooterXml(string $line = 'Confidentiel - Usage interne', bool $numPage = true): string
    {
        $escaped = htmlspecialchars($line, ENT_XML1, 'UTF-8');
        $rpr = '<w:rPr>'
            . '<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="16"/><w:szCs w:val="16"/>'
            . '<w:i/><w:color w:val="4B5563"/>'
            . '</w:rPr>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:p><w:pPr>'
            . '<w:pBdr><w:top w:val="single" w:sz="4" w:space="1" w:color="1F4E79"/></w:pBdr>'
            . '<w:jc w:val="center"/>'
            . '</w:pPr>'
            . "<w:r>{$rpr}<w:t xml:space=\"preserve\">{$escaped}" . ($numPage ? ($escaped !== '' ? ' - ' : '') . 'Page ' : '') . "</w:t></w:r>"
            . ($numPage ? "<w:r>{$rpr}<w:fldChar w:fldCharType=\"begin\"/></w:r>"
                . "<w:r>{$rpr}<w:instrText xml:space=\"preserve\"> PAGE </w:instrText></w:r>"
                . "<w:r>{$rpr}<w:fldChar w:fldCharType=\"end\"/></w:r>" : '')
            . '</w:p></w:ftr>';
    }

    /** Nettoyer le HTML provenant du contenteditable (garder uniquement b, u, i, ol, ul, li, br, div) */
    /** Classes d'interligne de l'éditeur => valeur OOXML w:line (240 = simple) */
    private const INTERLIGNES = ['lh-10', 'lh-115', 'lh-15', 'lh-20', 'lh-25'];
    private const INTERLIGNES_OOXML = ['lh-10' => 240, 'lh-115' => 276, 'lh-15' => 360, 'lh-20' => 480, 'lh-25' => 600];
    private int $ooxmlInterligne = 360;
    /** Tailles choisies dans « Mise en forme du document » (pt), null = défaut */
    private array $miseEnForme = [];
    /** Sprint du rapport en cours de génération (indicateurs : comparaison au sprint précédent). */
    private ?Sprint $sprintDocx = null;

    private function mefSz(string $cle, int $defautDemiPt): int
    {
        $pt = $this->miseEnForme[$cle] ?? null;
        return $pt ? (int) round($pt * 2) : $defautDemiPt;
    }

    /** Applique l'interligne porté par $el (classe lh-*) le temps de $fn */
    private function avecInterligne(\DOMNode $el, callable $fn): string
    {
        $avant = $this->ooxmlInterligne;
        if ($el instanceof \DOMElement) {
            foreach (self::INTERLIGNES_OOXML as $classe => $val) {
                if (str_contains(' ' . $el->getAttribute('class') . ' ', " {$classe} ")) $this->ooxmlInterligne = $val;
            }
        }
        try { return (string) $fn(); } finally { $this->ooxmlInterligne = $avant; }
    }

    /** Couleurs de texte de l'éditeur (classe tc-N) : sobres, lisibles à l'impression. */
    /** Couleurs de bloc du rapport : [libellé, fond, bordure], hexadécimal sans « # ». */
    public const BLOC_COULEURS = [
        'bleu' => ['Bleu', 'EFF6FF', '2E74B5'], 'vert' => ['Vert', 'F0FDF4', '15803D'], 'ambre' => ['Ambre', 'FFFBEB', 'B45309'],
        'rouge' => ['Rouge', 'FEF2F2', 'B91C1C'], 'violet' => ['Violet', 'F5F3FF', '7E22CE'], 'gris' => ['Gris', 'F8FAFC', '64748B'],
    ];
    public const HORS_US = 'Hors user story';

    /** Polices proposées pour le rendu (PDF et Word) : [libellé, famille CSS]. Segoe UI par défaut. */
    public const POLICES = [
        'segoe'   => ['Segoe UI', "'Segoe UI',system-ui,-apple-system,Arial,sans-serif"],
        'calibri' => ['Calibri', "Calibri,Carlito,'Segoe UI',Arial,sans-serif"],
        'arial'   => ['Arial', "Arial,Helvetica,sans-serif"],
        'verdana' => ['Verdana', "Verdana,Geneva,sans-serif"],
        'times'   => ['Times New Roman', "'Times New Roman',Times,serif"],
        'georgia' => ['Georgia', "Georgia,'Times New Roman',serif"],
        'cambria' => ['Cambria', "Cambria,Caladea,Georgia,serif"],
    ];

    /** Police de rendu d'un document : [libellé Word, famille CSS]. */
    public static function police(array $miseEnForme): array
    {
        return self::POLICES[$miseEnForme['police'] ?? ''] ?? self::POLICES['segoe'];
    }

    /** Teinte claire d'une couleur (mélange avec le blanc) : bordure légère des blocs colorés. */
    public static function teinte(string $hex, float $part = 0.35): string
    {
        $hex = ltrim($hex, '#');
        return implode('', array_map(fn($c) => sprintf('%02X', (int) round(255 - (255 - hexdec($c)) * $part)), str_split(substr($hex . '000000', 0, 6), 2)));
    }

    /** Fonctionnalité (module) d'une tâche du rapport. */
    /** Libellé d'une fonctionnalité dans le rapport : reformulation du pilote, sinon le nom importé du backlog. */
    public static function libelleModule(array $d, ?string $module): string
    {
        return (string) (($d['libelles_modules'] ?? [])[$module ?? ''] ?? $module);
    }

    public static function moduleTache(array $t): string
    {
        if (!empty($t['module'])) return $t['module'];
        return !empty($t['us_contexte']) ? explode(' › ', $t['us_contexte'])[0] : self::HORS_US;
    }

    /** Lignes d'un tableau limitées à une fonctionnalité (toutes si $module est vide). */
    public static function lignesModule(array $lignes, ?string $module, bool $taches): array
    {
        if (!$module) return $lignes;
        return array_filter($lignes, fn($l) => ($taches ? self::moduleTache($l) : ($l['module'] ?? '')) === $module);
    }

    /**
     * Tâches regroupées par fonctionnalité (ordre d'apparition, « Hors user story » en dernier).
     * Un seul groupe sans libellé quand le tableau est déjà filtré ou ne couvre qu'une fonctionnalité.
     */
    public static function groupesTaches(array $lignes, ?string $module): array
    {
        $lignes = array_values($lignes);
        if ($module) return ['' => $lignes];
        $g = [];
        foreach ($lignes as $t) $g[self::moduleTache($t)][] = $t;
        if (isset($g[self::HORS_US])) { $h = $g[self::HORS_US]; unset($g[self::HORS_US]); $g[self::HORS_US] = $h; }
        return count($g) > 1 ? $g : ['' => $lignes];
    }

    public const COULEURS = [
        1 => ['Noir', '1E293B'], 2 => ['Gris', '64748B'], 3 => ['Bleu foncé', '1F4E79'], 4 => ['Bleu', '2E74B5'],
        5 => ['Vert', '15803D'], 6 => ['Orange', 'C2410C'], 7 => ['Rouge', 'B91C1C'], 8 => ['Violet', '7E22CE'],
    ];

    private function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html, '<b><u><i><strong><em><ol><ul><li><br><div><p><blockquote><font>');
        // Seuls la taille (menu Taille) et la couleur de la palette (classe tc-N) sont conservées sur <font>
        $html = preg_replace_callback('/<font\b[^>]*>/i', function ($m) {
            $attr = preg_match('/\bsize=["\']?([1-7])\b/i', $m[0], $s) ? " size=\"{$s[1]}\"" : '';
            if (preg_match('/\bclass=["\'][^"\']*\btc-([1-8])\b/i', $m[0], $c)) $attr .= " class=\"tc-{$c[1]}\"";
            return "<font{$attr}>";
        }, $html);
        // Retire style, class… collés ; ne garde que les classes de l'éditeur (liste hiérarchique, interligne)
        $html = preg_replace('/<(b|u|i|strong|em|br)\b[^>]*>/i', '<$1>', $html);
        return preg_replace_callback('/<(ol|ul|li|div|p|blockquote)\b([^>]*)>/i', function ($m) {
            $tag = strtolower($m[1]);
            preg_match('/\bclass=["\']([^"\']*)["\']/i', $m[2], $c);
            $classes = array_filter(preg_split('/\s+/', $c[1] ?? ''), fn($k) =>
                ($k === 'hier' && $tag === 'ol') || in_array($k, self::INTERLIGNES, true));
            return $classes ? "<{$tag} class=\"" . implode(' ', $classes) . '">' : "<{$tag}>";
        }, $html);
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
        $this->ooxmlInterligne = 360;
        $this->ooxmlBlocs($racine, 0, $prefixe, $segs, $xml);
        $xml .= $this->ooxmlPara($segs, 0);

        return $xml ?: $this->p('(Non renseigné)');
    }

    private function ooxmlBlocs(\DOMNode $node, int $niveau, string $prefixe, array &$segs, string &$xml): void
    {
        foreach ($node->childNodes as $c) {
            $tag = $c instanceof \DOMElement ? strtolower($c->tagName) : null;
            if ($tag === null || in_array($tag, ['b', 'strong', 'i', 'em', 'u', 'span', 'a', 'font'])) {
                $this->ooxmlInline($c, [], $segs);
            } elseif ($tag === 'br') {
                $xml .= $this->ooxmlPara($segs, $niveau);
            } elseif (in_array($tag, ['ul', 'ol'])) {
                $xml .= $this->ooxmlPara($segs, $niveau);
                $xml .= $this->avecInterligne($c, fn() => $this->ooxmlListe($c, $niveau, false, '', $prefixe));
            } elseif ($tag === 'blockquote') {
                $xml .= $this->ooxmlPara($segs, $niveau);
                $xml .= $this->avecInterligne($c, function () use ($c, $niveau, $prefixe) {
                    $s = []; $x = '';
                    $this->ooxmlBlocs($c, $niveau + 1, $prefixe, $s, $x);
                    return $x . $this->ooxmlPara($s, $niveau + 1);
                });
            } else {
                $xml .= $this->ooxmlPara($segs, $niveau);
                if (in_array($tag, ['p', 'div']) && trim(str_replace("\xC2\xA0", ' ', $c->textContent)) === '') {
                    $xml .= '<w:p/>'; // ligne vide volontaire (bouton Espace / Entrée)
                    continue;
                }
                $xml .= $this->avecInterligne($c, function () use ($c, $niveau, $prefixe) {
                    $s = []; $x = '';
                    $this->ooxmlBlocs($c, $niveau, $prefixe, $s, $x);
                    return $x . $this->ooxmlPara($s, $niveau);
                });
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
            $xml .= $this->avecInterligne($c, function () use ($c, $n, $ordonnee, $hier, $prefixe, $chemin, $niveau) {
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
                return $this->ooxmlPara($segs, $niveau, $label) . $sousListes;
            });
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
        if ($tag === 'font' && ($taille = (int) $node->getAttribute('size'))) {
            // Proportionnel au texte : 9, 10, 12, 14, 16, 20, 24 pt pour un texte de 12 pt
            $ratio = [1 => 0.75, 2 => 10 / 12, 3 => 1, 4 => 14 / 12, 5 => 16 / 12, 6 => 20 / 12, 7 => 2][$taille] ?? 1;
            $fmt['sz'] = (int) round($this->mefSz('texte', 24) * $ratio);
        }
        if ($tag === 'font' && preg_match('/\btc-([1-8])\b/', $node->getAttribute('class'), $tc)) $fmt['color'] = self::COULEURS[(int) $tc[1]][1];
        foreach ($node->childNodes as $c) $this->ooxmlInline($c, $fmt, $segs);
    }

    /** Vide $segs dans un paragraphe ; avec $label, paragraphe de liste (puce ou numéro) */
    private function ooxmlPara(array &$segs, int $niveau, ?string $label = null): string
    {
        $texte = '';
        foreach ($segs as $s) $texte .= $s['text'];
        if ($label === null && trim($texte) === '') { $segs = []; return ''; }

        $rpr = fn(array $f) => '<w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            . '<w:sz w:val="' . ($f['sz'] ?? $this->mefSz('texte', 24)) . '"/><w:szCs w:val="' . ($f['sz'] ?? $this->mefSz('texte', 24)) . '"/>'
            . (!empty($f['b']) ? '<w:b/>' : '') . (!empty($f['u']) ? '<w:u w:val="single"/>' : '') . (!empty($f['i']) ? '<w:i/>' : '')
            . (!empty($f['color']) ? '<w:color w:val="' . $f['color'] . '"/>' : '')
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

        $pPr = '<w:jc w:val="both"/><w:spacing w:line="' . $this->ooxmlInterligne . '" w:lineRule="auto"/>';
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

    private function ecrireDocx(string $path, string $documentXml, ?string $logoPath = null, string $headerLine = '', string $footerLine = 'Confidentiel - Usage interne'): void
    {
        if (file_exists($path)) unlink($path);
        // Police de rendu choisie (Segoe UI par défaut) : remplace la police de base dans tout le document
        $police = htmlspecialchars(self::police($this->miseEnForme)[0], ENT_XML1, 'UTF-8');
        $documentXml = str_replace('Times New Roman', $police, $documentXml);
        // En-tête et pied de page réglés dans le document (sinon textes par défaut)
        $headerLine = $this->miseEnForme['entete'] ?? $headerLine;
        $footerLine = $this->miseEnForme['pied'] ?? $footerLine;
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);

        $imgContentType = '';
        if ($logoPath && file_exists($logoPath)) {
            $zip->addFile($logoPath, 'word/media/logo.png');
            $imgContentType = '<Default Extension="png" ContentType="image/png"/>';
        }

        // Header & Footer XML parts
        $zip->addFromString('word/header1.xml', str_replace('Times New Roman', $police, $this->genDocxHeaderXml($headerLine)));
        $zip->addFromString('word/footer1.xml', str_replace('Times New Roman', $police, $this->genDocxFooterXml($footerLine, $this->miseEnForme['num_page'] ?? true)));

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

        // styles.xml - Normal : Times New Roman 12pt, justifié, interligne 1.5
        $zip->addFromString('word/styles.xml',
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:docDefaults>'
            .   '<w:rPrDefault><w:rPr>'
            .     "<w:rFonts w:ascii=\"{$police}\" w:hAnsi=\"{$police}\" w:eastAsia=\"{$police}\" w:cs=\"{$police}\"/>"
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
            .     "<w:rFonts w:ascii=\"{$police}\" w:hAnsi=\"{$police}\" w:eastAsia=\"{$police}\" w:cs=\"{$police}\"/>"
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
