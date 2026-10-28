{{-- Fiche d'identité du sprint (objectif, dates, durée, story points). Paramètre : $sprint --}}
@once
@push('styles')
<style>
.fiche-sp{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:14px}
/* Objectif sur toute la largeur, les quatre indicateurs en dessous */
.fiche-sp .obj{grid-column:1/-1}
.fiche-sp>div{background:#fff;border:1px solid var(--bord);border-radius:10px;padding:10px 14px;min-width:0;display:flex;gap:10px;align-items:flex-start}
.fiche-sp>div>span.contenu{min-width:0;flex:1}
/* Pastille d'icône */
.fiche-sp .ic{flex:none;width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:#eef4fb;color:#2e74b5}
.fiche-sp .ic.vert{background:#ecfdf3;color:#15803d}.fiche-sp .ic.orange{background:#fff7ed;color:#c2410c}.fiche-sp .ic.violet{background:#f3f0ff;color:#6d28d9}
.fiche-sp .obj .ic{background:#2e74b5;color:#fff}
.fiche-sp .lib{display:block;font-size:10.5px;font-weight:700;color:var(--gris);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
.fiche-sp .val{font-size:15px;font-weight:700;color:var(--bleu);line-height:1.3}
.fiche-sp .val small{display:block;font-size:11.5px;font-weight:500;color:var(--gris);margin-top:2px}
/* Objectif : cadre fin du même bleu, fond très légèrement teinté */
.fiche-sp .obj{border:1.5px solid #93c5fd;background:#f8fbff}
.fiche-sp .obj .lib{color:#2e74b5}
.fiche-sp .obj .val{font-size:13.5px;font-weight:400;color:#64748b;white-space:pre-line;line-height:1.55}
@media(max-width:1000px){.fiche-sp{grid-template-columns:repeat(2,minmax(0,1fr))}.fiche-sp .obj{grid-column:1/-1}}
</style>
@endpush
@endonce
@php
    $ptsEng = $sprint->pointsEngages();
    $ptsLiv = $sprint->pointsLivres();
    $nonEstimees = $sprint->userStories->whereNull('effort_estime')->count();
@endphp
<div class="fiche-sp">
    <div class="obj"><span class="ic"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></span><span class="contenu"><span class="lib">Objectif du sprint</span><div class="val">{{ $sprint->objectif ?: '—' }}</div></span></div>
    <div><span class="ic vert"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><polyline points="9 15 11 17 15 13"/></svg></span><span class="contenu"><span class="lib">Date de début</span><div class="val">{{ $sprint->date_debut->format('d/m/Y') }}<small>{{ ucfirst($sprint->date_debut->translatedFormat('l')) }}</small></div></span></div>
    <div><span class="ic orange"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></span><span class="contenu"><span class="lib">Date de fin</span><div class="val">{{ $sprint->date_fin->format('d/m/Y') }}<small>{{ ucfirst($sprint->date_fin->translatedFormat('l')) }}</small></div></span></div>
    <div><span class="ic"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><span class="contenu"><span class="lib">Durée</span><div class="val">{{ $sprint->dureeJours() }} jours<small>ouvrés</small></div></span></div>
    <div><span class="ic violet"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></span><span class="contenu"><span class="lib">Story points</span><div class="val">{{ $ptsLiv }} / {{ $ptsEng }}<small>livrés / engagés @if($nonEstimees) · {{ $nonEstimees }} story(s) non estimée(s) @endif</small></div></span></div>
</div>
