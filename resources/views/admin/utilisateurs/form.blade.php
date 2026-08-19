@extends('layouts.app')
@section('titre', $user->exists ? 'Modifier ' . $user->nomComplet() : 'Nouveau compte')
@push('styles')
<style>
.form-admin{max-width:600px}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:4px}
.form-group input,.form-group select{width:100%;padding:8px 12px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.check-label{display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer}
.check-label input{width:auto}
.err{color:var(--ko);font-size:11.5px;margin-top:3px}
.req{color:var(--ko);font-weight:700}
</style>
@endpush
@section('contenu')
<h2>{{ $user->exists ? 'Modifier le compte' : 'Nouveau compte' }}</h2>
<div class="crumb">Administration &rsaquo; Comptes &rsaquo; {{ $user->exists ? $user->nomComplet() : 'Création' }}</div>

<div class="carte form-admin">
<form method="POST" action="{{ $user->exists ? route('admin.utilisateurs.update', $user) : route('admin.utilisateurs.store') }}">
    @csrf
    @if($user->exists) @method('PUT') @endif

    <div class="form-row">
        <div class="form-group">
            <label>Nom <span class="req">*</span></label>
            <input name="name" value="{{ old('name', $user->name) }}" required>
            @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Prénom <span class="req">*</span></label>
            <input name="prenom" value="{{ old('prenom', $user->prenom) }}" required>
            @error('prenom')<div class="err">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="form-group">
        <label>Email <span class="req">*</span></label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        @error('email')<div class="err">{{ $message }}</div>@enderror
    </div>

    <div class="form-group">
        <label>Mot de passe{{ $user->exists ? '' : ' *' }}</label>
        <input type="password" name="password" {{ $user->exists ? '' : 'required' }} minlength="8">
        @error('password')<div class="err">{{ $message }}</div>@enderror
    </div>

    <div class="form-row">
        <div class="form-group">
            <label>Rôle <span class="req">*</span></label>
            <select name="role" required>
                @foreach($roles as $r)<option value="{{ $r }}" @selected(old('role', $user->role)==$r)>{{ \App\Models\User::libelleRole($r) }}</option>@endforeach
            </select>
        </div>
        <div class="form-group">
            <label>Profil de mesure <span class="req">*</span></label>
            <select name="profil_mesure" required>
                @foreach($profils as $p)<option value="{{ $p }}" @selected(old('profil_mesure', $user->profil_mesure)==$p)>{{ ucfirst($p) }}</option>@endforeach
            </select>
        </div>
    </div>

    <div class="form-group">
        <label>Pôle</label>
        <select name="pole_id">
            <option value="">— Aucun —</option>
            @foreach($poles as $p)<option value="{{ $p->id }}" @selected(old('pole_id', $user->pole_id)==$p->id)>{{ $p->libelle }}</option>@endforeach
        </select>
    </div>

    <div class="form-group">
        <label class="check-label"><input type="checkbox" name="actif" value="1" @checked(old('actif', $user->actif))> Compte actif</label>
    </div>

    <div style="display:flex;gap:10px;margin-top:18px">
        <button class="btn">{{ $user->exists ? 'Enregistrer' : 'Créer le compte' }}</button>
        <a href="{{ route('admin.utilisateurs.index') }}" class="btn sec">Annuler</a>
    </div>
</form>
</div>
@endsection
