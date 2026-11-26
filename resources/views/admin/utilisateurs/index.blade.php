@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Comptes')
@push('styles')
<style>
.admin-tbl{width:100%;border-collapse:collapse;font-size:13px}
.admin-tbl th{text-align:left;color:var(--gris);font-weight:600;padding:8px 10px;border-bottom:2px solid var(--bord);font-size:11.5px;text-transform:uppercase}
.admin-tbl td{padding:8px 10px;border-bottom:1px solid var(--bord)}
.admin-tbl tr:hover{background:#f1f5f9}
.filtres{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px}
.filtres select,.filtres input{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.btn:hover{opacity:.9}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.danger{background:var(--ko)}
.badge-role{padding:2px 8px;border-radius:10px;font-size:10.5px;font-weight:600}
.r-administrateur{background:#dbeafe;color:#1d4ed8}.r-direction{background:#fef3c7;color:#b45309}
.r-charge_operations{background:#e0e7ff;color:#4338ca}.r-collaborateur{background:#e2e8f0;color:#475569}
.r-invite{background:#f1f5f9;color:#94a3b8}
.actif-oui{color:var(--ok)}.actif-non{color:var(--ko)}
.pagination-w{display:flex;gap:4px;margin-top:14px;font-size:12px}
.pagination-w a,.pagination-w span{padding:4px 10px;border:1px solid var(--bord);border-radius:6px;text-decoration:none;color:var(--bleu)}
.pagination-w .current{background:var(--accent);color:#fff;border-color:var(--accent)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}

@media(max-width:900px){
    .filtres{flex-direction:column}
    .admin-tbl{font-size:12px}
    .admin-tbl th:nth-child(2),.admin-tbl td:nth-child(2),
    .admin-tbl th:nth-child(5),.admin-tbl td:nth-child(5){display:none}
    .btn.sm{padding:4px 8px;font-size:10.5px}
}
@media(max-width:500px){
    .admin-tbl th:nth-child(3),.admin-tbl td:nth-child(3),
    .admin-tbl th:nth-child(6),.admin-tbl td:nth-child(6){display:none}
}
</style>
@endpush
@section('contenu')
@include('admin._ui')
@php
    $libProfils = \Illuminate\Support\Facades\Schema::hasTable('profils_mesure') ? \App\Models\ProfilMesure::pluck('libelle', 'code') : collect();
    $filtre = request()->hasAny(['q', 'role', 'profil', 'actif']) && collect(request()->only(['q', 'role', 'profil', 'actif']))->filter(fn($v) => $v !== null && $v !== '')->isNotEmpty();
    $ic = [
        'edit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
        'off' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="22" y2="13"/><line x1="22" y1="8" x2="17" y2="13"/></svg>',
        'on' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>',
        'cle' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7.5" cy="15.5" r="5.5"/><path d="m21 2-9.6 9.6"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>',
    ];
@endphp
<h2>Administration</h2>
<div class="crumb">Administration &rsaquo; Comptes et accès &rsaquo; Comptes</div>
@include('admin._nav')

@if(session('succes'))<div class="ad-alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="ad-alerte ko">{{ session('erreur') }}</div>@endif

<form class="ad-barre" method="GET" id="cp-filtres">
    <div class="ad-recherche">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" name="q" placeholder="Nom, e-mail…" value="{{ request('q') }}" data-sans-controle>
    </div>
    <select name="role" class="fm-champ" style="width:auto" onchange="this.form.submit()"><option value="">Tous les rôles</option>@foreach($roles as $r)<option value="{{ $r }}" @selected(request('role') == $r)>{{ \App\Models\User::libelleRole($r) }}</option>@endforeach</select>
    <select name="profil" class="fm-champ" style="width:auto" onchange="this.form.submit()"><option value="">Tous les profils</option>@foreach($profils as $p)<option value="{{ $p }}" @selected(request('profil') == $p)>{{ $libProfils[$p] ?? ucfirst($p) }}</option>@endforeach</select>
    <select name="actif" class="fm-champ" style="width:auto" onchange="this.form.submit()"><option value="">Actifs et inactifs</option><option value="1" @selected(request('actif') === '1')>Actifs</option><option value="0" @selected(request('actif') === '0')>Inactifs</option></select>
    @if($filtre)<a href="{{ route('admin.utilisateurs.index') }}" class="compte" style="color:var(--accent)">Effacer les filtres</a>@endif
    <span class="espace"></span>
    <span class="compte">{{ $users->total() }} compte{{ $users->total() > 1 ? 's' : '' }}</span>
    <a href="{{ route('admin.utilisateurs.create') }}" class="btn-p" data-sans-icone><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nouveau compte</a>
</form>

<div class="ad-panneau">
<table class="ad-table">
<thead><tr><th>Collaborateur</th><th>Pôle</th><th>Rôle</th><th>Poste</th><th>Profil</th><th>Statut</th><th></th></tr></thead>
<tbody>
@forelse($users as $u)
<tr class="{{ $u->actif ? '' : 'inactif' }}">
    <td><b style="font-weight:600">{{ $u->name }}</b> {{ $u->prenom }}<span class="sec">{{ $u->email }}</span></td>
    <td>{{ $u->pole?->libelle ?? '—' }}</td>
    <td>{{ \App\Models\User::libelleRole($u->role) }}@if($u->delegation_parc)<span class="sec">+ délégation parc</span>@endif</td>
    <td>{{ $u->titre_poste ?: '—' }}</td>
    <td>{{ $libProfils[$u->profil_mesure] ?? ucfirst((string) $u->profil_mesure) }}</td>
    <td><span class="etat {{ $u->actif ? 'ok' : 'mut' }}">{{ $u->actif ? 'Actif' : 'Inactif' }}</span></td>
    <td class="act">
        <a href="{{ route('admin.utilisateurs.edit', $u) }}" class="ib" title="Modifier" aria-label="Modifier">{!! $ic['edit'] !!}</a>
        <form method="POST" action="{{ route('admin.utilisateurs.reset-mdp', $u) }}" style="display:inline" onsubmit="return confirm('Réinitialiser le mot de passe de {{ addslashes($u->nomComplet()) }} ?')">@csrf
            <button class="ib warn" data-sans-icone title="Réinitialiser le mot de passe" aria-label="Réinitialiser le mot de passe">{!! $ic['cle'] !!}</button>
        </form>
        <form method="POST" action="{{ route('admin.utilisateurs.toggle', $u) }}" style="display:inline" onsubmit="return confirm('{{ $u->actif ? 'Désactiver' : 'Réactiver' }} le compte de {{ addslashes($u->nomComplet()) }} ?')">@csrf @method('PATCH')
            <button class="ib {{ $u->actif ? 'ko' : 'ok' }}" data-sans-icone title="{{ $u->actif ? 'Désactiver le compte' : 'Réactiver le compte' }}" aria-label="{{ $u->actif ? 'Désactiver' : 'Réactiver' }}">{!! $u->actif ? $ic['off'] : $ic['on'] !!}</button>
        </form>
    </td>
</tr>
@empty
<tr><td colspan="7" class="ad-vide">Aucun compte ne correspond.</td></tr>
@endforelse
</tbody>
</table>
</div>

@if($users->hasPages())
<div class="pagination-w">
    @if($users->onFirstPage())<span>&laquo;</span>@else<a href="{{ $users->previousPageUrl() }}">&laquo;</a>@endif
    @foreach($users->getUrlRange(1, $users->lastPage()) as $page => $url)
        @if($page == $users->currentPage())<span class="current">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
    @endforeach
    @if($users->hasMorePages())<a href="{{ $users->nextPageUrl() }}">&raquo;</a>@else<span>&raquo;</span>@endif
</div>
@endif
@endsection
