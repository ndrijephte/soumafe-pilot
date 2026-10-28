<?php
namespace App\Services;

use App\Models\{CompteRendu, DailyScrum, Projet, Sprint, User, UserStory};
use App\Services\ClasseurXlsx as X;

/**
 * Archive Excel d'un sprint, structure du classeur de référence LEVRY :
 * Product Backlog, Sprint N Backlog, Sprint Planning N, DAILY 1…n, REVIEW SPRINT, RETROSPECTIVE.
 */
class ArchiveScrum
{
    private X $c;

    public function __construct() { $this->c = new X(); }

    public static function sprint(Sprint $sprint): X
    {
        $sprint->load(['projet', 'taches.user', 'taches.userStory', 'userStories', 'dailyScrums.entrees.user']);
        $a = new self();
        $a->productBacklog($sprint->projet);
        $a->sprintBacklog($sprint);
        $a->planning($sprint, false);
        // Brouillons exclus, sauf pour le scribe du jour et les pilotes
        foreach ($sprint->dailyScrums as $d) if (!auth()->user() || $d->visiblePour(auth()->user())) $a->daily($sprint, $d);
        $a->planning($sprint, true);
        $a->retrospective($sprint);
        return $a->c;
    }

    public static function backlog(Projet $projet): X
    {
        $a = new self();
        $a->productBacklog($projet);
        return $a->c;
    }

    /** Modèle vierge d'import du Product Backlog. */
    public static function modeleImport(): X
    {
        $a = new self();
        $f = $a->c->feuille('Product Backlog')->largeurs([12, 30, 65, 60]);
        $f->ligne(['NOM DU PROJET'], X::TITRE)->fusionner(0, 3);
        $f->ligne(['PRODUCT BACKLOG'], X::SOUS_TITRE)->vide();
        $f->ligne(['Une ligne par user story. Le N° est facultatif (numérotation automatique) ; « US 2.3 - En tant que … » dans la colonne User Story est aussi reconnu. Les tâches techniques et les story points se renseignent ensuite au Sprint Planning.'])->fusionner(0, 3)->hauteur(32)->vide();
        $f->ligne(['N°', 'Module / Fonctionnalité', 'User Story', 'Exigences fonctionnelles'], X::ENTETE);
        $f->ligne(['US 1.1', 'Authentification', "En tant qu'utilisateur, je veux me connecter afin d'accéder à mon espace.", "• Formulaire email / mot de passe\n• Message d'erreur explicite"], X::CELLULE);
        $f->ligne(['US 1.2', 'Authentification', "En tant qu'utilisateur, je veux réinitialiser mon mot de passe.", '• Lien « mot de passe oublié »'], X::CELLULE);
        return $a->c;
    }

    /* ─── Mise en page ─── */

    /**
     * Fiche d'identité du sprint : une ligne d'intitulés (bleu) au-dessus d'une ligne de valeurs.
     * $cases : [[intitulé, valeur, colonne début, colonne fin], …], alignées sur les colonnes du tableau.
     */
    private function fiche(FeuilleXlsx $f, array $largeurs, array $cases): void
    {
        $nb = max(array_column($cases, 3)) + 1;
        $tete = array_fill(0, $nb, ['v' => '', 's' => X::ENTETE]);
        $vals = array_fill(0, $nb, ['v' => '', 's' => X::CENTRE]);
        $lignes = 1;
        foreach ($cases as [$lib, $val, $de, $a]) {
            $tete[$de]['v'] = $lib;
            $style = $de === 0 ? X::CELLULE : X::CENTRE; // l'objectif (1re case) reste aligné à gauche
            for ($i = $de; $i <= $a; $i++) $vals[$i] = ['v' => '', 's' => $style];
            $vals[$de]['v'] = $val;
            // Les cellules fusionnées ne s'ajustent pas seules dans Excel : hauteur estimée
            $largeur = array_sum(array_slice($largeurs, $de, $a - $de + 1)) ?: 10;
            $lignes = max($lignes, (int) ceil(mb_strlen((string) $val) * 1.1 / $largeur));
        }
        $f->ligne($tete)->hauteur(30);
        foreach ($cases as [, , $de, $a]) if ($a > $de) $f->fusionner($de, $a);
        $f->ligne($vals)->hauteur(max(22, $lignes * 15 + 6));
        foreach ($cases as [, , $de, $a]) if ($a > $de) $f->fusionner($de, $a);
        $f->vide();
    }

    private static function hauteurTexte(string $texte, int $largeur): float
    {
        $lignes = 0;
        foreach (explode("\n", TexteRiche::texte($texte)) as $l) $lignes += max(1, (int) ceil(mb_strlen($l) * 1.1 / max(10, $largeur)));
        return max(20, $lignes * 15 + 6);
    }

    private static function statutSprint(Sprint $s): string
    {
        return ['planifie' => 'Planifié', 'en_cours' => 'En cours', 'termine' => 'Terminé'][$s->statut] ?? ucfirst((string) $s->statut);
    }

    /* ─── Onglets ─── */

    private function productBacklog(Projet $projet): void
    {
        $f = $this->c->feuille('Product Backlog')->largeurs([10, 30, 65, 60]);
        $f->ligne([mb_strtoupper($projet->libelle)], X::TITRE)->fusionner(0, 3);
        $f->ligne(['PRODUCT BACKLOG'], X::SOUS_TITRE)->vide();
        $f->ligne(['Responsable : ' . ($projet->responsable?->nomComplet() ?? '—') . '   ·   Édité le ' . now()->format('d/m/Y H:i')])->fusionner(0, 3)->vide();
        $f->ligne(['N°', 'Module / Fonctionnalité', 'User Story', 'Exigences fonctionnelles'], X::ENTETE);
        $stories = UserStory::trierParNumero($projet->userStories()->get());
        foreach ($stories->groupBy(fn($s) => $s->module ?: 'Sans module') as $module => $groupe) {
            $debut = $f->numero();
            foreach ($groupe->values() as $i => $us) {
                $f->ligne([$us->numero, $i === 0 ? $module : '', $us->intitule, $us->exigences], X::CELLULE);
            }
            if ($groupe->count() > 1) $f->fusion('B' . $debut . ':B' . ($f->numero() - 1));
        }
        if ($stories->isEmpty()) $f->ligne(['Backlog vide.'])->fusionner(0, 3);
    }

    private function sprintBacklog(Sprint $sprint): void
    {
        $l = [10, 26, 60, 50, 50];
        $f = $this->c->feuille("Sprint {$sprint->numero} Backlog")->largeurs($l);
        $f->ligne([mb_strtoupper($sprint->projet->libelle)], X::TITRE)->fusionner(0, 4);
        $f->ligne(["SPRINT {$sprint->numero} BACKLOG"], X::SOUS_TITRE)->vide();
        $this->fiche($f, $l, [
            ['Objectif du sprint', $sprint->objectif, 0, 2],
            ['Dates et durée', 'Du ' . $sprint->date_debut->format('d/m/Y') . ' au ' . $sprint->date_fin->format('d/m/Y') . ' - ' . $sprint->dureeJours() . ' jours ouvrés', 3, 3],
            ['Story points engagés', $sprint->pointsEngages() . ' pts - ' . $sprint->userStories->count() . ' user story(s)', 4, 4],
        ]);
        $f->ligne(['N°', 'Module', 'User Story', 'Exigences fonctionnelles', 'Tâches techniques'], X::ENTETE);
        foreach (UserStory::trierParNumero($sprint->userStories) as $us) {
            $f->ligne([$us->numero, $us->module, $us->intitule, $us->exigences, $us->taches_techniques], X::CELLULE);
        }
        if ($sprint->userStories->isEmpty()) $f->ligne(['Aucune user story rattachée au sprint.'])->fusionner(0, 4);
    }

    /** Sprint Planning (ou Review : même tableau + statut de réalisation et observation). */
    private function planning(Sprint $sprint, bool $review): void
    {
        $cr = $review ? CompteRendu::where('sprint_id', $sprint->id)->where('type', 'review_sprint')->first() : null;
        $d = $cr ? $cr->getDonnees() : [];
        $obs = $d['observations'] ?? [];
        $nbCol = $review ? 10 : 9;
        $l = $review ? [8, 50, 42, 22, 13, 13, 15, 10, 16, 42] : [8, 50, 42, 22, 13, 13, 15, 10, 22];
        $f = $this->c->feuille($review ? 'REVIEW SPRINT' : "Sprint Planning {$sprint->numero}")->largeurs($l);

        $f->ligne([mb_strtoupper($sprint->projet->libelle)], X::TITRE)->fusionner(0, $nbCol - 1);
        $f->ligne([($review ? 'REVIEW - SPRINT ' : 'SPRINT PLANNING ') . $sprint->numero], X::SOUS_TITRE)->fusionner(0, $nbCol - 1)->vide();

        // Fiche du sprint, alignée sur les colonnes du tableau
        $cases = [
            ['Objectif du sprint', $sprint->objectif, 0, 3],
            ['Date de début', $sprint->date_debut->format('d/m/Y'), 4, 4],
            ['Date de fin', $sprint->date_fin->format('d/m/Y'), 5, 5],
            ['Durée', $sprint->dureeJours() . ' jours ouvrés', 6, 6],
            ['Story points', $sprint->pointsEngages(), 7, 7],
            ['Statut du sprint', self::statutSprint($sprint), 8, 8],
        ];
        if ($review) {
            $ind = ScrumService::indicateurs($sprint);
            $cases[] = ['Réalisation', "{$ind['taux']} % des tâches · {$ind['points_faits']}/{$ind['points']} pts · {$ind['stories_terminees']}/{$ind['stories']} user stories", 9, 9];
        }
        $this->fiche($f, $l, $cases);

        if ($review && $cr) {
            $tenue = collect([$cr->date_reunion?->format('d/m/Y'),
                $cr->heure_debut ? substr($cr->heure_debut, 0, 5) . ($cr->heure_fin ? ' → ' . substr($cr->heure_fin, 0, 5) : '') : null, $cr->lieu])->filter()->join(' · ');
            if ($tenue) $f->ligne(['Review tenue le', '', $tenue], [0 => X::LIBELLE])->fusionner(0, 1)->fusionner(2, $nbCol - 1);
            if (!empty($d['synthese'])) {
                $f->ligne(['Synthèse de la démonstration', '', $d['synthese']], [0 => X::LIBELLE, 2 => X::CELLULE])->fusionner(0, 1)->fusionner(2, $nbCol - 1)
                    ->hauteur(self::hauteurTexte($d['synthese'], array_sum(array_slice($l, 2))));
            }
            if ($tenue || !empty($d['synthese'])) $f->vide();
        }
        $f->ligne(['Statuts : D (Démarré), E (En cours), T (Terminé), TT (Terminé Testé), ER (En retard de n jours)'])->fusionner(0, $nbCol - 1);
        $f->ligne(['Story points : 1-2 Très simple (story claire, sans dépendance, < 1 jour) · 3-5 Modérée (quelques inconnus, 1 à 3 jours) · 8-13 Complexe (dépendance technique, à décomposer si possible)'])->fusionner(0, $nbCol - 1)->vide();

        $entetes = ['N°', 'Tâches', 'User Story', 'Attribution', 'Date de début', 'Date de fin', 'Durée (jrs)', 'Points', 'Statut'];
        if ($review) $entetes[] = 'Observation';
        $f->ligne($entetes, X::ENTETE);

        $m = 0;
        foreach (ScrumService::planning($sprint) as $bloc) {
            $m++;
            $f->ligne(array_pad(["MODULE {$m} : {$bloc['module']}"], $nbCol, ''), X::MODULE)->fusionner(0, $nbCol - 1);
            foreach ($bloc['stories'] as $g) {
                $us = $g['story'];
                $taches = $g['taches'];
                if ($us) {
                    $debut = $taches->pluck('date_debut')->filter()->min();
                    $fin = $taches->pluck('echeance')->filter()->max();
                    $ligne = [$us->numero, $us->module ?: $us->numero, $us->intitule, $taches->map(fn($t) => $t->user?->nomComplet())->filter()->unique()->join(', '),
                        $debut?->format('d/m/Y'), $fin?->format('d/m/Y'), $debut && $fin ? \App\Models\Tache::joursOuvres($debut, $fin) : '',
                        $us->effort_estime ?: '',
                        UserStory::STATUTS_SPRINT[$us->pivot->statut_sprint ?? ''] ?? $us->statutLibelle()];
                    if ($review) $ligne[] = $obs['us_' . $us->id] ?? '';
                    $f->ligne($ligne, X::STORY);
                }
                foreach ($taches->values() as $i => $t) {
                    $code = $t->codeScrum();
                    if ($review) {
                        [$lib] = ScrumService::statutReview($t);
                        $statut = ['v' => $lib, 's' => $t->statut === 'termine' ? X::OK : ($code['code'] === 'ER' ? X::ALERTE : X::CENTRE)];
                    } else {
                        $statut = ['v' => $code['code'] === '—' ? $code['libelle'] : $code['code'] . ($code['code'] === 'ER' ? ' (' . $code['libelle'] . ')' : ''), 's' => $code['code'] === 'ER' ? X::ALERTE : X::CENTRE];
                    }
                    $ligne = [($us ? preg_replace('/^US\s*/i', '', $us->numero) . '.' : '') . ($i + 1), $t->intitule, '', $t->user?->nomComplet(),
                        ['v' => $t->date_debut?->format('d/m/Y'), 's' => X::CENTRE], ['v' => $t->echeance?->format('d/m/Y'), 's' => X::CENTRE],
                        ['v' => $t->date_debut && $t->echeance ? $t->dureeJours() : '', 's' => X::CENTRE], ['v' => '', 's' => X::CENTRE], $statut];
                    if ($review) $ligne[] = $obs[$t->id] ?? '';
                    $f->ligne($ligne, X::CELLULE);
                }
            }
        }
        if ($sprint->taches->isEmpty()) {
            $f->ligne(['Aucune tâche planifiée.'])->fusionner(0, $nbCol - 1);
        } else {
            $total = array_fill(0, $nbCol, ['v' => '', 's' => X::STORY]);
            $total[0]['v'] = $review ? 'Total story points livrés / engagés' : 'Total story points engagés';
            $total[7]['v'] = $review ? $sprint->pointsLivres() . ' / ' . $sprint->pointsEngages() : $sprint->pointsEngages();
            $f->ligne($total)->fusionner(0, 6);
        }

        if ($review && !empty($d['decisions'])) {
            $f->vide()->ligne(['Décisions / suite à donner'], X::LIBELLE)->fusionner(0, $nbCol - 1);
            $f->ligne([$d['decisions']], X::CELLULE)->fusionner(0, $nbCol - 1)->hauteur(self::hauteurTexte($d['decisions'], array_sum($l)));
        }

        // La Review alimente le Product Backlog : nouvelles user stories identifiées
        if ($review && !empty($d['backlog'])) {
            $creees = UserStory::whereIn('id', collect($d['backlog'])->pluck('user_story_id')->filter())->pluck('numero', 'id');
            $f->vide()->ligne(['Ajustements du Product Backlog'], X::LIBELLE)->fusionner(0, $nbCol - 1);
            $f->ligne(['N°', 'Module', '', 'User Story', '', '', '', '', 'Exigences fonctionnelles', ''], X::ENTETE)->fusionner(1, 2)->fusionner(3, 7)->fusionner(8, $nbCol - 1);
            foreach ($d['backlog'] as $bk) {
                $num = !empty($bk['user_story_id']) ? ($creees[$bk['user_story_id']] ?? 'Supprimée') : 'En attente';
                $f->ligne([$num, $bk['module'] ?? '', '', $bk['intitule'] ?? '', '', '', '', '', $bk['exigences'] ?? '', ''], X::CELLULE)
                    ->fusionner(1, 2)->fusionner(3, 7)->fusionner(8, $nbCol - 1)
                    ->hauteur(max(self::hauteurTexte($bk['exigences'] ?? '', 58), self::hauteurTexte($bk['intitule'] ?? '', 90)));
            }
        }
    }

    private function daily(Sprint $sprint, DailyScrum $d): void
    {
        $f = $this->c->feuille($d->libelle())->largeurs([3, 3, 28, 48, 48, 44]);
        $users = User::whereIn('id', array_filter($d->roles ?? []))->get()->keyBy('id');
        $nom = fn($role) => ($id = $d->roleUserId($role)) && isset($users[$id]) ? $users[$id]->nomComplet() : '';
        [$statut] = $d->statutPublication();

        $f->ligne(['', '', 'DAILY SCRUM - JOUR ' . $d->numero_jour], [2 => X::TITRE])->fusionner(2, 5)->hauteur(24)->vide();
        $f->ligne(['', '', 'Objet', mb_strtoupper($sprint->projet->libelle)], [2 => X::FICHE_LIB, 3 => X::PROJET])->fusionner(3, 5)->hauteur(20);
        $f->ligne(['', '', 'Objectif du sprint', $sprint->objectif], [2 => X::FICHE_LIB, 3 => X::FICHE_VAL])->fusionner(3, 5)->hauteur(self::hauteurTexte((string) $sprint->objectif, 140))->vide();
        $f->ligne(['', '', 'Date', $d->date_reunion->translatedFormat('l d F Y'), 'Facilitateur', $nom('facilitateur')], [2 => X::FICHE_LIB, 3 => X::FICHE_VAL, 4 => X::FICHE_LIB, 5 => X::FICHE_VAL]);
        $f->ligne(['', '', 'Horaire', ($d->heure_debut ? substr($d->heure_debut, 0, 5) : '') . ($d->heure_fin ? ' → ' . substr($d->heure_fin, 0, 5) : ''), 'Scribe (rédacteur)', $nom('scribe')], [2 => X::FICHE_LIB, 3 => X::FICHE_VAL, 4 => X::FICHE_LIB, 5 => X::FICHE_VAL]);
        $f->ligne(['', '', 'Statut', $statut . ($d->estPublie() ? ' le ' . $d->publie_le->format('d/m/Y') : ''), 'Gardien du temps', $nom('gardien_temps')], [2 => X::FICHE_LIB, 3 => X::FICHE_VAL, 4 => X::FICHE_LIB, 5 => X::FICHE_VAL]);
        $f->ligne(['', '', '', '', 'Observateur', $nom('observateur')], [2 => X::FICHE_LIB, 3 => X::FICHE_VAL, 4 => X::FICHE_LIB, 5 => X::FICHE_VAL])->vide();
        $f->ligne(['', '', 'Participant', 'Réalisé (tâches précédentes / hier)', 'À faire (détail des tâches de ce jour)', 'Défis (blocages ou observations)'], [2 => X::ENTETE, 3 => X::ENTETE, 4 => X::ENTETE, 5 => X::ENTETE])->hauteur(26);
        foreach ($d->entrees->sortBy(fn($e) => $e->user?->name)->values() as $i => $e) {
            $s = $i % 2 ? X::CELLULE_ALT : X::CELLULE;
            $f->ligne(['', '', $e->user?->nomComplet(), $e->taches_hier, $e->taches_jour, $e->defis], [2 => X::STORY, 3 => $s, 4 => $s, 5 => $s]);
        }
        $f->vide();
        $f->ligne(['', '', 'Commentaire général', $d->commentaire_general], [2 => X::FICHE_LIB, 3 => X::FICHE_VAL])->fusionner(3, 5)->hauteur(self::hauteurTexte((string) $d->commentaire_general, 140));
        $f->ligne(['', '', 'Observation', $d->observation], [2 => X::FICHE_LIB, 3 => X::FICHE_VAL])->fusionner(3, 5)->hauteur(self::hauteurTexte((string) $d->observation, 140));
    }

    private function retrospective(Sprint $sprint): void
    {
        $l = [24, 40, 40, 36, 40];
        $f = $this->c->feuille('RETROSPECTIVE')->largeurs($l);
        $cr = CompteRendu::where('sprint_id', $sprint->id)->where('type', 'retrospective')->first();
        $f->ligne([mb_strtoupper($sprint->projet->libelle)], X::TITRE)->fusionner(0, 4);
        $f->ligne(['RÉTROSPECTIVE - SPRINT ' . $sprint->numero], X::SOUS_TITRE)->fusionner(0, 4)->vide();
        $this->fiche($f, $l, [
            ['Objectif du sprint', $sprint->objectif, 0, 1],
            ['Date de début', $sprint->date_debut->format('d/m/Y'), 2, 2],
            ['Date de fin', $sprint->date_fin->format('d/m/Y'), 3, 3],
            ['Durée', $sprint->dureeJours() . ' jours ouvrés', 4, 4],
        ]);
        if ($cr?->date_reunion) {
            $tenue = $cr->date_reunion->format('d/m/Y') . ($cr->heure_debut ? ' · ' . substr($cr->heure_debut, 0, 5) . ($cr->heure_fin ? ' → ' . substr($cr->heure_fin, 0, 5) : '') : '');
            $f->ligne(['Rétrospective tenue le', $tenue], [0 => X::LIBELLE])->fusionner(1, 4)->vide();
        }
        $f->ligne(['Thème', 'Ce qui a bien marché', 'Ce qui n’a pas marché', 'Causes', 'Actions à mettre en place'], X::ENTETE);
        $lignes = $cr ? ($cr->getDonnees()['lignes'] ?? []) : [];
        foreach ($lignes as $li) {
            $f->ligne([['v' => $li['theme'] ?? '', 's' => X::STORY], $li['bien'] ?? '', $li['pas_bien'] ?? '', $li['causes'] ?? '', $li['actions'] ?? ''], X::CELLULE);
        }
        if (!$lignes) $f->ligne(['Rétrospective non renseignée.'])->fusionner(0, 4);
    }
}
