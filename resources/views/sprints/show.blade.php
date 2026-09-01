@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Sprint ' . $sprint->numero . ' — ' . $sprint->projet->libelle)
@push('styles')
<style>
.sprint-header{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:18px}
.sprint-meta{font-size:13px;color:var(--gris);line-height:1.8}
.sprint-meta strong{color:#1e293b}
.badge-resp{display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#dbeafe,#eff6ff);border:1px solid #93c5fd;color:#1d4ed8;padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600}
.badge-resp svg{flex:none}
.badge-statut{padding:4px 12px;border-radius:8px;font-size:12px;font-weight:600}
.s-planifie{background:#e2e8f0;color:#475569}.s-en_cours{background:#dbeafe;color:#1d4ed8}.s-termine{background:#dcfce7;color:var(--ok)}
.progress-wrap{background:#e2e8f0;border-radius:6px;height:10px;overflow:hidden;margin:8px 0}
.progress-fill{height:100%;border-radius:6px;background:var(--accent);transition:width .5s}
.stats-row{display:flex;gap:20px;font-size:13px;color:var(--gris);margin-bottom:18px;flex-wrap:wrap}
.stats-row strong{color:#1e293b}
.taches-table{width:100%;border-collapse:collapse;font-size:13px;table-layout:fixed}
.taches-table th{text-align:left;padding:10px 14px;background:var(--fond);color:var(--gris);font-weight:600;font-size:11px;text-transform:uppercase;border-bottom:2px solid var(--bord);white-space:nowrap}
.taches-table td{padding:10px 14px;border-bottom:1px solid var(--bord);vertical-align:middle}
.taches-table tr:hover{background:#f8fafc}
.col-tache{width:48%}.col-assigne{width:12%}.col-prio{width:7%}.col-debut{width:7%}.col-ech{width:7%}.col-pts{width:5%;text-align:center}.col-statut{width:8%}.col-edit{width:6%;text-align:center}
.pill{padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.p-ok{background:#dcfce7;color:var(--ok)}.p-cours{background:#dbeafe;color:var(--accent)}.p-bloque{background:#fee2e2;color:var(--ko)}.p-attente{background:#fef3c7;color:#b45309}
.prio-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px;vertical-align:middle}
.typ{font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-right:4px}.t1{background:#dbeafe;color:#1d4ed8}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.actions-bar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:500px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
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
@media(max-width:768px){.form-row{grid-template-columns:1fr}.sprint-header{flex-direction:column}.filtre-bar{flex-direction:column}.taches-table{table-layout:auto}.col-tache,.col-assigne,.col-prio,.col-debut,.col-ech,.col-pts,.col-statut,.col-edit{width:auto}}
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
        <h2>Sprint {{ $sprint->numero }} — {{ $sprint->projet->libelle }}</h2>
        <div class="crumb"><a href="{{ route('sprints.index') }}" style="color:var(--accent);text-decoration:none">Projets / Sprints</a> &rsaquo; Sprint {{ $sprint->numero }}</div>
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

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

<div class="carte" style="margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div class="sprint-meta">
            <div><strong>Objectif :</strong> {{ $sprint->objectif }}</div>
            <div><strong>P&eacute;riode :</strong> {{ $sprint->date_debut->format('d/m/Y') }} &rarr; {{ $sprint->date_fin->format('d/m/Y') }}</div>
            @if($sprint->engagement_points)<div><strong>Points engag&eacute;s :</strong> {{ $sprint->engagement_points }}</div>@endif
        </div>
        @if($canEditSprint)
        <div style="display:flex;gap:6px;align-items:center">
            @if($sprint->statut !== 'termine')
            <button class="btn-edit-sprint" onclick="document.getElementById('dlg-edit-sprint').showModal()" title="Modifier le sprint"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Modifier</button>
            @endif
            @if($sprint->statut !== 'en_cours')
            <form method="POST" action="{{ route('sprints.destroy', $sprint) }}" style="margin:0" onsubmit="return confirm('Supprimer ce sprint et toutes ses tâches ? Cette action est irréversible.')">@csrf @method('DELETE')
                <button type="submit" class="btn-edit-sprint" style="border-color:var(--ko);color:var(--ko)" onmouseover="this.style.background='var(--ko)';this.style.color='#fff'" onmouseout="this.style.background='none';this.style.color='var(--ko)'" title="Supprimer le sprint"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg> Supprimer</button>
            </form>
            @endif
        </div>
        @endif
    </div>
    <div class="progress-wrap"><div class="progress-fill" style="width:{{ $pct }}%"></div></div>
    <div class="stats-row">
        <div>T&acirc;ches : <strong>{{ $totalTaches }}</strong></div>
        <div>Termin&eacute;es : <strong>{{ $terminees }}</strong></div>
        <div>En cours : <strong>{{ $enCours }}</strong></div>
        <div>Bloqu&eacute;es : <strong>{{ $bloquees }}</strong></div>
        <div>Points livr&eacute;s : <strong>{{ $sprint->pointsLivres() }}</strong>@if($sprint->engagement_points) / {{ $sprint->engagement_points }}@endif</div>
        <div>Progression : <strong>{{ $pct }}%</strong></div>
    </div>
</div>

@if($canEdit)
<div class="actions-bar">
    <button class="btn" onclick="document.getElementById('dlg-tache').showModal()">+ Ajouter une t&acirc;che</button>
    <button class="btn sec" onclick="document.getElementById('dlg-import').showModal()">Importer (.xlsx)</button>
    @if($sprint->statut === 'planifie')
    <form method="POST" action="{{ route('sprints.demarrer', $sprint) }}" style="margin:0" onsubmit="return confirm('Démarrer ce sprint ? Les tâches deviendront exécutables par les collaborateurs.')">
        @csrf
        <button class="btn" style="background:var(--ok)">D&eacute;marrer le sprint</button>
    </form>
    @endif
    @if($sprint->statut === 'en_cours')
    <button class="btn" style="background:var(--warn);color:#fff" onclick="document.getElementById('dlg-arreter').showModal()">Arr&ecirc;ter le sprint</button>
    <form method="POST" action="{{ route('sprints.cloturer', $sprint) }}" style="margin:0" onsubmit="return confirm('Clôturer ce sprint ? Les tâches non terminées seront marquées en retard.')">
        @csrf
        <button class="btn" style="background:var(--ko)">Cl&ocirc;turer le sprint</button>
    </form>
    @endif
    @if(in_array($sprint->statut, ['en_cours', 'termine']))
    <a href="{{ route('comptes-rendus.create', ['type' => 'demo_sprint', 'sprint_id' => $sprint->id]) }}" class="btn" style="background:#059669;color:#fff">CR D&eacute;mo</a>
    @endif
</div>
@endif

@if($sprint->statut === 'planifie')
<div style="background:#e0f2fe;border:1px solid #7dd3fc;border-radius:8px;padding:10px 14px;font-size:13px;color:#0369a1;margin-bottom:12px">
    <strong>Sprint en pr&eacute;paration</strong> — les t&acirc;ches sont consultables mais non ex&eacute;cutables. D&eacute;marrez le sprint pour activer l'ex&eacute;cution.
</div>
@elseif($sprint->statut === 'termine')
<div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:8px;padding:10px 14px;font-size:13px;color:#475569;margin-bottom:12px">
    <strong>Sprint termin&eacute;</strong> — consultation uniquement.
    @if($sprint->justification_arret)
    <br>Motif d'arr&ecirc;t : <em>{{ $sprint->justification_arret }}</em>
    @endif
</div>
@endif

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
    <select id="fStatut" onchange="filtrerSprint()">
        <option value="">Tous statuts</option>
        <option value="a_faire">&Agrave; faire</option>
        <option value="en_cours">En cours</option>
        <option value="a_valider">En cours de validation</option>
        <option value="bloque">Bloqu&eacute;</option>
        <option value="termine">Termin&eacute;</option>
    </select>
</div>
<div class="carte" style="padding:0;overflow-x:auto">
<table class="taches-table" data-collapse-table="10">
<thead>
    <tr>
        <th class="col-tache">T&acirc;che</th>
        <th class="col-assigne">Assign&eacute;</th>
        <th class="col-prio">Prio.</th>
        <th class="col-debut">D&eacute;but</th>
        <th class="col-ech">&Eacute;ch.</th>
        <th class="col-pts">Pts</th>
        <th class="col-statut">Statut</th>
        @if($canEditSprint && $sprint->statut !== 'termine')<th class="col-edit"></th>@endif
    </tr>
</thead>
<tbody>
@foreach($sprint->taches->sortByDesc('priorite') as $t)
<tr data-row data-nom="{{ strtolower($t->intitule) }}" data-user="{{ $t->user_id }}" data-prio="{{ $t->priorite }}" data-statut="{{ $t->statut }}" @if($sprint->statut !== 'en_cours') style="opacity:0.55" @endif>
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
    <td style="font-size:12px;color:#475569">{{ $t->echeance?->format('d/m') ?? '—' }}</td>
    <td style="text-align:center;font-weight:600;font-size:12px">{{ $t->points ?? '—' }}</td>
    <td>
        @php $sClasse = match($t->statut) { 'termine' => 'p-ok', 'en_cours' => 'p-cours', 'bloque' => 'p-bloque', default => 'p-attente' }; @endphp
        <span class="pill {{ $sClasse }}">{{ $t->labelStatut() }}</span>
    </td>
    @if($canEditSprint && $sprint->statut !== 'termine')
    <td style="text-align:center;white-space:nowrap">
        <button class="btn-edit-row" onclick="ouvrirEditTache({{ $t->id }}, '{{ addslashes($t->intitule) }}', {{ $t->user_id ?? 'null' }}, '{{ $t->priorite }}', '{{ $t->date_debut?->format('Y-m-d') ?? '' }}', '{{ $t->echeance?->format('Y-m-d') ?? '' }}', {{ $t->points ?? 'null' }})" title="Modifier"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></button>
        <form method="POST" action="{{ route('sprints.supprimer-tache', [$sprint, $t]) }}" style="display:inline;margin:0" onsubmit="return confirm('Supprimer la tâche « {{ addslashes($t->intitule) }} » ?')">@csrf @method('DELETE')
            <button type="submit" class="btn-edit-row" style="color:var(--ko)" title="Supprimer"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button>
        </form>
    </td>
    @endif
</tr>
@endforeach
</tbody>
</table>
@else
<div class="vide">
    <p>Aucune t&acirc;che dans ce sprint.</p>
    @if($canEdit)
    <p style="margin-top:8px;font-size:13px">Ajoutez des t&acirc;ches manuellement ou importez un fichier xlsx.</p>
    @endif
</div>
@endif
</div>

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
        <div class="form-row">
            <div>
                <label>Assign&eacute; &agrave; <span class="req">*</span></label>
                <select name="user_id" required>
                    <option value="">— Choisir —</option>
                    @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach
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
            <div><label>Date d&eacute;but</label><input type="date" name="date_debut"></div>
            <div><label>&Eacute;ch&eacute;ance</label><input type="date" name="echeance"></div>
        </div>
        <label>Points (story points)</label>
        <input type="number" name="points" min="1" placeholder="Ex : 3">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Ajouter</button>
        </div>
    </form>
</dialog>

{{-- Dialog import xlsx --}}
<dialog id="dlg-import">
    <h3 style="color:var(--bleu);margin-bottom:4px">Importer des t&acirc;ches (.xlsx)</h3>
    <div style="font-size:12px;color:var(--gris);margin-bottom:14px;line-height:1.6">
        <p style="margin-bottom:8px">Les t&acirc;ches seront rattach&eacute;es au <strong>Sprint {{ $sprint->numero }}</strong>.</p>
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
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">4</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Date d&eacute;but</td><td style="padding:3px 6px;border:1px solid var(--bord);color:var(--ko)">Oui</td><td style="padding:3px 6px;border:1px solid var(--bord)">28/07/2026</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">5</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">&Eacute;ch&eacute;ance</td><td style="padding:3px 6px;border:1px solid var(--bord);color:var(--ko)">Oui</td><td style="padding:3px 6px;border:1px solid var(--bord)">01/08/2026</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">6</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Points</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">3</td></tr>
                    <tr><td style="padding:3px 6px;border:1px solid var(--bord)">7</td><td style="padding:3px 6px;border:1px solid var(--bord);font-weight:600">Projet</td><td style="padding:3px 6px;border:1px solid var(--bord)">Non</td><td style="padding:3px 6px;border:1px solid var(--bord)">CROU Connect</td></tr>
                </tbody>
            </table>
            <div style="margin-top:6px;font-size:10.5px;color:#64748b">
                &bull; L'en-t&ecirc;te doit se trouver en <strong>ligne 1</strong> du fichier.<br>
                &bull; L'ordre des colonnes est <strong>libre</strong> &mdash; le syst&egrave;me reconna&icirc;t les noms.<br>
                &bull; Si &laquo; Assign&eacute; &agrave; &raquo; ne correspond &agrave; aucun collaborateur, la ligne sera ignor&eacute;e.<br>
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
        <label>Points engag&eacute;s</label>
        <input type="number" name="engagement_points" min="1" value="{{ $sprint->engagement_points }}">
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
        <div class="form-row">
            <div>
                <label>Assign&eacute; &agrave; <span class="req">*</span></label>
                <select name="user_id" id="et-user" required>
                    <option value="">— Choisir —</option>
                    @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }}</option>@endforeach
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
function ouvrirEditTache(id, intitule, userId, priorite, debut, echeance, points){
    document.getElementById('form-edit-tache').action = '/sprints/{{ $sprint->id }}/taches/' + id;
    document.getElementById('et-intitule').value = intitule;
    document.getElementById('et-user').value = userId || '';
    document.getElementById('et-priorite').value = priorite;
    document.getElementById('et-debut').value = debut;
    document.getElementById('et-echeance').value = echeance;
    document.getElementById('et-points').value = points || '';
    document.getElementById('dlg-edit-tache').showModal();
}
@endif
function filtrerSprint(){
    const nom=document.getElementById('fNom').value.toLowerCase();
    const user=document.getElementById('fAssigne').value;
    const prio=document.getElementById('fPrio').value;
    const statut=document.getElementById('fStatut').value;
    document.querySelectorAll('[data-row]').forEach(tr=>{
        let ok=true;
        if(nom && !tr.dataset.nom.includes(nom)) ok=false;
        if(user && tr.dataset.user!==user) ok=false;
        if(prio && tr.dataset.prio!==prio) ok=false;
        if(statut && tr.dataset.statut!==statut) ok=false;
        tr.style.display=ok?'':'none';
    });
}
</script>
@endpush
