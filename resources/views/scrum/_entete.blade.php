{{-- En-tête des pages de cérémonie. Paramètres : $sprint, $actif, $page (libellé du fil d'Ariane) --}}
@include('scrum._styles')
<div class="sc-tete">
    <div>
        <h2>Sprint {{ $sprint->numero }} - {{ $sprint->projet->libelle }}</h2>
        <div class="crumb">
            <a href="{{ route('sprints.index') }}" style="color:var(--accent);text-decoration:none">Projets / Sprints</a> &rsaquo;
            @if($sprint->projet->estVisiblePour(auth()->user()))
            <a href="{{ route('projets.espace', $sprint->projet) }}" style="color:var(--accent);text-decoration:none">{{ $sprint->projet->libelle }}</a> &rsaquo;
            <a href="{{ route('sprints.show', $sprint) }}" style="color:var(--accent);text-decoration:none">Sprint {{ $sprint->numero }}</a> &rsaquo; {{ $page }}
            @else
            <a href="{{ route('scrum.rapports') }}" style="color:var(--accent);text-decoration:none">Rapports Daily Scrum</a> &rsaquo; {{ $sprint->projet->libelle }} &rsaquo; Sprint {{ $sprint->numero }} &rsaquo; {{ $page }}
            @endif
        </div>
    </div>
</div>
@include('scrum._nav', ['sprint' => $sprint, 'actif' => $actif])
@include('scrum._fiche', ['sprint' => $sprint])
@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif
