@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', $daily->libelle() . ' - Sprint ' . $sprint->numero)

@push('styles')
<style>
.dl-tete{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:14px}
.dl-info{display:grid;grid-template-columns:150px minmax(0,1fr);gap:6px 10px;align-items:center;font-size:13px}
.dl-info .lib{font-weight:700;color:var(--bleu);font-size:12px;text-transform:uppercase}
.dl-nav{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.dl-titre{font-size:13px;font-weight:700;color:var(--bleu)}
.col-nom{width:14%}.col-pres{width:10%}.col-hier{width:20%}.col-jour{width:20%}.col-duree{width:9%}.col-defis{width:21%}.col-x{width:52px}
.pres-h{display:block;font-size:10.5px;color:var(--gris);margin-top:3px}
.sc-tbl td.nom small{display:block;font-weight:500;color:var(--gris);font-size:11px}
.duree-retard{color:var(--ko);font-weight:700}
.moi td{background:#fffbeb}
.ajout-part{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 0 0;font-size:12.5px}
.ajout-part select{padding:5px 9px;border:1px solid var(--bord);border-radius:7px;font-size:12.5px;min-width:240px}
@media(max-width:900px){.dl-tete{grid-template-columns:1fr}.dl-info{grid-template-columns:120px minmax(0,1fr)}}
</style>
@endpush

@section('contenu')
@include('scrum._entete', ['sprint' => $sprint, 'actif' => 'daily', 'page' => $daily->libelle()])
@php
    $tout = $edition === 'tout';
    $moi = auth()->id();
    $nomUser = fn($id) => $tousUsers->firstWhere('id', $id)?->nomComplet() ?? $membres->firstWhere('id', $id)?->nomComplet() ?? '—';
    $entrees = $daily->entrees->sortBy(fn($e) => $e->user?->name)->values();
    $autres = $tousUsers->whereNotIn('id', $membres->pluck('id'));
    $ajoutables = $tousUsers->whereNotIn('id', $entrees->pluck('user_id'));
    // Liste de présence : référence au Pointage du jour
    $presence = function ($uid) use ($pointages, $absences) {
        $p = $pointages[$uid] ?? null;
        // Sans pointage : absence déclarée dans la saisie quotidienne
        if (!$p && ($a = $absences[$uid] ?? null)) return ['Absent (' . mb_strtolower($a->labelType()) . ')', 'b-ko', $a->motif, 'absent'];
        $statut = $p?->statut ?? 'non_pointe';
        return [$p ? $p->statutLibelle() : 'Non pointé', match ($statut) { 'present' => 'b-ok', 'retard' => 'b-warn', 'sorti_tot' => 'b-tot', 'absent' => 'b-ko', default => 'b-gris' },
            $p && $p->heure_arrivee ? 'Arrivée ' . \Carbon\Carbon::parse($p->heure_arrivee)->format('H:i') : null, $statut];
    };
    [$libStatut, $clsStatut] = $daily->statutPublication();
@endphp

<div class="dl-nav" style="margin-bottom:12px">
    @if($precedent)<a class="btn sm sec" href="{{ route('scrum.daily', [$sprint, $precedent]) }}">‹ Daily {{ $precedent->numero_jour }}</a>@endif
    <a class="btn sm sec" href="{{ route('scrum.dailies', $sprint) }}">Calendrier</a>
    @if($suivant)<a class="btn sm sec" href="{{ route('scrum.daily', [$sprint, $suivant]) }}">Daily {{ $suivant->numero_jour }} ›</a>@endif
    <span style="margin-left:auto;font-size:12px;color:var(--gris)">Rédacteur (scribe du jour) : <strong style="color:#1e293b">{{ $nomUser($daily->roleUserId('scribe')) }}</strong> · créé par {{ $daily->createur?->nomComplet() ?? '—' }} · modifié le {{ $daily->updated_at->format('d/m/Y H:i') }}</span>
</div>

@if($tout && $manquants->isNotEmpty())
<div class="alerte info">{{ $manquants->count() }} membre(s) sans ligne ({{ $manquants->map->nomComplet()->join(', ') }}) : « Recalculer » pour les ajouter.</div>
@endif
@if($daily->estPublie())
<div class="alerte ok" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span><strong>Rapport publié</strong> le {{ $daily->publie_le->format('d/m/Y à H:i') }} par {{ $daily->publiePar?->nomComplet() ?? '—' }}. Modifiable par un pilote uniquement.</span>
    @if(auth()->user()->estAdmin())<form method="POST" action="{{ route('scrum.daily.depublier', [$sprint, $daily]) }}" style="margin:0 0 0 auto" onsubmit="return confirm('Retirer la publication et remettre ce Daily en brouillon ?')">@csrf<button class="btn sm sec">Remettre en brouillon</button></form>@endif
</div>
@elseif($daily->estSoumis())
<div class="alerte info" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span><strong>En attente de validation</strong> : soumis le {{ $daily->soumis_le->format('d/m/Y à H:i') }} par {{ $daily->soumisPar?->nomComplet() ?? '—' }}. Publication après validation d'un pilote.</span>
    @if(auth()->user()->estAdmin())
    <span style="margin-left:auto;display:flex;gap:6px">
        <button type="submit" form="f-publier" class="btn sm vert">Approuver et publier</button>
        <form method="POST" action="{{ route('scrum.daily.depublier', [$sprint, $daily]) }}" style="margin:0" onsubmit="return confirm('Renvoyer ce rapport en brouillon au scribe ?')">@csrf<button class="btn sm sec">Renvoyer en brouillon</button></form>
    </span>
    @endif
</div>
@endif
@if($edition === 'ligne')<div class="alerte info">Complétez votre ligne (surlignée).</div>@endif

<form method="POST" action="{{ route('scrum.daily.update', [$sprint, $daily]) }}" id="f-daily">@csrf @method('PUT')
<div class="carte" style="margin-bottom:14px">
    <div class="dl-tete">
        <div class="dl-info">
            <span class="lib">Objet</span><span>{{ mb_strtoupper($sprint->projet->libelle) }}</span>
            <span class="lib">Réunion</span><span class="dl-titre">DAILY SCRUM - JOUR {{ $daily->numero_jour }}</span>
            <span class="lib">Date</span><span>{{ ucfirst($daily->date_reunion->translatedFormat('l d F Y')) }} <span class="pill {{ $clsStatut }}" style="margin-left:6px">{{ $libStatut }}</span></span>
            @if($config['lieu'])<span class="lib">Lieu</span><span>{{ $config['lieu'] }}</span>@endif
            <span class="lib">Heure début / fin</span>
            <span style="display:flex;gap:6px;max-width:260px" class="champ">
                @if($tout)
                <input type="time" name="heure_debut" value="{{ $daily->heure_debut ? substr($daily->heure_debut, 0, 5) : '' }}">
                <input type="time" name="heure_fin" value="{{ $daily->heure_fin ? substr($daily->heure_fin, 0, 5) : '' }}">
                @else
                {{ $daily->heure_debut ? substr($daily->heure_debut, 0, 5) : '—' }} → {{ $daily->heure_fin ? substr($daily->heure_fin, 0, 5) : '—' }}
                @endif
            </span>
        </div>
        <div class="dl-info">
            @foreach(\App\Models\DailyScrum::ROLES as $code => $lib)
            <span class="lib">{{ $lib }}</span>
            @if($tout)
            <span class="champ"><select name="roles[{{ $code }}]">
                <option value="">—</option>
                <optgroup label="Équipe du sprint">@foreach($membres as $m)<option value="{{ $m->id }}" @selected($daily->roleUserId($code) === $m->id)>{{ $m->nomComplet() }}</option>@endforeach</optgroup>
                <optgroup label="Autres collaborateurs">@foreach($autres as $m)<option value="{{ $m->id }}" @selected($daily->roleUserId($code) === $m->id)>{{ $m->nomComplet() }}</option>@endforeach</optgroup>
            </select></span>
            @else
            <span>{{ $nomUser($daily->roleUserId($code)) }}</span>
            @endif
            @endforeach
        </div>
    </div>
</div>

<div class="panneau">
<table class="sc-tbl">
    <thead><tr>
        <th class="col-nom">Nom</th><th class="col-pres">Présence</th><th class="col-hier">Tâches réalisées précédemment / hier</th><th class="col-jour">Détail tâches à faire ce jour</th>
        <th class="col-duree ctr">Durée restante (jrs/h)</th><th class="col-defis">Défis ou observations</th>@if($tout)<th class="col-x"></th>@endif
    </tr></thead>
    <tbody>
    @forelse($entrees as $e)
        @php $ed = $tout || ($edition === 'ligne' && $e->user_id === $moi); @endphp
        <tr class="{{ $e->user_id === $moi ? 'moi' : '' }}">
            <td class="nom">{{ $e->user?->nomComplet() ?? '—' }}
                @foreach(\App\Models\DailyScrum::ROLES as $code => $lib)@if($daily->roleUserId($code) === $e->user_id)<small>{{ $lib }}</small>@endif @endforeach
            </td>
            @php [$pLib, $pCls, $pHeure] = $presence($e->user_id); @endphp
            <td><span class="badge {{ $pCls }}">{{ $pLib }}</span>@if($pHeure)<span class="pres-h">{{ $pHeure }}</span>@endif</td>
            @foreach(['taches_hier', 'taches_jour'] as $champ)
            <td>@if($ed)<x-redaction compact name="entrees[{{ $e->id }}][{{ $champ }}]" :value="$e->$champ" />@else<x-redaction lecture :value="$e->$champ" />@endif</td>
            @endforeach
            <td class="ctr">@if($ed)<input name="entrees[{{ $e->id }}][duree_restante]" value="{{ $e->duree_restante }}" maxlength="20" style="text-align:center">@else<span class="{{ str_starts_with((string) $e->duree_restante, 'Retard') ? 'duree-retard' : '' }}">{{ $e->duree_restante ?: '—' }}</span>@endif</td>
            <td>@if($ed)<x-redaction compact name="entrees[{{ $e->id }}][defis]" :value="$e->defis" />@else<x-redaction lecture :value="$e->defis" />@endif</td>
            @if($tout)<td class="ctr"><button type="submit" form="f-retirer-{{ $e->id }}" class="btn sm ko" title="Retirer la ligne (absent)">✕</button></td>@endif
        </tr>
    @empty
        <tr><td colspan="7" style="text-align:center;color:var(--gris);padding:24px">Aucune ligne : ajoutez des participants ou « Recalculer ».</td></tr>
    @endforelse
    </tbody>
</table>
@if($tout && $ajoutables->isNotEmpty())
<div class="ajout-part">
    <span>Ajouter un participant :</span>
    <select name="user_id" form="f-ajout-part" required>
        <option value="">— Choisir —</option>
        @if($membres->whereNotIn('id', $entrees->pluck('user_id'))->isNotEmpty())<optgroup label="Équipe du sprint">@foreach($membres->whereNotIn('id', $entrees->pluck('user_id')) as $m)<option value="{{ $m->id }}">{{ $m->nomComplet() }}</option>@endforeach</optgroup>@endif
        <optgroup label="Autres collaborateurs">@foreach($ajoutables->whereNotIn('id', $membres->pluck('id')) as $m)<option value="{{ $m->id }}">{{ $m->nomComplet() }}</option>@endforeach</optgroup>
    </select>
    <button type="submit" form="f-ajout-part" class="btn sm sec">+ Ajouter la ligne</button>
</div>
@endif
</div>

<div class="carte" style="margin-top:14px;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:14px">
    <div class="champ"><label>Commentaire général / informations du jour</label>
        @if($tout)<x-redaction name="commentaire_general" :value="$daily->commentaire_general" />@else<x-redaction lecture :value="$daily->commentaire_general" />@endif
    </div>
    <div class="champ"><label>Observation</label>
        @if($tout)<x-redaction name="observation" :value="$daily->observation" />@else<x-redaction lecture :value="$daily->observation" />@endif
    </div>
</div>

<div class="barre-actions">
    @if($edition)<button class="btn">Enregistrer</button>@endif
    @if($tout && !$daily->estPublie() && !$daily->estSoumis())
    @if(auth()->user()->estAdmin())
    <button type="submit" form="f-publier" class="btn vert" title="Diffuse le rapport à tous les collaborateurs">Publier le rapport</button>
    @else
    <button type="submit" form="f-publier" class="btn vert" title="Le rapport sera diffusé après validation d'un pilote">Soumettre pour publication</button>
    @endif
    @endif
    @if($tout)
    <button type="submit" form="f-recalc" class="btn sec" title="Recharge hier / ce jour / durée restante depuis la planification. Les défis saisis sont conservés.">Recalculer depuis la planification</button>
    @endif
    @if(auth()->user()->peutGererProjet($sprint->projet_id) && (!($daily->estPublie() || $daily->estSoumis()) || auth()->user()->estAdmin()))
    <button type="submit" form="f-suppr" class="btn ko" style="margin-left:auto">Supprimer ce Daily</button>
    @endif
</div>
</form>

@if($tout)
<form method="POST" action="{{ route('scrum.daily.entree.ajouter', [$sprint, $daily]) }}" id="f-ajout-part">@csrf</form>

<form method="POST" action="{{ route('scrum.daily.recalculer', [$sprint, $daily]) }}" id="f-recalc" onsubmit="return confirm('Recalculer les lignes à partir de l\'état actuel des tâches ? Les colonnes hier / ce jour / durée seront remplacées (les défis saisis sont conservés).')">@csrf
    <input type="hidden" name="roles" value="0">
</form>
@foreach($entrees as $e)
<form method="POST" action="{{ route('scrum.daily.entree.retirer', [$sprint, $daily, $e]) }}" id="f-retirer-{{ $e->id }}" onsubmit="return confirm('Retirer {{ addslashes($e->user?->nomComplet()) }} de ce Daily ?')">@csrf @method('DELETE')</form>
@endforeach
@endif
@if(($tout || auth()->user()->estAdmin()) && !$daily->estPublie())
<form method="POST" action="{{ route('scrum.daily.publier', [$sprint, $daily]) }}" id="f-publier" onsubmit="return confirm(@js(auth()->user()->estAdmin() ? 'Publier ce rapport ? Il sera visible de tous les collaborateurs.' : 'Soumettre ce rapport pour publication ? Enregistrez vos saisies avant : il ne sera plus modifiable, sauf par un pilote.'))">@csrf</form>
@endif
@if(auth()->user()->peutGererProjet($sprint->projet_id) && (!($daily->estPublie() || $daily->estSoumis()) || auth()->user()->estAdmin()))
<form method="POST" action="{{ route('scrum.daily.destroy', [$sprint, $daily]) }}" id="f-suppr" onsubmit="return demanderMotifSuppression(this,'ce Daily')">@csrf @method('DELETE')
    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
</form>
@endif
@endsection


