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
.type-card{background:var(--carte);border:2px solid var(--bord);border-radius:12px;padding:14px;cursor:pointer;transition:all .2s;text-decoration:none;color:inherit;display:flex;align-items:center;gap:10px}
.type-card:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.type-card.actif{border-color:var(--type-color);box-shadow:0 2px 10px rgba(0,0,0,.1)}
.type-card .tc-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex:none}
.type-card .tc-info{flex:1;min-width:0}
.type-card .tc-nom{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.type-card .tc-meta{font-size:10px;color:var(--gris);margin-top:2px;display:flex;align-items:center;gap:4px}
.type-card .tc-rec{font-size:9px;font-weight:700;padding:1px 6px;border-radius:5px;color:#fff}
.type-card .tc-roles{font-size:10px;color:var(--gris)}

/* Tableau planning */
.plan-table{width:100%;border-collapse:collapse;font-size:12.5px}
.plan-table th{padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;text-align:center;border-bottom:2px solid var(--bord)}
.plan-table th:first-child{text-align:left}
.plan-table td{padding:8px 10px;border-bottom:1px solid var(--bord);text-align:center;vertical-align:middle}
.plan-table td:first-child{text-align:left;font-weight:600;color:var(--bleu);white-space:nowrap}
.plan-table tr:hover{background:#f8fafc}

/* En-tête type coloré */
.type-header{display:flex;align-items:center;gap:10px;margin-bottom:12px}
.type-header .th-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex:none}
.type-header .th-info{flex:1}
.type-header .th-nom{font-size:16px;font-weight:700}
.type-header .th-rec{font-size:10px;font-weight:600;padding:2px 8px;border-radius:6px;color:#fff;margin-left:6px}

/* Cellule affectation */
.cell-aff{display:flex;flex-direction:column;align-items:center;gap:3px}
.cell-aff .nom{font-size:12px;font-weight:600;color:#1e293b}
.cell-aff .statut{font-size:9px;font-weight:700;padding:1px 6px;border-radius:6px}
.s-prevu{background:#dbeafe;color:#1d4ed8}
.s-present{background:#dcfce7;color:var(--ok)}
.s-absent{background:#fee2e2;color:var(--ko)}
.s-remplace{background:#fef3c7;color:#b45309}
.cell-aff .remplac{font-size:10px;color:var(--gris);font-style:italic}
.cell-aff .btn-abs{background:none;border:none;cursor:pointer;color:var(--gris);padding:2px;border-radius:4px}
.cell-aff .btn-abs:hover{color:var(--ko);background:#fee2e2}

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

@media(max-width:768px){
    .admin-grid{grid-template-columns:1fr}
    .types-grid{grid-template-columns:1fr 1fr}
    .plan-table{display:block;overflow-x:auto}
    .nav-semaine{gap:8px}
}
@media(max-width:480px){
    .types-grid{grid-template-columns:1fr}
}
</style>
@endpush

@section('contenu')
@php
    $ic = fn(string $d, int $s=17) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
    $semainePrecedente = $debut->copy()->subWeek()->format('Y-m-d');
    $semaineSuivante = $debut->copy()->addWeek()->format('Y-m-d');

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
    @php $c = $t->couleur(); @endphp
    <a href="{{ route('planning.index', ['type' => $t->id, 'semaine' => $debut->format('Y-m-d')]) }}"
       class="type-card {{ $type && $type->id === $t->id ? 'actif' : '' }}"
       style="{{ $type && $type->id === $t->id ? '--type-color:'.$c : '' }}">
        <div class="tc-icon" style="background:{{ $c }}15;color:{{ $c }}">
            {!! $ic($t->iconePath(), 20) !!}
        </div>
        <div class="tc-info">
            <div class="tc-nom">{{ $t->libelle }}</div>
            <div class="tc-meta">
                <span class="tc-rec" style="background:{{ $c }}">{{ $t->recurrenceLabel() }}</span>
                <span class="tc-roles">{{ $t->roles->count() }} rôles</span>
            </div>
        </div>
    </a>
    @endforeach
    @if($estPilote && auth()->user()->estAdmin())
    <div class="type-card" style="border-style:dashed;cursor:pointer;justify-content:center;color:var(--gris)" onclick="document.getElementById('dlg-type').showModal()">
        {!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',20) !!}
        <span style="font-size:12px;font-weight:600">Nouveau type</span>
    </div>
    @endif
</div>

{{-- Navigation semaine --}}
<div class="nav-semaine">
    <a href="{{ route('planning.index', ['type' => $type?->id, 'semaine' => $semainePrecedente]) }}">
        {!! $ic('<polyline points="15 18 9 12 15 6"/>',18) !!} Précédente
    </a>
    <span class="sem-label">
        Semaine du {{ $debut->isoFormat('D MMM') }} au {{ $fin->isoFormat('D MMM YYYY') }}
    </span>
    <a href="{{ route('planning.index', ['type' => $type?->id, 'semaine' => $semaineSuivante]) }}">
        Suivante {!! $ic('<polyline points="9 18 15 12 9 6"/>',18) !!}
    </a>
</div>

@if($type)
@php $tc = $type->couleur(); @endphp

{{-- En-tête type + tableau --}}
<div class="panneau">
    <div class="type-header">
        <div class="th-icon" style="background:{{ $tc }}15;color:{{ $tc }}">
            {!! $ic($type->iconePath(), 22) !!}
        </div>
        <div class="th-info">
            <span class="th-nom" style="color:{{ $tc }}">{{ $type->libelle }}</span>
            <span class="th-rec" style="background:{{ $tc }}">{{ $type->recurrenceLabel() }}</span>
        </div>
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
        <div style="overflow-x:auto">
        <table class="plan-table">
            <thead>
                <tr>
                    <th>Rôle</th>
                    @foreach($jours as $j)
                    <th style="border-bottom-color:{{ $tc }}">{{ $j->isoFormat('ddd D MMM') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($type->roles as $role)
                <tr>
                    <td>{!! $ic('<circle cx="12" cy="12" r="3"/>',12) !!} {{ $role->libelle }}</td>
                    @foreach($jours as $j)
                    @php $aff = $affMap[$j->format('Y-m-d') . '_' . $role->id] ?? null; @endphp
                    <td>
                        @if($aff)
                        <div class="cell-aff">
                            <span class="nom">{{ $aff->user?->nomComplet() ?? '—' }}</span>
                            <span class="statut s-{{ $aff->statut }}">{{ $aff->statutLabel() }}</span>
                            @if($aff->statut === 'remplace' && $aff->remplacant)
                                <span class="remplac">par {{ $aff->remplacant->nomComplet() }}</span>
                            @endif
                            @if(($estPilote || auth()->id() === $aff->user_id) && $aff->statut === 'prevu')
                            <button class="btn-abs" title="Signaler absence"
                                onclick="ouvrirAbsence({{ $aff->id }}, '{{ addslashes($aff->user?->nomComplet()) }}', '{{ addslashes($aff->role->libelle) }}', '{{ $j->format('d/m/Y') }}')">
                                {!! $ic('<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>',14) !!}
                            </button>
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
</div>

{{-- Participants + Générer --}}
<div class="admin-grid">
    <div class="panneau">
        <h3>
            {!! $ic('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') !!}
            Participants ({{ $participants->count() }})
        </h3>
        <div class="part-list">
            @forelse($participants as $p)
            <span class="part-chip">
                {{ $p->user?->nomComplet() }}
                @if($estPilote)
                <form method="POST" action="{{ route('planning.retirer', $p) }}" style="display:inline" onsubmit="return confirm('Retirer ce participant ?')">
                    @csrf
                    <button type="submit" class="retirer" title="Retirer">&times;</button>
                </form>
                @endif
            </span>
            @empty
            <span style="color:var(--gris);font-size:12px">Aucun participant</span>
            @endforelse
        </div>
        @if($estPilote)
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
        @endif
    </div>

    @if($estPilote)
    <div class="panneau">
        <h3>
            {!! $ic('<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>') !!}
            Générer le planning
        </h3>
        <form method="POST" action="{{ route('planning.generer') }}">
            @csrf
            <input type="hidden" name="type_reunion_id" value="{{ $type->id }}">
            <label>Date début <span class="req">*</span></label>
            <input type="date" name="date_debut" required value="{{ $debut->format('Y-m-d') }}" style="margin-bottom:8px">
            <label>Date fin <span class="req">*</span></label>
            <input type="date" name="date_fin" required value="{{ $fin->format('Y-m-d') }}" style="margin-bottom:12px">
            @if($type->recurrence === 'fin_sprint')
            <p style="font-size:11px;color:#b45309;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:8px 10px;margin-bottom:12px">
                {!! $ic('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',14) !!}
                Ce type est lié à la fin de sprint. Une seule date de réunion sera créée sur le premier jour ouvré de la période.
            </p>
            @else
            <p style="font-size:11px;color:var(--gris);margin-bottom:12px">
                Rotation entre {{ $participants->count() }} participants. Les affectations « Prévu » existantes seront remplacées.
            </p>
            @endif
            <button type="submit" class="btn" onclick="return confirm('Générer le planning pour cette période ?')">
                {!! $ic('<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',14) !!} Générer
            </button>
        </form>
    </div>
    @endif
</div>

{{-- Admin rôles --}}
@if($estPilote)
<div class="panneau" style="margin-top:16px">
    <h3>
        {!! $ic('<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>') !!}
        Rôles — {{ $type->libelle }}
    </h3>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        @forelse($type->roles as $r)
        <span style="background:{{ $tc }}12;color:{{ $tc }};border:1px solid {{ $tc }}30;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600">
            {{ $r->ordre }}. {{ $r->libelle }}
        </span>
        @empty
        <span style="color:var(--gris);font-size:12px">Aucun rôle</span>
        @endforelse
    </div>
    @if(auth()->user()->estAdmin())
    <button class="btn sm sec" onclick="document.getElementById('dlg-role').showModal()">
        {!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Nouveau rôle
    </button>
    @endif
</div>
@endif

@else
<div class="panneau">
    <div class="vide">Aucun type de réunion configuré. Demandez à un administrateur d'en créer.</div>
</div>
@endif

{{-- Dialog Absence --}}
<dialog id="dlg-absence">
    <h3 style="margin-bottom:14px;color:var(--bleu);display:flex;align-items:center;gap:8px">
        {!! $ic('<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>') !!}
        Signaler une absence
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
    <h3 style="margin-bottom:14px;color:var(--bleu)">Nouveau rôle — {{ $type->libelle }}</h3>
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
@endif

@endsection

@push('scripts')
<script>
function ouvrirAbsence(affId, nom, role, date) {
    document.getElementById('form-absence').action = '/planning/' + affId + '/absence';
    document.getElementById('abs-info').textContent = nom + ' — ' + role + ' le ' + date;
    document.getElementById('abs-remplacant').value = '';
    document.getElementById('dlg-absence').showModal();
}
</script>
@endpush
