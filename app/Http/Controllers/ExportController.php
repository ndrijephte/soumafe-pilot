<?php
namespace App\Http\Controllers;

use App\Models\{Evenement, Projet, Sprint, Tache, User};
use App\Services\GenerateurXlsx;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    /** Page d'export avec filtres. */
    public function index(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        return view('exports.index', [
            'projets' => Projet::orderBy('libelle')->get(),
            'sprints' => Sprint::with('projet')->orderByDesc('date_debut')->get(),
            'collaborateurs' => User::where('actif', true)->orderBy('name')->get(),
        ]);
    }

    /** Export collaborateurs. */
    public function collaborateurs(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $xlsx = new GenerateurXlsx('Collaborateurs');
        $xlsx->entetes(['Nom', 'Prénom', 'Email', 'Rôle', 'Profil', 'Pôle', 'Actif']);

        User::with('pole')->orderBy('name')->each(function ($u) use ($xlsx) {
            $xlsx->ligne([
                $u->name, $u->prenom, $u->email,
                ucfirst(str_replace('_', ' ', $u->role)),
                ucfirst($u->profil_mesure ?? ''),
                $u->pole->libelle ?? '—',
                $u->actif ? 'Oui' : 'Non',
            ]);
        });
        return $xlsx->telecharger('collaborateurs_soumafe_' . date('Ymd') . '.xlsx');
    }

    /** Export tâches avec filtres (projet, sprint, période, collaborateur, statut). */
    public function taches(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        $query = Tache::with(['user', 'projet', 'sprint']);

        if ($r->filled('projet_id')) $query->where('projet_id', $r->projet_id);
        if ($r->filled('sprint_id')) $query->where('sprint_id', $r->sprint_id);
        if ($r->filled('user_id')) $query->where('user_id', $r->user_id);
        if ($r->filled('statut')) $query->where('statut', $r->statut);
        if ($r->filled('date_debut')) $query->whereDate('date_debut', '>=', $r->date_debut);
        if ($r->filled('date_fin')) $query->where(function ($q) use ($r) {
            $q->whereDate('echeance', '<=', $r->date_fin)
              ->orWhereDate('date_fin_reelle', '<=', $r->date_fin);
        });

        $taches = $query->orderByDesc('date_debut')->get();

        $xlsx = new GenerateurXlsx('Tâches');
        $xlsx->entetes(['Date', 'Collaborateur', 'Projet', 'Sprint', 'Intitulé', 'Type', 'Statut', 'Priorité', 'Échéance', 'Date fin', 'Points']);

        foreach ($taches as $t) {
            $xlsx->ligne([
                $t->date_debut?->format('d/m/Y') ?? '',
                $t->user?->nomComplet() ?? '—',
                $t->projet?->libelle ?? '—',
                $t->sprint ? 'Sprint ' . $t->sprint->numero : '—',
                $t->intitule,
                $t->type,
                ucfirst(str_replace('_', ' ', $t->statut)),
                ucfirst($t->priorite ?? ''),
                $t->echeance?->format('d/m/Y') ?? '',
                $t->date_fin_reelle?->format('d/m/Y') ?? '',
                $t->points ?? '',
            ]);
        }
        return $xlsx->telecharger('taches_soumafe_' . date('Ymd') . '.xlsx');
    }

    /** Export sprint complet (fiche sprint + tâches). */
    public function sprint(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->estPilote(), 403);
        $sprint->load(['projet', 'taches.user']);

        $xlsx = new GenerateurXlsx('Sprint ' . $sprint->numero);

        // Feuille résumé (première ligne = méta)
        $xlsx->entetes(['Champ', 'Valeur']);
        $xlsx->ligne(['Projet', $sprint->projet->libelle]);
        $xlsx->ligne(['Sprint', $sprint->numero]);
        $xlsx->ligne(['Objectif', $sprint->objectif ?? '—']);
        $xlsx->ligne(['Début', $sprint->date_debut?->format('d/m/Y') ?? '—']);
        $xlsx->ligne(['Fin', $sprint->date_fin?->format('d/m/Y') ?? '—']);
        $xlsx->ligne(['Engagement', $sprint->engagement_points . ' pts']);
        $xlsx->ligne(['Livrés', $sprint->pointsLivres() . ' pts']);
        $xlsx->ligne(['Statut', ucfirst(str_replace('_', ' ', $sprint->statut))]);
        $xlsx->ligne(['', '']); // séparateur
        $xlsx->ligne(['--- TÂCHES ---', '']);
        $xlsx->ligne(['', '']);

        // En-têtes tâches (on réutilise la même feuille)
        $xlsx->ligne(['Collaborateur', 'Intitulé', 'Type', 'Statut', 'Priorité', 'Début', 'Échéance', 'Fin réelle', 'Points']);
        foreach ($sprint->taches as $t) {
            $xlsx->ligne([
                $t->user?->nomComplet() ?? '—',
                $t->intitule,
                $t->type,
                ucfirst(str_replace('_', ' ', $t->statut)),
                ucfirst($t->priorite ?? ''),
                $t->date_debut?->format('d/m/Y') ?? '',
                $t->echeance?->format('d/m/Y') ?? '',
                $t->date_fin_reelle?->format('d/m/Y') ?? '',
                $t->points ?? '',
            ]);
        }

        $nom = 'sprint_' . $sprint->projet->code . '_S' . $sprint->numero . '_' . date('Ymd');
        return $xlsx->telecharger($nom . '.xlsx');
    }

    /** Export événements / faits marquants. */
    public function evenements(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);
        $query = Evenement::with('user');

        if ($r->filled('date_debut')) $query->whereDate('date', '>=', $r->date_debut);
        if ($r->filled('date_fin')) $query->whereDate('date', '<=', $r->date_fin);
        if ($r->filled('type')) $query->where('type', $r->type);

        $xlsx = new GenerateurXlsx('Événements');
        $xlsx->entetes(['Date', 'Collaborateur', 'Type', 'Description', 'Impact']);

        foreach ($query->orderByDesc('date')->get() as $e) {
            $xlsx->ligne([
                $e->date->format('d/m/Y'),
                $e->user?->nomComplet() ?? '—',
                $e->labelType(),
                $e->description,
                $e->impact ?? '',
            ]);
        }
        return $xlsx->telecharger('evenements_soumafe_' . date('Ymd') . '.xlsx');
    }
}
