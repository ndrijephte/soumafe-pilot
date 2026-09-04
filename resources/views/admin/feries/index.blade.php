@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration — Jours fériés')
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
.inline-form input{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.annee-sel{display:flex;gap:6px;margin-bottom:14px}
.annee-sel a{padding:5px 14px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;text-decoration:none;color:var(--bleu)}
.annee-sel a.actif{background:var(--accent);color:#fff;border-color:var(--accent)}
.edit-row{display:none}.edit-row.visible{display:table-row}
</style>
@endpush
@section('contenu')
<h2>Calendrier des jours fériés</h2>
<div class="crumb">Administration &rsaquo; Référentiels &rsaquo; Jours fériés {{ $annee }}</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif

<div class="annee-sel">
    @foreach($annees as $a)
    <a href="{{ route('admin.feries.index', ['annee' => $a]) }}" class="{{ $a == $annee ? 'actif' : '' }}">{{ $a }}</a>
    @endforeach
</div>

<form class="inline-form" method="POST" action="{{ route('admin.feries.store') }}">
    @csrf
    <input type="date" name="date" required>
    <input name="libelle" placeholder="Libellé du jour férié…" required style="width:260px">
    <button class="btn sm">+ Ajouter</button>
</form>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Date</th><th>Jour</th><th>Libellé</th><th>Actions</th></tr></thead>
<tbody>
@foreach($feries as $f)
@php $jours = ['dimanche','lundi','mardi','mercredi','jeudi','vendredi','samedi']; @endphp
<tr>
    <td>{{ $f->date->format('d/m/Y') }}</td>
    <td>{{ ucfirst($jours[$f->date->dayOfWeek]) }}</td>
    <td><strong>{{ $f->libelle }}</strong></td>
    <td style="white-space:nowrap">
        <button class="btn sm sec" onclick="this.closest('tr').nextElementSibling.classList.toggle('visible')">Modifier</button>
        <form method="POST" action="{{ route('admin.feries.destroy', $f) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'ce jour férié')">@csrf @method('DELETE')
            <input type="hidden" name="motif_suppression" class="motif-suppr-input">
            <button class="btn sm danger">Supprimer</button>
        </form>
    </td>
</tr>
<tr class="edit-row">
    <td colspan="4">
        <form method="POST" action="{{ route('admin.feries.update', $f) }}" class="inline-form" style="margin:0">@csrf @method('PUT')
            <input type="date" name="date" value="{{ $f->date->format('Y-m-d') }}" required>
            <input name="libelle" value="{{ $f->libelle }}" required style="width:260px">
            <button class="btn sm">Enregistrer</button>
        </form>
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
<div style="font-size:11px;color:var(--gris);margin-top:12px">{{ $feries->count() }} jour(s) férié(s) en {{ $annee }}. Les fêtes mobiles (Ascension, Laylat al-Qadr…) sont à confirmer par décret.</div>
@endsection
