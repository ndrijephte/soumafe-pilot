@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', $daily->libelle() . ' - ' . $daily->contexte())

@push('styles')
<style>
.dl-tete{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:14px}
.dl-info{display:grid;grid-template-columns:150px minmax(0,1fr);gap:6px 10px;align-items:center;font-size:13px}
.dl-info .lib{font-weight:700;color:var(--bleu);font-size:12px;text-transform:uppercase}
.dl-nav{display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.dl-titre{font-size:13px;font-weight:700;color:var(--bleu)}
.col-nom{width:16%}.col-hier{width:28%}.col-jour{width:28%}.col-defis{width:28%}.col-x{width:52px}
.sc-tbl td.nom small{display:block;font-weight:500;color:var(--gris);font-size:11px}
/* Tableau du Daily : quadrillage gris sobre, en-tête raffiné */
.dl-tbl{border-collapse:separate;border-spacing:0;border:1px solid #d1d5db;border-radius:10px;overflow:hidden}
.dl-tbl th,.dl-tbl td{border-right:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb}
.dl-tbl th:last-child,.dl-tbl td:last-child{border-right:none}
.dl-tbl tbody tr:last-child td{border-bottom:none}
.dl-tbl thead th{background:linear-gradient(#f8fafc,#eef2f7);color:#334155;font-size:10.5px;font-weight:600;letter-spacing:.6px;padding:11px 12px;border-bottom:2px solid #cbd5e1}
.dl-tbl thead th .tt{display:flex;align-items:center;gap:9px}
.dl-tbl thead th .tt svg{flex:none;width:15px;height:15px;padding:6px;box-sizing:content-box;border-radius:8px;background:#eaf2fb;color:#2e74b5}
.dl-tbl thead th .tt b{display:block;color:#2e74b5;font-weight:700}
.dl-tbl thead th .sous{display:block;text-transform:none;letter-spacing:0;font-weight:400;font-size:10.5px;color:#64748b;margin-top:2px}
.dl-tbl td{padding:10px 12px;vertical-align:top}
.dl-tbl td.nom{background:#fafbfc}
.dl-projet{display:inline-block;background:#eef4fb;color:#2e74b5;border:1px solid #bfdbfe;border-radius:7px;padding:3px 11px;font-weight:700;letter-spacing:.4px}
.ajout-part{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 0 0;font-size:12.5px}
.ajout-part select{padding:5px 9px;border:1px solid var(--bord);border-radius:7px;font-size:12.5px;min-width:240px}
@media(max-width:900px){.dl-tete{grid-template-columns:1fr}.dl-info{grid-template-columns:120px minmax(0,1fr)}}
</style>
@endpush

@section('contenu')
@if($sprint)
@include('scrum._entete', ['sprint' => $sprint, 'actif' => 'daily', 'page' => $daily->libelle()])
@else
{{-- Daily hors sprint : en-tête simple --}}
@include('scrum._styles')
<div class="sc-tete">
    <div>
        <h2>Daily hors sprint - {{ $daily->objet ?: $daily->contexte() }}</h2>
        <div class="crumb"><a href="{{ route('scrum.rapports') }}" style="color:var(--accent);text-decoration:none">Daily Scrum</a> &rsaquo; {{ $daily->contexte() }} &rsaquo; {{ $daily->libelle() }}</div>
    </div>
</div>
@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif
@endif
@php
    $tout = $edition === 'tout';
    $nomUser = fn($id) => $tousUsers->firstWhere('id', $id)?->nomComplet() ?? $membres->firstWhere('id', $id)?->nomComplet() ?? '—';
    $entrees = $daily->entrees->sortBy(fn($e) => $e->user?->name)->values();
    $autres = $tousUsers->whereNotIn('id', $membres->pluck('id'));
    $ajoutables = $tousUsers->whereNotIn('id', $entrees->pluck('user_id'));
    [$libStatut, $clsStatut] = $daily->statutPublication();
@endphp

<div class="dl-nav" style="margin-bottom:12px">
    @if($precedent)<a class="btn sm sec" href="{{ $precedent->url() }}">‹ Daily {{ $precedent->numero_jour }}</a>@endif
    <a class="btn sm sec" href="{{ $sprint ? route('scrum.dailies', $sprint) : route('scrum.rapports') }}">{{ $sprint ? 'Calendrier' : 'Tous les Daily' }}</a>
    @if($suivant)<a class="btn sm sec" href="{{ $suivant->url() }}">Daily {{ $suivant->numero_jour }} ›</a>@endif
    <span style="margin-left:auto;font-size:12px;color:var(--gris)">Rédacteur (scribe du jour) : <strong style="color:#1e293b">{{ $nomUser($daily->roleUserId('scribe')) }}</strong> · créé par {{ $daily->createur?->nomComplet() ?? '—' }} · modifié le {{ $daily->updated_at->format('d/m/Y H:i') }}</span>
</div>

@if($tout && $manquants->isNotEmpty())
<div class="alerte info">{{ $manquants->count() }} membre(s) sans ligne ({{ $manquants->map->nomComplet()->join(', ') }}) : « Recalculer » pour les ajouter.</div>
@endif
@if($daily->estPublie())
<div class="alerte ok" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span><strong>Rapport publié</strong> le {{ $daily->publie_le->format('d/m/Y à H:i') }} par {{ $daily->publiePar?->nomComplet() ?? '—' }}.@if(auth()->user()->estAdmin()) Modifiable par un pilote uniquement.@endif</span>
    @if(auth()->user()->estAdmin())<form method="POST" action="{{ $daily->url('depublier') }}" style="margin:0 0 0 auto" onsubmit="return confirm('Retirer la publication et remettre ce Daily en brouillon ?')">@csrf<button class="btn sm sec">Remettre en brouillon</button></form>@endif
</div>
@elseif($daily->estSoumis())
<div class="alerte info" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <span><strong>En attente de publication</strong> : soumis le {{ $daily->soumis_le->format('d/m/Y à H:i') }} par {{ $daily->soumisPar?->nomComplet() ?? '—' }}.</span>
    @if(auth()->user()->estAdmin())
    <span style="margin-left:auto;display:flex;gap:6px">
        <button type="submit" form="f-publier" class="btn sm vert">Approuver et publier</button>
        <form method="POST" action="{{ $daily->url('depublier') }}" style="margin:0" onsubmit="return confirm('Renvoyer ce rapport en brouillon au scribe ?')">@csrf<button class="btn sm sec">Renvoyer en brouillon</button></form>
    </span>
    @endif
</div>
@endif

<form method="POST" action="{{ $daily->url('update') }}" id="f-daily">@csrf @method('PUT')
<div class="carte" style="margin-bottom:14px">
    <div class="dl-tete">
        <div class="dl-info">
            <span class="lib">Objet</span>
            @if($daily->estHorsSprint() && $edition === 'tout')
            <span class="champ"><input name="objet" value="{{ $daily->objet }}" maxlength="150" placeholder="{{ $daily->contexte() }}"></span>
            @else
            <span><span class="dl-projet">{{ mb_strtoupper($daily->objet ?: ($daily->projet?->libelle ?? 'Transversal')) }}</span></span>
            @endif
            @if($daily->estHorsSprint())<span class="lib">Contexte</span><span>@if($daily->projet)<span class="dl-projet">{{ mb_strtoupper($daily->projet->libelle) }}</span>@else{{ $daily->contexte() }}@endif</span>@endif
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
                <optgroup label="{{ $sprint ? 'Équipe du sprint' : ($daily->projet ? 'Équipe du projet' : 'Participants') }}">@foreach($membres as $m)<option value="{{ $m->id }}" @selected($daily->roleUserId($code) === $m->id)>{{ $m->nomComplet() }}</option>@endforeach</optgroup>
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
<table class="sc-tbl dl-tbl">
    <thead><tr>
        @php $svg = fn($d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>'; @endphp
        <th class="col-nom"><span class="tt">{!! $svg('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>') !!}<span><b>Participant</b><span class="sous">Rôle du jour</span></span></span></th>
        <th class="col-hier"><span class="tt">{!! $svg('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>') !!}<span><b>Réalisé</b><span class="sous">Tâches précédentes / hier</span></span></span></th>
        <th class="col-jour"><span class="tt">{!! $svg('<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><polyline points="3 6 4 7 6 5"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>') !!}<span><b>À faire</b><span class="sous">Détail des tâches de ce jour</span></span></span></th>
        <th class="col-defis"><span class="tt">{!! $svg('<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') !!}<span><b>Défis</b><span class="sous">Blocages ou observations</span></span></span></th>@if($tout)<th class="col-x"></th>@endif
    </tr></thead>
    <tbody>
    @forelse($entrees as $e)
        @php $ed = $tout; @endphp
        <tr>
            <td class="nom">{{ $e->user?->nomComplet() ?? '—' }}
                @foreach(\App\Models\DailyScrum::ROLES as $code => $lib)@if($daily->roleUserId($code) === $e->user_id)<small>{{ $lib }}</small>@endif @endforeach
            </td>
            @foreach(['taches_hier', 'taches_jour'] as $champ)
            <td>@if($ed)<x-redaction compact name="entrees[{{ $e->id }}][{{ $champ }}]" :value="$e->$champ" />@else<x-redaction lecture :value="$e->$champ" />@endif</td>
            @endforeach
            <td>@if($ed)<x-redaction compact name="entrees[{{ $e->id }}][defis]" :value="$e->defis" />@else<x-redaction lecture :value="$e->defis" />@endif</td>
            @if($tout)<td class="ctr"><button type="submit" form="f-retirer-{{ $e->id }}" class="btn sm ko" title="Retirer la ligne (absent)">✕</button></td>@endif
        </tr>
    @empty
        <tr><td colspan="5" style="text-align:center;color:var(--gris);padding:24px">Aucune ligne : ajoutez des participants ou « Recalculer ».</td></tr>
    @endforelse
    </tbody>
</table>
@if($tout && $ajoutables->isNotEmpty())
<div class="ajout-part">
    <span>Ajouter un participant :</span>
    <select name="user_id" form="f-ajout-part" required>
        <option value="">— Choisir —</option>
        @if($membres->whereNotIn('id', $entrees->pluck('user_id'))->isNotEmpty())<optgroup label="{{ $sprint ? 'Équipe du sprint' : 'Équipe du projet' }}">@foreach($membres->whereNotIn('id', $entrees->pluck('user_id')) as $m)<option value="{{ $m->id }}">{{ $m->nomComplet() }}</option>@endforeach</optgroup>@endif
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
    <button type="submit" form="f-publier" class="btn vert" title="Diffuse le rapport à tous les collaborateurs">Publier le rapport</button>
    @endif
    @if($tout)
    <button type="submit" form="f-recalc" class="btn sec" title="Recharge hier / ce jour / durée restante depuis la planification. Les défis saisis sont conservés.">Recalculer depuis la planification</button>
    @endif
    @if($peutGerer)
    <button type="submit" form="f-suppr" class="btn ko" style="margin-left:auto">Supprimer ce Daily</button>
    @endif
</div>
</form>

@if($tout)
<form method="POST" action="{{ $daily->url('entree.ajouter') }}" id="f-ajout-part">@csrf</form>

<form method="POST" action="{{ $daily->url('recalculer') }}" id="f-recalc" onsubmit="return confirm('Recalculer les lignes à partir de l\'état actuel des tâches ? Les colonnes hier / ce jour / durée seront remplacées (les défis saisis sont conservés).')">@csrf
    <input type="hidden" name="roles" value="0">
</form>
@foreach($entrees as $e)
<form method="POST" action="{{ $daily->url('entree.retirer', $e) }}" id="f-retirer-{{ $e->id }}" onsubmit="return confirm('Retirer {{ addslashes($e->user?->nomComplet()) }} de ce Daily ?')">@csrf @method('DELETE')</form>
@endforeach
@endif
@if($tout && !$daily->estPublie())
<form method="POST" action="{{ $daily->url('publier') }}" id="f-publier" onsubmit="return confirm(@js(auth()->user()->estAdmin() ? 'Publier ce rapport ? Il sera visible de tous les collaborateurs.' : 'Publier ce rapport ? Enregistrez vos saisies avant : il sera visible de tous les collaborateurs et ne sera plus modifiable, sauf par un pilote.'))">@csrf</form>
@endif
@if($peutGerer)
<form method="POST" action="{{ $daily->url('destroy') }}" id="f-suppr" onsubmit="return demanderMotifSuppression(this,'ce Daily')">@csrf @method('DELETE')
    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
</form>
@endif
@endsection


