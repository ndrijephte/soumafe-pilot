{{-- Fiche d'identité du sprint : objectif, période, durée, story points (bandeau compact). Paramètre : $sprint --}}
@once
@push('styles')
<style>
.fiche-sp{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center;background:#fff;border:1px solid var(--bord);border-left:3px solid #93c5fd;border-radius:10px;padding:10px 16px;margin-bottom:14px}
.fiche-sp .lib{display:block;font-size:10px;font-weight:700;color:#2e74b5;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px}
.fiche-sp .obj-txt{font-size:12.5px;color:#64748b;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.fiche-sp.ouvert .obj-txt{-webkit-line-clamp:unset;display:block;white-space:pre-line}
.fiche-sp .plus{background:none;border:none;padding:0;margin-top:2px;font-size:11px;color:#2e74b5;cursor:pointer;font-weight:600}
.fiche-sp .meta{display:flex;gap:18px;align-items:center;white-space:nowrap;font-size:12px;color:#475569}
.fiche-sp .meta>span{display:flex;align-items:center;gap:6px}
.fiche-sp .meta svg{width:14px;height:14px;color:#94a3b8;flex:none}
.fiche-sp .meta b{color:#1f2937;font-weight:600}
.fiche-sp .meta small{color:#94a3b8}
.fiche-sp .pts-bar{width:54px;height:5px;background:#e5eaf1;border-radius:5px;overflow:hidden}.fiche-sp .pts-bar i{display:block;height:100%;background:#2e74b5}
@media(max-width:1000px){.fiche-sp{grid-template-columns:1fr}.fiche-sp .meta{flex-wrap:wrap;white-space:normal;gap:8px 16px}}
</style>
@endpush
@endonce
@php
    $ptsEng = $sprint->pointsEngages();
    $ptsLiv = $sprint->pointsLivres();
    $nonEstimees = $sprint->userStories->whereNull('effort_estime')->count();
    $svg = fn($d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
    $long = mb_strlen((string) $sprint->objectif) > 180;
@endphp
<div class="fiche-sp" id="fiche-sp">
    <div style="min-width:0">
        <span class="lib">Objectif du sprint</span>
        <div class="obj-txt">{{ $sprint->objectif ?: '-' }}</div>
        @if($long)<button type="button" class="plus" onclick="const f=document.getElementById('fiche-sp');f.classList.toggle('ouvert');this.textContent=f.classList.contains('ouvert')?'Réduire':'Lire la suite'">Lire la suite</button>@endif
    </div>
    <div class="meta">
        <span title="Période du sprint">{!! $svg('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!}<b>{{ $sprint->date_debut->format('d/m') }}</b> → <b>{{ $sprint->date_fin->format('d/m/Y') }}</b></span>
        <span title="Durée en jours travaillés">{!! $svg('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>') !!}<b>{{ $sprint->dureeJours() }}</b> jours ouvrés</span>
        <span title="Story points livrés / engagés{{ $nonEstimees ? ' - ' . $nonEstimees . ' story(ies) non estimée(s)' : '' }}">{!! $svg('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>') !!}<b>{{ $ptsLiv }}</b>/{{ $ptsEng }} pts <span class="pts-bar"><i style="width:{{ $ptsEng ? round($ptsLiv / $ptsEng * 100) : 0 }}%"></i></span></span>
    </div>
</div>
