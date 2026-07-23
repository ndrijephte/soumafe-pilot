@extends('layouts.app')
@section('titre', 'Tableau de bord DG')
@push('styles')
<style>
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:18px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px;text-align:center}
.kpi .val{font-size:26px;font-weight:700;color:var(--bleu)}
.kpi .lab{font-size:11px;color:var(--gris);margin-top:2px}
.kpi.alerte .val{color:var(--ko)}
.kpi.warn .val{color:var(--warn)}
.deux-cols{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
@media(max-width:768px){.deux-cols{grid-template-columns:1fr}}
.section-titre{font-size:14px;font-weight:600;color:var(--bleu);margin-bottom:8px}
.mini-table{width:100%;border-collapse:collapse;font-size:12px}
.mini-table th{text-align:left;padding:6px 8px;background:var(--fond);color:var(--gris);font-weight:600;font-size:11px;text-transform:uppercase}
.mini-table td{padding:6px 8px;border-bottom:1px solid var(--bord)}
.progress-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.progress-bar .fill{height:100%;background:var(--accent);border-radius:3px}
.lien-arb{display:inline-block;margin-top:8px;font-size:12px;color:var(--accent);text-decoration:none;font-weight:600}
</style>
@endpush
@section('contenu')
<h2>Tableau de bord Direction</h2>
<div class="crumb">Vue d'ensemble de l'activit&eacute;</div>

<div class="kpi-grid">
    <div class="kpi"><div class="val">{{ $totalTaches }}</div><div class="lab">Total t&acirc;ches</div></div>
    <div class="kpi"><div class="val">{{ $terminees }}</div><div class="lab">Termin&eacute;es</div></div>
    <div class="kpi"><div class="val">{{ $enCours }}</div><div class="lab">En cours</div></div>
    <div class="kpi {{ $bloquees > 0 ? 'alerte' : '' }}"><div class="val">{{ $bloquees }}</div><div class="lab">Bloqu&eacute;es</div></div>
    <div class="kpi {{ $enRetard > 0 ? 'warn' : '' }}"><div class="val">{{ $enRetard }}</div><div class="lab">En retard</div></div>
    <div class="kpi"><div class="val">{{ $tauxGlobal }}%</div><div class="lab">Compl&eacute;tion</div></div>
</div>

<div class="kpi-grid" style="grid-template-columns:1fr 1fr">
    <div class="kpi {{ $arbitragesEnAttente > 0 ? 'alerte' : '' }}">
        <div class="val">{{ $arbitragesEnAttente }}</div>
        <div class="lab">Arbitrages en attente</div>
        @if($arbitragesEnAttente > 0)<a href="{{ route('arbitrages.index') }}" class="lien-arb">Voir &rarr;</a>@endif
    </div>
    <div class="kpi {{ $sanseSaisieHier > 0 ? 'warn' : '' }}">
        <div class="val">{{ $sanseSaisieHier }}</div>
        <div class="lab">Sans saisie hier</div>
    </div>
</div>

<div class="deux-cols">
    {{-- Sprints en cours --}}
    <div class="carte">
        <div class="section-titre">Sprints en cours</div>
        @forelse($sprintsEnCours as $s)
        <div style="margin-bottom:10px">
            <div style="font-size:13px;font-weight:600">Sprint {{ $s->numero }} — {{ $s->projet->libelle ?? '' }}</div>
            <div style="font-size:11px;color:var(--gris)">{{ $s->objectif }}</div>
            <div class="progress-bar" style="margin-top:4px"><div class="fill" style="width:{{ $s->progression ?? 0 }}%"></div></div>
        </div>
        @empty
        <div style="font-size:12px;color:var(--gris)">Aucun sprint en cours.</div>
        @endforelse
    </div>

    {{-- Tâches bloquées --}}
    <div class="carte">
        <div class="section-titre">T&acirc;ches bloqu&eacute;es</div>
        <table class="mini-table">
            <tr><th>T&acirc;che</th><th>Collaborateur</th><th>Projet</th></tr>
            @forelse($topBloquees as $t)
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none">{{ \Illuminate\Support\Str::limit($t->intitule, 35) }}</a></td>
                <td>{{ $t->user->nomComplet() }}</td>
                <td>{{ $t->projet->libelle ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="color:var(--gris)">Aucune.</td></tr>
            @endforelse
        </table>
    </div>
</div>

{{-- Événements / Faits marquants --}}
@if($evenements->count() > 0)
<div class="carte" style="margin-bottom:16px">
    <div class="section-titre">Faits marquants (7 derniers jours)</div>
    <table class="mini-table">
        <tr><th>Date</th><th>Type</th><th>Description</th><th>Collaborateur</th><th>Impact</th></tr>
        @foreach($evenements as $e)
        <tr>
            <td style="white-space:nowrap">{{ $e->date->format('d/m') }}</td>
            <td>
                @php $eCls = match($e->type) { 'interruption_service' => 'background:#fee2e2;color:#dc2626', 'absence' => 'background:#fef3c7;color:#b45309', 'condoleance' => 'background:#e2e8f0;color:#475569', 'reunion_exceptionnelle' => 'background:#dbeafe;color:#1d4ed8', default => 'background:#f1f5f9;color:#64748b' }; @endphp
                <span style="padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600;{{ $eCls }}">{{ $e->labelType() }}</span>
            </td>
            <td>{{ $e->description }}</td>
            <td>{{ $e->user->nomComplet() }}</td>
            <td style="font-size:11px;color:var(--gris)">{{ $e->impact ?? '—' }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif

{{-- Répartition par projet --}}
<div class="carte">
    <div class="section-titre">R&eacute;partition par projet</div>
    <table class="mini-table">
        <tr><th>Projet</th><th>T&acirc;ches</th><th>Termin&eacute;es</th><th>Progression</th></tr>
        @foreach($parProjet as $p)
        @php $pct = $p->taches_count > 0 ? round(($p->terminees_count / $p->taches_count) * 100) : 0; @endphp
        <tr>
            <td style="font-weight:600">{{ $p->libelle }}</td>
            <td>{{ $p->taches_count }}</td>
            <td>{{ $p->terminees_count }}</td>
            <td><div class="progress-bar" style="min-width:80px"><div class="fill" style="width:{{ $pct }}%"></div></div></td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
