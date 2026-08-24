@extends('layouts.app')
@section('titre', 'Reporting Parc')

@push('styles')
<style>
.rp-filtres{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:18px}
.rp-filtres label{font-size:12px;color:var(--gris);display:block;margin-bottom:2px}
.rp-filtres select,.rp-filtres input[type=date]{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;background:#fff}
.rp-filtres .btn-f{padding:7px 16px;border:none;border-radius:8px;background:var(--accent);color:#fff;font-size:13px;cursor:pointer;font-weight:600}
.rp-filtres .btn-f:hover{background:var(--bleu)}

.rp-kpi{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:22px}
.kpi-c{background:#fff;border:1px solid var(--bord);border-radius:12px;padding:14px 16px;text-align:center}
.kpi-v{font-size:26px;font-weight:700;color:var(--bleu)}
.kpi-l{font-size:11.5px;color:var(--gris);margin-top:2px}
.kpi-sub{font-size:11px;margin-top:4px;font-weight:600}
.kpi-ok{color:var(--ok)}.kpi-warn{color:var(--warn)}.kpi-ko{color:var(--ko)}

.rp-section{background:#fff;border:1px solid var(--bord);border-radius:12px;padding:16px 18px;margin-bottom:18px}
.rp-section h3{font-size:15px;color:var(--bleu);margin-bottom:12px;display:flex;align-items:center;gap:8px}
.rp-section h3 .ico{flex:none}

.rp-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:900px){.rp-grid2{grid-template-columns:1fr}}

/* Tableaux */
.rp-tbl{width:100%;border-collapse:collapse;font-size:12.5px}
.rp-tbl th{text-align:left;padding:8px 10px;background:#f8fafc;color:var(--gris);font-size:11px;text-transform:uppercase;border-bottom:2px solid var(--bord)}
.rp-tbl td{padding:7px 10px;border-bottom:1px solid #f1f5f9}
.rp-tbl tr:hover td{background:#f8fafc}
.rp-tbl .num{text-align:right;font-variant-numeric:tabular-nums}
.rp-tbl .pos{color:var(--ok);font-weight:600}
.rp-tbl .neg{color:var(--ko);font-weight:600}

/* Barres CSS */
.bar-wrap{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.bar-label{width:70px;font-size:11px;text-align:right;color:#475569;flex:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bar-track{flex:1;height:18px;background:#f1f5f9;border-radius:4px;position:relative;overflow:hidden}
.bar-fill{height:100%;border-radius:4px;transition:width .4s ease}
.bar-val{font-size:11px;color:#475569;width:60px;flex:none;font-variant-numeric:tabular-nums}

/* Donut CSS */
.donut-wrap{display:flex;align-items:center;gap:20px;justify-content:center;flex-wrap:wrap}
.donut{width:120px;height:120px;border-radius:50%;position:relative}
.donut-center{position:absolute;inset:25px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--bleu)}
.donut-legend{font-size:12px;line-height:2}
.donut-legend span{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:6px;vertical-align:middle}

/* Jauge taux */
.gauge{width:100%;height:22px;background:#f1f5f9;border-radius:6px;overflow:hidden;position:relative;margin-top:6px}
.gauge-fill{height:100%;border-radius:6px;transition:width .5s ease}
.gauge-txt{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#1e293b}

.lien-retour{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--accent);text-decoration:none;margin-bottom:10px}
.lien-retour:hover{text-decoration:underline}

.date-custom{display:none}
.date-custom.show{display:flex;gap:8px}
</style>
@endpush

@section('contenu')
@php
    $ico = fn(string $d) => '<svg class="ico" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
@endphp
<a href="{{ route('parc.index') }}" class="lien-retour">← Retour au parc</a>
<h2>Reporting Parc Automobile</h2>
<div class="crumb">Période : {{ \Carbon\Carbon::parse($debut)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }}</div>

{{-- ═══ FILTRES ═══ --}}
<form method="GET" action="{{ route('reporting.parc') }}" class="rp-filtres">
    <div>
        <label>Période</label>
        <select name="periode" id="sel-periode" onchange="toggleDates()">
            <option value="mois" {{ $periode==='mois'?'selected':'' }}>Mois en cours</option>
            <option value="mois_precedent" {{ $periode==='mois_precedent'?'selected':'' }}>Mois précédent</option>
            <option value="trimestre" {{ $periode==='trimestre'?'selected':'' }}>Trimestre</option>
            <option value="personnalise" {{ $periode==='personnalise'?'selected':'' }}>Personnalisé</option>
        </select>
    </div>
    <div class="date-custom {{ $periode==='personnalise'?'show':'' }}" id="dates-custom">
        <div><label>Du</label><input type="date" name="date_debut" value="{{ \Carbon\Carbon::parse($debut)->format('Y-m-d') }}"></div>
        <div><label>Au</label><input type="date" name="date_fin" value="{{ \Carbon\Carbon::parse($fin)->format('Y-m-d') }}"></div>
    </div>
    <div>
        <label>Catégorie</label>
        <select name="categorie">
            <option value="tous" {{ $categorie==='tous'?'selected':'' }}>Toutes</option>
            <option value="taxi" {{ $categorie==='taxi'?'selected':'' }}>Taxi</option>
            <option value="vtc" {{ $categorie==='vtc'?'selected':'' }}>VTC</option>
        </select>
    </div>
    <button type="submit" class="btn-f">Filtrer</button>
</form>

{{-- ═══ KPI ═══ --}}
<div class="rp-kpi">
    <div class="kpi-c">
        <div class="kpi-v">{{ number_format($recetteTotal, 0, ',', ' ') }}</div>
        <div class="kpi-l">Recettes (FCFA)</div>
        <div class="kpi-sub {{ $tauxRecette >= 100 ? 'kpi-ok' : ($tauxRecette >= 80 ? 'kpi-warn' : 'kpi-ko') }}">{{ $tauxRecette }}% de l'objectif</div>
    </div>
    <div class="kpi-c">
        <div class="kpi-v">{{ number_format($objectifTotal, 0, ',', ' ') }}</div>
        <div class="kpi-l">Objectif (FCFA)</div>
        @php $ecartG = $recetteTotal - $objectifTotal; @endphp
        <div class="kpi-sub {{ $ecartG >= 0 ? 'kpi-ok' : 'kpi-ko' }}">{{ $ecartG >= 0 ? '+' : '' }}{{ number_format($ecartG, 0, ',', ' ') }}</div>
    </div>
    <div class="kpi-c">
        <div class="kpi-v">{{ number_format($coutPannes, 0, ',', ' ') }}</div>
        <div class="kpi-l">Coût pannes (FCFA)</div>
        <div class="kpi-sub kpi-ko">{{ $nbPannes }} panne{{ $nbPannes > 1 ? 's' : '' }}</div>
    </div>
    <div class="kpi-c">
        <div class="kpi-v">{{ $delaiMoyen }} j</div>
        <div class="kpi-l">Délai moyen résolution</div>
    </div>
    <div class="kpi-c">
        <div class="kpi-v">{{ number_format($montantPenalites, 0, ',', ' ') }}</div>
        <div class="kpi-l">Pénalités (FCFA)</div>
        <div class="kpi-sub kpi-ko">{{ $nbPenalites }} infraction{{ $nbPenalites > 1 ? 's' : '' }}</div>
    </div>
    <div class="kpi-c">
        <div class="kpi-v">{{ $maintenancesRetard }}</div>
        <div class="kpi-l">Maintenances en retard</div>
        <div class="kpi-sub kpi-warn">{{ $maintenancesProches }} à venir (15j)</div>
    </div>
</div>

{{-- ═══ GRAPHIQUES RECETTES ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>') !!} Recettes vs Objectifs par jour</h3>
    @if($recettesJour->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune recette sur cette période.</p>
    @else
        @php
            $maxR = max($recettesJour->max('total'), $recettesJour->max('obj'), 1);
        @endphp
        <div style="overflow-x:auto">
            <div style="display:flex;align-items:flex-end;gap:3px;min-width:{{ $recettesJour->count() * 38 }}px;height:180px;padding-bottom:24px;position:relative">
                @foreach($recettesJour as $jr)
                    @php
                        $hR = round(($jr['total'] / $maxR) * 150);
                        $hO = round(($jr['obj'] / $maxR) * 150);
                        $jour = \Carbon\Carbon::parse($jr['jour'])->format('d');
                    @endphp
                    <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;min-width:30px">
                        <div style="display:flex;gap:2px;align-items:flex-end;height:155px">
                            <div style="width:12px;height:{{ $hR }}px;background:{{ $jr['total'] >= $jr['obj'] ? 'var(--ok)' : 'var(--ko)' }};border-radius:3px 3px 0 0" title="Recette: {{ number_format($jr['total'],0,',',' ') }}"></div>
                            <div style="width:12px;height:{{ $hO }}px;background:#cbd5e1;border-radius:3px 3px 0 0" title="Objectif: {{ number_format($jr['obj'],0,',',' ') }}"></div>
                        </div>
                        <span style="font-size:10px;color:var(--gris)">{{ $jour }}</span>
                    </div>
                @endforeach
            </div>
            <div style="display:flex;gap:16px;font-size:11px;color:var(--gris);margin-top:6px">
                <span><span style="display:inline-block;width:10px;height:10px;background:var(--ok);border-radius:2px;vertical-align:middle;margin-right:4px"></span>Recette ≥ objectif</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:var(--ko);border-radius:2px;vertical-align:middle;margin-right:4px"></span>Recette < objectif</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#cbd5e1;border-radius:2px;vertical-align:middle;margin-right:4px"></span>Objectif</span>
            </div>
        </div>
    @endif
</div>

<div class="rp-grid2">
    {{-- ═══ PANNES PAR URGENCE ═══ --}}
    <div class="rp-section">
        <h3>{!! $ico('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>') !!} Pannes par niveau d'urgence</h3>
        @php
            $urgLabels = ['critique' => 'Critique', 'haute' => 'Haute', 'moyenne' => 'Moyenne', 'faible' => 'Faible'];
            $urgColors = ['critique' => '#dc2626', 'haute' => '#f59e0b', 'moyenne' => '#2E74B5', 'faible' => '#16a34a'];
            $maxU = max($pannesUrgence->max() ?? 1, 1);
        @endphp
        @foreach($urgLabels as $code => $label)
            @php $nb = $pannesUrgence[$code] ?? 0; @endphp
            <div class="bar-wrap">
                <div class="bar-label">{{ $label }}</div>
                <div class="bar-track"><div class="bar-fill" style="width:{{ round(($nb/$maxU)*100) }}%;background:{{ $urgColors[$code] }}"></div></div>
                <div class="bar-val">{{ $nb }}</div>
            </div>
        @endforeach
    </div>

    {{-- ═══ PÉNALITÉS PAR TYPE ═══ --}}
    <div class="rp-section">
        <h3>{!! $ico('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') !!} Pénalités par type</h3>
        @if($penalitesType->isEmpty())
            <p style="color:var(--gris);font-size:13px">Aucune pénalité sur cette période.</p>
        @else
            @php $maxPT = max($penalitesType->max('montant'), 1); @endphp
            @foreach($penalitesType as $pt)
                <div class="bar-wrap">
                    <div class="bar-label" title="{{ $pt['type'] }}">{{ $pt['type'] }}</div>
                    <div class="bar-track"><div class="bar-fill" style="width:{{ round(($pt['montant']/$maxPT)*100) }}%;background:#f59e0b"></div></div>
                    <div class="bar-val">{{ number_format($pt['montant'],0,',',' ') }}</div>
                </div>
            @endforeach
        @endif
    </div>
</div>

{{-- ═══ MAINTENANCE PAR TYPE ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>') !!} État des maintenances par type</h3>
    @if($maintenanceType->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune échéance de maintenance enregistrée.</p>
    @else
        <table class="rp-tbl" data-collapse-table="8">
            <thead><tr><th>Type</th><th class="num">Total</th><th class="num">En retard</th><th class="num">À venir (15j)</th><th class="num">OK</th></tr></thead>
            <tbody>
            @foreach($maintenanceType as $mt)
                <tr>
                    <td>{{ $mt['type'] }}</td>
                    <td class="num">{{ $mt['nb'] }}</td>
                    <td class="num {{ $mt['retard'] > 0 ? 'neg' : '' }}">{{ $mt['retard'] }}</td>
                    <td class="num {{ $mt['proche'] > 0 ? 'kpi-warn' : '' }}" style="{{ $mt['proche'] > 0 ? 'color:var(--warn);font-weight:600' : '' }}">{{ $mt['proche'] }}</td>
                    <td class="num pos">{{ $mt['nb'] - $mt['retard'] - $mt['proche'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ═══ TABLEAU RECETTES PAR VÉHICULE ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>') !!} Recettes par véhicule</h3>
    @if($recettesVehicule->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune recette enregistrée.</p>
    @else
        <table class="rp-tbl" data-collapse-table="8">
            <thead><tr>
                <th>Véhicule</th><th>Cat.</th><th class="num">Jours</th>
                <th class="num">Recette</th><th class="num">Objectif</th>
                <th class="num">Écart</th><th class="num">Taux</th><th class="num">Moy/jour</th>
            </tr></thead>
            <tbody>
            @foreach($recettesVehicule as $rv)
                <tr>
                    <td><strong>{{ $rv['immat'] }}</strong></td>
                    <td>{{ $rv['categorie'] }}</td>
                    <td class="num">{{ $rv['nb_jours'] }}</td>
                    <td class="num">{{ number_format($rv['recette'],0,',',' ') }}</td>
                    <td class="num">{{ number_format($rv['objectif'],0,',',' ') }}</td>
                    <td class="num {{ $rv['ecart'] >= 0 ? 'pos' : 'neg' }}">{{ $rv['ecart'] >= 0 ? '+' : '' }}{{ number_format($rv['ecart'],0,',',' ') }}</td>
                    <td class="num">
                        <div class="gauge"><div class="gauge-fill" style="width:{{ min($rv['taux'],100) }}%;background:{{ $rv['taux']>=100?'var(--ok)':($rv['taux']>=80?'var(--warn)':'var(--ko)') }}"></div><div class="gauge-txt">{{ $rv['taux'] }}%</div></div>
                    </td>
                    <td class="num">{{ number_format($rv['moyenne'],0,',',' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ═══ TABLEAU PANNES PAR VÉHICULE ═══ --}}
<div class="rp-section">
    <h3><span class="ico">🔧</span> Pannes par véhicule</h3>
    @if($pannesVehicule->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune panne sur cette période.</p>
    @else
        <table class="rp-tbl" data-collapse-table="8">
            <thead><tr><th>Véhicule</th><th class="num">Nombre</th><th class="num">Coût total</th><th class="num">Délai moy.</th></tr></thead>
            <tbody>
            @foreach($pannesVehicule as $pv)
                <tr>
                    <td><strong>{{ $pv['immat'] }}</strong></td>
                    <td class="num">{{ $pv['nb'] }}</td>
                    <td class="num neg">{{ number_format($pv['cout'],0,',',' ') }}</td>
                    <td class="num">{{ $pv['delai'] }} j</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ═══ CLASSEMENT GLOBAL ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>') !!} Classement des véhicules (résultat net)</h3>
    @if($classement->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucun véhicule en service.</p>
    @else
        <p style="font-size:12px;color:var(--gris);margin-bottom:10px">Résultat = Recettes − Coût pannes − Pénalités</p>
        <table class="rp-tbl" data-collapse-table="10">
            <thead><tr>
                <th>#</th><th>Véhicule</th><th>Cat.</th>
                <th class="num">Recette</th><th class="num">Taux obj.</th>
                <th class="num">Pannes</th><th class="num">Coût pannes</th>
                <th class="num">Pénalités</th><th class="num">Maint. retard</th>
                <th class="num">Résultat</th>
            </tr></thead>
            <tbody>
            @foreach($classement as $i => $cl)
                <tr>
                    <td><strong>{{ $i + 1 }}</strong></td>
                    <td><strong>{{ $cl['immat'] }}</strong></td>
                    <td>{{ $cl['categorie'] }}</td>
                    <td class="num">{{ number_format($cl['recette'],0,',',' ') }}</td>
                    <td class="num">
                        <div class="gauge"><div class="gauge-fill" style="width:{{ min($cl['taux'],100) }}%;background:{{ $cl['taux']>=100?'var(--ok)':($cl['taux']>=80?'var(--warn)':'var(--ko)') }}"></div><div class="gauge-txt">{{ $cl['taux'] }}%</div></div>
                    </td>
                    <td class="num">{{ $cl['nb_pannes'] }}</td>
                    <td class="num neg">{{ number_format($cl['cout_pannes'],0,',',' ') }}</td>
                    <td class="num neg">{{ number_format($cl['penalites'],0,',',' ') }}</td>
                    <td class="num {{ $cl['maint_retard'] > 0 ? 'neg' : '' }}">{{ $cl['maint_retard'] }}</td>
                    <td class="num {{ $cl['resultat'] >= 0 ? 'pos' : 'neg' }}"><strong>{{ $cl['resultat'] >= 0 ? '+' : '' }}{{ number_format($cl['resultat'],0,',',' ') }}</strong></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection

@push('scripts')
<script>
function toggleDates(){
    var v=document.getElementById('sel-periode').value;
    document.getElementById('dates-custom').classList.toggle('show',v==='personnalise');
}
</script>
@endpush
