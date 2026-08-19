@extends('layouts.app')
@section('titre', 'Administration — Services')
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
.form-card label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin:10px 0 4px}
.form-card label:first-of-type{margin-top:0}
.form-card input,.form-card select{width:100%;max-width:400px;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-card input:focus,.form-card select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-actions{display:flex;gap:8px;margin-top:12px}
</style>
@endpush
@section('contenu')
<h2>Services</h2>
<div class="crumb">Administration &rsaquo; R&eacute;f&eacute;rentiels &rsaquo; Services</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())
<div class="alerte ko">
    <ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="form-card" id="formSection">
    <h3 id="formTitre">{{ old('_editing') ? 'Modifier le service' : 'Nouveau service' }}</h3>
    <form method="POST" id="formSvc" action="{{ old('_editing') ? url('admin/services/'.old('_editing')) : route('admin.services.store') }}">
        @csrf
        @if(old('_editing'))
        <input type="hidden" name="_method" value="PUT">
        @endif
        <input type="hidden" name="_editing" id="fEditing" value="{{ old('_editing', '') }}">
        <label>Libell&eacute;</label>
        <input name="libelle" id="fLib" required maxlength="100" value="{{ old('libelle') }}" placeholder="Ex : Service informatique">
        <label>Domaine</label>
        <select name="domaine_id" id="fDom">
            <option value="">— Aucun —</option>
            @foreach($domaines as $d)<option value="{{ $d->id }}" {{ old('domaine_id') == $d->id ? 'selected' : '' }}>{{ $d->libelle }}</option>@endforeach
        </select>
        <div class="form-actions">
            <button class="btn">Enregistrer</button>
            <button type="button" class="btn sec" onclick="resetForm()">Annuler</button>
        </div>
    </form>
</div>

<div style="margin-bottom:10px;font-size:13px;color:var(--gris)">{{ $services->count() }} service(s)</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Libell&eacute;</th><th>Domaine</th><th>P&ocirc;les</th><th>Actions</th></tr></thead>
<tbody>
@forelse($services as $s)
<tr>
    <td><strong>{{ $s->libelle }}</strong></td>
    <td>{{ $s->domaine?->libelle ?? '—' }}</td>
    <td>{{ $s->poles_count }}</td>
    <td style="white-space:nowrap">
        <button class="btn sm sec" onclick="editMode({{ $s->id }}, '{{ addslashes($s->libelle) }}', '{{ $s->domaine_id ?? '' }}')">Modifier</button>
        @if($s->poles_count === 0)
        <form method="POST" action="{{ route('admin.services.destroy', $s) }}" style="display:inline">@csrf @method('DELETE')
            <button class="btn sm danger" onclick="return confirm('Supprimer ?')">Supprimer</button>
        </form>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="4" style="text-align:center;color:var(--gris);padding:24px">Aucun service.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
@push('scripts')
<script>
function editMode(id, lib, domId) {
    document.getElementById('formTitre').textContent = 'Modifier le service';
    document.getElementById('formSvc').action = '{{ url("admin/services") }}/' + id;
    document.getElementById('fEditing').value = id;
    document.getElementById('fLib').value = lib;
    document.getElementById('fDom').value = domId || '';
    var mf = document.querySelector('#formSvc input[name="_method"]');
    if (!mf) { mf = document.createElement('input'); mf.type='hidden'; mf.name='_method'; document.getElementById('formSvc').appendChild(mf); }
    mf.value = 'PUT';
    document.getElementById('formSection').scrollIntoView({behavior:'smooth'});
    document.getElementById('fLib').focus();
}
function resetForm() {
    document.getElementById('formTitre').textContent = 'Nouveau service';
    document.getElementById('formSvc').action = '{{ route("admin.services.store") }}';
    document.getElementById('fEditing').value = '';
    document.getElementById('fLib').value = '';
    document.getElementById('fDom').value = '';
    var mf = document.querySelector('#formSvc input[name="_method"]');
    if (mf) mf.remove();
}
</script>
@endpush
