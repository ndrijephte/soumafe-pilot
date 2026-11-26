{{-- Journal d'activité groupé par jour. Paramètres : $lignes, $idJournal, $filtres (puces par domaine + recherche), $recherche (recherche seule) --}}
@php
    $DOM = \App\Services\JournalActivite::DOMAINES;
    $J = \App\Services\JournalActivite::class;
    $lignes = collect($lignes)->values();
    $parJour = $lignes->groupBy(fn($l) => $l['quand']->toDateString());
    $limite = 150;
@endphp
@if($lignes->isEmpty())
<p class="vide">Aucune action tracée sur cette période.</p>
@else
<div id="{{ $idJournal }}" class="jr">
    @if(($filtres ?? false) || ($recherche ?? false))
    <div class="jr-outils">
        @if($filtres ?? false)
        <button type="button" class="jr-puce actif" data-dom="" title="Toutes les actions, hors connexions">Actions <span>{{ $lignes->where('domaine', '!=', 'connexion')->count() }}</span></button>
        @foreach($lignes->countBy('domaine')->sortDesc() as $d => $n)
        <button type="button" class="jr-puce" data-dom="{{ $d }}"><i style="background:{{ $DOM[$d]['couleur'] }}"></i>{{ $DOM[$d]['libelle'] }} <span>{{ $n }}</span></button>
        @endforeach
        @endif
        <input type="search" class="jr-recherche" placeholder="Rechercher (action, objet, détail)…">
    </div>
    @endif
    @foreach($parJour as $jour => $lj)
    <div class="jr-groupe">
        <div class="jr-jour">{{ ucfirst(\Carbon\Carbon::parse($jour)->translatedFormat('l j F Y')) }}<span class="jr-nb">{{ $lj->count() }} action{{ $lj->count() > 1 ? 's' : '' }}</span></div>
        @foreach($lj as $l)
        @php $d = $DOM[$l['domaine']]; @endphp
        <div class="jr-ligne" data-dom="{{ $l['domaine'] }}">
            <span class="h">{{ $l['heure'] ? $l['quand']->format('H:i') : '' }}</span>
            <span class="jr-dom" style="background:{{ $d['fond'] }};color:{{ $d['couleur'] }}">{{ $d['libelle'] }}</span>
            <span class="jr-quoi">
                <strong>{{ $J::libelleModule($l['module']) }} · {{ $J::libelleAction($l['action']) }}</strong>@if($l['cible'])<span class="cible"> : {{ \Illuminate\Support\Str::limit($l['cible'], 140) }}</span>@endif
                @if($l['details'])<span class="det" title="{{ \Illuminate\Support\Str::limit($l['details'], 600) }}">{{ \Illuminate\Support\Str::limit($l['details'], 260) }}</span>@endif
            </span>
        </div>
        @endforeach
    </div>
    @endforeach
    @if($lignes->count() > $limite)
    <button type="button" class="btn sec sm jr-plus">Afficher la suite</button>
    @endif
</div>
<script>
(function(){
    const racine = document.getElementById(@json($idJournal));
    const LIMITE = {{ $limite }};
    let domaine = '', terme = '', max = LIMITE;
    const norm = s => (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
    const filtrable = !!racine.querySelector('.jr-puce');
    const lignes = [...racine.querySelectorAll('.jr-ligne')].map(el => ({el, txt: norm(el.textContent)}));
    function appliquer(){
        let vus = 0;
        lignes.forEach(l => {
            // Par défaut, les connexions (bruit) sont masquées ; la puce « Connexions » les affiche
            const ok = (domaine ? l.el.dataset.dom === domaine : l.el.dataset.dom !== 'connexion' || !filtrable) && (!terme || l.txt.includes(terme));
            const montre = ok && vus < max;
            if (ok) vus++;
            l.el.style.display = montre ? '' : 'none';
        });
        racine.querySelectorAll('.jr-groupe').forEach(g => {
            const n = [...g.querySelectorAll('.jr-ligne')].filter(x => x.style.display !== 'none').length;
            g.style.display = n ? '' : 'none';
            g.querySelector('.jr-nb').textContent = n + ' action' + (n > 1 ? 's' : '');
        });
        const plus = racine.querySelector('.jr-plus');
        if (plus) plus.style.display = vus > max ? '' : 'none';
    }
    racine.querySelectorAll('.jr-puce').forEach(b => b.addEventListener('click', () => {
        racine.querySelectorAll('.jr-puce').forEach(x => x.classList.toggle('actif', x === b));
        domaine = b.dataset.dom; max = LIMITE; appliquer();
    }));
    const r = racine.querySelector('.jr-recherche');
    if (r) r.addEventListener('input', () => { terme = norm(r.value.trim()); max = LIMITE; appliquer(); });
    const plus = racine.querySelector('.jr-plus');
    if (plus) plus.addEventListener('click', () => { max += LIMITE; appliquer(); });
    appliquer();
})();
</script>
@endif
