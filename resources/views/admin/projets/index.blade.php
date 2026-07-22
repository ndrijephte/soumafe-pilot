@extends('layouts.app')
@section('titre', 'Administration — Projets')
@push('styles')
<style>
.admin-tbl{width:100%;border-collapse:collapse;font-size:13px}
.admin-tbl th{text-align:left;color:var(--gris);font-weight:600;padding:8px 10px;border-bottom:2px solid var(--bord);font-size:11.5px;text-transform:uppercase}
.admin-tbl td{padding:8px 10px;border-bottom:1px solid var(--bord)}
.admin-tbl tr:hover{background:#f1f5f9}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.pill{padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600}
.s-actif{background:#dcfce7;color:var(--ok)}.s-pause{background:#fef3c7;color:#b45309}
.s-termine{background:#e2e8f0;color:var(--gris)}.s-annule{background:#fee2e2;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:520px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(0,0,0,.4)}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:4px}
.form-group input,.form-group select{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.err{color:var(--ko);font-size:11.5px;margin-top:3px}
</style>
@endpush
@section('contenu')
<h2>Gestion des projets</h2>
<div class="crumb">Administration &rsaquo; Référentiels &rsaquo; Projets</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <span style="font-size:13px;color:var(--gris)">{{ $projets->count() }} projet(s)</span>
    <button class="btn" onclick="openDialog()">+ Nouveau projet</button>
</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Code</th><th>Libellé</th><th>Client</th><th>Chef de projet</th><th>Statut</th><th>Tâches</th><th>Actions</th></tr></thead>
<tbody>
@foreach($projets as $p)
<tr>
    <td><strong>{{ $p->code }}</strong></td>
    <td>{{ $p->libelle }}</td>
    <td>{{ $p->client ?? '—' }}</td>
    <td>{{ $p->chefProjet?->nomComplet() ?? '—' }}</td>
    <td><span class="pill s-{{ str_replace('en_', '', $p->statut) }}">{{ ucfirst(str_replace('_',' ',$p->statut)) }}</span></td>
    <td>{{ $p->taches_count }}</td>
    <td><button class="btn sm sec" onclick="openDialog({{ $p->toJson() }})">Modifier</button></td>
</tr>
@endforeach
</tbody>
</table>
</div>

<dialog id="dlg">
<form method="POST" id="projetForm">
    @csrf
    <input type="hidden" name="_method" id="formMethod" value="POST">
    <h3 id="dlgTitre" style="color:var(--bleu);margin-bottom:14px">Nouveau projet</h3>
    <div class="form-row">
        <div class="form-group"><label>Code</label><input name="code" id="f_code" required maxlength="20"></div>
        <div class="form-group"><label>Statut</label>
            <select name="statut" id="f_statut">
                <option value="actif">Actif</option><option value="en_pause">En pause</option>
                <option value="termine">Terminé</option><option value="annule">Annulé</option>
            </select>
        </div>
    </div>
    <div class="form-group"><label>Libellé</label><input name="libelle" id="f_libelle" required maxlength="150"></div>
    <div class="form-row">
        <div class="form-group"><label>Client</label><input name="client" id="f_client" maxlength="100"></div>
        <div class="form-group"><label>Chef de projet</label>
            <select name="chef_projet_id" id="f_chef">
                <option value="">— Aucun —</option>
                @foreach($chefs as $c)<option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group"><label>Date début</label><input type="date" name="date_debut" id="f_debut"></div>
        <div class="form-group"><label>Date cible</label><input type="date" name="date_cible" id="f_cible"></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px">
        <button class="btn" type="submit">Enregistrer</button>
        <button class="btn sec" type="button" onclick="document.getElementById('dlg').close()">Annuler</button>
    </div>
</form>
</dialog>
@endsection

@push('scripts')
<script>
function openDialog(p){
    const d=document.getElementById('dlg'),f=document.getElementById('projetForm');
    if(p){
        document.getElementById('dlgTitre').textContent='Modifier le projet';
        f.action='{{ url("admin/projets") }}/'+p.id;
        document.getElementById('formMethod').value='PUT';
        document.getElementById('f_code').value=p.code;
        document.getElementById('f_libelle').value=p.libelle;
        document.getElementById('f_client').value=p.client||'';
        document.getElementById('f_chef').value=p.chef_projet_id||'';
        document.getElementById('f_statut').value=p.statut;
        document.getElementById('f_debut').value=p.date_debut?p.date_debut.substring(0,10):'';
        document.getElementById('f_cible').value=p.date_cible?p.date_cible.substring(0,10):'';
    } else {
        document.getElementById('dlgTitre').textContent='Nouveau projet';
        f.action='{{ route("admin.projets.store") }}';
        document.getElementById('formMethod').value='POST';
        f.reset();
    }
    d.showModal();
}
</script>
@endpush
