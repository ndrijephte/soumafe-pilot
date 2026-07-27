<?php
namespace App\Http\Controllers;

use App\Models\{Evenement, Projet, Sprint, Tache, User};
use App\Services\GenerateurXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

        try {
            $xlsx = new GenerateurXlsx('Collaborateurs');
            $xlsx->entetes(['Nom', 'Prénom', 'Email', 'Rôle', 'Profil', 'Pôle', 'Actif']);

            $users = User::orderBy('name')->get();
            foreach ($users as $u) {
                $poleName = '—';
                if (Schema::hasColumn('users', 'pole_id') && $u->pole_id) {
                    $poleName = $u->pole->libelle ?? '—';
                }
                $xlsx->ligne([
                    $u->name ?? '',
                    $u->prenom ?? '',
                    $u->email ?? '',
                    ucfirst(str_replace('_', ' ', $u->role ?? '')),
                    ucfirst($u->profil_mesure ?? ''),
                    $poleName,
                    $u->actif ? 'Oui' : 'Non',
                ]);
            }
            return $xlsx->telecharger('collaborateurs_soumafe_' . date('Ymd') . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export collaborateurs : ' . $e->getMessage());
        }
    }

    /** Export tâches avec filtres. */
    public function taches(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        try {
            $query = Tache::with(['user', 'projet', 'sprint']);

            if ($r->filled('projet_id')) $query->where('projet_id', $r->projet_id);
            if ($r->filled('sprint_id')) $query->where('sprint_id', $r->sprint_id);
            if ($r->filled('user_id')) $query->where('user_id', $r->user_id);
            if ($r->filled('statut')) $query->where('statut', $r->statut);
            if ($r->filled('date_debut')) $query->whereDate('date_debut', '>=', $r->date_debut);
            if ($r->filled('date_fin')) {
                $query->where(function ($q) use ($r) {
                    $q->whereDate('echeance', '<=', $r->date_fin)
                      ->orWhereDate('date_fin_reelle', '<=', $r->date_fin);
                });
            }

            $taches = $query->orderByDesc('date_debut')->get();

            $xlsx = new GenerateurXlsx('Tâches');
            $xlsx->entetes(['Date', 'Collaborateur', 'Projet', 'Sprint', 'Intitulé', 'Type', 'Statut', 'Priorité', 'Échéance', 'Date fin', 'Points']);

            foreach ($taches as $t) {
                $xlsx->ligne([
                    $t->date_debut ? $t->date_debut->format('d/m/Y') : '',
                    $t->user ? $t->user->nomComplet() : '—',
                    $t->projet ? $t->projet->libelle : '—',
                    $t->sprint ? 'Sprint ' . $t->sprint->numero : '—',
                    $t->intitule ?? '',
                    $t->type ?? '',
                    ucfirst(str_replace('_', ' ', $t->statut ?? '')),
                    ucfirst($t->priorite ?? ''),
                    $t->echeance ? $t->echeance->format('d/m/Y') : '',
                    $t->date_fin_reelle ? $t->date_fin_reelle->format('d/m/Y') : '',
                    $t->points !== null ? (string) $t->points : '',
                ]);
            }
            return $xlsx->telecharger('taches_soumafe_' . date('Ymd') . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export tâches : ' . $e->getMessage());
        }
    }

    /** Export sprint complet. */
    public function sprint(Request $r, Sprint $sprint)
    {
        abort_unless($r->user()->estPilote(), 403);

        try {
            $sprint->load(['projet', 'taches.user']);

            $projetLibelle = $sprint->projet ? $sprint->projet->libelle : 'Projet';
            $projetCode = $sprint->projet ? ($sprint->projet->code ?? 'PROJ') : 'PROJ';

            $xlsx = new GenerateurXlsx('Sprint ' . $sprint->numero);

            $xlsx->entetes(['Champ', 'Valeur']);
            $xlsx->ligne(['Projet', $projetLibelle]);
            $xlsx->ligne(['Sprint', (string) $sprint->numero]);
            $xlsx->ligne(['Objectif', $sprint->objectif ?? '—']);
            $xlsx->ligne(['Début', $sprint->date_debut ? $sprint->date_debut->format('d/m/Y') : '—']);
            $xlsx->ligne(['Fin', $sprint->date_fin ? $sprint->date_fin->format('d/m/Y') : '—']);
            $xlsx->ligne(['Engagement', ($sprint->engagement_points ?? 0) . ' pts']);
            $xlsx->ligne(['Livrés', $sprint->pointsLivres() . ' pts']);
            $xlsx->ligne(['Statut', ucfirst(str_replace('_', ' ', $sprint->statut ?? ''))]);
            $xlsx->ligne(['', '']);
            $xlsx->ligne(['--- TÂCHES ---', '']);
            $xlsx->ligne(['', '']);
            $xlsx->ligne(['Collaborateur', 'Intitulé', 'Type', 'Statut', 'Priorité', 'Début', 'Échéance', 'Fin réelle', 'Points']);

            foreach ($sprint->taches as $t) {
                $xlsx->ligne([
                    $t->user ? $t->user->nomComplet() : '—',
                    $t->intitule ?? '',
                    $t->type ?? '',
                    ucfirst(str_replace('_', ' ', $t->statut ?? '')),
                    ucfirst($t->priorite ?? ''),
                    $t->date_debut ? $t->date_debut->format('d/m/Y') : '',
                    $t->echeance ? $t->echeance->format('d/m/Y') : '',
                    $t->date_fin_reelle ? $t->date_fin_reelle->format('d/m/Y') : '',
                    $t->points !== null ? (string) $t->points : '',
                ]);
            }

            $nom = 'sprint_' . $projetCode . '_S' . $sprint->numero . '_' . date('Ymd');
            return $xlsx->telecharger($nom . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export sprint : ' . $e->getMessage());
        }
    }

    /** Export événements. */
    public function evenements(Request $r)
    {
        abort_unless($r->user()->estPilote(), 403);

        try {
            if (!Schema::hasTable('evenements')) {
                return back()->with('erreur', 'La table événements n\'existe pas encore. Lancez les migrations.');
            }

            $query = Evenement::with('user');

            if ($r->filled('date_debut')) $query->whereDate('date', '>=', $r->date_debut);
            if ($r->filled('date_fin')) $query->whereDate('date', '<=', $r->date_fin);
            if ($r->filled('type')) $query->where('type', $r->type);

            $xlsx = new GenerateurXlsx('Événements');
            $xlsx->entetes(['Date', 'Collaborateur', 'Type', 'Description', 'Action engagée', 'Impact']);

            foreach ($query->orderByDesc('date')->get() as $e) {
                $xlsx->ligne([
                    $e->date ? $e->date->format('d/m/Y') : '',
                    $e->user ? $e->user->nomComplet() : '—',
                    $e->labelType(),
                    $e->description ?? '',
                    $e->action_engagee ?? '',
                    $e->impact ?? '',
                ]);
            }
            return $xlsx->telecharger('evenements_soumafe_' . date('Ymd') . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export événements : ' . $e->getMessage());
        }
    }
}
