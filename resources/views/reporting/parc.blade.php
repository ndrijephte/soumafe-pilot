@extends('layouts.app')
@section('sousTitre', 'Reporting parc automobile')
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
@media(max-width:900px){.rp-grid2{grid-template-columns:1fr}.rp-tbl{display:block;overflow-x:auto}.rp-section{padding:12px 10px}}
@media(max-width:600px){.rp-kpi{grid-template-columns:repeat(2,1fr)}.kpi-v{font-size:20px}.rp-section h3{font-size:13px}.rp-filtres{gap:6px}}
@media(max-width:400px){.rp-kpi{grid-template-columns:1fr}.kpi-c{padding:10px}}

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
.donut-wrap{display:flex;align-items:center;gap:24px;justify-content:center;flex-wrap:wrap;padding:16px 0}
.donut{width:160px;height:160px;border-radius:50%;position:relative;flex-shrink:0}
.donut-center{position:absolute;inset:32px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:var(--bleu)}
.donut-legend{font-size:13px;line-height:2.2}
.donut-legend span{display:inline-block;width:12px;height:12px;border-radius:3px;margin-right:6px;vertical-align:middle}

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
    <div>
        <label>Véhicule</label>
        <select name="vehicule_id">
            <option value="">Tous</option>
            @foreach($tousVehicules as $tv)
            <option value="{{ $tv->id }}" {{ $filtreVehicule==$tv->id?'selected':'' }}>{{ $tv->immatriculation }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Chauffeur</label>
        <select name="chauffeur_id">
            <option value="">Tous</option>
            @foreach($tousChauffeurs as $tc)
            <option value="{{ $tc->id }}" {{ $filtreChauffeur==$tc->id?'selected':'' }}>{{ $tc->nomComplet() }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn-f">Filtrer</button>
</form>

{{-- ═══ KPI ═══ --}}
<div class="rp-kpi">
    <div class="kpi-c">
        <div class="kpi-v">{{ number_format($recetteTotalAvecRecouv, 0, ',', ' ') }}</div>
        <div class="kpi-l">Recettes (FCFA)</div>
        <div class="kpi-sub {{ $tauxRecette >= 100 ? 'kpi-ok' : ($tauxRecette >= 80 ? 'kpi-warn' : 'kpi-ko') }}">{{ $tauxRecette }}% de l'objectif</div>
        @if($recouvrementPercu > 0)<div class="kpi-sub kpi-ok" style="font-size:.7rem">dont {{ number_format($recouvrementPercu, 0, ',', ' ') }} F recouvrements</div> @endif
    </div>
    <div class="kpi-c">
        <div class="kpi-v">{{ number_format($objectifTotal, 0, ',', ' ') }}</div>
        <div class="kpi-l">Objectif (FCFA)</div>
        @php $ecartG = $recetteTotalAvecRecouv - $objectifTotal; @endphp
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
    <div class="kpi-c">
        <div class="kpi-v" style="color:var(--ko)">{{ number_format($depensesTotal, 0, ',', ' ') }}</div>
        <div class="kpi-l">Autres dépenses (FCFA)</div>
    </div>
    <div class="kpi-c">
        <div class="kpi-v" style="color:{{ $rentabilite >= 0 ? 'var(--ok)' : 'var(--ko)' }}">{{ $rentabilite >= 0 ? '+' : '' }}{{ number_format($rentabilite, 0, ',', ' ') }}</div>
        <div class="kpi-l">Rentabilit&eacute; nette (FCFA)</div>
        <div class="kpi-sub" style="font-size:10px;color:var(--gris)">Recettes - D&eacute;penses - P&eacute;nalit&eacute;s - Pannes</div>
    </div>
    @if(($totalEmp ?? 0) > 0 || ($remboursementsPeriode ?? 0) > 0)
    <div class="kpi-c" style="background:{{ $soldeCaisse >= 0 ? '#f0fdf4' : '#fef2f2' }};border-color:{{ $soldeCaisse >= 0 ? '#86efac' : '#fca5a5' }}">
        <div class="kpi-v" style="color:{{ $soldeCaisse >= 0 ? 'var(--ok)' : 'var(--ko)' }}">{{ $soldeCaisse >= 0 ? '+' : '' }}{{ number_format($soldeCaisse, 0, ',', ' ') }}</div>
        <div class="kpi-l">Solde en caisse (FCFA)</div>
        <div class="kpi-sub" style="font-size:10px;color:var(--gris)">Recettes - charges fonds propres - remboursements ({{ number_format($remboursementsPeriode ?? 0, 0, ',', ' ') }} F)</div>
    </div>
    @endif
    @if(($empruntsData['nb_emprunts'] ?? 0) > 0)
    <div class="kpi-c" style="background:#eff6ff;border-color:#bfdbfe">
        <div class="kpi-v" style="color:#1e40af">{{ number_format($empruntsData['total_emprunte'], 0, ',', ' ') }}</div>
        <div class="kpi-l">Ressources emprunt&eacute;es (FCFA)</div>
        <div class="kpi-sub" style="font-size:10px;color:var(--gris)">{{ $empruntsData['nb_emprunts'] }} emprunt{{ $empruntsData['nb_emprunts'] > 1 ? 's' : '' }} &mdash; {{ $empruntsData['nb_en_cours'] }} en cours</div>
        @if($empruntsData['solde_du'] > 0)
        <div class="kpi-sub kpi-ko" style="margin-top:2px">Reste d&ucirc; : {{ number_format($empruntsData['solde_du'], 0, ',', ' ') }} F</div>
        @else
        <div class="kpi-sub kpi-ok" style="margin-top:2px">Enti&egrave;rement rembours&eacute;</div>
        @endif
    </div>
    @endif
    @if(($remboursementsPeriode ?? 0) > 0)
    <div class="kpi-c">
        <div class="kpi-v" style="color:#b45309">{{ number_format($remboursementsPeriode, 0, ',', ' ') }}</div>
        <div class="kpi-l">Remboursements (FCFA)</div>
        <div class="kpi-sub" style="font-size:10px;color:var(--gris)">Sommes rembours&eacute;es sur la p&eacute;riode</div>
    </div>
    @endif
    @if(($pointageTotal ?? 0) > 0)
    <div class="kpi-c">
        <div class="kpi-v" style="color:var(--accent)">{{ number_format($pointageTotal, 0, ',', ' ') }}</div>
        <div class="kpi-l">Pointage cumul&eacute; (FCFA)</div>
        <div class="kpi-sub" style="font-size:10px;color:var(--gris)">Contribution infractions / radars</div>
    </div>
    @endif
</div>

{{-- ═══ DÉTAIL JOUR PAR JOUR (si filtre actif) ═══ --}}
@if($detailJour->isNotEmpty())
<div class="rp-section" style="border-left:4px solid var(--accent)">
    <h3>{!! $ico('<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!} Détail jour par jour — {{ $filtreNom }}</h3>
    @php
        $dtRec = $detailJour->sum('recette');
        $dtObj = $detailJour->sum('objectif');
        $dtTaux = $dtObj > 0 ? round(($dtRec / $dtObj) * 100) : 0;
        $dtAbsences = $detailJour->whereNotNull('motif_absence')->count();
    @endphp
    <div style="display:flex;gap:20px;flex-wrap:wrap;margin-bottom:14px;font-size:13px">
        <span><strong>{{ $detailJour->count() }}</strong> jours enregistrés</span>
        <span>Recettes : <strong>{{ number_format($dtRec, 0, ',', ' ') }}</strong> FCFA</span>
        <span>Objectif : <strong>{{ number_format($dtObj, 0, ',', ' ') }}</strong> FCFA</span>
        <span>Taux : <strong style="color:{{ $dtTaux >= 100 ? 'var(--ok)' : ($dtTaux >= 80 ? '#b45309' : 'var(--ko)') }}">{{ $dtTaux }}%</strong></span>
        @if($dtAbsences > 0)<span style="color:var(--ko)">{{ $dtAbsences }} absence{{ $dtAbsences > 1 ? 's' : '' }} justifiée{{ $dtAbsences > 1 ? 's' : '' }}</span> @endif
    </div>
    <div style="overflow-x:auto">
    <table class="rp-tbl">
    <thead><tr><th>Date</th><th>Jour</th><th>Véhicule</th><th>Chauffeur</th><th>Recette</th><th>Objectif</th><th>Écart</th><th>Mode</th><th>Motif</th></tr></thead>
    <tbody>
    @foreach($detailJour as $dj)
    @php
        $ecDj = $dj->recette - $dj->objectif;
        $tjL = match($dj->type_jour ?? 'ouvrable') { 'ferie' => 'Férié', 'dimanche' => 'Dim.', default => 'Ouv.' };
    @endphp
    <tr>
        <td>{{ $dj->date->format('d/m/Y') }}</td>
        <td>{{ $tjL }}</td>
        <td>{{ $dj->vehicule->immatriculation ?? '?' }}</td>
        <td>{{ $dj->chauffeur ?? '—' }}</td>
        <td class="num" style="font-weight:600">{{ number_format($dj->recette, 0, ',', ' ') }}</td>
        <td class="num">{{ number_format($dj->objectif, 0, ',', ' ') }}</td>
        <td class="num {{ $ecDj >= 0 ? 'pos' : 'neg' }}">{{ ($ecDj >= 0 ? '+' : '') . number_format($ecDj, 0, ',', ' ') }}</td>
        <td>{{ $dj->mode_remise ? ucfirst(str_replace('_', ' ', $dj->mode_remise)) : '—' }}</td>
        <td>@if($dj->motif_absence)<span style="color:var(--ko);font-weight:600">{{ $dj->motif_absence }}</span>@else — @endif</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr style="font-weight:700;background:#f8fafc">
        <td colspan="4">TOTAL</td>
        <td class="num">{{ number_format($dtRec, 0, ',', ' ') }}</td>
        <td class="num">{{ number_format($dtObj, 0, ',', ' ') }}</td>
        @php $ecTot = $dtRec - $dtObj; @endphp
        <td class="num {{ $ecTot >= 0 ? 'pos' : 'neg' }}">{{ ($ecTot >= 0 ? '+' : '') . number_format($ecTot, 0, ',', ' ') }}</td>
        <td colspan="2"></td>
    </tr>
    </tfoot>
    </table>
    </div>
</div>
@endif

{{-- ═══ COURBE RECETTES + DONUT ATTEINTE ═══ --}}
@if($recettesJour->isNotEmpty())
@php
    $jours = $recettesJour->values();
    $nbPts = $jours->count();
    $depBurMax = ($depensesJour ?? collect())->isNotEmpty() ? ($depensesJour ?? collect())->max() : 0;
    $depVehMax = ($depVehJour ?? collect())->isNotEmpty() ? ($depVehJour ?? collect())->max() : 0;
    $maxR = max($jours->max('total'), $jours->max('obj'), $depBurMax, $depVehMax, 1);
    $svgW = 700; $svgH = 240; $padL = 55; $padR = 15; $padT = 25; $padB = 40;
    $chartW = $svgW - $padL - $padR;
    $chartH = $svgH - $padT - $padB;
    $stepX = $nbPts > 1 ? $chartW / ($nbPts - 1) : $chartW;
    $ptsRec = []; $ptsObj = []; $ptsBur = []; $ptsVeh = []; $labels = [];
    $nbOk = 0; $nbWarn = 0; $nbKo = 0;
    foreach ($jours as $idx => $jr) {
        $x = round($padL + $idx * $stepX, 1);
        $yR = round($padT + $chartH - ($jr['total'] / $maxR) * $chartH, 1);
        $yO = round($padT + $chartH - ($jr['obj'] / $maxR) * $chartH, 1);
        $burJ = (int) (($depensesJour ?? collect())[$jr['jour']] ?? 0);
        $vehJ = (int) (($depVehJour ?? collect())[$jr['jour']] ?? 0);
        $yB = round($padT + $chartH - ($burJ / $maxR) * $chartH, 1);
        $yV = round($padT + $chartH - ($vehJ / $maxR) * $chartH, 1);
        $ptsRec[] = "$x,$yR"; $ptsObj[] = "$x,$yO"; $ptsBur[] = "$x,$yB"; $ptsVeh[] = "$x,$yV";
        $tauxJ = $jr['obj'] > 0 ? ($jr['total'] / $jr['obj']) * 100 : 0;
        if ($tauxJ >= 100) $nbOk++; elseif ($tauxJ >= 80) $nbWarn++; else $nbKo++;
        $labels[] = ['x' => $x, 'jour' => \Carbon\Carbon::parse($jr['jour'])->format('d/m'), 'rec' => $jr['total'], 'obj' => $jr['obj'], 'bur' => $burJ, 'veh' => $vehJ];
    }
    $lineRec = implode(' ', $ptsRec);
    $lineObj = implode(' ', $ptsObj);
    $lineBur = implode(' ', $ptsBur);
    $lineVeh = implode(' ', $ptsVeh);
    $hasBur = collect($labels)->sum('bur') > 0;
    $hasVeh = collect($labels)->sum('veh') > 0;
    $gradY = [];
    $step = ceil($maxR / 5 / 1000) * 1000;
    if ($step < 1) $step = 1;
    for ($v = 0; $v <= $maxR; $v += $step) { $gradY[] = $v; }
    if (end($gradY) < $maxR) $gradY[] = ceil($maxR / $step) * $step;
    $maxR = max(end($gradY), $maxR);
    // Donut
    $pOk = $nbPts > 0 ? round($nbOk / $nbPts * 100) : 0;
    $pWarn = $nbPts > 0 ? round($nbWarn / $nbPts * 100) : 0;
    $pKo = 100 - $pOk - $pWarn;
@endphp
<div class="rp-grid2">
    <div class="rp-section">
        <h3>{!! $ico('<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>') !!} Évolution entrées / sorties</h3>
        <div style="overflow-x:auto">
            <svg viewBox="0 0 {{ $svgW }} {{ $svgH }}" width="100%" style="max-width:{{ $svgW }}px;font-family:inherit">
                {{-- Axe Y label --}}
                <text x="12" y="{{ $padT + $chartH / 2 }}" fill="#94a3b8" font-size="10" text-anchor="middle" transform="rotate(-90,12,{{ $padT + $chartH / 2 }})">Montant (FCFA)</text>
                {{-- Axe X label --}}
                <text x="{{ $padL + $chartW / 2 }}" y="{{ $svgH - 2 }}" fill="#94a3b8" font-size="10" text-anchor="middle">Jours</text>
                {{-- Grille horizontale + labels Y --}}
                @foreach($gradY as $gv)
                    @php $gy = round($padT + $chartH - ($gv / $maxR) * $chartH, 1); @endphp
                    <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $svgW - $padR }}" y2="{{ $gy }}" stroke="#e2e8f0" stroke-width="1"/>
                    <text x="{{ $padL - 6 }}" y="{{ $gy + 3.5 }}" fill="#94a3b8" font-size="10" text-anchor="end">{{ number_format($gv / 1000, 0) }}k</text>
                @endforeach
                <polyline points="{{ $lineObj }}" fill="none" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="5,4"/>
                <polyline points="{{ $lineRec }}" fill="none" stroke="var(--accent)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                @if($hasVeh)<polyline points="{{ $lineVeh }}" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>@endif
                @if($hasBur)<polyline points="{{ $lineBur }}" fill="none" stroke="var(--ko)" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>@endif
                @foreach($labels as $lb)
                    @php
                        $yPt = round($padT + $chartH - ($lb['rec'] / $maxR) * $chartH, 1);
                        $cPt = $lb['rec'] >= $lb['obj'] ? 'var(--ok)' : ($lb['rec'] >= $lb['obj'] * 0.8 ? 'var(--warn)' : 'var(--ko)');
                        $yBp = round($padT + $chartH - (($lb['bur'] ?? 0) / $maxR) * $chartH, 1);
                        $yVp = round($padT + $chartH - (($lb['veh'] ?? 0) / $maxR) * $chartH, 1);
                    @endphp
                    <circle cx="{{ $lb['x'] }}" cy="{{ $yPt }}" r="3.5" fill="{{ $cPt }}" stroke="#fff" stroke-width="1.5"/>
                    @if(($lb['veh'] ?? 0) > 0)<circle cx="{{ $lb['x'] }}" cy="{{ $yVp }}" r="3" fill="#f59e0b" stroke="#fff" stroke-width="1.5"/>@endif
                    @if(($lb['bur'] ?? 0) > 0)<circle cx="{{ $lb['x'] }}" cy="{{ $yBp }}" r="3" fill="var(--ko)" stroke="#fff" stroke-width="1.5"/>@endif
                    <text x="{{ $lb['x'] }}" y="{{ $svgH - $padB + 14 }}" fill="#64748b" font-size="9" text-anchor="middle">{{ $lb['jour'] }}</text>
                @endforeach
            </svg>
        </div>
        <div style="display:flex;gap:14px;font-size:11px;color:var(--gris);margin-top:4px;flex-wrap:wrap">
            <span><span style="display:inline-block;width:14px;height:2.5px;background:var(--accent);vertical-align:middle;margin-right:4px;border-radius:2px"></span>Recettes</span>
            <span><span style="display:inline-block;width:14px;height:2.5px;background:#f59e0b;vertical-align:middle;margin-right:4px;border-radius:2px"></span>Coûts véhicules</span>
            <span><span style="display:inline-block;width:14px;height:2.5px;background:var(--ko);vertical-align:middle;margin-right:4px;border-radius:2px"></span>Dépenses bureau</span>
            <span><span style="display:inline-block;width:14px;height:0;border-top:1.5px dashed #94a3b8;vertical-align:middle;margin-right:4px"></span>Objectif</span>
        </div>
    </div>
    <div class="rp-section">
        <h3>{!! $ico('<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>') !!} Taux d'atteinte des objectifs</h3>
        <div class="donut-wrap">
            <div class="donut" style="background:conic-gradient(var(--ok) 0% {{ $pOk }}%, var(--warn) {{ $pOk }}% {{ $pOk + $pWarn }}%, var(--ko) {{ $pOk + $pWarn }}% 100%)">
                <div class="donut-center">{{ $pOk }}%</div>
            </div>
            <div class="donut-legend">
                <div><span style="background:var(--ok)"></span> &ge; 100% — {{ $nbOk }} jour(s)</div>
                <div><span style="background:var(--warn)"></span> 80-99% — {{ $nbWarn }} jour(s)</div>
                <div><span style="background:var(--ko)"></span> &lt; 80% — {{ $nbKo }} jour(s)</div>
            </div>
        </div>
        <div style="margin-top:16px;text-align:center;font-size:13px;color:var(--bleu);font-weight:600">
            {{ $nbPts }} jour(s) analysé(s)
        </div>
    </div>
</div>
@else
<div class="rp-section">
    <h3>{!! $ico('<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>') !!} Évolution des recettes journalières</h3>
    <p style="color:var(--gris);font-size:13px">Aucune recette sur cette période.</p>
</div>
@endif

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

{{-- ═══ PÉNALITÉS PAR STATUT ═══ --}}
@if($penalitesStatut->count() > 0)
<div class="rp-section">
    <h3>{!! $ico('<circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>') !!} R&eacute;partition p&eacute;nalit&eacute;s par statut</h3>
    @php
        $statutColors = ['payee' => '#16a34a', 'non_payee' => '#dc2626', 'contestee' => '#f59e0b', 'annulee' => '#94a3b8'];
        $statutLabels = ['payee' => 'Payée', 'non_payee' => 'Non payée', 'contestee' => 'Contestée', 'annulee' => 'Annulée'];
        $totalPenS = max((int) $penalitesStatut->sum(), 1);
        $gradParts = []; $cumulPS = 0;
        foreach($penalitesStatut as $st => $mont) {
            $pctPS = round(((int) $mont / $totalPenS) * 360);
            $color = $statutColors[$st] ?? '#94a3b8';
            $gradParts[] = "$color {$cumulPS}deg " . ($cumulPS + $pctPS) . "deg";
            $cumulPS += $pctPS;
        }
    @endphp
    <div class="donut-wrap">
        <div class="donut" style="background:conic-gradient({{ implode(', ', $gradParts) }})">
            <div class="donut-center">{{ number_format($totalPenS,0,',',' ') }}</div>
        </div>
        <div class="donut-legend">
            @foreach($penalitesStatut as $st => $mont)
                <div><span style="background:{{ $statutColors[$st] ?? '#94a3b8' }}"></span>{{ $statutLabels[$st] ?? ucfirst(str_replace('_', ' ', $st)) }} : {{ number_format((int) $mont,0,',',' ') }} FCFA</div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ═══ MAINTENANCE PAR TYPE ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>') !!} État des maintenances par type</h3>
    @if($maintenanceType->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune échéance de maintenance enregistrée.</p>
    @else
        {{-- Barres empilées --}}
        <div style="margin-bottom:14px">
            @foreach($maintenanceType as $mt)
                @php $okM = max($mt['nb'] - $mt['retard'] - $mt['proche'], 0); @endphp
                <div class="bar-wrap">
                    <div class="bar-label" title="{{ $mt['type'] }}">{{ $mt['type'] }}</div>
                    <div class="bar-track" style="display:flex;overflow:hidden">
                        @if($mt['retard'] > 0)<div style="width:{{ round(($mt['retard']/$mt['nb'])*100) }}%;height:100%;background:#dc2626" title="Retard: {{ $mt['retard'] }}"></div>@endif
                        @if($mt['proche'] > 0)<div style="width:{{ round(($mt['proche']/$mt['nb'])*100) }}%;height:100%;background:#f59e0b" title="À venir: {{ $mt['proche'] }}"></div>@endif
                        @if($okM > 0)<div style="width:{{ round(($okM/$mt['nb'])*100) }}%;height:100%;background:#16a34a" title="OK: {{ $okM }}"></div>@endif
                    </div>
                    <div class="bar-val">{{ $mt['nb'] }}</div>
                </div>
            @endforeach
            <div style="display:flex;gap:14px;font-size:11px;color:var(--gris);margin-top:6px">
                <span><span style="display:inline-block;width:10px;height:10px;background:#dc2626;border-radius:2px;vertical-align:middle;margin-right:4px"></span>En retard</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#f59e0b;border-radius:2px;vertical-align:middle;margin-right:4px"></span>À venir (15j)</span>
                <span><span style="display:inline-block;width:10px;height:10px;background:#16a34a;border-radius:2px;vertical-align:middle;margin-right:4px"></span>OK</span>
            </div>
        </div>
    @endif
</div>

{{-- ═══ TABLEAU RECETTES PAR VÉHICULE ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h2"/>') !!} Recettes par véhicule</h3>
    @if($recettesVehicule->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune recette enregistrée.</p>
    @else
        <table class="rp-tbl" data-collapse-table="8">
            <thead><tr>
                <th>Véhicule</th><th>Cat.</th><th class="num">Jours</th>
                <th class="num">Recette</th><th class="num">Recouv.</th><th class="num">Objectif</th>
                <th class="num">Écart</th><th class="num">Taux</th><th class="num">Moy/jour</th>
            </tr></thead>
            <tbody>
            @foreach($recettesVehicule as $rv)
                <tr>
                    <td><strong>{{ $rv['immat'] }}</strong></td>
                    <td>{{ $rv['categorie'] }}</td>
                    <td class="num">{{ $rv['nb_jours'] }}</td>
                    <td class="num">{{ number_format($rv['recette_totale'] ?? $rv['recette'],0,',',' ') }}</td>
                    <td class="num" style="color:#059669">{{ ($rv['recouvrement'] ?? 0) > 0 ? '+'.number_format($rv['recouvrement'],0,',',' ') : '—' }}</td>
                    <td class="num">{{ number_format($rv['objectif'],0,',',' ') }}</td>
                    <td class="num {{ $rv['ecart'] >= 0 ? 'pos' : 'neg' }}">{{ $rv['ecart'] >= 0 ? '+' : '' }}{{ number_format($rv['ecart'],0,',',' ') }}</td>
                    <td class="num"><span style="font-weight:700;color:{{ $rv['taux']>=100?'var(--ok)':($rv['taux']>=80?'var(--warn)':'var(--ko)') }}">{{ $rv['taux'] }}%</span></td>
                    <td class="num">{{ number_format($rv['moyenne'],0,',',' ') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ═══ PANNES PAR VÉHICULE ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>') !!} Pannes par véhicule</h3>
    @if($pannesVehicule->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune panne sur cette période.</p>
    @else
        @php $maxPVCout = max($pannesVehicule->max('cout'), 1); @endphp
        @foreach($pannesVehicule as $pv)
            <div class="bar-wrap">
                <div class="bar-label" title="{{ $pv['immat'] }}">{{ $pv['immat'] }}</div>
                <div class="bar-track"><div class="bar-fill" style="width:{{ round(($pv['cout']/$maxPVCout)*100) }}%;background:#dc2626"></div></div>
                <div class="bar-val">{{ number_format($pv['cout'],0,',',' ') }} <span style="font-size:10px;color:var(--gris)">&middot; {{ $pv['nb'] }}x &middot; {{ $pv['delai'] }}j</span></div>
            </div>
        @endforeach
        <div style="font-size:11px;color:var(--gris);margin-top:6px">Coût total &mdash; nombre de pannes &mdash; délai moyen résolution</div>
    @endif
</div>

{{-- ═══ CLASSEMENT GLOBAL ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>') !!} Classement des véhicules (résultat net)</h3>
    @if($classement->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucun véhicule en service.</p>
    @else
        <p style="font-size:12px;color:var(--gris);margin-bottom:10px">Résultat = Recettes (+ recouvrements) − Coût pannes − Pénalités</p>
        <table class="rp-tbl" data-collapse-table="10">
            <thead><tr>
                <th>#</th><th>Véhicule</th><th>Cat.</th>
                <th class="num">Recette</th><th class="num">Recouv.</th><th class="num">Taux obj.</th>
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
                    <td class="num">{{ number_format($cl['recette_totale'] ?? $cl['recette'],0,',',' ') }}</td>
                    <td class="num" style="color:#059669">{{ ($cl['recouvrement'] ?? 0) > 0 ? '+'.number_format($cl['recouvrement'],0,',',' ') : '—' }}</td>
                    <td class="num"><span style="font-weight:700;color:{{ $cl['taux']>=100?'var(--ok)':($cl['taux']>=80?'var(--warn)':'var(--ko)') }}">{{ $cl['taux'] }}%</span></td>
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

{{-- ═══ GESTIONNAIRES PARC ═══ --}}
@if($gestionnairesActuels->isNotEmpty())
<div class="rp-section" style="margin-bottom:18px">
    <h3>{!! $ico('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') !!} Gestionnaire(s) parc en fonction</h3>
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px">
        @foreach($gestionnairesActuels as $gn)
        <span style="background:var(--accent);color:#fff;padding:5px 14px;border-radius:8px;font-size:13px;font-weight:600">{{ $gn }}</span>
        @endforeach
    </div>
    @if($historiqueGestionnaires->isNotEmpty())
    <details style="margin-top:8px">
        <summary style="cursor:pointer;font-size:12px;color:var(--gris);font-weight:600">Historique des changements</summary>
        <div style="margin-top:6px;font-size:12px;max-height:200px;overflow-y:auto">
            @foreach($historiqueGestionnaires as $hg)
            <div style="display:flex;gap:8px;padding:4px 0;border-bottom:1px solid var(--bord)">
                <span style="color:var(--gris);min-width:100px">{{ $hg->created_at?->format('d/m/Y H:i') }}</span>
                <span style="font-weight:600">{{ $hg->user_name }}</span>
                <span style="color:#666">{{ $hg->details }}</span>
            </div>
            @endforeach
        </div>
    </details>
    @endif
</div>
@endif

{{-- ═══ PERFORMANCE OKR ═══ --}}
@if($okrGestionnaire || $okrChauffeurs->isNotEmpty())
<div class="rp-section" style="margin-bottom:18px">
    <h3>{!! $ico('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>') !!} Performance OKR — {{ \Carbon\Carbon::parse($periodeOkr.'-01')->translatedFormat('F Y') }}</h3>

    @if($okrGestionnaire)
    <div class="rp-kpi" style="margin-bottom:14px">
        <div class="kpi-c"><div class="kpi-v" style="color:{{ $okrGestionnaire['score']>=80?'var(--ok)':($okrGestionnaire['score']>=50?'var(--warn)':'var(--ko)') }}">{{ $okrGestionnaire['score'] }}%</div><div class="kpi-l">Mon score OKR</div></div>
        <div class="kpi-c"><div class="kpi-v">{{ $okrGestionnaire['total'] }}</div><div class="kpi-l">Objectifs</div></div>
        <div class="kpi-c"><div class="kpi-v" style="color:var(--ok)">{{ $okrGestionnaire['atteints'] }}</div><div class="kpi-l">Atteints</div></div>
        <div class="kpi-c"><div class="kpi-v" style="color:var(--warn)">{{ $okrGestionnaire['partiels'] }}</div><div class="kpi-l">Partiels</div></div>
        <div class="kpi-c"><div class="kpi-v" style="color:var(--gris)">{{ $okrGestionnaire['en_cours'] }}</div><div class="kpi-l">En cours</div></div>
    </div>
    @endif

    @if($okrChauffeurs->isNotEmpty())
    <h4 style="font-size:12.5px;color:var(--bleu);margin:10px 0 6px">OKR Chauffeurs</h4>
    <table class="rp-tbl">
        <thead><tr><th>Chauffeur</th><th class="num">Score</th><th class="num">Objectifs</th><th class="num">Atteints</th></tr></thead>
        <tbody>
        @foreach($okrChauffeurs as $oc)
        <tr>
            <td style="font-weight:600">{{ $oc['nom'] }}</td>
            <td class="num" style="font-weight:700;color:{{ $oc['score']>=80?'var(--ok)':($oc['score']>=50?'var(--warn)':'var(--ko)') }}">{{ $oc['score'] }}%</td>
            <td class="num">{{ $oc['total'] }}</td>
            <td class="num" style="color:var(--ok)">{{ $oc['atteints'] }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @elseif($okrGestionnaire)
    <p style="color:var(--gris);font-size:12px;margin-top:8px">Aucun chauffeur n'a d'objectifs OKR pour cette p&eacute;riode.</p>
    @endif
</div>
@endif

{{-- ═══ PERFORMANCE CHAUFFEURS ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/><line x1="20" y1="8" x2="20" y2="14"/>') !!} Performance des chauffeurs</h3>
    @if($performanceChauffeurs->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucun chauffeur actif avec v&eacute;hicule affect&eacute;.</p>
    @else
        {{-- Graphique comparaison recettes --}}
        @php $maxChR = max($performanceChauffeurs->max('recette_totale'), 1); @endphp
        <div style="margin-bottom:16px">
            @foreach($performanceChauffeurs as $ch)
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <div style="width:90px;font-size:11.5px;font-weight:600;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:none" title="{{ $ch['nom'] }}">{{ $ch['nom'] }}</div>
                    <div style="flex:1;display:flex;align-items:center;gap:6px">
                        <div style="height:16px;width:{{ round(($ch['recette_totale']/$maxChR)*100) }}%;background:{{ $ch['taux']>=100?'var(--ok)':($ch['taux']>=80?'var(--warn)':'var(--ko)') }};border-radius:4px;min-width:2px"></div>
                        <span style="font-size:10.5px;font-weight:700;color:{{ $ch['taux']>=100?'var(--ok)':($ch['taux']>=80?'var(--warn)':'var(--ko)') }};white-space:nowrap">{{ $ch['taux'] }}%</span>
                    </div>
                    <div style="width:80px;font-size:12px;font-weight:700;text-align:right;flex:none;color:{{ $ch['resultat']>=0?'var(--ok)':'var(--ko)' }}">{{ $ch['resultat']>=0?'+':'' }}{{ number_format($ch['resultat'],0,',',' ') }}</div>
                </div>
            @endforeach
        </div>

        {{-- Tableau détaillé avec jauges --}}
        <table class="rp-tbl" data-collapse-table="8">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Chauffeur</th>
                    <th>V&eacute;hicule(s)</th>
                    <th class="num">Recettes</th>
                    <th class="num">Recouv.</th>
                    <th class="num">Objectif</th>
                    <th style="min-width:90px">Taux</th>
                    <th class="num">Pointage</th>
                    <th class="num">P&eacute;nalit&eacute;s</th>
                    <th class="num">Pannes</th>
                    <th class="num">R&eacute;sultat net</th>
                </tr>
            </thead>
            <tbody>
            @foreach($performanceChauffeurs as $i => $ch)
                @php $resCl = $ch['resultat'] >= 0 ? 'pos' : 'neg'; @endphp
                <tr>
                    <td style="font-weight:700;color:var(--gris)">{{ $i + 1 }}</td>
                    <td style="font-weight:600">{{ $ch['nom'] }}</td>
                    <td style="font-size:11px;color:var(--gris)">{{ $ch['vehicules'] }}</td>
                    <td class="num">{{ number_format($ch['recette'],0,',',' ') }}</td>
                    <td class="num" style="color:#059669">{{ ($ch['recouvrement'] ?? 0) > 0 ? '+'.number_format($ch['recouvrement'],0,',',' ') : '—' }}</td>
                    <td class="num">{{ number_format($ch['objectif'],0,',',' ') }}</td>
                    <td class="num"><span style="font-weight:700;color:{{ $ch['taux']>=100?'var(--ok)':($ch['taux']>=80?'var(--warn)':'var(--ko)') }}">{{ $ch['taux'] }}%</span></td>
                    <td class="num" style="color:var(--accent)">{{ number_format($ch['pointage'] ?? 0, 0, ',', ' ') }}</td>
                    <td class="num" style="color:var(--warn)">{{ $ch['nb_penalites'] }} <span style="font-size:10px">({{ number_format($ch['montant_penalites'],0,',',' ') }})</span></td>
                    <td class="num">{{ $ch['nb_pannes'] }} <span style="font-size:10px;color:var(--ko)">({{ number_format($ch['cout_pannes'],0,',',' ') }})</span></td>
                    <td class="num {{ $resCl }}"><strong>{{ $ch['resultat']>=0?'+':'' }}{{ number_format($ch['resultat'],0,',',' ') }}</strong></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- ═══ ANALYSE DES SORTIES D'ARGENT ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<path d="M2 7h20M5 7v12a2 2 0 002 2h10a2 2 0 002-2V7M9 11v4M15 11v4M10 3h4l1 4H9l1-4z"/>') !!} Analyse des sorties d'argent</h3>
    @php
        $coutVeh = ($coutPannes ?? 0) + ($montantPenalites ?? 0);
        $totalSort = $coutVeh + ($depensesTotal ?? 0);
        $pctVeh = $totalSort > 0 ? round(($coutVeh / $totalSort) * 100) : 0;
        $pctAutre = 100 - $pctVeh;
    @endphp
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    {{-- Comparaison coûts véhicules vs autres dépenses --}}
    <div>
        <h4 style="font-size:13px;color:var(--bleu);margin:0 0 10px">Coûts véhicules vs Autres dépenses</h4>
        @if($totalSort > 0)
        <div class="donut-wrap" style="padding:8px 0">
            <div class="donut" style="width:130px;height:130px;background:conic-gradient(#f59e0b 0% {{ $pctVeh }}%, var(--ko) {{ $pctVeh }}% 100%)">
                <div class="donut-center" style="inset:26px;font-size:14px">{{ number_format($totalSort,0,',',' ') }}</div>
            </div>
            <div class="donut-legend" style="font-size:12px;line-height:2.4">
                <div><span style="background:#f59e0b"></span>Véhicules : {{ number_format($coutVeh,0,',',' ') }} F ({{ $pctVeh }}%)</div>
                <div style="font-size:11px;color:var(--gris);padding-left:18px">Pannes : {{ number_format($coutPannes,0,',',' ') }} F</div>
                <div style="font-size:11px;color:var(--gris);padding-left:18px">Pénalités : {{ number_format($montantPenalites,0,',',' ') }} F</div>
                <div><span style="background:var(--ko)"></span>Autres : {{ number_format($depensesTotal,0,',',' ') }} F ({{ $pctAutre }}%)</div>
            </div>
        </div>
        @if($coutVeh > $depensesTotal)
        <div style="padding:8px 12px;background:#fef3c7;border-radius:8px;font-size:12px;color:#92400e;font-weight:600;margin-top:6px;display:flex;align-items:center;gap:6px">
            {!! $ico('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') !!}
            Les coûts véhicules dépassent les autres dépenses
        </div>
        @endif
        @else
        <p style="font-size:12px;color:var(--gris)">Aucune sortie sur la période</p>
        @endif
    </div>

    {{-- Répartition par type de dépense --}}
    <div>
        <h4 style="font-size:13px;color:var(--bleu);margin:0 0 10px">Détail autres dépenses</h4>
        @if(($depensesType ?? collect())->isNotEmpty())
        @php $maxDep = ($depensesType ?? collect())->max('total') ?: 1; @endphp
        @foreach($depensesType ?? [] as $dt)
        @php $pctDep = round(($dt->total / $maxDep) * 100); @endphp
        <div style="margin-bottom:8px">
            <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:2px">
                <span style="font-weight:600">{{ $typesDepenseLabels[$dt->type_depense] ?? ucfirst(str_replace('_', ' ', $dt->type_depense)) }}</span>
                <span style="color:var(--ko);font-weight:600">{{ number_format($dt->total, 0, ',', ' ') }} F</span>
            </div>
            <div style="height:8px;background:#fee2e2;border-radius:4px;overflow:hidden">
                <div style="height:100%;width:{{ $pctDep }}%;background:var(--ko);border-radius:4px"></div>
            </div>
        </div>
        @endforeach
        @else
        <p style="font-size:12px;color:var(--gris)">Aucune dépense sur la période</p>
        @endif
    </div>
    </div>
</div>

{{-- ═══ RENTABILITÉ DYNAMIQUE PAR VÉHICULE ═══ --}}
@if(($rentaVehicules ?? collect())->isNotEmpty())
<div class="rp-section">
    <h3>{!! $ico('<path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>') !!} Rentabilit&eacute; dynamique par v&eacute;hicule</h3>

    <div class="rp-kpi" style="margin-bottom:18px">
        <div class="kpi-c">
            <div class="kpi-v" style="color:var(--ok)">{{ $nbRentables ?? 0 }}</div>
            <div class="kpi-l">V&eacute;hicules rentables</div>
        </div>
        <div class="kpi-c">
            <div class="kpi-v" style="color:var(--ko)">{{ $nbDeficit ?? 0 }}</div>
            <div class="kpi-l">V&eacute;hicules en d&eacute;ficit</div>
        </div>
        <div class="kpi-c">
            <div class="kpi-v" style="color:{{ ($profitTotal ?? 0) >= 0 ? 'var(--ok)' : 'var(--ko)' }}">{{ ($profitTotal ?? 0) >= 0 ? '+' : '' }}{{ number_format($profitTotal ?? 0,0,',',' ') }} F</div>
            <div class="kpi-l">Profit net global</div>
        </div>
        <div class="kpi-c">
            <div class="kpi-v" style="color:{{ ($detteNetteTotale ?? 0) > 0 ? 'var(--warn)' : 'var(--ok)' }}">{{ number_format($detteNetteTotale ?? 0,0,',',' ') }} F</div>
            <div class="kpi-l">Dette nette emprunts</div>
        </div>
    </div>

    <div style="overflow-x:auto">
    <table class="rp-tbl">
        <thead><tr>
            <th>V&eacute;hicule</th><th>Cat.</th>
            <th class="num">Recettes</th><th class="num">Recouv.</th><th class="num">Pannes</th><th class="num">P&eacute;nal.</th>
            <th class="num">Maint.</th><th class="num">Autres d&eacute;p.</th><th class="num">Total d&eacute;p.</th>
            <th class="num">Profit</th><th class="num">ROI</th>
            <th class="num">Emprunt</th><th>Verdict</th>
        </tr></thead>
        <tbody>
        @foreach($rentaVehicules as $rv)
        <tr>
            <td style="font-weight:600">{{ $rv['immat'] }}</td>
            <td style="font-size:11px">{{ $rv['categorie'] }}</td>
            <td class="num" style="color:var(--ok);font-weight:600">{{ number_format($rv['recette'],0,',',' ') }}</td>
            <td class="num" style="color:var(--ok)">{{ ($rv['recouvrement'] ?? 0) > 0 ? number_format($rv['recouvrement'],0,',',' ') : '—' }}</td>
            <td class="num neg">{{ $rv['pannes'] > 0 ? number_format($rv['pannes'],0,',',' ') : '—' }}</td>
            <td class="num neg">{{ $rv['penalites'] > 0 ? number_format($rv['penalites'],0,',',' ') : '—' }}</td>
            <td class="num neg">{{ $rv['maintenance'] > 0 ? number_format($rv['maintenance'],0,',',' ') : '—' }}</td>
            <td class="num neg">{{ $rv['depenses'] > 0 ? number_format($rv['depenses'],0,',',' ') : '—' }}</td>
            <td class="num" style="font-weight:700;color:var(--ko)">{{ number_format($rv['total_depenses'],0,',',' ') }}</td>
            <td class="num" style="font-weight:700;color:{{ $rv['profit'] >= 0 ? 'var(--ok)' : 'var(--ko)' }}">{{ $rv['profit'] >= 0 ? '+' : '' }}{{ number_format($rv['profit'],0,',',' ') }}</td>
            <td class="num" style="font-weight:600;color:{{ $rv['roi'] >= 100 ? 'var(--ok)' : 'var(--ko)' }}">{{ $rv['roi'] }}%</td>
            <td class="num" style="color:var(--warn)">{{ $rv['dette_nette'] > 0 ? number_format($rv['dette_nette'],0,',',' ') : '—' }}</td>
            <td><span class="pill {{ $rv['verdict'] === 'rentable' ? 'p-ok' : ($rv['verdict'] === 'equilibre' ? 'p-warn' : 'p-ko') }}">{{ $rv['verdict'] === 'rentable' ? 'Rentable' : ($rv['verdict'] === 'equilibre' ? 'Equilibre' : 'Deficit') }}</span></td>
        </tr>
        @endforeach
        @if($rentaVehicules->count() > 1)
        <tr style="background:#f0f4f8;font-weight:700;border-top:2px solid var(--bord)">
            <td colspan="2">TOTAL</td>
            <td class="num" style="color:var(--ok)">{{ number_format($rentaVehicules->sum('recette'),0,',',' ') }}</td>
            <td class="num" style="color:var(--ok)">{{ $rentaVehicules->sum('recouvrement') > 0 ? number_format($rentaVehicules->sum('recouvrement'),0,',',' ') : '—' }}</td>
            <td class="num neg">{{ number_format($rentaVehicules->sum('pannes'),0,',',' ') }}</td>
            <td class="num neg">{{ number_format($rentaVehicules->sum('penalites'),0,',',' ') }}</td>
            <td class="num neg">{{ number_format($rentaVehicules->sum('maintenance'),0,',',' ') }}</td>
            <td class="num neg">{{ number_format($rentaVehicules->sum('depenses'),0,',',' ') }}</td>
            <td class="num" style="color:var(--ko)">{{ number_format($depTotalVeh,0,',',' ') }}</td>
            <td class="num" style="color:{{ $profitTotal >= 0 ? 'var(--ok)' : 'var(--ko)' }}">{{ $profitTotal >= 0 ? '+' : '' }}{{ number_format($profitTotal,0,',',' ') }}</td>
            <td class="num">{{ $depTotalVeh > 0 ? round(($rentaVehicules->sum('recette_totale') / $depTotalVeh) * 100) : 0 }}%</td>
            <td class="num" style="color:var(--warn)">{{ $detteNetteTotale > 0 ? number_format($detteNetteTotale,0,',',' ') : '—' }}</td>
            <td></td>
        </tr>
        @endif
        </tbody>
    </table>
    </div>

    {{-- Aide à la décision --}}
    @php
        $pire = $rentaVehicules->where('verdict', 'deficit')->sortBy('profit')->first();
        $meilleur = $rentaVehicules->where('verdict', 'rentable')->sortByDesc('profit')->first();
    @endphp
    @if($pire || $meilleur)
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px;margin-top:14px">
        @if($meilleur)
        <div style="padding:12px 16px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px">
            <div style="font-size:12px;font-weight:700;color:var(--ok);margin-bottom:4px">{!! $ico('<polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/>') !!} Meilleur v&eacute;hicule</div>
            <div style="font-weight:700;font-size:15px">{{ $meilleur['immat'] }}</div>
            <div style="font-size:12px;color:var(--gris)">Profit : +{{ number_format($meilleur['profit'],0,',',' ') }} F &mdash; ROI {{ $meilleur['roi'] }}%</div>
        </div>
        @endif
        @if($pire)
        <div style="padding:12px 16px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px">
            <div style="font-size:12px;font-weight:700;color:var(--ko);margin-bottom:4px">{!! $ico('<polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/>') !!} V&eacute;hicule le plus co&ucirc;teux</div>
            <div style="font-weight:700;font-size:15px">{{ $pire['immat'] }}</div>
            <div style="font-size:12px;color:var(--gris)">D&eacute;ficit : {{ number_format($pire['profit'],0,',',' ') }} F &mdash; D&eacute;penses : {{ number_format($pire['total_depenses'],0,',',' ') }} F</div>
        </div>
        @endif
    </div>
    @endif
</div>
@endif

{{-- ═══ FONDS PROPRES vs EMPRUNTS ═══ --}}
@if(($empruntsData['total_emprunte'] ?? 0) > 0 || ($totalFP ?? 0) > 0 || ($totalEmp ?? 0) > 0)
<div class="rp-section">
    <h3>{!! $ico('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>') !!} Fonds propres vs Ressources emprunt&eacute;es</h3>
    <p style="font-size:12px;color:var(--gris);margin:-6px 0 14px">Emprunt = ressource d'urgence remboursable. Ce n'est pas une d&eacute;pense.</p>

    <div class="rp-kpi" style="margin-bottom:18px">
        <div class="kpi-c">
            <div class="kpi-v" style="color:var(--ok)">{{ number_format($totalFP ?? 0,0,',',' ') }} F</div>
            <div class="kpi-l">Charges fonds propres</div>
        </div>
        <div class="kpi-c">
            <div class="kpi-v" style="color:#6366f1">{{ number_format($totalEmp ?? 0,0,',',' ') }} F</div>
            <div class="kpi-l">Charges couvertes par emprunt</div>
        </div>
        <div class="kpi-c">
            <div class="kpi-v" style="color:#b45309">{{ number_format($remboursementsPeriode ?? 0,0,',',' ') }} F</div>
            <div class="kpi-l">Rembours&eacute; (sorti de caisse)</div>
        </div>
        <div class="kpi-c">
            <div class="kpi-v" style="color:{{ ($detteGlobale ?? 0) > 0 ? 'var(--warn)' : 'var(--ok)' }}">{{ number_format($detteGlobale ?? 0,0,',',' ') }} F</div>
            <div class="kpi-l">Dette restante (tous emprunts)</div>
        </div>
    </div>

    @php
        $totalDepFP = ($totalFP ?? 0) + ($totalEmp ?? 0);
        $pctFP = $totalDepFP > 0 ? round(($totalFP / $totalDepFP) * 100) : 0;
        $pctEmp = 100 - $pctFP;
    @endphp

    <div class="rp-grid2">
        {{-- Donut --}}
        <div>
            <h4 style="font-size:13px;color:var(--bleu);margin:0 0 10px">R&eacute;partition du financement</h4>
            @if($totalDepFP > 0)
            <div class="donut-wrap">
                <div class="donut" style="background:conic-gradient(var(--ok) 0deg {{ $pctFP * 3.6 }}deg, var(--ko) {{ $pctFP * 3.6 }}deg 360deg)">
                    <div class="donut-center">{{ $pctFP }}%</div>
                </div>
                <div class="donut-legend">
                    <div><span style="background:var(--ok)"></span>Fonds propres : {{ number_format($totalFP,0,',',' ') }} F ({{ $pctFP }}%)</div>
                    <div><span style="background:var(--ko)"></span>Couvert par emprunt : {{ number_format($totalEmp,0,',',' ') }} F ({{ $pctEmp }}%)</div>
                </div>
            </div>
            @if($pctEmp > 50)
            <div style="padding:8px 12px;background:#fef3c7;border-radius:8px;font-size:12px;color:#92400e;font-weight:600;margin-top:6px;display:flex;align-items:center;gap:6px">
                {!! $ico('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') !!}
                L'entreprise finance plus de la moiti&eacute; de ses charges par emprunt
            </div>
            @endif
            @else
            <p style="font-size:12px;color:var(--gris)">Aucune charge sur la p&eacute;riode</p>
            @endif
        </div>

        {{-- D&eacute;tail par cat&eacute;gorie --}}
        <div>
            <h4 style="font-size:13px;color:var(--bleu);margin:0 0 10px">D&eacute;tail par cat&eacute;gorie</h4>
            <table class="rp-tbl">
                <thead><tr><th>Cat&eacute;gorie</th><th class="num">Fonds propres</th><th class="num">Couvert par emprunt</th><th class="num">Total</th></tr></thead>
                <tbody>
                    <tr>
                        <td style="font-weight:600">Pannes</td>
                        <td class="num pos">{{ number_format($fondsData['pannes_fp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="color:#6366f1">{{ number_format($fondsData['pannes_emp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="font-weight:700">{{ number_format(($fondsData['pannes_fp'] ?? 0)+($fondsData['pannes_emp'] ?? 0),0,',',' ') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">P&eacute;nalit&eacute;s</td>
                        <td class="num pos">{{ number_format($fondsData['penalites_fp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="color:#6366f1">{{ number_format($fondsData['penalites_emp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="font-weight:700">{{ number_format(($fondsData['penalites_fp'] ?? 0)+($fondsData['penalites_emp'] ?? 0),0,',',' ') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">Maintenance</td>
                        <td class="num pos">{{ number_format($fondsData['maint_fp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="color:#6366f1">{{ number_format($fondsData['maint_emp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="font-weight:700">{{ number_format(($fondsData['maint_fp'] ?? 0)+($fondsData['maint_emp'] ?? 0),0,',',' ') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:600">D&eacute;penses bureau</td>
                        <td class="num pos">{{ number_format($fondsData['depenses_fp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="color:#6366f1">{{ number_format($fondsData['depenses_emp'] ?? 0,0,',',' ') }}</td>
                        <td class="num" style="font-weight:700">{{ number_format(($fondsData['depenses_fp'] ?? 0)+($fondsData['depenses_emp'] ?? 0),0,',',' ') }}</td>
                    </tr>
                    <tr style="background:#f8fafc;font-weight:700;border-top:2px solid var(--bord)">
                        <td>TOTAL</td>
                        <td class="num pos">{{ number_format($totalFP,0,',',' ') }}</td>
                        <td class="num" style="color:#6366f1">{{ number_format($totalEmp,0,',',' ') }}</td>
                        <td class="num" style="color:var(--bleu)">{{ number_format($totalDepFP,0,',',' ') }}</td>
                    </tr>
                </tbody>
            </table>

            @if(($empruntsData['nb_emprunts'] ?? 0) > 0 || ($remboursementsPeriode ?? 0) > 0)
            <div style="margin-top:12px;padding:10px 14px;background:#f8fafc;border-radius:8px;font-size:12px">
                @if(($empruntsData['nb_emprunts'] ?? 0) > 0)
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span>Emprunts contract&eacute;s (p&eacute;riode)</span><span style="font-weight:700">{{ $empruntsData['nb_emprunts'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span>Encore en cours</span><span style="font-weight:700;color:var(--warn)">{{ $empruntsData['nb_en_cours'] }}</span>
                </div>
                @endif
                <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                    <span>Remboursements pay&eacute;s (p&eacute;riode)</span><span style="font-weight:700;color:#b45309">{{ number_format($remboursementsPeriode ?? 0,0,',',' ') }} F</span>
                </div>
                <div style="display:flex;justify-content:space-between">
                    <span>Dette totale restante</span><span style="font-weight:700;color:{{ ($detteGlobale ?? 0) > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ number_format($detteGlobale ?? 0,0,',',' ') }} F</span>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Jauge autonomie financière --}}
    <div style="margin-top:16px">
        <div style="display:flex;justify-content:space-between;font-size:12px;font-weight:600;margin-bottom:4px">
            <span>Autonomie financi&egrave;re</span>
            <span style="color:{{ $pctFP >= 70 ? 'var(--ok)' : ($pctFP >= 50 ? 'var(--warn)' : 'var(--ko)') }}">{{ $pctFP }}%</span>
        </div>
        <div class="gauge">
            <div class="gauge-fill" style="width:{{ $pctFP }}%;background:{{ $pctFP >= 70 ? 'var(--ok)' : ($pctFP >= 50 ? 'var(--warn)' : 'var(--ko)') }}"></div>
            <div class="gauge-txt">{{ $pctFP >= 70 ? 'Bonne autonomie' : ($pctFP >= 50 ? 'Autonomie moyenne' : 'Dépendance aux emprunts') }}</div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
function toggleDates(){
    var v=document.getElementById('sel-periode').value;
    document.getElementById('dates-custom').classList.toggle('show',v==='personnalise');
}
</script>
@endpush
