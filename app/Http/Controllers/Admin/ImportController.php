<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Projet, Tache, User};
use App\Services\LecteurXlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    /** Page d'import (US-19 + US-20). */
    public function index()
    {
        return view('admin.import.index');
    }

    /** US-19 — Import collaborateurs. */
    public function collaborateurs(Request $r)
    {
        $r->validate(['fichier' => 'required|file|mimes:xlsx']);
        $lecteur = new LecteurXlsx($r->file('fichier')->getRealPath());

        // Détecter la ligne d'en-tête (cherche "Nom" dans les 5 premières lignes)
        $brut = $lecteur->lire(1);
        $ligneEntete = 1;
        foreach ($brut as $i => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && str_contains(mb_strtolower($cell), 'nom')) {
                    $ligneEntete = $i + 1;
                    break 2;
                }
            }
        }

        $data = $lecteur->lire(1, $ligneEntete);
        $crees = 0;
        $maj = 0;
        $ignores = 0;

        foreach ($data as $row) {
            $nom = trim($row['nom'] ?? '');
            $prenom = trim($row['prenom'] ?? $row['_prenom'] ?? '');
            $email = trim($row['email'] ?? '');
            $fonction = trim($row['fonction'] ?? $row['role'] ?? '');
            $dept = trim($row['departement'] ?? $row['département'] ?? '');

            if (empty($nom) || empty($prenom)) {
                $ignores++;
                continue;
            }

            // Déduire le rôle
            $role = $this->deduireRole($fonction);
            $profil = $this->deduireProfil($fonction);

            // Upsert par email ou nom+prénom
            $user = null;
            if ($email) {
                $user = User::where('email', $email)->first();
            }
            if (!$user) {
                $user = User::whereRaw('LOWER(name) = ? AND LOWER(prenom) = ?', [mb_strtolower($nom), mb_strtolower($prenom)])->first();
            }

            if ($user) {
                $user->update(array_filter([
                    'email' => $email ?: $user->email,
                    'role' => $role,
                    'profil_mesure' => $profil,
                ]));
                $maj++;
            } else {
                $generatedEmail = $email ?: Str::slug($prenom) . '.' . Str::slug($nom) . '@soumafe.ci';
                User::create([
                    'name' => mb_strtoupper($nom),
                    'prenom' => ucfirst(mb_strtolower($prenom)),
                    'email' => $generatedEmail,
                    'password' => Hash::make('Soumafe2026!'),
                    'role' => $role,
                    'profil_mesure' => $profil,
                    'actif' => true,
                ]);
                $crees++;
            }
        }

        return back()->with('succes', "Import terminé : {$crees} créé(s), {$maj} mis à jour, {$ignores} ignoré(s). Mot de passe par défaut : Soumafe2026!");
    }

    /** US-20 — Import tâches depuis suivi quotidien. */
    public function taches(Request $r)
    {
        $r->validate(['fichier' => 'required|file|mimes:xlsx']);
        $lecteur = new LecteurXlsx($r->file('fichier')->getRealPath());

        // Feuille SAISIE, en-tête ligne 4
        $data = $lecteur->lire(1, 4);
        $importees = 0;
        $ignores = 0;

        foreach ($data as $row) {
            $dateStr = trim($row['date'] ?? '');
            $collabStr = trim($row['collaborateur'] ?? '');
            $projetStr = trim($row['projet'] ?? '');
            $tacheHier = trim($row['tâche_réalisée_(hier)'] ?? $row['tache_réalisée_(hier)'] ?? '');
            $tachePrevue = trim($row["tâche_prévue_(aujourd'hui)"] ?? $row["tache_prévue_(aujourd'hui)"] ?? '');
            $statut = trim($row['statut'] ?? '');
            $blocage = trim($row['blocage_/_observation'] ?? $row['blocage_/_observation'] ?? '');
            $duree = trim($row['durée_(h)'] ?? $row['duree_(h)'] ?? '');

            if (empty($dateStr) || empty($collabStr)) {
                $ignores++;
                continue;
            }

            // Trouver le collaborateur
            $parts = preg_split('/\s+/', $collabStr, 2);
            $nom = $parts[0] ?? '';
            $prenom = $parts[1] ?? '';
            $user = User::whereRaw('LOWER(name) = ?', [mb_strtolower($nom)])->first();
            if (!$user) {
                $ignores++;
                continue;
            }

            // Trouver ou créer le projet
            $projet = null;
            if ($projetStr) {
                $projet = Projet::whereRaw('LOWER(libelle) = ?', [mb_strtolower($projetStr)])->first();
                if (!$projet) {
                    $projet = Projet::create([
                        'code' => Str::upper(Str::slug($projetStr, '_')),
                        'libelle' => $projetStr,
                        'statut' => 'en_cours',
                    ]);
                }
            }

            // Mapper le statut
            $statutMapped = match (mb_strtolower($statut)) {
                'terminé', 'termine', 'terminée' => 'termine',
                'bloqué', 'bloque', 'bloquée' => 'bloque',
                'non démarré', 'non demarre', 'non demarré' => 'a_faire',
                default => 'en_cours',
            };

            // Créer la tâche (la tâche prévue prime, sinon la réalisée)
            $intitule = $tachePrevue ?: $tacheHier;
            if (empty($intitule)) {
                $ignores++;
                continue;
            }

            // Éviter les doublons exacts
            $date = \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
            $existe = Tache::where('user_id', $user->id)
                ->whereDate('date_debut', $date)
                ->where('intitule', $intitule)->exists();
            if ($existe) {
                $ignores++;
                continue;
            }

            Tache::create([
                'user_id' => $user->id,
                'projet_id' => $projet?->id,
                'intitule' => $intitule,
                'type' => 'T2',
                'statut' => $statutMapped,
                'priorite' => 'moyenne',
                'date_debut' => $date,
                'date_fin_reelle' => $statutMapped === 'termine' ? $date : null,
            ]);
            $importees++;
        }

        return back()->with('succes', "Import terminé : {$importees} tâche(s) importée(s), {$ignores} ignorée(s).");
    }

    private function deduireRole(string $fonction): string
    {
        $f = mb_strtolower($fonction);
        if (str_contains($f, 'directeur') || str_contains($f, 'dg')) return 'direction';
        if (str_contains($f, 'chef') || str_contains($f, 'lead')) return 'collaborateur';
        return 'collaborateur';
    }

    private function deduireProfil(string $fonction): string
    {
        $f = mb_strtolower($fonction);
        if (str_contains($f, 'dev') || str_contains($f, 'développeur')) return 'developpeur';
        if (str_contains($f, 'support') || str_contains($f, 'assistance')) return 'support';
        return 'operationnel';
    }
}
