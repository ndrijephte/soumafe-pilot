@extends('layouts.app')
@section('sousTitre', 'Objectifs')
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
.bar-ok{background:var(--ok)}.bar-warn{background:var(--warn)}.bar-ko{background:var(--ko)}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}.b-att{background:#e2e8f0;color:#475569}.b-accent{background:#dbeafe;color:#1d4ed8}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11px}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin:10px 0 4px}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;box-sizing:border-box}
dialog input:focus,dialog select:focus,dialog textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.req{color:var(--ko)}
.score-global{display:flex;gap:12px;align-items:center;margin-bottom:16px;padding:12px 16px;background:var(--carte);border:1px solid var(--bord);border-radius:12px}
.score-global .val{font-size:22px;font-weight:700;color:var(--bleu)}
.score-global .lab{font-size:11px;color:var(--gris);text-transform:uppercase}
.projet-tag{background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:6px;font-size:10px;font-weight:600;margin-left:4px}
.eval-badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.eval-atteint{background:#dcfce7;color:var(--ok)}.eval-partiel{background:#fef3c7;color:#b45309}.eval-non_atteint{background:#fee2e2;color:var(--ko)}.eval-en_cours{background:#e2e8f0;color:#475569}
.echeance-past{color:var(--ko);font-weight:600}
.jours-restants{font-size:10px;color:var(--gris)}
.hint{font-size:10.5px;color:var(--gris);margin-top:2px}

/* === V2 : KR, courbe, cascade, alertes === */
.alertes-banner{background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px 14px;margin-bottom:14px}
.alertes-banner .al-item{display:flex;align-items:center;gap:8px;font-size:12px;padding:3px 0}
.alertes-banner .al-item svg{flex-shrink:0}
.al-echeance{color:#b45309}.al-retard{color:var(--ko)}.al-stagnation{color:#6b7280}
.expand-btn{background:none;border:none;cursor:pointer;padding:2px;display:flex;align-items:center}
.expand-btn svg{transition:transform .2s}
.expand-btn.open svg{transform:rotate(90deg)}
.kr-panel{display:none;padding:10px 14px;background:#f8fafc;border-bottom:1px solid var(--bord)}
.kr-panel.open{display:block}
.kr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px}
.kr-card{background:var(--carte);border:1px solid var(--bord);border-radius:10px;padding:10px 12px}
.kr-card .kr-title{font-size:12px;font-weight:600;color:var(--bleu);margin-bottom:6px}
.kr-card .kr-vals{font-size:11px;color:var(--gris);margin-bottom:6px}
.kr-card .kr-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;margin-bottom:4px}
.kr-card .kr-bar-fill{height:100%;border-radius:3px}
.kr-card .kr-pct{font-size:10px;font-weight:600;text-align:right}
.kr-actions{display:flex;gap:4px;margin-top:6px}
.taches-liees{margin-top:10px}
.tache-chip{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:11px;margin:2px 4px 2px 0;border:1px solid var(--bord);background:var(--carte)}
.tache-chip.termine{text-decoration:line-through;opacity:.7}
.courbe-container{margin:10px 0}
.courbe-container svg{width:100%;max-width:300px;height:60px}
.prio-badge{padding:1px 6px;border-radius:6px;font-size:9px;font-weight:600;margin-left:4px}
.cascade-indent{display:inline-block;width:14px;height:14px;color:var(--gris);margin-right:2px;vertical-align:middle}
.eval-section{margin-top:10px;padding:10px 12px;background:var(--carte);border:1px solid var(--bord);border-radius:10px}
.eval-section h5{font-size:12px;color:var(--bleu);margin:0 0 8px}
.stars{display:flex;gap:2px}.star{cursor:pointer;padding:2px}
.star svg{fill:none;stroke:var(--gris);stroke-width:2}
.star.filled svg{fill:#f59e0b;stroke:#f59e0b}
/* Anneau KPI */
.anneau-wrap{display:flex;align-items:center;gap:14px;margin-bottom:10px;padding:8px 12px;background:var(--carte);border:1px solid var(--bord);border-radius:10px}
.anneau-legend{display:flex;flex-direction:column;gap:3px}
.anneau-legend-item{display:flex;align-items:center;gap:6px;font-size:11px}
.anneau-legend-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.kr-validation{display:flex;gap:4px;margin-top:6px}
.kr-validation .btn-val{padding:3px 8px;font-size:10px;font-weight:600;border:1px solid var(--bord);border-radius:6px;cursor:pointer;background:#fff}
.btn-val.v-ok{color:var(--ok);border-color:var(--ok)}.btn-val.v-ok:hover{background:#dcfce7}
.btn-val.v-ko{color:var(--ko);border-color:var(--ko)}.btn-val.v-ko:hover{background:#fee2e2}
.btn-val.v-reset{color:var(--gris);border-color:var(--bord)}.btn-val.v-reset:hover{background:#f1f5f9}
.kr-statut{display:inline-block;padding:2px 6px;border-radius:6px;font-size:9px;font-weight:600;margin-top:4px}
.kr-statut.en_attente{background:#e2e8f0;color:#475569}
.kr-statut.atteint{background:#dcfce7;color:var(--ok)}
.kr-statut.non_atteint{background:#fee2e2;color:var(--ko)}
@media(max-width:768px){.form-row{grid-template-columns:1fr}.kr-grid{grid-template-columns:1fr}.obj-table{font-size:11px}.anneau-wrap{flex-direction:column}}
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

@php
$ic = fn(string $d, int $s=17) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
$icChevron = $ic('<polyline points="9 18 15 12 9 6"/>',14);
$icAlert = $ic('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',14);
$icClock = $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',14);
$icTarget = $ic('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',14);
$icLink = $ic('<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>',14);
$icPlus = $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14);
$icStar = $ic('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',14);
$icTrash = $ic('<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>',14);
$icTree = $ic('<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',14);
@endphp

{{-- Alertes intelligentes --}}
@if(isset($alertesObj) && count($alertesObj) > 0)
<div class="alertes-banner">
    @foreach($alertesObj as $al)
    <div class="al-item al-{{ $al['type'] }}">
        @if($al['type']==='retard'){!! $icAlert !!}@elseif($al['type']==='echeance'){!! $icClock !!}@else{!! $icTarget !!}@endif
        <span>{{ $al['msg'] }}</span>
    </div>
    @endforeach
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

<div class="score-global">
    <div><div class="val">{{ $scoreEquipe }}%</div><div class="lab">Score &eacute;quipe (p&eacute;riode)</div></div>
    <form method="POST" action="{{ route('objectifs.evaluer') }}" style="margin:0">
        @csrf
        <input type="hidden" name="periode" value="{{ $periode }}">
        <button class="btn sm sec" title="Lancer l'&eacute;valuation automatique des objectifs de cette p&eacute;riode">&#x2713; &Eacute;valuer la p&eacute;riode</button>
    </form>
</div>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px">
    <button class="btn" onclick="resetForm();document.getElementById('dlg-obj').showModal()">+ Assigner un objectif</button>
</div>

{{-- ===== VUE UNIFIÉE PAR COLLABORATEUR ===== --}}
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
        <h4>{{ $collab->nomComplet() }} <span style="font-size:11px;color:var(--gris);font-weight:400">&mdash; Score : {{ $score }}%</span></h4>
        <span class="pond-total {{ $pondClass }}">Pond. : {{ $sommePond }}%</span>
    </div>
    <table class="obj-table">
    <thead><tr><th style="width:20px"></th><th>Objectif</th><th>Projet</th><th>Pond.</th><th>P&eacute;riode</th><th>&Eacute;val.</th><th>Statut</th><th>Actions</th></tr></thead>
    <tbody>
    @foreach($objs as $o)
    @php
        $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
        $statutBadge = match($o->statut_validation) { 'valide' => 'b-ok', 'rejete' => 'b-ko', default => 'b-att' };
        $evalClass = 'eval-' . ($o->evaluation ?? 'en_cours');
        $echPast = $o->date_echeance && $o->date_echeance->isPast() && $o->avancement < 100;
        $jRest = $o->joursRestants();
        $prio = $o->badgePriorite();
        $hasKRs = isset($hasKR) && $hasKR && $o->resultats->count() > 0;
        $hasTaches = isset($hasPivot) && $hasPivot && $o->tachesLiees->count() > 0;
        $hasHisto = isset($hasHist) && $hasHist && $o->historique->count() > 1;
        $showExpand = $hasKRs || $hasTaches || $hasHisto || (isset($hasKR) && $hasKR);
    @endphp
    <tr>
        <td>
            @if($showExpand)
            <button class="expand-btn" onclick="toggleExpand({{ $o->id }})" id="exp-{{ $o->id }}">{!! $icChevron !!}</button>
            @endif
        </td>
        <td>
            <strong>{{ $o->libelle }}</strong>
            <span class="prio-badge {{ $prio['class'] }}">{{ $prio['label'] }}</span>
            @if(($o->type ?? 'individuel') === 'projet')<span class="projet-tag" style="margin-left:4px">Projet</span>@endif
            @if($o->commentaire_pilote)<br><span style="font-size:11px;color:var(--gris)">{{ $o->commentaire_pilote }}</span>@endif
            @if(!empty($o->alertes()))
            @foreach($o->alertes() as $alrt)
            <br><span style="font-size:10px" class="al-{{ $alrt['type'] }}">{!! $icAlert !!} {{ $alrt['msg'] }}</span>
            @endforeach
            @endif
        </td>
        <td>@if($o->projet)<span class="projet-tag">{{ $o->projet->libelle }}</span>@else &mdash; @endif</td>
        <td style="font-weight:600">{{ $o->ponderation }}%</td>
        <td>
            @if($o->date_debut){{ $o->date_debut->format('d/m') }}@endif
            @if($o->date_debut && $o->date_echeance)&rarr;@endif
            @if($o->date_echeance)<span class="{{ $echPast ? 'echeance-past' : '' }}">{{ $o->date_echeance->format('d/m') }}</span>@endif
            @if($jRest !== null && $jRest > 0 && $o->avancement < 100)<br><span class="jours-restants">{{ $jRest }}j restant(s)</span>@endif
            @if(!$o->date_debut && !$o->date_echeance)&mdash;@endif
        </td>
        <td><span class="eval-badge {{ $evalClass }}">{{ ucfirst(str_replace('_', ' ', $o->evaluation ?? 'en cours')) }}</span></td>
        <td><span class="badge {{ $statutBadge }}">{{ ucfirst(str_replace('_', ' ', $o->statut_validation)) }}</span></td>
        <td style="white-space:nowrap">
            <button class="btn sm sec" onclick="editObj({{ $o->id }}, 'individuel', {{ $o->user_id }}, {{ json_encode($o->libelle) }}, '{{ $o->projet_id ?? '' }}', {{ $o->ponderation }}, {{ $o->avancement }}, '{{ $o->date_debut ? $o->date_debut->format('Y-m-d') : '' }}', '{{ $o->date_echeance ? $o->date_echeance->format('Y-m-d') : '' }}', '{{ $o->evaluation ?? '' }}', '{{ $o->statut_validation }}', {{ json_encode($o->commentaire_pilote ?? '') }}, '{{ $o->priorite ?? 'moyenne' }}', '{{ $o->parent_id ?? '' }}')">Modifier</button>
            @if($o->statut_validation !== 'valide')
            <form method="POST" action="{{ route('objectifs.valider', $o) }}" style="display:inline"><button class="btn sm" style="background:var(--ok)">Valider</button>@csrf</form>
            @endif
            <form method="POST" action="{{ route('objectifs.destroy', $o) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'cet objectif')">@csrf @method('DELETE')<input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="btn sm danger">Suppr.</button></form>
        </td>
    </tr>
    {{-- Panel KR expandable --}}
    @if($showExpand)
    <tr><td colspan="8" style="padding:0">
    <div class="kr-panel" id="panel-kr-{{ $o->id }}">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <strong style="font-size:12px;color:var(--bleu)">Indicateurs KPI</strong>
            @if(isset($hasKR) && $hasKR)
            <button class="btn sm" onclick="openKRForm({{ $o->id }})">{!! $icPlus !!} Ajouter</button>
            @endif
        </div>

        {{-- Anneau KPI --}}
        @if($hasKRs)
        @php $segments = $o->anneauKPI(); @endphp
        @if(count($segments) > 0)
        <div class="anneau-wrap">
            <svg width="80" height="80" viewBox="0 0 42 42">
                <circle cx="21" cy="21" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="4"/>
                @php
                    $offset = 25;
                @endphp
                @foreach($segments as $seg)
                @php
                    $dash = $seg['ratio'] * 100;
                    $gap = 100 - $dash;
                @endphp
                <circle cx="21" cy="21" r="15.9" fill="none" stroke="{{ $seg['couleur'] }}" stroke-width="4" stroke-dasharray="{{ $dash }} {{ $gap }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="butt"/>
                @php $offset -= $dash; @endphp
                @endforeach
                <text x="21" y="22" text-anchor="middle" font-size="7" font-weight="700" fill="var(--bleu)">{{ $o->resultats->whereNotNull('valide')->count() }}/{{ $o->resultats->count() }}</text>
            </svg>
            <div class="anneau-legend">
                @foreach($segments as $seg)
                <div class="anneau-legend-item">
                    <span class="anneau-legend-dot" style="background:{{ $seg['couleur'] }}"></span>
                    <span>{{ $seg['libelle'] }} — <em>{{ ucfirst(str_replace('_', ' ', $seg['statut'])) }}</em></span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        @endif

        @if($hasKRs)
        <div class="kr-grid">
        @foreach($o->resultats as $kr)
        @php $krPct = $kr->progression(); $krBar = $krPct >= 80 ? 'bar-ok' : ($krPct >= 40 ? 'bar-warn' : 'bar-ko'); $krStatut = $kr->statutValidation(); @endphp
        <div class="kr-card">
            <div class="kr-title">{{ $kr->libelle }}</div>
            <div class="kr-vals">{{ $kr->valeur_actuelle }} / {{ $kr->valeur_cible }} {{ $kr->labelUnite() }} &middot; Poids: {{ $kr->poids }}</div>
            <div class="kr-bar"><div class="kr-bar-fill {{ $krBar }}" style="width:{{ $krPct }}%"></div></div>
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div class="kr-pct">{{ $krPct }}%</div>
                <span class="kr-statut {{ $krStatut }}">{{ ucfirst(str_replace('_', ' ', $krStatut)) }}</span>
            </div>
            @if($kr->date_validation)<div style="font-size:9px;color:var(--gris)">Valid&eacute; le {{ $kr->date_validation->format('d/m/Y') }}</div>@endif
            <div class="kr-validation">
                @if($krStatut === 'en_attente')
                <form method="POST" action="{{ route('objectifs.kr.valider', $kr) }}" style="display:inline">@csrf @method('PUT')<input type="hidden" name="valide" value="1"><button class="btn-val v-ok" title="Marquer atteint">Atteint</button></form>
                <form method="POST" action="{{ route('objectifs.kr.valider', $kr) }}" style="display:inline">@csrf @method('PUT')<input type="hidden" name="valide" value="0"><button class="btn-val v-ko" title="Marquer non atteint">Non atteint</button></form>
                @else
                <form method="POST" action="{{ route('objectifs.kr.resetValidation', $kr) }}" style="display:inline">@csrf @method('PUT')<button class="btn-val v-reset" title="R&eacute;initialiser">R&eacute;initialiser</button></form>
                @endif
            </div>
            <div class="kr-actions">
                <button class="btn sm sec" onclick="editKR({{ $kr->id }},{{ json_encode($kr->libelle) }},'{{ $kr->type_mesure }}',{{ $kr->valeur_cible }},{{ $kr->valeur_actuelle }},'{{ $kr->unite ?? '' }}',{{ $kr->poids }})">Modifier</button>
                <form method="POST" action="{{ route('objectifs.kr.destroy', $kr) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'ce KR')">@csrf @method('DELETE')<input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="btn sm danger">Suppr.</button></form>
            </div>
        </div>
        @endforeach
        </div>
        @else
        <div style="color:var(--gris);font-size:12px;padding:6px 0">Aucun indicateur KPI. Ajoutez-en pour une &eacute;valuation objective.</div>
        @endif

        {{-- Tâches liées --}}
        <div class="taches-liees">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <span style="font-size:11px;font-weight:600;color:var(--gris);text-transform:uppercase">T&acirc;ches li&eacute;es</span>
                @if(isset($hasPivot) && $hasPivot)
                <button class="btn sm sec" onclick="openLierTache({{ $o->id }})">{!! $icLink !!} Lier</button>
                @endif
            </div>
            @if($hasTaches)
            @foreach($o->tachesLiees as $t)
            <span class="tache-chip {{ $t->statut === 'termine' ? 'termine' : '' }}">
                {{ $t->intitule }}
                <form method="POST" action="{{ route('objectifs.taches.delier', [$o, $t]) }}" style="display:inline">@csrf @method('DELETE')
                <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--ko);font-size:12px;padding:0" title="Retirer">&times;</button>
                </form>
            </span>
            @endforeach
            @php $avTaches = $o->avancementDepuisTaches(); @endphp
            @if($avTaches !== null)
            <div style="font-size:10px;color:var(--gris);margin-top:4px">Taux compl&eacute;tion t&acirc;ches : {{ $avTaches }}%</div>
            @endif
            @else
            <div style="font-size:11px;color:var(--gris)">Aucune t&acirc;che li&eacute;e.</div>
            @endif
        </div>
    </div>
    </td></tr>
    @endif
    @endforeach
    </tbody>
    </table>
</div>
@endforeach
@else
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun objectif pour cette p&eacute;riode.</div>
@endif

@else
{{-- ==================== VUE COLLABORATEUR (consultation) ==================== --}}

@if(isset($mesObjectifs) && $mesObjectifs->count() > 0)
@php $scoreGlobal = \App\Models\Objectif::scoreGroupe($mesObjectifs); @endphp
<div class="score-global">
    <div><div class="val">{{ $scoreGlobal }}%</div><div class="lab">Mon score global</div></div>
</div>

<h3 style="font-size:14px;color:var(--bleu);margin-bottom:8px">Mes objectifs</h3>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="obj-table" style="border:none">
<thead><tr><th style="width:20px"></th><th>Objectif</th><th>Projet</th><th>Pond.</th><th>P&eacute;riode</th><th>&Eacute;val.</th><th>Statut</th></tr></thead>
<tbody>
@foreach($mesObjectifs as $o)
@php
    $barClass = $o->avancement >= 80 ? 'bar-ok' : ($o->avancement >= 40 ? 'bar-warn' : 'bar-ko');
    $evalClass = 'eval-' . ($o->evaluation ?? 'en_cours');
    $echPast = $o->date_echeance && $o->date_echeance->isPast() && $o->avancement < 100;
    $jRest = $o->joursRestants();
    $prio = $o->badgePriorite();
    $statutBadge = match($o->statut_validation) { 'valide' => 'b-ok', 'rejete' => 'b-ko', default => 'b-att' };
    $hasKRs = isset($hasKR) && $hasKR && $o->resultats->count() > 0;
    $hasHisto = isset($hasHist) && $hasHist && $o->historique->count() > 1;
    $showExpand = $hasKRs || $hasHisto;
@endphp
<tr>
    <td>
        @if($showExpand)
        <button class="expand-btn" onclick="toggleExpand({{ $o->id }})" id="exp-c-{{ $o->id }}">{!! $icChevron !!}</button>
        @endif
    </td>
    <td>
        <strong>{{ $o->libelle }}</strong>
        @if(($o->type ?? 'individuel') === 'projet')<span class="projet-tag" style="margin-left:6px">Projet</span>@endif
        <span class="prio-badge {{ $prio['class'] }}">{{ $prio['label'] }}</span>
        @if($o->commentaire_pilote)<br><span style="font-size:11px;color:var(--gris)">Note : {{ $o->commentaire_pilote }}</span>@endif
        @if(!empty($o->alertes()))
        @foreach($o->alertes() as $alrt)
        <br><span style="font-size:10px" class="al-{{ $alrt['type'] }}">{!! $icAlert !!} {{ $alrt['msg'] }}</span>
        @endforeach
        @endif
    </td>
    <td>@if($o->projet)<span class="projet-tag">{{ $o->projet->libelle }}</span>@else &mdash; @endif</td>
    <td style="font-weight:600">{{ $o->ponderation }}%</td>
    <td>
        @if($o->date_debut){{ $o->date_debut->format('d/m') }}@endif
        @if($o->date_debut && $o->date_echeance)&rarr;@endif
        @if($o->date_echeance)<span class="{{ $echPast ? 'echeance-past' : '' }}">{{ $o->date_echeance->format('d/m/Y') }}</span>@endif
        @if($jRest !== null && $jRest > 0 && $o->avancement < 100)<br><span class="jours-restants">{{ $jRest }}j restant(s)</span>@endif
        @if(!$o->date_debut && !$o->date_echeance)&mdash;@endif
    </td>
    <td><span class="eval-badge {{ $evalClass }}">{{ ucfirst(str_replace('_', ' ', $o->evaluation ?? 'en cours')) }}</span></td>
    <td><span class="badge {{ $statutBadge }}">{{ ucfirst(str_replace('_', ' ', $o->statut_validation)) }}</span></td>
</tr>
@if($showExpand)
<tr><td colspan="7" style="padding:0">
<div class="kr-panel" id="panel-kr-{{ $o->id }}">
    @if($hasKRs)
    <strong style="font-size:12px;color:var(--bleu)">Indicateurs KPI</strong>

    {{-- Anneau KPI consultation --}}
    @php $segments = $o->anneauKPI(); @endphp
    @if(count($segments) > 0)
    <div class="anneau-wrap" style="margin-top:6px">
        <svg width="80" height="80" viewBox="0 0 42 42">
            <circle cx="21" cy="21" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="4"/>
            @php $offset = 25; @endphp
            @foreach($segments as $seg)
            @php $dash = $seg['ratio'] * 100; $gap = 100 - $dash; @endphp
            <circle cx="21" cy="21" r="15.9" fill="none" stroke="{{ $seg['couleur'] }}" stroke-width="4" stroke-dasharray="{{ $dash }} {{ $gap }}" stroke-dashoffset="{{ $offset }}" stroke-linecap="butt"/>
            @php $offset -= $dash; @endphp
            @endforeach
            <text x="21" y="22" text-anchor="middle" font-size="7" font-weight="700" fill="var(--bleu)">{{ $o->resultats->whereNotNull('valide')->count() }}/{{ $o->resultats->count() }}</text>
        </svg>
        <div class="anneau-legend">
            @foreach($segments as $seg)
            <div class="anneau-legend-item"><span class="anneau-legend-dot" style="background:{{ $seg['couleur'] }}"></span><span>{{ $seg['libelle'] }} — <em>{{ ucfirst(str_replace('_', ' ', $seg['statut'])) }}</em></span></div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="kr-grid" style="margin-top:6px">
    @foreach($o->resultats as $kr)
    @php $krPct = $kr->progression(); $krBar = $krPct >= 80 ? 'bar-ok' : ($krPct >= 40 ? 'bar-warn' : 'bar-ko'); $krStatut = $kr->statutValidation(); @endphp
    <div class="kr-card">
        <div class="kr-title">{{ $kr->libelle }}</div>
        <div class="kr-vals">{{ $kr->valeur_actuelle }} / {{ $kr->valeur_cible }} {{ $kr->labelUnite() }}</div>
        <div class="kr-bar"><div class="kr-bar-fill {{ $krBar }}" style="width:{{ $krPct }}%"></div></div>
        <div style="display:flex;justify-content:space-between;align-items:center">
            <div class="kr-pct">{{ $krPct }}%</div>
            <span class="kr-statut {{ $krStatut }}">{{ ucfirst(str_replace('_', ' ', $krStatut)) }}</span>
        </div>
    </div>
    @endforeach
    </div>
    @endif
</div>
</td></tr>
@endif
@endforeach
</tbody>
</table>
</div>
@else
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun objectif pour cette p&eacute;riode.</div>
@endif


@endif

{{-- ==================== DIALOGS ==================== --}}

@if(auth()->user()->estPilote())
{{-- Dialog Assigner/Modifier objectif --}}
<dialog id="dlg-obj" style="width:560px">
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
        <input name="libelle" id="fLib" required maxlength="255" placeholder="Ex : Livrer le module facturation">

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
                <label>Priorit&eacute;</label>
                <select name="priorite" id="fPrio">
                    <option value="moyenne">Moyenne</option>
                    <option value="haute">Haute</option>
                    <option value="basse">Basse</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Date de d&eacute;but <span class="req">*</span></label>
                <input type="date" name="date_debut" id="fDebut" required value="{{ now()->format('Y-m-d') }}">
            </div>
            <div>
                <label>&Eacute;ch&eacute;ance <span class="req">*</span></label>
                <input type="date" name="date_echeance" id="fEcheance" required>
            </div>
        </div>

        <input type="hidden" name="parent_id" id="fParent" value="">

        <div id="editFields" style="display:none">
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
                    <textarea name="commentaire_pilote" id="fComm" rows="2" maxlength="500" style="resize:vertical"></textarea>
                </div>
            </div>
            <input type="hidden" name="avancement" id="fAvanc" value="0">
            <input type="hidden" name="evaluation" id="fEval" value="">
        </div>

        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="document.getElementById('dlg-obj').close()">Annuler</button>
            <button class="btn" id="btnSubmit">Ajouter</button>
        </div>
    </form>
</dialog>

{{-- Dialog Ajouter/Modifier KR --}}
<dialog id="dlg-kr" style="width:480px">
    <h3 id="krTitre" style="color:var(--bleu);margin-bottom:8px">Ajouter un R&eacute;sultat Cl&eacute;</h3>
    <form method="POST" id="formKR">
        @csrf
        <input type="hidden" name="_kr_method" id="krMethod" value="">

        <label>Libell&eacute; <span class="req">*</span></label>
        <input name="libelle" id="krLib" required maxlength="255" placeholder="Ex : Nombre de modules livrés">

        <div class="form-row">
            <div>
                <label>Type de mesure <span class="req">*</span></label>
                <select name="type_mesure" id="krType">
                    <option value="pourcentage">Pourcentage</option>
                    <option value="nombre">Nombre</option>
                    <option value="binaire">Binaire (Oui/Non)</option>
                    <option value="monnaie">Monnaie (FCFA)</option>
                </select>
            </div>
            <div>
                <label>Unit&eacute;</label>
                <input name="unite" id="krUnite" maxlength="30" placeholder="%, FCFA, jours...">
            </div>
        </div>

        <div class="form-row">
            <div>
                <label>Valeur cible <span class="req">*</span></label>
                <input type="number" name="valeur_cible" id="krCible" required min="0" step="0.01" value="100">
            </div>
            <div>
                <label>Valeur actuelle</label>
                <input type="number" name="valeur_actuelle" id="krActuelle" min="0" step="0.01" value="0">
            </div>
        </div>

        <div>
            <label>Poids <span class="req">*</span></label>
            <input type="number" name="poids" id="krPoids" required min="1" max="100" value="1">
            <div class="hint">Poids relatif parmi les KR de cet objectif</div>
        </div>

        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="document.getElementById('dlg-kr').close()">Annuler</button>
            <button class="btn" id="krBtnSubmit">Ajouter</button>
        </div>
    </form>
</dialog>

{{-- Dialog Lier tâche --}}
@if(isset($tachesActives) && $tachesActives->count() > 0)
<dialog id="dlg-tache" style="width:420px">
    <h3 style="color:var(--bleu);margin-bottom:8px">Lier une t&acirc;che</h3>
    <form method="POST" id="formLierTache">
        @csrf
        <label>T&acirc;che <span class="req">*</span></label>
        <select name="tache_id" required>
            <option value="">— Choisir —</option>
            @foreach($tachesActives as $ta)
            <option value="{{ $ta->id }}">{{ $ta->intitule }}</option>
            @endforeach
        </select>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="document.getElementById('dlg-tache').close()">Annuler</button>
            <button class="btn">Lier</button>
        </div>
    </form>
</dialog>
@endif
@endif
@endsection

@push('scripts')
<script>
function toggleType() {
    var t = document.getElementById('fType').value;
    var fProjet = document.getElementById('fProjet');
    var labelProjet = document.getElementById('labelProjet');
    if (t === 'projet') {
        fProjet.setAttribute('required', 'required');
        labelProjet.innerHTML = 'Projet <span class="req">*</span>';
    } else {
        fProjet.removeAttribute('required');
        labelProjet.innerHTML = 'Projet';
    }
}

function toggleExpand(id) {
    var panels = document.querySelectorAll('#panel-kr-' + id);
    panels.forEach(function(p) { p.classList.toggle('open'); });
    var btns = document.querySelectorAll('[id$="-' + id + '"].expand-btn, #exp-' + id + ', #exp-p-' + id + ', #exp-c-' + id);
    btns.forEach(function(b) { b.classList.toggle('open'); });
}

function editObj(id, type, userId, libelle, projetId, pond, avanc, debut, echeance, evaluation, statut, comm, prio, parentId) {
    document.getElementById('formTitre').textContent = "Modifier l'objectif";
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
    var fPrio = document.getElementById('fPrio');
    if (fPrio) fPrio.value = prio || 'moyenne';
    var fParent = document.getElementById('fParent');
    if (fParent) fParent.value = parentId || '';
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
    var fPrio = document.getElementById('fPrio');
    if (fPrio) fPrio.value = 'moyenne';
    var fParent = document.getElementById('fParent');
    if (fParent) fParent.value = '';
    document.getElementById('editFields').style.display = 'none';
    document.getElementById('btnSubmit').textContent = 'Ajouter';
    var mf = document.querySelector('#formObj input[name="_method"]');
    if (mf) mf.remove();
}

// === KR ===
function openKRForm(objectifId) {
    document.getElementById('krTitre').textContent = 'Ajouter un Résultat Clé';
    document.getElementById('formKR').action = '{{ url("objectifs") }}/' + objectifId + '/kr';
    document.getElementById('krMethod').value = '';
    document.getElementById('krLib').value = '';
    document.getElementById('krType').value = 'pourcentage';
    document.getElementById('krUnite').value = '';
    document.getElementById('krCible').value = '100';
    document.getElementById('krActuelle').value = '0';
    document.getElementById('krPoids').value = '1';
    document.getElementById('krBtnSubmit').textContent = 'Ajouter';
    // Retirer _method si présent
    var mf = document.querySelector('#formKR input[name="_method"]');
    if (mf) mf.remove();
    document.getElementById('dlg-kr').showModal();
}

function editKR(id, libelle, type, cible, actuelle, unite, poids) {
    document.getElementById('krTitre').textContent = 'Modifier le Résultat Clé';
    document.getElementById('formKR').action = '{{ url("kr") }}/' + id;
    document.getElementById('krLib').value = libelle;
    document.getElementById('krType').value = type;
    document.getElementById('krCible').value = cible;
    document.getElementById('krActuelle').value = actuelle;
    document.getElementById('krUnite').value = unite;
    document.getElementById('krPoids').value = poids;
    document.getElementById('krBtnSubmit').textContent = 'Enregistrer';
    var mf = document.querySelector('#formKR input[name="_method"]');
    if (!mf) { mf = document.createElement('input'); mf.type='hidden'; mf.name='_method'; document.getElementById('formKR').appendChild(mf); }
    mf.value = 'PUT';
    document.getElementById('dlg-kr').showModal();
}

// === Tâches ===
function openLierTache(objectifId) {
    var dlg = document.getElementById('dlg-tache');
    if (!dlg) return;
    document.getElementById('formLierTache').action = '{{ url("objectifs") }}/' + objectifId + '/taches';
    dlg.showModal();
}
</script>
@endpush
