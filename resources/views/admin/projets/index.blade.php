@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Projets')
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
.pill{padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600}
.s-actif{background:#dcfce7;color:var(--ok)}.s-pause{background:#fef3c7;color:#b45309}
.s-termine{background:#e2e8f0;color:var(--gris)}.s-annule{background:#fee2e2;color:var(--ko)}
.form-card{background:var(--carte);border:1px solid var(--accent);border-radius:12px;padding:16px;margin-bottom:16px}
.form-card h3{font-size:14px;color:var(--bleu);margin:0 0 10px}
.form-card label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin:10px 0 4px}
.form-card label:first-of-type{margin-top:0}
.form-card input,.form-card select{width:100%;max-width:400px;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-card input:focus,.form-card select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-actions{display:flex;gap:8px;margin-top:12px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.hint{font-size:10.5px;color:var(--gris);margin-top:2px}
</style>
@endpush
@section('contenu')
<h2>Gestion des projets</h2>
<div class="crumb">Administration &rsaquo; R&eacute;f&eacute;rentiels &rsaquo; Projets</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())
<div class="alerte ko">
    <ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<div class="form-card" id="formSection">
    <h3 id="formTitre">{{ old('_editing') ? 'Modifier le projet' : 'Nouveau projet' }}</h3>
    <form method="POST" id="formProjet" action="{{ old('_editing') ? url('admin/projets/'.old('_editing')) : route('admin.projets.store') }}">
        @csrf
        @if(old('_editing'))<input type="hidden" name="_method" value="PUT">@endif
        <input type="hidden" name="_editing" id="fEditing" value="{{ old('_editing', '') }}">

        <div class="form-row">
            <div>
                <label>Code</label>
                <input name="code" id="fCode" required maxlength="20" value="{{ old('code') }}">
            </div>
            <div>
                <label>Statut</label>
                <select name="statut" id="fStatut">
                    <option value="actif" {{ old('statut') === 'actif' ? 'selected' : '' }}>Actif</option>
                    <option value="en_pause" {{ old('statut') === 'en_pause' ? 'selected' : '' }}>En pause</option>
                    <option value="termine" {{ old('statut') === 'termine' ? 'selected' : '' }}>Termin&eacute;</option>
                    <option value="annule" {{ old('statut') === 'annule' ? 'selected' : '' }}>Annul&eacute;</option>
                </select>
            </div>
        </div>

        <label>Libell&eacute;</label>
        <input name="libelle" id="fLib" required maxlength="150" value="{{ old('libelle') }}">

        <div class="form-row">
            <div>
                <label>Client</label>
                <input name="client" id="fClient" maxlength="100" value="{{ old('client') }}">
            </div>
            <div>
                <label>Responsable projet</label>
                <select name="responsable_projet_id" id="fResp">
                    <option value="">— Aucun —</option>
                    @foreach($responsables as $c)
                    <option value="{{ $c->id }}" {{ old('responsable_projet_id') == $c->id ? 'selected' : '' }}>{{ $c->nomComplet() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Date d&eacute;but</label>
                <input type="date" name="date_debut" id="fDebut" value="{{ old('date_debut') }}">
            </div>
            <div>
                <label>Date cible</label>
                <input type="date" name="date_cible" id="fCible" value="{{ old('date_cible') }}">
            </div>
        </div>

        <div style="margin-top:10px">
            <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;text-transform:none;font-size:13px">
                <input type="hidden" name="delegation_sprint" value="0">
                <input type="checkbox" name="delegation_sprint" id="fDeleg" value="1" style="width:auto;accent-color:var(--accent)" {{ old('delegation_sprint') ? 'checked' : '' }}>
                D&eacute;l&eacute;guer la gestion des sprints au responsable projet
            </label>
            <div class="hint">Si activ&eacute;, le responsable projet pourra cr&eacute;er et importer des sprints.</div>
        </div>

        <div class="form-actions">
            <button class="btn" id="btnSubmit">Enregistrer</button>
            <button type="button" class="btn sec" onclick="resetForm()">Annuler / Nouveau</button>
        </div>
    </form>
</div>

<div style="margin-bottom:10px;font-size:13px;color:var(--gris)">{{ $projets->count() }} projet(s)</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Code</th><th>Libell&eacute;</th><th>Client</th><th>Responsable</th><th>D&eacute;l&eacute;g.</th><th>Statut</th><th>T&acirc;ches</th><th>Actions</th></tr></thead>
<tbody>
@foreach($projets as $p)
<tr>
    <td><strong>{{ $p->code }}</strong></td>
    <td>{{ $p->libelle }}</td>
    <td>{{ $p->client ?? '—' }}</td>
    <td>{{ $p->responsable?->nomComplet() ?? '—' }}</td>
    <td style="text-align:center">{!! $p->delegation_sprint ? '<span style="color:var(--ok);font-weight:700">&#10003;</span>' : '' !!}</td>
    <td><span class="pill s-{{ str_replace('en_', '', $p->statut) }}">{{ ucfirst(str_replace('_',' ',$p->statut)) }}</span></td>
    <td>{{ $p->taches_count }}</td>
    <td><button class="btn sm sec" onclick="editMode({{ $p->id }}, {{ json_encode($p->code) }}, {{ json_encode($p->libelle) }}, {{ json_encode($p->client ?? '') }}, '{{ $p->responsable_projet_id ?? '' }}', '{{ $p->statut }}', '{{ $p->date_debut ? \Carbon\Carbon::parse($p->date_debut)->format('Y-m-d') : '' }}', '{{ $p->date_cible ? \Carbon\Carbon::parse($p->date_cible)->format('Y-m-d') : '' }}', {{ $p->delegation_sprint ? 'true' : 'false' }})">Modifier</button></td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection

@push('scripts')
<script>
function editMode(id, code, libelle, client, respId, statut, debut, cible, deleg) {
    document.getElementById('formTitre').textContent = 'Modifier le projet';
    document.getElementById('formProjet').action = '{{ url("admin/projets") }}/' + id;
    document.getElementById('fEditing').value = id;
    document.getElementById('fCode').value = code;
    document.getElementById('fLib').value = libelle;
    document.getElementById('fClient').value = client;
    document.getElementById('fResp').value = respId;
    document.getElementById('fStatut').value = statut;
    document.getElementById('fDebut').value = debut;
    document.getElementById('fCible').value = cible;
    document.getElementById('fDeleg').checked = deleg;
    // Ajouter _method PUT
    var mf = document.querySelector('#formProjet input[name="_method"]');
    if (!mf) { mf = document.createElement('input'); mf.type='hidden'; mf.name='_method'; document.getElementById('formProjet').appendChild(mf); }
    mf.value = 'PUT';
    document.getElementById('formSection').scrollIntoView({behavior:'smooth'});
    document.getElementById('fLib').focus();
}
function resetForm() {
    document.getElementById('formTitre').textContent = 'Nouveau projet';
    document.getElementById('formProjet').action = '{{ route("admin.projets.store") }}';
    document.getElementById('fEditing').value = '';
    document.getElementById('fCode').value = '';
    document.getElementById('fLib').value = '';
    document.getElementById('fClient').value = '';
    document.getElementById('fResp').value = '';
    document.getElementById('fStatut').value = 'actif';
    document.getElementById('fDebut').value = '';
    document.getElementById('fCible').value = '';
    document.getElementById('fDeleg').checked = false;
    var mf = document.querySelector('#formProjet input[name="_method"]');
    if (mf) mf.remove();
}
</script>
@endpush
