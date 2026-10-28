<?php
namespace App\Http\Controllers;

use App\Models\{Chauffeur, Evenement, MaintenancePreventive, Panne, Penalite, Projet, RecetteVehicule, Sprint, Tache, User, Vehicule};
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
            $xlsx->ligne(['Engagement', $sprint->pointsEngages() . ' pts']);
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

    // ═══════════════════════════════════════════════════════
    //  PARC AUTOMOBILE
    // ═══════════════════════════════════════════════════════

    /** Export véhicules. */
    public function vehicules(Request $r)
    {
        abort_unless($r->user()->peutGererParc(), 403);
        if (!Schema::hasTable('vehicules')) return back()->with('erreur', 'Table véhicules absente.');

        try {
            $query = Vehicule::with(['chauffeurs' => fn($q) => $q->wherePivot('actif', true)]);
            if ($r->filled('categorie') && $r->categorie !== 'tous') $query->where('categorie', $r->categorie);
            if ($r->filled('statut')) $query->where('statut', $r->statut);

            $xlsx = new GenerateurXlsx('Véhicules');
            $xlsx->entetes(['Immatriculation', 'Catégorie', 'Marque', 'Modèle', 'Statut', 'Km', 'Mise en service', 'Obj. recette', 'Chauffeur(s)', 'Observations']);

            foreach ($query->orderBy('immatriculation')->get() as $v) {
                $chauffeurs = $v->chauffeurs->map(fn($c) => $c->nomComplet())->join(', ');
                $xlsx->ligne([
                    $v->immatriculation,
                    $v->categorieLabel(),
                    $v->marque ?? '',
                    $v->modele ?? '',
                    $v->statutLabel(),
                    $v->kilometrage ?? '',
                    $v->date_mise_en_service ? $v->date_mise_en_service->format('d/m/Y') : '',
                    $v->objectif_recette ?? '',
                    $chauffeurs ?: '—',
                    $v->observations ?? '',
                ]);
            }
            return $xlsx->telecharger('vehicules_soumafe_' . date('Ymd') . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export véhicules : ' . $e->getMessage());
        }
    }

    /** Export recettes. */
    public function recettes(Request $r)
    {
        abort_unless($r->user()->peutGererParc(), 403);
        if (!Schema::hasTable('recettes_vehicules')) return back()->with('erreur', 'Table recettes absente.');

        try {
            $query = RecetteVehicule::with('vehicule');
            if ($r->filled('date_debut')) $query->whereDate('date', '>=', $r->date_debut);
            if ($r->filled('date_fin')) $query->whereDate('date', '<=', $r->date_fin);
            if ($r->filled('vehicule_id')) $query->where('vehicule_id', $r->vehicule_id);

            $xlsx = new GenerateurXlsx('Recettes');
            $xlsx->entetes(['Date', 'Véhicule', 'Chauffeur', 'Type jour', 'Recette', 'Objectif', 'Écart', 'Mode remise', 'Contrôlé par', 'Observations']);

            foreach ($query->orderByDesc('date')->get() as $rec) {
                $xlsx->ligne([
                    $rec->date ? $rec->date->format('d/m/Y') : '',
                    $rec->vehicule ? $rec->vehicule->immatriculation : '?',
                    $rec->chauffeur ?? '—',
                    ucfirst(str_replace('_', ' ', $rec->type_jour ?? '')),
                    $rec->recette ?? 0,
                    $rec->objectif ?? 0,
                    $rec->ecart ?? 0,
                    $rec->mode_remise ?? '',
                    $rec->controle_par ?? '',
                    $rec->observations ?? '',
                ]);
            }
            return $xlsx->telecharger('recettes_parc_' . date('Ymd') . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export recettes : ' . $e->getMessage());
        }
    }

    /** Export pannes. */
    public function pannes(Request $r)
    {
        abort_unless($r->user()->peutGererParc(), 403);
        if (!Schema::hasTable('pannes')) return back()->with('erreur', 'Table pannes absente.');

        try {
            $query = Panne::with('vehicule');
            if ($r->filled('date_debut')) $query->whereDate('date_declaration', '>=', $r->date_debut);
            if ($r->filled('date_fin')) $query->whereDate('date_declaration', '<=', $r->date_fin);

            $xlsx = new GenerateurXlsx('Pannes');
            $xlsx->entetes(['Date déclaration', 'Véhicule', 'Description', 'Urgence', 'Statut', 'Coût estimé', 'Coût réel', 'Délai (j)', 'Décision', 'Date résolution', 'Observations']);

            foreach ($query->orderByDesc('date_declaration')->get() as $p) {
                $xlsx->ligne([
                    $p->date_declaration ? $p->date_declaration->format('d/m/Y') : '',
                    $p->vehicule ? $p->vehicule->immatriculation : '?',
                    $p->description ?? '',
                    ucfirst($p->urgence ?? ''),
                    ucfirst(str_replace('_', ' ', $p->statut ?? '')),
                    $p->cout_estime ?? '',
                    $p->cout_reel ?? '',
                    $p->delai_resolution ?? '',
                    $p->decision ?? '',
                    $p->date_resolution ? $p->date_resolution->format('d/m/Y') : '',
                    $p->observations ?? '',
                ]);
            }
            return $xlsx->telecharger('pannes_parc_' . date('Ymd') . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export pannes : ' . $e->getMessage());
        }
    }

    /** Export chauffeurs. */
    public function chauffeurs(Request $r)
    {
        abort_unless($r->user()->peutGererParc(), 403);
        if (!Schema::hasTable('chauffeurs')) return back()->with('erreur', 'Table chauffeurs absente.');

        try {
            $query = Chauffeur::with(['vehicules' => fn($q) => $q->wherePivot('actif', true)]);
            if ($r->filled('statut')) $query->where('statut', $r->statut);

            $xlsx = new GenerateurXlsx('Chauffeurs');
            $xlsx->entetes(['Nom', 'Prénom', 'N° permis', 'Téléphone', 'Statut', 'Véhicule(s) affecté(s)', 'Rôle(s)', 'Observations']);

            foreach ($query->orderBy('nom')->get() as $c) {
                $vehicules = $c->vehicules->map(fn($v) => $v->immatriculation)->join(', ');
                $roles = $c->vehicules->map(fn($v) => $v->pivot->role === 'principal' ? 'Principal' : 'Alternant')->join(', ');
                $xlsx->ligne([
                    $c->nom ?? '',
                    $c->prenom ?? '',
                    $c->numero_permis ?? '',
                    $c->telephone ?? '',
                    ucfirst($c->statut ?? ''),
                    $vehicules ?: '—',
                    $roles ?: '—',
                    $c->observations ?? '',
                ]);
            }
            return $xlsx->telecharger('chauffeurs_soumafe_' . date('Ymd') . '.xlsx');
        } catch (\Throwable $e) {
            return back()->with('erreur', 'Erreur export chauffeurs : ' . $e->getMessage());
        }
    }
}
