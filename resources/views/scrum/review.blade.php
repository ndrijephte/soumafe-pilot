@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Review - Sprint ' . $sprint->numero . ' - ' . $sprint->projet->libelle)

@push('styles')
<style>
.col-n{width:8%}.col-t{width:23%}.col-a{width:12%}.col-d{width:8%}.col-du{width:6%}.col-p{width:5%}.col-s{width:12%}.col-o{width:26%}
.sc-tbl td.ctr{text-align:center;white-space:nowrap}
.st-ok{color:#166534;font-weight:700}.st-ko{color:#991b1b;font-weight:700}.st-cours{color:#1d4ed8;font-weight:700}.st-attente{color:#475569;font-weight:700}
.statut-cr{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px}
.grille2{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px}
.grille4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.bloc-titre{font-size:14px;font-weight:700;color:var(--bleu);margin:0 0 4px;display:flex;align-items:center;gap:6px}
.bloc-aide{font-size:12px;color:var(--gris);margin:0 0 10px}
.bk-us{font-size:11.5px;font-weight:600;color:#166534;text-decoration:none;white-space:nowrap}
.col-bm{width:20%}.col-bi{width:32%}.col-be{width:32%}.col-bs{width:10%}.col-bx{width:52px}
@media(max-width:800px){.grille2,.grille4{grid-template-columns:1fr}}
</style>
@endpush

@section('contenu')
@include('scrum._entete', ['sprint' => $sprint, 'actif' => 'review', 'page' => 'Review'])
@php
    $obs = $donnees['observations'] ?? [];
    $valide = $cr?->estConfirme();
    $bkLignes = $donnees['backlog'] ?? [];
    $enAttente = collect($bkLignes)->filter(fn($l) => empty($l['user_story_id']) && trim($l['intitule'] ?? '') !== '')->count();
@endphp

<div class="statut-cr">
    @if(!$cr)<span class="pill p-attente">Review non commencée</span><span style="font-size:12px;color:var(--gris)">Ajoutez vos observations puis enregistrez.</span>
    @elseif($valide)<span class="pill p-ok">Review validée</span><span style="font-size:12px;color:var(--gris)">Enregistrée dans les comptes rendus le {{ $cr->updated_at->format('d/m/Y') }}.</span>
        <span style="font-size:12px;color:var(--gris)">Modifiable par un pilote uniquement.</span>
        @if(auth()->user()->estAdmin())<form method="POST" action="{{ route('scrum.rouvrir', [$sprint, 'review_sprint']) }}" style="margin:0">@csrf<button class="btn sm sec">Rouvrir</button></form>@endif
    @else<span class="pill p-warn">Brouillon</span>@endif
    <span style="font-size:12px;color:var(--gris)">Rédacteur : <strong style="color:#1e293b">{{ $redacteur?->nomComplet() ?? '—' }}</strong> (scribe du jour au planning des réunions)</span>
    @if($crDemo)<a href="{{ route('comptes-rendus.show', $crDemo) }}" class="btn sm sec" style="margin-left:auto" title="Compte rendu de démonstration rédigé dans le module Comptes rendus">CR Démo du {{ $crDemo->date_reunion->format('d/m/Y') }} ({{ $crDemo->estConfirme() ? 'confirmé' : 'brouillon' }})</a>@endif
</div>

<div class="indic">
    <div><b>{{ $ind['taux'] }} %</b><span>tâches réalisées ({{ $ind['realisees'] }}/{{ $ind['taches'] }})</span></div>
    <div><b>{{ $ind['testees'] }}</b><span>tâches testées (TT)</span></div>
    <div><b>{{ $ind['stories_terminees'] }}/{{ $ind['stories'] }}</b><span>user stories terminées</span></div>
    <div><b>{{ $ind['points_faits'] }}/{{ $ind['points'] }}</b><span>story points livrés</span></div>
    <div class="{{ $ind['en_retard'] ? 'alerte-i' : '' }}"><b>{{ $ind['en_retard'] }}</b><span>tâche(s) en retard</span></div>
    <div><b>{{ $ind['dailies'] }}/{{ $ind['jours'] }}</b><span>Dailies tenus</span></div>
    <div title="Tâches T2 / T3 terminées par l'équipe pendant le sprint"><b>{{ $ind['hors_sprint'] }}</b><span>tâche(s) hors sprint réalisée(s)</span></div>
</div>

<form method="POST" action="{{ route('scrum.review.update', $sprint) }}">@csrf @method('PUT')
<div class="carte" style="margin-bottom:14px">
    <div class="grille4">
        <div class="champ"><label>Date de la review</label>@if($canEdit)<input type="date" name="date_reunion" value="{{ ($cr?->date_reunion ?? ($sprint->date_fin->lt(today()) ? $sprint->date_fin : today()))->toDateString() }}">@else<div>{{ $cr?->date_reunion?->format('d/m/Y') ?? '—' }}</div>@endif</div>
        <div class="champ"><label>Heure début</label>@if($canEdit)<input type="time" name="heure_debut" value="{{ $cr?->heure_debut }}">@else<div>{{ $cr?->heure_debut ?? '—' }}</div>@endif</div>
        <div class="champ"><label>Heure fin</label>@if($canEdit)<input type="time" name="heure_fin" value="{{ $cr?->heure_fin }}">@else<div>{{ $cr?->heure_fin ?? '—' }}</div>@endif</div>
        <div class="champ"><label>Lieu</label>@if($canEdit)<input name="lieu" value="{{ $cr?->lieu }}" maxlength="255">@else<div>{{ $cr?->lieu ?? '—' }}</div>@endif</div>
    </div>
</div>

<div class="panneau">
<h3><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> Réalisation du sprint</h3>
<table class="sc-tbl">
    <thead><tr><th class="col-n">N°</th><th class="col-t">Tâches</th><th class="col-a">Attribution</th><th class="col-d ctr">Début</th><th class="col-d ctr">Fin</th><th class="col-du ctr">Durée (j)</th><th class="col-p ctr" title="Story points">Pts</th><th class="col-s">Statut</th><th class="col-o">Observation</th></tr></thead>
    <tbody>
    @php $m = 0; @endphp
    @forelse($planning as $bloc)
        @php $m++; @endphp
        <tr class="module"><td colspan="9">Module {{ $m }} : {{ $bloc['module'] }}</td></tr>
        @foreach($bloc['stories'] as $g)
            @if($us = $g['story'])
            @php $etatUs = \App\Models\UserStory::STATUTS_SPRINT[$us->pivot->statut_sprint ?? ''] ?? $us->statutLibelle(); @endphp
            <tr class="story">
                <td>{{ $us->numero }}</td>
                <td colspan="5">{{ $us->intitule }}</td>
                <td class="ctr" title="{{ $us->complexiteStory()['libelle'] ?? 'Non estimée' }}">{{ $us->effort_estime ?: '—' }}</td>
                <td><span class="pill {{ ($us->pivot->statut_sprint ?? '') === 'termine' ? 'p-ok' : (($us->pivot->statut_sprint ?? '') === 'en_cours' ? 'p-cours' : 'p-attente') }}">{{ $etatUs }}</span></td>
                <td>@if($canEdit)<x-redaction compact name="observations[us_{{ $us->id }}]" :value="$obs['us_' . $us->id] ?? null" placeholder="Observation sur la story" />@else<x-redaction lecture vide="" :value="$obs['us_' . $us->id] ?? null" />@endif</td>
            </tr>
            @endif
            @foreach($g['taches']->values() as $i => $t)
            @php [$lib, $cls] = \App\Services\ScrumService::statutReview($t); $code = $t->codeScrum(); @endphp
            <tr>
                <td>{{ $us ? preg_replace('/^US\s*/i', '', $us->numero) . '.' : '' }}{{ $i + 1 }}</td>
                <td>{{ $t->intitule }}</td>
                <td>{{ $t->user?->nomComplet() ?? '—' }}</td>
                <td class="ctr">{{ $t->date_debut?->format('d/m/Y') ?? '—' }}</td>
                <td class="ctr">{{ $t->echeance?->format('d/m/Y') ?? '—' }}</td>
                <td class="ctr">{{ $t->date_debut && $t->echeance ? $t->dureeJours() : '—' }}</td>
                <td class="ctr"></td>
                <td><span class="st-{{ $cls }}">{{ $lib }}</span> <span class="scc {{ $code['classe'] }}" title="{{ $code['libelle'] }}">{{ $code['code'] }}</span></td>
                <td>@if($canEdit)<x-redaction compact name="observations[{{ $t->id }}]" :value="$obs[$t->id] ?? null" />@else<x-redaction lecture vide="" :value="$obs[$t->id] ?? null" />@endif</td>
            </tr>
            @endforeach
        @endforeach
    @empty
        <tr><td colspan="9" style="text-align:center;color:var(--gris);padding:24px">Aucune tâche dans ce sprint.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<div class="carte grille2" style="margin-top:14px">
    <div class="champ"><label>Synthèse de la démonstration</label>
        @if($canEdit)<x-redaction name="synthese" :value="$donnees['synthese'] ?? null" :hauteur="110" placeholder="Ce qui a été présenté, retours du Product Owner / client…" />@else<x-redaction lecture :value="$donnees['synthese'] ?? null" />@endif
    </div>
    <div class="champ"><label>Décisions / suite à donner</label>
        @if($canEdit)<x-redaction name="decisions" :value="$donnees['decisions'] ?? null" :hauteur="110" placeholder="Priorités du prochain sprint, arbitrages…" />@else<x-redaction lecture :value="$donnees['decisions'] ?? null" />@endif
    </div>
</div>

{{-- La Sprint Review alimente le Product Backlog --}}
<div class="carte" style="margin-top:14px">
    <h3 class="bloc-titre"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg> Ajustements du Product Backlog</h3>
    <p class="bloc-aide">Nouvelles user stories issues de la démonstration, ajoutées au Product Backlog.</p>
    <div style="overflow-x:auto">
    <table class="sc-tbl" id="t-backlog">
        <thead><tr><th class="col-bm">Module</th><th class="col-bi">User story</th><th class="col-be">Exigences fonctionnelles</th><th class="col-bs">Backlog</th>@if($canEdit)<th class="col-bx"></th>@endif</tr></thead>
        <tbody>
        @foreach($bkLignes as $i => $l)
            @php $usC = !empty($l['user_story_id']) ? ($storiesCreees[$l['user_story_id']] ?? null) : null; @endphp
            <tr>
            @if($canEdit && empty($l['user_story_id']))
                <td><input name="backlog[{{ $i }}][module]" value="{{ $l['module'] ?? '' }}" maxlength="150" list="dl-modules" placeholder="Module"></td>
                <td><textarea name="backlog[{{ $i }}][intitule]" rows="2" maxlength="2000" placeholder="En tant que …, je veux … afin de …">{{ $l['intitule'] ?? '' }}</textarea></td>
                <td><x-redaction compact name="backlog[{{ $i }}][exigences]" :value="$l['exigences'] ?? null" /></td>
                <td><span class="pill p-warn">En attente</span><input type="hidden" name="backlog[{{ $i }}][user_story_id]" value=""></td>
                <td class="ctr"><button type="button" class="btn sm ko" onclick="this.closest('tr').remove()" title="Retirer la ligne">✕</button></td>
            @else
                <td>{{ $l['module'] ?? '—' }}@if($canEdit)<input type="hidden" name="backlog[{{ $i }}][module]" value="{{ $l['module'] ?? '' }}">@endif</td>
                <td>{{ $l['intitule'] ?? '' }}@if($canEdit)<input type="hidden" name="backlog[{{ $i }}][intitule]" value="{{ $l['intitule'] ?? '' }}">@endif</td>
                <td><x-redaction lecture vide="" :value="$l['exigences'] ?? null" />@if($canEdit)<input type="hidden" name="backlog[{{ $i }}][exigences]" value="{{ $l['exigences'] ?? '' }}">@endif</td>
                <td>@if($usC)<a class="bk-us" href="{{ route('projets.espace', [$sprint->projet, 'onglet' => 'backlog']) }}" title="Voir dans le Product Backlog">✓ {{ $usC->numero }}</a>@elseif(!empty($l['user_story_id']))<span style="font-size:11.5px;color:var(--gris)">Story supprimée</span>@else<span class="pill p-warn">En attente</span>@endif
                    @if($canEdit)<input type="hidden" name="backlog[{{ $i }}][user_story_id]" value="{{ $l['user_story_id'] ?? '' }}">@endif</td>
                @if($canEdit)<td></td>@endif
            @endif
            </tr>
        @endforeach
        @if(!$bkLignes && !$canEdit)<tr><td colspan="4" style="text-align:center;color:var(--gris);padding:16px">Aucun ajustement du backlog.</td></tr>@endif
        </tbody>
    </table>
    </div>
    @if($canEdit)
    <datalist id="dl-modules">@foreach($modules as $mod)<option value="{{ $mod }}">@endforeach</datalist>
    <div style="margin-top:8px"><button type="button" class="btn sm sec" onclick="ajouterStoryReview()">+ Nouvelle user story</button></div>
    @endif
</div>

@if($canEdit)
<div class="barre-actions">
    <button class="btn" name="apres" value="enregistrer">Enregistrer la review</button>
    <button class="btn sec" name="apres" value="backlog">Enregistrer et ajouter au Product Backlog</button>
    @if(!$valide && $canGerer)<button type="button" class="btn vert" onclick="document.getElementById('dlg-valider').showModal()">Valider la review…</button>@endif
</div>
@endif
</form>

@if($canEdit)
<template id="tpl-bk">
    <tr>
        <td><input name="backlog[__I__][module]" maxlength="150" list="dl-modules" placeholder="Module"></td>
        <td><textarea name="backlog[__I__][intitule]" rows="2" maxlength="2000" placeholder="En tant que …, je veux … afin de …"></textarea></td>
        <td><x-redaction compact name="backlog[__I__][exigences]" /></td>
        <td><span class="pill p-warn">En attente</span><input type="hidden" name="backlog[__I__][user_story_id]" value=""></td>
        <td class="ctr"><button type="button" class="btn sm ko" onclick="this.closest('tr').remove()" title="Retirer la ligne">✕</button></td>
    </tr>
</template>
@endif

@if($canGerer && !$valide)
<dialog id="dlg-valider" style="border:none;border-radius:14px;padding:22px;width:500px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18)">
    <h3 style="color:var(--bleu);margin-bottom:8px">Valider la review du sprint {{ $sprint->numero }}</h3>
    <p style="font-size:13px;color:#334155">Enregistrez vos saisies avant de valider.</p>
    <form method="POST" action="{{ route('scrum.review.valider', $sprint) }}">@csrf
        <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;margin:12px 0 6px;cursor:pointer">
            <input type="checkbox" name="retour_backlog" value="1" checked style="margin-top:3px">
            <span>Remettre au backlog les user stories non terminées ({{ $ind['stories'] - $ind['stories_terminees'] }})</span>
        </label>
        <label style="display:flex;gap:8px;align-items:flex-start;font-size:13px;margin:6px 0 12px;cursor:pointer">
            <input type="checkbox" name="ajouter_backlog" value="1" @checked($enAttente) @disabled(!$enAttente) style="margin-top:3px">
            <span>Ajouter au backlog les nouvelles user stories ({{ $enAttente }})</span>
        </label>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn vert">Valider</button>
        </div>
    </form>
</dialog>
@endif
@endsection

@if($canEdit)
@push('scripts')
<script>
let idxBk = {{ count($bkLignes) }};
function ajouterStoryReview() {
    const tpl = document.getElementById('tpl-bk').innerHTML.replaceAll('__I__', idxBk++);
    const corps = document.querySelector('#t-backlog tbody');
    corps.insertAdjacentHTML('beforeend', tpl);
    corps.lastElementChild.querySelector('textarea').focus();
}
</script>
@endpush
@endif
