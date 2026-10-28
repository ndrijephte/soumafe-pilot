@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Titres de poste')
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
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:420px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(0,0,0,.4)}
dialog label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin:10px 0 4px}
dialog input{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
</style>
@endpush
@section('contenu')
<h2>Titres de poste</h2>
<div class="crumb">Administration &rsaquo; R&eacute;f&eacute;rentiels &rsaquo; Titres de poste</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())
<div class="alerte ko">
    <ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <span style="font-size:13px;color:var(--gris)">{{ $titres->count() }} titre(s)</span>
    <button class="btn" onclick="openDlg()">+ Nouveau titre</button>
</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>#</th><th>Libell&eacute;</th><th>Actions</th></tr></thead>
<tbody>
@forelse($titres as $t)
<tr>
    <td style="color:var(--gris)">{{ $t->id }}</td>
    <td><strong>{{ $t->libelle }}</strong></td>
    <td style="white-space:nowrap">
        <button class="btn sm sec" onclick="openDlg({{ $t->id }}, '{{ addslashes($t->libelle) }}')">Modifier</button>
        <form method="POST" action="{{ url('admin/titres-poste/'.$t->id) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'ce titre')">@csrf @method('DELETE')
            <input type="hidden" name="motif_suppression" class="motif-suppr-input">
            <button class="btn sm danger">Supprimer</button>
        </form>
    </td>
</tr>
@empty
<tr><td colspan="3" style="text-align:center;color:var(--gris);padding:24px">Aucun titre de poste.</td></tr>
@endforelse
</tbody>
</table>
</div>

<dialog id="dlg">
<form method="POST" id="formTitre">@csrf
    <input type="hidden" name="_method" id="fMethod" value="POST">
    <h3 id="dlgTitre" style="color:var(--bleu);margin-bottom:4px">Nouveau titre de poste</h3>
    <label>Libell&eacute;</label>
    <input name="libelle" id="fLib" required maxlength="100" placeholder="Ex : Lead Developer, Chef de Mission...">
    <div style="display:flex;gap:10px;margin-top:16px">
        <button class="btn">Enregistrer</button>
        <button type="button" class="btn sec" onclick="document.getElementById('dlg').close()">Annuler</button>
    </div>
</form>
</dialog>
@endsection
@push('scripts')
<script>
function openDlg(id, lib){
    const f=document.getElementById('formTitre');
    if(id){
        f.action='{{ url("admin/titres-poste") }}/'+id;
        document.getElementById('fMethod').value='PUT';
        document.getElementById('dlgTitre').textContent='Modifier le titre';
        document.getElementById('fLib').value=lib;
    } else {
        f.action='{{ url("admin/titres-poste") }}';
        document.getElementById('fMethod').value='POST';
        document.getElementById('dlgTitre').textContent='Nouveau titre de poste';
        f.reset();
    }
    document.getElementById('dlg').showModal();
}
</script>
@endpush
