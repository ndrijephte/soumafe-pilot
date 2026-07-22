@extends('layouts.app')
@section('titre', 'Administration — Pôles')
@push('styles')
<style>
.admin-tbl{width:100%;border-collapse:collapse;font-size:13px}
.admin-tbl th{text-align:left;color:var(--gris);font-weight:600;padding:8px 10px;border-bottom:2px solid var(--bord);font-size:11.5px;text-transform:uppercase}
.admin-tbl td{padding:8px 10px;border-bottom:1px solid var(--bord)}
.admin-tbl tr:hover{background:#f1f5f9}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.danger{background:var(--ko)}
.inline-form{display:flex;gap:8px;align-items:center;margin-bottom:14px}
.inline-form input{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;width:260px}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.edit-row{display:none}.edit-row.visible{display:table-row}
</style>
@endpush
@section('contenu')
<h2>Gestion des pôles</h2>
<div class="crumb">Administration &rsaquo; Référentiels &rsaquo; Pôles</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

<form class="inline-form" method="POST" action="{{ route('admin.poles.store') }}">
    @csrf
    <input name="libelle" placeholder="Nouveau pôle…" required>
    <button class="btn sm">+ Ajouter</button>
</form>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Pôle</th><th>Collaborateurs</th><th>Actions</th></tr></thead>
<tbody>
@foreach($poles as $p)
<tr>
    <td><strong>{{ $p->libelle }}</strong></td>
    <td>{{ $p->users_count }}</td>
    <td style="white-space:nowrap">
        <button class="btn sm sec" onclick="this.closest('tr').nextElementSibling.classList.toggle('visible')">Modifier</button>
        @if($p->users_count == 0)
        <form method="POST" action="{{ route('admin.poles.destroy', $p) }}" style="display:inline">@csrf @method('DELETE')
            <button class="btn sm danger" onclick="return confirm('Supprimer ce pôle ?')">Supprimer</button>
        </form>
        @endif
    </td>
</tr>
<tr class="edit-row">
    <td colspan="3">
        <form method="POST" action="{{ route('admin.poles.update', $p) }}" class="inline-form" style="margin:0">@csrf @method('PUT')
            <input name="libelle" value="{{ $p->libelle }}" required>
            <button class="btn sm">Enregistrer</button>
        </form>
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
