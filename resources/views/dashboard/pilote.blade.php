@extends('layouts.app')
@section('sousTitre', 'Vue pilotage')
@section('titre', 'Tableau de bord - Pilotage')
@include('dashboard._theme')
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
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:8px}
.alerte.warn{background:#fff7ed;border:1px solid #fed7aa;color:#b45309}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.alerte.info{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8}
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
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
@media(max-width:900px){.grille{grid-template-columns:1fr}.kpis{grid-template-columns:repeat(2,1fr)!important}.mini-table{display:block;overflow-x:auto}}
@media(max-width:600px){.kpis{grid-template-columns:1fr!important}.kpi .val{font-size:22px}.kpi{padding:10px}.panneau{padding:10px}.membre-line{gap:8px}}
</style>
@endpush
@section('contenu')
<h2>Bonjour, {{ auth()->user()->prenom ?? auth()->user()->name }}</h2>
<div class="crumb">{{ now()->translatedFormat('l j F Y') }} - Pilotage des op&eacute;rations</div>

{{-- Bannière pointage (masquée les jours non ouvrés) --}}
@if(($pointageActif ?? true) && isset($monPointage))
@php $dejaArrive = $monPointage && $monPointage->heure_arrivee; $dejaParti = $monPointage && $monPointage->heure_depart; @endphp
<div style="display:flex;align-items:center;justify-content:space-between;background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:12px 18px;margin-bottom:14px;gap:12px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:10px">
        <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#eff6ff);display:flex;align-items:center;justify-content:center">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bleu)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div style="font-size:13px;color:var(--gris)">
            @if(!$dejaArrive)
                <strong style="color:var(--bleu);display:block">Pointage</strong>Vous n'avez pas encore pointé
            @elseif(!$dejaParti)
                <strong style="color:var(--ok);display:block">En poste depuis {{ \Carbon\Carbon::parse($monPointage->heure_arrivee)->format('H:i') }}</strong>Bonne journée !
            @else
                <strong style="color:var(--bleu);display:block">Journée terminée</strong>{{ \Carbon\Carbon::parse($monPointage->heure_arrivee)->format('H:i') }} - {{ \Carbon\Carbon::parse($monPointage->heure_depart)->format('H:i') }} ({{ $monPointage->dureeFormatee() }})
            @endif
        </div>
    </div>
    <div style="display:flex;gap:8px">
        @if(!$dejaArrive)
            <form method="POST" action="{{ route('pointage.arrivee') }}">@csrf<button style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:#f0fdf4;color:#15803d;border:1.5px solid #bbf7d0;transition:all .2s"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Pointer mon arrivée</button></form>
        @elseif(!$dejaParti)
            <form method="POST" action="{{ route('pointage.depart') }}">@csrf<button style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:#fffbeb;color:#92400e;border:1.5px solid #fde68a;transition:all .2s"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> Pointer mon départ</button></form>
        @endif
    </div>
</div>
@elseif(($pointageActif ?? true) && \Illuminate\Support\Facades\Schema::hasTable('pointages'))
<div style="display:flex;align-items:center;justify-content:space-between;background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:12px 18px;margin-bottom:14px;gap:12px;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:10px">
        <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#eff6ff);display:flex;align-items:center;justify-content:center">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bleu)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div style="font-size:13px;color:var(--gris)"><strong style="color:var(--bleu);display:block">Pointage</strong>Vous n'avez pas encore pointé</div>
    </div>
    <form method="POST" action="{{ route('pointage.arrivee') }}">@csrf<button style="display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:#f0fdf4;color:#15803d;border:1.5px solid #bbf7d0;transition:all .2s"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg> Pointer mon arrivée</button></form>
</div>
@endif

{{-- Alertes --}}
@include('dashboard._attention', ['alertes' => $alertes])

{{-- Indicateurs clés --}}
<div class="kpis kpi-v2">
    <div class="kpi {{ $enRetard ? 'signal-ko' : '' }}"><x-voyant :niveau="$enRetard ? 'retard' : 'ok'" :titre="$enRetard ? $enRetard . ' tâche(s) en retard' : 'Aucun retard'" :lien="route('taches.pilote')" lien-texte="Voir le détail" /><div class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>En retard</div><div class="val">{{ $enRetard }}</div></div>
    <div class="kpi {{ $bloquees ? 'signal-ko' : '' }}"><x-voyant :niveau="$bloquees ? 'bloque' : 'ok'" :titre="$bloquees ? $bloquees . ' tâche(s) bloquée(s)' : 'Aucun blocage'" :lien="route('taches.pilote', ['statut' => 'bloque'])" lien-texte="Voir le détail" /><div class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Bloquées</div><div class="val">{{ $bloquees }}</div></div>
    <div class="kpi {{ $aValider->count() ? 'signal-info' : '' }}"><x-voyant :niveau="$aValider->count() ? 'semaine' : 'ok'" :titre="$aValider->count() ? $aValider->count() . ' tâche(s) à valider' : 'Rien à valider'" :lien="route('taches.pilote')" lien-texte="Voir le détail" /><div class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>À valider</div><div class="val">{{ $aValider->count() }}</div></div>
    <div class="kpi {{ $sansSaisieHier ? 'signal-warn' : '' }}"><x-voyant :niveau="$sansSaisieHier ? 'proche' : 'ok'" :titre="$sansSaisieHier ? $sansSaisieHier . ' collaborateur(s) sans saisie hier' : 'Toutes les saisies sont faites'" :lien="route('saisie.index')" lien-texte="Voir le détail" /><div class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Sans saisie hier</div><div class="val">{{ $sansSaisieHier }}</div><div class="sub"><a href="{{ route('saisie.index') }}">Relancer</a></div></div>
    <div class="kpi signal-ok"><div class="lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Complétion</div><div class="val">{{ $tauxGlobal }} %</div><div class="barre-k"><i style="width:{{ $tauxGlobal }}%"></i></div></div>
</div>

{{-- Tâches à valider --}}
@if($aValider->count() > 0)
<div class="panneau" style="margin-bottom:20px;border:1px solid #93c5fd;background:linear-gradient(135deg,#eff6ff,#fff)">
    <h3 style="color:#1d4ed8">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        T&acirc;ches &agrave; valider ({{ $aValider->count() }})
    </h3>
    <table class="mini-table" data-collapse-table="5">
        <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>Sprint</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($aValider as $t)
        <tr>
            <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 35) }}</a></td>
            <td>{{ $t->user->nomComplet() }}</td>
            <td style="font-size:11px;color:var(--gris)">{{ $t->sprint ? 'Sprint ' . $t->sprint->numero : '—' }}</td>
            <td>
                <a href="{{ route('taches.show', $t) }}" class="lien">Examiner &rarr;</a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Tendance + Performance équipe --}}
<div class="grille">
    <div class="panneau panneau-tendance">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Tendance (4 semaines)
        </h3>
        @include('dashboard._tendance', ['series' => $tendanceHebdoGlobal, 'max' => $tendanceMaxGlobal, 'legende' => 'Tâches terminées par semaine (toute l\'équipe)'])
    </div>
    @include('dashboard._performance')
</div>

{{-- Sprints en cours + Livraison des sprints terminés --}}
<div class="grille">
    <div class="panneau" @if($sprintsTermines->isEmpty()) style="grid-column:1/-1" @endif>
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
            Sprints en cours ({{ $sprintsEnCours->count() }})
        </h3>
        @forelse($sprintsEnCours as $s)
        <div class="sprint-item">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                <span style="font-size:13px;font-weight:600;color:var(--bleu)">Sprint {{ $s->numero }} - {{ $s->projet->libelle ?? '' }}</span>
                @if($s->jours_restants !== null)
                <span style="font-size:11px;font-weight:600;color:#475569;white-space:nowrap">{{ $s->urgence()['court'] }}</span>
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

    @if($sprintsTermines->count() > 0)
    <div class="panneau">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Livraison sprints terminés
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

{{-- Tâches bloquées + en retard --}}
<div class="grille">
    <div class="panneau">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            T&acirc;ches bloqu&eacute;es ({{ $topBloquees->count() }})
        </h3>
        <table class="mini-table" data-collapse-table="5">
            <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>Projet</th></tr></thead>
            <tbody>
            @forelse($topBloquees as $t)
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 35) }}</a></td>
                <td>{{ $t->user->nomComplet() }}</td>
                <td style="font-size:11px;color:var(--gris)">{{ $t->projet->libelle ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="color:var(--gris);text-align:center;padding:14px">Aucune</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="panneau">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            T&acirc;ches en retard ({{ $tachesEnRetard->count() }})
        </h3>
        <table class="mini-table" data-collapse-table="5">
            <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>&Eacute;ch&eacute;ance</th><th>Retard</th></tr></thead>
            <tbody>
            @forelse($tachesEnRetard as $t)
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 30) }}</a></td>
                <td>{{ $t->user->nomComplet() }}</td>
                <td style="font-size:11px;color:var(--gris)">{{ $t->echeance->format('d/m') }}</td>
                <td style="white-space:nowrap"><x-voyant :tache="$t" texte /></td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:var(--gris);text-align:center;padding:14px">Aucune</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Mes tâches actives --}}
@if($mesTachesActives->count() > 0)
<div class="panneau" style="margin-bottom:20px">
    <h3>
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="4"/><polyline points="8 12 11 15 16 9"/></svg>
        Mes t&acirc;ches actives ({{ $mesTachesActives->count() }})
    </h3>
    <table class="mini-table" data-collapse-table="5">
        <thead><tr><th>T&acirc;che</th><th>Projet</th><th>Priorit&eacute;</th><th>D&eacute;but</th><th>&Eacute;ch&eacute;ance</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach($mesTachesActives as $t)
        @php
            $statutCls = match($t->statut) { 'en_cours' => 'b-ok', 'bloque' => 'b-ko', default => 'b-warn' };
            $prioCls = match($t->priorite) { 'haute' => 'color:var(--ko);font-weight:700', 'basse' => 'color:var(--gris)', default => 'color:var(--accent);font-weight:600' };
        @endphp
        <tr>
            <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 40) }}</a></td>
            <td style="font-size:11px;color:var(--gris)">{{ $t->projet->libelle ?? '—' }}</td>
            <td style="{{ $prioCls }};font-size:12px">{{ ucfirst($t->priorite) }}</td>
            <td style="font-size:11px;color:var(--gris)">{{ $t->date_debut?->format('d/m') ?? '—' }}</td>
            <td style="font-size:11px;color:var(--gris);white-space:nowrap"><x-voyant :tache="$t" /> {{ $t->echeance?->format('d/m') ?? '-' }}</td>
            <td><span class="badge {{ $statutCls }}">{{ $t->labelStatut() }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    <div style="margin-top:10px"><a href="{{ route('taches.index') }}" class="lien">Voir toutes mes activit&eacute;s &rarr;</a></div>
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

<div style="margin-top:16px;display:flex;gap:10px">
    <a href="{{ route('reporting.index') }}" class="btn">Voir le Reporting complet</a>
    <a href="{{ route('sprints.index') }}" class="btn" style="background:#fff;color:var(--accent);border:1px solid var(--accent)">Projets &amp; Sprints</a>
</div>
@endsection
