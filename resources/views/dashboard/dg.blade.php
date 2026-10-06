@extends('layouts.app')
@section('sousTitre', 'Vue Direction Générale')
@section('titre', 'Tableau de bord — Direction')
@push('styles')
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;transition:transform .15s,box-shadow .15s}
.kpi:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.06)}
.kpi .lbl{font-size:11px;color:var(--gris);text-transform:uppercase;letter-spacing:.4px}
.kpi .val{font-size:28px;font-weight:700;color:var(--bleu);margin:4px 0}
.kpi .sub{font-size:11px;color:var(--gris)}
.grille{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px}
.panneau h3{font-size:14px;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.jauge-wrap{background:#e2e8f0;border-radius:6px;height:8px;overflow:hidden}
.jauge-bar{height:100%;border-radius:6px;transition:width .5s}
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.mini-table tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}
.membre-line{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--bord)}
.membre-line:last-child{border-bottom:none}
.membre-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#1a5276);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex:none;box-shadow:0 2px 6px rgba(46,116,181,.2)}
.sprint-item{margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--bord)}
.sprint-item:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.lien{font-size:12px;color:var(--accent);text-decoration:none;font-weight:600}
.lien:hover{text-decoration:underline}
.evt-line{padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:12px;display:flex;gap:8px;align-items:center}
.evt-line:last-child{border:none}
@media(max-width:900px){.grille{grid-template-columns:1fr}.kpis{grid-template-columns:repeat(2,1fr)!important}.mini-table{display:block;overflow-x:auto}}
@media(max-width:600px){.kpis{grid-template-columns:1fr!important}.kpi .val{font-size:22px}.kpi{padding:10px}.panneau{padding:10px}.membre-line{gap:8px}}
</style>
@endpush
@section('contenu')
<h2>Tableau de bord Direction</h2>

{{-- KPI principaux --}}
<div class="kpis">
    <div class="kpi"><div class="lbl">Total t&acirc;ches</div><div class="val">{{ $totalTaches }}</div></div>
    <div class="kpi"><div class="lbl">Termin&eacute;es</div><div class="val" style="color:var(--ok)">{{ $terminees }}</div><div class="sub">Cette semaine : {{ $termineesSemaine }}</div></div>
    <div class="kpi"><div class="lbl">En cours</div><div class="val" style="color:var(--accent)">{{ $enCours }}</div></div>
    <div class="kpi" @if($enRetard > 0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">En retard</div><div class="val" style="color:{{ $enRetard > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $enRetard }}</div></div>
    <div class="kpi" @if($bloquees > 0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">Bloqu&eacute;es</div><div class="val" style="color:{{ $bloquees > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $bloquees }}</div></div>
    <div class="kpi">
        <div class="lbl">Compl&eacute;tion globale</div><div class="val">{{ $tauxGlobal }}%</div>
        <div class="jauge-wrap" style="height:10px"><div class="jauge-bar" style="width:{{ $tauxGlobal }}%;background:var(--accent)"></div></div>
    </div>
</div>

{{-- Indicateurs secondaires --}}
<div class="kpis" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px">
    <div class="kpi" @if($arbitragesEnAttente > 0) style="background:#fef2f2;border-color:#fecaca" @endif>
        <div class="lbl">Arbitrages en attente</div>
        <div class="val" style="color:{{ $arbitragesEnAttente > 0 ? 'var(--ko)' : 'var(--bleu)' }}">{{ $arbitragesEnAttente }}</div>
        @if($arbitragesEnAttente > 0)<a href="{{ route('arbitrages.index') }}" class="lien">Voir &rarr;</a>@endif
    </div>
    <div class="kpi">
        <div class="lbl">Sans saisie hier</div>
        <div class="val" style="color:{{ $sansSaisieHier > 0 ? 'var(--warn)' : 'var(--ok)' }}">{{ $sansSaisieHier }}</div>
    </div>
    <div class="kpi">
        <div class="lbl">Collaborateurs actifs</div>
        <div class="val">{{ $collaborateurs->count() }}</div>
    </div>
</div>

{{-- Pointage du jour --}}
@if($pointageStats)
<div class="kpis" style="grid-template-columns:repeat(4,1fr);margin-bottom:22px">
    <div class="kpi">
        <div class="lbl">Présence aujourd'hui</div>
        <div class="val" style="color:var(--ok)">{{ $pointageStats['tauxPresence'] }}%</div>
        <div class="sub">{{ $pointageStats['presentsJour'] }} / {{ $pointageStats['totalCollabs'] }}</div>
    </div>
    <div class="kpi">
        <div class="lbl">Retards</div>
        <div class="val" style="color:{{ $pointageStats['retardsJour'] > 0 ? '#b45309' : 'var(--ok)' }}">{{ $pointageStats['retardsJour'] }}</div>
    </div>
    <div class="kpi">
        <div class="lbl">Absents</div>
        <div class="val" style="color:{{ $pointageStats['absentsJour'] > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $pointageStats['absentsJour'] }}</div>
    </div>
    <div class="kpi">
        <div class="lbl">Non pointés</div>
        <div class="val">{{ $pointageStats['totalCollabs'] - $pointageStats['presentsJour'] - $pointageStats['absentsJour'] }}</div>
        <a href="{{ route('pointage.index') }}" class="lien">Détail &rarr;</a>
    </div>
</div>
@endif

{{-- Tendance + Taux livraison --}}
<div class="grille">
    <div class="panneau">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Tendance (4 semaines)
        </h3>
        <div style="display:flex;align-items:flex-end;gap:12px;height:130px;padding:20px 0 8px">
            @foreach($tendanceHebdo as $s)
            @php $h = round(($s['count'] / $tendanceMax) * 80); @endphp
            <div style="flex:1;display:flex;flex-direction:column;align-items:center">
                <span style="font-size:12px;font-weight:700;color:var(--bleu);margin-bottom:4px">{{ $s['count'] }}</span>
                <div style="width:100%;max-width:40px;height:{{ max($h, 4) }}px;background:linear-gradient(180deg,var(--accent),#1a5276);border-radius:6px 6px 0 0"></div>
                <span style="font-size:10px;color:var(--gris);margin-top:4px">{{ $s['label'] }}</span>
            </div>
            @endforeach
        </div>
        <div style="font-size:11px;color:var(--gris);text-align:center;margin-top:4px">Tâches terminées (global)</div>
    </div>
    @if($sprintsTermines->count() > 0)
    <div class="panneau">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Livraison sprints termin&eacute;s
        </h3>
        @foreach($sprintsTermines as $st)
        @php $livColor = $st->taux_livraison >= 80 ? 'var(--ok)' : ($st->taux_livraison >= 50 ? 'var(--warn)' : 'var(--ko)'); @endphp
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
            <span style="font-size:12px;font-weight:600;min-width:140px;color:var(--bleu)">S{{ $st->numero }} {{ $st->projet?->libelle ?? '' }}</span>
            <div style="flex:1;background:#e2e8f0;border-radius:6px;height:8px;overflow:hidden">
                <div style="height:100%;width:{{ $st->taux_livraison }}%;background:{{ $livColor }};border-radius:6px"></div>
            </div>
            <span style="font-size:12px;font-weight:700;min-width:40px;text-align:right">{{ $st->taux_livraison }}%</span>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- Sprints en cours + Performance équipe --}}
<div class="grille">
    <div class="panneau">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
            Sprints en cours ({{ $sprintsEnCours->count() }})
        </h3>
        @forelse($sprintsEnCours as $s)
        <div class="sprint-item">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                <span style="font-size:13px;font-weight:600;color:var(--bleu)">Sprint {{ $s->numero }} &mdash; {{ $s->projet->libelle ?? '' }}</span>
                @if($s->jours_restants !== null)
                <span class="badge {{ $s->jours_restants <= 3 ? 'b-ko' : 'b-ok' }}">{{ $s->jours_restants }}j</span>
                @endif
            </div>
            <div class="jauge-wrap"><div class="jauge-bar" style="width:{{ $s->progression ?? 0 }}%;background:var(--accent)"></div></div>
            <div style="display:flex;justify-content:space-between;margin-top:3px">
                <span style="font-size:10.5px;color:var(--gris)">{{ $s->stats_terminees ?? 0 }}/{{ $s->stats_total ?? 0 }} t&acirc;ches</span>
                <a href="{{ route('sprints.show', $s) }}" class="lien">D&eacute;tail &rarr;</a>
            </div>
        </div>
        @empty
        <div style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Aucun sprint en cours.</div>
        @endforelse
    </div>

    <div class="panneau">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Performance &eacute;quipe
        </h3>
        <div data-collapse="5">
        @foreach($collaborateurs as $c)
        @php $barColor = $c->taux >= 70 ? 'var(--ok)' : ($c->taux >= 40 ? 'var(--warn)' : 'var(--ko)'); @endphp
        <div class="membre-line" data-collapse-item>
            <div class="membre-avatar">{{ mb_strtoupper(mb_substr($c->name,0,1).mb_substr($c->prenom ?? '',0,1)) }}</div>
            <div style="flex:1">
                <div style="font-size:13px;font-weight:600">{{ $c->nomComplet() }}</div>
                <div style="font-size:10.5px;color:var(--gris)">
                    {{ $c->taches_en_cours }} en cours
                    @if($c->taches_bloquees > 0)&middot; <span style="color:var(--ko)">{{ $c->taches_bloquees }} bloq.</span>@endif
                    &middot; {{ $c->taches_terminees }}/{{ $c->taches_total }}
                </div>
                <div class="jauge-wrap" style="margin-top:3px;height:4px"><div class="jauge-bar" style="width:{{ $c->taux }}%;background:{{ $barColor }}"></div></div>
            </div>
            <span style="font-size:12px;font-weight:700;color:var(--bleu)">{{ $c->taux }}%</span>
        </div>
        @endforeach
        </div>
    </div>
</div>

{{-- Tâches bloquées --}}
@if($topBloquees->count() > 0)
<div class="panneau" style="margin-bottom:20px">
    <h3>
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        T&acirc;ches bloqu&eacute;es ({{ $topBloquees->count() }})
    </h3>
    <table class="mini-table" data-collapse-table="5">
        <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>Projet</th></tr></thead>
        <tbody>
        @foreach($topBloquees as $t)
        <tr>
            <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 35) }}</a></td>
            <td>{{ $t->user->nomComplet() }}</td>
            <td style="font-size:11px;color:var(--gris)">{{ $t->projet->libelle ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Répartition par projet --}}
<div class="panneau" style="margin-bottom:20px">
    <h3>
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        R&eacute;partition par projet
    </h3>
    <table class="mini-table">
        <thead><tr><th>Projet</th><th>T&acirc;ches</th><th>Term.</th><th>Retard</th><th>Progression</th></tr></thead>
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
                    <div class="jauge-wrap" style="flex:1;min-width:80px"><div class="jauge-bar" style="width:{{ $pct }}%;background:{{ $pct >= 70 ? 'var(--ok)' : ($pct >= 40 ? 'var(--warn)' : 'var(--accent)') }}"></div></div>
                    <span style="font-size:11px;font-weight:700;min-width:30px;text-align:right">{{ $pct }}%</span>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Parc automobile --}}
@if($parc)
<div class="panneau" style="margin-bottom:20px">
    <h3>
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="22" height="12" rx="3"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 10h14"/></svg>
        Parc automobile &mdash; Exercice {{ $parc['exercice'] }}
        <a href="{{ route('reporting.parc') }}" class="lien" style="margin-left:auto">Reporting d&eacute;taill&eacute; &rarr;</a>
    </h3>
    <div class="kpis" style="margin-bottom:14px">
        <div class="kpi" style="padding:12px 14px">
            <div class="lbl">V&eacute;hicules actifs</div>
            <div class="val" style="font-size:24px">{{ $parc['vehiculesActifs'] }}<span style="font-size:12px;color:var(--gris);font-weight:400">/{{ $parc['vehiculesTotal'] }}</span></div>
        </div>
        <div class="kpi" style="padding:12px 14px">
            <div class="lbl">Recettes</div>
            <div class="val" style="font-size:22px">{{ number_format($parc['recetteMois'],0,',',' ') }}</div>
            <div class="sub" style="font-weight:600;color:{{ $parc['tauxRecette'] >= 100 ? 'var(--ok)' : ($parc['tauxRecette'] >= 80 ? 'var(--warn)' : 'var(--ko)') }}">{{ $parc['tauxRecette'] }}% de l'objectif</div>
            <div class="jauge-wrap" style="height:6px;margin-top:4px"><div class="jauge-bar" style="width:{{ min($parc['tauxRecette'],100) }}%;background:{{ $parc['tauxRecette']>=100?'var(--ok)':($parc['tauxRecette']>=80?'var(--warn)':'var(--ko)') }}"></div></div>
        </div>
        <div class="kpi" style="padding:12px 14px">
            <div class="lbl">Co&ucirc;t pannes</div>
            <div class="val" style="font-size:22px;color:var(--ko)">{{ number_format($parc['coutPannesMois'],0,',',' ') }}</div>
            <div class="sub">{{ $parc['pannesOuvertes'] }} ouverte{{ $parc['pannesOuvertes'] > 1 ? 's' : '' }}</div>
        </div>
        <div class="kpi" style="padding:12px 14px">
            <div class="lbl">P&eacute;nalit&eacute;s</div>
            <div class="val" style="font-size:22px;color:var(--warn)">{{ number_format($parc['montantPenalitesMois'],0,',',' ') }}</div>
            <div class="sub">{{ $parc['penalitesNonPayees'] }} impay&eacute;e{{ $parc['penalitesNonPayees'] > 1 ? 's' : '' }}</div>
        </div>
        <div class="kpi" style="padding:12px 14px;{{ $parc['maintenancesRetard'] > 0 ? 'background:#fef2f2;border-color:#fecaca' : '' }}">
            <div class="lbl">Maint. en retard</div>
            <div class="val" style="font-size:24px;color:{{ $parc['maintenancesRetard'] > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $parc['maintenancesRetard'] }}</div>
        </div>
        <div class="kpi" style="padding:12px 14px">
            <div class="lbl">R&eacute;sultat net</div>
            <div class="val" style="font-size:22px;color:{{ $parc['resultatNet'] >= 0 ? 'var(--ok)' : 'var(--ko)' }}">{{ $parc['resultatNet'] >= 0 ? '+' : '' }}{{ number_format($parc['resultatNet'],0,',',' ') }}</div>
            <div class="sub">Recettes &minus; Pannes &minus; P&eacute;nalit&eacute;s</div>
            @php
                $totDG = max($parc['recetteMois'], 1);
                $pctPanDG = round(($parc['coutPannesMois'] / $totDG) * 100);
                $pctPenDG = round(($parc['montantPenalitesMois'] / $totDG) * 100);
                $pctNetDG = max(100 - $pctPanDG - $pctPenDG, 0);
            @endphp
            <div style="display:flex;height:6px;border-radius:4px;overflow:hidden;margin-top:4px" title="Vert: net, Rouge: pannes, Orange: p&eacute;nalit&eacute;s">
                <div style="width:{{ $pctNetDG }}%;background:var(--ok)"></div>
                <div style="width:{{ $pctPanDG }}%;background:var(--ko)"></div>
                <div style="width:{{ $pctPenDG }}%;background:var(--warn)"></div>
            </div>
        </div>
    </div>

    @if(!empty($parc['gestionnaire']))
    <div style="font-size:12px;color:var(--gris);margin-bottom:10px">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Gestionnaire : <strong style="color:var(--bleu)">{{ $parc['gestionnaire'] }}</strong>
    </div>
    @endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        @if($parc['topVehicules']->count() > 0)
        <table class="mini-table">
            <thead><tr><th>Top 5 v&eacute;hicules</th><th>Cat.</th><th style="text-align:right">Recette</th><th style="text-align:right">Objectif</th><th style="text-align:right">Taux</th></tr></thead>
            <tbody>
            @foreach($parc['topVehicules'] as $tv)
            @php $tvColor = $tv['taux'] >= 100 ? 'var(--ok)' : ($tv['taux'] >= 80 ? 'var(--warn)' : 'var(--ko)'); @endphp
            <tr>
                <td style="font-weight:600">{{ $tv['immat'] }}</td>
                <td>{{ $tv['categorie'] }}</td>
                <td style="text-align:right">{{ number_format($tv['recette'],0,',',' ') }}</td>
                <td style="text-align:right">{{ number_format($tv['objectif'],0,',',' ') }}</td>
                <td style="text-align:right">
                    <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end">
                        <div class="jauge-wrap" style="width:60px"><div class="jauge-bar" style="width:{{ min($tv['taux'],100) }}%;background:{{ $tvColor }}"></div></div>
                        <span style="font-size:11px;font-weight:700;min-width:30px">{{ $tv['taux'] }}%</span>
                    </div>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        @if($parc['topChauffeurs']->count() > 0)
        <table class="mini-table">
            <thead><tr><th>Top 3 chauffeurs</th><th>V&eacute;hicule(s)</th><th style="text-align:right">Recettes</th><th style="text-align:right">Taux</th><th style="text-align:right">R&eacute;sultat</th></tr></thead>
            <tbody>
            @foreach($parc['topChauffeurs'] as $tc)
            @php $tcColor = $tc['taux'] >= 100 ? 'var(--ok)' : ($tc['taux'] >= 80 ? 'var(--warn)' : 'var(--ko)'); @endphp
            <tr>
                <td style="font-weight:600">{{ $tc['nom'] }}</td>
                <td style="font-size:11px;color:var(--gris)">{{ $tc['vehicules'] }}</td>
                <td style="text-align:right">{{ number_format($tc['recette'],0,',',' ') }}</td>
                <td style="text-align:right">
                    <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end">
                        <div class="jauge-wrap" style="width:50px"><div class="jauge-bar" style="width:{{ min($tc['taux'],100) }}%;background:{{ $tcColor }}"></div></div>
                        <span style="font-size:11px;font-weight:700;min-width:30px">{{ $tc['taux'] }}%</span>
                    </div>
                </td>
                <td style="text-align:right;font-weight:700;color:{{ $tc['resultat'] >= 0 ? 'var(--ok)' : 'var(--ko)' }}">{{ $tc['resultat'] >= 0 ? '+' : '' }}{{ number_format($tc['resultat'],0,',',' ') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endif

{{-- Événements récents --}}
@if($evenements->count() > 0)
<div class="panneau">
    <h3>
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Faits marquants (30 derniers jours)
    </h3>
    <div data-collapse="5">
    @foreach($evenements as $e)
    <div class="evt-line" data-collapse-item>
        <span style="font-size:11px;color:var(--gris);min-width:50px">{{ $e->date->format('d/m') }}</span>
        @php $eCls = match($e->type ?? 'autre') { 'interruption_service' => 'background:#fee2e2;color:#dc2626', 'absence' => 'background:#fef3c7;color:#b45309', 'condoleance' => 'background:#e2e8f0;color:#475569', 'reunion_exceptionnelle' => 'background:#dbeafe;color:#1d4ed8', default => 'background:#f1f5f9;color:#64748b' }; @endphp
        <span style="padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600;{{ $eCls }}">{{ $e->labelType() }}</span>
        <span style="flex:1">{{ $e->description }}</span>
        <span style="font-size:11px;color:var(--gris)">{{ $e->user->nomComplet() }}</span>
    </div>
    @endforeach
    </div>
</div>
@endif

<div style="margin-top:16px">
    <a href="{{ route('reporting.index') }}" style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;font-size:13px;font-weight:600;color:#fff;background:var(--accent);text-decoration:none">Voir le Reporting complet</a>
</div>
@endsection
