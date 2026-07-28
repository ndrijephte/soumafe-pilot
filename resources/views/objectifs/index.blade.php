@extends('layouts.app')
@section('titre', 'Objectifs')
@push('styles')
<style>
.filtres{display:flex;gap:8px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.filtres select{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.tabs{display:flex;gap:0;margin-bottom:16px;border-bottom:2px solid var(--bord)}
.tab{padding:8px 18px;font-size:13px;font-weight:600;color:var(--gris);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .2s}
.tab.actif{color:var(--accent);border-bottom-color:var(--accent)}
.tab-panel{display:none}.tab-panel.actif{display:block}
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
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}.b-att{background:#e2e8f0;color:#475569}.b-accent{background:#dbeafe;color:#1d4ed8}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11px}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin:10px 0 4px}
dialog input,dialog select{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog input:focus,dialog select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.req{color:var(--ko)}
.score-global{display:flex;gap:12px;align-items:center;margin-bottom:16px;padding:12px 16px;background:var(--carte);border:1px solid var(--bord);border-radius:12px}
.score-global .val{font-size:22px;font-weight:700;color:var(--bleu)}
.score-global .lab{font-size:11px;color:var(--gris);text-transform:uppercase}
.import-card{background:var(--carte);border:1px dashed var(--bord);border-radius:12px;padding:14px 16px;margin-bottom:16px}
.import-card h4{font-size:13px;color:var(--gris);margin:0 0 8px}
.hint{font-size:10.5px;color:var(--gris);margin-top:2px}
.projet-tag{background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:6px;font-size:10px;font-weight:600;margin-left:4px}
.eval-badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.eval-atteint{background:#dcfce7;color:var(--ok)}.eval-partiel{background:#fef3c7;color:#b45309}.eval-non_atteint{background:#fee2e2;color:var(--ko)}.eval-en_cours{background:#e2e8f0;color:#475569}
.echeance-past{color:var(--ko);font-weight:600}
.jours-restants{font-size:10px;color:var(--gris)}
@media(max-width:768px){.form-row{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
<h2>Objectifs</h2>
<div class="crumb">Suivi &rsaquo; Objectifs individuels &amp; projets</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())
<div class="alerte ko">
    <ul style="margin:0;padding-left:16px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

{{-- Filtres --}}
<form class="filtres" method="GET" action="{{ route('objectifs.index') }}">
    <select name="periode">
        @foreach($moisMenu as $val => $label)
        <option value="{{ $val }}" {{ $periode === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @if(auth()->user()->estPilote() && isset($collaborateurs))
    <select name="user_id">
        <option value="">Tous les collaborateurs</option>
        @foreach($collaborateurs as $c)
        <option value="{{ $c->id }}" {{ ($filtreUser ?? '') == $c->id ? 'selected' : '' }}>{{ $c->nomComplet() }}</option>
        @endforeach
    </select>
    <select name="projet_id">
        <option value="">Tous les projets</option>
        @foreach($projets as $p)
        <option value="{{ $p->id }}" {{ ($filtreProjet ?? '') == $p->id ? 'selected' : '' }}>{{ $p->libelle }}</option>
        @endforeach
    </select>
    @endif
    <button class="btn">Filtrer</button>
</form>

@if(auth()->user()->estPilote())
{{-- ==================== VUE PILOTE ==================== --}}

{{-- Score équipe --}}
<div class="score-global">
    <div><div class="val">{{ $scoreEquipe }}%</div><div class="lab">Score &eacute;quipe (p&eacute;riode)</div></div>
    <div class="bar-wrap" style="flex:1;height:12px"><div class="bar-fill bar-accent" style="width:{{ $scoreEquipe }}%"></div></div>
    <form method="POST" action="{{ route('objectifs.evaluer') }}" style="margin:0">
        @csrf
        <input type="hidden" name="periode" value="{{ $periode }}">
        <button class="btn sm sec" title="Lancer l'&eacute;valuation automatique des objectifs de cette p&eacute;riode">&#x2713; &Eacute;valuer la p&eacute;riode</button>
    </form>
</div>

{{-- Onglets + bouton ajout --}}
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:8px">
    <div class="tabs" style="margin-bottom:0">
        <div class="tab actif" onclick="switchTab('indiv')">Objectifs individuels</div>
        <div class="tab" onclick="switchTab('projet')">Objectifs projet</div>
    </div>
    <button class="btn" onclick="resetForm();document.getElementById('dlg-obj').showModal()">+ Assigner un objectif</button>
</div>

{{-- ===== PANEL INDIVIDUEL ===== --}}
<div class="tab-panel actif" id="panel-indiv">
@if(isset($parCollab) && $parCollab->count() > 0)
@foreach($parCollab as $userId => $objs)
@php
    $collab = $objs->first()->user;
    $sommePond = $objs->sum('ponderation');
    $score = \App\Models\Objectif::scoreGroupe($objs);
    $pondClass = $sommePond === 100 ? 'pond-ok' : ($sommePond > 100 ? 'pond-ko' : 'pond-warn');
@endphp
<div class="collab-section">
    <div class="collab-header">
        <h4>{{ $collab->nomComplet() }} <span style="font-size:11px;color:var(--gris);font-weight:400">— Score : {{ $score }}%</span></h4>
        <span class="pond-total {{ $pondClass }}">Pond. : {{ $sommePond }}%</span>
    </div>
    <table class="obj-table">
    <thead><tr><th>Objectif</th><th>Projet</th><th>Pond.</th><th>Avancement</th><th>P&eacute;riode</th><th>&Eacute;val.</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($objs as $o)
    @php
        $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
        $statutBadge = match($o->statut_validation) { 'valide' => 'b-ok', 'rejete' => 'b-ko', default => 'b-att' };
        $evalClass = 'eval-' . ($o->evaluation ?? 'en_cours');
        $echPast = $o->date_echeance && $o->date_echeance->isPast() && $o->avancement < 100;
        $jRest = $o->joursRestants();
    @endphp
    <tr>
        <td>
            <strong>{{ $o->libelle }}</strong>
            @if($o->commentaire_pilote)<br><span style="font-size:11px;color:var(--gris)">{{ $o->commentaire_pilote }}</span>@endif
        </td>
        <td>@if($o->projet)<span class="projet-tag">{{ $o->projet->libelle }}</span>@else — @endif</td>
        <td style="font-weight:600">{{ $o->ponderation }}%</td>
        <td>
            <div style="display:flex;align-items:center;gap:6px">
                <div class="bar-wrap" style="width:60px"><div class="bar-fill {{ $barClass }}" style="width:{{ $o->avancement }}%"></div></div>
                <span style="font-size:11px;font-weight:600">{{ $o->avancement }}%</span>
            </div>
        </td>
        <td>
            @if($o->date_debut){{ $o->date_debut->format('d/m') }}@endif
            @if($o->date_debut && $o->date_echeance)→@endif
            @if($o->date_echeance)<span class="{{ $echPast ? 'echeance-past' : '' }}">{{ $o->date_echeance->format('d/m') }}</span>@endif
            @if($jRest !== null && $jRest > 0 && $o->avancement < 100)<br><span class="jours-restants">{{ $jRest }}j restant(s)</span>@endif
            @if(!$o->date_debut && !$o->date_echeance)—@endif
        </td>
        <td><span class="eval-badge {{ $evalClass }}">{{ ucfirst(str_replace('_', ' ', $o->evaluation ?? 'en cours')) }}</span></td>
        <td><span class="badge {{ $statutBadge }}">{{ ucfirst(str_replace('_', ' ', $o->statut_validation)) }}</span></td>
        <td style="white-space:nowrap">
            <button class="btn sm sec" onclick="editObj({{ $o->id }}, 'individuel', {{ $o->user_id }}, {{ json_encode($o->libelle) }}, '{{ $o->projet_id ?? '' }}', {{ $o->ponderation }}, {{ $o->avancement }}, '{{ $o->date_debut ? $o->date_debut->format('Y-m-d') : '' }}', '{{ $o->date_echeance ? $o->date_echeance->format('Y-m-d') : '' }}', '{{ $o->evaluation ?? '' }}', '{{ $o->statut_validation }}', {{ json_encode($o->commentaire_pilote ?? '') }})">Modifier</button>
            @if($o->statut_validation !== 'valide')
            <form method="POST" action="{{ route('objectifs.valider', $o) }}" style="display:inline"><button class="btn sm" style="background:var(--ok)">Valider</button>@csrf</form>
            @endif
            <form method="POST" action="{{ route('objectifs.destroy', $o) }}" style="display:inline" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn sm danger">Suppr.</button></form>
        </td>
    </tr>
    @endforeach
    </tbody>
    </table>
</div>
@endforeach
@else
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun objectif individuel pour cette p&eacute;riode.</div>
@endif
</div>

{{-- ===== PANEL PROJET ===== --}}
<div class="tab-panel" id="panel-projet">
@if(isset($parProjet) && $parProjet->count() > 0)
@foreach($parProjet as $projetId => $objs)
@php
    $proj = $objs->first()->projet;
    $scoreProj = \App\Models\Objectif::scoreGroupe($objs);
@endphp
<div class="collab-section">
    <div class="collab-header">
        <h4>{{ $proj?->libelle ?? 'Sans projet' }} <span style="font-size:11px;color:var(--gris);font-weight:400">— Score projet : {{ $scoreProj }}%</span></h4>
    </div>
    <table class="obj-table">
    <thead><tr><th>Objectif</th><th>Pond.</th><th>Avancement</th><th>P&eacute;riode</th><th>&Eacute;val.</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($objs as $o)
    @php
        $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
        $evalClass = 'eval-' . ($o->evaluation ?? 'en_cours');
        $echPast = $o->date_echeance && $o->date_echeance->isPast() && $o->avancement < 100;
        $jRest = $o->joursRestants();
    @endphp
    <tr>
        <td><strong>{{ $o->libelle }}</strong>@if($o->commentaire_pilote)<br><span style="font-size:11px;color:var(--gris)">{{ $o->commentaire_pilote }}</span>@endif</td>
        <td style="font-weight:600">{{ $o->ponderation }}%</td>
        <td>
            <div style="display:flex;align-items:center;gap:6px">
                <div class="bar-wrap" style="width:60px"><div class="bar-fill {{ $barClass }}" style="width:{{ $o->avancement }}%"></div></div>
                <span style="font-size:11px;font-weight:600">{{ $o->avancement }}%</span>
            </div>
        </td>
        <td>
            @if($o->date_debut){{ $o->date_debut->format('d/m') }}@endif
            @if($o->date_debut && $o->date_echeance)→@endif
            @if($o->date_echeance)<span class="{{ $echPast ? 'echeance-past' : '' }}">{{ $o->date_echeance->format('d/m') }}</span>@endif
            @if($jRest !== null && $jRest > 0 && $o->avancement < 100)<br><span class="jours-restants">{{ $jRest }}j restant(s)</span>@endif
            @if(!$o->date_debut && !$o->date_echeance)—@endif
        </td>
        <td><span class="eval-badge {{ $evalClass }}">{{ ucfirst(str_replace('_', ' ', $o->evaluation ?? 'en cours')) }}</span></td>
        <td style="white-space:nowrap">
            <button class="btn sm sec" onclick="editObj({{ $o->id }}, 'projet', {{ $o->user_id }}, {{ json_encode($o->libelle) }}, '{{ $o->projet_id ?? '' }}', {{ $o->ponderation }}, {{ $o->avancement }}, '{{ $o->date_debut ? $o->date_debut->format('Y-m-d') : '' }}', '{{ $o->date_echeance ? $o->date_echeance->format('Y-m-d') : '' }}', '{{ $o->evaluation ?? '' }}', '{{ $o->statut_validation }}', {{ json_encode($o->commentaire_pilote ?? '') }})">Modifier</button>
            @if($o->statut_validation !== 'valide')
            <form method="POST" action="{{ route('objectifs.valider', $o) }}" style="display:inline"><button class="btn sm" style="background:var(--ok)">Valider</button>@csrf</form>
            @endif
            <form method="POST" action="{{ route('objectifs.destroy', $o) }}" style="display:inline" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')<button class="btn sm danger">Suppr.</button></form>
        </td>
    </tr>
    @endforeach
    </tbody>
    </table>
</div>
@endforeach
@else
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun objectif projet pour cette p&eacute;riode.</div>
@endif
</div>

@else
{{-- ==================== VUE COLLABORATEUR ==================== --}}

{{-- Mes objectifs individuels --}}
@if(isset($mesIndiv) && $mesIndiv->count() > 0)
@php
    $scoreIndiv = \App\Models\Objectif::scoreGroupe($mesIndiv);
@endphp
<div class="score-global">
    <div><div class="val">{{ $scoreIndiv }}%</div><div class="lab">Mon score individuel</div></div>
    <div class="bar-wrap" style="flex:1;height:12px"><div class="bar-fill bar-accent" style="width:{{ $scoreIndiv }}%"></div></div>
</div>

<h3 style="font-size:14px;color:var(--bleu);margin-bottom:8px">Mes objectifs individuels</h3>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="obj-table" style="border:none">
<thead><tr><th>Objectif</th><th>Projet</th><th>Pond.</th><th>Avancement</th><th>P&eacute;riode</th><th>&Eacute;val.</th><th>Action</th></tr></thead>
<tbody>
@foreach($mesIndiv as $o)
@php
    $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
    $evalClass = 'eval-' . ($o->evaluation ?? 'en_cours');
    $echPast = $o->date_echeance && $o->date_echeance->isPast() && $o->avancement < 100;
    $jRest = $o->joursRestants();
@endphp
<tr>
    <td>
        <strong>{{ $o->libelle }}</strong>
        @if($o->commentaire_pilote)<br><span style="font-size:11px;color:var(--gris)">Note : {{ $o->commentaire_pilote }}</span>@endif
    </td>
    <td>@if($o->projet)<span class="projet-tag">{{ $o->projet->libelle }}</span>@else — @endif</td>
    <td style="font-weight:600">{{ $o->ponderation }}%</td>
    <td>
        <div style="display:flex;align-items:center;gap:6px">
            <div class="bar-wrap" style="width:60px"><div class="bar-fill {{ $barClass }}" style="width:{{ $o->avancement }}%"></div></div>
            <span style="font-size:11px;font-weight:600">{{ $o->avancement }}%</span>
        </div>
    </td>
    <td>
        @if($o->date_debut){{ $o->date_debut->format('d/m') }}@endif
        @if($o->date_debut && $o->date_echeance)→@endif
        @if($o->date_echeance)<span class="{{ $echPast ? 'echeance-past' : '' }}">{{ $o->date_echeance->format('d/m/Y') }}</span>@endif
        @if($jRest !== null && $jRest > 0 && $o->avancement < 100)<br><span class="jours-restants">{{ $jRest }}j restant(s)</span>@endif
        @if(!$o->date_debut && !$o->date_echeance)—@endif
    </td>
    <td><span class="eval-badge {{ $evalClass }}">{{ ucfirst(str_replace('_', ' ', $o->evaluation ?? 'en cours')) }}</span></td>
    <td>
        @if($o->statut_validation !== 'rejete')
        <form method="POST" action="{{ route('objectifs.update', $o) }}" style="display:flex;gap:4px;align-items:center">
            @csrf @method('PUT')
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
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun objectif individuel pour cette p&eacute;riode.</div>
@endif

{{-- Objectifs de mes projets --}}
@if(isset($objProjets) && $objProjets->count() > 0)
<h3 style="font-size:14px;color:var(--bleu);margin:20px 0 8px">Objectifs de mes projets</h3>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="obj-table" style="border:none">
<thead><tr><th>Projet</th><th>Objectif</th><th>Pond.</th><th>Avancement</th><th>P&eacute;riode</th><th>&Eacute;val.</th></tr></thead>
<tbody>
@foreach($objProjets as $o)
@php
    $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
    $evalClass = 'eval-' . ($o->evaluation ?? 'en_cours');
    $jRest = $o->joursRestants();
@endphp
<tr>
    <td><span class="projet-tag">{{ $o->projet?->libelle ?? '—' }}</span></td>
    <td><strong>{{ $o->libelle }}</strong></td>
    <td style="font-weight:600">{{ $o->ponderation }}%</td>
    <td>
        <div style="display:flex;align-items:center;gap:6px">
            <div class="bar-wrap" style="width:60px"><div class="bar-fill {{ $barClass }}" style="width:{{ $o->avancement }}%"></div></div>
            <span style="font-size:11px;font-weight:600">{{ $o->avancement }}%</span>
        </div>
    </td>
    <td>
        @if($o->date_debut){{ $o->date_debut->format('d/m') }}@endif
        @if($o->date_debut && $o->date_echeance)→@endif
        @if($o->date_echeance){{ $o->date_echeance->format('d/m/Y') }}@endif
        @if($jRest !== null && $jRest > 0 && $o->avancement < 100)<br><span class="jours-restants">{{ $jRest }}j</span>@endif
        @if(!$o->date_debut && !$o->date_echeance)—@endif
    </td>
    <td><span class="eval-badge {{ $evalClass }}">{{ ucfirst(str_replace('_', ' ', $o->evaluation ?? 'en cours')) }}</span></td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endif

@endif

@if(auth()->user()->estPilote())
<dialog id="dlg-obj" style="width:540px">
    <h3 id="formTitre" style="color:var(--bleu);margin-bottom:8px">Assigner un objectif</h3>
    <form method="POST" id="formObj" action="{{ route('objectifs.store') }}">
        @csrf
        <input type="hidden" name="_editing" id="fEditing" value="">

        <div class="form-row">
            <div>
                <label>Type <span class="req">*</span></label>
                <select name="type" id="fType" onchange="toggleType()">
                    <option value="individuel">Individuel</option>
                    <option value="projet">Projet</option>
                </select>
            </div>
            <div id="wrapUser">
                <label>Collaborateur <span class="req">*</span></label>
                <select name="user_id" id="fUser" required>
                    <option value="">— Choisir —</option>
                    @foreach($collaborateurs as $c)
                    <option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label>Objectif <span class="req">*</span></label>
        <input name="libelle" id="fLib" required maxlength="255" placeholder="Ex : Livrer le module facturation" style="width:100%">

        <div class="form-row">
            <div id="wrapProjet">
                <label id="labelProjet">Projet</label>
                <select name="projet_id" id="fProjet">
                    <option value="">— Aucun —</option>
                    @foreach($projets as $p)
                    <option value="{{ $p->id }}">{{ $p->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>P&eacute;riode <span class="req">*</span></label>
                <select name="periode" id="fPeriode" required>
                    @foreach($moisMenu as $val => $label)
                    <option value="{{ $val }}" {{ $periode === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Pond&eacute;ration (%) <span class="req">*</span></label>
                <input type="number" name="ponderation" id="fPond" min="0" max="100" required value="20">
            </div>
            <div>
                <label>Date de d&eacute;but <span class="req">*</span></label>
                <input type="date" name="date_debut" id="fDebut" required value="{{ now()->format('Y-m-d') }}">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>&Eacute;ch&eacute;ance <span class="req">*</span></label>
                <input type="date" name="date_echeance" id="fEcheance" required>
            </div>
            <div>&nbsp;</div>
        </div>

        <div id="editFields" style="display:none">
            <div class="form-row">
                <div>
                    <label>Avancement (%)</label>
                    <input type="number" name="avancement" id="fAvanc" min="0" max="100" value="0">
                </div>
                <div>
                    <label>&Eacute;valuation</label>
                    <select name="evaluation" id="fEval">
                        <option value="">— Auto —</option>
                        <option value="en_cours">En cours</option>
                        <option value="atteint">Atteint</option>
                        <option value="partiel">Partiel</option>
                        <option value="non_atteint">Non atteint</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label>Validation</label>
                    <select name="statut_validation" id="fStatut">
                        <option value="en_attente">En attente</option>
                        <option value="valide">Valid&eacute;</option>
                        <option value="rejete">Rejet&eacute;</option>
                    </select>
                </div>
                <div>
                    <label>Commentaire pilote</label>
                    <textarea name="commentaire_pilote" id="fComm" rows="2" maxlength="500" style="width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;resize:vertical"></textarea>
                </div>
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="document.getElementById('dlg-obj').close()">Annuler</button>
            <button class="btn" id="btnSubmit">Ajouter</button>
        </div>
    </form>
</dialog>
@endif
@endsection

@push('scripts')
<script>
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('actif'); });
    document.querySelectorAll('.tab-panel').forEach(function(p) { p.classList.remove('actif'); });
    document.getElementById('panel-' + tab).classList.add('actif');
    event.target.classList.add('actif');
    // Synchroniser le type du formulaire avec l'onglet sélectionné
    var fType = document.getElementById('fType');
    if (fType && !document.getElementById('fEditing').value) {
        fType.value = tab === 'projet' ? 'projet' : 'individuel';
        toggleType();
    }
}

function toggleType() {
    var t = document.getElementById('fType').value;
    var wrapUser = document.getElementById('wrapUser');
    var fUser = document.getElementById('fUser');
    var fProjet = document.getElementById('fProjet');
    var labelProjet = document.getElementById('labelProjet');

    if (t === 'individuel') {
        wrapUser.style.display = '';
        fUser.setAttribute('required', 'required');
        fProjet.removeAttribute('required');
        labelProjet.innerHTML = 'Projet';
    } else {
        wrapUser.style.display = 'none';
        fUser.removeAttribute('required');
        fUser.value = '';
        fProjet.setAttribute('required', 'required');
        labelProjet.innerHTML = 'Projet <span class="req">*</span>';
    }
}

function editObj(id, type, userId, libelle, projetId, pond, avanc, debut, echeance, evaluation, statut, comm) {
    document.getElementById('formTitre').textContent = 'Modifier l\'objectif';
    document.getElementById('formObj').action = '{{ url("objectifs") }}/' + id;
    document.getElementById('fEditing').value = id;
    document.getElementById('fType').value = type;
    toggleType();
    document.getElementById('fUser').value = userId;
    document.getElementById('fLib').value = libelle;
    document.getElementById('fProjet').value = projetId;
    document.getElementById('fPond').value = pond;
    document.getElementById('fAvanc').value = avanc;
    document.getElementById('fDebut').value = debut;
    document.getElementById('fEcheance').value = echeance;
    document.getElementById('fEval').value = evaluation;
    document.getElementById('fStatut').value = statut;
    document.getElementById('fComm').value = comm;
    document.getElementById('editFields').style.display = '';
    document.getElementById('btnSubmit').textContent = 'Enregistrer';
    var mf = document.querySelector('#formObj input[name="_method"]');
    if (!mf) { mf = document.createElement('input'); mf.type='hidden'; mf.name='_method'; document.getElementById('formObj').appendChild(mf); }
    mf.value = 'PUT';
    document.getElementById('dlg-obj').showModal();
    document.getElementById('fLib').focus();
}

function resetForm() {
    document.getElementById('formTitre').textContent = 'Ajouter un objectif';
    document.getElementById('formObj').action = '{{ route("objectifs.store") }}';
    document.getElementById('fEditing').value = '';
    document.getElementById('fType').value = 'individuel';
    toggleType();
    document.getElementById('fUser').value = '';
    document.getElementById('fLib').value = '';
    document.getElementById('fProjet').value = '';
    document.getElementById('fPond').value = '20';
    document.getElementById('fAvanc').value = '0';
    document.getElementById('fDebut').value = '{{ now()->format("Y-m-d") }}';
    document.getElementById('fEcheance').value = '';
    document.getElementById('fEval').value = '';
    document.getElementById('fStatut').value = 'en_attente';
    document.getElementById('fComm').value = '';
    document.getElementById('editFields').style.display = 'none';
    document.getElementById('btnSubmit').textContent = 'Ajouter';
    var mf = document.querySelector('#formObj input[name="_method"]');
    if (mf) mf.remove();
}
</script>
@endpush
