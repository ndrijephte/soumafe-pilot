@extends('layouts.app')
@section('sousTitre', 'Reporting parc automobile')
@section('titre', 'Reporting Parc')

@push('styles')
<style>
.rp-filtres{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin:12px 0 18px;padding:12px 14px;background:#fff;border:1px solid var(--bord);border-radius:12px}
.rp-filtres label{font-size:11.5px;color:var(--gris);display:block;margin-bottom:2px;font-weight:600}
.rp-filtres select,.rp-filtres input[type=date]{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;background:#fff}
.rp-filtres .btn-f{padding:8px 18px;border:none;border-radius:8px;background:var(--accent);color:#fff;font-size:13px;cursor:pointer;font-weight:600}
.rp-filtres .btn-f:hover{background:var(--bleu)}
.rp-filtres .actions{display:flex;gap:8px;align-items:center}
.rp-filtres .btn-raz{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1px solid var(--bord);border-radius:8px;background:#fff;color:#475569;font-size:13px;font-weight:600;text-decoration:none;cursor:pointer}
.rp-filtres .btn-raz:hover{border-color:var(--ko);color:var(--ko);background:#fef2f2}

/* Évolution */
.evo-totaux{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin:8px 0 12px}
.evo-tot{border:1px solid var(--bord);border-left:4px solid var(--c);border-radius:8px;padding:8px 12px;display:flex;flex-direction:column}
.evo-tot span{font-size:11.5px;color:var(--gris);font-weight:600}
.evo-tot strong{font-size:16px;color:#1e293b;font-variant-numeric:tabular-nums}
.evo-tot em{font-size:10.5px;color:var(--gris);font-style:normal}
.evo-legende{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:6px}
.evo-legende button{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border:1px solid var(--bord);border-radius:20px;background:#fff;font-size:12px;color:#334155;cursor:pointer;opacity:.45;transition:opacity .15s}
.evo-legende button.on{opacity:1}
.evo-legende button i{display:inline-block;width:16px;height:3px;border-radius:2px}
.evo-aide{font-size:11px;color:var(--gris);margin-left:auto}
.evo-zone{position:relative}
.evo-bulle{position:absolute;top:8px;width:230px;background:#fff;border:1px solid var(--bord);border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,.12);padding:10px 12px;font-size:12px;pointer-events:none;opacity:0;transition:opacity .12s}
.evo-bulle.on{opacity:1}
.evo-bulle strong{display:block;margin-bottom:6px;color:var(--bleu)}
.evo-bulle div{display:flex;align-items:center;gap:6px;padding:2px 0;color:#475569}
.evo-bulle div i{width:9px;height:9px;border-radius:2px;flex:none}
.evo-bulle div b{margin-left:auto;color:#1e293b;font-variant-numeric:tabular-nums}
@media(max-width:700px){.evo-totaux{grid-template-columns:repeat(2,1fr)}.evo-aide{display:none}}
.periode-bandeau{display:flex;flex-wrap:wrap;gap:6px 16px;align-items:center;font-size:13px;color:#1e40af;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:8px 14px;margin-bottom:16px}

.rp-titre{font-size:13px;font-weight:700;color:var(--gris);text-transform:uppercase;letter-spacing:.5px;margin:22px 0 10px}

/* L'essentiel */
.essentiel{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.ess{background:#fff;border:1px solid var(--bord);border-radius:12px;padding:14px 16px;display:flex;flex-direction:column;gap:4px}
.ess .lbl{font-size:12px;color:var(--gris);font-weight:600}
.ess .val{font-size:24px;font-weight:800;color:var(--bleu);font-variant-numeric:tabular-nums;line-height:1.2}
.ess .val small{font-size:13px;font-weight:600;color:var(--gris)}
.ess .aide{font-size:11.5px;color:var(--gris)}
.ess.ok{background:#f0fdf4;border-color:#86efac}.ess.ok .val{color:var(--ok)}
.ess.ko{background:#fef2f2;border-color:#fca5a5}.ess.ko .val{color:var(--ko)}
.var{font-size:11.5px;font-weight:700}
.var.up{color:var(--ok)}.var.down{color:var(--ko)}.var.eq{color:var(--gris)}
.mini{display:flex;justify-content:space-between;font-size:11.5px;color:#475569;border-top:1px dashed var(--bord);padding-top:3px}
.mini:first-of-type{border-top:none}

/* Jauges */
.gauge{height:8px;background:#f1f5f9;border-radius:6px;overflow:hidden;margin-top:4px}
.gauge>div{height:100%;border-radius:6px}
.g-ok{background:var(--ok)}.g-warn{background:#f59e0b}.g-ko{background:var(--ko)}
.taux-cell{min-width:110px}
.taux-cell .t{font-weight:700;font-size:12px}

/* À traiter */
.todo{display:grid;gap:8px}
.todo-item{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--bord);border-left:4px solid var(--gris);border-radius:10px;padding:10px 14px}
.todo-item.ko{border-left-color:var(--ko);background:#fffafa}
.todo-item.warn{border-left-color:#f59e0b;background:#fffdf5}
.todo-item.info{border-left-color:var(--accent)}
.todo-item .tt{font-weight:700;font-size:13.5px;color:var(--texte,#1e293b)}
.todo-item .dd{font-size:12.5px;color:#475569;margin-top:2px}
.todo-item a{margin-left:auto;font-size:12px;font-weight:600;color:var(--accent);white-space:nowrap;text-decoration:none;align-self:center}
.todo-item a:hover{text-decoration:underline}
.todo-ok{background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:12px 14px;color:#166534;font-size:13px;font-weight:600}

.rp-section{background:#fff;border:1px solid var(--bord);border-radius:12px;padding:16px 18px;margin-bottom:16px}
.rp-section h3{font-size:15px;color:var(--bleu);margin-bottom:4px;display:flex;align-items:center;gap:8px}
.rp-section .intro{font-size:12.5px;color:var(--gris);margin-bottom:12px}
.rp-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* Tableaux */
.rp-tbl{width:100%;border-collapse:collapse;font-size:12.5px}
.rp-tbl th{text-align:left;padding:8px 10px;background:#f8fafc;color:var(--gris);font-size:11px;text-transform:uppercase;border-bottom:2px solid var(--bord);white-space:nowrap}
.rp-tbl td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.rp-tbl tr:hover td{background:#f8fafc}
.rp-tbl .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.rp-tbl .pos{color:var(--ok);font-weight:700}
.rp-tbl .neg{color:var(--ko);font-weight:700}
.rp-tbl tfoot td{font-weight:700;background:#f8fafc;border-top:2px solid var(--bord)}
.rp-tbl .sous{display:block;font-size:10.5px;color:var(--gris);font-weight:400}
.verdict{display:inline-block;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700}
.v-rentable{background:#dcfce7;color:#166534}.v-deficit{background:#fee2e2;color:#991b1b}.v-equilibre{background:#f1f5f9;color:#475569}

/* Barres */
.bar-wrap{display:flex;align-items:center;gap:8px;margin-bottom:7px}
.bar-label{width:130px;font-size:12px;color:#475569;flex:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bar-track{flex:1;height:16px;background:#f1f5f9;border-radius:4px;overflow:hidden}
.bar-fill{height:100%;border-radius:4px}
.bar-val{font-size:12px;color:#334155;width:140px;flex:none;text-align:right;font-variant-numeric:tabular-nums}

.lien-retour{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--accent);text-decoration:none}
.lien-retour:hover{text-decoration:underline}
.date-custom{display:none;gap:8px}.date-custom.show{display:flex}
.sel-exercice.hide{display:none}

@media(max-width:1000px){.essentiel{grid-template-columns:repeat(2,1fr)}}
@media(max-width:900px){.rp-grid2{grid-template-columns:1fr}.rp-scroll{overflow-x:auto}.rp-section{padding:12px 10px}}
@media(max-width:520px){.essentiel{grid-template-columns:1fr}.ess .val{font-size:21px}.bar-label{width:90px}.bar-val{width:90px}}
</style>
@endpush

@section('contenu')
@php
    $ico = fn(string $d) => '<svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
    $F = fn($n) => number_format((int) $n, 0, ',', ' ');
    $P = fn($v) => number_format((float) $v, 2, ',', ' ');
    $signe = fn($n) => ($n > 0 ? '+' : '') . number_format((int) $n, 0, ',', ' ');
    // Variation vs période précédente ; $hausseBonne = la hausse est-elle une bonne nouvelle ?
    $variation = function ($cur, $prec, bool $hausseBonne = true, bool $enMontant = false) use ($libellePrec, $P) {
        if ($enMontant) {
            $diff = (int) $cur - (int) $prec;
            if ($diff === 0) return '<span class="var eq">= identique à ' . e($libellePrec) . '</span>';
            return '<span class="var ' . ($diff > 0 ? 'up' : 'down') . '">' . ($diff > 0 ? '▲ +' : '▼ ') . number_format($diff, 0, ',', ' ') . ' F vs ' . e($libellePrec) . '</span>';
        }
        if (!$prec) return '<span class="var eq">Aucune donnée sur ' . e($libellePrec) . ' pour comparer</span>';
        $pct = round(($cur - $prec) / abs($prec) * 100, 2);
        if ($pct == 0) return '<span class="var eq">= stable vs ' . e($libellePrec) . '</span>';
        $bon = ($pct > 0) === $hausseBonne;
        return '<span class="var ' . ($bon ? 'up' : 'down') . '">' . ($pct > 0 ? '▲ +' : '▼ ') . $P($pct) . ' % vs ' . e($libellePrec) . '</span>';
    };
    $classeTaux = fn($t) => $t === null ? 'g-warn' : ($t >= 100 ? 'g-ok' : ($t >= 80 ? 'g-warn' : 'g-ko'));
    $couleurTaux = fn($t) => $t === null ? 'var(--gris)' : ($t >= 100 ? 'var(--ok)' : ($t >= 80 ? '#b45309' : 'var(--ko)'));
@endphp

@php
    $filtresActifs = $periode !== 'exercice' || $exercice !== array_key_first($exercices)
        || $categorie !== 'tous' || $filtreVehicule || $filtreChauffeur;
@endphp
<a href="{{ route('parc.index') }}" class="lien-retour">← Retour au parc</a>
<h2 style="margin-top:4px">Reporting Parc Automobile</h2>

{{-- ═══ FILTRES ═══ --}}
<form method="GET" action="{{ route('reporting.parc') }}" class="rp-filtres">
    <div>
        <label>Période</label>
        <select name="periode" id="sel-periode" onchange="majPeriode()">
            <option value="exercice" @selected($periode === 'exercice')>Un exercice (05 → 04)</option>
            <option value="trois_exercices" @selected($periode === 'trois_exercices')>3 exercices</option>
            <option value="personnalise" @selected($periode === 'personnalise')>Dates personnalisées</option>
        </select>
    </div>
    <div class="sel-exercice {{ $periode === 'personnalise' ? 'hide' : '' }}" id="bloc-exercice">
        <label id="lbl-exercice">{{ $periode === 'trois_exercices' ? 'Jusqu\'à l\'exercice' : 'Exercice' }}</label>
        <select name="exercice">
            @foreach($exercices as $cle => $ex)
            <option value="{{ $cle }}" @selected($exercice === $cle)>{{ $ex['libelle'] }}{{ $loop->first ? ' — en cours' : '' }}</option>
            @endforeach
        </select>
    </div>
    <div class="date-custom {{ $periode === 'personnalise' ? 'show' : '' }}" id="dates-custom">
        <div><label>Du</label><input type="date" name="date_debut" value="{{ $debut->format('Y-m-d') }}"></div>
        <div><label>Au</label><input type="date" name="date_fin" value="{{ $fin->format('Y-m-d') }}"></div>
    </div>
    <div>
        <label>Catégorie</label>
        <select name="categorie">
            <option value="tous" @selected($categorie === 'tous')>Toutes</option>
            <option value="taxi" @selected($categorie === 'taxi')>Taxi</option>
            <option value="vtc" @selected($categorie === 'vtc')>VTC</option>
        </select>
    </div>
    <div>
        <label>Véhicule</label>
        <select name="vehicule_id">
            <option value="">Tous</option>
            @foreach($tousVehicules as $tv)
            <option value="{{ $tv->id }}" @selected($filtreVehicule == $tv->id)>{{ $tv->immatriculation }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Chauffeur</label>
        <select name="chauffeur_id">
            <option value="">Tous</option>
            @foreach($tousChauffeurs as $tc)
            <option value="{{ $tc->id }}" @selected($filtreChauffeur == $tc->id)>{{ $tc->nomComplet() }}</option>
            @endforeach
        </select>
    </div>
    <div class="actions">
        <button type="submit" class="btn-f">Afficher</button>
        @if($filtresActifs)
        <a href="{{ route('reporting.parc') }}" class="btn-raz" title="Revenir à l'exercice en cours, tous véhicules et chauffeurs">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><polyline points="3 3 3 9 9 9"/></svg>
            Réinitialiser
        </a>
        @endif
    </div>
</form>

<div class="periode-bandeau">
    <span>{!! $ico('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!}</span>
    <span>Période analysée : <strong>{{ $libellePeriode }}</strong></span>
    <span style="color:#475569">Comparée à : {{ $libellePrec }}</span>
    @if($filtreNom)<span style="color:#475569">Filtre : <strong>{{ $filtreNom }}</strong></span>@endif
    @if($gestionnaires->isNotEmpty())<span style="margin-left:auto;color:#475569">Gestionnaire(s) : {{ $gestionnaires->join(', ') }}</span>@endif
</div>

{{-- ═══ 1. L'ESSENTIEL ═══ --}}
<div class="rp-titre">1. L'essentiel de la période</div>
<div class="essentiel">
    <div class="ess">
        <div class="lbl">Recettes encaissées</div>
        <div class="val">{{ $F($kpi['recettes']) }} <small>F</small></div>
        <div class="aide">{{ $P($kpi['taux']) }} % de l'objectif ({{ $F($kpi['objectif']) }} F)</div>
        <div class="gauge"><div class="{{ $classeTaux($kpi['objectif'] ? $kpi['taux'] : null) }}" style="width:{{ min(100, $kpi['taux']) }}%"></div></div>
        @if($kpi['recouvrement'] > 0)<div class="aide">dont {{ $F($kpi['recouvrement']) }} F de recouvrements</div>@endif
        {!! $variation($kpi['recettes'], $kpiPrec['recettes']) !!}
    </div>
    <div class="ess">
        <div class="lbl">Charges</div>
        <div class="val" style="color:var(--ko)">{{ $F($kpi['charges']) }} <small>F</small></div>
        <div class="mini"><span>Pannes ({{ $kpi['nb_pannes'] }})</span><strong>{{ $F($kpi['pannes']) }}</strong></div>
        <div class="mini"><span>Maintenance</span><strong>{{ $F($kpi['maintenance']) }}</strong></div>
        <div class="mini"><span>Pénalités ({{ $kpi['nb_penalites'] }})</span><strong>{{ $F($kpi['penalites']) }}</strong></div>
        <div class="mini"><span>Autres dépenses</span><strong>{{ $F($kpi['autres_depenses']) }}</strong></div>
        {!! $variation($kpi['charges'], $kpiPrec['charges'], false) !!}
    </div>
    <div class="ess {{ $kpi['resultat'] >= 0 ? 'ok' : 'ko' }}">
        <div class="lbl">Résultat net</div>
        <div class="val">{{ $signe($kpi['resultat']) }} <small>F</small></div>
        <div class="aide">Recettes − charges @if($kpi['marge'] !== null) · marge {{ $P($kpi['marge']) }} %@endif</div>
        {!! $variation($kpi['resultat'], $kpiPrec['resultat'], true, true) !!}
    </div>
    <div class="ess">
        <div class="lbl">Régularité des recettes</div>
        @php $pctJours = $kpi['jours'] ? round($kpi['jours_atteints'] / $kpi['jours'] * 100, 2) : 0; @endphp
        <div class="val">{{ $kpi['jours_atteints'] }} <small>/ {{ $kpi['jours'] }} jours</small></div>
        <div class="aide">journées-chauffeur où l'objectif a été atteint ({{ $P($pctJours) }} %)</div>
        <div class="gauge"><div class="{{ $classeTaux($kpi['jours'] ? $pctJours : null) }}" style="width:{{ $pctJours }}%"></div></div>
        @php
            $nbDeficit = $parVehicule->where('verdict', 'deficit')->count();
            $nbRentables = $parVehicule->where('verdict', 'rentable')->count();
        @endphp
        <div class="aide" style="margin-top:2px"><strong style="color:var(--ok)">{{ $nbRentables }}</strong> véhicule(s) rentable(s) · <strong style="color:{{ $nbDeficit ? 'var(--ko)' : 'var(--gris)' }}">{{ $nbDeficit }}</strong> en déficit</div>
    </div>
</div>

{{-- ═══ 2. À TRAITER ═══ --}}
<div class="rp-titre">2. À traiter en priorité</div>
@if(empty($aTraiter))
<div class="todo-ok">Aucun point bloquant : pas de déficit, de retard de maintenance, de panne ouverte ni d'impayé.</div>
@else
<div class="todo">
    @foreach($aTraiter as $pt)
    <div class="todo-item {{ $pt['niveau'] }}">
        <div>
            <div class="tt">{{ $pt['titre'] }}</div>
            @if($pt['detail'])<div class="dd">{{ $pt['detail'] }}</div>@endif
        </div>
        <a href="{{ route('parc.index', ['tab' => $pt['onglet']]) }}">Voir →</a>
    </div>
    @endforeach
</div>
@endif

{{-- ═══ 3. RENTABILITÉ PAR VÉHICULE ═══ --}}
<div class="rp-titre">3. Rentabilité par véhicule</div>
<div class="rp-section">
    <h3>{!! $ico('<path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>') !!} Quels véhicules rapportent, lesquels coûtent ?</h3>
    <div class="intro">Cliquez sur une immatriculation pour ouvrir sa fiche rapport détaillée. Classés du moins rentable au plus rentable. Résultat = recettes (y compris recouvrements) − pannes − maintenance − pénalités du véhicule. Les autres dépenses (bureau) ne sont imputées à aucun véhicule.</div>
    @if($parVehicule->isEmpty())
    <p style="color:var(--gris);font-size:13px">Aucun véhicule dans ce périmètre.</p>
    @else
    <div class="rp-scroll">
    <table class="rp-tbl">
        <thead><tr><th>Véhicule</th><th class="num">Jours</th><th class="num">Recettes</th><th>Atteinte objectif</th><th class="num">Charges</th><th class="num">Résultat</th><th>Verdict</th></tr></thead>
        <tbody>
        @foreach($parVehicule as $v)
        <tr>
            <td><a href="{{ route('reporting.parc.vehicule', ['vehicule' => $v['id']] + request()->only(['periode', 'exercice', 'date_debut', 'date_fin'])) }}" title="Ouvrir la fiche rapport du véhicule" style="color:var(--accent);text-decoration:none"><strong>{{ $v['immat'] }}</strong> ↗</a><span class="sous">{{ $v['categorie'] }}@if($v['statut'] !== 'en_service') · {{ $v['statut_label'] }}@endif</span></td>
            <td class="num">{{ $v['jours'] }}</td>
            <td class="num">{{ $F($v['recettes']) }}<span class="sous">obj. {{ $F($v['objectif']) }}</span></td>
            <td class="taux-cell">
                @if($v['taux'] === null)<span style="color:var(--gris);font-size:12px">—</span>
                @else
                <span class="t" style="color:{{ $couleurTaux($v['taux']) }}">{{ $P($v['taux']) }} %</span>
                <div class="gauge"><div class="{{ $classeTaux($v['taux']) }}" style="width:{{ min(100, $v['taux']) }}%"></div></div>
                @endif
            </td>
            <td class="num">{{ $F($v['charges']) }}
                @if($v['charges'] > 0)<span class="sous">@if($v['pannes'])pannes {{ $F($v['pannes']) }} @endif @if($v['maintenance'])· maint. {{ $F($v['maintenance']) }} @endif @if($v['penalites'])· pén. {{ $F($v['penalites']) }} @endif @if($v['autres'])· dép. {{ $F($v['autres']) }}@endif</span>@endif
            </td>
            <td class="num {{ $v['resultat'] >= 0 ? 'pos' : 'neg' }}">{{ $signe($v['resultat']) }}</td>
            <td><span class="verdict v-{{ $v['verdict'] }}">{{ ['rentable' => 'Rentable', 'deficit' => 'Déficit', 'equilibre' => 'Équilibre'][$v['verdict']] }}</span></td>
        </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <td>Total ({{ $parVehicule->count() }})</td>
            <td class="num">{{ $parVehicule->sum('jours') }}</td>
            <td class="num">{{ $F($parVehicule->sum('recettes')) }}</td>
            @php $tauxTot = $parVehicule->sum('objectif') > 0 ? round($parVehicule->sum('recettes') / $parVehicule->sum('objectif') * 100, 2) : null; @endphp
            <td style="color:{{ $couleurTaux($tauxTot) }}">{{ $tauxTot !== null ? $P($tauxTot) . ' %' : '—' }}</td>
            <td class="num">{{ $F($parVehicule->sum('charges')) }}</td>
            <td class="num {{ $parVehicule->sum('resultat') >= 0 ? 'pos' : 'neg' }}">{{ $signe($parVehicule->sum('resultat')) }}</td>
            <td></td>
        </tr></tfoot>
    </table>
    </div>
    @php $horsTableau = $kpi['charges'] - $parVehicule->sum('charges'); @endphp
    @if($horsTableau > 0)
    <div class="intro" style="margin:8px 0 0">{{ $F($horsTableau) }} F de charges ne sont rattachées à aucun véhicule de ce tableau (autres dépenses de bureau, véhicule hors tableau…). Elles sont comptées dans le résultat net de la section 1, pas ici.</div>
    @endif
    @endif
</div>

{{-- ═══ 4. CHAUFFEURS ═══ --}}
<div class="rp-titre">4. Performance des chauffeurs</div>
<div class="rp-section">
    <h3>{!! $ico('<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>') !!} Qui atteint ses objectifs ?</h3>
    <div class="intro">Les {{ $chauffeurs->count() }} chauffeur(s) du formulaire Chauffeurs, du taux d'atteinte le plus faible au plus élevé. « Reste dû » = créances de recouvrement encore actives.</div>
    @if($chauffeurs->isEmpty())
    <p style="color:var(--gris);font-size:13px">Aucun chauffeur enregistré dans le formulaire Chauffeurs.</p>
    @else
    <div class="rp-scroll">
    <table class="rp-tbl">
        <thead><tr><th>Chauffeur</th><th class="num">Jours</th><th class="num">Recettes</th><th>Atteinte objectif</th><th class="num">Écart</th><th class="num">Moy. / jour</th><th class="num">Pénalités</th><th class="num">Reste dû</th></tr></thead>
        <tbody>
        @foreach($chauffeurs as $c)
        <tr>
            <td><strong>{{ $c['nom'] }}</strong>@if($c['nb_vehicules'] > 1)<span class="sous">{{ $c['nb_vehicules'] }} véhicules</span>@endif</td>
            <td class="num">{{ $c['jours'] }}<span class="sous">@if($c['jours'] === 0) Aucune recette saisie @else {{ $c['jours_atteints'] }} jour(s) objectif atteint @if($c['absences']) · {{ $c['absences'] }} absence(s) @endif @endif</span></td>
            <td class="num">{{ $F($c['recettes']) }}<span class="sous">obj. {{ $F($c['objectif']) }}</span></td>
            <td class="taux-cell">
                @if($c['taux'] === null)<span style="color:var(--gris)">—</span>
                @else
                <span class="t" style="color:{{ $couleurTaux($c['taux']) }}">{{ $P($c['taux']) }} %</span>
                <div class="gauge"><div class="{{ $classeTaux($c['taux']) }}" style="width:{{ min(100, $c['taux']) }}%"></div></div>
                @endif
            </td>
            <td class="num {{ $c['ecart'] >= 0 ? 'pos' : 'neg' }}">{{ $signe($c['ecart']) }}</td>
            <td class="num">{{ $F($c['moyenne']) }}</td>
            <td class="num" style="{{ $c['penalites'] ? 'color:var(--ko)' : '' }}">{{ $c['penalites'] ? $F($c['penalites']) : '—' }}@if($c['nb_penalites'])<span class="sous">{{ $c['nb_penalites'] }} infraction(s)</span>@endif</td>
            <td class="num" style="{{ $c['dette'] ? 'color:var(--ko);font-weight:700' : '' }}">{{ $c['dette'] ? $F($c['dette']) : '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @endif
</div>

{{-- ═══ 5. ÉVOLUTION : RECETTES FACE AUX DÉPENSES ═══ --}}
<div class="rp-titre">5. Évolution des recettes et des dépenses</div>
<div class="rp-section">
    <h3>{!! $ico('<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>') !!} Recettes {{ $evolution['par_semaine'] ? 'hebdomadaires' : 'journalières' }} face à l'objectif et aux dépenses</h3>
    @php $pts = collect($evolution['points']); @endphp
    @if($pts->isEmpty() || ($pts->sum('recette') + $pts->sum('objectif') + $pts->sum('bureau') + $pts->sum('vehicule')) === 0)
    <p style="color:var(--gris);font-size:13px">Aucune recette ni dépense sur cette période.</p>
    @else
    @php
        $n = $pts->count();
        $vmax = max($pts->max('recette'), $pts->max('objectif'), $pts->max('bureau'), $pts->max('vehicule'), 1);
        // Graduation « ronde » : 1, 2 ou 5 × 10^k
        $brut = $vmax / 5; $p10 = 10 ** floor(log10($brut)); $pas = (int) (collect([1, 2, 5, 10])->first(fn($m) => $m * $p10 >= $brut) * $p10);
        $vmax = (int) (ceil($vmax / $pas) * $pas);
        $W = 960; $H = 300; $L = 58; $R = 14; $T = 16; $B = 36;
        $cw = $W - $L - $R; $chH = $H - $T - $B; $base = $T + $chH;
        $col = $cw / $n;
        $xOf = fn($i) => round($L + ($i + 0.5) * $col, 1);
        $yOf = fn($v) => round($base - ($v / $vmax) * $chH, 1);
        $bw = round(min(26, $col * 0.62), 1);
        $kfmt = fn($v) => $v >= 1000000 ? rtrim(rtrim(number_format($v / 1000000, 1, ',', ' '), '0'), ',') . ' M' : ($v >= 1000 ? number_format($v / 1000, 0, ',', ' ') . ' k' : (string) $v);
        // Courbe lissée (Catmull-Rom → Bézier), bornée entre le haut du graphe et l'axe
        // $vals : [index => valeur] ; les index absents ne sont pas tracés (la courbe les enjambe)
        $courbe = function (array $vals) use ($xOf, $yOf, $T, $base) {
            $p = array_map(fn($v, $i) => [$xOf($i), $yOf($v)], $vals, array_keys($vals));
            if (count($p) === 1) return 'M' . $p[0][0] . ',' . $p[0][1];
            $d = 'M' . $p[0][0] . ',' . $p[0][1];
            $cl = fn($y) => round(max($T, min($base, $y)), 1);
            for ($i = 0; $i < count($p) - 1; $i++) {
                $p0 = $p[max($i - 1, 0)]; $p1 = $p[$i]; $p2 = $p[$i + 1]; $p3 = $p[min($i + 2, count($p) - 1)];
                $c1 = [round($p1[0] + ($p2[0] - $p0[0]) / 6, 1), $cl($p1[1] + ($p2[1] - $p0[1]) / 6)];
                $c2 = [round($p2[0] - ($p3[0] - $p1[0]) / 6, 1), $cl($p2[1] - ($p3[1] - $p1[1]) / 6)];
                $d .= " C{$c1[0]},{$c1[1]} {$c2[0]},{$c2[1]} {$p2[0]},{$p2[1]}";
            }
            return $d;
        };
        $aire = fn(string $d, array $vals) => $d . ' L' . $xOf(array_key_last($vals)) . ',' . $base . ' L' . $xOf(array_key_first($vals)) . ',' . $base . ' Z';
        $series = [
            // Objectif : uniquement les jours travaillés (un jour sans saisie n'a pas d'objectif, pas « 0 »)
            'objectif' => ['Objectif recette', '#94a3b8', $pts->pluck('objectif')->filter(fn($v) => $v > 0)->all()],
            'vehicule' => ['Dépenses véhicules', '#f59e0b', $pts->pluck('vehicule')->all()],
            'bureau'   => ['Dépenses bureau', '#dc2626', $pts->pluck('bureau')->all()],
        ];
        $tous = max(1, (int) ceil($n / 15));
        $fmtDate = fn($d) => \Carbon\Carbon::parse($d)->format('d/m');
        $totaux = ['recette' => $pts->sum('recette'), 'objectif' => $pts->sum('objectif'), 'vehicule' => $pts->sum('vehicule'), 'bureau' => $pts->sum('bureau')];
        $infos = $pts->map(fn($p) => [
            'd' => $evolution['par_semaine'] ? 'Semaine du ' . \Carbon\Carbon::parse($p['date'])->translatedFormat('d M Y') : ucfirst(\Carbon\Carbon::parse($p['date'])->translatedFormat('l d M Y')),
            'r' => $p['recette'], 'o' => $p['objectif'], 'v' => $p['vehicule'], 'b' => $p['bureau'], 's' => $p['saisi'],
        ])->values();
    @endphp

    <div class="evo-totaux">
        <div class="evo-tot" style="--c:var(--accent)"><span>Recettes</span><strong>{{ $F($totaux['recette']) }} F</strong></div>
        <div class="evo-tot" style="--c:#94a3b8"><span>Objectif</span><strong>{{ $F($totaux['objectif']) }} F</strong></div>
        <div class="evo-tot" style="--c:#f59e0b"><span>Dépenses véhicules</span><strong>{{ $F($totaux['vehicule']) }} F</strong><em>pannes + maintenance + pénalités</em></div>
        <div class="evo-tot" style="--c:#dc2626"><span>Dépenses bureau</span><strong>{{ $F($totaux['bureau']) }} F</strong></div>
    </div>

    <div class="evo-legende" id="evo-legende">
        <button type="button" data-serie="recette" class="on"><i style="background:var(--accent);height:10px;border-radius:2px"></i>Recettes</button>
        @foreach($series as $cle => [$lib, $coul])
        <button type="button" data-serie="{{ $cle }}" class="on"><i style="background:{{ $coul }}{{ $cle === 'objectif' ? ';height:0;border-top:2px dashed ' . $coul . ';background:none' : '' }}"></i>{{ $lib }}</button>
        @endforeach
        <span class="evo-aide">Cliquez sur une légende pour masquer ou afficher la série.</span>
    </div>

    <div class="rp-scroll evo-zone">
        <svg id="evo-svg" viewBox="0 0 {{ $W }} {{ $H }}" width="100%" style="min-width:600px;font-family:inherit;display:block" role="img" aria-label="Évolution des recettes et des dépenses">
            <defs>
                @foreach($series as $cle => [$lib, $coul])
                <linearGradient id="evo-g-{{ $cle }}" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="{{ $coul }}" stop-opacity="{{ $cle === 'objectif' ? 0 : .18 }}"/>
                    <stop offset="1" stop-color="{{ $coul }}" stop-opacity="0"/>
                </linearGradient>
                @endforeach
            </defs>
            @for($g = 0; $g <= $vmax; $g += $pas)
                <line x1="{{ $L }}" y1="{{ $yOf($g) }}" x2="{{ $W - $R }}" y2="{{ $yOf($g) }}" stroke="{{ $g === 0 ? '#cbd5e1' : '#eef2f7' }}"/>
                <text x="{{ $L - 8 }}" y="{{ $yOf($g) + 3.5 }}" fill="#94a3b8" font-size="10.5" text-anchor="end">{{ $kfmt($g) }}</text>
            @endfor

            <g data-serie="recette">
            @foreach($pts as $i => $p)
                @if($p['recette'] > 0)
                <rect x="{{ round($xOf($i) - $bw / 2, 1) }}" y="{{ $yOf($p['recette']) }}" width="{{ $bw }}" height="{{ round($base - $yOf($p['recette']), 1) }}" rx="3"
                      fill="var(--accent)" opacity="{{ $p['objectif'] > 0 && $p['recette'] < $p['objectif'] ? .45 : .85 }}"/>
                @endif
            @endforeach
            </g>

            @foreach($series as $cle => [$lib, $coul, $vals])
            @if(array_sum($vals) > 0)
            @php $d = $courbe($vals); @endphp
            <g data-serie="{{ $cle }}">
                @if($cle !== 'objectif')<path d="{{ $aire($d, $vals) }}" fill="url(#evo-g-{{ $cle }})" stroke="none"/>@endif
                <path d="{{ $d }}" fill="none" stroke="{{ $coul }}" stroke-width="{{ $cle === 'objectif' ? 1.6 : 2.2 }}" stroke-linecap="round" stroke-linejoin="round" @if($cle === 'objectif') stroke-dasharray="6,5" @endif/>
                @foreach($vals as $i => $v)
                    @if($cle === 'objectif')<circle cx="{{ $xOf($i) }}" cy="{{ $yOf($v) }}" r="2.2" fill="{{ $coul }}"/>
                    @elseif($v > 0)<circle cx="{{ $xOf($i) }}" cy="{{ $yOf($v) }}" r="3.2" fill="#fff" stroke="{{ $coul }}" stroke-width="2"/>@endif
                @endforeach
            </g>
            @endif
            @endforeach

            @foreach($pts as $i => $p)
                @if($i % $tous === 0)
                <text x="{{ $xOf($i) }}" y="{{ $H - $B + 16 }}" fill="#64748b" font-size="10" text-anchor="middle">{{ $fmtDate($p['date']) }}</text>
                @endif
            @endforeach

            <line id="evo-curseur" x1="0" y1="{{ $T }}" x2="0" y2="{{ $base }}" stroke="#334155" stroke-width="1" stroke-dasharray="3,3" opacity="0"/>
            @foreach($pts as $i => $p)
                <rect class="evo-zone-survol" data-i="{{ $i }}" x="{{ round($L + $i * $col, 1) }}" y="{{ $T }}" width="{{ round($col, 1) }}" height="{{ $chH }}" fill="transparent"/>
            @endforeach
        </svg>
        <div class="evo-bulle" id="evo-bulle"></div>
    </div>
    <div class="intro" style="margin:6px 0 0">Barres pâles : {{ $evolution['par_semaine'] ? 'semaines' : 'jours' }} sous l'objectif. Les dépenses sont rattachées à leur date : déclaration de la panne, date de la dépense ou de l'infraction.</div>
    <script>
    (function(){
        var infos = @json($infos), svg = document.getElementById('evo-svg'), bulle = document.getElementById('evo-bulle'), curseur = document.getElementById('evo-curseur');
        var fmt = function(v){ return Math.round(v).toLocaleString('fr-FR') + ' F'; };
        var ligne = function(c, l, v){ return '<div><i style="background:'+c+'"></i>'+l+'<b>'+fmt(v)+'</b></div>'; };
        svg.querySelectorAll('.evo-zone-survol').forEach(function(z){
            z.addEventListener('mouseenter', function(){
                var p = infos[+z.dataset.i], x = +z.getAttribute('x') + (+z.getAttribute('width')) / 2;
                curseur.setAttribute('x1', x); curseur.setAttribute('x2', x); curseur.setAttribute('opacity', .5);
                var taux = p.o > 0 ? ' <span style="color:'+(p.r >= p.o ? '#16a34a' : '#dc2626')+'">('+(p.r / p.o * 100).toLocaleString('fr-FR', {minimumFractionDigits: 2, maximumFractionDigits: 2})+' %)</span>' : '';
                bulle.innerHTML = '<strong>'+p.d+'</strong>'
                    + (p.s ? ligne('var(--accent)', 'Recettes', p.r).replace('</b>', '</b>'+taux) : '<div style="color:#94a3b8">Aucune recette saisie</div>')
                    + (p.o ? ligne('#94a3b8', 'Objectif', p.o) : '')
                    + ligne('#f59e0b', 'Dépenses véhicules', p.v) + ligne('#dc2626', 'Dépenses bureau', p.b);
                var r = svg.getBoundingClientRect(), px = x / {{ $W }} * r.width;
                bulle.style.left = Math.min(Math.max(px - 110, 0), r.width - 230) + 'px';
                bulle.classList.add('on');
            });
        });
        svg.addEventListener('mouseleave', function(){ bulle.classList.remove('on'); curseur.setAttribute('opacity', 0); });
        document.querySelectorAll('#evo-legende button').forEach(function(b){
            b.addEventListener('click', function(){
                b.classList.toggle('on');
                svg.querySelectorAll('g[data-serie="'+b.dataset.serie+'"]').forEach(function(g){ g.style.display = b.classList.contains('on') ? '' : 'none'; });
            });
        });
    })();
    </script>
    @endif
</div>

{{-- ═══ 6. OÙ PASSE L'ARGENT ═══ --}}
<div class="rp-titre">6. Où passe l'argent ?</div>
<div class="rp-grid2">
    <div class="rp-section">
        <h3>{!! $ico('<path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/>') !!} Répartition des charges</h3>
        @php
            $postes = collect([
                ['Pannes', $kpi['pannes'], '#dc2626'],
                ['Maintenance', $kpi['maintenance'], '#f59e0b'],
                ['Pénalités', $kpi['penalites'], '#a855f7'],
            ])->merge($depensesParType->map(fn($d) => [$d['type'], $d['total'], '#64748b']))
              ->filter(fn($p) => $p[1] > 0)->sortByDesc(fn($p) => $p[1])->values();
            $totPostes = max($postes->sum(fn($p) => $p[1]), 1);
        @endphp
        @if($postes->isEmpty())
        <p style="color:var(--gris);font-size:13px">Aucune charge sur cette période.</p>
        @else
        <div class="intro">Part de chaque poste dans le total des charges ({{ $F($kpi['charges']) }} F).</div>
        @foreach($postes as [$lib, $mt, $coul])
        <div class="bar-wrap">
            <div class="bar-label" title="{{ $lib }}">{{ $lib }}</div>
            <div class="bar-track"><div class="bar-fill" style="width:{{ round($mt / $totPostes * 100) }}%;background:{{ $coul }}"></div></div>
            <div class="bar-val">{{ $F($mt) }} F · {{ $P($mt / $totPostes * 100) }} %</div>
        </div>
        @endforeach
        @endif
    </div>
    <div class="rp-section">
        <h3>{!! $ico('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') !!} Incidents de la période</h3>
        <table class="rp-tbl">
            <thead><tr><th>Pannes par urgence</th><th class="num">Nombre</th><th class="num">Coût</th></tr></thead>
            <tbody>
            @php $urg = ['critique' => 'Critique', 'haute' => 'Haute', 'moyenne' => 'Moyenne', 'faible' => 'Faible']; @endphp
            @forelse(collect($urg)->filter(fn($l, $k) => isset($pannesUrgence[$k])) as $k => $lib)
            <tr><td>{{ $lib }}</td><td class="num">{{ $pannesUrgence[$k]->nb }}</td><td class="num">{{ $F($pannesUrgence[$k]->cout) }}</td></tr>
            @empty
            <tr><td colspan="3" style="color:var(--gris)">Aucune panne déclarée.</td></tr>
            @endforelse
            </tbody>
        </table>
        <table class="rp-tbl" style="margin-top:12px">
            <thead><tr><th>Pénalités par type</th><th class="num">Nombre</th><th class="num">Montant</th></tr></thead>
            <tbody>
            @forelse($penalitesType as $p)
            <tr><td>{{ $p['type'] }}</td><td class="num">{{ $p['nb'] }}</td><td class="num">{{ $F($p['montant']) }}</td></tr>
            @empty
            <tr><td colspan="3" style="color:var(--gris)">Aucune pénalité.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ═══ DÉTAIL JOUR PAR JOUR (filtre véhicule ou chauffeur) ═══ --}}
@if($detailJour->isNotEmpty())
<div class="rp-titre">Détail jour par jour — {{ $filtreNom }}</div>
<div class="rp-section">
    @php
        $dtRec = $detailJour->sum('recette'); $dtObj = $detailJour->sum('objectif');
    @endphp
    <div class="rp-scroll">
    <table class="rp-tbl">
        <thead><tr><th>Date</th><th>Véhicule</th><th>Chauffeur</th><th class="num">Recette</th><th class="num">Objectif</th><th class="num">Écart</th><th>Mode</th><th>Motif</th></tr></thead>
        <tbody>
        @foreach($detailJour as $dj)
        @php $ec = $dj->recette - $dj->objectif; @endphp
        <tr>
            <td>{{ $dj->date->translatedFormat('D d/m/Y') }}</td>
            <td>{{ $dj->vehicule->immatriculation ?? '?' }}</td>
            <td>{{ $dj->chauffeur ?? '—' }}</td>
            <td class="num" style="font-weight:600">{{ $F($dj->recette) }}</td>
            <td class="num">{{ $F($dj->objectif) }}</td>
            <td class="num {{ $ec >= 0 ? 'pos' : 'neg' }}">{{ $signe($ec) }}</td>
            <td>{{ $dj->mode_remise ? ucfirst(str_replace('_', ' ', $dj->mode_remise)) : '—' }}</td>
            <td>@if($dj->motif_absence)<span style="color:var(--ko);font-weight:600">{{ \App\Models\TypeParc::libelleMotif($dj->motif_absence) }}</span>@else — @endif</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot><tr>
            <td colspan="3">Total ({{ $detailJour->count() }} jours)</td>
            <td class="num">{{ $F($dtRec) }}</td><td class="num">{{ $F($dtObj) }}</td>
            <td class="num {{ $dtRec - $dtObj >= 0 ? 'pos' : 'neg' }}">{{ $signe($dtRec - $dtObj) }}</td><td colspan="2"></td>
        </tr></tfoot>
    </table>
    </div>
</div>
@endif

{{-- ═══ OKR ═══ --}}
@if($okrGestionnaire || $okrChauffeurs->isNotEmpty())
<div class="rp-titre">Objectifs OKR — exercice {{ \App\Services\ExerciceParc::nom($periodeOkr) }}</div>
<div class="rp-section">
    <table class="rp-tbl">
        <thead><tr><th>Personne</th><th class="num">Objectifs</th><th class="num">Atteints</th><th>Score</th></tr></thead>
        <tbody>
        @if($okrGestionnaire)
        <tr><td><strong>Vous (gestionnaire)</strong></td><td class="num">{{ $okrGestionnaire['total'] }}</td><td class="num">{{ $okrGestionnaire['atteints'] }}</td>
            <td class="taux-cell"><span class="t" style="color:{{ $couleurTaux($okrGestionnaire['score']) }}">{{ $P($okrGestionnaire['score']) }} %</span><div class="gauge"><div class="{{ $classeTaux($okrGestionnaire['score']) }}" style="width:{{ min(100, $okrGestionnaire['score']) }}%"></div></div></td></tr>
        @endif
        @foreach($okrChauffeurs as $o)
        <tr><td>{{ $o['nom'] }}</td><td class="num">{{ $o['total'] }}</td><td class="num">{{ $o['atteints'] }}</td>
            <td class="taux-cell"><span class="t" style="color:{{ $couleurTaux($o['score']) }}">{{ $P($o['score']) }} %</span><div class="gauge"><div class="{{ $classeTaux($o['score']) }}" style="width:{{ min(100, $o['score']) }}%"></div></div></td></tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

<script>
function majPeriode(){
    var p = document.getElementById('sel-periode').value;
    document.getElementById('dates-custom').classList.toggle('show', p === 'personnalise');
    document.getElementById('bloc-exercice').classList.toggle('hide', p === 'personnalise');
    document.getElementById('lbl-exercice').textContent = p === 'trois_exercices' ? "Jusqu'à l'exercice" : 'Exercice';
}
</script>
@endsection
