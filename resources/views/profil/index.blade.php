@extends('layouts.app')
@section('sousTitre', 'Mon profil')
@section('titre', 'Mon profil')
@push('styles')
<style>
.profil-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px}
@media(max-width:768px){.profil-grid{grid-template-columns:1fr}}
.carte{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:18px 20px}
.carte h3{font-size:15px;color:var(--bleu);margin-bottom:14px}
.info-line{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--bord);font-size:13px}
.info-line:last-child{border:none}
.info-label{color:var(--gris);font-weight:600;font-size:12px;text-transform:uppercase}
.info-val{font-weight:500}
.form-group{margin-bottom:14px}
.form-group label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:4px}
.form-group input{width:100%;padding:8px 12px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-group input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.avatar-lg{width:64px;height:64px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:22px;margin:0 auto 14px}
</style>
@endpush
@section('contenu')
<h2>Mon profil</h2>
<div class="crumb">Informations personnelles et s&eacute;curit&eacute;</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<div class="profil-grid">
    <div class="carte">
        <div class="avatar-lg">{{ mb_strtoupper(mb_substr($user->name,0,1).mb_substr($user->prenom,0,1)) }}</div>
        <h3 style="text-align:center;margin-bottom:16px">{{ $user->nomComplet() }}</h3>
        <div class="info-line"><span class="info-label">Email</span><span class="info-val">{{ $user->email }}</span></div>
        <div class="info-line"><span class="info-label">R&ocirc;le</span><span class="info-val">{{ \App\Models\User::libelleRole($user->role) }}</span></div>
        @if($user->titre_poste)<div class="info-line"><span class="info-label">Titre de poste</span><span class="info-val">{{ $user->titre_poste }}</span></div>@endif
        <div class="info-line"><span class="info-label">P&ocirc;le</span><span class="info-val">{{ $user->pole?->libelle ?? '—' }}</span></div>
        <div class="info-line"><span class="info-label">Profil mesure</span><span class="info-val">{{ ucfirst($user->profil_mesure) }}</span></div>
        <div class="info-line"><span class="info-label">Compte cr&eacute;&eacute;</span><span class="info-val">{{ $user->created_at?->format('d/m/Y') ?? '—' }}</span></div>
    </div>

    <div class="carte">
        <h3>Changer le mot de passe</h3>
        <form method="POST" action="{{ route('profil.password') }}">
            @csrf
            @method('PATCH')
            <div class="form-group">
                <label>Mot de passe actuel</label>
                <input type="password" name="mot_de_passe_actuel" required>
            </div>
            <div class="form-group">
                <label>Nouveau mot de passe</label>
                <input type="password" name="mot_de_passe" required minlength="8">
            </div>
            <div class="form-group">
                <label>Confirmer le nouveau mot de passe</label>
                <input type="password" name="mot_de_passe_confirmation" required>
            </div>
            <button class="btn">Modifier le mot de passe</button>
        </form>
    </div>
</div>
@endsection
