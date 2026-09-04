@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration — Domaines')
@push('styles')
<style>
.admin-tbl{width:100%;border-collapse:collapse;font-size:13px}
.admin-tbl th{text-align:left;color:var(--gris);font-weight:600;padding:8px 10px;border-bottom:2px solid var(--bord);font-size:11.5px;text-transform:uppercase}
.admin-tbl td{padding:8px 10px;border-bottom:1px solid var(--bord)}
.admin-tbl tr:hover{background:#f1f5f9}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.form-card{background:var(--carte);border:1px solid var(--accent);border-radius:12px;padding:16px;margin-bottom:16px}
.form-card h3{font-size:14px;color:var(--bleu);margin:0 0 10px}
.form-card label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:4px}
.form-card input{width:100%;max-width:400px;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-card input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-actions{display:flex;gap:8px;margin-top:12px}
</style>
@endpush
@section('contenu')
<h2>Domaines d'activit&eacute;</h2>
<div class="crumb">Administration &rsaquo; R&eacute;f&eacute;rentiels &rsaquo; Domaines</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())
<div class="alerte ko">
    <ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Formulaire créer/modifier --}}
<div class="form-card" id="formSection">
    <h3 id="formTitre">{{ old('_editing') ? 'Modifier le domaine' : 'Nouveau domaine' }}</h3>
    <form method="POST" id="formDom" action="{{ old('_editing') ? url('admin/domaines/'.old('_editing')) : route('admin.domaines.store') }}">
        @csrf
        @if(old('_editing'))
        <input type="hidden" name="_method" value="PUT">
        @endif
        <input type="hidden" name="_editing" id="fEditing" value="{{ old('_editing', '') }}">
        <label>Libell&eacute;</label>
        <input name="libelle" id="fLib" required maxlength="100" value="{{ old('libelle') }}" placeholder="Ex : Infrastructures">
        <div class="form-actions">
            <button class="btn">Enregistrer</button>
            <button type="button" class="btn sec" onclick="resetForm()">Annuler</button>
        </div>
    </form>
</div>

<div style="margin-bottom:10px;font-size:13px;color:var(--gris)">{{ $domaines->count() }} domaine(s)</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Libell&eacute;</th><th>Services</th><th>Actions</th></tr></thead>
<tbody>
@forelse($domaines as $d)
<tr>
    <td><strong>{{ $d->libelle }}</strong></td>
    <td>{{ $d->services_count }}</td>
    <td style="white-space:nowrap">
        <button class="btn sm sec" onclick="editMode({{ $d->id }}, '{{ addslashes($d->libelle) }}')">Modifier</button>
        @if($d->services_count === 0)
        <form method="POST" action="{{ route('admin.domaines.destroy', $d) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'ce domaine')">@csrf @method('DELETE')
            <input type="hidden" name="motif_suppression" class="motif-suppr-input">
            <button class="btn sm danger">Supprimer</button>
        </form>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="3" style="text-align:center;color:var(--gris);padding:24px">Aucun domaine.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
@push('scripts')
<script>
function editMode(id, lib) {
    document.getElementById('formTitre').textContent = 'Modifier le domaine';
    document.getElementById('formDom').action = '{{ url("admin/domaines") }}/' + id;
    document.getElementById('fEditing').value = id;
    document.getElementById('fLib').value = lib;
    // Ajouter le champ _method PUT s'il n'existe pas
    var methodField = document.querySelector('#formDom input[name="_method"]');
    if (!methodField) {
        methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        document.getElementById('formDom').appendChild(methodField);
    }
    methodField.value = 'PUT';
    document.getElementById('formSection').scrollIntoView({behavior:'smooth'});
    document.getElementById('fLib').focus();
}
function resetForm() {
    document.getElementById('formTitre').textContent = 'Nouveau domaine';
    document.getElementById('formDom').action = '{{ route("admin.domaines.store") }}';
    document.getElementById('fEditing').value = '';
    document.getElementById('fLib').value = '';
    var methodField = document.querySelector('#formDom input[name="_method"]');
    if (methodField) methodField.remove();
}
</script>
@endpush
