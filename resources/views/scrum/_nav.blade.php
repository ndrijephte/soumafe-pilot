{{-- Navigation des cérémonies Scrum d'un sprint. Paramètres : $sprint, $actif (planning|daily|review|retro) --}}
@once
@push('styles')
<style>
.scrum-nav{display:flex;gap:4px;align-items:center;flex-wrap:wrap;border-bottom:2px solid var(--bord);margin:0 0 16px}
.scrum-nav a.onglet{padding:9px 15px;font-size:13.5px;font-weight:600;color:var(--gris);text-decoration:none;border-bottom:3px solid transparent;margin-bottom:-2px;display:inline-flex;align-items:center;gap:6px}
.scrum-nav a.onglet:hover{color:var(--bleu)}
.scrum-nav a.onglet.actif{color:var(--bleu);border-bottom-color:var(--accent)}
.scrum-nav .nb{background:#e2e8f0;color:#475569;border-radius:10px;padding:1px 7px;font-size:11px}
.scrum-nav .etat{width:8px;height:8px;border-radius:50%;background:#cbd5e1}.scrum-nav .etat.ok{background:var(--ok)}.scrum-nav .etat.brouillon{background:var(--warn)}
.scrum-nav .export{margin-left:auto;display:inline-flex;align-items:center;gap:6px;background:#166534;color:#fff;text-decoration:none;font-size:12.5px;font-weight:600;padding:7px 13px;border-radius:8px;margin-bottom:6px}
.scrum-nav .export:hover{background:#14532d}
@media(max-width:700px){.scrum-nav .export{margin-left:0}}
</style>
@endpush
@endonce
@php
    $crScrum = \App\Models\CompteRendu::where('sprint_id', $sprint->id)->whereIn('type', ['review_sprint', 'retrospective'])->pluck('statut', 'type');
    $etat = fn($t) => isset($crScrum[$t]) ? ($crScrum[$t] === 'confirme' ? 'ok' : 'brouillon') : '';
    // Les Daily sont consultables par tous ; les autres cérémonies restent réservées aux membres du projet
    $voitProjet = $sprint->projet->estVisiblePour(auth()->user());
    $titreEtat = fn($t) => isset($crScrum[$t]) ? ($crScrum[$t] === 'confirme' ? 'Validée' : 'Brouillon') : 'Non commencée';
@endphp
<nav class="scrum-nav" aria-label="Cérémonies Scrum">
    @if($voitProjet)<a class="onglet {{ $actif === 'planning' ? 'actif' : '' }}" href="{{ route('sprints.show', $sprint) }}">Sprint Planning</a>@endif
    <a class="onglet {{ $actif === 'daily' ? 'actif' : '' }}" href="{{ route('scrum.dailies', $sprint) }}">Daily Scrum <span class="nb">{{ $sprint->dailyScrums()->count() }}</span></a>
    @if($voitProjet)
    <a class="onglet {{ $actif === 'review' ? 'actif' : '' }}" href="{{ route('scrum.review', $sprint) }}" title="{{ $titreEtat('review_sprint') }}"><span class="etat {{ $etat('review_sprint') }}"></span>Review</a>
    <a class="onglet {{ $actif === 'retro' ? 'actif' : '' }}" href="{{ route('scrum.retro', $sprint) }}" title="{{ $titreEtat('retrospective') }}"><span class="etat {{ $etat('retrospective') }}"></span>Rétrospective</a>
    <a class="export" href="{{ route('scrum.archive', $sprint) }}" title="Product Backlog, Sprint Backlog, Sprint Planning, Dailies, Review et Rétrospective dans un classeur Excel">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exporter archive Scrum
    </a>
    @else
    <a class="onglet" href="{{ route('scrum.rapports') }}">Tous les rapports Daily</a>
    @endif
</nav>
