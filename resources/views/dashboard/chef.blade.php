@extends('layouts.app')
@section('titre', 'Tableau de bord Responsable projet')
@push('styles')
<style>
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:18px}
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
.membre-card{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--bord)}
.membre-card:last-child{border-bottom:none}
.membre-avatar{width:30px;height:30px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:11px;flex:none}
.membre-nom{font-size:13px;font-weight:600}
.membre-stats{font-size:11px;color:var(--gris)}
</style>
@endpush
@section('contenu')
<h2>Tableau de bord Responsable projet</h2>
<div class="crumb">Mes projets &rsaquo; Vue d'ensemble</div>

<div class="kpi-grid">
    <div class="kpi"><div class="val">{{ $totalTaches }}</div><div class="lab">T&acirc;ches</div></div>
    <div class="kpi"><div class="val">{{ $terminees }}</div><div class="lab">Termin&eacute;es</div></div>
    <div class="kpi"><div class="val">{{ $enCours }}</div><div class="lab">En cours</div></div>
    <div class="kpi {{ $bloquees > 0 ? 'alerte' : '' }}"><div class="val">{{ $bloquees }}</div><div class="lab">Bloqu&eacute;es</div></div>
    <div class="kpi {{ $enRetard > 0 ? 'warn' : '' }}"><div class="val">{{ $enRetard }}</div><div class="lab">En retard</div></div>
    <div class="kpi"><div class="val">{{ $tauxGlobal }}%</div><div class="lab">Compl&eacute;tion</div></div>
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

    {{-- Équipe --}}
    <div class="carte">
        <div class="section-titre">&Eacute;quipe ({{ $membres->count() }})</div>
        @foreach($membres as $m)
        <div class="membre-card">
            <div class="membre-avatar">{{ mb_strtoupper(mb_substr($m->name,0,1).mb_substr($m->prenom,0,1)) }}</div>
            <div>
                <div class="membre-nom">{{ $m->nomComplet() }}</div>
                <div class="membre-stats">{{ $m->taches_en_cours }} en cours · {{ $m->taches_bloquees }} bloqu&eacute;e(s)</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Tâches bloquées --}}
<div class="carte">
    <div class="section-titre">T&acirc;ches bloqu&eacute;es</div>
    <table class="mini-table">
        <tr><th>T&acirc;che</th><th>Collaborateur</th><th>Projet</th></tr>
        @forelse($topBloquees as $t)
        <tr>
            <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none">{{ \Illuminate\Support\Str::limit($t->intitule, 40) }}</a></td>
            <td>{{ $t->user->nomComplet() }}</td>
            <td>{{ $t->projet->libelle ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="3" style="color:var(--gris)">Aucune t&acirc;che bloqu&eacute;e.</td></tr>
        @endforelse
    </table>
</div>
@endsection
