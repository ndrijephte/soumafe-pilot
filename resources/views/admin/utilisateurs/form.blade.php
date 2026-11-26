@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', $user->exists ? 'Modifier ' . $user->nomComplet() : 'Nouveau compte')
@push('styles')
<style>
.cp-grille{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:16px;align-items:start}
.cp-carte{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:6px 20px 18px}
.cp-carte .fm-section:first-child{border-top:none;margin-top:0}
.cp-apercu{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;position:sticky;top:16px}
.cp-av{width:46px;height:46px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:16px}
.cp-nom{font-size:15px;font-weight:600;color:var(--bleu);margin-top:10px;word-break:break-word}
.cp-ligne{font-size:12px;color:var(--gris);margin-top:3px;word-break:break-word}
.cp-ligne b{color:#334155;font-weight:600}
.cp-mdp{display:flex;gap:6px}
.cp-mdp .fm-champ{flex:1}
.cp-mdp button{flex:none}
.cp-err{color:var(--ko);font-size:11.5px;margin-top:3px}
@media(max-width:900px){.cp-grille{grid-template-columns:1fr}.cp-apercu{position:static}}
</style>
@endpush
@section('contenu')
@include('admin._ui')
@php
    $libProfils = \Illuminate\Support\Facades\Schema::hasTable('profils_mesure') ? \App\Models\ProfilMesure::pluck('libelle', 'code') : collect();
    $titres = \Illuminate\Support\Facades\Schema::hasTable('titres_poste') ? \App\Models\TitrePoste::orderBy('libelle')->pluck('libelle') : collect();
    $couleursRole = ['administrateur' => '#1d4ed8', 'direction' => '#b45309', 'charge_operations' => '#4338ca', 'collaborateur' => '#475569', 'invite' => '#94a3b8'];
@endphp
<h2>Administration</h2>
<div class="crumb">Administration &rsaquo; Comptes &rsaquo; {{ $user->exists ? $user->nomComplet() : 'Nouveau compte' }}</div>
@include('admin._nav')

@if($errors->any())<div class="ad-alerte ko">{{ $errors->first() }}</div>@endif

<form method="POST" action="{{ $user->exists ? route('admin.utilisateurs.update', $user) : route('admin.utilisateurs.store') }}" id="cp-form">
@csrf
@if($user->exists) @method('PUT') @endif
<div class="cp-grille">
<div class="cp-carte">
    <div class="fm-section"><h4>Identité</h4>
        <div class="fm-ligne2">
            <div><label class="fm-lab" for="cp-nom">Nom <span class="req">*</span></label>
                <input name="name" id="cp-nom" class="fm-champ" value="{{ old('name', $user->name) }}" required maxlength="100" style="text-transform:uppercase" oninput="cpApercu()"></div>
            <div><label class="fm-lab" for="cp-prenom">Prénom <span class="req">*</span></label>
                <input name="prenom" id="cp-prenom" class="fm-champ" value="{{ old('prenom', $user->prenom) }}" required maxlength="100" oninput="cpApercu()"></div>
        </div>
        <label class="fm-lab" for="cp-email">Adresse e-mail <span class="req">*</span></label>
        <input type="email" name="email" id="cp-email" class="fm-champ" value="{{ old('email', $user->email) }}" required placeholder="prenom.nom@soumafe.ci" oninput="cpApercu()">
        <label class="fm-lab" for="cp-mdp">Mot de passe @if($user->exists)<span class="fac">laisser vide pour ne pas le changer</span>@else<span class="req">*</span>@endif</label>
        <div class="cp-mdp">
            <input type="password" name="password" id="cp-mdp" class="fm-champ" {{ $user->exists ? '' : 'required' }} minlength="8" autocomplete="new-password" placeholder="8 caractères au moins">
            <button type="button" class="btn-s" data-sans-icone onclick="cpVoir()" id="cp-voir" title="Afficher ou masquer">Afficher</button>
            <button type="button" class="btn-s" data-sans-icone onclick="cpGenerer()" title="Proposer un mot de passe">Générer</button>
        </div>
        <p class="fm-aide" id="cp-force"></p>
    </div>

    <div class="fm-section"><h4>Accès et suivi</h4>
        <label class="fm-lab" for="cp-role">Rôle <span class="req">*</span></label>
        <select name="role" id="cp-role" required data-pastilles onchange="cpApercu()">
            @foreach($roles as $r)<option value="{{ $r }}" data-couleur="{{ $couleursRole[$r] ?? '#64748b' }}" @selected(old('role', $user->role ?: 'collaborateur') == $r)>{{ \App\Models\User::libelleRole($r) }}</option>@endforeach
        </select>
        <p class="fm-aide" id="cp-role-aide"></p>
        <label class="fm-lab" for="cp-profil">Profil de mesure <span class="req">*</span></label>
        <select name="profil_mesure" id="cp-profil" required data-pastilles onchange="cpApercu()">
            @foreach($profils as $p)<option value="{{ $p }}" @selected(old('profil_mesure', $user->profil_mesure) == $p)>{{ $libProfils[$p] ?? ucfirst($p) }}</option>@endforeach
        </select>
        <p class="fm-aide">Détermine les indicateurs suivis pour ce collaborateur.</p>
    </div>

    <div class="fm-section"><h4>Rattachement</h4>
        <div class="fm-ligne2">
            <div><label class="fm-lab" for="cp-pole">Pôle</label>
                <select name="pole_id" id="cp-pole" class="fm-champ" onchange="cpApercu()">
                    <option value="">— Aucun —</option>
                    @foreach($poles as $p)<option value="{{ $p->id }}" @selected(old('pole_id', $user->pole_id) == $p->id)>{{ $p->libelle }}</option>@endforeach
                </select></div>
            <div><label class="fm-lab" for="cp-titre">Titre de poste</label>
                <select name="titre_poste" id="cp-titre" class="fm-champ" onchange="cpApercu()" data-modalite="titre_poste" data-modalite-url="{{ route('modalites.store') }}" data-modalite-nom="un titre de poste">
                    <option value="">— Aucun —</option>
                    @foreach($titres as $tp)<option value="{{ $tp }}" @selected(old('titre_poste', $user->titre_poste) == $tp)>{{ $tp }}</option>@endforeach
                </select></div>
        </div>
    </div>

    <div class="fm-section"><h4>Options</h4>
        <input type="hidden" name="actif" value="0"><input type="hidden" name="delegation_parc" value="0">
        <label class="fm-option {{ old('actif', $user->exists ? $user->actif : true) ? 'on' : '' }}"><span class="inter"><input type="checkbox" name="actif" value="1" @checked(old('actif', $user->exists ? $user->actif : true)) onchange="this.closest('.fm-option').classList.toggle('on', this.checked); cpApercu()"><i></i></span>
            <span><b>Compte actif</b><span>Un compte inactif ne peut plus se connecter ; son historique est conservé.</span></span></label>
        <label class="fm-option {{ old('delegation_parc', $user->delegation_parc) ? 'on' : '' }}"><span class="inter"><input type="checkbox" name="delegation_parc" value="1" @checked(old('delegation_parc', $user->delegation_parc)) onchange="this.closest('.fm-option').classList.toggle('on', this.checked)"><i></i></span>
            <span><b>Délégation parc automobile</b><span>Donne accès à la gestion du parc (recettes, pannes, entretiens…).</span></span></label>
    </div>

    <div class="fm-pied">
        <a href="{{ route('admin.utilisateurs.index') }}" class="btn-s">Annuler</a>
        <button class="btn-p">{{ $user->exists ? 'Enregistrer' : 'Créer le compte' }}</button>
    </div>
</div>

<div class="cp-apercu" aria-label="Aperçu du compte">
    <div class="cp-av" id="ap-av">{{ mb_strtoupper(mb_substr($user->name ?? '', 0, 1) . mb_substr($user->prenom ?? '', 0, 1)) ?: '?' }}</div>
    <div class="cp-nom" id="ap-nom"></div>
    <div class="cp-ligne" id="ap-email"></div>
    <div class="cp-ligne" id="ap-poste"></div>
    <div style="margin-top:12px;display:flex;flex-direction:column;gap:6px">
        <span class="etat" id="ap-role"></span>
        <span class="etat" id="ap-actif"></span>
    </div>
</div>
</div>
</form>
@endsection

@push('scripts')
<script>
var CP_ROLES = {administrateur: 'Accès complet, y compris l\'Administration.', direction: 'Consultation de tout le système, arbitrages ; ne saisit pas.',
    charge_operations: 'Pilotage : sprints, arbitrages, reporting, relances.', collaborateur: 'Saisie quotidienne de ses tâches, Daily, pointage.', invite: 'Consultation limitée.'};
function cpApercu(){
    var f = document.getElementById('cp-form'), v = function(id){ var e = document.getElementById(id); return e ? e.value.trim() : ''; };
    var nom = v('cp-nom').toUpperCase(), prenom = v('cp-prenom');
    document.getElementById('ap-av').textContent = ((nom[0] || '') + (prenom[0] || '')).toUpperCase() || '?';
    document.getElementById('ap-nom').textContent = (nom + ' ' + prenom).trim() || 'Nouveau compte';
    document.getElementById('ap-email').textContent = v('cp-email');
    var pole = document.getElementById('cp-pole'), titre = v('cp-titre');
    document.getElementById('ap-poste').innerHTML = [titre, pole.value ? pole.options[pole.selectedIndex].text : ''].filter(Boolean).map(function(x){ return '<b>' + x.replace(/</g, '&lt;') + '</b>'; }).join(' · ');
    var role = document.getElementById('cp-role');
    document.getElementById('ap-role').textContent = role.options[role.selectedIndex].text;
    document.getElementById('ap-role').className = 'etat bleu';
    document.getElementById('cp-role-aide').textContent = CP_ROLES[role.value] || '';
    var actif = f.querySelector('input[type=checkbox][name=actif]').checked;
    document.getElementById('ap-actif').textContent = actif ? 'Compte actif' : 'Compte inactif';
    document.getElementById('ap-actif').className = 'etat ' + (actif ? 'ok' : 'mut');
}
function cpVoir(){ var m = document.getElementById('cp-mdp'), b = document.getElementById('cp-voir'); m.type = m.type === 'password' ? 'text' : 'password'; b.textContent = m.type === 'password' ? 'Afficher' : 'Masquer'; }
function cpGenerer(){
    var c = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789', s = '', a = new Uint32Array(12); crypto.getRandomValues(a);
    for (var i = 0; i < 12; i++) s += c[a[i] % c.length];
    var m = document.getElementById('cp-mdp'); m.value = s.slice(0, 4) + '-' + s.slice(4, 8) + '-' + s.slice(8); m.type = 'text';
    document.getElementById('cp-voir').textContent = 'Masquer'; cpForce();
}
function cpForce(){
    var m = document.getElementById('cp-mdp').value, p = document.getElementById('cp-force');
    if (!m) { p.textContent = ''; return; }
    var n = (m.length >= 8) + (m.length >= 12) + /[A-Z]/.test(m) + /[0-9]/.test(m) + /[^A-Za-z0-9]/.test(m);
    p.innerHTML = m.length < 8 ? '<span style="color:var(--ko)">Trop court : 8 caractères au moins.</span>' : 'Robustesse : <b style="color:' + (n >= 4 ? 'var(--ok)' : '#b45309') + '">' + (n >= 4 ? 'bonne' : 'moyenne') + '</b>. Communiquez-le au collaborateur, il pourra le changer depuis son profil.';
}
document.getElementById('cp-mdp').addEventListener('input', cpForce);
cpApercu();
</script>
@endpush
