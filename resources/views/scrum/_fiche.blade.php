{{-- Fiche d'identité du sprint : objectif, période, durée, story points (bandeau compact). Paramètre : $sprint --}}
@once
@push('styles')
<style>
/* Objectif du sprint : mis en avant, fond bleu doux ; période, durée et points en pastilles */
.fiche-sp{position:relative;background:#F2F7FD;border:1px solid #D6E5F5;border-radius:14px;padding:14px 20px 16px 20px;margin-bottom:14px;overflow:hidden}
.fiche-sp::after{content:"";position:absolute;right:-40px;top:-40px;width:160px;height:160px;border-radius:50%;border:22px solid rgba(46,116,181,.05);pointer-events:none}
.fiche-sp .tete{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:8px}
.fiche-sp .obj-ic{flex:none;width:26px;height:26px;border-radius:50%;background:#2e74b5;color:#fff;display:flex;align-items:center;justify-content:center}
.fiche-sp .obj-ic svg{width:15px;height:15px}
.fiche-sp .lib{font-size:11px;font-weight:700;color:#1f4e79;text-transform:uppercase;letter-spacing:.7px}
.fiche-sp .meta{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-left:auto;position:relative;z-index:1}
.fiche-sp .meta>span{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;background:#2e74b5;border:1px solid #2e74b5;border-radius:999px;font-size:11.5px;color:#dbeafe;white-space:nowrap;box-shadow:0 1px 2px rgba(31,78,121,.2)}
.fiche-sp .meta svg{width:13px;height:13px;color:#fff;flex:none}
.fiche-sp .meta b{color:#fff;font-weight:700}
.fiche-sp .meta>span.non-estimees{background:#fff7ed;border-color:#fdba74;color:#9a3412;box-shadow:none}
.fiche-sp .meta>span.non-estimees svg,.fiche-sp .meta>span.non-estimees b{color:#c2410c}
.fiche-sp .pts-bar{width:44px;height:4px;background:rgba(255,255,255,.3);border-radius:4px;overflow:hidden}.fiche-sp .pts-bar i{display:block;height:100%;background:#fff}
.fiche-sp .corps{position:relative;z-index:1;padding-left:36px}
.fiche-sp .obj-txt{font-size:13px;color:#2e74b5;line-height:1.6;font-weight:700}
.fiche-sp .obj-liste{list-style:none;margin:0;padding:0;display:grid;gap:4px}
.fiche-sp .obj-liste li{position:relative;padding-left:20px;font-size:13px;color:#2e74b5;line-height:1.55;font-weight:700}
.fiche-sp .obj-liste li::before{content:'';position:absolute;left:2px;top:.45em;width:10px;height:10px;border-radius:50%;border:2px solid #2e74b5;box-sizing:border-box;background:#fff}
.fiche-sp .obj-liste li::after{content:'';position:absolute;left:5px;top:calc(.45em + 3px);width:4px;height:4px;border-radius:50%;background:#2e74b5}
.fiche-sp .court{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.fiche-sp .obj-liste.court{display:grid}.fiche-sp .obj-liste.court li:nth-child(n+4){display:none}
.fiche-sp.ouvert .court{-webkit-line-clamp:unset;display:block}.fiche-sp.ouvert .obj-liste.court{display:grid}.fiche-sp.ouvert .obj-liste.court li{display:block}
.fiche-sp .plus{background:none;border:none;padding:0;margin-top:6px;font-size:11.5px;color:#2e74b5;cursor:pointer;font-weight:600}
.fiche-sp .vide{font-size:13px;color:#94a3b8;font-style:italic}
@media(max-width:800px){.fiche-sp .meta{margin-left:0;width:100%}.fiche-sp .corps{padding-left:0}}
</style>
@endpush
@endonce
@php
    $ptsEng = $sprint->pointsEngages();
    $ptsLiv = $sprint->pointsLivres();
    $nonEstimees = $sprint->storiesNonEstimees();
    $ptsReal = $sprint->pointsRealises();
    $svg = fn($d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
    // Objectif découpé en objectifs distincts (lignes ou « ; ») : liste ; sinon texte, replié au-delà de 2 lignes
    $objectifs = $sprint->objectifs();
    $long = count($objectifs) > 3 || (count($objectifs) === 1 && mb_strlen($objectifs[0]) > 200);
@endphp
<div class="fiche-sp" id="fiche-sp">
    <div class="tete">
        <span class="obj-ic" aria-hidden="true">{!! $svg('<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5"/>') !!}</span>
        <span class="lib">{{ count($objectifs) > 1 ? 'Objectifs du sprint' : 'Objectif du sprint' }}</span>
        <div class="meta">
            <span title="Période du sprint">{!! $svg('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!}<b>{{ $sprint->date_debut->format('d/m') }}</b> → <b>{{ $sprint->date_fin->format('d/m/Y') }}</b></span>
            <span title="Durée en jours travaillés">{!! $svg('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>') !!}<b>{{ $sprint->dureeJours() }}</b> jours ouvrés</span>
            <span title="{{ $sprint->resumePoints() }}">{!! $svg('<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>') !!}<b>{{ $ptsLiv }}</b>/{{ $ptsEng }} pts @if($ptsReal > $ptsLiv) <small style="color:#dbeafe">· {{ $ptsReal }} réalisés</small>@endif <span class="pts-bar"><i style="width:{{ $ptsEng ? min(100, round($ptsReal / $ptsEng * 100)) : 0 }}%"></i></span></span>
            @if($nonEstimees && $sprint->statut !== 'termine')<span class="non-estimees" title="Leur travail n'entre pas dans les story points : estimez-les dans le Sprint Backlog">{!! $svg('<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') !!}<b>{{ $nonEstimees }}</b> non estimée{{ $nonEstimees > 1 ? 's' : '' }}</span>@endif
        </div>
    </div>
    <div class="corps">
        @if(!$objectifs)<div class="vide">Aucun objectif défini.</div>
        @elseif(count($objectifs) > 1)
        <ul class="obj-liste {{ $long ? 'court' : '' }}">@foreach($objectifs as $o)<li>{{ $o }}</li>@endforeach</ul>
        @else<div class="obj-txt {{ $long ? 'court' : '' }}">{{ $objectifs[0] }}</div>@endif
        @if($long)<button type="button" class="plus" onclick="const f=document.getElementById('fiche-sp');f.classList.toggle('ouvert');this.textContent=f.classList.contains('ouvert')?'Réduire':'Lire la suite'">Lire la suite</button>@endif
    </div>
</div>
