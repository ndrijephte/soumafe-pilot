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
/* Fiche du voyant : en-tête teinté par l'urgence, lignes à icône, accès en pied */
#voyant-pop{position:fixed;z-index:10050;display:none;width:286px;background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:14px;box-shadow:0 1px 2px rgba(15,23,42,.06),0 18px 40px -8px rgba(15,23,42,.22);font-size:12px;color:#334155;overflow:hidden;opacity:0;transform:translateY(4px);transition:opacity .14s ease,transform .14s ease}
#voyant-pop.ouvert{display:block}
#voyant-pop.visible{opacity:1;transform:none}
#voyant-pop .tete{position:relative;padding:12px 14px 11px 16px;background:linear-gradient(0deg,#fff,var(--vf));border-bottom:1px solid #f1f5f9}
#voyant-pop .tete::before{content:"";position:absolute;left:0;top:12px;bottom:11px;width:3px;border-radius:0 3px 3px 0;background:var(--v)}
#voyant-pop .niveau{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:var(--v)}
#voyant-pop .niveau i{width:7px;height:7px;border-radius:50%;background:var(--v);box-shadow:0 0 0 3px #fff}
#voyant-pop .t{margin-top:4px;font-weight:700;color:#0f172a;font-size:13px;line-height:1.35}
#voyant-pop dl{margin:0;padding:6px 8px}
#voyant-pop .ligne{display:grid;grid-template-columns:22px 82px minmax(0,1fr);align-items:center;gap:0 6px;padding:5px 6px;border-radius:8px}
#voyant-pop .ligne:hover{background:#f8fafc}
#voyant-pop .ligne svg{width:14px;height:14px;color:#94a3b8}
#voyant-pop dt{font-size:11px;color:#64748b}
#voyant-pop dd{margin:0;color:#0f172a;font-weight:600;overflow-wrap:anywhere}
#voyant-pop dd .pastille{display:inline-block;padding:2px 9px;border-radius:999px;background:var(--vf);color:var(--v);font-size:11px;font-weight:700}
#voyant-pop a{display:flex;align-items:center;justify-content:space-between;margin:0;padding:9px 16px;border-top:1px solid #f1f5f9;color:#2e74b5;font-weight:600;text-decoration:none;background:#fbfdff}
#voyant-pop a:hover{background:#eef4fb}
#voyant-pop a svg{width:14px;height:14px;transition:transform .15s}
#voyant-pop a:hover svg{transform:translateX(2px)}
@media(prefers-reduced-motion:reduce){#voyant-pop{transition:none}}
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
        pop.classList.remove('ouvert', 'visible');
        if (actif) { actif.classList.remove('ouvert'); actif.setAttribute('aria-expanded', 'false'); }
        actif = null; epingle = false;
    }
    // Icône de chaque rubrique de la fiche
    const ICONES = {
        'Statut': '<circle cx="12" cy="12" r="9"/><polyline points="8 12 11 15 16 9"/>',
        'Échéance': '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'Priorité': '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
        'Assignée à': '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'Projet': '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
        'User story': '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
    };
    const svg = d => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + d + '</svg>';
    const el = (tag, cls, txt) => { const n = document.createElement(tag); if (cls) n.className = cls; if (txt !== undefined) n.textContent = txt; return n; };
    function ouvrir(v) {
        clearTimeout(minuteur);
        if (actif && actif !== v) actif.classList.remove('ouvert');
        actif = v; v.classList.add('ouvert'); v.setAttribute('aria-expanded', 'true');
        const style = getComputedStyle(v);
        pop.style.setProperty('--v', style.getPropertyValue('--v'));
        pop.style.setProperty('--vf', style.getPropertyValue('--vf'));
        pop.textContent = '';
        const tete = el('div', 'tete');
        if (v.dataset.vNiveau) { const n = el('div', 'niveau'); n.append(el('i'), document.createTextNode(v.dataset.vNiveau)); tete.appendChild(n); }
        tete.appendChild(el('div', 't', v.dataset.vTitre));
        pop.appendChild(tete);
        const lignes = JSON.parse(v.dataset.vLignes || '{}');
        if (Object.keys(lignes).length) {
            const dl = el('dl');
            Object.entries(lignes).forEach(([k, val]) => {
                const l = el('div', 'ligne');
                l.insertAdjacentHTML('beforeend', svg(ICONES[k] || '<circle cx="12" cy="12" r="2"/>'));
                const dd = el('dd');
                if (k === 'Statut') dd.appendChild(el('span', 'pastille', val)); else dd.textContent = val;
                l.append(el('dt', '', k), dd);
                dl.appendChild(l);
            });
            pop.appendChild(dl);
        }
        if (v.dataset.vLien) {
            const a = el('a'); a.href = v.dataset.vLien; a.appendChild(document.createTextNode(v.dataset.vLienTexte));
            a.insertAdjacentHTML('beforeend', svg('<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>'));
            pop.appendChild(a);
        }
        pop.classList.remove('visible');
        pop.classList.add('ouvert');
        requestAnimationFrame(() => pop.classList.add('visible'));
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
    data-v-titre="{{ $u['libelle'] }}" @if($tache) data-v-niveau="{{ \App\Models\Tache::URGENCES[$u['niveau']] ?? '' }}" @endif data-v-lignes="{{ json_encode((object) $lignes, JSON_UNESCAPED_UNICODE) }}" @if($lien) data-v-lien="{{ $lien }}" data-v-lien-texte="{{ $lienTexte }}" @endif></button>@if($texte && ($u['court'] ?? ''))<span class="voyant-court">{{ $u['court'] }}</span>@endif
