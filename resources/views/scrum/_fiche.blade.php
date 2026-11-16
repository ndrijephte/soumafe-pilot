{{-- Fiche d'identité du sprint : objectif, période, durée, story points (bandeau compact). Paramètre : $sprint --}}
@once
@push('styles')
<style>
.fiche-sp{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:22px;align-items:center;background:linear-gradient(180deg,#F5F9FE,#fff 70%);border:1px solid #dbe7f5;border-radius:12px;padding:12px 18px;margin-bottom:14px}
.fiche-sp .obj{display:flex;gap:12px;align-items:flex-start;min-width:0}
.fiche-sp .obj-ic{flex:none;width:34px;height:34px;border-radius:10px;background:#EAF2FB;color:#2e74b5;display:flex;align-items:center;justify-content:center}
.fiche-sp .obj-ic svg{width:18px;height:18px}
.fiche-sp .lib{display:block;font-size:10.5px;font-weight:700;color:#2e74b5;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px}
.fiche-sp .obj-txt{font-size:13px;color:#1e293b;line-height:1.55;font-weight:500}
.fiche-sp .obj-liste{list-style:none;margin:0;padding:0;display:grid;gap:3px}
.fiche-sp .obj-liste li{position:relative;padding-left:20px;font-size:13px;color:#1e293b;line-height:1.5}
.fiche-sp .obj-liste li::before{content:'';position:absolute;left:2px;top:.45em;width:10px;height:10px;border-radius:50%;border:2px solid #2e74b5;box-sizing:border-box;background:#fff}
.fiche-sp .obj-liste li::after{content:'';position:absolute;left:5px;top:calc(.45em + 3px);width:4px;height:4px;border-radius:50%;background:#2e74b5}
.fiche-sp .court{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.fiche-sp .obj-liste.court{display:grid}.fiche-sp .obj-liste.court li:nth-child(n+4){display:none}
.fiche-sp.ouvert .court{-webkit-line-clamp:unset;display:block}.fiche-sp.ouvert .obj-liste.court{display:grid}.fiche-sp.ouvert .obj-liste.court li{display:list-item;display:block}
.fiche-sp .plus{background:none;border:none;padding:0;margin-top:4px;font-size:11.5px;color:#2e74b5;cursor:pointer;font-weight:600}
.fiche-sp .vide{font-size:12.5px;color:#94a3b8;font-style:italic}
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
    // Objectif découpé en objectifs distincts (lignes ou « ; ») : liste ; sinon texte, replié au-delà de 2 lignes
    $objectifs = $sprint->objectifs();
    $long = count($objectifs) > 3 || (count($objectifs) === 1 && mb_strlen($objectifs[0]) > 200);
@endphp
<div class="fiche-sp" id="fiche-sp">
    <div class="obj">
        <span class="obj-ic" aria-hidden="true">{!! $svg('<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>') !!}</span>
        <div style="min-width:0">
            <span class="lib">{{ count($objectifs) > 1 ? 'Objectifs du sprint' : 'Objectif du sprint' }}</span>
            @if(!$objectifs)<div class="vide">Aucun objectif défini.</div>
            @elseif(count($objectifs) > 1)
            <ul class="obj-liste {{ $long ? 'court' : '' }}">@foreach($objectifs as $o)<li>{{ $o }}</li>@endforeach</ul>
            @else<div class="obj-txt {{ $long ? 'court' : '' }}">{{ $objectifs[0] }}</div>@endif
            @if($long)<button type="button" class="plus" onclick="const f=document.getElementById('fiche-sp');f.classList.toggle('ouvert');this.textContent=f.classList.contains('ouvert')?'Réduire':'Lire la suite'">Lire la suite</button>@endif
        </div>
    </div>
    <div class="meta">
        <span title="Période du sprint">{!! $svg('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!}<b>{{ $sprint->date_debut->format('d/m') }}</b> → <b>{{ $sprint->date_fin->format('d/m/Y') }}</b></span>
        <span title="Durée en jours travaillés">{!! $svg('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>') !!}<b>{{ $sprint->dureeJours() }}</b> jours ouvrés</span>
        <span title="Story points livrés / engagés{{ $nonEstimees ? ' - ' . $nonEstimees . ' story(ies) non estimée(s)' : '' }}">{!! $svg('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>') !!}<b>{{ $ptsLiv }}</b>/{{ $ptsEng }} pts <span class="pts-bar"><i style="width:{{ $ptsEng ? round($ptsLiv / $ptsEng * 100) : 0 }}%"></i></span></span>
    </div>
</div>
