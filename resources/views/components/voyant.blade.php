{{--
    Voyant d'urgence interactif : point plein, couleur selon l'urgence, onde autour du point si l'urgence le demande ;
    au survol, au focus ou au clic, une fiche donne le détail et un lien.
    <x-voyant :tache="$t" />                 tâche
    <x-voyant :tache="$t" texte />           tâche, avec le libellé court à côté
    <x-voyant niveau="retard" titre="…" />   valeur libre
--}}
@props(['tache' => null, 'niveau' => null, 'titre' => null, 'lignes' => [], 'lien' => null, 'lienTexte' => 'Ouvrir', 'texte' => false])
@php
    $u = $tache?->urgence() ?? ['niveau' => $niveau ?? 'aucune', 'libelle' => $titre ?? '', 'court' => ''];
    if ($tache) {
        $lignes = array_filter([
            'Statut' => $tache->labelStatut(),
            'Échéance' => $tache->echeance?->format('d/m/Y') ?? 'Non définie',
            'Priorité' => ucfirst((string) $tache->priorite),
            'Assignée à' => $tache->relationLoaded('user') ? $tache->user?->nomComplet() : null,
            'Projet' => $tache->relationLoaded('projet') ? $tache->projet?->libelle : null,
            'User story' => $tache->relationLoaded('userStory') ? $tache->userStory?->numero : null,
        ]);
        $lien ??= route('taches.show', $tache);
        $lienTexte = 'Ouvrir la tâche';
    }
@endphp
@once
@push('styles')
<style>
.voyant{appearance:none;position:relative;display:inline-block;box-sizing:border-box;width:9px;height:9px;padding:0;margin:0;border:0;border-radius:50%;background:var(--v);cursor:pointer;flex:none;vertical-align:1px;transition:transform .12s,box-shadow .12s}
.voyant::before{content:"";position:absolute;inset:-7px;border-radius:50%}
.voyant:hover,.voyant.ouvert{transform:scale(1.3);box-shadow:0 0 0 3px var(--vf)}
.voyant:focus-visible{outline:2px solid #1f4e79;outline-offset:3px}
.v-retard{--v:#dc2626;--vf:#fee2e2}.v-bloque{--v:#7c3aed;--vf:#ede9fe}.v-proche{--v:#ea580c;--vf:#ffedd5}.v-semaine{--v:#ca8a04;--vf:#fef9c3}
.v-ok{--v:#16a34a;--vf:#dcfce7}.v-aucune{--v:#94a3b8;--vf:#f1f5f9}.v-fait{--v:#2e74b5;--vf:#dbeafe}.v-clos{--v:#64748b;--vf:#e2e8f0}
/* Pulsion : une onde part du point. Plus c'est urgent, plus elle est rapide et ample ; rien tant qu'il n'y a pas lieu d'agir, ni pendant la lecture de la fiche */
.voyant.v-retard::after,.voyant.v-bloque::after,.voyant.v-proche::after{content:"";position:absolute;inset:0;border-radius:50%;background:var(--v);pointer-events:none;animation:voyant-onde var(--onde-duree) ease-out infinite}
.voyant.v-retard{--onde-duree:1.4s;--onde-ampleur:3}.voyant.v-bloque{--onde-duree:2s;--onde-ampleur:2.6}.voyant.v-proche{--onde-duree:2.8s;--onde-ampleur:2.2}
.voyant:hover::after,.voyant.ouvert::after{animation:none!important;opacity:0}
@keyframes voyant-onde{0%{transform:scale(1);opacity:.55}100%{transform:scale(var(--onde-ampleur));opacity:0}}
@media(prefers-reduced-motion:reduce){.voyant::after{animation:none!important;opacity:0}}
.voyant-court{font-size:11px;font-weight:600;color:#475569;margin-left:5px;white-space:nowrap}
#voyant-pop{position:fixed;z-index:10050;display:none;width:240px;background:#fff;border:1px solid #e2e8f0;border-top:4px solid var(--v);border-radius:10px;box-shadow:0 12px 28px rgba(15,23,42,.16);padding:10px 12px;font-size:12px;color:#334155}
#voyant-pop.ouvert{display:block}
#voyant-pop .t{font-weight:700;color:#1f2937;margin-bottom:6px;font-size:12.5px}
#voyant-pop dl{display:grid;grid-template-columns:auto minmax(0,1fr);gap:2px 10px;margin:0}
#voyant-pop dt{color:#64748b}#voyant-pop dd{margin:0;color:#1f2937;overflow-wrap:anywhere}
#voyant-pop a{display:inline-block;margin-top:8px;color:#2e74b5;font-weight:600;text-decoration:none}
#voyant-pop a:hover{text-decoration:underline}
</style>
@endpush
@push('scripts')
<script>
(function () {
    const pop = document.createElement('div');
    pop.id = 'voyant-pop'; pop.setAttribute('role', 'tooltip');
    document.body.appendChild(pop);
    let actif = null, epingle = false, minuteur = null;

    function fermer() {
        clearTimeout(minuteur);
        pop.classList.remove('ouvert');
        if (actif) { actif.classList.remove('ouvert'); actif.setAttribute('aria-expanded', 'false'); }
        actif = null; epingle = false;
    }
    function ouvrir(v) {
        clearTimeout(minuteur);
        if (actif && actif !== v) actif.classList.remove('ouvert');
        actif = v; v.classList.add('ouvert'); v.setAttribute('aria-expanded', 'true');
        pop.style.setProperty('--v', getComputedStyle(v).getPropertyValue('--v'));
        pop.textContent = '';
        const t = document.createElement('div'); t.className = 't'; t.textContent = v.dataset.vTitre; pop.appendChild(t);
        const lignes = JSON.parse(v.dataset.vLignes || '{}');
        if (Object.keys(lignes).length) {
            const dl = document.createElement('dl');
            Object.entries(lignes).forEach(([k, val]) => {
                const dt = document.createElement('dt'); dt.textContent = k;
                const dd = document.createElement('dd'); dd.textContent = val;
                dl.append(dt, dd);
            });
            pop.appendChild(dl);
        }
        if (v.dataset.vLien) {
            const a = document.createElement('a'); a.href = v.dataset.vLien; a.textContent = v.dataset.vLienTexte + ' ›'; pop.appendChild(a);
        }
        pop.classList.add('ouvert');
        const r = v.getBoundingClientRect(), h = pop.offsetHeight, w = pop.offsetWidth;
        pop.style.left = Math.max(8, Math.min(r.left + r.width / 2 - w / 2, innerWidth - w - 8)) + 'px';
        pop.style.top = Math.max(8, Math.min(r.bottom + 8 + h > innerHeight ? r.top - h - 8 : r.bottom + 8, innerHeight - h - 8)) + 'px';
    }
    const plusTard = () => { clearTimeout(minuteur); if (!epingle) minuteur = setTimeout(fermer, 180); };

    document.addEventListener('mouseover', e => {
        const v = e.target.closest && e.target.closest('.voyant[data-v-titre]');
        if (v && !epingle) ouvrir(v);
        else if (e.target.closest && e.target.closest('#voyant-pop')) clearTimeout(minuteur);
    });
    document.addEventListener('mouseout', e => {
        if (e.target.closest && e.target.closest('.voyant[data-v-titre], #voyant-pop')) plusTard();
    });
    document.addEventListener('focusin', e => { if (e.target.matches && e.target.matches('.voyant[data-v-titre]') && !epingle) ouvrir(e.target); });
    document.addEventListener('focusout', e => { if (e.target.matches && e.target.matches('.voyant[data-v-titre]')) plusTard(); });
    // Clic : épingle la fiche (indispensable sur écran tactile) ; second clic ou clic ailleurs : referme
    document.addEventListener('click', e => {
        const v = e.target.closest && e.target.closest('.voyant[data-v-titre]');
        if (v) {
            e.preventDefault(); e.stopPropagation();
            if (actif === v && epingle) { fermer(); return; }
            ouvrir(v); epingle = true;
        } else if (!(e.target.closest && e.target.closest('#voyant-pop'))) fermer();
    }, true);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') fermer(); });
    window.addEventListener('scroll', fermer, true);
})();
</script>
@endpush
@endonce
<button type="button" {{ $attributes->merge(['class' => 'voyant v-' . $u['niveau']]) }} aria-label="{{ $u['libelle'] }}" aria-expanded="false" draggable="false"
    data-v-titre="{{ $u['libelle'] }}" data-v-lignes="{{ json_encode((object) $lignes, JSON_UNESCAPED_UNICODE) }}" @if($lien) data-v-lien="{{ $lien }}" data-v-lien-texte="{{ $lienTexte }}" @endif></button>@if($texte && ($u['court'] ?? ''))<span class="voyant-court">{{ $u['court'] }}</span>@endif
