@extends('layouts.app')
@section('sousTitre', 'Comptes rendus')
@section('titre', $cr->titre)
@section('contenu')
@php
$ic = fn(string $d, int $s=17) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
$moscowOpts = \App\Models\UserStory::MOSCOW;
@endphp
<style>
.rw{max-width:1060px;margin:0 auto}
.rw-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:18px}
.rw-head h2{font-size:1.35rem;font-weight:700;color:var(--bleu,#1F4E79);margin:0}
.rw-statut{display:inline-flex;align-items:center;gap:5px;padding:4px 14px;border-radius:20px;font-size:.78rem;font-weight:600}
.rw-statut.brouillon{background:#FFF8E1;color:#B8860B}
.rw-statut.en_revision{background:#E3F2FD;color:#1565C0}
.rw-statut.confirme{background:#E8F5E9;color:#2E7D32}
/* Tabs */
.rw-tabs{display:flex;gap:0;overflow-x:auto;background:#fff;border-radius:12px 12px 0 0;box-shadow:0 1px 4px rgba(0,0,0,.06);border-bottom:2px solid #DDEAF5}
.rw-tab{padding:12px 18px;font-size:.82rem;font-weight:600;color:#64748b;cursor:pointer;white-space:nowrap;border-bottom:3px solid transparent;transition:all .15s;background:none;border:none;display:flex;align-items:center;gap:6px}
.rw-tab:hover{color:var(--bleu,#1F4E79);background:#FAFCFF}
.rw-tab.active{color:var(--bleu,#1F4E79);border-bottom-color:var(--bleu,#1F4E79);background:#fff}
.rw-tab .tab-num{width:22px;height:22px;border-radius:50%;background:#e2e8f0;color:#64748b;font-size:.72rem;display:flex;align-items:center;justify-content:center;font-weight:700}
.rw-tab.active .tab-num{background:var(--bleu,#1F4E79);color:#fff}
/* Panel */
.rw-panel{background:#fff;border-radius:0 0 12px 12px;box-shadow:0 1px 4px rgba(0,0,0,.06);padding:24px;min-height:300px;display:none}
.rw-panel.active{display:block}
/* Fields */
.fg{margin-bottom:14px}
.fg label{font-size:.85rem;font-weight:600;color:#475569;display:block;margin-bottom:4px}
.fg input,.fg select,.fg textarea{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;font-family:inherit}
.fg textarea{min-height:60px;resize:vertical}
.fg.row2,.fg .row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fg.row3,.fg .row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
/* Tables */
.tbl{width:100%;border-collapse:collapse;font-size:.85rem}
.tbl th{background:var(--bleu,#1F4E79);color:#fff;padding:8px 10px;text-align:left;font-weight:600}
.tbl td{padding:8px 10px;border-bottom:1px solid #e5e7eb;vertical-align:top}
.tbl input[type=text],.tbl select,.tbl textarea{width:100%;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.82rem;font-family:inherit}
.tbl textarea{min-height:40px;resize:vertical}
.chk{width:18px;height:18px;cursor:pointer;accent-color:var(--bleu,#1F4E79)}
/* BDD */
.bdd-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;margin-bottom:14px}
.bdd-card h4{font-size:.95rem;font-weight:700;color:var(--bleu,#1F4E79);margin:0 0 10px}
.bdd-crit{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin-bottom:8px}
.bdd-crit label{font-size:.78rem;font-weight:700;color:#64748b;display:block;margin-bottom:2px}
.bdd-crit .bdd-kw{font-weight:700;color:var(--accent,#2E74B5);font-size:.78rem;margin-bottom:1px}
/* Verdict */
.verdict-row{display:flex;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap}
.verdict-btn{padding:4px 14px;border-radius:20px;font-size:.78rem;font-weight:600;cursor:pointer;border:2px solid #d1d5db;background:#fff;transition:all .15s}
.verdict-btn.sel-acceptee{background:#E8F5E9;border-color:#2E7D32;color:#2E7D32}
.verdict-btn.sel-refusee{background:#FEECEC;border-color:#CC3333;color:#CC3333}
.verdict-btn.sel-partielle{background:#FFF8E1;border-color:#B8860B;color:#B8860B}
/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px}
.kpi-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;text-align:center}
.kpi-card .kval{font-size:1.6rem;font-weight:800;color:var(--bleu,#1F4E79)}
.kpi-card .klab{font-size:.78rem;color:#64748b;margin-top:2px}
/* Buttons */
.btn-add{background:#f1f5f9;color:#475569;border:1px dashed #94a3b8;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.82rem;display:inline-flex;align-items:center;gap:5px}
.btn-add:hover{background:#e2e8f0}
.btn-rm{background:none;border:none;color:#dc2626;cursor:pointer;font-size:1.1rem;padding:2px 6px}
.btn-act{text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:8px 20px;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;border:none;transition:all .15s}
.btn-save{background:var(--bleu,#1F4E79);color:#fff}.btn-save:hover{background:#163d5e}
.btn-confirm{background:#16a34a;color:#fff}.btn-confirm:hover{background:#15803d}
.btn-dl{background:#2E74B5;color:#fff}.btn-dl:hover{background:#1F4E79}
.btn-back{background:#f1f5f9;color:#475569;border:1px solid #d1d5db}.btn-back:hover{background:#e2e8f0}
/* Footer bar */
.rw-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-top:18px;padding:16px 24px;background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
/* Feedback */
.fb-list{display:flex;flex-direction:column;gap:6px}
.fb-item{display:flex;gap:6px;align-items:start}
.fb-item input{flex:1}
/* Responsive */
@media(max-width:768px){
.rw-tabs{gap:0}.rw-tab{padding:10px 12px;font-size:.76rem}
.fg.row2,.fg.row3,.fg .row2,.fg .row3{grid-template-columns:1fr}
.kpi-grid{grid-template-columns:1fr 1fr}
.rw-footer{flex-direction:column;align-items:stretch}
.rw-footer .btn-act{justify-content:center}
}
</style>

<div class="rw">
{{-- En-tete --}}
<div class="rw-head">
    <div>
        <h2>{!! $ic('<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/>',20) !!} {{ $cr->titre }}</h2>
        <span style="font-size:.82rem;color:#64748b">Sprint {{ $sprint->numero ?? '?' }} - {{ $sprint->projet->libelle ?? '' }} · R&eacute;dacteur : {{ $cr->redacteur?->nomComplet() ?? '-' }}</span>
    </div>
    <span class="rw-statut {{ $cr->statut }}">
        @if ($cr->estConfirme()) {!! $ic('<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',14) !!} Confirm&eacute;
        @elseif ($cr->estEnRevision()) {!! $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',14) !!} En r&eacute;vision
        @else {!! $ic('<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>',14) !!} Brouillon
        @endif
    </span>
</div>

@if(session('ok'))<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:.86rem">{{ session('ok') }}</div>@endif
@if(session('erreur'))<div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:.86rem">{{ session('erreur') }}</div>@endif
@if($errors->any())<div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:.86rem">{{ $errors->first() }}</div>@endif
@if($cr->estEnRevision())<div style="background:#eef4fb;border:1px solid #bfdbfe;color:#1f4e79;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:.86rem">En relecture par le responsable du projet{{ $peutValider ? ' : vous pouvez le corriger, le renvoyer au rédacteur ou le confirmer.' : '.' }}</div>@endif

{{-- Onglets --}}
<div class="rw-tabs" id="rwTabs">
    <button class="rw-tab active" onclick="rwTab(0)" data-i="0"><span class="tab-num">1</span> Informations</button>
    <button class="rw-tab" onclick="rwTab(1)" data-i="1"><span class="tab-num">2</span> Modules</button>
    <button class="rw-tab" onclick="rwTab(2)" data-i="2"><span class="tab-num">3</span> User Stories</button>
    <button class="rw-tab" onclick="rwTab(3)" data-i="3"><span class="tab-num">4</span> Retours</button>
    <button class="rw-tab" onclick="rwTab(4)" data-i="4"><span class="tab-num">5</span> KPIs</button>
    <button class="rw-tab" onclick="rwTab(5)" data-i="5"><span class="tab-num">6</span> D&eacute;cisions</button>
    <button class="rw-tab" onclick="rwTab(6)" data-i="6"><span class="tab-num">7</span> Signatures</button>
    <button class="rw-tab" onclick="rwTab(7)" data-i="7"><span class="tab-num">8</span> Conclusion</button>
</div>

<form method="POST" action="{{ route('comptes-rendus.review-sprint.update', $cr) }}" id="rwForm">
@csrf @method('PUT')
<input type="hidden" name="donnees_json" id="donneesJson">
<input type="hidden" name="apres" id="rwApres" value="enregistrer">

{{-- ====== TAB 1 : INFORMATIONS ====== --}}
<div class="rw-panel active" data-panel="0">
    <div class="fg row2">
        <div>
            <label>Titre</label>
            <input type="text" name="titre" value="{{ $cr->titre }}" {{ $estLecture ? 'readonly' : '' }}>
        </div>
        <div>
            <label>Date review</label>
            <input type="date" name="date_reunion" value="{{ $cr->date_reunion?->format('Y-m-d') }}" {{ $estLecture ? 'readonly' : '' }}>
        </div>
    </div>
    <div class="fg row3">
        <div>
            <label>Lieu</label>
            <input type="text" name="lieu" value="{{ $cr->lieu }}" {{ $estLecture ? 'readonly' : '' }}>
        </div>
        <div>
            <label>Heure d&eacute;but</label>
            <input type="time" name="heure_debut" value="{{ $cr->heure_debut ? substr($cr->heure_debut, 0, 5) : '' }}" {{ $estLecture ? 'readonly' : '' }}>
        </div>
        <div>
            <label>Heure fin</label>
            <input type="time" name="heure_fin" value="{{ $cr->heure_fin ? substr($cr->heure_fin, 0, 5) : '' }}" {{ $estLecture ? 'readonly' : '' }}>
        </div>
    </div>
    @if(!empty($donnees['objectif'] ?? $sprint?->objectif))
    <div style="border:1.5px solid #93c5fd;background:#f8fbff;border-radius:10px;padding:10px 14px;margin-bottom:14px">
        <div style="font-size:.72rem;font-weight:700;color:#2e74b5;text-transform:uppercase;letter-spacing:.4px;margin-bottom:3px">Objectif du sprint</div>
        <div style="font-size:.88rem;color:#64748b;white-space:pre-line">{{ $donnees['objectif'] ?? $sprint->objectif }}</div>
    </div>
    @endif
    @if ($peutValider || $user->estAdmin())
    <div class="fg">
        <label>R&eacute;dacteur</label>
        <select name="redacteur_id" {{ $estLecture ? 'disabled' : '' }}>
            @foreach ($tousUsers as $u)
            <option value="{{ $u->id }}" {{ $cr->redacteur_id == $u->id ? 'selected' : '' }}>{{ $u->nomComplet() }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:20px 0 8px">Participants</h3>
    <table class="tbl" id="tblPresences">
        <thead><tr><th>Collaborateur</th><th>Fonction</th><th style="width:80px">Pr&eacute;sent</th>@if (!$estLecture)<th style="width:40px"></th>@endif</tr></thead>
        <tbody id="presBody"></tbody>
    </table>
    @if (!$estLecture)
    <button type="button" class="btn-add" style="margin-top:8px" onclick="addPresence()">{!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter</button>
    @endif
</div>

{{-- ====== TAB 2 : MODULES ====== --}}
<div class="rw-panel" data-panel="1">
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:0 0 12px">Modules livr&eacute;s</h3>
    <table class="tbl" id="tblModules">
        <thead><tr><th style="width:40px">N&deg;</th><th>Module</th><th style="width:140px">Statut</th><th>Observation</th>@if (!$estLecture)<th style="width:40px"></th>@endif</tr></thead>
        <tbody id="modBody"></tbody>
    </table>
    @if (!$estLecture)
    <button type="button" class="btn-add" style="margin-top:8px" onclick="addModule()">{!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter module</button>
    @endif
</div>

{{-- ====== TAB 3 : USER STORIES BDD ====== --}}
<div class="rw-panel" data-panel="2">
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:0 0 12px">User Stories - Crit&egrave;res d'acceptation (BDD)</h3>
    <div id="usContainer"></div>
</div>

{{-- ====== TAB 4 : RETOURS ====== --}}
<div class="rw-panel" data-panel="3">
    <div class="fg">
        <label>Retour client / PO</label>
        @if($estLecture)<x-redaction lecture :value="$donnees['feedback']['client'] ?? null" />@else<x-redaction name="feedback_client" :value="$donnees['feedback']['client'] ?? null" placeholder="Appréciation du client / Product Owner sur les livrables présentés…" />@endif
    </div>
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:18px 0 8px">Points positifs</h3>
    <div class="fb-list" id="fbPositifs"></div>
    @if (!$estLecture)
    <button type="button" class="btn-add" style="margin-top:8px" onclick="addFb('positifs')">{!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter</button>
    @endif
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:18px 0 8px">Axes d'am&eacute;lioration</h3>
    <div class="fb-list" id="fbAmeliorations"></div>
    @if (!$estLecture)
    <button type="button" class="btn-add" style="margin-top:8px" onclick="addFb('ameliorations')">{!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter</button>
    @endif
</div>

{{-- ====== TAB 5 : KPIs ====== --}}
<div class="rw-panel" data-panel="4">
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:0 0 4px">Indicateurs cl&eacute;s du Sprint</h3>
    <div style="font-size:.78rem;color:#64748b;margin-bottom:14px">{{ $cr->estConfirme() ? 'Valeurs figées à la confirmation' . (!empty($donnees['kpis_figes_le']) ? ' (' . $donnees['kpis_figes_le'] . ')' : '') . '.' : 'Valeurs du jour, recalculées à chaque enregistrement et figées à la confirmation.' }}</div>
    @php $taux = $kpis['taux'] ?? 0; @endphp
    <div class="kpi-grid">
        <div class="kpi-card"><div class="kval">{{ $kpis['taches'] ?? 0 }}</div><div class="klab">T&acirc;ches totales</div></div>
        <div class="kpi-card"><div class="kval">{{ $kpis['realisees'] ?? 0 }}</div><div class="klab">R&eacute;alis&eacute;es</div></div>
        <div class="kpi-card"><div class="kval" style="color:{{ $taux >= 75 ? '#16a34a' : ($taux >= 50 ? '#B8860B' : '#dc2626') }}">{{ $taux }}%</div><div class="klab">Taux de r&eacute;alisation</div></div>
        <div class="kpi-card"><div class="kval">{{ $kpis['points_faits'] ?? 0 }}/{{ $kpis['points'] ?? 0 }}</div><div class="klab">Story points</div></div>
        <div class="kpi-card"><div class="kval">{{ ($kpis['stories_terminees'] ?? 0) }}/{{ $kpis['stories'] ?? 0 }}</div><div class="klab">User stories termin&eacute;es</div></div>
        <div class="kpi-card"><div class="kval" style="color:#dc2626">{{ $kpis['en_retard'] ?? 0 }}</div><div class="klab">En retard</div></div>
        <div class="kpi-card"><div class="kval" style="color:#dc2626">{{ $kpis['blocages_ouverts'] ?? 0 }}</div><div class="klab">Blocages ouverts</div></div>
        <div class="kpi-card"><div class="kval">{{ $kpis['dailies'] ?? 0 }}</div><div class="klab">Dailies tenus</div></div>
        <div class="kpi-card"><div class="kval">{{ $kpis['hors_sprint'] ?? 0 }}</div><div class="klab">Hors sprint</div></div>
    </div>
</div>

{{-- ====== TAB 6 : DECISIONS ====== --}}
<div class="rw-panel" data-panel="5">
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:0 0 12px">D&eacute;cisions et actions &agrave; mener</h3>
    <table class="tbl" id="tblDecisions">
        <thead><tr><th style="width:40px">N&deg;</th><th>Action / D&eacute;cision</th><th style="width:160px">Responsable(s)</th><th style="width:100px">Priorit&eacute;</th><th style="width:110px">&Eacute;ch&eacute;ance</th>@if (!$estLecture)<th style="width:40px"></th>@endif</tr></thead>
        <tbody id="decBody"></tbody>
    </table>
    @if (!$estLecture)
    <button type="button" class="btn-add" style="margin-top:8px" onclick="addDecision()">{!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter</button>
    @endif
</div>

{{-- ====== TAB 7 : SIGNATURES ====== --}}
<div class="rw-panel" data-panel="6">
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:0 0 12px">Signataires</h3>
    <table class="tbl" id="tblSig">
        <thead><tr><th>Nom</th><th>R&ocirc;le / Fonction</th><th style="width:130px">Date</th>@if (!$estLecture)<th style="width:40px"></th>@endif</tr></thead>
        <tbody id="sigBody"></tbody>
    </table>
    @if (!$estLecture)
    <button type="button" class="btn-add" style="margin-top:8px" onclick="addSignature()">{!! $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',14) !!} Ajouter signataire</button>
    @endif
</div>

{{-- ====== TAB 8 : EXPORT ====== --}}
<div class="rw-panel" data-panel="7">
    <h3 style="font-size:1rem;font-weight:700;color:var(--bleu);margin:0 0 12px">Conclusion et document</h3>
    <div class="fg">
        <label>Conclusion g&eacute;n&eacute;rale</label>
        @if($estLecture)<x-redaction lecture :value="$donnees['conclusion'] ?? null" />@else<x-redaction name="conclusion" :value="$donnees['conclusion'] ?? null" hauteur="120" />@endif
    </div>
    @if ($cr->estConfirme() && $cr->fichier_path)
    <div style="background:#E8F5E9;border-radius:10px;padding:14px 18px;margin-bottom:14px;display:flex;align-items:center;gap:10px">
        {!! $ic('<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',18) !!}
        <span style="font-size:.88rem;color:#2E7D32;font-weight:600">Document DOCX g&eacute;n&eacute;r&eacute;</span>
        <a href="{{ route('comptes-rendus.telecharger', $cr) }}" class="btn-act btn-dl" style="margin-left:auto;padding:6px 16px;font-size:.82rem">
            {!! $ic('<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',14) !!} T&eacute;l&eacute;charger
        </a>
    </div>
    @endif
</div>

{{-- Footer --}}
<div class="rw-footer">
    <a href="{{ route('comptes-rendus.index') }}" class="btn-act btn-back">{!! $ic('<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',14) !!} Retour</a>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        @if (!$estLecture)
        <button type="button" class="btn-act btn-save" onclick="envoyer('enregistrer')">{!! $ic('<path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',14) !!} Enregistrer</button>
        @endif
        @if (!$estLecture && $cr->estBrouillon())
        <button type="button" class="btn-act btn-dl" onclick="envoyer('transmettre','Transmettre ce compte rendu au responsable du projet pour relecture ?')">Transmettre pour relecture</button>
        @endif
        @if ($peutValider && $cr->estEnRevision())
        <button type="button" class="btn-act btn-back" onclick="envoyer('renvoyer','Renvoyer ce compte rendu en brouillon au rédacteur ?')">Renvoyer au r&eacute;dacteur</button>
        @endif
        @if ($peutValider)
        <button type="button" class="btn-act btn-confirm" onclick="envoyer('confirmer','Confirmer le CR Review Sprint ? Les indicateurs seront figés, le DOCX généré et les critères d\'acceptation reportés sur les user stories.')">{!! $ic('<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',14) !!} Confirmer + DOCX</button>
        @endif
        @if ($cr->estConfirme() && $cr->fichier_path)
        <a href="{{ route('comptes-rendus.telecharger', $cr) }}" class="btn-act btn-dl">{!! $ic('<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',14) !!} DOCX</a>
        @endif
    </div>
</div>
</form>

</div>

<script>
const RO = @json($estLecture);
const D = @json($donnees);
const MOSCOW = @json($moscowOpts);
const membres = @json($membres->map(fn($u) => ['id'=>$u->id,'nom'=>$u->nomComplet(),'poste'=>$u->titre_poste??$u->roleLibelle()]));

// ========== TABS ==========
function rwTab(i){
    document.querySelectorAll('.rw-tab').forEach(t=>t.classList.toggle('active',+t.dataset.i===i));
    document.querySelectorAll('.rw-panel').forEach(p=>p.classList.toggle('active',+p.dataset.panel===i));
}

// ========== COLLECT DATA ==========
function collectData(){
    let d = JSON.parse(JSON.stringify(D));
    // Presences
    d.presences = [];
    document.querySelectorAll('#presBody tr').forEach(tr=>{
        d.presences.push({
            nom: tr.querySelector('.p-nom')?.value||'',
            fonction: tr.querySelector('.p-fn')?.value||'',
            present: tr.querySelector('.p-chk')?.checked??true
        });
    });
    // Modules
    d.modules_livres = [];
    document.querySelectorAll('#modBody tr').forEach(tr=>{
        d.modules_livres.push({
            nom: tr.querySelector('.m-nom')?.value||'',
            statut: tr.querySelector('.m-st')?.value||'non_livre',
            observation: tr.querySelector('.m-obs')?.value||''
        });
    });
    // User Stories BDD
    d.user_stories_bdd = [];
    document.querySelectorAll('.us-card').forEach(card=>{
        let us = {
            us_id: card.dataset.usid||'',
            numero: card.querySelector('.us-num')?.textContent||'',
            intitule: card.querySelector('.us-int')?.textContent||'',
            module: card.dataset.module||'',
            moscow: card.querySelector('.us-moscow')?.value||'should',
            verdict: card.dataset.verdict||'partielle',
            observation: card.querySelector('.us-obs')?.value||'',
            niveau: card.dataset.niveau === '' ? null : +card.dataset.niveau,
            niveau_origine: card.dataset.origine||'calcule',
            epic: card.dataset.epic||'',
            criteres: []
        };
        card.querySelectorAll('.crit-row').forEach(cr=>{
            us.criteres.push({
                given: cr.querySelector('.c-given')?.value||'',
                when: cr.querySelector('.c-when')?.value||'',
                then: cr.querySelector('.c-then')?.value||'',
                statut: cr.querySelector('.c-stat')?.value||'na'
            });
        });
        d.user_stories_bdd.push(us);
    });
    // Feedback
    d.feedback = {
        positifs: [],
        ameliorations: []
    };
    document.querySelectorAll('#fbPositifs .fb-inp').forEach(inp=>{ if(inp.value.trim()) d.feedback.positifs.push(inp.value); });
    document.querySelectorAll('#fbAmeliorations .fb-inp').forEach(inp=>{ if(inp.value.trim()) d.feedback.ameliorations.push(inp.value); });
    // Decisions
    d.decisions = [];
    document.querySelectorAll('#decBody tr').forEach(tr=>{
        d.decisions.push({
            action: tr.querySelector('.d-act')?.value||'',
            responsables: (tr.querySelector('.d-resp')?.value||'').split(',').map(s=>s.trim()).filter(Boolean),
            priorite: tr.querySelector('.d-pri')?.value||'',
            echeance: tr.querySelector('.d-ech')?.value||''
        });
    });
    // Signatures
    d.signatures = [];
    document.querySelectorAll('#sigBody tr').forEach(tr=>{
        d.signatures.push({
            nom: tr.querySelector('.s-nom')?.value||'',
            role: tr.querySelector('.s-role')?.value||'',
            date: tr.querySelector('.s-date')?.value||''
        });
    });
    d.format = 'review_v1';
    return d;
}

// ========== ENREGISTRER / CIRCUIT ==========
// Un seul envoi : enregistrement puis, côté serveur, l'étape demandée (transmettre, renvoyer, confirmer)
function envoyer(apres, question){
    if(question && !confirm(question)) return;
    document.getElementById('rwApres').value = apres;
    document.getElementById('donneesJson').value = JSON.stringify(collectData());
    document.getElementById('rwForm').submit();
}

// ========== PRESENCES ==========
function renderPresences(){
    let h='';
    (D.presences||[]).forEach((p,i)=>{
        h+=`<tr>
            <td><input type="text" class="p-nom" value="${esc(p.nom)}" ${RO?'readonly':''}></td>
            <td><input type="text" class="p-fn" value="${esc(p.fonction)}" ${RO?'readonly':''}></td>
            <td style="text-align:center"><input type="checkbox" class="chk p-chk" ${p.present?'checked':''} ${RO?'disabled':''}></td>
            ${RO?'':`<td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`}
        </tr>`;
    });
    document.getElementById('presBody').innerHTML=h;
}
function addPresence(){
    let tr=document.createElement('tr');
    tr.innerHTML=`<td><input type="text" class="p-nom" value=""></td><td><input type="text" class="p-fn" value=""></td><td style="text-align:center"><input type="checkbox" class="chk p-chk" checked></td><td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`;
    document.getElementById('presBody').appendChild(tr);
}

// ========== MODULES ==========
function renderModules(){
    let h='';
    (D.modules_livres||[]).forEach((m,i)=>{
        h+=`<tr>
            <td>${i+1}</td>
            <td><input type="text" class="m-nom" value="${esc(m.nom)}" ${RO?'readonly':''}></td>
            <td><select class="m-st" ${RO?'disabled':''}><option value="livre" ${m.statut==='livre'?'selected':''}>Livré</option><option value="partiel" ${m.statut==='partiel'?'selected':''}>Partiel</option><option value="non_livre" ${m.statut==='non_livre'?'selected':''}>Non livré</option></select></td>
            <td><input type="text" class="m-obs" value="${esc(m.observation||'')}" ${RO?'readonly':''}></td>
            ${RO?'':`<td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`}
        </tr>`;
    });
    document.getElementById('modBody').innerHTML=h;
}
function addModule(){
    let b=document.getElementById('modBody'),n=b.rows.length+1;
    let tr=document.createElement('tr');
    tr.innerHTML=`<td>${n}</td><td><input type="text" class="m-nom" value=""></td><td><select class="m-st"><option value="livre">Livré</option><option value="partiel">Partiel</option><option value="non_livre" selected>Non livré</option></select></td><td><input type="text" class="m-obs" value=""></td><td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`;
    b.appendChild(tr);
}

// ========== USER STORIES BDD ==========
function renderUS(){
    let c=document.getElementById('usContainer');c.innerHTML='';
    (D.user_stories_bdd||[]).forEach((us,i)=>{
        let card=document.createElement('div');
        card.className='bdd-card us-card';
        card.dataset.usid=us.us_id||'';
        card.dataset.module=us.module||'';
        card.dataset.verdict=us.verdict||'partielle';
        card.dataset.niveau=(us.niveau ?? '') === '' ? '' : us.niveau;
        card.dataset.origine=us.niveau_origine||'calcule';
        card.dataset.epic=us.epic||'';
        let moscowSel='';
        Object.entries(MOSCOW).forEach(([k,v])=>{moscowSel+=`<option value="${k}" ${(us.moscow||'should')===k?'selected':''}>${v}</option>`;});
        let critHtml='';
        (us.criteres||[]).forEach((cr,ci)=>{
            critHtml+=critRow(cr,ci);
        });
        card.innerHTML=`
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;margin-bottom:10px">
                <h4><span class="us-num">${esc(us.numero)}</span> - <span class="us-int">${esc(us.intitule)}</span>${(us.niveau ?? null) !== null ? `<small style="display:block;font-weight:500;color:#64748b;font-size:.75rem;margin-top:2px">${esc(us.module)}${us.epic ? ' › ' + esc(us.epic) : ''} · exécution ${us.niveau} %${us.niveau_origine === 'declare' ? ' (déclaré)' : ''}</small>` : ''}</h4>
                <div style="display:flex;gap:8px;align-items:center">
                    <label style="font-size:.78rem;font-weight:600;color:#64748b">MoSCoW</label>
                    <select class="us-moscow" style="padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.82rem" ${RO?'disabled':''}>${moscowSel}</select>
                </div>
            </div>
            <div class="crit-container">${critHtml}</div>
            ${RO?'':`<button type="button" class="btn-add" style="margin:6px 0" onclick="addCritere(this)">+ Critère</button>`}
            <div class="verdict-row">
                <span style="font-size:.82rem;font-weight:600;color:#475569">Verdict :</span>
                <button type="button" class="verdict-btn ${us.verdict==='acceptee'?'sel-acceptee':''}" onclick="setVerdict(this,'acceptee')" ${RO?'disabled':''}>Acceptée</button>
                <button type="button" class="verdict-btn ${us.verdict==='partielle'?'sel-partielle':''}" onclick="setVerdict(this,'partielle')" ${RO?'disabled':''}>Partielle</button>
                <button type="button" class="verdict-btn ${us.verdict==='refusee'?'sel-refusee':''}" onclick="setVerdict(this,'refusee')" ${RO?'disabled':''}>Refusée</button>
            </div>
            <div class="fg" style="margin-top:8px">
                <label style="font-size:.78rem">Observation</label>
                <textarea class="us-obs" rows="2" ${RO?'readonly':''}>${esc(us.observation||'')}</textarea>
            </div>
        `;
        c.appendChild(card);
    });
}
function critRow(cr,ci){
    return `<div class="bdd-crit crit-row">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
            <span style="font-size:.78rem;font-weight:700;color:#64748b">Critère ${ci+1}</span>
            <div style="display:flex;gap:6px;align-items:center">
                <select class="c-stat" style="padding:3px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.78rem" ${RO?'disabled':''}>
                    <option value="na" ${(cr.statut||'na')==='na'?'selected':''}>N/A</option>
                    <option value="valide" ${cr.statut==='valide'?'selected':''}>Validé</option>
                    <option value="echoue" ${cr.statut==='echoue'?'selected':''}>Échoué</option>
                </select>
                ${RO?'':`<button type="button" class="btn-rm" onclick="this.closest('.crit-row').remove()">&times;</button>`}
            </div>
        </div>
        <div class="bdd-kw">Given</div>
        <input type="text" class="c-given" value="${esc(cr.given||'')}" placeholder="Contexte initial..." ${RO?'readonly':''}>
        <div class="bdd-kw" style="margin-top:6px">When</div>
        <input type="text" class="c-when" value="${esc(cr.when||'')}" placeholder="Action effectuée..." ${RO?'readonly':''}>
        <div class="bdd-kw" style="margin-top:6px">Then</div>
        <input type="text" class="c-then" value="${esc(cr.then||'')}" placeholder="Résultat attendu..." ${RO?'readonly':''}>
    </div>`;
}
function addCritere(btn){
    let container=btn.previousElementSibling;
    let n=container.querySelectorAll('.crit-row').length;
    container.insertAdjacentHTML('beforeend',critRow({given:'',when:'',then:'',statut:'na'},n));
}
function setVerdict(btn,v){
    let card=btn.closest('.us-card');
    card.dataset.verdict=v;
    card.querySelectorAll('.verdict-btn').forEach(b=>{
        b.className='verdict-btn';
        if(b===btn) b.classList.add('sel-'+v);
    });
}

// ========== FEEDBACK ==========
function renderFeedback(){
    ['positifs','ameliorations'].forEach(type=>{
        let c=document.getElementById('fb'+type.charAt(0).toUpperCase()+type.slice(1));
        c.innerHTML='';
        let items=(D.feedback?.[type])||[''];
        items.forEach((val)=>{
            addFbItem(c,val);
        });
    });
}
function addFbItem(container,val){
    let div=document.createElement('div');div.className='fb-item';
    div.innerHTML=`<input type="text" class="fb-inp" value="${esc(val)}" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.85rem" ${RO?'readonly':''}>
    ${RO?'':`<button type="button" class="btn-rm" onclick="this.parentElement.remove()">&times;</button>`}`;
    container.appendChild(div);
}
function addFb(type){
    let c=document.getElementById('fb'+type.charAt(0).toUpperCase()+type.slice(1));
    addFbItem(c,'');
}

// ========== DECISIONS ==========
function renderDecisions(){
    let h='';
    (D.decisions||[]).forEach((d,i)=>{
        let resp=Array.isArray(d.responsables)?d.responsables.join(', '):(d.responsables||'');
        h+=`<tr>
            <td>${i+1}</td>
            <td><input type="text" class="d-act" value="${esc(d.action||'')}" ${RO?'readonly':''}></td>
            <td><input type="text" class="d-resp" value="${esc(resp)}" ${RO?'readonly':''}></td>
            <td><select class="d-pri" ${RO?'disabled':''}><option value="">--</option><option value="haute" ${d.priorite==='haute'?'selected':''}>Haute</option><option value="moyenne" ${d.priorite==='moyenne'?'selected':''}>Moyenne</option><option value="basse" ${d.priorite==='basse'?'selected':''}>Basse</option></select></td>
            <td><input type="date" class="d-ech" value="${esc(d.echeance||'')}" ${RO?'readonly':''}></td>
            ${RO?'':`<td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`}
        </tr>`;
    });
    document.getElementById('decBody').innerHTML=h;
}
function addDecision(){
    let b=document.getElementById('decBody'),n=b.rows.length+1;
    let tr=document.createElement('tr');
    tr.innerHTML=`<td>${n}</td><td><input type="text" class="d-act" value=""></td><td><input type="text" class="d-resp" value=""></td><td><select class="d-pri"><option value="">--</option><option value="haute">Haute</option><option value="moyenne">Moyenne</option><option value="basse">Basse</option></select></td><td><input type="date" class="d-ech" value=""></td><td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`;
    b.appendChild(tr);
}

// ========== SIGNATURES ==========
function renderSignatures(){
    let h='';
    (D.signatures||[]).forEach((s,i)=>{
        h+=`<tr>
            <td><input type="text" class="s-nom" value="${esc(s.nom||'')}" ${RO?'readonly':''}></td>
            <td><input type="text" class="s-role" value="${esc(s.role||'')}" ${RO?'readonly':''}></td>
            <td><input type="date" class="s-date" value="${esc(s.date||'')}" ${RO?'readonly':''}></td>
            ${RO?'':`<td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`}
        </tr>`;
    });
    document.getElementById('sigBody').innerHTML=h;
}
function addSignature(){
    let tr=document.createElement('tr');
    tr.innerHTML=`<td><input type="text" class="s-nom" value=""></td><td><input type="text" class="s-role" value=""></td><td><input type="date" class="s-date" value=""></td><td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()">&times;</button></td>`;
    document.getElementById('sigBody').appendChild(tr);
}

// ========== UTILS ==========
function esc(s){return String(s??'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

// ========== INIT ==========
document.addEventListener('DOMContentLoaded',()=>{
    renderPresences();
    renderModules();
    renderUS();
    renderFeedback();
    renderDecisions();
    renderSignatures();
});
</script>
@endsection
