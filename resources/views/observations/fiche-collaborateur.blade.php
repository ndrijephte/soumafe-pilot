@extends('layouts.app')
@section('sousTitre', 'Fiche collaborateur')
@section('titre', 'Fiche collaborateur')
@push('styles')
<style>
.fc-entete{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:12px}
.fc-entete h2{display:flex;align-items:center;gap:8px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:12px}
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:14px}
.panneau h3{font-size:13.5px;color:var(--bleu);margin:0 0 12px;display:flex;align-items:center;gap:6px}
.panneau h3 .nb{margin-left:auto;font-size:11px;font-weight:600;color:var(--gris)}
.vide{color:var(--gris);font-size:12.5px;text-align:center;padding:14px 0}

/* Sélection */
.fc-filtre{display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap}
.fc-filtre .champ label{font-size:10.5px;font-weight:600;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:4px}
.fc-filtre select,.fc-filtre input[type=date]{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;background:#fff;height:36px;box-sizing:border-box}
.fc-filtre select{min-width:260px}
.raccourcis{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
.raccourcis button{border:1px solid var(--bord);background:#fff;color:#475569;border-radius:20px;padding:4px 12px;font-size:11.5px;cursor:pointer}
.raccourcis button:hover{border-color:var(--accent);color:var(--accent)}

/* Identité */
.fc-id{display:grid;grid-template-columns:auto 1fr auto;gap:18px;align-items:center}
.fc-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#1f4e79,#2e74b5);color:#fff;display:flex;align-items:center;justify-content:center;font-size:19px;font-weight:700;letter-spacing:.5px}
.fc-id h3{margin:0;font-size:17px;color:var(--bleu)}
.fc-id .sous{font-size:12.5px;color:var(--gris);margin-top:2px}
.etiquettes{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
.etq{font-size:10.5px;font-weight:600;border-radius:12px;padding:2px 9px;background:#e2e8f0;color:#475569;display:inline-flex;align-items:center;gap:4px}
.etq.parc{background:#ccfbf1;color:#0f766e}.etq.ancien{background:#fee2e2;color:#b91c1c}.etq.pilote{background:#dbeafe;color:#1d4ed8}
.etq.projet{background:#f1f5f9;color:#334155;font-family:ui-monospace,monospace}
.etq.projet.resp{background:#1f4e79;color:#fff}
.fc-score{display:flex;align-items:center;gap:14px}
.anneau{--v:0;--c:var(--ok);width:74px;height:74px;border-radius:50%;background:conic-gradient(var(--c) calc(var(--v)*1%),#e2e8f0 0);display:flex;align-items:center;justify-content:center}
.anneau span{width:58px;height:58px;border-radius:50%;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:17px;font-weight:700;color:var(--bleu);line-height:1}
.anneau small{font-size:8.5px;font-weight:600;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;margin-top:3px}

/* Onglets */
.onglets{display:flex;gap:2px;border-bottom:2px solid var(--bord);margin:4px 0 14px;overflow-x:auto;overflow-y:hidden;scrollbar-width:none}
.onglets button{background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-2px;padding:9px 14px;font-size:13px;font-weight:600;color:var(--gris);cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:6px}
.onglets button .c{font-size:10.5px;background:#eef2f7;color:#475569;border-radius:10px;padding:1px 7px}
.onglets button.actif{color:var(--bleu);border-bottom-color:var(--accent)}
.onglets button.actif .c{background:var(--accent);color:#fff}
.vue{display:none}.vue.actif{display:block}

/* Indicateurs */
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:14px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:10px;padding:11px 12px}
.kpi .val{font-size:20px;font-weight:700;color:var(--bleu);line-height:1.1}
.kpi .lbl{font-size:10.5px;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;margin-top:4px}
.kpi .aide{font-size:11px;color:#94a3b8;margin-top:2px}
.deux{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.barre-ligne{display:grid;grid-template-columns:150px 1fr 44px;gap:10px;align-items:center;font-size:12.5px;margin-bottom:8px}
.barre-ligne .piste{height:8px;border-radius:5px;background:#eef2f7;overflow:hidden}
.barre-ligne .piste i{display:block;height:100%;border-radius:5px}
.barre-ligne .n{text-align:right;font-weight:600;color:#334155}
.composante{display:grid;grid-template-columns:150px 1fr 64px;gap:10px;align-items:center;font-size:12.5px;margin-bottom:8px}

/* Tableaux */
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord);vertical-align:top}
.mini-table tr:hover td{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10.5px;font-weight:600;display:inline-block;white-space:nowrap}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}.b-info{background:#dbeafe;color:#1d4ed8}.b-gris{background:#e2e8f0;color:#475569}
.code{font-family:ui-monospace,monospace;font-size:11.5px;color:var(--accent);font-weight:600}
.attenue{color:var(--gris);font-size:11.5px}

/* Journal */
.jr-outils{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:12px}
.jr-puce{border:1px solid var(--bord);background:#fff;border-radius:20px;padding:4px 11px;font-size:11.5px;font-weight:600;color:#475569;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.jr-puce i{width:8px;height:8px;border-radius:50%;display:inline-block}
.jr-puce.actif{background:var(--bleu);border-color:var(--bleu);color:#fff}
.jr-recherche{margin-left:auto;padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;min-width:220px}
.jr-jour{font-size:11.5px;font-weight:700;color:var(--bleu);padding:12px 0 6px;border-bottom:1px solid var(--bord);display:flex;justify-content:space-between}
.jr-jour span{font-weight:600;color:var(--gris);text-transform:none}
.jr-ligne{display:grid;grid-template-columns:48px 128px 1fr;gap:10px;padding:7px 0;border-bottom:1px dashed #edf1f6;font-size:12.5px;align-items:start}
.jr-ligne .h{color:var(--gris);font-variant-numeric:tabular-nums;font-size:11.5px;padding-top:1px}
.jr-dom{font-size:10.5px;font-weight:600;border-radius:10px;padding:2px 8px;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.jr-quoi strong{font-weight:600;color:#1e293b}
.jr-quoi .cible{color:#334155}
.jr-quoi .det{display:block;color:var(--gris);font-size:11.5px;margin-top:2px;white-space:pre-line;max-height:3.2em;overflow:hidden}
.jr-plus{display:block;margin:12px auto 0}
@media(max-width:900px){.deux{grid-template-columns:1fr}.fc-id{grid-template-columns:auto 1fr}.fc-score{grid-column:1/-1}.jr-ligne{grid-template-columns:44px 1fr}.jr-ligne .jr-dom{display:none}}
</style>
@endpush
@section('contenu')
@php
    $ic = fn(string $d, int $s=16) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
    $badgeStatut = fn($s) => match($s) { 'a_faire' => 'b-info', 'en_cours', 'a_valider' => 'b-warn', 'termine' => 'b-ok', 'bloque' => 'b-ko', default => 'b-gris' };
    $DOM = \App\Services\JournalActivite::DOMAINES;
@endphp

<div class="fc-entete">
    <h2>{!! $ic('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>', 20) !!} Fiche collaborateur</h2>
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('observations.index') }}" class="btn sec sm">{!! $ic('<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>', 14) !!} Retour</a>
</div>

<div class="panneau">
    <form method="GET" action="{{ route('observations.fiche-collaborateur') }}" id="fc-form">
        <div class="fc-filtre">
            <div class="champ">
                <label>Collaborateur</label>
                <select name="collaborateur_id" required onchange="this.form.submit()">
                    <option value="">Sélectionner…</option>
                    <optgroup label="En activité">
                    @foreach($collaborateurs->where('actif', true) as $c)
                    <option value="{{ $c->id }}" @selected(request('collaborateur_id') == $c->id)>{{ $c->nomComplet() }} · {{ $c->roleLibelle() }}</option>
                    @endforeach
                    </optgroup>
                    @if($collaborateurs->where('actif', false)->isNotEmpty())
                    <optgroup label="Anciens collaborateurs">
                    @foreach($collaborateurs->where('actif', false) as $c)
                    <option value="{{ $c->id }}" @selected(request('collaborateur_id') == $c->id)>{{ $c->nomComplet() }} · {{ $c->roleLibelle() }}</option>
                    @endforeach
                    </optgroup>
                    @endif
                </select>
            </div>
            <div class="champ"><label>Du</label><input type="date" name="debut" value="{{ $debut }}"></div>
            <div class="champ"><label>Au</label><input type="date" name="fin" value="{{ $fin }}"></div>
            <input type="hidden" name="onglet" value="{{ request('onglet', 'synthese') }}" id="fc-onglet">
            <button class="btn" style="height:36px">Consulter</button>
        </div>
        @php
            $auj = now();
            $presets = [
                'Ce mois' => [$auj->copy()->startOfMonth(), $auj],
                'Mois précédent' => [$auj->copy()->subMonthNoOverflow()->startOfMonth(), $auj->copy()->subMonthNoOverflow()->endOfMonth()],
                '3 derniers mois' => [$auj->copy()->subMonthsNoOverflow(3)->addDay(), $auj],
                'Cette année' => [$auj->copy()->startOfYear(), $auj],
                '12 derniers mois' => [$auj->copy()->subYear()->addDay(), $auj],
            ];
        @endphp
        <div class="raccourcis">
            @foreach($presets as $lib => [$d, $f])
            <button type="button" onclick="fcPeriode('{{ $d->toDateString() }}','{{ $f->toDateString() }}')">{{ $lib }}</button>
            @endforeach
        </div>
    </form>
</div>

@if($collab && $data)
@php
    $s = $data['stats'];
    $journal = $data['journal'];
    $scoreGlobal = $data['scoreGlobal'];
    $couleur = fn($v) => $v >= 80 ? 'var(--ok)' : ($v >= 50 ? '#b45309' : 'var(--ko)');
    $obsToutes = $data['observations']->map(fn($o) => [$o, 'Responsable'])
        ->concat($data['observationsSuivies']->reject(fn($o) => $data['observations']->contains('id', $o->id))->map(fn($o) => [$o, 'Suivi délégué']));
    $p = $data['parcData'];
    $parDomaine = $journal->countBy('domaine');
    $maxDom = max(1, $parDomaine->max() ?? 1);
    $ongletsListe = [
        'synthese' => ['Synthèse', null],
        'journal' => ["Journal d'activité", $journal->count()],
        'taches' => ['Tâches', $data['taches']->count()],
        'objectifs' => ['Objectifs', $data['objectifs']->count()],
        'observations' => ['Observations', $obsToutes->count()],
        'presence' => ['Présence & réunions', $data['pointages']->count() + $data['reunions']->count()],
    ];
    if ($p) $ongletsListe['parc'] = ['Parc auto', $p['actions']->count()];
@endphp

{{-- Identité --}}
<div class="panneau fc-id">
    <div class="fc-avatar">{{ mb_strtoupper(mb_substr($collab->prenom ?: $collab->name, 0, 1) . mb_substr($collab->name, 0, 1)) }}</div>
    <div>
        <h3>{{ $collab->nomComplet() }}</h3>
        <div class="sous">{{ $collab->roleLibelle() }} · {{ $collab->email }}</div>
        <div class="etiquettes">
            @if(!$collab->actif)<span class="etq ancien">Ancien collaborateur</span>@endif
            @if($collab->estPilote())<span class="etq pilote">Pilote</span>@endif
            @if($p && $p['delegue'])<span class="etq parc">Gestionnaire du parc auto</span>@endif
            @foreach($data['projets'] as $pr)
            <span class="etq projet {{ $pr->responsable_projet_id === $collab->id ? 'resp' : '' }}" title="{{ $pr->libelle }}{{ $pr->responsable_projet_id === $collab->id ? ' (responsable du projet)' : '' }}">{{ $pr->code }}</span>
            @endforeach
        </div>
    </div>
    <div class="fc-score">
        <div class="anneau" style="--v:{{ $scoreGlobal ?? 0 }};--c:{{ $couleur($scoreGlobal ?? 0) }}" title="Score pondéré des composantes applicables sur la période"><span>{{ $scoreGlobal !== null ? $scoreGlobal . '%' : '—' }}<small>Score</small></span></div>
        @if(auth()->user()->estPilote())
        <button class="btn sm" onclick="document.getElementById('dlg-notif').showModal()">{!! $ic('<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>', 14) !!} Notifier</button>
        @endif
    </div>
</div>

<nav class="onglets" id="fc-onglets">
    @foreach($ongletsListe as $cle => [$lib, $n])
    <button type="button" data-onglet="{{ $cle }}">{{ $lib }}@if($n !== null)<span class="c">{{ $n }}</span>@endif</button>
    @endforeach
</nav>

{{-- ============ Synthèse ============ --}}
<section class="vue" data-vue="synthese">
    <div class="kpis">
        <div class="kpi"><div class="val">{{ $s['audit_actions'] }}</div><div class="lbl">Actions tracées</div><div class="aide">sur {{ $s['jours_actifs'] }} jour(s) d'activité</div></div>
        <div class="kpi"><div class="val">{{ $s['taches_terminees'] }}/{{ $s['taches_total'] }}</div><div class="lbl">Tâches terminées</div><div class="aide">{{ $s['taux_completion'] }} % · {{ $s['productivite'] }}/jour ouvré</div></div>
        <div class="kpi"><div class="val" style="color:{{ $s['taches_retard'] ? 'var(--ko)' : 'var(--bleu)' }}">{{ $s['taches_retard'] }}</div><div class="lbl">Tâches en retard</div></div>
        <div class="kpi"><div class="val">{{ round($s['obj_score']) }} %</div><div class="lbl">Score OKR</div><div class="aide">{{ $s['kr_atteints'] }}/{{ $s['kr_total'] }} résultats clés atteints</div></div>
        <div class="kpi"><div class="val">{{ $s['obs_resolus'] }}/{{ $s['obs_total'] }}</div><div class="lbl">Observations résolues</div></div>
        <div class="kpi"><div class="val">{{ $s['jours_presents'] }}</div><div class="lbl">Jours de présence</div><div class="aide">{{ $s['retards'] }} retard(s) · {{ $s['jours_absents'] }} absence(s)</div></div>
        @if($p)<div class="kpi"><div class="val" style="color:#0f766e">{{ $p['actions']->count() }}</div><div class="lbl">Actions parc auto</div><div class="aide">sur {{ $p['jours'] }} jour(s)</div></div>@endif
        <div class="kpi"><div class="val">{{ $s['connexions'] }}</div><div class="lbl">Connexions</div></div>
    </div>

    <div class="deux">
        <div class="panneau">
            <h3>Activité par domaine <span class="nb">{{ \Carbon\Carbon::parse($debut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($fin)->format('d/m/Y') }}</span></h3>
            @forelse(collect($DOM)->filter(fn($d, $k) => $parDomaine->has($k)) as $k => $d)
            <div class="barre-ligne">
                <span>{{ $d['libelle'] }}</span>
                <span class="piste"><i style="width:{{ round($parDomaine[$k] / $maxDom * 100) }}%;background:{{ $d['couleur'] }}"></i></span>
                <span class="n">{{ $parDomaine[$k] }}</span>
            </div>
            @empty
            <p class="vide">Aucune action tracée sur cette période.</p>
            @endforelse
        </div>
        <div class="panneau">
            <h3>Composantes du score <span class="nb">pondérées · sans objet = écartée</span></h3>
            @foreach($data['scoreComposantes'] as $comp)
            <div class="composante" @if($comp['na']) style="opacity:.55" title="Rien à évaluer sur la période : composante écartée du score" @endif>
                <span>{{ $comp['label'] }} <span class="attenue">({{ $comp['poids'] }} %)</span></span>
                <span class="barre-ligne" style="display:block;margin:0"><span class="piste" style="display:block"><i style="width:{{ $comp['na'] ? 0 : $comp['score'] }}%;background:{{ $couleur($comp['score']) }}"></i></span></span>
                @if($comp['na'])<span class="n attenue" style="text-align:right">sans objet</span>@else<span class="n" style="text-align:right;font-weight:600;color:{{ $couleur($comp['score']) }}">{{ $comp['score'] }} %</span>@endif
            </div>
            @endforeach
        </div>
    </div>

    <div class="panneau">
        <h3>Dernières actions <span class="nb"><a href="#" onclick="fcOnglet('journal');return false" style="color:var(--accent)">Tout le journal →</a></span></h3>
        @include('observations._journal', ['lignes' => $journal->where('domaine', '!=', 'connexion')->take(8), 'filtres' => false, 'idJournal' => 'jr-apercu'])
    </div>
</section>

{{-- ============ Journal ============ --}}
<section class="vue" data-vue="journal">
    <div class="panneau">
        @include('observations._journal', ['lignes' => $journal, 'filtres' => true, 'idJournal' => 'jr-complet'])
    </div>
</section>

{{-- ============ Tâches ============ --}}
<section class="vue" data-vue="taches">
    <div class="panneau" style="overflow-x:auto">
        <h3>{!! $ic('<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>') !!} Tâches de la période <span class="nb">débutées, à échéance ou achevées sur la période</span></h3>
        @if($data['taches']->isEmpty())
        <p class="vide">Aucune tâche sur cette période.</p>
        @else
        <table class="mini-table">
            <thead><tr><th>Intitulé</th><th>Projet</th><th>Début</th><th>Échéance</th><th>Achevée le</th><th>Statut</th></tr></thead>
            <tbody>
            @foreach($data['taches'] as $t)
            @php $retard = $t->estEnRetard() || ($t->date_fin_reelle && $t->echeance && $t->date_fin_reelle->gt($t->echeance)); @endphp
            <tr>
                <td>{{ $t->intitule }}@if($t->trashed()) <span class="badge b-gris">supprimée</span>@endif</td>
                <td style="white-space:nowrap"><span class="code">{{ $t->projet?->code ?? '—' }}</span>@if($t->sprint) <span class="attenue">· S{{ $t->sprint->numero }}</span>@endif</td>
                <td class="attenue" style="white-space:nowrap">{{ $t->date_debut?->format('d/m/Y') ?? '—' }}</td>
                <td style="white-space:nowrap;{{ $retard ? 'color:var(--ko);font-weight:600' : '' }}">{{ $t->echeance?->format('d/m/Y') ?? '—' }}</td>
                <td class="attenue" style="white-space:nowrap">{{ $t->date_fin_reelle?->format('d/m/Y') ?? '—' }}</td>
                <td><span class="badge {{ $badgeStatut($t->statut) }}">{{ $t->labelStatut() }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</section>

{{-- ============ Objectifs ============ --}}
<section class="vue" data-vue="objectifs">
    <div class="panneau" style="overflow-x:auto">
        <h3>{!! $ic('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>') !!} Objectifs <span class="nb">score pondéré {{ round($s['obj_score']) }} %</span></h3>
        @if($data['objectifs']->isEmpty())
        <p class="vide">Aucun objectif sur cette période.</p>
        @else
        <table class="mini-table">
            <thead><tr><th>Libellé</th><th>Projet</th><th>Échéance</th><th style="text-align:center">Pondération</th><th style="text-align:center">Résultats clés</th><th>Verdict</th></tr></thead>
            <tbody>
            @foreach($data['objectifs'] as $obj)
            @php
                $verdict = $obj->evaluerAuto();
                [$bv, $lv] = match($verdict) { 'atteint' => ['b-ok', 'Atteint'], 'partiel' => ['b-warn', 'Partiel'], 'non_atteint' => ['b-ko', 'Non atteint'], default => ['b-gris', 'En cours'] };
                $krCount = $obj->resultats->count();
            @endphp
            <tr>
                <td>{{ $obj->libelle }}</td>
                <td><span class="code">{{ $obj->projet?->code ?? '—' }}</span></td>
                <td class="attenue" style="white-space:nowrap">{{ $obj->date_echeance?->format('d/m/Y') ?? '—' }}</td>
                <td style="text-align:center;font-weight:600">{{ $obj->ponderation }} %</td>
                <td style="text-align:center">{{ $krCount ? $obj->resultats->where('valide', true)->count() . '/' . $krCount : '—' }}</td>
                <td><span class="badge {{ $bv }}">{{ $lv }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</section>

{{-- ============ Observations ============ --}}
<section class="vue" data-vue="observations">
    <div class="panneau" style="overflow-x:auto">
        <h3>{!! $ic('<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/>') !!} Observations <span class="nb">confiées ou dont le suivi est délégué</span></h3>
        @if($obsToutes->isEmpty())
        <p class="vide">Aucune observation sur cette période.</p>
        @else
        <table class="mini-table">
            <thead><tr><th>Code</th><th>Reçue le</th><th>Projet</th><th>Description</th><th>Rôle</th><th>Statut</th></tr></thead>
            <tbody>
            @foreach($obsToutes as [$obs, $role])
            <tr>
                <td class="code">{{ $obs->code }}</td>
                <td class="attenue" style="white-space:nowrap">{{ $obs->date_reception ? \Carbon\Carbon::parse($obs->date_reception)->format('d/m/Y') : '—' }}</td>
                <td><span class="code">{{ $obs->projet?->code ?? '—' }}</span></td>
                <td>{{ \Illuminate\Support\Str::limit($obs->description, 160) }}</td>
                <td class="attenue">{{ $role }}</td>
                <td><span class="badge {{ match($obs->statut) { 'resolu' => 'b-ok', 'en_cours' => 'b-warn', 'bloque' => 'b-ko', default => 'b-info' } }}">{{ $obs->statutLibelle() }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</section>

{{-- ============ Présence & réunions ============ --}}
<section class="vue" data-vue="presence">
    <div class="deux">
        <div class="panneau" style="overflow-x:auto">
            <h3>{!! $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>') !!} Pointage <span class="nb">{{ $s['jours_presents'] }} présent(s) · {{ $s['retards'] }} retard(s) · {{ $s['jours_absents'] }} absence(s)</span></h3>
            @if($data['pointages']->isEmpty())
            <p class="vide">Aucun pointage sur cette période.</p>
            @else
            <table class="mini-table">
                <thead><tr><th>Date</th><th>Arrivée</th><th>Départ</th><th>Statut</th></tr></thead>
                <tbody>
                @foreach($data['pointages'] as $pt)
                <tr>
                    <td style="white-space:nowrap">{{ \Carbon\Carbon::parse($pt->date)->translatedFormat('D d/m/Y') }}</td>
                    <td>{{ $pt->heure_arrivee ? \Carbon\Carbon::parse($pt->heure_arrivee)->format('H:i') : '—' }}</td>
                    <td>{{ $pt->heure_depart ? \Carbon\Carbon::parse($pt->heure_depart)->format('H:i') : '—' }}</td>
                    <td><span class="badge {{ match($pt->statut) { 'present', 'sorti_tot' => 'b-ok', 'retard' => 'b-warn', 'absent' => 'b-ko', default => 'b-gris' } }}">{{ ucfirst(str_replace('_', ' ', $pt->statut)) }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
        <div class="panneau" style="overflow-x:auto">
            <h3>{!! $ic('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!} Réunions <span class="nb">{{ $s['reunions_present'] }}/{{ $s['reunions_total'] }} honorée(s)</span></h3>
            @if($data['reunions']->isEmpty())
            <p class="vide">Aucune réunion sur cette période.</p>
            @else
            <table class="mini-table">
                <thead><tr><th>Date</th><th>Réunion</th><th>Rôle</th><th>Statut</th></tr></thead>
                <tbody>
                @foreach($data['reunions'] as $reu)
                <tr>
                    <td style="white-space:nowrap">{{ $reu->date_reunion->format('d/m/Y') }}</td>
                    <td>{{ $reu->typeReunion?->libelle ?? '—' }}</td>
                    <td class="attenue">{{ $reu->role?->libelle ?? '—' }}</td>
                    <td><span class="badge {{ match($reu->statut) { 'present' => 'b-ok', 'absent' => 'b-ko', 'remplace' => 'b-warn', default => 'b-info' } }}">{{ $reu->statutLabel() }}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</section>

{{-- ============ Parc auto ============ --}}
@if($p)
<section class="vue" data-vue="parc">
    @if($p['actions']->isEmpty())
    <div class="panneau"><p class="vide">Aucune action sur le parc auto tracée sur cette période{{ $p['delegue'] ? ', bien que ce collaborateur soit gestionnaire du parc' : '' }}.</p></div>
    @else
    <div class="kpis">
        <div class="kpi"><div class="val" style="color:#0f766e">{{ $p['actions']->count() }}</div><div class="lbl">Actions sur le parc</div></div>
        <div class="kpi"><div class="val">{{ $p['jours'] }}</div><div class="lbl">Jours d'activité</div></div>
        @foreach($p['par_module']->take(4) as $m)
        <div class="kpi"><div class="val">{{ $m['n'] }}</div><div class="lbl">{{ $m['libelle'] }}</div></div>
        @endforeach
    </div>
    <div class="panneau" style="overflow-x:auto">
        <h3>{!! $ic('<rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>') !!} Répartition des actions</h3>
        <table class="mini-table">
            <thead><tr><th>Objet</th><th style="text-align:center">Actions</th><th>Détail</th></tr></thead>
            <tbody>
            @foreach($p['par_module'] as $m)
            <tr><td style="font-weight:600">{{ $m['libelle'] }}</td><td style="text-align:center">{{ $m['n'] }}</td><td class="attenue">{{ $m['detail'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="panneau">
        <h3>Journal du parc auto</h3>
        @include('observations._journal', ['lignes' => $p['actions'], 'filtres' => false, 'idJournal' => 'jr-parc', 'recherche' => true])
    </div>
    @endif
</section>
@endif

<script>
function fcOnglet(cle){
    const btn = document.querySelector('#fc-onglets [data-onglet="'+cle+'"]') || document.querySelector('#fc-onglets [data-onglet]');
    cle = btn.dataset.onglet;
    document.querySelectorAll('#fc-onglets [data-onglet]').forEach(b => b.classList.toggle('actif', b === btn));
    document.querySelectorAll('.vue[data-vue]').forEach(v => v.classList.toggle('actif', v.dataset.vue === cle));
    document.getElementById('fc-onglet').value = cle;
    const u = new URL(location.href); u.searchParams.set('onglet', cle); history.replaceState(null, '', u);
}
document.querySelectorAll('#fc-onglets [data-onglet]').forEach(b => b.addEventListener('click', () => fcOnglet(b.dataset.onglet)));
fcOnglet(@json(request('onglet', 'synthese')));
</script>
@endif

<script>
function fcPeriode(du, au){
    const f = document.getElementById('fc-form');
    f.debut.value = du; f.fin.value = au;
    if (f.collaborateur_id.value) f.submit();
}
</script>

{{-- Dialog Notification collaborateur --}}
@if(auth()->user()->estPilote() && isset($collab) && $collab)
<dialog id="dlg-notif" style="width:95%;max-width:560px;border:none;border-radius:14px;padding:0;box-shadow:0 12px 40px rgba(0,0,0,.2)">
<div style="padding:20px 24px">
    <h3 style="margin:0 0 14px;display:flex;align-items:center;gap:6px;font-size:15px;color:var(--bleu)">{!! $ic('<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>') !!} Notifier {{ $collab->prenom ?? $collab->name }}</h3>
    <form method="POST" action="{{ route('notifications.envoyer') }}" id="form-notif" onsubmit="return prepNotif()">
        @csrf
        <input type="hidden" name="user_id" value="{{ $collab->id }}">
        <input type="hidden" name="contenu" id="notif-contenu-hidden">
        <div style="margin-bottom:10px">
            <label style="font-size:12px;font-weight:600;color:var(--gris);display:block;margin-bottom:3px">Sujet <span style="color:var(--ko)">*</span></label>
            <input type="text" name="sujet" required maxlength="200" placeholder="Objet du message" style="width:100%;padding:8px 12px;border:1px solid var(--bord);border-radius:8px;font-size:13px;box-sizing:border-box">
        </div>
        <div style="margin-bottom:10px">
            <label style="font-size:12px;font-weight:600;color:var(--gris);display:block;margin-bottom:3px">Canal</label>
            <select name="canal" style="padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px">
                <option value="in_app">In-app uniquement</option>
                <option value="email">Email uniquement</option>
                <option value="les_deux" selected>In-app + Email</option>
            </select>
        </div>
        <div style="margin-bottom:10px">
            <label style="font-size:12px;font-weight:600;color:var(--gris);display:block;margin-bottom:3px">Message <span style="color:var(--ko)">*</span></label>
            <div class="rte-toolbar">
                <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('bold')" title="Gras"><strong>G</strong></button>
                <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('underline')" title="Souligne"><u>S</u></button>
                <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('italic')" title="Italique"><em>I</em></button>
                <span style="width:1px;background:var(--bord);margin:2px 4px"></span>
                <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('insertUnorderedList')" title="Liste a puces">{!! $ic('<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>', 14) !!}</button>
                <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('insertOrderedList')" title="Liste numerotee">{!! $ic('<line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4M4 10h2M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/>',14) !!}</button>
            </div>
            <div contenteditable="true" id="notif-contenu" style="min-height:120px;max-height:250px;overflow-y:auto;padding:10px 12px;border:1px solid var(--bord);border-top:none;border-radius:0 0 8px 8px;font-size:13px;line-height:1.6;outline:none" placeholder="Votre message..."></div>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:14px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Envoyer</button>
        </div>
    </form>
</div>
</dialog>
<style>
.rte-toolbar{display:flex;gap:2px;padding:4px 6px;background:#f8fafc;border:1px solid var(--bord);border-bottom:none;border-radius:8px 8px 0 0}
.rte-toolbar button{background:none;border:1px solid transparent;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:13px;color:var(--bleu)}
.rte-toolbar button:hover{background:#e2e8f0;border-color:var(--bord)}
#notif-contenu:empty:before{content:attr(placeholder);color:#94a3b8;pointer-events:none}
#notif-contenu ul,#notif-contenu ol{margin:4px 0;padding-left:20px}
</style>
<script>
function rteCmd(c){document.execCommand(c,false,null);document.getElementById('notif-contenu').focus()}
function prepNotif(){var c=document.getElementById('notif-contenu').innerHTML.trim();if(!c||c==='<br>'){alert('Veuillez saisir un message.');return false}document.getElementById('notif-contenu-hidden').value=c;return true}
</script>
 @endif
@endsection
