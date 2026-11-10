{{-- Déroulement du sprint : étapes, état, prochaine étape et règles (App\Services\CeremoniesScrum). Paramètres : $sprint, $actif (backlog|planning|daily|review|retro) --}}
@once
@push('styles')
<style>
.scrum-nav{display:flex;gap:4px;align-items:center;flex-wrap:wrap;border-bottom:2px solid var(--bord);margin:0 0 16px}
.scrum-nav a.onglet{padding:9px 11px;font-size:13px;font-weight:600;color:var(--gris);text-decoration:none;border-bottom:3px solid transparent;margin-bottom:-2px;display:inline-flex;align-items:center;gap:6px}
.scrum-nav a.onglet:hover{color:var(--bleu)}
.scrum-nav a.onglet.actif{color:var(--bleu);border-bottom-color:var(--accent)}
.scrum-nav .nb{background:#e2e8f0;color:#475569;border-radius:10px;padding:1px 7px;font-size:11px}
.scrum-nav .etat{width:8px;height:8px;border-radius:50%;background:#cbd5e1}.scrum-nav .etat.ok{background:var(--ok)}.scrum-nav .etat.brouillon{background:var(--warn)}.scrum-nav .etat.cours{background:var(--accent)}
.scrum-nav .rang{color:#94a3b8;font-weight:500}
/* Product Backlog : transversal aux sprints, séparé des cérémonies */
.scrum-nav a.onglet.transversal{color:var(--bleu)}
.scrum-nav a.onglet.transversal svg{color:var(--accent);flex:none}
.scrum-nav .sep-nav{width:1px;height:20px;background:#cbd5e1;margin:0 2px 2px}
.scrum-suite{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px 14px;margin:-8px 0 14px;font-size:12.5px;color:var(--gris)}
.scrum-suite a{color:var(--accent);font-weight:600;text-decoration:none}.scrum-suite a:hover{text-decoration:underline}
.scrum-suite button{background:none;border:0;padding:0;font:inherit;color:var(--accent);font-weight:600;cursor:pointer}.scrum-suite button:hover{text-decoration:underline}
#dlg-regles{border:none;border-radius:14px;padding:20px 22px;width:860px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);color:#334155}
#dlg-regles h3{color:var(--bleu);margin:0 0 12px;font-size:15px}
#dlg-regles table{width:100%;border-collapse:collapse;font-size:12.5px;line-height:1.45}
#dlg-regles th{text-align:left;font-size:11px;color:#64748b;font-weight:700;padding:0 10px 6px;border-bottom:1px solid var(--bord)}
#dlg-regles td{padding:9px 10px;border-bottom:1px solid #f1f5f9;vertical-align:top}
#dlg-regles td:first-child{font-weight:700;color:var(--bleu);white-space:nowrap}
#dlg-regles form{text-align:right;margin-top:12px}
@media(max-width:700px){#dlg-regles td:first-child{white-space:normal}}
.scrum-nav .export{margin-left:auto;display:inline-flex;align-items:center;gap:6px;background:#fff;color:#166534;text-decoration:none;font-size:12px;font-weight:600;padding:5px 11px;border:1px solid #bbf7d0;border-radius:7px;margin-bottom:6px}
.scrum-nav .export:hover{background:#f0fdf4}
@media(max-width:700px){.scrum-nav .export{margin-left:0}}
</style>
@endpush
@endonce
@php
    $etats = \App\Services\CeremoniesScrum::etats($sprint);
    // Les Daily sont consultables par tous ; les autres cérémonies restent réservées aux membres du projet
    $voitProjet = $sprint->projet->estVisiblePour(auth()->user());
    [$suite, $lienSuite] = \App\Services\CeremoniesScrum::prochaineEtape($sprint);
@endphp
<nav class="scrum-nav" aria-label="Cérémonies Scrum">
    @if($voitProjet)
    <a class="onglet transversal" href="{{ route('projets.espace', [$sprint->projet, 'onglet' => 'backlog']) }}" title="Toutes les user stories du projet, tous sprints confondus">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>
        Product Backlog <span class="nb">{{ $sprint->projet->userStories()->count() }}</span>
    </a>
    <span class="sep-nav" aria-hidden="true"></span>
    @endif
    @if($voitProjet)<a class="onglet {{ $actif === 'backlog' ? 'actif' : '' }}" href="{{ route('sprints.show', $sprint) }}" title="{{ $etats['backlog'][1] }}"><span class="etat {{ $etats['backlog'][0] }}"></span><span class="rang">1</span> Sprint Backlog <span class="nb">{{ $sprint->userStories()->count() }}</span></a>
    <a class="onglet {{ $actif === 'planning' ? 'actif' : '' }}" href="{{ route('sprints.planning', $sprint) }}" title="{{ $etats['planning'][1] }}"><span class="etat {{ $etats['planning'][0] }}"></span><span class="rang">2</span> Sprint Planning <span class="nb">{{ $sprint->taches()->count() }}</span></a>@endif
    <a class="onglet {{ $actif === 'daily' ? 'actif' : '' }}" href="{{ route('scrum.dailies', $sprint) }}" title="{{ $etats['daily'][1] }}"><span class="etat {{ $etats['daily'][0] }}"></span><span class="rang">3</span> Daily Scrum <span class="nb">{{ $sprint->dailyScrums()->count() }}</span></a>
    @if($voitProjet)
    <a class="onglet {{ $actif === 'review' ? 'actif' : '' }}" href="{{ route('scrum.review', $sprint) }}" title="{{ $etats['review'][1] }}"><span class="etat {{ $etats['review'][0] }}"></span><span class="rang">4</span> Review</a>
    <a class="onglet {{ $actif === 'retro' ? 'actif' : '' }}" href="{{ route('scrum.retro', $sprint) }}" title="{{ $etats['retro'][1] }}"><span class="etat {{ $etats['retro'][0] }}"></span><span class="rang">5</span> Rétrospective</a>
    <a class="export" href="{{ route('scrum.archive', $sprint) }}" title="Product Backlog, Sprint Backlog, Sprint Planning, Dailies, Review et Rétrospective dans un classeur Excel">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Exporter archive Scrum
    </a>
    @else
    <a class="onglet" href="{{ route('scrum.rapports') }}">Tous les rapports Daily</a>
    @endif
</nav>
@if($voitProjet)
<div class="scrum-suite">
    <span>Prochaine étape : @if($lienSuite)<a href="{{ $lienSuite }}">{{ $suite }}</a>@else{{ $suite }}@endif</span>
    <button type="button" onclick="document.getElementById('dlg-regles').showModal()">Règles du sprint</button>
</div>
<dialog id="dlg-regles">
    <h3>Règles du sprint</h3>
    <table>
        <thead><tr><th>Étape</th><th>Quand</th><th>Qui</th><th>Résultat</th></tr></thead>
        <tbody>
        @foreach(\App\Services\CeremoniesScrum::REGLES as [$etape, $quand, $qui, $resultat])
        <tr><td>{{ $etape }}</td><td>{{ $quand }}</td><td>{{ $qui }}</td><td>{{ $resultat }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <form method="dialog"><button class="btn sm sec">Fermer</button></form>
</dialog>
@endif
