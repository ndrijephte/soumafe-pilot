@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Rétrospective - Sprint ' . $sprint->numero . ' - ' . $sprint->projet->libelle)

@push('styles')
<style>
.col-th{width:14%}.col-b{width:20%}.col-c{width:19%}.col-ac{width:21%}.col-x{width:52px}
.sc-tbl td.theme input{font-weight:700;color:var(--bleu)}
.grille4{display:grid;grid-template-columns:repeat(3,minmax(0,180px));gap:10px}
.aide-retro{font-size:12px;color:var(--gris);margin:-4px 0 12px}
@media(max-width:800px){.grille4{grid-template-columns:1fr}}
</style>
@endpush

@section('contenu')
@include('scrum._entete', ['sprint' => $sprint, 'actif' => 'retro', 'page' => 'Rétrospective'])
@php
    $valide = $cr?->estConfirme();
    $colonnes = ['bien', 'pas_bien', 'causes', 'actions'];
@endphp

<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
    @if(!$cr)<span class="pill p-attente">Rétrospective non commencée</span>
    @elseif($valide)<span class="pill p-ok">Rétrospective validée</span>
        <span style="font-size:12px;color:var(--gris)">Modifiable par un pilote uniquement.</span>
        @if(auth()->user()->estAdmin())<form method="POST" action="{{ route('scrum.rouvrir', [$sprint, 'retrospective']) }}" style="margin:0">@csrf<button class="btn sm sec">Rouvrir</button></form>@endif
    @else<span class="pill p-warn">Brouillon</span>@endif
    <span style="font-size:12px;color:var(--gris)">Rédacteur : <strong style="color:#1e293b">{{ $redacteur?->nomComplet() ?? '—' }}</strong> (scribe du jour au planning des réunions)</span>
</div>

<div class="indic">
    <div><b>{{ $ind['taux'] }} %</b><span>tâches réalisées ({{ $ind['realisees'] }}/{{ $ind['taches'] }})</span></div>
    <div class="{{ $ind['en_retard'] ? 'alerte-i' : '' }}"><b>{{ $ind['en_retard'] }}</b><span>tâche(s) en retard</span></div>
    <div class="{{ $ind['blocages_ouverts'] ? 'alerte-i' : '' }}"><b>{{ $ind['blocages'] }}</b><span>blocage(s) dont {{ $ind['blocages_ouverts'] }} ouvert(s)</span></div>
    <div><b>{{ $ind['stories_terminees'] }}/{{ $ind['stories'] }}</b><span>user stories terminées</span></div>
    <div><b>{{ $ind['dailies'] }}/{{ $ind['jours'] }}</b><span>Dailies tenus</span></div>
</div>

<form method="POST" action="{{ route('scrum.retro.update', $sprint) }}" id="f-retro">@csrf @method('PUT')
@if($canEdit)
<div class="carte" style="margin-bottom:14px">
    <div class="grille4">
        <div class="champ"><label>Date</label><input type="date" name="date_reunion" value="{{ ($cr?->date_reunion ?? ($sprint->date_fin->lt(today()) ? $sprint->date_fin : today()))->toDateString() }}"></div>
        <div class="champ"><label>Heure début</label><input type="time" name="heure_debut" value="{{ $cr?->heure_debut }}"></div>
        <div class="champ"><label>Heure fin</label><input type="time" name="heure_fin" value="{{ $cr?->heure_fin }}"></div>
    </div>
</div>
<p class="aide-retro">Une ligne par thème : ce qui a marché, ce qui n'a pas marché, causes et actions pour le prochain sprint.</p>
@endif

<div class="panneau">
<h3><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg> Analyse par thème</h3>
<table class="sc-tbl" id="t-retro">
    <thead><tr>
        <th class="col-th">Thème</th><th class="col-b">Ce qui a bien marché</th><th class="col-b">Ce qui n’a pas marché</th><th class="col-c">Causes</th><th class="col-ac">Actions à mettre en place</th>
        @if($canEdit)<th class="col-x"></th>@endif
    </tr></thead>
    <tbody>
    @foreach($lignes as $i => $l)
        <tr>
            @if($canEdit)
            <td class="theme"><input name="lignes[{{ $i }}][theme]" value="{{ $l['theme'] ?? '' }}" maxlength="255"></td>
            @foreach($colonnes as $c)<td><x-redaction compact name="lignes[{{ $i }}][{{ $c }}]" :value="$l[$c] ?? null" /></td>@endforeach
            <td class="ctr"><button type="button" class="btn sm ko" onclick="this.closest('tr').remove()" title="Retirer la ligne">✕</button></td>
            @else
            <td><strong style="color:var(--bleu)">{{ $l['theme'] ?? '' }}</strong></td>
            @foreach($colonnes as $c)<td><x-redaction lecture vide="" :value="$l[$c] ?? null" /></td>@endforeach
            @endif
        </tr>
    @endforeach
    </tbody>
</table>
</div>

@if($canEdit)
<div class="barre-actions">
    <button type="button" class="btn sm sec" onclick="ajouterLigne()">+ Ligne</button>
    <span style="flex:1"></span>
    <button class="btn" name="apres" value="enregistrer">Enregistrer</button>
    @if(!$valide && $canGerer)<button class="btn vert" name="apres" value="valider" onclick="return confirm('Valider la rétrospective ? Elle apparaîtra dans les comptes rendus.')">Enregistrer et valider</button>@endif
</div>
@endif
</form>

@if($canEdit)
<template id="tpl-retro">
    <tr>
        <td class="theme"><input name="lignes[__I__][theme]" maxlength="255" placeholder="Thème"></td>
        @foreach($colonnes as $c)<td><x-redaction compact name="lignes[__I__][{{ $c }}]" /></td>@endforeach
        <td class="ctr"><button type="button" class="btn sm ko" onclick="this.closest('tr').remove()" title="Retirer la ligne">✕</button></td>
    </tr>
</template>
@endif
@endsection

@if($canEdit)
@push('scripts')
<script>
let idxRetro = {{ count($lignes) }};
function ajouterLigne() {
    const corps = document.querySelector('#t-retro tbody');
    corps.insertAdjacentHTML('beforeend', document.getElementById('tpl-retro').innerHTML.replaceAll('__I__', idxRetro++));
    corps.lastElementChild.querySelector('input').focus();
}
</script>
@endpush
@endif
