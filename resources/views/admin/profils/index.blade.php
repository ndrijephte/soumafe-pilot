@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration — Profils de mesure')
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
.badge-sys{background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700}
.form-card{background:var(--carte);border:1px solid var(--accent);border-radius:12px;padding:16px;margin-bottom:16px}
.form-card h3{font-size:14px;color:var(--bleu);margin:0 0 10px}
.form-card label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin:10px 0 4px}
.form-card label:first-of-type{margin-top:0}
.form-card input{width:100%;max-width:400px;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-card input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-actions{display:flex;gap:8px;margin-top:12px}
.hint{font-size:10.5px;color:var(--gris);margin-top:2px}
</style>
@endpush
@section('contenu')
<h2>Profils de mesure</h2>
<div class="crumb">Administration &rsaquo; R&eacute;f&eacute;rentiels &rsaquo; Profils de mesure</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())
<div class="alerte ko">
    <ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="form-card" id="formSection">
    <h3 id="formTitre">{{ old('_editing') ? 'Modifier le profil' : 'Nouveau profil' }}</h3>
    <form method="POST" id="formProfil" action="{{ old('_editing') ? url('admin/profils/'.old('_editing')) : url('admin/profils') }}">
        @csrf
        @if(old('_editing'))<input type="hidden" name="_method" value="PUT">@endif
        <input type="hidden" name="_editing" id="fEditing" value="{{ old('_editing', '') }}">
        <div id="codeWrap" style="{{ old('_editing') ? 'display:none' : '' }}">
            <label>Code (identifiant technique)</label>
            <input name="code" id="fCode" maxlength="50" pattern="[a-z_]+" placeholder="ex: analyste" value="{{ old('code') }}">
            <div class="hint">Minuscules et underscores uniquement.</div>
        </div>
        <label>Libell&eacute; affich&eacute;</label>
        <input name="libelle" id="fLib" required maxlength="100" placeholder="ex: Analyste" value="{{ old('libelle') }}">
        <div class="form-actions">
            <button class="btn">Enregistrer</button>
            <button type="button" class="btn sec" onclick="resetForm()">Annuler</button>
        </div>
    </form>
</div>

<div style="margin-bottom:10px;font-size:13px;color:var(--gris)">{{ $profils->count() }} profil(s)</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Code</th><th>Libell&eacute;</th><th>Type</th><th>Utilisateurs</th><th>Actions</th></tr></thead>
<tbody>
@foreach($profils as $p)
<tr>
    <td><code>{{ $p->code }}</code></td>
    <td><strong>{{ $p->libelle }}</strong></td>
    <td>@if($p->systeme)<span class="badge-sys">Syst&egrave;me</span>@else Personnalis&eacute; @endif</td>
    <td>{{ $p->users_count }}</td>
    <td style="white-space:nowrap">
        <button class="btn sm sec" onclick="editMode({{ $p->id }}, '{{ addslashes($p->libelle) }}')">Modifier</button>
        @if(!$p->systeme && $p->users_count == 0)
        <form method="POST" action="{{ url('admin/profils/'.$p->id) }}" style="display:inline">@csrf @method('DELETE')
            <button class="btn sm danger" onclick="return confirm('Supprimer ?')">Supprimer</button>
        </form>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
@push('scripts')
<script>
function editMode(id, lib) {
    document.getElementById('formTitre').textContent = 'Modifier le profil';
    document.getElementById('formProfil').action = '{{ url("admin/profils") }}/' + id;
    document.getElementById('fEditing').value = id;
    document.getElementById('fLib').value = lib;
    document.getElementById('codeWrap').style.display = 'none';
    var mf = document.querySelector('#formProfil input[name="_method"]');
    if (!mf) { mf = document.createElement('input'); mf.type='hidden'; mf.name='_method'; document.getElementById('formProfil').appendChild(mf); }
    mf.value = 'PUT';
    document.getElementById('formSection').scrollIntoView({behavior:'smooth'});
    document.getElementById('fLib').focus();
}
function resetForm() {
    document.getElementById('formTitre').textContent = 'Nouveau profil';
    document.getElementById('formProfil').action = '{{ url("admin/profils") }}';
    document.getElementById('fEditing').value = '';
    document.getElementById('fLib').value = '';
    document.getElementById('fCode').value = '';
    document.getElementById('codeWrap').style.display = '';
    var mf = document.querySelector('#formProfil input[name="_method"]');
    if (mf) mf.remove();
}
</script>
@endpush
