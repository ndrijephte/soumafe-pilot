@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Sprint ' . $sprint->numero . ' - ' . $sprint->projet->libelle)
@push('styles')
<style>
.sprint-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:18px}
.sprint-meta{font-size:13px;color:var(--gris);line-height:1.8}
.sprint-meta strong{color:#1e293b}
.badge-resp{display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#dbeafe,#eff6ff);border:1px solid #93c5fd;color:#1d4ed8;padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600}
.badge-resp svg{flex:none}
.badge-statut{padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600}
.s-planifie{background:#e2e8f0;color:#475569}.s-en_cours{background:#dbeafe;color:#1d4ed8}.s-termine{background:#dcfce7;color:var(--ok)}
.av-sp{background:#fff;border:1px solid var(--bord);border-radius:12px;padding:12px 16px;margin-bottom:16px}
.av-sp .ligne{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.av-sp .pct{display:flex;align-items:baseline;gap:8px;min-width:150px}
.av-sp .pct b{font-size:22px;font-weight:800;color:#1f4e79;line-height:1}
.av-sp .pct span{font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
.av-sp .barre{flex:1;min-width:160px;height:6px;background:#e9eef5;border-radius:6px;overflow:hidden;display:flex}
.av-sp .barre i{display:block;height:100%}
.av-sp .barre .t{background:#2e74b5}.av-sp .barre .c{background:#93c5fd}.av-sp .barre .b{background:#f87171}
.av-sp .chiffres{display:flex;gap:4px;flex-wrap:wrap}
.av-sp .chiffres span{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:999px;background:#f8fafc;border:1px solid #edf1f6;font-size:11.5px;color:#64748b;white-space:nowrap}
.av-sp .chiffres b{color:#1e293b;font-weight:700}
.av-sp .chiffres i{width:7px;height:7px;border-radius:50%}
.av-sp .actions{display:flex;gap:4px;margin-left:auto}
.av-sp .ico-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:1px solid transparent;background:none;color:#64748b;cursor:pointer;transition:all .15s}
.av-sp .ico-btn:hover{background:#eef4fb;color:#2e74b5;border-color:#d6e4f3}
.av-sp .ico-btn.ko:hover{background:#fef2f2;color:#dc2626;border-color:#fecaca}
.av-sp .ico-btn svg{width:15px;height:15px}
.periode-ex{display:flex;gap:10px;align-items:center;flex-wrap:wrap;font-size:12px;color:#64748b;border-top:1px solid #f1f5f9;padding-top:8px;margin-top:10px}
.periode-ex strong{color:var(--bleu)}
.periode-ex .arb{font-size:11.5px;color:var(--gris)}
.periode-ex.a-arbitrer{color:#92400e}
.periode-ex.a-arbitrer strong{color:#92400e}
.taches-table{width:100%;border-collapse:collapse;font-size:12.5px;table-layout:fixed}
.taches-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;vertical-align:middle}
.taches-table td{padding:7px 10px;border-bottom:1px solid var(--bord);vertical-align:middle}
.taches-table tbody tr[data-row]:hover td{background:#f8fafc}
.rattacher{display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:8px 12px;margin-bottom:10px;background:#f8fafc;border:1px solid var(--bord);border-radius:10px;font-size:12.5px;color:#475569}
.rattacher select{flex:1 1 280px;min-width:0;max-width:640px;padding:5px 8px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.rattacher .btn:disabled{opacity:.5;cursor:not-allowed}
.t-coche,.t-tout{width:15px;height:15px;accent-color:#2e74b5;cursor:pointer;vertical-align:middle}
.taches-table th.col-duree,.taches-table th.col-pts,.taches-table th.col-edit{text-align:center}
.col-coche{width:30px}.col-tache{width:30%}.col-assigne{width:12%}.col-prio{width:9%}.col-debut{width:6%}.col-ech{width:6%}.col-duree{width:6%;text-align:center}.col-pts{width:4%;text-align:center}.col-statut{width:13%}.col-edit{width:13%;text-align:center}
.sc{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;white-space:nowrap;max-width:100%;overflow:hidden;text-overflow:ellipsis}
.sc b{display:inline-block;min-width:26px;text-align:center;padding:2px 5px;border-radius:6px;font-size:11px}
.sc-d b{background:#e0f2fe;color:#0369a1}.sc-e b{background:#dbeafe;color:#1d4ed8}.sc-t b{background:#dcfce7;color:#166534}.sc-tt b{background:#166534;color:#fff}
.sc-er b{background:#fee2e2;color:#991b1b}.sc-bloque b{background:#fee2e2;color:#991b1b}.sc-autre b{background:#f1f5f9;color:#64748b}
.sc-legende{display:flex;gap:12px;flex-wrap:wrap;font-size:11.5px;color:var(--gris);margin:-4px 0 10px}
.groupe-us td{background:var(--fond);font-size:11.5px;font-weight:600;color:var(--bleu);padding:7px 10px}
.groupe-us .gu-num{color:var(--accent);margin-right:6px}
.sb-liste{display:grid;gap:8px}
.sb-us{display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid var(--bord);border-radius:10px;padding:10px 12px}
.sb-us .num{font-weight:700;color:var(--accent);font-size:12.5px;white-space:nowrap}
.sb-us .txt{flex:1;min-width:220px;font-size:13px}
.sb-us .mod{display:block;font-size:11px;color:var(--gris)}
.sb-us.modifiee{border-color:#fcd9a8;box-shadow:inset 3px 0 0 #f59e0b}
.sb-us .signal{display:inline-flex;align-items:center;gap:5px;margin-top:4px;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:#fff7ed;color:#9a3412}
.sb-us .signal svg{flex:none}
.sb-us .signal.fige{background:#f1f5f9;color:#475569;font-weight:500}
.sb-us .avance{font-size:11.5px;color:var(--gris);white-space:nowrap}
.sb-us .niv .org{display:none}
.sb-us select{padding:4px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12px}
.sb-pts{display:flex;align-items:center;gap:6px}
.sb-pts select{font-weight:700;color:var(--bleu)}
.cx{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10.5px;font-weight:700;white-space:nowrap}
.cx-simple{background:#dcfce7;color:#166534}.cx-moderee{background:#fef3c7;color:#92400e}.cx-complexe{background:#fee2e2;color:#991b1b}.cx-aucune{background:#f1f5f9;color:#64748b}
.echelle{display:flex;gap:8px;flex-wrap:wrap;font-size:11.5px;color:var(--gris);margin-bottom:10px}
.echelle span b{margin-right:3px}
.gu-pts{float:right;font-size:11.5px}
.eq-liste{display:flex;flex-wrap:wrap;gap:8px}
.eq-membre{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--bord);border-radius:10px;padding:7px 8px 7px 12px;font-size:12.5px}
.eq-membre .nom{font-weight:600;color:#1e293b}
.eq-membre .role{display:block;font-size:11px;color:var(--gris)}
.eq-membre .tch{font-size:11px;color:#475569;background:#f1f5f9;border-radius:8px;padding:1px 7px;white-space:nowrap}
.eq-tag{font-size:10.5px;font-weight:600;border-radius:8px;padding:1px 7px;white-space:nowrap}
.eq-arrive{background:#e0f2fe;color:#0369a1}.eq-hors{background:#fef3c7;color:#92400e}
.eq-retires{margin-top:10px;font-size:12px;color:var(--gris);display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.eq-retires span.r{background:#f1f5f9;border-radius:8px;padding:3px 4px 3px 9px;display:inline-flex;gap:6px;align-items:center}
.eq-ajout{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:12px;padding-top:10px;border-top:1px dashed var(--bord)}
.eq-ajout select{padding:5px 9px;border:1px solid var(--bord);border-radius:7px;font-size:12.5px}
.btn-teste{background:none;border:1px solid var(--bord);border-radius:6px;font-size:10.5px;font-weight:700;padding:2px 6px;cursor:pointer;color:#166534}
.btn-teste.on{background:#166534;color:#fff;border-color:#166534}
.liste-coche{max-height:50vh;overflow-y:auto;border:1px solid var(--bord);border-radius:8px}
.liste-coche label{display:flex!important;gap:8px;align-items:flex-start;padding:8px 10px;margin:0!important;border-bottom:1px solid #f1f5f9;text-transform:none!important;font-weight:400!important;font-size:13px!important;color:#1e293b!important;cursor:pointer}
.liste-coche input{width:auto!important;margin-top:3px}
.pill{padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.p-ok{background:#dcfce7;color:var(--ok)}.p-cours{background:#dbeafe;color:var(--accent)}.p-bloque{background:#fee2e2;color:var(--ko)}.p-attente{background:#fef3c7;color:#b45309}
.prio-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px;vertical-align:middle}
.typ{font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-right:4px}.t1{background:#dbeafe;color:#1d4ed8}
.btn{background:var(--accent);color:#fff;border:none;padding:6px 13px;border-radius:7px;font-size:12.5px;font-weight:500;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-family:inherit}
.btn.sec{background:#fff;color:#1f4e79;border:1px solid #cbd5e1}
.btn.sec:hover{border-color:#93c5fd;background:#f8fbff}
.btn.contour-ok{background:#fff;color:#15803d;border:1px solid #bbf7d0}.btn.contour-warn{background:#fff;color:#b45309;border:1px solid #fde68a}.btn.contour-ko{background:#fff;color:#b91c1c;border:1px solid #fecaca}
.btn.contour-ok:hover{background:#f0fdf4}.btn.contour-warn:hover{background:#fffbeb}.btn.contour-ko:hover{background:#fef2f2}
.btn.sm{padding:5px 12px;font-size:11.5px}
.actions-bar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center}
.actions-bar .sep{flex:1}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:500px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:600;text-transform:uppercase}
dialog input,dialog select{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog input:focus,dialog select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.req{color:var(--ko)}
.vide{text-align:center;padding:40px;color:var(--gris);font-size:14px}
.btn-edit-row{background:none;border:none;cursor:pointer;color:var(--accent);font-size:14px;padding:3px 7px;border-radius:4px;vertical-align:middle;transition:background .15s}
.btn-edit-row:hover{background:#dbeafe}
.btn-edit-sprint{background:none;border:1px solid var(--accent);color:var(--accent);padding:4px 10px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:500;transition:all .15s}
.btn-edit-sprint:hover{background:var(--accent);color:#fff}
.filtre-bar{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center}
.filtre-bar input,.filtre-bar select{padding:5px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.filtre-bar input{min-width:160px}
.tache-nom{display:inline;text-decoration:none;color:#1e293b;font-weight:500;line-height:1.5}
.tache-nom:hover{color:var(--accent)}
@media(max-width:900px){.form-row{grid-template-columns:1fr}.sprint-header{flex-direction:column}.filtre-bar{flex-direction:column}.taches-table{display:block;overflow-x:auto;table-layout:auto}.col-tache,.col-assigne,.col-prio,.col-debut,.col-ech,.col-pts,.col-statut,.col-edit{width:auto}}
@media(max-width:600px){dialog{padding:16px}.btn{padding:6px 12px;font-size:12px}.stats-row{gap:12px;font-size:12px}}
</style>
@endpush
@section('contenu')
@php
    $totalTaches = $sprint->taches->count();
    $terminees = $sprint->taches->where('statut', 'termine')->count();
    $enCours = $sprint->taches->where('statut', 'en_cours')->count();
    $bloquees = $sprint->taches->where('statut', 'bloque')->count();
    $pct = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;
@endphp

<div class="sprint-header">
    <div>
        <h2>Sprint {{ $sprint->numero }} - {{ $sprint->projet->libelle }}</h2>
        <div class="crumb"><a href="{{ route('sprints.index') }}" style="color:var(--accent);text-decoration:none">Projets / Sprints</a> &rsaquo; @if($sprint->projet->estVisiblePour(auth()->user()))<a href="{{ route('projets.espace', $sprint->projet) }}" style="color:var(--accent);text-decoration:none">{{ $sprint->projet->libelle }}</a> &rsaquo; @endif Sprint {{ $sprint->numero }} &rsaquo; {{ $vue === 'planning' ? 'Sprint Planning' : 'Sprint Backlog' }}</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
        <span class="badge-statut s-{{ $sprint->statut }}">{{ ucfirst(str_replace('_',' ',$sprint->statut)) }}</span>
        @if($sprint->projet->responsable)
        <span class="badge-resp">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            {{ $sprint->projet->responsable->nomComplet() }}
        </span>
        @endif
    </div>
</div>

@if($sprint->projet->estVisiblePour(auth()->user()))
@include('scrum._nav', ['sprint' => $sprint, 'actif' => $vue])
@endif

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

@include('scrum._fiche', ['sprint' => $sprint])

@php
    $pT = $totalTaches ? $terminees / $totalTaches * 100 : 0;
    $pC = $totalTaches ? $enCours / $totalTaches * 100 : 0;
    $pB = $totalTaches ? $bloquees / $totalTaches * 100 : 0;
@endphp
<div class="av-sp">
    <div class="ligne">
        <div class="pct" title="Tâches terminées sur l'ensemble des tâches du sprint"><b>{{ $pct }} %</b><span>Avancement</span></div>
        <div class="barre" role="img" aria-label="{{ $terminees }} terminées, {{ $enCours }} en cours, {{ $bloquees }} bloquées sur {{ $totalTaches }} tâches">
            <i class="t" style="width:{{ $pT }}%"></i><i class="c" style="width:{{ $pC }}%"></i><i class="b" style="width:{{ $pB }}%"></i>
        </div>
        <div class="chiffres">
            <span><b>{{ $totalTaches }}</b> tâches</span>
            <span><i style="background:#2e74b5"></i><b>{{ $terminees }}</b> terminées</span>
            <span><i style="background:#93c5fd"></i><b>{{ $enCours }}</b> en cours</span>
            @if($bloquees)<span><i style="background:#f87171"></i><b>{{ $bloquees }}</b> bloquées</span>@endif
            <span title="Story points livrés / engagés"><b>{{ $sprint->pointsLivres() }}</b>/{{ $sprint->pointsEngages() }} pts</span>
        </div>
        @if($canEditSprint)
        <div class="actions">
            @if($sprint->statut !== 'termine')
            <button type="button" class="ico-btn" onclick="document.getElementById('dlg-edit-sprint').showModal()" title="Modifier le sprint" aria-label="Modifier le sprint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
            @endif
            @if($sprint->statut !== 'en_cours')
            <form method="POST" action="{{ route('sprints.destroy', $sprint) }}" style="margin:0" onsubmit="return demanderMotifSuppression(this,'ce sprint')">@csrf @method('DELETE')
                <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                <button type="submit" class="ico-btn ko" title="Supprimer le sprint" aria-label="Supprimer le sprint"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
            </form>
            @endif
        </div>
        @endif
    </div>
    {{-- Période d'exercice (05 → 04) : un sprint à cheval est arbitré par un pilote --}}
    @php $periodeEx = $sprint->periodeExercice(); @endphp
    <div class="periode-ex {{ $periodeEx ? '' : 'a-arbitrer' }}">
        @if($periodeEx)
            <span>P&eacute;riode d'exercice : <strong>{{ \App\Services\PeriodeExercice::nom($periodeEx) }}</strong> ({{ \App\Services\PeriodeExercice::libelle($periodeEx) }})</span>
            @if($sprint->estACheval())<span class="arb">arbitr&eacute;e{{ $sprint->arbitrePar ? ' par ' . $sprint->arbitrePar->nomComplet() : '' }}{{ $sprint->periode_arbitree_le ? ' le ' . $sprint->periode_arbitree_le->format('d/m/Y') : '' }}</span>@endif
        @else
            <span><strong>Sprint &agrave; cheval</strong> sur {{ $sprint->periodesCouvertes()->count() }} p&eacute;riodes d'exercice : arbitrage requis.</span>
        @endif
        @if($sprint->estACheval() && auth()->user()->estAdmin())
        <form method="POST" action="{{ route('sprints.periode', $sprint) }}" style="display:flex;gap:6px;align-items:center;margin:0 0 0 auto">@csrf @method('PATCH')
            <select name="periode_exercice" style="padding:4px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12px">
                @foreach($sprint->periodesCouvertes() as $p)<option value="{{ $p->toDateString() }}" @selected(($periodeEx ?? $sprint->periodeProposee())->isSameDay($p))>{{ \App\Services\PeriodeExercice::nom($p) }} ({{ \App\Services\PeriodeExercice::libelle($p) }}){{ $sprint->periodeProposee()->isSameDay($p) ? ' - proposée' : '' }}</option>@endforeach
            </select>
            <button class="btn sm">{{ $periodeEx ? 'Modifier' : 'Arbitrer' }}</button>
        </form>
        @endif
    </div>
</div>

@if($canEdit)
<div class="actions-bar">
    @if($vue === 'planning' && $sprint->statut !== 'termine')
    <button class="btn" onclick="document.getElementById('dlg-tache').showModal()">+ Ajouter une t&acirc;che</button>
    <button class="btn sec" onclick="document.getElementById('dlg-import').showModal()">Importer (.xlsx)</button>
    @endif
    @if($sprint->statut === 'termine')
    <a href="{{ route('scrum.demo', $sprint) }}" class="btn sec">Rapport de review</a>
    @php $crReview = \App\Models\CompteRendu::where('sprint_id', $sprint->id)->where('type', 'review_sprint')->where('format', 'review_v1')->first(); @endphp
    @if($crReview)<a href="{{ route('comptes-rendus.review-sprint.edit', $crReview) }}" class="btn sec" title="Document formel antérieur au rapport de review unique">Ancien CR Review</a>@endif
    @endif
    <span class="sep"></span>
    @if($sprint->statut === 'planifie')
    <form method="POST" action="{{ route('sprints.demarrer', $sprint) }}" style="margin:0" onsubmit="return confirm('Démarrer ce sprint ? Les tâches deviendront exécutables par les collaborateurs.')">
        @csrf
        <button class="btn contour-ok">D&eacute;marrer le sprint</button>
    </form>
    @endif
    @if($sprint->statut === 'en_cours')
    <button class="btn contour-warn" onclick="document.getElementById('dlg-arreter').showModal()">Arr&ecirc;ter</button>
    <form method="POST" action="{{ route('sprints.cloturer', $sprint) }}" style="margin:0" onsubmit="return confirm('Clôturer ce sprint ? L\'exécution sera figée : les tâches non terminées seront marquées en retard et leurs user stories retourneront au Product Backlog. Suivront la démonstration, la Review et la Rétrospective.')">
        @csrf
        <button class="btn contour-ko">Cl&ocirc;turer le sprint</button>
    </form>
    @endif
</div>
@endif

@if($sprint->statut === 'planifie')
<div style="background:#e0f2fe;border:1px solid #7dd3fc;border-radius:8px;padding:10px 14px;font-size:13px;color:#0369a1;margin-bottom:12px">
    <strong>Sprint en pr&eacute;paration</strong> - les t&acirc;ches sont consultables mais non ex&eacute;cutables. D&eacute;marrez le sprint pour activer l'ex&eacute;cution.
</div>
@elseif($sprint->statut === 'termine')
<div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:10px 14px;font-size:13px;color:#475569;margin-bottom:12px">
    <strong>Sprint termin&eacute;</strong> - ex&eacute;cution fig&eacute;e.
    @if($manquants = \App\Services\CeremoniesScrum::rapportsManquants($sprint))
    <span style="margin-left:6px;color:#92400e">Rapports &agrave; finaliser : {{ implode(' · ', $manquants) }}.</span>
    @endif
    @if($sprint->justification_arret)
    <br>{{ $sprint->arrete_le ? "Motif d'arrêt" : 'Motif de clôture' }} : <em>{{ $sprint->justification_arret }}</em>
    @endif
</div>
@endif

{{-- ═══ ÉQUIPE DU SPRINT : dynamique (arrivées en cours de projet, retraits propres au sprint) ═══ --}}
{{-- ═══ Onglet Sprint Backlog : équipe et user stories ═══ --}}
@if($vue === 'backlog' || !$voitProjet)
@if($voitProjet)
@php $editEquipe = $canEdit && $sprint->statut !== 'termine'; @endphp
<div class="carte" style="margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <h3 style="font-size:14px;color:var(--bleu);margin:0">&Eacute;quipe du sprint <span class="pill p-attente">{{ $equipe->count() }}</span></h3>
    </div>
    <div class="eq-liste">
        @forelse($equipe as $m)
        @php
            $pc = $rolesProjet[$m->id] ?? null;
            $tm = $sprint->taches->where('user_id', $m->id);
            $arrive = $pc && $pc->date_ajout && $pc->date_ajout->gt($sprint->date_debut) && $pc->date_ajout->lte($sprint->date_fin);
        @endphp
        <div class="eq-membre">
            <span><span class="nom">{{ $m->nomComplet() }}</span><span class="role">{{ $pc?->roleLibelle() ?? 'Hors équipe projet' }}</span></span>
            @if($arrive)<span class="eq-tag eq-arrive" title="A rejoint le projet en cours de sprint">Arriv&eacute;(e) le {{ $pc->date_ajout->format('d/m') }}</span>@endif
            @if($pc && !$pc->actif)<span class="eq-tag eq-hors" title="Retiré(e) de l'équipe projet">Retir&eacute;(e) du projet le {{ $pc->date_retrait?->format('d/m') }}</span>@endif
            <span class="tch" title="Tâches terminées / attribuées dans ce sprint">{{ $tm->where('statut', 'termine')->count() }}/{{ $tm->count() }} t&acirc;che(s)</span>
            @if($editEquipe)
            <form method="POST" action="{{ route('scrum.equipe.retirer', [$sprint, $m]) }}" style="margin:0" onsubmit="return motifRetraitSprint(this, @js($m->nomComplet()), {{ $tm->whereNotIn('statut', ['termine', 'reporte'])->count() }})">@csrf @method('DELETE')
                <input type="hidden" name="motif">
                <button class="btn-edit-row" style="color:var(--ko)" title="Retirer de ce sprint">&#10005;</button>
            </form>
            @endif
        </div>
        @empty
        <span style="font-size:12.5px;color:var(--gris)">Aucun membre. Ajoutez des collaborateurs ci-dessous ou dans l'&eacute;quipe du projet.</span>
        @endforelse
    </div>
    @if($retiresSprint->isNotEmpty())
    <div class="eq-retires">Retir&eacute;s de ce sprint :
        @foreach($retiresSprint as $rs)
        <span class="r" title="{{ $rs->motif ? 'Motif : ' . $rs->motif . ' - ' : '' }}par {{ $rs->par?->nomComplet() ?? '—' }} le {{ $rs->updated_at->format('d/m/Y') }}">{{ $rs->user?->nomComplet() }}
            @if($editEquipe)<form method="POST" action="{{ route('scrum.equipe.reintegrer', [$sprint, $rs->user_id]) }}" style="margin:0">@csrf @method('PATCH')<button class="btn sm sec" style="padding:1px 7px">R&eacute;int&eacute;grer</button></form>@endif
        </span>
        @endforeach
    </div>
    @endif
    @if($editEquipe)
    @php $horsEquipe = $users->whereNotIn('id', $equipe->pluck('id'))->whereNotIn('id', $retiresSprint->pluck('user_id')); @endphp
    @if($horsEquipe->isNotEmpty())
    <form method="POST" action="{{ route('scrum.equipe.ajouter', $sprint) }}" class="eq-ajout">@csrf
        <span style="font-size:12.5px">Ajouter au sprint :</span>
        <select name="user_id" required>
            <option value="">— Collaborateur —</option>
            @php $duProjet = $horsEquipe->filter(fn($u) => ($rolesProjet[$u->id] ?? null)?->actif); @endphp
            @if($duProjet->isNotEmpty())<optgroup label="Équipe du projet">@foreach($duProjet as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach</optgroup>@endif
            <optgroup label="Autres collaborateurs (rejoignent le projet)">@foreach($horsEquipe->whereNotIn('id', $duProjet->pluck('id')) as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach</optgroup>
        </select>
        <select name="role_projet" title="Rôle dans le projet (pour un nouveau membre)">
            @foreach(\App\Models\ProjetCollaborateur::ROLES as $k => $l)<option value="{{ $k }}" @selected($k === 'developpeur')>{{ $l }}</option>@endforeach
        </select>
        <button class="btn sm">Ajouter</button>
    </form>
    @endif
    @endif
</div>
@endif

@include('scrum._actions_retro', ['sprint' => $sprint, 'enTache' => $canEdit && $sprint->statut !== 'termine'])

{{-- ═══ SPRINT BACKLOG : user stories retenues ═══ --}}
<div class="carte" style="margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <h3 style="font-size:14px;color:var(--bleu);margin:0">Sprint Backlog - user stories du sprint <span class="pill p-attente">{{ $storiesSprint->count() }}</span></h3>
        @if($peutPuiser)
        <button class="btn sm sec" onclick="document.getElementById('dlg-stories').showModal()" @disabled($backlogDispo->isEmpty()) title="{{ $backlogDispo->isEmpty() ? 'Aucune user story disponible dans le backlog' : '' }}">+ User stories du backlog</button>
        @endif
    </div>
    <div class="echelle" title="Complexité estimée des user stories">
        <span>Story points :</span>
        @foreach(\App\Models\UserStory::COMPLEXITES as $cle => $c)<span><b class="cx cx-{{ $cle }}">{{ $c['points'] }}</b>{{ $c['libelle'] }} - {{ $c['aide'] }}</span>@endforeach
    </div>
    @if($storiesSprint->isEmpty())
    <div style="font-size:12.5px;color:var(--gris)">Aucune user story rattachée. @if($sprint->projet->estVisiblePour(auth()->user()))Alimentez le <a href="{{ route('projets.espace', [$sprint->projet, 'onglet' => 'backlog']) }}" style="color:var(--accent)">Product Backlog du projet</a>, puis ajoutez ses user stories à ce sprint.@endif</div>
    @else
    <div class="sb-liste">
        @foreach($storiesSprint as $us)
        @php
            $tUs = $sprint->taches->where('user_story_id', $us->id);
            $modifiee = $us->modifieeDansSprint($sprint);
            // Sprint clôturé : la story se lit à sa valeur de clôture ; on signale qu'elle a évolué depuis
            $figeLe = $us->pivot->fige_le ? \Carbon\Carbon::parse($us->pivot->fige_le) : null;
            $evolueeDepuis = $figeLe && $us->contenu_modifie_le && $us->contenu_modifie_le->gt($figeLe);
        @endphp
        <div class="sb-us {{ $modifiee ? 'modifiee' : '' }}">
            <span class="num">{{ $us->numero }}</span>
            <span class="txt">{{ $us->intitule }}<span class="mod">{{ collect([$us->epic, $us->module, $us->profil])->filter()->join(' · ') }}</span>
                @if($modifiee)<span class="signal" title="Modifiée dans le Product Backlog le {{ $us->contenu_modifie_le->format('d/m/Y à H:i') }}, après son entrée dans le sprint"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>Modifiée le {{ $us->contenu_modifie_le->format('d/m') }} : {{ $us->contenu_modifie_champs }}</span>@endif
                @if($evolueeDepuis)<span class="signal fige" title="Valeurs figées à la clôture du sprint, le {{ $figeLe->format('d/m/Y') }}. Modifiée depuis dans le Product Backlog ({{ $us->contenu_modifie_champs }}) le {{ $us->contenu_modifie_le->format('d/m/Y') }}.">Valeurs à la clôture · modifiée depuis dans le Product Backlog</span>@endif
            </span>
            @if($us->priorite_moscow)<span class="cx cx-aucune" title="Priorité MoSCoW">{{ \App\Models\UserStory::MOSCOW[$us->priorite_moscow] ?? $us->priorite_moscow }}</span>@endif
            <span class="cx {{ $us->dor ? 'cx-simple' : 'cx-aucune' }}" title="Definition of Ready">DoR {{ $us->dor ? 'Oui' : 'Non' }}</span>
            <span class="avance">@include('scrum._niveau_us', ['us' => $us, 'taches' => $tUs])<small style="display:block;color:#94a3b8;font-size:10.5px;margin-top:2px">{{ $tUs->where('statut', 'termine')->count() }}/{{ $tUs->count() }} tâche(s) terminée(s)</small></span>
            @php $cx = $us->complexiteStory(); @endphp
            @if($canEdit && $sprint->statut !== 'termine')
            <form method="POST" action="{{ route('sprints.user-stories.planifier', [$sprint, $us]) }}" class="sb-pts" style="margin:0" title="Story points (complexité estimée)">@csrf @method('PATCH')
                <select name="effort_estime" onchange="this.form.submit()">
                    <option value="">— pts</option>
                    @foreach(\App\Models\UserStory::POINTS as $p)<option value="{{ $p }}" @selected($us->effort_estime === $p)>{{ $p }} pt{{ $p > 1 ? 's' : '' }}</option>@endforeach
                </select>
                <span class="cx cx-{{ $cx['cle'] ?? 'aucune' }}" title="{{ $cx['aide'] ?? 'Story non estimée' }}">{{ $cx['libelle'] ?? 'Non estimée' }}</span>
            </form>
            <button type="button" class="btn sm sec" onclick='ouvrirTachesUs(@json(route('sprints.user-stories.planifier', [$sprint, $us])), @json($us->numero), @json(\App\Services\TexteRiche::html($us->taches_techniques)))' title="Tâches techniques de la user story (Sprint Backlog)">T&acirc;ches techniques{{ $us->taches_techniques ? ' (' . count(array_filter(preg_split('/\R/', \App\Services\TexteRiche::texte($us->taches_techniques)))) . ')' : '' }}</button>
            @include('scrum._statut_us', ['us' => $us, 'peutAlimenter' => $peutAlimenter])
            <form method="POST" action="{{ route('sprints.user-stories.detacher', [$sprint, $us]) }}" style="margin:0" onsubmit="return confirm('Retirer {{ $us->numero }} du sprint ? Elle retourne au backlog ; ses tâches restent dans le sprint.')">@csrf @method('DELETE')
                <button class="btn-edit-row" style="color:var(--ko)" title="Retirer du sprint">&#10005;</button>
            </form>
            @else
            <span class="cx cx-{{ $cx['cle'] ?? 'aucune' }}" title="{{ $cx['aide'] ?? '' }}">{{ $us->effort_estime ? $us->effort_estime . ' pts · ' . $cx['libelle'] : 'Non estimée' }}</span>
            @if($sprint->statut === 'termine')
            <span class="pill {{ $us->pivot->statut_sprint === 'termine' ? 'p-ok' : ($us->pivot->statut_sprint === 'en_cours' ? 'p-cours' : 'p-attente') }}" title="Statut à la clôture du sprint">{{ \App\Models\UserStory::STATUTS_SPRINT[$us->pivot->statut_sprint] ?? $us->pivot->statut_sprint }}</span>
            @else
            @include('scrum._statut_us', ['us' => $us, 'peutAlimenter' => $peutAlimenter])
            @endif
            @if($peutPuiser)
            <form method="POST" action="{{ route('sprints.user-stories.detacher', [$sprint, $us]) }}" style="margin:0" onsubmit="return confirm('Retirer {{ $us->numero }} du sprint ? Elle retourne au backlog.')">@csrf @method('DELETE')
                <button class="btn-edit-row" style="color:var(--ko)" title="Retirer du sprint">&#10005;</button>
            </form>
            @endif
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>

@endif

{{-- ═══ Onglet Sprint Planning : tâches ═══ --}}
@if($vue === 'planning' || !$voitProjet)
@if($totalTaches > 0)
<div class="filtre-bar">
    <input type="text" id="fNom" placeholder="Rechercher une t&acirc;che..." oninput="filtrerSprint()">
    <select id="fAssigne" onchange="filtrerSprint()">
        <option value="">Tous les assign&eacute;s</option>
        @foreach($sprint->taches->pluck('user')->unique('id')->filter()->sortBy('name') as $u)
        <option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>
        @endforeach
    </select>
    <select id="fPrio" onchange="filtrerSprint()">
        <option value="">Toutes priorit&eacute;s</option>
        <option value="haute">Haute</option>
        <option value="moyenne">Moyenne</option>
        <option value="basse">Basse</option>
    </select>
    @if($storiesSprint->isNotEmpty())
    <select id="fUs" onchange="filtrerSprint()">
        <option value="">Toutes les user stories</option>
        @foreach($storiesSprint as $us)<option value="{{ $us->id }}">{{ $us->numero }}</option>@endforeach
        <option value="aucune">Hors user story</option>
    </select>
    @endif
    <select id="fStatut" onchange="filtrerSprint()">
        <option value="">Tous statuts</option>
        <option value="a_faire">&Agrave; faire</option>
        <option value="en_cours">En cours</option>
        <option value="a_valider">En cours de validation</option>
        <option value="bloque">Bloqu&eacute;</option>
        <option value="termine">Termin&eacute;</option>
    </select>
</div>
<div class="sc-legende">
    <span class="sc sc-d"><b>D</b> D&eacute;marr&eacute;</span><span class="sc sc-e"><b>E</b> En cours</span><span class="sc sc-t"><b>T</b> Termin&eacute;</span>
    <span class="sc sc-tt"><b>TT</b> Termin&eacute; test&eacute;</span><span class="sc sc-er"><b>ER</b> En retard (jours ouvr&eacute;s)</span>
</div>
<div class="carte" style="overflow-x:auto">
<h3 style="font-size:14px;color:var(--bleu);margin:0 0 10px;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> T&acirc;ches du sprint</h3>
@if($peutRattacher)
<form method="POST" id="f-rattacher" class="rattacher" action="{{ route('sprints.taches.rattacher', $sprint) }}">@csrf
    <span>T&acirc;ches coch&eacute;es :</span>
    <select name="user_story_id" aria-label="User story">
        <optgroup label="User stories du sprint">
            @foreach($storiesSprint as $us)<option value="{{ $us->id }}">{{ $us->numero }} - {{ \Illuminate\Support\Str::limit($us->intitule, 90) }}</option>@endforeach
        </optgroup>
        <optgroup label="Product Backlog (DoR = Oui)">
            @foreach($backlogDispo as $us)<option value="{{ $us->id }}">{{ $us->numero }} - {{ \Illuminate\Support\Str::limit($us->intitule, 90) }}</option>@endforeach
        </optgroup>
        <option value="">Aucune (hors user story)</option>
    </select>
    <button class="btn sm" id="ratt-btn" disabled>Rattacher <span id="ratt-nb"></span></button>
</form>
@endif
<table class="taches-table">
<thead>
    <tr>
        @if($peutRattacher)<th class="col-coche"><input type="checkbox" class="t-tout" id="t-tout" title="Tout cocher (tâches affichées)" aria-label="Cocher toutes les tâches affichées"></th>@endif
        <th class="col-tache">T&acirc;che</th>
        <th class="col-assigne">Assign&eacute;</th>
        <th class="col-prio">Prio.</th>
        <th class="col-debut">D&eacute;but</th>
        <th class="col-ech">Fin</th>
        <th class="col-duree" title="Durée planifiée en jours ouvrés">Dur&eacute;e</th>
        <th class="col-pts">Pts</th>
        <th class="col-statut">Statut</th>
        @if($canEdit && $sprint->statut !== 'termine')<th class="col-edit"></th>@endif
    </tr>
</thead>
<tbody>
@php
    $nbCol = 8 + ($canEdit && $sprint->statut !== 'termine' ? 1 : 0) + ($peutRattacher ? 1 : 0);
    // Regroupement par user story (ordre du Sprint Backlog), puis tâches hors story
    $ordreUs = $storiesSprint->pluck('id')->flip();
    $groupes = $sprint->taches->groupBy(fn($t) => $t->user_story_id ?: 0)
        ->sortBy(fn($g, $k) => $k === 0 ? PHP_INT_MAX : ($ordreUs[$k] ?? PHP_INT_MAX - 1));
@endphp
@foreach($groupes as $usId => $tachesGroupe)
@php $usG = $usId ? ($tachesGroupe->first()->userStory) : null; @endphp
@if($storiesSprint->isNotEmpty() || $usId)
<tr class="groupe-us" data-groupe="{{ $usId ?: 'aucune' }}"><td colspan="{{ $nbCol }}">@if($usG)<span class="gu-num">{{ $usG->numero }}</span>{{ \Illuminate\Support\Str::limit($usG->intitule, 140) }}@if($usG->effort_estime)<span class="gu-pts cx cx-{{ $usG->complexiteStory()['cle'] }}">{{ $usG->effort_estime }} story pts · {{ $usG->complexiteStory()['libelle'] }}</span>@endif @else Hors user story @endif</td></tr>
@endif
@foreach($tachesGroupe->sortBy([['date_debut', 'asc'], ['echeance', 'asc']]) as $t)
@php $sc = $t->codeScrum(); @endphp
<tr data-row data-us="{{ $t->user_story_id ?: 'aucune' }}" data-nom="{{ strtolower($t->intitule) }}" data-user="{{ $t->user_id }}" data-prio="{{ $t->priorite }}" data-statut="{{ $t->statut }}" @if($sprint->statut !== 'en_cours') style="opacity:0.55" @endif>
    @if($peutRattacher)<td style="text-align:center"><input type="checkbox" class="t-coche" form="f-rattacher" name="taches[]" value="{{ $t->id }}" aria-label="Sélectionner {{ $t->intitule }}"></td>@endif
    <td>
        <span class="typ t1">T1</span>
        <a href="{{ route('taches.show', $t) }}" class="tache-nom">{{ $t->intitule }}</a>
    </td>
    <td style="font-size:12px">{{ $t->user?->nomComplet() ?? '—' }}</td>
    <td>
        @php $pCouleur = match($t->priorite) { 'haute' => '#ef4444', 'basse' => '#94a3b8', default => '#2E74B5' }; @endphp
        <span class="prio-dot" style="background:{{ $pCouleur }}"></span><span style="font-size:11.5px">{{ ucfirst($t->priorite) }}</span>
    </td>
    <td style="font-size:12px;color:#475569">{{ $t->date_debut?->format('d/m') ?? '—' }}</td>
    <td style="font-size:12px;color:#475569;white-space:nowrap"><x-voyant :tache="$t" /> {{ $t->echeance?->format('d/m') ?? '-' }}</td>
    <td style="text-align:center;font-size:12px">{{ $t->dureeJours() ?: '—' }}</td>
    <td style="text-align:center;font-weight:600;font-size:12px">{{ $t->points ?? '—' }}</td>
    <td><span class="sc {{ $sc['classe'] }}" title="{{ $sc['libelle'] }} - statut : {{ $t->labelStatut() }}"><b>{{ $sc['code'] }}</b>{{ $sc['code'] === 'ER' ? preg_replace('/\D+/', '', $sc['libelle']) . ' j' : ($sc['code'] === 'TT' ? 'Testé' : $sc['libelle']) }}</span></td>
    @if($canEdit && $sprint->statut !== 'termine')
    <td style="text-align:center;white-space:nowrap">
        @if($t->statut === 'termine')
        <form method="POST" action="{{ route('sprints.taches.teste', [$sprint, $t]) }}" style="display:inline;margin:0">@csrf @method('PATCH')
            <button class="btn-teste {{ $t->teste ? 'on' : '' }}" title="{{ $t->teste ? 'Annuler le test' : 'Marquer comme testée (TT)' }}">{{ $t->teste ? 'Testée' : 'Tester' }}</button>
        </form>
        @endif
        @if($canEditSprint)
        <button class="btn-edit-row" onclick="ouvrirEditTache({{ $t->id }}, '{{ addslashes($t->intitule) }}', {{ $t->user_id ?? 'null' }}, '{{ $t->priorite }}', '{{ $t->date_debut?->format('Y-m-d') ?? '' }}', '{{ $t->echeance?->format('Y-m-d') ?? '' }}', {{ $t->points ?? 'null' }}, {{ $t->user_story_id ?? 'null' }})" title="Modifier"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
        <form method="POST" action="{{ route('sprints.supprimer-tache', [$sprint, $t]) }}" style="display:inline;margin:0" onsubmit="return demanderMotifSuppression(this,'cette tâche')">@csrf @method('DELETE')
            <input type="hidden" name="motif_suppression" class="motif-suppr-input">
            <button type="submit" class="btn-edit-row" style="color:var(--ko)" title="Supprimer"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
        </form>
        @endif
    </td>
    @endif
</tr>
@endforeach
@endforeach
</tbody>
</table>
</div>
@else
<div class="vide">
    <p>Aucune t&acirc;che dans ce sprint.</p>
    @if($canEdit)
    <p style="margin-top:8px;font-size:13px">Ajoutez des t&acirc;ches manuellement ou importez un fichier xlsx.</p>
    @endif
</div>
@endif
@endif

{{-- Dialog Sprint Backlog : puiser dans le Product Backlog (gestionnaire et équipe du projet) --}}
@if($peutPuiser)
<dialog id="dlg-stories">
    <h3 style="color:var(--bleu);margin-bottom:4px">Ajouter des user stories au sprint</h3>
    <div style="font-size:12px;color:var(--gris);margin-bottom:10px">User stories prêtes (DoR = Oui), non achevées, engagées dans aucun sprint ouvert.</div>
    <form method="POST" action="{{ route('sprints.user-stories.attacher', $sprint) }}">@csrf
        <div class="liste-coche">
            @foreach($backlogDispo as $us)
            <label><input type="checkbox" name="user_stories[]" value="{{ $us->id }}"><span><strong style="color:var(--accent)">{{ $us->numero }}</strong> {{ $us->intitule }}@if($us->module)<span style="display:block;font-size:11px;color:var(--gris)">{{ $us->module }}</span>@endif<span style="display:block;font-size:11px;color:var(--gris)">{{ collect([\App\Models\UserStory::MOSCOW[$us->priorite_moscow] ?? null, $us->effort_estime ? $us->effort_estime . ' pts' : null, 'DoR ' . ($us->dor ? 'Oui' : 'Non')])->filter()->join(' · ') }}</span></span></label>
            @endforeach
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Ajouter au sprint</button>
        </div>
    </form>
</dialog>
@endif

@if($canEdit)
{{-- Dialog arrêt anticipé --}}
@if($sprint->statut === 'en_cours')
<dialog id="dlg-arreter">
    <h3 style="color:var(--ko);margin-bottom:4px">Arr&ecirc;ter le sprint</h3>
    @if($sprint->date_fin->isFuture())
    <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400e;margin-bottom:12px">
        La date de fin pr&eacute;vue est le <strong>{{ $sprint->date_fin->format('d/m/Y') }}</strong>. L'arr&ecirc;t anticip&eacute; n&eacute;cessite une justification.
    </div>
    @endif
    <form method="POST" action="{{ route('sprints.arreter', $sprint) }}">@csrf
        <label>Justification <span class="req">*</span></label>
        <textarea name="justification" required rows="3" placeholder="Raison de l'arr&ecirc;t anticipé..." style="width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;resize:vertical"></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn" style="background:var(--ko)">Confirmer l'arr&ecirc;t</button>
        </div>
    </form>
</dialog>
@endif

{{-- Dialog ajout tâche --}}
<dialog id="dlg-tache">
    <h3 style="color:var(--bleu);margin-bottom:4px">Ajouter une t&acirc;che au sprint</h3>
    <form method="POST" action="{{ route('sprints.ajouter-tache', $sprint) }}">@csrf
        <label>Intitul&eacute; <span class="req">*</span></label>
        <input name="intitule" required placeholder="Ex : Intégration page d'accueil">
        <label>User story</label>
        <select name="user_story_id" id="at-us">
            <option value="">— Hors user story —</option>
            @foreach($storiesSprint as $us)<option value="{{ $us->id }}">{{ $us->numero }} - {{ \Illuminate\Support\Str::limit($us->intitule, 70) }}</option>@endforeach
            @foreach($backlogDispo as $us)<option value="{{ $us->id }}">{{ $us->numero }} - {{ \Illuminate\Support\Str::limit($us->intitule, 70) }} (backlog)</option>@endforeach
        </select>
        <div class="form-row">
            <div>
                <label>Assign&eacute; &agrave; <span class="req">*</span></label>
                <select name="user_id" required>
                    <option value="">— Choisir —</option>
                    @if($equipe->isNotEmpty())
                    <optgroup label="Équipe du sprint">@foreach($equipe as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach</optgroup>
                    <optgroup label="Autres collaborateurs">@foreach($users->whereNotIn('id', $equipe->pluck('id')) as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach</optgroup>
                    @else
                    @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach
                    @endif
                </select>
            </div>
            <div>
                <label>Priorit&eacute; <span class="req">*</span></label>
                <select name="priorite" required>
                    <option value="moyenne">Moyenne</option>
                    <option value="haute">Haute</option>
                    <option value="basse">Basse</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div><label>Date d&eacute;but</label><input type="date" name="date_debut" value="{{ max(today(), $sprint->date_debut)->format('Y-m-d') }}"></div>
            <div><label>Date de fin pr&eacute;vue</label><input type="date" name="echeance"></div>
        </div>
        <label>Points (story points)</label>
        <input type="number" name="points" min="1" placeholder="Ex : 3">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Ajouter</button>
        </div>
    </form>
</dialog>

{{-- Dialog tâches techniques d'une user story --}}
<dialog id="dlg-taches-us">
    <h3 style="color:var(--bleu);margin-bottom:4px">T&acirc;ches techniques - <span id="tu-num"></span></h3>
    <div style="font-size:12px;color:var(--gris);margin-bottom:6px">Une t&acirc;che par puce.</div>
    <form method="POST" id="form-taches-us" action="">@csrf @method('PATCH')
        <x-redaction name="taches_techniques" :hauteur="150" />
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>


{{-- Dialog import xlsx --}}
<dialog id="dlg-import">
    <h3 style="color:var(--bleu);margin-bottom:4px">Importer le Sprint Planning (.xlsx)</h3>
    <div style="font-size:12px;color:var(--gris);margin-bottom:14px;line-height:1.6">
        <p style="margin-bottom:8px">Les t&acirc;ches seront rattach&eacute;es au <strong>Sprint {{ $sprint->numero }}</strong>.
            <a href="{{ route('sprints.planning.modele', $sprint) }}" style="color:var(--accent);font-weight:600;margin-left:6px">⤓ T&eacute;l&eacute;charger le mod&egrave;le d'import</a></p>
        <div style="background:var(--fond);border:1px solid var(--bord);border-radius:8px;padding:10px 12px;margin-bottom:8px">
            <div style="font-weight:700;color:#1e293b;margin-bottom:4px;font-size:12px">Format attendu du fichier Excel :</div>
            <table style="width:100%;font-size:11px;border-collapse:collapse">
                <thead><tr style="background:#e2e8f0">
                    <th style="padding:4px 6px;text-align:left;border:1px solid var(--bord)">N&deg;</th>
                    <th style="padding:4px 6px;text-align:left;border:1px solid var(--bord)">Colonne</th>
                    <th style="padding:4px 6px;text-align:left;border:1px solid var(--bord)">Obligatoire</th>
                    <th style="padding:4px 6px;text-align:left;border:1px solid var(--bord)">Exemple</th>
                </tr></thead>
                <tbody>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">1</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Intitul&eacute;</td><td style="padding:3px 6px;border:1px solid var(--bord);color:var(--ko)">Oui</td><td style="padding:3px 6px;border:1px solid var(--bord)">Int&eacute;gration page login</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">2</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Assign&eacute; &agrave;</td><td style="padding:3px 6px;border:1px solid var(--bord);color:var(--ko)">Oui</td><td style="padding:3px 6px;border:1px solid var(--bord)">YAPO Kouadio</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">3</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Priorit&eacute;</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">Haute / Moyenne / Basse</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">4</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">&Eacute;ch&eacute;ance</td><td style="padding:3px 6px;border:1px solid var(--bord);color:var(--ko)">Oui</td><td style="padding:3px 6px;border:1px solid var(--bord)">01/08/2026</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">5</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Date d&eacute;but</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">28/07/2026</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">6</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Points</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">3</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">7</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Projet</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">CROU Connect</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">8</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">User story</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">US 1.2 - En tant que…</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">9</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Module</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">Gestion des paiements</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">10</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Epic</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">Paiement en ligne</td></tr>
                </tbody>
            </table>
            <div style="margin-top:6px;font-size:10.5px;color:#64748b">
                &bull; La ligne d'en-t&ecirc;te est d&eacute;tect&eacute;e automatiquement (premi&egrave;re ligne contenant &laquo; T&acirc;che &raquo; ou &laquo; Intitul&eacute; &raquo;).<br>
                &bull; <strong>User story</strong> : num&eacute;ro (&laquo; US 1.2 &raquo;) ou intitul&eacute;. Une story absente du Product Backlog y est cr&eacute;&eacute;e avec son module et son epic ; toutes sont rattach&eacute;es au sprint.<br>
                &bull; Le <strong>Sprint Planning export&eacute;</strong> (archive Scrum) se r&eacute;importe tel quel : lignes &laquo; MODULE &raquo;, &laquo; Epic &raquo;, &laquo; US x.y &raquo; et leurs t&acirc;ches.<br>
                &bull; Sans &eacute;ch&eacute;ance, une t&acirc;che prend la date de fin du sprint.<br>
                &bull; L'ordre des colonnes est <strong>libre</strong> - le syst&egrave;me reconna&icirc;t les noms.<br>
                &bull; Si &laquo; Assign&eacute; &agrave; &raquo; ne correspond &agrave; aucun collaborateur, la t&acirc;che est attribu&eacute;e &agrave; la personne qui importe.<br>
                &bull; R&eacute;import : une ligne dont l'intitul&eacute; et l'assign&eacute; existent d&eacute;j&agrave; dans le sprint met la t&acirc;che &agrave; jour au lieu de la dupliquer.<br>
                &bull; Si &laquo; Projet &raquo; est vide, le projet du sprint sera utilis&eacute;.
            </div>
        </div>
    </div>
    <form method="POST" action="{{ route('sprints.importer') }}" enctype="multipart/form-data">@csrf
        <input type="hidden" name="sprint_id" value="{{ $sprint->id }}">
        <label>Fichier Excel (.xlsx) <span class="req">*</span></label>
        <input type="file" name="fichier" accept=".xlsx" required style="padding:8px;border:1px dashed var(--bord);border-radius:8px;background:var(--fond);margin-top:4px">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Importer</button>
        </div>
    </form>
</dialog>

{{-- Dialog modification sprint (pilotes uniquement) --}}
@if($canEditSprint && $sprint->statut !== 'termine')
<dialog id="dlg-edit-sprint">
    <h3 style="color:var(--bleu);margin-bottom:4px">Modifier le sprint</h3>
    <form method="POST" action="{{ route('sprints.update', $sprint) }}">@csrf @method('PUT')
        <label>Objectif <span class="req">*</span></label>
        <input name="objectif" required value="{{ $sprint->objectif }}">
        <div class="form-row">
            <div><label>Date d&eacute;but <span class="req">*</span></label><input type="date" name="date_debut" required value="{{ $sprint->date_debut->format('Y-m-d') }}"></div>
            <div><label>Date fin <span class="req">*</span></label><input type="date" name="date_fin" required value="{{ $sprint->date_fin->format('Y-m-d') }}"></div>
        </div>
        <label>Points engag&eacute;s (si aucune user story n'est estim&eacute;e)</label>
        <input type="number" name="engagement_points" min="1" value="{{ $sprint->engagement_points }}">
        <div style="font-size:11px;color:var(--gris);margin-top:3px">Calculé automatiquement : {{ $sprint->userStories->sum('effort_estime') }} pts (stories du sprint).</div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endif

{{-- Dialog modification tâche (pilotes uniquement) --}}
@if($canEditSprint)
<dialog id="dlg-edit-tache">
    <h3 style="color:var(--bleu);margin-bottom:4px">Modifier la t&acirc;che</h3>
    <form id="form-edit-tache" method="POST" action="">@csrf @method('PUT')
        <label>Intitul&eacute; <span class="req">*</span></label>
        <input name="intitule" id="et-intitule" required>
        <label>User story</label>
        <select name="user_story_id" id="et-us">
            <option value="">— Hors user story —</option>
            @foreach($storiesSprint as $us)<option value="{{ $us->id }}">{{ $us->numero }} - {{ \Illuminate\Support\Str::limit($us->intitule, 70) }}</option>@endforeach
            @foreach($backlogDispo as $us)<option value="{{ $us->id }}">{{ $us->numero }} - {{ \Illuminate\Support\Str::limit($us->intitule, 70) }} (backlog)</option>@endforeach
        </select>
        <div class="form-row">
            <div>
                <label>Assign&eacute; &agrave; <span class="req">*</span></label>
                <select name="user_id" id="et-user" required>
                    <option value="">— Choisir —</option>
                    @if($equipe->isNotEmpty())
                    <optgroup label="Équipe du sprint">@foreach($equipe as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach</optgroup>
                    <optgroup label="Autres collaborateurs">@foreach($users->whereNotIn('id', $equipe->pluck('id')) as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach</optgroup>
                    @else
                    @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach
                    @endif
                </select>
            </div>
            <div>
                <label>Priorit&eacute; <span class="req">*</span></label>
                <select name="priorite" id="et-priorite" required>
                    <option value="moyenne">Moyenne</option>
                    <option value="haute">Haute</option>
                    <option value="basse">Basse</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div><label>Date d&eacute;but</label><input type="date" name="date_debut" id="et-debut"></div>
            <div><label>&Eacute;ch&eacute;ance</label><input type="date" name="echeance" id="et-echeance"></div>
        </div>
        <label>Points (story points)</label>
        <input type="number" name="points" id="et-points" min="1">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endif
@endif
@endsection

@push('scripts')
<script>
@if($canEditSprint)
function ouvrirEditTache(id, intitule, userId, priorite, debut, echeance, points, usId){
    document.getElementById('form-edit-tache').action = '/sprints/{{ $sprint->id }}/taches/' + id;
    document.getElementById('et-intitule').value = intitule;
    document.getElementById('et-user').value = userId || '';
    document.getElementById('et-priorite').value = priorite;
    document.getElementById('et-debut').value = debut;
    document.getElementById('et-echeance').value = echeance;
    document.getElementById('et-points').value = points || '';
    document.getElementById('et-us').value = usId || '';
    document.getElementById('dlg-edit-tache').showModal();
}
@endif
function motifRetraitSprint(f, nom, ouvertes){
    const m = prompt('Retirer ' + nom + ' de ce sprint ?' + (ouvertes ? '\n' + ouvertes + ' tâche(s) non terminée(s) lui restent attribuées.' : '') + '\n\nMotif (facultatif) :', '');
    if (m === null) return false;
    f.querySelector('[name=motif]').value = m;
    return true;
}
function ouvrirTachesUs(action, numero, texte){
    const d = document.getElementById('dlg-taches-us');
    if (!d) return;
    document.getElementById('form-taches-us').action = action;
    document.getElementById('tu-num').textContent = numero;
    Redaction.definir('taches_techniques', texte || '');
    d.showModal();
}
// Rattachement en lot : compteur et « tout cocher » limité aux tâches affichées
(function(){
    const tout = document.getElementById('t-tout'), btn = document.getElementById('ratt-btn');
    if (!tout || !btn) return;
    const maj = () => {
        const n = document.querySelectorAll('.t-coche:checked').length;
        btn.disabled = !n;
        document.getElementById('ratt-nb').textContent = n ? '(' + n + ')' : '';
    };
    document.addEventListener('change', e => {
        if (e.target === tout) document.querySelectorAll('[data-row]').forEach(tr => {
            if (tr.style.display !== 'none') tr.querySelector('.t-coche').checked = tout.checked;
        });
        if (e.target === tout || e.target.classList.contains('t-coche')) maj();
    });
})();

function filtrerSprint(){
    const nom=document.getElementById('fNom').value.toLowerCase();
    const user=document.getElementById('fAssigne').value;
    const prio=document.getElementById('fPrio').value;
    const statut=document.getElementById('fStatut').value;
    const us=(document.getElementById('fUs')||{}).value||'';
    const groupesVisibles=new Set();
    document.querySelectorAll('[data-row]').forEach(tr=>{
        let ok=true;
        if(us && tr.dataset.us!==us) ok=false;
        if(nom && !tr.dataset.nom.includes(nom)) ok=false;
        if(user && tr.dataset.user!==user) ok=false;
        if(prio && tr.dataset.prio!==prio) ok=false;
        if(statut && tr.dataset.statut!==statut) ok=false;
        tr.style.display=ok?'':'none';
        if(ok) groupesVisibles.add(tr.dataset.us);
    });
    document.querySelectorAll('tr.groupe-us').forEach(g=>{ g.style.display=groupesVisibles.has(g.dataset.groupe)?'':'none'; });
}
</script>
@endpush
