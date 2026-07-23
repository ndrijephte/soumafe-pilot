@extends('layouts.app')
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
@media(max-width:900px){.deux-cols{grid-template-columns:1fr}}
.sprint-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px;margin-bottom:10px}
.evt-line{padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px;display:flex;gap:8px;align-items:center}
.evt-line:last-child{border:none}
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

{{-- KPI globaux --}}
<div class="kpi-grid">
    <div class="kpi"><div class="val">{{ $creees }}</div><div class="lab">Cr&eacute;&eacute;es</div></div>
    <div class="kpi ok"><div class="val">{{ $terminees }}</div><div class="lab">Termin&eacute;es</div></div>
    <div class="kpi"><div class="val">{{ $aTemps }}</div><div class="lab">&Agrave; temps</div></div>
    <div class="kpi warn"><div class="val">{{ $anticipees }}</div><div class="lab">Anticip&eacute;es</div></div>
    <div class="kpi ko"><div class="val">{{ $enRetardClotures }}</div><div class="lab">En retard (cl&ocirc;t.)</div></div>
    <div class="kpi {{ $enRetardOuvert > 0 ? 'ko' : '' }}"><div class="val">{{ $enRetardOuvert }}</div><div class="lab">Retard ouvert</div></div>
    <div class="kpi {{ $bloquees > 0 ? 'ko' : '' }}"><div class="val">{{ $bloquees }}</div><div class="lab">Bloqu&eacute;es</div></div>
    <div class="kpi"><div class="val">{{ $tauxGlobal }}%</div><div class="lab">Compl&eacute;tion</div></div>
</div>

{{-- Par collaborateur --}}
<div class="section-titre">
    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
    Performance par collaborateur
</div>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="tbl">
<thead><tr><th>Collaborateur</th><th>P&ocirc;le</th><th>Total</th><th>P&eacute;riode</th><th>Retard</th><th>Bloq.</th><th>Taux</th><th>Assiduit&eacute;</th></tr></thead>
<tbody>
@foreach($collaborateurs as $c)
@php
    $s = $c->stats;
    $coulBar = $s['taux'] >= 70 ? 'bar-ok' : ($s['taux'] >= 40 ? 'bar-warn' : 'bar-ko');
    $coulAss = $s['assiduite'] >= 80 ? 'b-ok' : ($s['assiduite'] >= 50 ? 'b-warn' : 'b-ko');
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
@endsection

@push('scripts')
<script>
function togglePerso(v){
    document.getElementById('perso-dates').style.display = v === 'personnalise' ? '' : 'none';
}
</script>
@endpush
