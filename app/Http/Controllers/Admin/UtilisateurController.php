<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Pole, ProfilMesure, Role, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Schema};
use Illuminate\Validation\Rule;

class UtilisateurController extends Controller
{
    /** Récupère les rôles disponibles (BD ou fallback). */
    private function listeRoles(): array
    {
        if (Schema::hasTable('roles')) {
            return Role::orderBy('libelle')->pluck('code')->toArray();
        }
        return ['administrateur', 'direction', 'charge_operations', 'collaborateur', 'invite'];
    }

    /** Récupère les profils disponibles (BD ou fallback). */
    private function listeProfils(): array
    {
        if (Schema::hasTable('profils_mesure')) {
            return ProfilMesure::orderBy('libelle')->pluck('code')->toArray();
        }
        return ['developpeur', 'operationnel', 'support'];
    }

    public function index(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $users = User::with('pole')
            ->when($r->q, fn ($q) => $q->where('name', 'like', "%{$r->q}%")->orWhere('prenom', 'like', "%{$r->q}%")->orWhere('email', 'like', "%{$r->q}%"))
            ->when($r->role, fn ($q) => $q->where('role', $r->role))
            ->when($r->profil, fn ($q) => $q->where('profil_mesure', $r->profil))
            ->when($r->filled('actif'), fn ($q) => $q->where('actif', $r->actif))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.utilisateurs.index', [
            'users' => $users,
            'poles' => Pole::orderBy('libelle')->get(),
            'roles' => $this->listeRoles(),
            'profils' => $this->listeProfils(),
        ]);
    }

    public function create(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.utilisateurs.form', [
            'user' => new User(['actif' => true, 'role' => 'collaborateur', 'profil_mesure' => 'support']),
            'poles' => Pole::orderBy('libelle')->get(),
            'roles' => $this->listeRoles(),
            'profils' => $this->listeProfils(),
        ]);
    }

    public function store(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $v = $r->validate([
            'name' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'pole_id' => 'nullable|exists:poles,id',
            'role' => ['required', Rule::in($this->listeRoles())],
            'profil_mesure' => ['required', Rule::in($this->listeProfils())],
            'actif' => 'boolean',
        ]);
        $v['password'] = Hash::make($v['password']);
        $v['actif'] = $r->boolean('actif');
        User::create($v);
        return redirect()->route('admin.utilisateurs.index')->with('succes', 'Compte créé.');
    }

    public function edit(Request $r, User $utilisateur)
    {
        abort_unless($r->user()->estAdmin(), 403);
        return view('admin.utilisateurs.form', [
            'user' => $utilisateur,
            'poles' => Pole::orderBy('libelle')->get(),
            'roles' => $this->listeRoles(),
            'profils' => $this->listeProfils(),
        ]);
    }

    public function update(Request $r, User $utilisateur)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $v = $r->validate([
            'name' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => ['required', 'email', Rule::unique('users')->ignore($utilisateur->id)],
            'password' => 'nullable|string|min:8',
            'pole_id' => 'nullable|exists:poles,id',
            'role' => ['required', Rule::in($this->listeRoles())],
            'profil_mesure' => ['required', Rule::in($this->listeProfils())],
            'actif' => 'boolean',
        ]);
        if (empty($v['password'])) unset($v['password']);
        else $v['password'] = Hash::make($v['password']);
        $v['actif'] = $r->boolean('actif');
        $utilisateur->update($v);
        return redirect()->route('admin.utilisateurs.index')->with('succes', 'Compte mis à jour.');
    }

    public function toggleActif(Request $r, User $utilisateur)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $utilisateur->update(['actif' => !$utilisateur->actif]);
        return back()->with('succes', $utilisateur->actif ? 'Compte réactivé.' : 'Compte désactivé.');
    }
}
