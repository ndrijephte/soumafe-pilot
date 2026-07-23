@extends('layouts.app')
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
.taches-table{width:100%;border-collapse:collapse;font-size:13px}
.taches-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:11px;text-transform:uppercase;border-bottom:2px solid var(--bord)}
.taches-table td{padding:8px 10px;border-bottom:1px solid var(--bord)}
.taches-table tr:hover{background:#f8fafc}
.pill{padding:2px 9px;border-radius:10px;font-size:10.5px;font-weight:600}
.p-ok{background:#dcfce7;color:var(--ok)}.p-cours{background:#dbeafe;color:var(--accent)}.p-bloque{background:#fee2e2;color:var(--ko)}.p-attente{background:#fef3c7;color:#b45309}
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
@media(max-width:768px){.form-row{grid-template-columns:1fr}.sprint-header{flex-direction:column}}
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
    <div class="sprint-meta">
        <div><strong>Objectif :</strong> {{ $sprint->objectif }}</div>
        <div><strong>P&eacute;riode :</strong> {{ $sprint->date_debut->format('d/m/Y') }} &rarr; {{ $sprint->date_fin->format('d/m/Y') }}</div>
        @if($sprint->engagement_points)<div><strong>Points engag&eacute;s :</strong> {{ $sprint->engagement_points }}</div>@endif
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
    @if($sprint->statut !== 'termine')
    <form method="POST" action="{{ route('sprints.cloturer', $sprint) }}" style="margin:0" onsubmit="return confirm('Clôturer ce sprint ? Les tâches non terminées seront marquées en retard.')">
        @csrf
        <button class="btn" style="background:var(--ko)">Cl&ocirc;turer le sprint</button>
    </form>
    @endif
</div>
@endif

<div class="carte" style="padding:0;overflow-x:auto">
@if($totalTaches > 0)
<table class="taches-table">
<thead>
    <tr><th>T&acirc;che</th><th>Assign&eacute; &agrave;</th><th>Priorit&eacute;</th><th>D&eacute;but</th><th>&Eacute;ch&eacute;ance</th><th>Points</th><th>Statut</th></tr>
</thead>
<tbody>
@foreach($sprint->taches->sortByDesc('priorite') as $t)
<tr>
    <td>
        <span class="typ t1">T1</span>
        <a href="{{ route('taches.show', $t) }}" style="text-decoration:none;color:#1e293b;font-weight:500">{{ $t->intitule }}</a>
    </td>
    <td>{{ $t->user?->nomComplet() ?? '—' }}</td>
    <td>
        @php $pCouleur = match($t->priorite) { 'haute' => 'var(--ko)', 'basse' => 'var(--gris)', default => 'var(--accent)' }; @endphp
        <span style="color:{{ $pCouleur }};font-weight:600;font-size:12px">{{ ucfirst($t->priorite) }}</span>
    </td>
    <td style="font-size:12px">{{ $t->date_debut?->format('d/m') ?? '—' }}</td>
    <td style="font-size:12px">{{ $t->echeance?->format('d/m') ?? '—' }}</td>
    <td style="text-align:center">{{ $t->points ?? '—' }}</td>
    <td>
        @php $sClasse = match($t->statut) { 'termine' => 'p-ok', 'en_cours' => 'p-cours', 'bloque' => 'p-bloque', default => 'p-attente' }; @endphp
        <span class="pill {{ $sClasse }}">{{ ucfirst(str_replace('_',' ',$t->statut)) }}</span>
    </td>
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
    <p style="font-size:12px;color:var(--gris);margin-bottom:12px;line-height:1.5">
        Colonnes attendues : <strong>Projet, Intitul&eacute;, Assign&eacute; &agrave;, Date d&eacute;but, &Eacute;ch&eacute;ance, Priorit&eacute;, Points</strong>.<br>
        Les t&acirc;ches seront rattach&eacute;es au <strong>Sprint {{ $sprint->numero }}</strong>.
    </p>
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
@endif
@endsection
