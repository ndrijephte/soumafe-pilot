@extends('layouts.app')
@section('sousTitre', 'Planning Réunions')
@section('titre', 'Planning')
@push('styles')
<style>
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:16px}
.panneau h3{font-size:14px;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.btn.ok{background:var(--ok)}
.btn.ko{background:var(--ko)}
.btn:hover{opacity:.9}

/* Navigation semaine */
.nav-semaine{display:flex;align-items:center;gap:14px;margin-bottom:18px;flex-wrap:wrap}
.nav-semaine a{text-decoration:none;color:var(--accent);font-size:13px;font-weight:600;display:flex;align-items:center;gap:4px}
.nav-semaine .sem-label{font-size:15px;font-weight:700;color:var(--bleu)}

/* Cards types réunion */
.types-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:20px}
.type-card{background:var(--carte);border:2px solid var(--bord);border-radius:12px;padding:14px;cursor:pointer;transition:all .2s;text-decoration:none;color:inherit}
.type-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.type-card.actif{border-color:var(--type-color);box-shadow:0 2px 10px rgba(0,0,0,.1)}
.type-card .tc-info{flex:1;min-width:0}
.type-card .tc-nom{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.type-card .tc-meta{font-size:10px;color:var(--gris);margin-top:2px;display:flex;align-items:center;gap:4px}
.type-card .tc-rec{font-size:9px;font-weight:700;padding:1px 6px;border-radius:5px;color:var(--accent)}
.type-card .tc-roles{font-size:10px;color:var(--gris)}

/* Tableau planning */
.plan-table{width:100%;border-collapse:collapse;font-size:12.5px}
.plan-table th{padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;text-align:center;border-bottom:2px solid var(--bord)}
.plan-table th:first-child{text-align:left}
.plan-table td{padding:8px 10px;border-bottom:1px solid var(--bord);text-align:center;vertical-align:middle}
.plan-table td:first-child{text-align:left;font-weight:600;color:var(--bleu);white-space:nowrap}
.plan-table tr:hover{background:#f8fafc}

/* En-tête type */
.type-header{display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap}
.type-header .th-info{display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap}
.type-header .th-nom{font-size:16px;font-weight:700}
.type-header .th-rec{font-size:10px;font-weight:600;padding:2px 8px;border-radius:6px;color:var(--accent)}

/* Cellule affectation - actions au hover */
.cell-aff{display:flex;flex-direction:column;align-items:center;gap:3px;position:relative}
.cell-aff .nom{font-size:12px;font-weight:600;color:#1e293b}
.cell-aff .statut{font-size:9px;font-weight:700;padding:1px 6px;border-radius:6px}
.s-prevu{background:#dbeafe;color:#1d4ed8}
.s-present{background:#dcfce7;color:var(--ok)}
.s-absent{background:#fee2e2;color:var(--ko)}
.s-remplace{background:#fef3c7;color:#b45309}
.cell-aff .remplac{font-size:10px;color:var(--gris);font-style:italic}
.cell-actions{display:none;gap:2px;margin-top:2px}
.cell-aff:hover .cell-actions,.plan-table tr:hover .cell-actions{display:flex;justify-content:center}
.cell-actions .btn-act{background:none;border:none;cursor:pointer;color:var(--gris);padding:2px;border-radius:4px;line-height:1}
.cell-actions .btn-act:hover{color:var(--accent);background:rgba(46,116,181,.08)}
.cell-actions .btn-act.del:hover{color:var(--ko);background:#fee2e2}

/* Instance ponctuelle - actions en-tête au hover */
.inst-card{background:var(--fond);border:1px solid var(--bord);border-radius:10px;padding:12px 16px;margin-bottom:10px;position:relative}
.inst-header{font-weight:700;color:var(--bleu);font-size:14px;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.inst-actions{display:none;gap:4px;margin-left:auto}
.inst-card:hover .inst-actions{display:flex}
.inst-actions .btn-act{background:none;border:none;cursor:pointer;color:var(--gris);padding:3px;border-radius:4px}
.inst-actions .btn-act:hover{color:var(--ko);background:#fee2e2}

/* Row actions ponctuel - hover */
.row-acts{display:none;gap:2px;justify-content:center}
.plan-table tr:hover .row-acts{display:flex}
.row-acts .btn-act{background:none;border:none;cursor:pointer;color:var(--gris);padding:2px;border-radius:4px;line-height:1}
.row-acts .btn-act:hover{color:var(--accent);background:rgba(46,116,181,.08)}

/* Participants */
.part-list{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px}
.part-chip{display:inline-flex;align-items:center;gap:5px;background:var(--fond);border:1px solid var(--bord);padding:4px 10px;border-radius:16px;font-size:12px}
.part-chip .retirer{background:none;border:none;cursor:pointer;color:var(--gris);font-size:14px;line-height:1}
.part-chip .retirer:hover{color:var(--ko)}

/* Grille admin */
.admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.admin-grid .panneau{margin-bottom:0}

/* Dialog */
dialog{border:none;border-radius:14px;padding:24px;width:440px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
.req{color:var(--ko)}
.vide{text-align:center;padding:30px;color:var(--gris);font-size:13px}
.panneau form label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
.panneau form label:first-child{margin-top:0}
.panneau form input[type="date"],
.panneau form input[type="time"],
.panneau form input[type="text"],
.panneau form select,
.panneau form textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit;background:#fff}
.part-list-ro{display:flex;flex-direction:column;gap:5px}
.part-list-ro .part-item{display:flex;align-items:center;gap:8px;padding:6px 10px;background:var(--fond);border-radius:8px;font-size:13px}
.part-list-ro .part-idx{color:var(--gris);font-size:11px;min-width:18px}

@media(max-width:768px){
    .admin-grid{grid-template-columns:1fr}
    .types-grid{grid-template-columns:1fr 1fr}
    .plan-table{display:block;overflow-x:auto}
    .nav-semaine{gap:8px}
    .cell-actions,.inst-actions,.row-acts{display:flex!important}
}
@media(max-width:480px){
    .types-grid{grid-template-columns:1fr}
}
</style>
@endpush

@section('contenu')
@php
    $ic = fn(string $d, int $s=17) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
    if ($estPonctuel) {
        $semainePrecedente = $debut->copy()->subMonth()->startOfMonth()->format('Y-m-d');
        $semaineSuivante = $debut->copy()->addMonth()->startOfMonth()->format('Y-m-d');
    } else {
        $semainePrecedente = $debut->copy()->subWeek()->format('Y-m-d');
        $semaineSuivante = $debut->copy()->addWeek()->format('Y-m-d');
    }

    $affMap = [];
    foreach ($affectations as $a) {
        $affMap[$a->date_reunion->format('Y-m-d') . '_' . $a->role_reunion_id] = $a;
    }
@endphp

@if(session('succes'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok);padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:13px;display:flex;align-items:center;gap:8px">
    {!! $ic('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',16) !!}
    {{ session('succes') }}
</div>
@endif
@if($errors->any())
<div style="background:#fef2f2;border:1px solid #fecaca;color:var(--ko);padding:10px 14px;border-radius:10px;margin-bottom:14px;font-size:13px">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

{{-- Cards types de réunion --}}
<div class="types-grid">
    @foreach($types as $t)
    <a href="{{ route('planning.index', ['type' => $t->id, 'semaine' => $debut->format('Y-m-d')]) }}"
       class="type-card {{ $type && $type->id === $t->id ? 'actif' : '' }}"
       style="{{ $type && $type->id === $t->id ? '--type-color:var(--accent)' : '' }}">
        <div class="tc-info">
            <div class="tc-nom">{{ $t->libelle }}</div>
            <div class="tc-meta">
                <span class="tc-rec" style="background:rgba(46,116,181,.1)">{{ $t->recurrenceLabel() }}</span>
                @if($t->heure)<span class="tc-roles">{!! $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',9) !!} {{ \Carbon\Carbon::parse($t->heure)->format('H:i') }}</span>@endif
                <span class="tc-roles">{{ $t->roles->count() }} rôles</span>
            </div>
        </div>
    </a>
    @endforeach
    @if($estGestionnaire)
    <div class="type-card" style="border-style:dashed;cursor:pointer;justify-content:center;color:var(--gris)" onclick="document.getElementById('dlg-type').showModal()">
        {!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',20) !!}
        <span style="font-size:12px;font-weight:600">Nouveau type</span>
    </div>
    @endif
</div>

{{-- Navigation période --}}
@if($type)
<div class="nav-semaine">
    <a href="{{ route('planning.index', ['type' => $type->id, 'semaine' => $semainePrecedente]) }}">
        {!! $ic('<polyline points="15 18 9 12 15 6"/>',18) !!} Précédente
    </a>
    <span class="sem-label">
        @if($estPonctuel)
        {{ $debut->isoFormat('MMMM YYYY') }}
        @else
        Semaine du {{ $debut->isoFormat('D MMM') }} au {{ $fin->isoFormat('D MMM YYYY') }}
        @endif
    </span>
    <a href="{{ route('planning.index', ['type' => $type->id, 'semaine' => $semaineSuivante]) }}">
        Suivante {!! $ic('<polyline points="9 18 15 12 9 6"/>',18) !!}
    </a>
</div>
@endif

@if($type)

{{-- En-tête type + tableau --}}
<div class="panneau">
    <div class="type-header">
        <div class="th-info">
            <span class="th-nom" style="color:var(--bleu)">{!! $ic('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',16) !!} {{ $type->libelle }}</span>
            <span class="th-rec" style="background:rgba(46,116,181,.1)">{{ $type->recurrenceLabel() }}</span>
            @if($type->heure)<span style="font-size:12px;color:var(--gris);font-weight:600">{!! $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',13) !!} {{ \Carbon\Carbon::parse($type->heure)->format('H\hi') }}</span>@endif
        </div>
        @if($estGestionnaire)
        <button class="btn-act" title="Modifier le type" onclick="ouvrirEditType({{ $type->id }}, '{{ addslashes($type->libelle) }}', '{{ $type->heure ? \Carbon\Carbon::parse($type->heure)->format('H:i') : '' }}')" style="background:none;border:none;cursor:pointer;color:var(--gris);padding:4px">
            {!! $ic('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',15) !!}
        </button>
        @endif
    </div>

    @if($type->roles->isEmpty())
        <div class="vide">Aucun rôle défini pour ce type.
            @if($estPilote && auth()->user()->estAdmin())
            <button class="btn sm sec" style="margin-top:8px" onclick="document.getElementById('dlg-role').showModal()">
                {!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter un rôle
            </button>
            @endif
        </div>
    @elseif($jours)
        @if($estPonctuel)
        {{-- Présentation ponctuelle : instances multiples par date --}}
        @foreach($jours as $j)
        @php $dateInstances = $instances[$j->format('Y-m-d')] ?? collect(); @endphp
        @foreach($dateInstances as $instIdx => $instance)
        <div class="inst-card">
            <div class="inst-header">
                {!! $ic('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',14) !!}
                {{ $j->isoFormat('dddd D MMMM YYYY') }}
                @if($type->heure)<span style="font-size:11px;color:var(--gris);font-weight:600">{{ \Carbon\Carbon::parse($type->heure)->format('H\hi') }}</span>@endif
                @if($dateInstances->count() > 1)
                <span style="font-size:11px;font-weight:600;background:rgba(46,116,181,.1);color:var(--accent);padding:2px 8px;border-radius:6px">Réunion {{ $instIdx + 1 }}</span>
                @endif
                @if($instance->first()?->projet)
                <span style="font-size:11px;font-weight:600;background:rgba(34,139,34,.1);color:#228b22;padding:2px 8px;border-radius:6px">{!! $ic('<path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/>',11) !!} {{ $instance->first()->projet->libelle }}</span>
                @endif
                @if($estGestionnaire)
                <div class="inst-actions">
                    <button class="btn-act" title="Supprimer cette réunion"
                        onclick="ouvrirSupprInstance('{{ $instance->pluck('id')->join(',') }}', '{{ $j->format('d/m/Y') }}')">
                        {!! $ic('<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>',14) !!}
                    </button>
                </div>
                @endif
            </div>
            <table class="plan-table">
                <thead>
                    <tr>
                        <th style="text-align:left">Rôle</th>
                        <th>Acteur</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($instance as $aff)
                <tr>
                    <td>{!! $ic('<circle cx="12" cy="12" r="3"/>',10) !!} {{ $aff->role->libelle ?? '—' }}@if(($aff->role->code ?? null) === 'scribe')<span title="Le scribe du jour est le rédacteur de tous les rapports de la journée (Daily Scrum, Review, Rétrospective, comptes rendus)" style="font-size:9.5px;font-weight:600;background:#eef2ff;color:#3730a3;padding:1px 6px;border-radius:6px;margin-left:4px">Rédacteur des rapports</span>@endif</td>
                    <td>
                        {{ $aff->user?->nomComplet() ?? '—' }}
                        @if($aff->statut !== 'prevu')
                        <span class="statut s-{{ $aff->statut }}" style="font-size:9px;margin-left:4px">{{ $aff->statutLabel() }}</span>
                        @endif
                        @if($aff->statut === 'remplace' && $aff->remplacant)
                            <span style="font-size:10px;color:var(--gris);font-style:italic"> par {{ $aff->remplacant->nomComplet() }}</span>
                        @endif
                    </td>
                    <td>
                        @if($aff->statut === 'prevu')
                        <div class="row-acts">
                            @if($estGestionnaire)
                            <button class="btn-act" title="Modifier"
                                onclick="ouvrirModifAff({{ $aff->id }}, '{{ addslashes($aff->role->libelle) }}', {{ $aff->user_id }})">
                                {!! $ic('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',12) !!}
                            </button>
                            @endif
                            @if($estGestionnaire || $aff->user_id === auth()->id())
                            <button class="btn-act" title="Se faire remplacer"
                                onclick="ouvrirAbsence({{ $aff->id }}, '{{ addslashes($aff->user?->nomComplet()) }}', '{{ addslashes($aff->role->libelle) }}', '{{ $j->format('d/m/Y') }}')">
                                {!! $ic('<path d="M16 3h5v5"/><path d="M21 3l-7 7"/><path d="M8 21H3v-5"/><path d="M3 21l7-7"/>',12) !!}
                            </button>
                            @endif
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
        @endforeach
        @else
        {{-- Présentation quotidien : grille semaine --}}
        <div style="overflow-x:auto">
        <table class="plan-table">
            <thead>
                <tr>
                    <th>Rôle</th>
                    @foreach($jours as $j)
                    <th style="border-bottom-color:var(--accent)">{{ $j->isoFormat('ddd D MMM') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($type->roles as $role)
                <tr>
                    <td>{!! $ic('<circle cx="12" cy="12" r="3"/>',12) !!} {{ $role->libelle }}@if(($role->code ?? null) === 'scribe')<span title="Le scribe du jour est le rédacteur de tous les rapports de la journée (Daily Scrum, Review, Rétrospective, comptes rendus)" style="font-size:9.5px;font-weight:600;background:#eef2ff;color:#3730a3;padding:1px 6px;border-radius:6px;margin-left:4px">Rédacteur des rapports</span>@endif</td>
                    @foreach($jours as $j)
                    @php $aff = $affMap[$j->format('Y-m-d') . '_' . $role->id] ?? null; @endphp
                    <td>
                        @if($aff)
                        <div class="cell-aff">
                            <span class="nom">{{ $aff->user?->nomComplet() ?? '—' }}</span>
                            @if($estGestionnaire || $aff->statut !== 'prevu')
                            <span class="statut s-{{ $aff->statut }}">{{ $aff->statutLabel() }}</span>
                            @endif
                            @if($aff->statut === 'remplace' && $aff->remplacant)
                                <span class="remplac">par {{ $aff->remplacant->nomComplet() }}</span>
                            @endif
                            @if(($estGestionnaire || $aff->user_id === auth()->id()) && $aff->statut === 'prevu')
                            <div class="cell-actions">
                                @if($estGestionnaire)
                                <button class="btn-act" title="Modifier"
                                    onclick="ouvrirModifAff({{ $aff->id }}, '{{ addslashes($aff->role->libelle) }}', {{ $aff->user_id }})">
                                    {!! $ic('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',12) !!}
                                </button>
                                @endif
                                <button class="btn-act" title="Se faire remplacer"
                                    onclick="ouvrirAbsence({{ $aff->id }}, '{{ addslashes($aff->user?->nomComplet()) }}', '{{ addslashes($aff->role->libelle) }}', '{{ $j->format('d/m/Y') }}')">
                                    {!! $ic('<path d="M16 3h5v5"/><path d="M21 3l-7 7"/><path d="M8 21H3v-5"/><path d="M3 21l7-7"/>',12) !!}
                                </button>
                            </div>
                            @endif
                        </div>
                        @else
                        <span style="color:var(--gris);font-size:11px">—</span>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    @elseif($estPonctuel)
        <div class="vide">Aucune réunion programmée pour {{ $debut->isoFormat('MMMM YYYY') }}.</div>
    @endif
</div>

{{-- Participants + Générer --}}
<div class="{{ $estGestionnaire ? 'admin-grid' : '' }}">
    <div class="panneau">
        <h3>
            {!! $ic('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') !!}
            Participants ({{ $participants->count() }})
        </h3>
        @if($estGestionnaire)
        <div class="part-list">
            @forelse($participants as $p)
            <span class="part-chip">
                {{ $p->user?->nomComplet() }}
                <form method="POST" action="{{ route('planning.retirer', $p) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'ce participant')">
                    @csrf
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button type="submit" class="retirer" title="Retirer">&times;</button>
                </form>
            </span>
            @empty
            <span style="color:var(--gris);font-size:12px">Aucun participant</span>
            @endforelse
        </div>
        <form method="POST" action="{{ route('planning.ajouter') }}" style="display:flex;gap:8px;align-items:center;margin-top:8px">
            @csrf
            <input type="hidden" name="type_reunion_id" value="{{ $type->id }}">
            <select name="user_id" required style="flex:1;padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px">
                <option value="">— Ajouter un participant —</option>
                @foreach($collaborateurs as $c)
                    @unless($participants->pluck('user_id')->contains($c->id))
                    <option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>
                    @endunless
                @endforeach
            </select>
            <button type="submit" class="btn sm">{!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter</button>
        </form>
        @else
        <div class="part-list-ro">
            @forelse($participants as $i => $p)
            <div class="part-item">
                <span class="part-idx">{{ $i+1 }}.</span>
                {!! $ic('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',14) !!}
                {{ $p->user?->nomComplet() }}
            </div>
            @empty
            <span style="color:var(--gris);font-size:12px">Aucun participant</span>
            @endforelse
        </div>
        @endif
    </div>

    @if($estGestionnaire)
    <div class="panneau">
        @if($type->recurrence === 'quotidien')
        <h3>Générer le planning (rotation)</h3>
        <form method="POST" action="{{ route('planning.generer') }}">
            @csrf
            <input type="hidden" name="type_reunion_id" value="{{ $type->id }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <div>
                    <label>Date début <span class="req">*</span></label>
                    <input type="date" name="date_debut" required value="{{ $debut->format('Y-m-d') }}">
                </div>
                <div>
                    <label>Date fin <span class="req">*</span></label>
                    <input type="date" name="date_fin" required value="{{ $fin->format('Y-m-d') }}">
                </div>
            </div>
            <label>Heure de la réunion</label>
            <input type="time" name="heure" value="{{ $type->heure ? \Carbon\Carbon::parse($type->heure)->format('H:i') : '' }}" style="margin-bottom:12px">
            @if($participants->isEmpty())
            <p style="font-size:11px;color:var(--ko);background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 10px;margin-bottom:12px">
                Ajoutez au moins un participant avant de générer.
            </p>
            @else
            <p style="font-size:11px;color:var(--gris);margin-bottom:12px">
                Rotation entre {{ $participants->count() }} participants.
            </p>
            @endif
            <button type="submit" class="btn" {{ $participants->isEmpty() ? 'disabled style="opacity:.5;cursor:not-allowed"' : '' }}
                onclick="return confirm('Générer le planning pour cette période ?')">
                Générer
            </button>
        </form>
        @else
        <h3>Programmer une réunion</h3>
        <form method="POST" action="{{ route('planning.generer') }}">
            @csrf
            <input type="hidden" name="type_reunion_id" value="{{ $type->id }}">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <div>
                    <label>Date <span class="req">*</span></label>
                    <input type="date" name="date_debut" required value="{{ now()->format('Y-m-d') }}">
                </div>
                <div>
                    <label>Heure</label>
                    <input type="time" name="heure" value="{{ $type->heure ? \Carbon\Carbon::parse($type->heure)->format('H:i') : '' }}">
                </div>
            </div>
            <input type="hidden" name="date_fin" id="ponctuelFin" value="{{ now()->format('Y-m-d') }}">
            <label>Projet concerné</label>
            <select name="projet_id" style="margin-bottom:6px">
                <option value="">— Aucun projet —</option>
                @foreach($projets as $pr)
                <option value="{{ $pr->id }}">{{ $pr->code }} - {{ $pr->libelle }}</option>
                @endforeach
            </select>
            @if($participants->isEmpty())
            <p style="font-size:11px;color:var(--ko);background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 10px;margin:10px 0">
                Ajoutez au moins un participant.
            </p>
            @elseif($type->roles->isNotEmpty())
            <div style="margin-top:10px;border-top:1px solid var(--bord);padding-top:10px">
                <span style="font-size:12px;font-weight:600;color:var(--bleu);display:block;margin-bottom:6px">Affectation des rôles</span>
                @foreach($type->roles as $role)
                <label>{{ $role->libelle }} <span class="req">*</span></label>
                <select name="affectations[{{ $role->id }}]" required style="margin-bottom:6px">
                    <option value="">— Choisir —</option>
                    @foreach($participants as $p)
                    <option value="{{ $p->user_id }}">{{ $p->user?->nomComplet() }}</option>
                    @endforeach
                </select>
                @endforeach
            </div>
            @endif
            <button type="submit" class="btn" style="margin-top:10px" {{ $participants->isEmpty() || $type->roles->isEmpty() ? 'disabled style="opacity:.5;cursor:not-allowed;margin-top:10px"' : '' }}
                onclick="document.getElementById('ponctuelFin').value=this.form.date_debut.value;return confirm('Programmer cette réunion ?')">
                Programmer
            </button>
        </form>
        @endif
    </div>
    @endif
</div>

{{-- Rôles (consultation pour tous, modification admin/charge_ops) --}}
<div class="panneau" style="margin-top:16px">
    <h3>
        {!! $ic('<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>') !!}
        Rôles - {{ $type->libelle }}
    </h3>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        @forelse($type->roles as $r)
        <span style="background:rgba(46,116,181,.08);color:var(--accent);border:1px solid rgba(46,116,181,.18);padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px">
            {{ $r->ordre }}. {{ $r->libelle }}
            @if($estGestionnaire)
            <button type="button" style="background:none;border:none;cursor:pointer;color:var(--gris);padding:0;line-height:1;display:inline-flex" title="Supprimer ce rôle"
                onclick="ouvrirSupprRole({{ $r->id }}, '{{ addslashes($r->libelle) }}')">
                {!! $ic('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',12) !!}
            </button>
            @endif
        </span>
        @empty
        <span style="color:var(--gris);font-size:12px">Aucun rôle</span>
        @endforelse
    </div>
    @if($estGestionnaire)
    <button class="btn sm sec" onclick="document.getElementById('dlg-role').showModal()">
        {!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Nouveau rôle
    </button>
    @endif
</div>

@else
<div class="panneau">
    <div class="vide">Aucun type de réunion configuré. Demandez à un administrateur d'en créer.</div>
</div>
@endif

{{-- Dialog Absence --}}
<dialog id="dlg-absence">
    <h3 style="margin-bottom:14px;color:var(--bleu);display:flex;align-items:center;gap:8px">
        {!! $ic('<path d="M16 3h5v5"/><path d="M21 3l-7 7"/><path d="M8 21H3v-5"/><path d="M3 21l7-7"/>') !!}
        Substituer un participant
    </h3>
    <form id="form-absence" method="POST">
        @csrf
        <p id="abs-info" style="font-size:13px;color:#1e293b;margin-bottom:12px"></p>
        <label>Remplaçant (optionnel)</label>
        <select name="remplacant_id" id="abs-remplacant" style="margin-bottom:14px">
            <option value="">— Aucun remplaçant —</option>
            @foreach($collaborateurs as $c)
            <option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>
            @endforeach
        </select>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn ko">Confirmer absence</button>
        </div>
    </form>
</dialog>

{{-- Dialog Nouveau type --}}
<dialog id="dlg-type">
    <h3 style="margin-bottom:14px;color:var(--bleu)">Nouveau type de réunion</h3>
    <form method="POST" action="{{ route('planning.type.store') }}">
        @csrf
        <label>Code <span class="req">*</span></label>
        <input type="text" name="code" required placeholder="ex: daily_scrum" style="margin-bottom:8px">
        <label>Libellé <span class="req">*</span></label>
        <input type="text" name="libelle" required placeholder="ex: Daily Scrum" style="margin-bottom:8px">
        <label>Récurrence <span class="req">*</span></label>
        <select name="recurrence" required style="margin-bottom:14px">
            <option value="quotidien">Quotidien</option>
            <option value="hebdomadaire">Hebdomadaire</option>
            <option value="bimensuel">Bimensuel</option>
            <option value="mensuel">Mensuel</option>
            <option value="fin_sprint">Fin de sprint</option>
        </select>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn">Créer</button>
        </div>
    </form>
</dialog>

{{-- Dialog Nouveau rôle --}}
@if($type)
<dialog id="dlg-role">
    <h3 style="margin-bottom:14px;color:var(--bleu)">Nouveau rôle - {{ $type->libelle }}</h3>
    <form method="POST" action="{{ route('planning.role.store') }}">
        @csrf
        <input type="hidden" name="type_reunion_id" value="{{ $type->id }}">
        <label>Libellé <span class="req">*</span></label>
        <input type="text" name="libelle" required placeholder="ex: Facilitateur" style="margin-bottom:14px">
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn">Créer</button>
        </div>
    </form>
</dialog>

{{-- Dialog suppression rôle --}}
<dialog id="dlg-suppr-role">
    <h3 style="margin:0 0 12px;font-size:15px">Supprimer un rôle</h3>
    <p id="suppr-role-info" style="font-size:13px;color:var(--bleu);margin:0 0 10px"></p>
    <form id="form-suppr-role" method="POST">
        @csrf
        <label>Motif de suppression <span class="req">*</span></label>
        <textarea name="motif_suppression" required minlength="5" rows="2" placeholder="Précisez le motif..."></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn" style="background:var(--ko)">Supprimer</button>
        </div>
    </form>
</dialog>

{{-- Dialog Modifier type --}}
<dialog id="dlg-edit-type">
    <h3 style="margin-bottom:14px;color:var(--bleu)">Modifier le type de réunion</h3>
    <form id="form-edit-type" method="POST">
        @csrf
        @method('PUT')
        <label>Libellé <span class="req">*</span></label>
        <input type="text" name="libelle" id="edit-type-libelle" required style="margin-bottom:8px">
        <label>Heure par défaut</label>
        <input type="time" name="heure" id="edit-type-heure" style="margin-bottom:14px">
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>

{{-- Dialog Modifier affectation --}}
<dialog id="dlg-modif-aff">
    <h3 style="margin-bottom:14px;color:var(--bleu);display:flex;align-items:center;gap:8px">
        {!! $ic('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>') !!}
        Modifier l'affectation
    </h3>
    <form id="form-modif-aff" method="POST">
        @csrf
        @method('PUT')
        <p id="modif-aff-info" style="font-size:13px;color:#1e293b;margin-bottom:12px"></p>
        <label>Nouveau collaborateur <span class="req">*</span></label>
        <select name="user_id" id="modif-aff-user" required style="margin-bottom:14px">
            <option value="">— Choisir —</option>
            @foreach($collaborateurs as $c)
            <option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>
            @endforeach
        </select>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>

{{-- Dialog Supprimer instance réunion --}}
<dialog id="dlg-suppr-instance">
    <h3 style="margin:0 0 12px;font-size:15px;color:var(--ko)">Supprimer une réunion</h3>
    <p id="suppr-inst-info" style="font-size:13px;color:var(--bleu);margin:0 0 10px"></p>
    <form id="form-suppr-instance" method="POST" action="{{ route('planning.instance.destroy') }}">
        @csrf
        @method('DELETE')
        <input type="hidden" name="affectation_ids" id="suppr-inst-ids">
        <label>Motif de suppression <span class="req">*</span></label>
        <textarea name="motif_suppression" required minlength="5" rows="2" placeholder="Précisez le motif..."></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn" style="background:var(--ko)">Supprimer</button>
        </div>
    </form>
</dialog>
@endif

@endsection

@push('scripts')
<script>
function ouvrirAbsence(affId, nom, role, date) {
    document.getElementById('form-absence').action = '/planning/' + affId + '/absence';
    document.getElementById('abs-info').textContent = nom + ' - ' + role + ' le ' + date;
    document.getElementById('abs-remplacant').value = '';
    document.getElementById('dlg-absence').showModal();
}
function ouvrirSupprRole(roleId, libelle) {
    document.getElementById('form-suppr-role').action = '/planning/role/' + roleId + '/supprimer';
    document.getElementById('suppr-role-info').textContent = 'Rôle : ' + libelle;
    document.getElementById('form-suppr-role').querySelector('textarea').value = '';
    document.getElementById('dlg-suppr-role').showModal();
}
function ouvrirEditType(typeId, libelle, heure) {
    document.getElementById('form-edit-type').action = '/planning/type/' + typeId;
    document.getElementById('edit-type-libelle').value = libelle;
    document.getElementById('edit-type-heure').value = heure || '';
    document.getElementById('dlg-edit-type').showModal();
}
function ouvrirModifAff(affId, role, userId) {
    document.getElementById('form-modif-aff').action = '/planning/affectation/' + affId;
    document.getElementById('modif-aff-info').textContent = 'Rôle : ' + role;
    document.getElementById('modif-aff-user').value = userId;
    document.getElementById('dlg-modif-aff').showModal();
}
function ouvrirSupprInstance(ids, date) {
    document.getElementById('suppr-inst-ids').value = ids;
    document.getElementById('suppr-inst-info').textContent = 'Réunion du ' + date;
    document.getElementById('form-suppr-instance').querySelector('textarea').value = '';
    document.getElementById('dlg-suppr-instance').showModal();
}
</script>
@endpush
