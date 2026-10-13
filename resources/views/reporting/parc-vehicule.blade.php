@extends('layouts.app')
@section('sousTitre', 'Fiche véhicule')
@section('titre', 'Rapport ' . $vehicule->immatriculation)

@push('styles')
<style>
.fv-barre{display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:space-between;margin-bottom:14px}
.fv-barre form{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.fv-barre select{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;background:#fff}
.fv-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:1px solid var(--bord);background:#fff;color:#334155}
.fv-btn.pri{background:var(--accent);border-color:var(--accent);color:#fff}
.fiche{background:#fff;border:1px solid var(--bord);border-radius:14px;overflow:hidden}
.fiche-tete{display:flex;flex-wrap:wrap;gap:18px;align-items:center;justify-content:space-between;padding:20px 24px;background:linear-gradient(135deg,#1F4E79,#2E74B5);color:#fff}
.fiche-tete h1{font-size:26px;letter-spacing:1px;margin:0;font-weight:800}
.fiche-tete .sous{font-size:13px;opacity:.85;margin-top:2px}
.fiche-tete .badges{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.fiche-tete .bdg{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.35);border-radius:20px;padding:2px 10px;font-size:11.5px;font-weight:600}
.fiche-tete .rang{text-align:right}
.fiche-tete .rang strong{display:block;font-size:30px;line-height:1}
.fiche-tete .rang span{font-size:12px;opacity:.85}
.fiche-corps{padding:20px 24px}
.fiche-periode{font-size:12.5px;color:#475569;margin-bottom:16px}
.fiche-periode strong{color:var(--bleu)}
.fv-section{margin-top:22px}
.fv-section h3{font-size:12px;font-weight:800;color:var(--gris);text-transform:uppercase;letter-spacing:.6px;margin:0 0 10px;padding-bottom:6px;border-bottom:1px solid var(--bord)}
.fv-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
.fv-kpi{border:1px solid var(--bord);border-radius:10px;padding:12px 14px}
.fv-kpi .l{font-size:11.5px;color:var(--gris);font-weight:700}
.fv-kpi .v{font-size:22px;font-weight:800;color:var(--bleu);font-variant-numeric:tabular-nums}
.fv-kpi .v small{font-size:12px;color:var(--gris);font-weight:600}
.fv-kpi .a{font-size:11.5px;color:#64748b}
.fv-kpi.ok{background:#f0fdf4;border-color:#86efac}.fv-kpi.ok .v{color:var(--ok)}
.fv-kpi.ko{background:#fef2f2;border-color:#fca5a5}.fv-kpi.ko .v{color:var(--ko)}
.fv-ligne{display:flex;justify-content:space-between;font-size:11.5px;color:#475569;padding:1px 0}
.var{font-size:11px;font-weight:700}.var.up{color:var(--ok)}.var.down{color:var(--ko)}.var.eq{color:var(--gris)}
.gauge{height:7px;background:#f1f5f9;border-radius:6px;overflow:hidden;margin:4px 0 2px}.gauge>div{height:100%;border-radius:6px}
.g-ok{background:var(--ok)}.g-warn{background:#f59e0b}.g-ko{background:var(--ko)}
.constats{display:grid;gap:6px}
.constat{padding:8px 12px;border-radius:8px;font-size:13px;border-left:4px solid}
.constat.ok{background:#f0fdf4;border-color:var(--ok);color:#166534}
.constat.warn{background:#fffbeb;border-color:#f59e0b;color:#92400e}
.constat.ko{background:#fef2f2;border-color:var(--ko);color:#991b1b}
.fv-grid2{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.fv-tbl{width:100%;border-collapse:collapse;font-size:12.5px}
.fv-tbl th{text-align:left;padding:6px 8px;background:#f8fafc;color:var(--gris);font-size:10.5px;text-transform:uppercase;border-bottom:2px solid var(--bord)}
.fv-tbl td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top}
.fv-tbl .n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
.fv-tbl tfoot td{font-weight:700;background:#f8fafc}
.fv-tbl .s{display:block;font-size:10.5px;color:var(--gris)}
.vide{font-size:12.5px;color:var(--gris);padding:6px 0}
.pill{display:inline-block;padding:1px 8px;border-radius:10px;font-size:10.5px;font-weight:700}
.p-ok{background:#dcfce7;color:#166534}.p-ko{background:#fee2e2;color:#991b1b}.p-warn{background:#fef3c7;color:#92400e}.p-gris{background:#f1f5f9;color:#475569}
.fiche-pied{padding:12px 24px;border-top:1px solid var(--bord);font-size:11px;color:var(--gris);display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px}
@media(max-width:900px){.fv-kpis{grid-template-columns:repeat(2,1fr)}.fv-grid2{grid-template-columns:1fr}.fv-scroll{overflow-x:auto}}
@media(max-width:520px){.fv-kpis{grid-template-columns:1fr}.fiche-tete .rang{text-align:left}}
@media print{
    #sidebar,.topbar,.fv-barre{display:none!important}
    main{margin:0!important;padding:0!important}
    body{background:#fff!important}
    .fiche{border:none;border-radius:0}
    .fiche-tete{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .fv-kpi,.constat,.pill,.gauge>div{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .fv-section{break-inside:avoid}
}
</style>
@endpush

@section('contenu')
@php
    $F = fn($n) => number_format((int) $n, 0, ',', ' ');
    $P = fn($v) => number_format((float) $v, 2, ',', ' ');
    $signe = fn($n) => ($n > 0 ? '+' : '') . number_format((int) $n, 0, ',', ' ');
    $classeTaux = fn($t) => $t === null ? 'g-warn' : ($t >= 100 ? 'g-ok' : ($t >= 80 ? 'g-warn' : 'g-ko'));
    $couleurTaux = fn($t) => $t === null ? 'var(--gris)' : ($t >= 100 ? 'var(--ok)' : ($t >= 80 ? '#b45309' : 'var(--ko)'));
    $variation = function ($cur, $prec, bool $hausseBonne = true, bool $enMontant = false) use ($libellePrec, $P) {
        if ($enMontant) {
            $d = (int) $cur - (int) $prec;
            return $d === 0 ? '<span class="var eq">= ' . e($libellePrec) . '</span>'
                : '<span class="var ' . ($d > 0 ? 'up' : 'down') . '">' . ($d > 0 ? '▲ +' : '▼ ') . number_format($d, 0, ',', ' ') . ' F vs ' . e($libellePrec) . '</span>';
        }
        if (!$prec) return '<span class="var eq">Pas de donnée sur ' . e($libellePrec) . '</span>';
        $pct = round(($cur - $prec) / abs($prec) * 100, 2);
        if ($pct == 0) return '<span class="var eq">= ' . e($libellePrec) . '</span>';
        return '<span class="var ' . ((($pct > 0) === $hausseBonne) ? 'up' : 'down') . '">' . ($pct > 0 ? '▲ +' : '▼ ') . $P($pct) . ' % vs ' . e($libellePrec) . '</span>';
    };
    $qs = request()->only(['periode', 'exercice', 'date_debut', 'date_fin']);
    $statutPanne = ['declaree' => ['Déclarée', 'p-ko'], 'en_diagnostic' => ['En diagnostic', 'p-warn'], 'en_intervention' => ['En intervention', 'p-warn'], 'cloturee' => ['Clôturée', 'p-ok']];
    $chauffeursActifs = $vehicule->chauffeurs;
@endphp

<div class="fv-barre">
    <a href="{{ route('reporting.parc', $qs) }}" class="fv-btn">← Reporting</a>
    <form method="GET" id="fv-form">
        <select onchange="location.href='{{ url('parc/reporting/vehicules') }}/'+this.value+'?'+new URLSearchParams(new FormData(document.getElementById('fv-form'))).toString()" aria-label="Véhicule">
            @foreach($vehicules as $v)<option value="{{ $v->id }}" @selected($v->id === $vehicule->id)>{{ $v->immatriculation }}</option>@endforeach
        </select>
        <input type="hidden" name="periode" value="{{ $periode === 'personnalise' ? 'personnalise' : $periode }}">
        @if($periode === 'personnalise')
        <input type="hidden" name="date_debut" value="{{ $debut->format('Y-m-d') }}"><input type="hidden" name="date_fin" value="{{ $fin->format('Y-m-d') }}">
        @else
        <select name="exercice" onchange="this.form.submit()" aria-label="Exercice">
            @foreach($exercices as $cle => $ex)<option value="{{ $cle }}" @selected($exercice === $cle)>{{ $ex['libelle'] }}</option>@endforeach
        </select>
        @endif
    </form>
    <button type="button" class="fv-btn pri" onclick="window.print()">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Imprimer / PDF
    </button>
</div>

<div class="fiche">
    {{-- ═══ EN-TÊTE ═══ --}}
    <div class="fiche-tete">
        <div>
            <h1>{{ $vehicule->immatriculation }}</h1>
            <div class="sous">{{ trim($vehicule->marque . ' ' . $vehicule->modele) ?: 'Marque / modèle non renseignés' }}</div>
            <div class="badges">
                <span class="bdg">{{ $vehicule->categorieLabel() }}</span>
                <span class="bdg">{{ $vehicule->statutLabel() }}</span>
                @if($vehicule->date_mise_en_service)<span class="bdg">En service depuis le {{ $vehicule->date_mise_en_service->format('d/m/Y') }}</span>@endif
                @if($vehicule->kilometrage)<span class="bdg">{{ $F($vehicule->kilometrage) }} km</span>@endif
            </div>
            <div class="sous" style="margin-top:8px">Chauffeur(s) : {{ $chauffeursActifs->isNotEmpty() ? $chauffeursActifs->map(fn($c) => $c->nomComplet() . ' (' . ($c->pivot->role === 'principal' ? 'principal' : 'alternant') . ')')->join(', ') : 'aucun affecté' }}</div>
        </div>
        @if($rang)
        <div class="rang"><strong>{{ $rang }}<sup style="font-size:14px">{{ $rang === 1 ? 'er' : 'e' }}</sup></strong><span>sur {{ $flotte->count() }} véhicules<br>au résultat de la période</span></div>
        @endif
    </div>

    <div class="fiche-corps">
        <div class="fiche-periode">Période : <strong>{{ $libellePeriode }}</strong> · comparée à {{ $libellePrec }}</div>

        {{-- ═══ INDICATEURS ═══ --}}
        <div class="fv-kpis">
            <div class="fv-kpi">
                <div class="l">Recettes encaissées</div>
                <div class="v">{{ $F($kpi['recettes']) }} <small>F</small></div>
                <div class="gauge"><div class="{{ $classeTaux($kpi['objectif'] ? $kpi['taux'] : null) }}" style="width:{{ min(100, $kpi['taux']) }}%"></div></div>
                <div class="a">{{ $P($kpi['taux']) }} % de l'objectif ({{ $F($kpi['objectif']) }} F)@if($kpi['recouvrement']) · dont {{ $F($kpi['recouvrement']) }} F recouvrés @endif</div>
                {!! $variation($kpi['recettes'], $kpiPrec['recettes']) !!}
            </div>
            <div class="fv-kpi">
                <div class="l">Charges du véhicule</div>
                <div class="v" style="color:var(--ko)">{{ $F($kpi['charges']) }} <small>F</small></div>
                <div class="fv-ligne"><span>Pannes ({{ $kpi['nb_pannes'] }})</span><strong>{{ $F($kpi['pannes']) }}</strong></div>
                <div class="fv-ligne"><span>Maintenance</span><strong>{{ $F($kpi['maintenance']) }}</strong></div>
                <div class="fv-ligne"><span>Pénalités ({{ $kpi['nb_penalites'] }})</span><strong>{{ $F($kpi['penalites']) }}</strong></div>
                {!! $variation($kpi['charges'], $kpiPrec['charges'], false) !!}
            </div>
            <div class="fv-kpi {{ $kpi['resultat'] >= 0 ? 'ok' : 'ko' }}">
                <div class="l">Résultat net</div>
                <div class="v">{{ $signe($kpi['resultat']) }} <small>F</small></div>
                <div class="a">Recettes − charges @if($kpi['marge'] !== null)· marge {{ $P($kpi['marge']) }} %@endif</div>
                {!! $variation($kpi['resultat'], $kpiPrec['resultat'], true, true) !!}
            </div>
            <div class="fv-kpi">
                <div class="l">Activité</div>
                @php $pctJ = $kpi['jours'] ? round($kpi['jours_atteints'] / $kpi['jours'] * 100, 2) : 0; @endphp
                <div class="v">{{ $kpi['jours'] }} <small>jours saisis</small></div>
                <div class="gauge"><div class="{{ $classeTaux($kpi['jours'] ? $pctJ : null) }}" style="width:{{ $pctJ }}%"></div></div>
                <div class="a">{{ $kpi['jours_atteints'] }} jour(s) objectif atteint ({{ $P($pctJ) }} %)</div>
                <div class="a">Moyenne : {{ $F($kpi['jours'] ? $kpi['recettes'] / $kpi['jours'] : 0) }} F / jour</div>
            </div>
        </div>

        {{-- ═══ CONSTATS ═══ --}}
        <div class="fv-section">
            <h3>Constats et points d'attention</h3>
            <div class="constats">
                @foreach($constats as [$niv, $txt])<div class="constat {{ $niv }}">{{ $txt }}</div>@endforeach
            </div>
        </div>

        {{-- ═══ RECETTES ═══ --}}
        <div class="fv-section fv-grid2">
            <div>
                <h3>Recettes par chauffeur</h3>
                @if($parChauffeur->isEmpty())<div class="vide">Aucune recette sur la période.</div>
                @else
                <div class="fv-scroll"><table class="fv-tbl">
                    <thead><tr><th>Chauffeur</th><th class="n">Jours</th><th class="n">Recettes</th><th class="n">Taux</th></tr></thead>
                    <tbody>
                    @foreach($parChauffeur as $c)
                    <tr><td>{{ $c['nom'] }}<span class="s">{{ $c['atteints'] }} jour(s) objectif atteint</span></td><td class="n">{{ $c['jours'] }}</td>
                        <td class="n">{{ $F($c['recette']) }}<span class="s">obj. {{ $F($c['objectif']) }}</span></td>
                        <td class="n" style="font-weight:700;color:{{ $couleurTaux($c['taux']) }}">{{ $c['taux'] !== null ? $P($c['taux']) . ' %' : '—' }}</td></tr>
                    @endforeach
                    </tbody>
                </table></div>
                @endif
            </div>
            <div>
                <h3>Évolution {{ $evolution['par_semaine'] ? 'hebdomadaire' : 'journalière' }}</h3>
                @php $pts = collect($evolution['points']); @endphp
                @if($pts->isEmpty() || $pts->sum('recette') + $pts->sum('vehicule') === 0)<div class="vide">Aucune donnée sur la période.</div>
                @else
                @php
                    $n = $pts->count(); $max = max($pts->max('recette'), $pts->max('objectif'), $pts->max('vehicule'), 1);
                    $W = 460; $H = 170; $L = 8; $B = 22; $T = 8; $ch = $H - $B - $T; $col = ($W - 2 * $L) / $n; $bw = max(2, min(14, $col * .62));
                    $x = fn($i) => round($L + ($i + .5) * $col, 1); $y = fn($v) => round($T + $ch - $v / $max * $ch, 1);
                    $obj = $pts->map(fn($p, $i) => $p['objectif'] > 0 ? $x($i) . ',' . $y($p['objectif']) : null)->filter()->join(' ');
                    $veh = $pts->map(fn($p, $i) => $x($i) . ',' . $y($p['vehicule']))->join(' ');
                    $tous = max(1, (int) ceil($n / 8));
                @endphp
                <svg viewBox="0 0 {{ $W }} {{ $H }}" width="100%" role="img" aria-label="Recettes, objectif et charges du véhicule">
                    <line x1="{{ $L }}" y1="{{ $T + $ch }}" x2="{{ $W - $L }}" y2="{{ $T + $ch }}" stroke="#cbd5e1"/>
                    @foreach($pts as $i => $p)
                        @if($p['recette'] > 0)<rect x="{{ round($x($i) - $bw / 2, 1) }}" y="{{ $y($p['recette']) }}" width="{{ round($bw, 1) }}" height="{{ round($T + $ch - $y($p['recette']), 1) }}" rx="2" fill="#2E74B5" opacity="{{ $p['objectif'] > 0 && $p['recette'] < $p['objectif'] ? .45 : .85 }}"><title>{{ \Carbon\Carbon::parse($p['date'])->format('d/m') }} : {{ $F($p['recette']) }} F</title></rect>@endif
                        @if($i % $tous === 0)<text x="{{ $x($i) }}" y="{{ $H - 6 }}" font-size="9" fill="#64748b" text-anchor="middle">{{ \Carbon\Carbon::parse($p['date'])->format('d/m') }}</text>@endif
                    @endforeach
                    @if($obj)<polyline points="{{ $obj }}" fill="none" stroke="#94a3b8" stroke-width="1.4" stroke-dasharray="5,4"/>@endif
                    @if($pts->sum('vehicule') > 0)<polyline points="{{ $veh }}" fill="none" stroke="#f59e0b" stroke-width="1.8" stroke-linejoin="round"/>@endif
                </svg>
                <div style="display:flex;gap:12px;font-size:11px;color:var(--gris);flex-wrap:wrap">
                    <span><i style="display:inline-block;width:10px;height:10px;background:#2E74B5;border-radius:2px;vertical-align:middle"></i> Recettes (pâle : sous l'objectif)</span>
                    <span><i style="display:inline-block;width:14px;border-top:2px dashed #94a3b8;vertical-align:middle"></i> Objectif</span>
                    <span><i style="display:inline-block;width:14px;border-top:2px solid #f59e0b;vertical-align:middle"></i> Charges</span>
                </div>
                @endif
            </div>
        </div>

        {{-- ═══ INTERVENTIONS ═══ --}}
        <div class="fv-section">
            <h3>Pannes de la période</h3>
            @if($pannes->isEmpty())<div class="vide">Aucune panne déclarée.</div>
            @else
            <div class="fv-scroll"><table class="fv-tbl">
                <thead><tr><th>Date</th><th>Description</th><th>Urgence</th><th>Statut</th><th class="n">Délai</th><th class="n">Coût</th></tr></thead>
                <tbody>
                @foreach($pannes as $pa)
                <tr><td>{{ $pa->date_declaration?->format('d/m/Y') }}</td>
                    <td>{{ $pa->description }}@if($pa->lignes->count())<span class="s">Facture : {{ $pa->lignes->map(fn($l) => $l->designation . ($l->quantite > 1 ? ' ×' . $l->quantite : ''))->join(', ') }}</span>@endif</td>
                    <td>{{ ucfirst($pa->urgence) }}</td>
                    <td><span class="pill {{ $statutPanne[$pa->statut][1] ?? 'p-gris' }}">{{ $statutPanne[$pa->statut][0] ?? $pa->statut }}</span>@if($pa->emprunt_id)<span class="s">financée par emprunt</span>@endif</td>
                    <td class="n">{{ $pa->delai_resolution !== null ? $pa->delai_resolution . ' j' : '—' }}</td>
                    <td class="n">{{ $F($pa->cout_reel) }}</td></tr>
                @endforeach
                </tbody>
                <tfoot><tr><td colspan="5">Total</td><td class="n">{{ $F($pannes->sum('cout_reel')) }}</td></tr></tfoot>
            </table></div>
            @endif
        </div>

        <div class="fv-section fv-grid2">
            <div>
                <h3>Maintenances réalisées</h3>
                @if($maintenances->isEmpty())<div class="vide">Aucune maintenance réalisée sur la période.</div>
                @else
                <table class="fv-tbl">
                    <thead><tr><th>Date</th><th>Type</th><th class="n">Coût</th></tr></thead>
                    <tbody>
                    @foreach($maintenances as $m)
                    <tr><td>{{ $m->date_realisation->format('d/m/Y') }}</td><td>{{ $m->typeLabel() }}<span class="s">{{ $m->prestataire }}@if($m->lignes->count()) · {{ $m->lignes->count() }} ligne(s) de facture @endif</span></td><td class="n">{{ $F($m->cout) }}</td></tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="2">Total</td><td class="n">{{ $F($maintenances->sum('cout')) }}</td></tr></tfoot>
                </table>
                @endif
            </div>
            <div>
                <h3>Prochaines échéances</h3>
                @if($prochaines->isEmpty())<div class="vide">Aucune maintenance planifiée.</div>
                @else
                <table class="fv-tbl">
                    <thead><tr><th>Type</th><th>Prévue le</th><th>État</th></tr></thead>
                    <tbody>
                    @foreach($prochaines as $m)
                    @php $j = (int) today()->diffInDays($m->prochaine_echeance, false); @endphp
                    <tr><td>{{ $m->typeLabel() }}</td><td>{{ $m->prochaine_echeance->format('d/m/Y') }}</td>
                        <td><span class="pill {{ $j < 0 ? 'p-ko' : ($j <= 15 ? 'p-warn' : 'p-ok') }}">{{ $j < 0 ? abs($j) . ' j de retard' : 'dans ' . $j . ' j' }}</span></td></tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        {{-- ═══ PÉNALITÉS ET ENGAGEMENTS ═══ --}}
        <div class="fv-section">
            <h3>Pénalités de la période</h3>
            @if($penalites->isEmpty())<div class="vide">Aucune pénalité.</div>
            @else
            <div class="fv-scroll"><table class="fv-tbl">
                <thead><tr><th>Date</th><th>Type</th><th>Chauffeur</th><th>Statut</th><th class="n">Montant</th><th class="n">Reste dû</th></tr></thead>
                <tbody>
                @foreach($penalites as $pe)
                <tr><td>{{ $pe->date_infraction->format('d/m/Y') }}</td><td>{{ $pe->typeLabel() }}</td><td>{{ $pe->chauffeur ?? '—' }}</td>
                    <td><span class="pill {{ $pe->statut === 'payee' ? 'p-ok' : 'p-ko' }}">{{ $pe->statutLabel() }}</span></td>
                    <td class="n">{{ $F($pe->montant) }}</td><td class="n">{{ $F($pe->soldeRestant()) }}</td></tr>
                @endforeach
                </tbody>
                <tfoot><tr><td colspan="4">Total</td><td class="n">{{ $F($penalites->sum('montant')) }}</td><td class="n">{{ $F($penalites->sum(fn($p) => $p->soldeRestant())) }}</td></tr></tfoot>
            </table></div>
            @endif
        </div>

        <div class="fv-section fv-grid2">
            <div>
                <h3>Recouvrements en cours</h3>
                @if($recouvrements->isEmpty())<div class="vide">Aucune créance active.</div>
                @else
                <table class="fv-tbl">
                    <thead><tr><th>Date</th><th>Chauffeur</th><th class="n">Reste dû</th></tr></thead>
                    <tbody>
                    @foreach($recouvrements as $rc)
                    <tr><td>{{ $rc->date_recette->format('d/m/Y') }}</td><td>{{ $rc->chauffeur_nom ?? '—' }}<span class="s">{{ ($rc->type_recouvrement ?? 'recette') === 'penalite' ? 'Pénalité' : 'Déficit de recette' }}</span></td><td class="n">{{ $F($rc->solde) }}</td></tr>
                    @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="2">Total</td><td class="n">{{ $F($recouvrements->sum('solde')) }}</td></tr></tfoot>
                </table>
                @endif
            </div>
            <div>
                <h3>Emprunts à rembourser</h3>
                @if($emprunts->isEmpty())<div class="vide">Aucun emprunt en cours.</div>
                @else
                <table class="fv-tbl">
                    <thead><tr><th>Prêteur</th><th>Échéance</th><th class="n">Reste dû</th></tr></thead>
                    <tbody>
                    @foreach($emprunts as $em)
                    <tr><td>{{ $em->preteur }}<span class="s">{{ $em->sourceLabel() }} · emprunté {{ $F($em->montant) }} F</span></td>
                        <td>{{ $em->date_echeance?->format('d/m/Y') ?? 'Non définie' }}@if($em->date_echeance && $em->date_echeance->lt(today())) <span class="pill p-ko">En retard</span>@endif</td>
                        <td class="n">{{ $F($em->soldeRestant()) }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    <div class="fiche-pied">
        <span>SOUMAFÊ — Parc automobile · Fiche véhicule {{ $vehicule->immatriculation }}</span>
        <span>Éditée le {{ now()->format('d/m/Y à H:i') }} par {{ auth()->user()->nomComplet() }}</span>
    </div>
</div>
@endsection
