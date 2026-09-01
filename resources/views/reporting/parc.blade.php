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
        @if($dtAbsences > 0)<span style="color:var(--ko)">{{ $dtAbsences }} absence{{ $dtAbsences > 1 ? 's' : '' }} justifiée{{ $dtAbsences > 1 ? 's' : '' }}</span>@endif
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
    $maxR = max($jours->max('total'), $jours->max('obj'), 1);
    $svgW = 700; $svgH = 240; $padL = 55; $padR = 15; $padT = 25; $padB = 40;
    $chartW = $svgW - $padL - $padR;
    $chartH = $svgH - $padT - $padB;
    $stepX = $nbPts > 1 ? $chartW / ($nbPts - 1) : $chartW;
    $ptsRec = []; $ptsObj = []; $labels = [];
    $nbOk = 0; $nbWarn = 0; $nbKo = 0;
    foreach ($jours as $idx => $jr) {
        $x = round($padL + $idx * $stepX, 1);
        $yR = round($padT + $chartH - ($jr['total'] / $maxR) * $chartH, 1);
        $yO = round($padT + $chartH - ($jr['obj'] / $maxR) * $chartH, 1);
        $ptsRec[] = "$x,$yR"; $ptsObj[] = "$x,$yO";
        $tauxJ = $jr['obj'] > 0 ? ($jr['total'] / $jr['obj']) * 100 : 0;
        if ($tauxJ >= 100) $nbOk++; elseif ($tauxJ >= 80) $nbWarn++; else $nbKo++;
        $labels[] = ['x' => $x, 'jour' => \Carbon\Carbon::parse($jr['jour'])->format('d/m'), 'rec' => $jr['total'], 'obj' => $jr['obj']];
    }
    $lineRec = implode(' ', $ptsRec);
    $lineObj = implode(' ', $ptsObj);
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
        <h3>{!! $ico('<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>') !!} Évolution des recettes journalières</h3>
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
                @foreach($labels as $lb)
                    @php
                        $yPt = round($padT + $chartH - ($lb['rec'] / $maxR) * $chartH, 1);
                        $cPt = $lb['rec'] >= $lb['obj'] ? 'var(--ok)' : ($lb['rec'] >= $lb['obj'] * 0.8 ? 'var(--warn)' : 'var(--ko)');
                    @endphp
                    <circle cx="{{ $lb['x'] }}" cy="{{ $yPt }}" r="3.5" fill="{{ $cPt }}" stroke="#fff" stroke-width="1.5"/>
                    <text x="{{ $lb['x'] }}" y="{{ $svgH - $padB + 14 }}" fill="#64748b" font-size="9" text-anchor="middle">{{ $lb['jour'] }}</text>
                @endforeach
            </svg>
        </div>
        <div style="display:flex;gap:16px;font-size:11px;color:var(--gris);margin-top:4px;flex-wrap:wrap">
            <span><span style="display:inline-block;width:14px;height:2.5px;background:var(--accent);vertical-align:middle;margin-right:4px;border-radius:2px"></span>Recettes</span>
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

{{-- ═══ GESTIONNAIRE PARC ═══ --}}
@if($gestionnaireParc)
<div class="rp-section" style="margin-bottom:18px">
    <h3>{!! $ico('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') !!} Gestionnaire parc d&eacute;sign&eacute;</h3>
    <p style="font-size:14px;font-weight:600;color:var(--bleu)">{{ $gestionnaireParc }}</p>
</div>
@endif

{{-- ═══ PERFORMANCE CHAUFFEURS ═══ --}}
<div class="rp-section">
    <h3>{!! $ico('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="23" y1="11" x2="17" y2="11"/><line x1="20" y1="8" x2="20" y2="14"/>') !!} Performance des chauffeurs</h3>
    @if($performanceChauffeurs->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucun chauffeur actif avec v&eacute;hicule affect&eacute;.</p>
    @else
        {{-- Graphique comparaison recettes --}}
        @php $maxChR = max($performanceChauffeurs->max('recette'), 1); @endphp
        <div style="margin-bottom:16px">
            @foreach($performanceChauffeurs as $ch)
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px">
                    <div style="width:90px;font-size:11.5px;font-weight:600;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:none" title="{{ $ch['nom'] }}">{{ $ch['nom'] }}</div>
                    <div style="flex:1;display:flex;align-items:center;gap:6px">
                        <div style="height:16px;width:{{ round(($ch['recette']/$maxChR)*100) }}%;background:{{ $ch['taux']>=100?'var(--ok)':($ch['taux']>=80?'var(--warn)':'var(--ko)') }};border-radius:4px;min-width:2px"></div>
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
                    <th class="num">Objectif</th>
                    <th style="min-width:90px">Taux</th>
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
                    <td class="num">{{ number_format($ch['objectif'],0,',',' ') }}</td>
                    <td class="num"><span style="font-weight:700;color:{{ $ch['taux']>=100?'var(--ok)':($ch['taux']>=80?'var(--warn)':'var(--ko)') }}">{{ $ch['taux'] }}%</span></td>
                    <td class="num" style="color:var(--warn)">{{ $ch['nb_penalites'] }} <span style="font-size:10px">({{ number_format($ch['montant_penalites'],0,',',' ') }})</span></td>
                    <td class="num">{{ $ch['nb_pannes'] }} <span style="font-size:10px;color:var(--ko)">({{ number_format($ch['cout_pannes'],0,',',' ') }})</span></td>
                    <td class="num {{ $resCl }}"><strong>{{ $ch['resultat']>=0?'+':'' }}{{ number_format($ch['resultat'],0,',',' ') }}</strong></td>
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
