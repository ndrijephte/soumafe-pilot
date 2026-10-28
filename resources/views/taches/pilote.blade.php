@extends('layouts.app')
@section('sousTitre', 'Pilotage des tâches')
@section('titre', 'Toutes les tâches - Pilote')
@push('styles')
<style>
.filtre-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;padding:14px 18px;background:var(--carte);border:1px solid var(--bord);border-radius:12px}
.filtre-bar select{padding:7px 12px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;background:#fff;color:#334155;min-width:130px}
.filtre-bar .btn-reset{padding:7px 14px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;background:#f1f5f9;color:#64748b;cursor:pointer;text-decoration:none}
.filtre-bar .btn-reset:hover{background:#e2e8f0}
.tbl-pilote{width:100%;border-collapse:collapse;font-size:12.5px;background:var(--carte);border:1px solid var(--bord);border-radius:12px;overflow:hidden}
.tbl-pilote th{text-align:left;padding:10px 12px;background:var(--bleu);color:#fff;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.3px}
.tbl-pilote td{padding:9px 12px;border-bottom:1px solid var(--bord)}
.tbl-pilote tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}.b-blue{background:#dbeafe;color:#1d4ed8}.b-gris{background:#f1f5f9;color:#64748b}
.lien{color:var(--accent);text-decoration:none;font-weight:500}
.lien:hover{text-decoration:underline}
.pagination-wrap{display:flex;justify-content:center;gap:4px;margin-top:16px}
.pagination-wrap .page-link{padding:6px 12px;border:1px solid var(--bord);border-radius:6px;font-size:12px;color:var(--bleu);text-decoration:none;background:#fff}
.pagination-wrap .page-item.active .page-link{background:var(--bleu);color:#fff;border-color:var(--bleu)}
.pagination-wrap .page-item.disabled .page-link{color:#cbd5e1}
.stat-bar{display:flex;gap:16px;margin-bottom:14px;font-size:12px;color:var(--gris)}
.stat-bar strong{color:var(--bleu)}
</style>
@endpush
@section('contenu')
<h2>Projets & Sprints</h2>
<div class="onglets-page">
    <a href="{{ route('sprints.index') }}">Projets / Sprints</a>
    <a href="{{ route('taches.pilote') }}" class="actif">Toutes les tâches</a>
</div>

<div class="stat-bar">
    <span>Total affiché : <strong>{{ $taches->total() }}</strong></span>
</div>

<form class="filtre-bar" method="GET" action="{{ route('taches.pilote') }}">
    <select name="collaborateur" onchange="this.form.submit()">
        <option value="">— Collaborateur —</option>
        @foreach($collaborateurs as $c)
        <option value="{{ $c->id }}" {{ ($filtres['collaborateur'] ?? '') == $c->id ? 'selected' : '' }}>{{ $c->nomComplet() }}</option>
        @endforeach
    </select>
    <select name="projet" onchange="this.form.submit()">
        <option value="">— Projet —</option>
        @foreach($projets as $p)
        <option value="{{ $p->id }}" {{ ($filtres['projet'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->libelle }}</option>
        @endforeach
    </select>
    <select name="statut" onchange="this.form.submit()">
        <option value="">— Statut —</option>
        @foreach(['a_faire' => 'À faire', 'en_cours' => 'En cours', 'bloque' => 'Bloqué', 'a_valider' => 'À valider', 'termine' => 'Terminé'] as $k => $v)
        <option value="{{ $k }}" {{ ($filtres['statut'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <select name="priorite" onchange="this.form.submit()">
        <option value="">— Priorité —</option>
        @foreach(['haute' => 'Haute', 'moyenne' => 'Moyenne', 'basse' => 'Basse'] as $k => $v)
        <option value="{{ $k }}" {{ ($filtres['priorite'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <select name="type" onchange="this.form.submit()">
        <option value="">— Type —</option>
        @foreach(['T1' => 'T1 (Sprint)', 'T2' => 'T2 (Récurrente)', 'T3' => 'T3 (Urgente)'] as $k => $v)
        <option value="{{ $k }}" {{ ($filtres['type'] ?? '') == $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    @if(array_filter($filtres))
    <a href="{{ route('taches.pilote') }}" class="btn-reset">Réinitialiser</a>
    @endif
</form>

<table class="tbl-pilote" data-collapse-table="15">
    <thead>
        <tr>
            <th>Intitulé</th>
            <th>Collaborateur</th>
            <th>Projet</th>
            <th>Type</th>
            <th>Priorité</th>
            <th>Début</th>
            <th>Échéance</th>
            <th>Statut</th>
        </tr>
    </thead>
    <tbody>
    @forelse($taches as $t)
    @php
        $statutCls = match($t->statut) { 'termine' => 'b-ok', 'en_cours' => 'b-ok', 'bloque' => 'b-ko', 'a_valider' => 'b-blue', default => 'b-warn' };
        $prioCls = match($t->priorite) { 'haute' => 'color:var(--ko);font-weight:700', 'basse' => 'color:var(--gris)', default => '' };
        $retard = $t->echeance && $t->echeance->lt(today()) && !in_array($t->statut, ['termine']);
    @endphp
    <tr>
        <td><a href="{{ route('taches.show', $t) }}" class="lien">{{ \Illuminate\Support\Str::limit($t->intitule, 45) }}</a></td>
        <td style="font-size:11.5px">{{ $t->user->nomComplet() }}</td>
        <td style="font-size:11px;color:var(--gris)">{{ $t->projet?->libelle ?? '—' }}</td>
        <td><span class="badge b-gris">{{ $t->type }}</span></td>
        <td style="{{ $prioCls }};font-size:12px">{{ ucfirst($t->priorite) }}</td>
        <td style="font-size:11px;color:var(--gris)">{{ $t->date_debut?->format('d/m') ?? '—' }}</td>
        <td style="font-size:11px;{{ $retard ? 'color:var(--ko);font-weight:700' : 'color:var(--gris)' }}">{{ $t->echeance?->format('d/m') ?? '—' }}@if($retard) ⚠@endif</td>
        <td><span class="badge {{ $statutCls }}">{{ $t->labelStatut() }}</span></td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;color:var(--gris);padding:24px">Aucune tâche trouvée.</td></tr>
    @endforelse
    </tbody>
</table>

@if($taches->hasPages())
<div class="pagination-wrap">
    @foreach($taches->links()->elements as $element)
        @if(is_string($element))
            <span class="page-item disabled"><span class="page-link">{{ $element }}</span></span>
        @endif
        @if(is_array($element))
            @foreach($element as $page => $url)
                <span class="page-item {{ $page == $taches->currentPage() ? 'active' : '' }}">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                </span>
            @endforeach
        @endif
    @endforeach
</div>
@endif
@endsection
