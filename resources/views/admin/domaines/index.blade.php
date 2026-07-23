@extends('layouts.app')
@section('titre', 'Administration — Domaines')
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
dialog{border:none;border-radius:14px;padding:24px;width:420px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(0,0,0,.4)}
dialog label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:4px}
dialog input{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
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

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <span style="font-size:13px;color:var(--gris)">{{ $domaines->count() }} domaine(s)</span>
    <button class="btn" onclick="openDlg()">+ Nouveau domaine</button>
</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="admin-tbl">
<thead><tr><th>Libell&eacute;</th><th>Services</th><th>Actions</th></tr></thead>
<tbody>
@forelse($domaines as $d)
<tr>
    <td><strong>{{ $d->libelle }}</strong></td>
    <td>{{ $d->services_count }}</td>
    <td style="white-space:nowrap">
        <button class="btn sm sec" onclick="openDlg({{ $d->id }}, '{{ addslashes($d->libelle) }}')">Modifier</button>
        @if($d->services_count === 0)
        <form method="POST" action="{{ route('admin.domaines.destroy', $d) }}" style="display:inline">@csrf @method('DELETE')
            <button class="btn sm danger" onclick="return confirm('Supprimer ce domaine ?')">Supprimer</button>
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

<dialog id="dlg">
<form method="POST" id="formDom">@csrf
    <input type="hidden" name="_method" id="fMethod" value="POST">
    <h3 id="dlgTitre" style="color:var(--bleu);margin-bottom:14px">Nouveau domaine</h3>
    <label>Libell&eacute;</label>
    <input name="libelle" id="fLib" required maxlength="100" value="{{ old('libelle') }}">
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
    const f=document.getElementById('formDom');
    if(id){
        f.action='{{ url("admin/domaines") }}/'+id;
        document.getElementById('fMethod').value='PUT';
        document.getElementById('dlgTitre').textContent='Modifier le domaine';
        document.getElementById('fLib').value=lib;
    } else {
        f.action='{{ route("admin.domaines.store") }}';
        document.getElementById('fMethod').value='POST';
        document.getElementById('dlgTitre').textContent='Nouveau domaine';
        f.reset();
    }
    document.getElementById('dlg').showModal();
}
@if($errors->any())
document.addEventListener('DOMContentLoaded', function(){ openDlg(); });
@endif
</script>
@endpush
