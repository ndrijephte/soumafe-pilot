@extends('layouts.app')
@section('titre', 'Tableau de bord Responsable projet')
@push('styles')
<style>
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:18px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;text-align:center;transition:transform .15s,box-shadow .15s}
.kpi:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.06)}
.kpi .val{font-size:28px;font-weight:700;color:var(--bleu)}
.kpi .lab{font-size:10.5px;color:var(--gris);margin-top:3px;text-transform:uppercase;letter-spacing:.3px}
.kpi.alerte .val{color:var(--ko)}
.kpi.warn .val{color:var(--warn)}
.kpi.ok .val{color:var(--ok)}
.deux-cols{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
@media(max-width:768px){.deux-cols{grid-template-columns:1fr}}
.section-titre{font-size:15px;font-weight:700;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:8px}
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.mini-table tr:hover{background:#f8fafc}
.progress-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden}
.progress-bar .fill{height:100%;border-radius:3px;background:var(--accent);transition:width .4s}
.fill-ok{background:var(--ok)}.fill-warn{background:var(--warn)}.fill-ko{background:var(--ko)}
.membre-card{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--bord)}
.membre-card:last-child{border-bottom:none}
.membre-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#1a5276);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex:none;box-shadow:0 2px 6px rgba(46,116,181,.25)}
.membre-nom{font-size:13px;font-weight:600;color:#1e293b}
.membre-stats{font-size:11px;color:var(--gris);margin-top:1px}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}
.sprint-item{margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--bord)}
.sprint-item:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.lien-detail{font-size:12px;color:var(--accent);text-decoration:none;font-weight:600}
.lien-detail:hover{text-decoration:underline}
.evt-line{padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:12px;display:flex;gap:8px;align-items:center}
.evt-line:last-child{border:none}
@media(max-width:900px){.deux-cols{grid-template-columns:1fr}.kpi-grid{grid-template-columns:repeat(2,1fr)}.mini-table{display:block;overflow-x:auto}}
@media(max-width:600px){.kpi-grid{grid-template-columns:1fr}.kpi .val{font-size:22px}.kpi{padding:10px}.membre-card{gap:8px}}
</style>
@endpush
@section('contenu')
<h2>Tableau de bord Responsable projet</h2>
<div class="crumb">Mes projets &rsaquo; Vue d'ensemble</div>

{{-- KPI principaux --}}
<div class="kpi-grid">
    <div class="kpi"><div class="val">{{ $totalTaches }}</div><div class="lab">Total t&acirc;ches</div></div>
    <div class="kpi ok"><div class="val">{{ $terminees }}</div><div class="lab">Termin&eacute;es</div></div>
    <div class="kpi"><div class="val">{{ $enCours }}</div><div class="lab">En cours</div></div>
    <div class="kpi {{ $bloquees > 0 ? 'alerte' : '' }}"><div class="val">{{ $bloquees }}</div><div class="lab">Bloqu&eacute;es</div></div>
    <div class="kpi {{ $enRetard > 0 ? 'warn' : '' }}"><div class="val">{{ $enRetard }}</div><div class="lab">En retard</div></div>
    <div class="kpi"><div class="val">{{ $tauxGlobal }}%</div><div class="lab">Compl&eacute;tion</div></div>
</div>

{{-- Ligne secondaire --}}
<div class="kpi-grid" style="grid-template-columns:1fr 1fr;margin-bottom:20px">
    <div class="kpi ok">
        <div class="val">{{ $termineesSemaine }}</div>
        <div class="lab">Termin&eacute;es cette semaine</div>
    </div>
    <div class="kpi">
        <div class="val">{{ $membres->count() }}</div>
        <div class="lab">Membres d'&eacute;quipe</div>
    </div>
</div>

<div class="deux-cols">
    {{-- Sprints en cours --}}
    <div class="carte">
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
            Sprints en cours
        </div>
        @forelse($sprintsEnCours as $s)
        <div class="sprint-item">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <div style="font-size:13px;font-weight:600;color:var(--bleu)">Sprint {{ $s->numero }} &mdash; {{ $s->projet->libelle ?? '' }}</div>
                @if($s->jours_restants !== null)
                <span class="badge {{ $s->jours_restants <= 3 ? 'b-ko' : 'b-ok' }}">{{ $s->jours_restants }}j</span>
                @endif
            </div>
            <div style="font-size:11.5px;color:var(--gris);margin-bottom:6px">{{ $s->objectif }}</div>
            <div class="progress-bar" style="height:8px"><div class="fill" style="width:{{ $s->progression ?? 0 }}%"></div></div>
            <div style="display:flex;justify-content:space-between;margin-top:4px">
                <span style="font-size:10.5px;color:var(--gris)">{{ $s->stats_terminees ?? 0 }}/{{ $s->stats_total ?? 0 }} t&acirc;ches</span>
                <a href="{{ route('sprints.show', $s) }}" class="lien-detail">D&eacute;tail &rarr;</a>
            </div>
        </div>
        @empty
        <div style="text-align:center;color:var(--gris);font-size:13px;padding:20px">Aucun sprint en cours.</div>
        @endforelse
    </div>

    {{-- Équipe --}}
    <div class="carte">
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            &Eacute;quipe ({{ $membres->count() }})
        </div>
        @foreach($membres as $m)
        @php $barClass = $m->taux >= 70 ? 'fill-ok' : ($m->taux >= 40 ? 'fill-warn' : 'fill-ko'); @endphp
        <div class="membre-card">
            <div class="membre-avatar">{{ mb_strtoupper(mb_substr($m->name,0,1).mb_substr($m->prenom ?? '',0,1)) }}</div>
            <div style="flex:1">
                <div class="membre-nom">{{ $m->nomComplet() }}</div>
                <div class="membre-stats">
                    {{ $m->taches_en_cours }} en cours &middot;
                    @if($m->taches_bloquees > 0)<span style="color:var(--ko)">{{ $m->taches_bloquees }} bloqu&eacute;e(s)</span>@else 0 bloqu&eacute;e @endif
                    &middot; {{ $m->taches_terminees }}/{{ $m->taches_total }}
                </div>
                <div class="progress-bar" style="margin-top:4px;height:4px"><div class="fill {{ $barClass }}" style="width:{{ $m->taux }}%"></div></div>
            </div>
            <span style="font-size:12px;font-weight:700;color:var(--bleu)">{{ $m->taux }}%</span>
        </div>
        @endforeach
        @if($membres->isEmpty())
        <div style="text-align:center;color:var(--gris);font-size:13px;padding:20px">Aucun membre affect&eacute;.</div>
        @endif
    </div>
</div>

<div class="deux-cols">
    {{-- Tâches bloquées --}}
    <div class="carte">
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            T&acirc;ches bloqu&eacute;es
        </div>
        <table class="mini-table">
            <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>Projet</th></tr></thead>
            <tbody>
            @forelse($topBloquees as $t)
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 40) }}</a></td>
                <td>{{ $t->user->nomComplet() }}</td>
                <td style="font-size:11px;color:var(--gris)">{{ $t->projet->libelle ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="color:var(--gris);text-align:center;padding:14px">Aucune t&acirc;che bloqu&eacute;e</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Tâches en retard --}}
    <div class="carte">
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            T&acirc;ches en retard
        </div>
        <table class="mini-table">
            <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>&Eacute;ch&eacute;ance</th><th>Retard</th></tr></thead>
            <tbody>
            @forelse($tachesEnRetard as $t)
            @php $jRetard = (int) $t->echeance->diffInDays(today()); @endphp
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 35) }}</a></td>
                <td>{{ $t->user->nomComplet() }}</td>
                <td style="font-size:11px;color:var(--ko)">{{ $t->echeance->format('d/m') }}</td>
                <td><span class="badge b-ko">{{ $jRetard }}j</span></td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:var(--gris);text-align:center;padding:14px">Aucune t&acirc;che en retard</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Répartition par projet --}}
<div class="carte" style="margin-bottom:16px">
    <div class="section-titre">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        R&eacute;partition par projet
    </div>
    <table class="mini-table">
        <thead><tr><th>Projet</th><th>T&acirc;ches</th><th>Termin&eacute;es</th><th>En retard</th><th>Progression</th></tr></thead>
        <tbody>
        @foreach($parProjet as $p)
        @php $pct = $p->taches_count > 0 ? round(($p->terminees_count / $p->taches_count) * 100) : 0; @endphp
        <tr>
            <td style="font-weight:600">{{ $p->libelle }}</td>
            <td>{{ $p->taches_count }}</td>
            <td>{{ $p->terminees_count }}</td>
            <td>@if($p->en_retard_count > 0)<span class="badge b-ko">{{ $p->en_retard_count }}</span>@else <span style="color:var(--gris)">&mdash;</span> @endif</td>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <div class="progress-bar" style="min-width:80px;flex:1"><div class="fill {{ $pct >= 70 ? 'fill-ok' : ($pct >= 40 ? 'fill-warn' : '') }}" style="width:{{ $pct }}%"></div></div>
                    <span style="font-size:11px;font-weight:700;min-width:30px;text-align:right">{{ $pct }}%</span>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Événements récents --}}
@if($evenements->count() > 0)
<div class="carte">
    <div class="section-titre">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Faits marquants (30 derniers jours)
    </div>
    @foreach($evenements as $e)
    <div class="evt-line">
        <span style="font-size:11px;color:var(--gris);min-width:50px">{{ $e->date->format('d/m') }}</span>
        @php $eCls = match($e->type ?? 'autre') { 'interruption_service' => 'background:#fee2e2;color:#dc2626', 'absence' => 'background:#fef3c7;color:#b45309', 'condoleance' => 'background:#e2e8f0;color:#475569', 'reunion_exceptionnelle' => 'background:#dbeafe;color:#1d4ed8', default => 'background:#f1f5f9;color:#64748b' }; @endphp
        <span style="padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600;{{ $eCls }}">{{ $e->labelType() }}</span>
        <span style="flex:1">{{ $e->description }}</span>
        <span style="font-size:11px;color:var(--gris)">{{ $e->user->nomComplet() }}</span>
    </div>
    @endforeach
</div>
@endif
@endsection
