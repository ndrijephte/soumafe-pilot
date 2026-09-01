@extends('layouts.app')
@section('sousTitre', 'Reporting opérationnel')
@section('titre', 'Reporting')
@push('styles')
<style>
.filtres{display:flex;gap:8px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
.filtres select,.filtres input{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.filtres .btn{padding:6px 14px;font-size:12px}
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:18px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px;text-align:center}
.kpi .val{font-size:24px;font-weight:700;color:var(--bleu)}
.kpi .lab{font-size:10.5px;color:var(--gris);margin-top:2px;text-transform:uppercase}
.kpi.ok .val{color:var(--ok)}.kpi.ko .val{color:var(--ko)}.kpi.warn .val{color:var(--warn)}
.section-titre{font-size:15px;font-weight:700;color:var(--bleu);margin:20px 0 10px;display:flex;align-items:center;gap:8px}
.tbl{width:100%;border-collapse:collapse;font-size:12.5px}
.tbl th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:11px;text-transform:uppercase;position:sticky;top:0}
.tbl td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.tbl tr:hover{background:#f8fafc}
.bar-wrap{background:#e2e8f0;border-radius:4px;height:8px;min-width:60px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;background:var(--accent)}
.bar-ok{background:var(--ok)}.bar-warn{background:var(--warn)}.bar-ko{background:var(--ko)}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}
.deux-cols{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:900px){.deux-cols{grid-template-columns:1fr}.kpi-grid{grid-template-columns:repeat(2,1fr)}.filtres{flex-direction:column;align-items:stretch}.tbl{display:block;overflow-x:auto}}
@media(max-width:600px){.kpi-grid{grid-template-columns:1fr}.kpi .val{font-size:20px}.kpi{padding:10px}.sprint-card{padding:10px}}
.sprint-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px;margin-bottom:10px}
.evt-line{padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px;display:flex;gap:8px;align-items:center}
.evt-line:last-child{border:none}
.export-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;background:var(--accent);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;box-shadow:0 2px 6px rgba(46,116,181,.2);transition:transform .15s,box-shadow .15s}
.export-btn:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(46,116,181,.3)}
.export-btn:active{transform:translateY(0)}
</style>
@endpush
@section('contenu')
<h2>Reporting &amp; Indicateurs</h2>
<div class="crumb">Pilotage &rsaquo; Synth&egrave;se d'activit&eacute;</div>

<form class="filtres" method="GET" action="{{ route('reporting.index') }}">
    <select name="periode" onchange="togglePerso(this.value)">
        <option value="semaine" {{ $periode==='semaine'?'selected':'' }}>Cette semaine</option>
        <option value="mois" {{ $periode==='mois'?'selected':'' }}>Ce mois</option>
        <option value="trimestre" {{ $periode==='trimestre'?'selected':'' }}>Ce trimestre</option>
        <option value="personnalise" {{ $periode==='personnalise'?'selected':'' }}>Personnalis&eacute;</option>
    </select>
    <span id="perso-dates" style="{{ $periode==='personnalise'?'':'display:none' }}">
        <input type="date" name="date_debut" value="{{ $debut->format('Y-m-d') }}">
        <input type="date" name="date_fin" value="{{ $fin->format('Y-m-d') }}">
    </span>
    <button class="btn" style="background:var(--accent);color:#fff;border:none;border-radius:8px;cursor:pointer">Actualiser</button>
    <span style="font-size:11px;color:var(--gris);margin-left:8px">{{ $debut->format('d/m/Y') }} &mdash; {{ $fin->format('d/m/Y') }}</span>
</form>
<div style="margin-bottom:14px">
    <a href="{{ route('reporting.imprimer', ['periode' => $periode, 'date_debut' => $debut->format('Y-m-d'), 'date_fin' => $fin->format('Y-m-d')]) }}" class="btn" style="font-size:12px;padding:6px 14px;text-decoration:none" target="_blank">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="vertical-align:middle;margin-right:4px"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Imprimer / PDF
    </a>
</div>

{{-- KPI globaux --}}
<div class="kpi-grid">
    <div class="kpi"><div class="val">{{ $creees }}</div><div class="lab">T&acirc;ches cr&eacute;&eacute;es</div></div>
    <div class="kpi ok"><div class="val">{{ $terminees }}</div><div class="lab">T&acirc;ches termin&eacute;es</div></div>
    <div class="kpi"><div class="val">{{ $aTemps }}</div><div class="lab">T&acirc;ches &agrave; temps</div></div>
    <div class="kpi warn"><div class="val">{{ $anticipees }}</div><div class="lab">T&acirc;ches anticip&eacute;es</div></div>
    <div class="kpi ko"><div class="val">{{ $enRetardClotures }}</div><div class="lab">T&acirc;ches en retard (cl&ocirc;t.)</div></div>
    <div class="kpi {{ $enRetardOuvert > 0 ? 'ko' : '' }}"><div class="val">{{ $enRetardOuvert }}</div><div class="lab">T&acirc;ches en retard (ouvert)</div></div>
    <div class="kpi {{ $bloquees > 0 ? 'ko' : '' }}"><div class="val">{{ $bloquees }}</div><div class="lab">T&acirc;ches bloqu&eacute;es</div></div>
    <div class="kpi"><div class="val">{{ $tauxGlobal }}%</div><div class="lab">Taux de compl&eacute;tion</div></div>
</div>

{{-- Par collaborateur --}}
<div class="section-titre">
    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
    Performance par collaborateur
</div>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="tbl">
<thead><tr><th>Collaborateur</th><th>P&ocirc;le</th><th>Total</th><th>P&eacute;riode</th><th>Retard</th><th>Bloq.</th><th>Taux</th><th>Objectifs</th><th>Assiduit&eacute;</th></tr></thead>
<tbody>
@foreach($collaborateurs as $c)
@php
    $s = $c->stats;
    $coulBar = $s['taux'] >= 70 ? 'bar-ok' : ($s['taux'] >= 40 ? 'bar-warn' : 'bar-ko');
    $coulAss = $s['assiduite'] >= 80 ? 'b-ok' : ($s['assiduite'] >= 50 ? 'b-warn' : 'b-ko');
    $sObj = $s['score_objectifs'];
    $coulObj = $sObj === null ? 'b-warn' : ($sObj >= 70 ? 'b-ok' : ($sObj >= 40 ? 'b-warn' : 'b-ko'));
@endphp
<tr>
    <td><strong>{{ $c->nomComplet() }}</strong></td>
    <td style="font-size:11px;color:var(--gris)">{{ $c->pole?->libelle ?? '—' }}</td>
    <td>{{ $s['terminees'] }}/{{ $s['total'] }}</td>
    <td><strong>{{ $s['terminees_periode'] }}</strong></td>
    <td>@if($s['en_retard'] > 0)<span class="badge b-ko">{{ $s['en_retard'] }}</span>@else — @endif</td>
    <td>@if($s['bloquees'] > 0)<span class="badge b-warn">{{ $s['bloquees'] }}</span>@else — @endif</td>
    <td>
        <div style="display:flex;align-items:center;gap:6px">
            <div class="bar-wrap"><div class="bar-fill {{ $coulBar }}" style="width:{{ $s['taux'] }}%"></div></div>
            <span style="font-size:11px;font-weight:600">{{ $s['taux'] }}%</span>
        </div>
    </td>
    <td><span class="badge {{ $coulObj }}">{{ $sObj !== null ? $sObj.'%' : '—' }}</span></td>
    <td><span class="badge {{ $coulAss }}">{{ $s['assiduite'] }}%</span></td>
</tr>
@endforeach
</tbody>
</table>
</div>

<div class="deux-cols">
    {{-- Par projet --}}
    <div>
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            Par projet
        </div>
        <div class="carte" style="padding:0;overflow-x:auto">
        <table class="tbl">
        <thead><tr><th>Projet</th><th>T&acirc;ches</th><th>Term.</th><th>Retard</th><th>Bloq.</th><th>Progr.</th></tr></thead>
        <tbody>
        @foreach($projets as $p)
        @php $pct = $p->taches_count > 0 ? round(($p->terminees_count / $p->taches_count) * 100) : 0; @endphp
        <tr>
            <td><strong>{{ $p->libelle }}</strong></td>
            <td>{{ $p->taches_count }}</td>
            <td>{{ $p->terminees_count }}</td>
            <td>@if($p->en_retard_count > 0)<span class="badge b-ko">{{ $p->en_retard_count }}</span>@else — @endif</td>
            <td>@if($p->bloquees_count > 0)<span class="badge b-warn">{{ $p->bloquees_count }}</span>@else — @endif</td>
            <td>
                <div style="display:flex;align-items:center;gap:6px">
                    <div class="bar-wrap"><div class="bar-fill" style="width:{{ $pct }}%"></div></div>
                    <span style="font-size:11px;font-weight:600">{{ $pct }}%</span>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
        </table>
        </div>
    </div>

    {{-- Sprints en cours --}}
    <div>
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
            Sprints en cours
        </div>
        @forelse($sprints as $sp)
        <div class="sprint-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
                <div>
                    <strong style="font-size:13px">Sprint {{ $sp->numero }}</strong>
                    <span style="font-size:11px;color:var(--gris);margin-left:6px">{{ $sp->projet->libelle ?? '' }}</span>
                </div>
                @if($sp->jours_restants !== null)
                <span class="badge {{ $sp->jours_restants <= 2 ? 'b-ko' : 'b-ok' }}">{{ $sp->jours_restants }}j restant(s)</span>
                @endif
            </div>
            <div style="font-size:11px;color:var(--gris);margin-bottom:4px">{{ $sp->stats_terminees }}/{{ $sp->stats_total }} t&acirc;ches &middot; {{ $sp->objectif ?? '' }}</div>
            <div class="bar-wrap" style="height:10px"><div class="bar-fill" style="width:{{ $sp->progression }}%"></div></div>
        </div>
        @empty
        <div class="carte" style="text-align:center;color:var(--gris);font-size:13px;padding:20px">Aucun sprint en cours.</div>
        @endforelse
    </div>
</div>

{{-- Absences signalées --}}
@if($absences->count() > 0)
<div class="section-titre">
    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    Absences signal&eacute;es ({{ $absences->count() }})
</div>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="tbl">
<thead><tr><th>Collaborateur</th><th>Type</th><th>Du</th><th>Au</th><th>Motif</th><th>D&eacute;clar&eacute; par</th></tr></thead>
<tbody>
@foreach($absences as $abs)
@php
    $coulType = match($abs->type) {
        'conge' => 'b-ok', 'mission' => 'b-warn', 'maladie' => 'b-ko', default => 'b-warn'
    };
@endphp
<tr>
    <td><strong>{{ $abs->user->nomComplet() ?? '—' }}</strong></td>
    <td><span class="badge {{ $coulType }}">{{ $abs->labelType() }}</span></td>
    <td style="font-size:11px">{{ $abs->date_debut->format('d/m/Y') }}</td>
    <td style="font-size:11px">{{ $abs->date_fin->format('d/m/Y') }}</td>
    <td style="font-size:11px;color:var(--gris)">{{ $abs->motif ?? '—' }}</td>
    <td style="font-size:11px">{{ $abs->declarant?->nomComplet() ?? '—' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endif

{{-- Événements de la période --}}
@if($evenements->count() > 0)
<div class="section-titre">
    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    &Eacute;v&eacute;nements de la p&eacute;riode ({{ $evenements->count() }})
</div>
<div class="carte">
    @foreach($evenements as $ev)
    @php $c = $ev->couleurType(); @endphp
    <div class="evt-line">
        <span style="font-size:11px;color:var(--gris);min-width:55px">{{ $ev->date->format('d/m') }}</span>
        <span style="padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600;background:{{ $c['fond'] }};color:{{ $c['texte'] }}">{{ $ev->labelType() }}</span>
        <span style="flex:1">{{ $ev->description }}</span>
        <span style="font-size:11px;color:var(--gris)">{{ $ev->user->nomComplet() }}</span>
        @if($ev->estTraite())
        <span class="badge b-ok">Trait&eacute;</span>
        @else
        <span class="badge b-warn">En attente</span>
        @endif
    </div>
    @endforeach
</div>
@endif
{{-- Exports --}}
<div class="section-titre" style="margin-top:20px">
    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Exporter les donn&eacute;es
</div>
<div class="carte" style="display:flex;gap:12px;flex-wrap:wrap;padding:16px">
    <a href="{{ route('export.collaborateurs') }}" class="export-btn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Collaborateurs
    </a>
    <a href="{{ route('export.taches') }}" class="export-btn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        T&acirc;ches
    </a>
    <a href="{{ route('export.evenements') }}" class="export-btn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        &Eacute;v&eacute;nements
    </a>
</div>
@endsection

@push('scripts')
<script>
function togglePerso(v){
    document.getElementById('perso-dates').style.display = v === 'personnalise' ? '' : 'none';
}
</script>
@endpush
