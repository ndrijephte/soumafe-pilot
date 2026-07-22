@extends('layouts.app')
@section('titre', 'Administration — Comptes')
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
.r-admin{background:#dbeafe;color:#1d4ed8}.r-direction{background:#fef3c7;color:#b45309}
.r-chef{background:#e0e7ff;color:#4338ca}.r-collab{background:#e2e8f0;color:#475569}
.r-invite{background:#f1f5f9;color:#94a3b8}
.actif-oui{color:var(--ok)}.actif-non{color:var(--ko)}
.pagination-w{display:flex;gap:4px;margin-top:14px;font-size:12px}
.pagination-w a,.pagination-w span{padding:4px 10px;border:1px solid var(--bord);border-radius:6px;text-decoration:none;color:var(--bleu)}
.pagination-w .current{background:var(--accent);color:#fff;border-color:var(--accent)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
</style>
@endpush
@section('contenu')
<h2>Gestion des comptes</h2>
<div class="crumb">Administration &rsaquo; Comptes utilisateurs</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

<form class="filtres" method="GET">
    <input name="q" placeholder="Rechercher…" value="{{ request('q') }}">
    <select name="role"><option value="">Tous les rôles</option>@foreach($roles as $r)<option value="{{ $r }}" @selected(request('role')==$r)>{{ ucfirst(str_replace('_',' ',$r)) }}</option>@endforeach</select>
    <select name="profil"><option value="">Tous les profils</option>@foreach($profils as $p)<option value="{{ $p }}" @selected(request('profil')==$p)>{{ ucfirst($p) }}</option>@endforeach</select>
    <select name="actif"><option value="">Tous</option><option value="1" @selected(request('actif')==='1')>Actifs</option><option value="0" @selected(request('actif')==='0')>Inactifs</option></select>
    <button class="btn sm">Filtrer</button>
    <a href="{{ route('admin.utilisateurs.index') }}" class="btn sm sec">Réinitialiser</a>
</form>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
    <span style="font-size:13px;color:var(--gris)">{{ $users->total() }} compte(s)</span>
    <a href="{{ route('admin.utilisateurs.create') }}" class="btn">+ Nouveau compte</a>
</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Nom</th><th>Email</th><th>Pôle</th><th>Rôle</th><th>Profil mesure</th><th>Actif</th><th>Actions</th></tr></thead>
<tbody>
@forelse($users as $u)
<tr>
    <td><strong>{{ $u->name }}</strong> {{ $u->prenom }}</td>
    <td>{{ $u->email }}</td>
    <td>{{ $u->pole?->libelle ?? '—' }}</td>
    <td><span class="badge-role r-{{ explode('_',$u->role)[0] ?? 'collab' }}">{{ ucfirst(str_replace('_',' ',$u->role)) }}</span></td>
    <td>{{ ucfirst($u->profil_mesure) }}</td>
    <td class="{{ $u->actif ? 'actif-oui' : 'actif-non' }}">{{ $u->actif ? 'Oui' : 'Non' }}</td>
    <td style="white-space:nowrap">
        <a href="{{ route('admin.utilisateurs.edit', $u) }}" class="btn sm sec">Modifier</a>
        <form method="POST" action="{{ route('admin.utilisateurs.toggle', $u) }}" style="display:inline">@csrf @method('PATCH')
            <button class="btn sm {{ $u->actif ? 'danger' : '' }}" onclick="return confirm('{{ $u->actif ? 'Désactiver' : 'Réactiver' }} ce compte ?')">{{ $u->actif ? 'Désactiver' : 'Réactiver' }}</button>
        </form>
    </td>
</tr>
@empty
<tr><td colspan="7" style="text-align:center;color:var(--gris);padding:24px">Aucun compte trouvé.</td></tr>
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
