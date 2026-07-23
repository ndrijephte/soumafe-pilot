@extends('layouts.app')
@section('titre', 'Objectifs individuels')
@push('styles')
<style>
.filtres{display:flex;gap:8px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.filtres select{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.filtres .btn{padding:6px 14px;font-size:12px}
.collab-section{margin-bottom:20px}
.collab-header{display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:linear-gradient(135deg,#f0f4f8,#e2e8f0);border-radius:10px 10px 0 0;border:1px solid var(--bord);border-bottom:none}
.collab-header h4{font-size:14px;color:var(--bleu);margin:0}
.pond-total{font-size:11px;font-weight:600;padding:3px 10px;border-radius:8px}
.pond-ok{background:#dcfce7;color:var(--ok)}.pond-warn{background:#fef3c7;color:#b45309}.pond-ko{background:#fee2e2;color:var(--ko)}
.obj-table{width:100%;border-collapse:collapse;font-size:12.5px;border:1px solid var(--bord);border-top:none}
.obj-table th{text-align:left;padding:7px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase}
.obj-table td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.obj-table tr:hover{background:#f8fafc}
.bar-wrap{background:#e2e8f0;border-radius:4px;height:8px;min-width:60px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px}
.bar-ok{background:var(--ok)}.bar-warn{background:var(--warn)}.bar-ko{background:var(--ko)}.bar-accent{background:var(--accent)}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}.b-att{background:#e2e8f0;color:#475569}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11px}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:500px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:600;text-transform:uppercase}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog input:focus,dialog select:focus,dialog textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.req{color:var(--ko)}
.score-global{display:flex;gap:12px;align-items:center;margin-bottom:16px;padding:12px 16px;background:var(--carte);border:1px solid var(--bord);border-radius:12px}
.score-global .val{font-size:22px;font-weight:700;color:var(--bleu)}
.score-global .lab{font-size:11px;color:var(--gris);text-transform:uppercase}
@media(max-width:768px){.form-row{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
<h2>Objectifs individuels</h2>
<div class="crumb">Suivi &rsaquo; Objectifs par collaborateur</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

<form class="filtres" method="GET" action="{{ route('objectifs.index') }}">
    <select name="periode">
        @forelse($periodes as $p)
        <option value="{{ $p }}" {{ $periode === $p ? 'selected' : '' }}>{{ $p }}</option>
        @empty
        <option value="{{ now()->format('Y-\TQ') }}">{{ now()->format('Y-\TQ') }}</option>
        @endforelse
    </select>
    @if(auth()->user()->estPilote() && isset($collaborateurs))
    <select name="user_id">
        <option value="">Tous les collaborateurs</option>
        @foreach($collaborateurs as $c)
        <option value="{{ $c->id }}" {{ ($filtreUser ?? '') == $c->id ? 'selected' : '' }}>{{ $c->nomComplet() }}</option>
        @endforeach
    </select>
    @endif
    <button class="btn" style="background:var(--accent);color:#fff;border:none;border-radius:8px;cursor:pointer">Filtrer</button>
</form>

@if(auth()->user()->estPilote())
{{-- Vue pilote : par collaborateur --}}
<div style="margin-bottom:14px;display:flex;gap:8px">
    <button class="btn" onclick="document.getElementById('dlg-obj').showModal()">+ Ajouter un objectif</button>
</div>

@if(isset($parCollab) && $parCollab->count() > 0)
@foreach($parCollab as $userId => $objs)
@php
    $collab = $objs->first()->user;
    $sommePond = $objs->sum('ponderation');
    $scoreGlobal = $sommePond > 0 ? round($objs->sum(fn($o) => $o->avancement * $o->ponderation / 100)) : 0;
    $pondClass = $sommePond === 100 ? 'pond-ok' : ($sommePond > 100 ? 'pond-ko' : 'pond-warn');
@endphp
<div class="collab-section">
    <div class="collab-header">
        <h4>{{ $collab->nomComplet() }} <span style="font-size:11px;color:var(--gris);font-weight:400">— Score global : {{ $scoreGlobal }}%</span></h4>
        <span class="pond-total {{ $pondClass }}">Pond&eacute;ration : {{ $sommePond }}%</span>
    </div>
    <table class="obj-table">
    <thead><tr><th>Objectif</th><th>Pond.</th><th>Avancement</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($objs as $o)
    @php
        $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
        $statutBadge = match($o->statut_validation) { 'valide' => 'b-ok', 'rejete' => 'b-ko', default => 'b-att' };
    @endphp
    <tr>
        <td><strong>{{ $o->libelle }}</strong>@if($o->commentaire_pilote)<br><span style="font-size:11px;color:var(--gris)">{{ $o->commentaire_pilote }}</span>@endif</td>
        <td style="font-weight:600">{{ $o->ponderation }}%</td>
        <td>
            <div style="display:flex;align-items:center;gap:6px">
                <div class="bar-wrap" style="width:80px"><div class="bar-fill {{ $barClass }}" style="width:{{ $o->avancement }}%"></div></div>
                <span style="font-size:11px;font-weight:600">{{ $o->avancement }}%</span>
            </div>
        </td>
        <td><span class="badge {{ $statutBadge }}">{{ ucfirst(str_replace('_', ' ', $o->statut_validation)) }}</span></td>
        <td style="white-space:nowrap">
            <button class="btn sm sec" onclick="editObj({{ $o->id }}, {{ json_encode($o->libelle) }}, {{ $o->ponderation }}, {{ $o->avancement }}, '{{ $o->statut_validation }}', {{ json_encode($o->commentaire_pilote ?? '') }})">Modifier</button>
            <form method="POST" action="{{ route('objectifs.destroy', $o) }}" style="display:inline" onsubmit="return confirm('Supprimer cet objectif ?')">@csrf <input type="hidden" name="_method" value="DELETE"><button class="btn sm danger">Suppr.</button></form>
        </td>
    </tr>
    @endforeach
    </tbody>
    </table>
</div>
@endforeach
@else
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun objectif pour cette p&eacute;riode.</div>
@endif

@else
{{-- Vue collaborateur --}}
@if($objectifs->count() > 0)
@php
    $sommePond = $objectifs->sum('ponderation');
    $scoreGlobal = $sommePond > 0 ? round($objectifs->sum(fn($o) => $o->avancement * $o->ponderation / 100)) : 0;
@endphp
<div class="score-global">
    <div><div class="val">{{ $scoreGlobal }}%</div><div class="lab">Score global</div></div>
    <div class="bar-wrap" style="flex:1;height:12px"><div class="bar-fill bar-accent" style="width:{{ $scoreGlobal }}%"></div></div>
</div>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="obj-table" style="border:none">
<thead><tr><th>Objectif</th><th>Pond.</th><th>Avancement</th><th>Statut</th><th>Action</th></tr></thead>
<tbody>
@foreach($objectifs as $o)
@php
    $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
    $statutBadge = match($o->statut_validation) { 'valide' => 'b-ok', 'rejete' => 'b-ko', default => 'b-att' };
@endphp
<tr>
    <td><strong>{{ $o->libelle }}</strong>@if($o->commentaire_pilote)<br><span style="font-size:11px;color:var(--gris)">Note : {{ $o->commentaire_pilote }}</span>@endif</td>
    <td style="font-weight:600">{{ $o->ponderation }}%</td>
    <td>
        <div style="display:flex;align-items:center;gap:6px">
            <div class="bar-wrap" style="width:80px"><div class="bar-fill {{ $barClass }}" style="width:{{ $o->avancement }}%"></div></div>
            <span style="font-size:11px;font-weight:600">{{ $o->avancement }}%</span>
        </div>
    </td>
    <td><span class="badge {{ $statutBadge }}">{{ ucfirst(str_replace('_', ' ', $o->statut_validation)) }}</span></td>
    <td>
        @if($o->statut_validation !== 'rejete')
        <form method="POST" action="{{ route('objectifs.update', $o) }}" style="display:flex;gap:4px;align-items:center">
            @csrf <input type="hidden" name="_method" value="PUT">
            <input type="number" name="avancement" value="{{ $o->avancement }}" min="0" max="100" style="width:60px;padding:4px 6px;border:1px solid var(--bord);border-radius:6px;font-size:12px">
            <button class="btn sm">OK</button>
        </form>
        @else
        <span style="font-size:11px;color:var(--gris)">Rejet&eacute;</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
@else
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun objectif d&eacute;fini pour cette p&eacute;riode.</div>
@endif
@endif

@if(auth()->user()->estPilote())
{{-- Dialog ajout objectif --}}
<dialog id="dlg-obj">
    <h3 style="color:var(--bleu);margin-bottom:4px">Ajouter un objectif</h3>
    <form method="POST" action="{{ route('objectifs.store') }}">@csrf
        <label>Collaborateur <span class="req">*</span></label>
        <select name="user_id" required>
            <option value="">— Choisir —</option>
            @foreach($collaborateurs as $c)<option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>@endforeach
        </select>
        <label>Objectif <span class="req">*</span></label>
        <input name="libelle" required placeholder="Ex : Finaliser le module facturation">
        <div class="form-row">
            <div>
                <label>Pond&eacute;ration (%) <span class="req">*</span></label>
                <input type="number" name="ponderation" min="0" max="100" required value="20">
            </div>
            <div>
                <label>P&eacute;riode <span class="req">*</span></label>
                <input name="periode" required value="{{ $periode }}" placeholder="Ex : 2026-T3">
            </div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Ajouter</button>
        </div>
    </form>
</dialog>

{{-- Dialog modifier objectif --}}
<dialog id="dlg-edit">
    <h3 style="color:var(--bleu);margin-bottom:4px">Modifier l'objectif</h3>
    <form method="POST" id="form-edit">@csrf <input type="hidden" name="_method" value="PUT">
        <label>Objectif <span class="req">*</span></label>
        <input name="libelle" id="ed-libelle" required>
        <div class="form-row">
            <div>
                <label>Pond&eacute;ration (%) <span class="req">*</span></label>
                <input type="number" name="ponderation" id="ed-pond" min="0" max="100" required>
            </div>
            <div>
                <label>Avancement (%)</label>
                <input type="number" name="avancement" id="ed-avanc" min="0" max="100">
            </div>
        </div>
        <label>Validation</label>
        <select name="statut_validation" id="ed-statut">
            <option value="en_attente">En attente</option>
            <option value="valide">Valid&eacute;</option>
            <option value="rejete">Rejet&eacute;</option>
        </select>
        <label>Commentaire pilote</label>
        <textarea name="commentaire_pilote" id="ed-comm" rows="2" placeholder="Observation, feedback..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endif
@endsection

@push('scripts')
<script>
function editObj(id, libelle, pond, avanc, statut, comm) {
    document.getElementById('form-edit').action = '/objectifs/' + id;
    document.getElementById('ed-libelle').value = libelle;
    document.getElementById('ed-pond').value = pond;
    document.getElementById('ed-avanc').value = avanc;
    document.getElementById('ed-statut').value = statut;
    document.getElementById('ed-comm').value = comm;
    document.getElementById('dlg-edit').showModal();
}
</script>
@endpush
