{{--
    Graphe d'évolution recettes / objectif / dépenses en courbes (rapport du parc, rapport d'un véhicule).
    Paramètres : $evolution (points du contrôleur), $cle (identifiant unique), $titre, $icone (facultatif), $compact (colonne étroite).
--}}
@php $compact = $compact ?? false; $F ??= fn($n) => number_format((int) $n, 0, ',', ' '); @endphp
@once
<style>
/* Évolution (courbes) */
.evo-tete{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.evo-tete h3{margin-bottom:0}
.evo-mode{display:inline-flex;background:#f1f5f9;border-radius:9px;padding:3px}
.evo-mode button{border:none;background:none;border-radius:7px;padding:4px 12px;font-size:12px;font-weight:600;color:#475569;cursor:pointer;font-family:inherit}
.evo-mode button.on{background:#fff;color:var(--bleu);box-shadow:0 1px 3px rgba(15,23,42,.12)}
.evo-legende{display:flex;flex-wrap:wrap;gap:4px 20px;align-items:center;margin:12px 0 6px}
.evo-legende button{display:inline-flex;align-items:center;gap:7px;padding:2px 0;border:none;background:none;font-size:12px;color:#475569;cursor:pointer;font-family:inherit;opacity:.4}
.evo-legende button.on{opacity:1}
.evo-legende button b,.evo-solde b{color:#1e293b;font-weight:600;font-variant-numeric:tabular-nums}
.evo-legende i.trait{width:16px;height:2.5px;border-radius:2px}
.evo-legende i.trait.pointille{background:repeating-linear-gradient(90deg,#64748b 0 5px,transparent 5px 8px)}
.evo-taux{font-size:11px;font-weight:600;margin-left:2px}
.evo-taux.ok,.evo-bulle em.ok,.evo-bulle b.ok{color:var(--ok)}.evo-taux.ko,.evo-bulle em.ko,.evo-bulle b.ko{color:var(--ko)}
.evo-solde{margin-left:auto;font-size:12px;color:var(--gris)}
.evo-solde b.ok{color:var(--ok)}.evo-solde b.ko{color:var(--ko)}
.evo-zone{position:relative}
.evo-bulle{position:absolute;top:10px;width:268px;background:#fff;border:1px solid var(--bord);border-radius:10px;box-shadow:0 8px 24px rgba(15,23,42,.12);padding:10px 12px;font-size:12px;pointer-events:none;opacity:0;transition:opacity .12s}
.evo-bulle.on{opacity:1}
.evo-bulle strong{display:block;margin-bottom:6px;color:var(--bleu);font-weight:600}
.evo-bulle div{display:flex;align-items:center;gap:7px;padding:2px 0;color:#475569}
.evo-bulle div i{width:12px;height:3px;border-radius:2px;flex:none}
.evo-bulle div b{margin-left:auto;color:#1e293b;font-weight:600;font-variant-numeric:tabular-nums;white-space:nowrap}
.evo-bulle div span{white-space:nowrap}
.evo-bulle div em{font-style:normal;font-size:11px;font-weight:600;min-width:34px;text-align:right}
.evo-bulle .det{padding-left:19px;font-size:11px;color:var(--gris)}
.evo-bulle .sol{border-top:1px solid #eef2f7;margin-top:5px;padding-top:6px}
.evo-table{margin-top:8px}
.evo-table summary{cursor:pointer;font-size:12px;color:var(--accent);font-weight:600;width:max-content}
.evo-table table{margin-top:8px}
.evo-bloc .rp-scroll{overflow-x:auto}
.evo-bloc .evo-table .rp-tbl{width:100%;border-collapse:collapse;font-size:12px}
.evo-bloc .evo-table .rp-tbl th{text-align:left;padding:7px 8px;color:var(--gris);font-size:10.5px;text-transform:uppercase;border-bottom:1px solid var(--bord);white-space:nowrap}
.evo-bloc .evo-table .rp-tbl td{padding:6px 8px;border-bottom:1px solid #f1f5f9}
.evo-bloc .evo-table .num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
</style>
@endonce
<div class="evo-bloc">
    @php $pts = collect($evolution['points']); $unite = $evolution['par_semaine'] ? 'semaine' : 'jour'; @endphp
<div class="evo-tete">
    <h3>{!! $icone ?? '' !!} {{ $titre }}</h3>
    @if($pts->isNotEmpty())
    <div class="evo-mode" role="tablist" aria-label="Présentation">
        <button type="button" data-sans-icone data-mode="cumul" class="on" title="Montants cumulés depuis le début de la période">Cumulé</button>
        <button type="button" data-sans-icone data-mode="periode" title="Montant de chaque {{ $unite }}">Par {{ $unite }}</button>
    </div>
    @endif
</div>
@if($pts->isEmpty() || ($pts->sum('recette') + $pts->sum('objectif') + $pts->sum('bureau') + $pts->sum('vehicule')) === 0)
<p style="color:var(--gris);font-size:13px">Aucune recette ni dépense sur cette période.</p>
@else
@php
    $totaux = ['recette' => $pts->sum('recette'), 'objectif' => $pts->sum('objectif'), 'depenses' => $pts->sum('vehicule') + $pts->sum('bureau')];
    $solde = $totaux['recette'] - $totaux['depenses'];
    $infos = $pts->map(fn($p) => [
        'd' => $evolution['par_semaine'] ? 'Semaine du ' . \Carbon\Carbon::parse($p['date'])->translatedFormat('j F Y') : ucfirst(\Carbon\Carbon::parse($p['date'])->translatedFormat('l j F Y')),
        'c' => \Carbon\Carbon::parse($p['date'])->format('d/m'),
        'r' => $p['recette'], 'o' => $p['objectif'], 'v' => $p['vehicule'], 'b' => $p['bureau'], 's' => $p['saisi'],
    ])->values();
@endphp
<div class="evo-legende" id="{{ $cle }}-legende">
    <button type="button" data-sans-icone data-serie="recette" class="on"><i class="trait" style="background:#2e74b5"></i>Recettes <b>{{ $F($totaux['recette']) }} F</b></button>
    <button type="button" data-sans-icone data-serie="objectif" class="on"><i class="trait pointille"></i>Objectif <b>{{ $F($totaux['objectif']) }} F</b>@if($totaux['objectif'])<span class="evo-taux {{ $totaux['recette'] >= $totaux['objectif'] ? 'ok' : 'ko' }}">{{ round($totaux['recette'] / $totaux['objectif'] * 100) }} % atteint</span>@endif</button>
    <button type="button" data-sans-icone data-serie="depenses" class="on"><i class="trait" style="background:#d97706"></i>Dépenses <b>{{ $F($totaux['depenses']) }} F</b></button>
    <span class="evo-solde">Solde <b class="{{ $solde >= 0 ? 'ok' : 'ko' }}">{{ $solde >= 0 ? '+' : '−' }}{{ $F(abs($solde)) }} F</b></span>
</div>

<div class="rp-scroll evo-zone">
    <svg id="{{ $cle }}-svg" viewBox="0 0 {{ $compact ? 620 : 960 }} {{ $compact ? 260 : 280 }}" width="100%" style="min-width:{{ $compact ? 420 : 600 }}px;font-family:inherit;display:block" role="img" aria-labelledby="{{ $cle }}-desc">
        <desc id="{{ $cle }}-desc">Courbes des recettes, de l'objectif et des dépenses sur la période ; le détail est dans le tableau sous le graphe.</desc>
    </svg>
    <div class="evo-bulle" id="{{ $cle }}-bulle" aria-hidden="true"></div>
</div>
<details class="evo-table">
    <summary>Voir les données</summary>
    <div class="rp-scroll"><table class="rp-tbl">
        <thead><tr><th>{{ ucfirst($unite) }}</th><th class="num">Recettes</th><th class="num">Objectif</th><th class="num">Atteinte</th><th class="num">Dépenses véhicules</th><th class="num">Dépenses bureau</th></tr></thead>
        <tbody>
        @foreach($infos as $p)
            <tr><td>{{ $p['d'] }}</td><td class="num">{{ $p['s'] ? $F($p['r']) . ' F' : '—' }}</td><td class="num">{{ $p['o'] ? $F($p['o']) . ' F' : '—' }}</td>
                <td class="num">{{ $p['o'] ? round($p['r'] / $p['o'] * 100) . ' %' : '—' }}</td><td class="num">{{ $p['v'] ? $F($p['v']) . ' F' : '—' }}</td><td class="num">{{ $p['b'] ? $F($p['b']) . ' F' : '—' }}</td></tr>
        @endforeach
        </tbody>
    </table></div>
</details>
<script>
(function(){
    var racine = document.getElementById(@js($cle . '-svg')).closest('.evo-bloc'), D = @json($infos), svg = document.getElementById(@js($cle . '-svg')), bulle = document.getElementById(@js($cle . '-bulle'));
    var NS = 'http://www.w3.org/2000/svg', W = {{ $compact ? 620 : 960 }}, H = {{ $compact ? 260 : 280 }}, L = 54, R = 118, T = 14, B = 30;
    var COUL = {recette: '#2e74b5', objectif: '#64748b', depenses: '#d97706'};
    var NOMS = {recette: 'Recettes', objectif: 'Objectif', depenses: 'Dépenses'};
    var FIN_CUMUL = {recette: 'Recettes cumulées', objectif: 'Objectif cumulé', depenses: 'Dépenses cumulées'};
    var mode = 'cumul', visibles = {recette: true, objectif: true, depenses: true};
    var fmt = function(v){ return Math.round(v).toLocaleString('fr-FR') + ' F'; };
    var court = function(v){ return v >= 1e6 ? (v / 1e6).toLocaleString('fr-FR', {maximumFractionDigits: 1}) + ' M' : v >= 1000 ? Math.round(v / 1000).toLocaleString('fr-FR') + ' k' : String(Math.round(v)); };
    var el = function(tag, attrs, parent){ var e = document.createElementNS(NS, tag); for (var k in attrs) e.setAttribute(k, attrs[k]); (parent || svg).appendChild(e); return e; };

    /* Séries selon le mode : par période, ou cumulées depuis le début de la période */
    function series(){
        var s = {recette: [], objectif: [], depenses: []}, c = {recette: 0, objectif: 0, depenses: 0};
        D.forEach(function(p){
            var v = {recette: p.r, objectif: p.o, depenses: p.v + p.b};
            // par jour : un jour sans recette saisie est un trou dans la courbe, pas un zéro
            for (var k in v) { c[k] += v[k]; s[k].push(mode === 'cumul' ? c[k] : (k !== 'depenses' && !p.s ? null : v[k])); }
        });
        return s;
    }
    /* Graduation ronde de l'axe des montants */
    function echelle(max){
        var brut = Math.max(max, 1) / 4, p10 = Math.pow(10, Math.floor(Math.log10(brut))), pas = [1, 2, 2.5, 5, 10].map(function(m){ return m * p10; }).find(function(x){ return x >= brut; });
        return {pas: pas, max: Math.ceil(Math.max(max, 1) / pas) * pas};
    }
    /* Tracé lissé (courbe monotone : ne dépasse jamais les valeurs réelles) */
    function chemin(P){
        if (P.length < 2) return P.length ? 'M' + P[0][0] + ',' + P[0][1] : '';
        // par jour / semaine : segments droits (chaque point est une valeur réelle, pas une tendance)
        if (mode !== 'cumul') { var t = '', ouvert = false; P.forEach(function(p){ if (p[1] === null) { ouvert = false; return; } t += (ouvert ? 'L' : 'M') + p[0].toFixed(1) + ',' + p[1].toFixed(1); ouvert = true; }); return t; }
        var n = P.length, d = [], m = [];
        for (var i = 0; i < n - 1; i++) d.push((P[i + 1][1] - P[i][1]) / (P[i + 1][0] - P[i][0]));
        m[0] = d[0]; m[n - 1] = d[n - 2];
        for (i = 1; i < n - 1; i++) m[i] = d[i - 1] * d[i] <= 0 ? 0 : (d[i - 1] + d[i]) / 2;
        for (i = 0; i < n - 1; i++) { if (d[i] === 0) { m[i] = 0; m[i + 1] = 0; continue; } var a = m[i] / d[i], b = m[i + 1] / d[i], h = a * a + b * b; if (h > 9) { var t = 3 / Math.sqrt(h); m[i] = t * a * d[i]; m[i + 1] = t * b * d[i]; } }
        var s = 'M' + P[0][0].toFixed(1) + ',' + P[0][1].toFixed(1);
        for (i = 0; i < n - 1; i++) { var dx = (P[i + 1][0] - P[i][0]) / 3; s += 'C' + (P[i][0] + dx).toFixed(1) + ',' + (P[i][1] + m[i] * dx).toFixed(1) + ' ' + (P[i + 1][0] - dx).toFixed(1) + ',' + (P[i + 1][1] - m[i + 1] * dx).toFixed(1) + ' ' + P[i + 1][0].toFixed(1) + ',' + P[i + 1][1].toFixed(1); }
        return s;
    }

    var X, Y, S;
    function dessiner(){
        while (svg.lastChild && svg.lastChild.tagName !== 'desc') svg.removeChild(svg.lastChild);
        S = series();
        var n = D.length, cw = W - L - R, base = H - B;
        var max = Math.max.apply(null, Object.keys(S).filter(function(k){ return visibles[k]; }).map(function(k){ return Math.max.apply(null, S[k].map(function(v){ return v || 0; })); }).concat([1]));
        var e = echelle(max);
        X = function(i){ return n === 1 ? L + cw / 2 : L + i * cw / (n - 1); };
        Y = function(v){ return base - v / e.max * (base - T); };
        // grille horizontale fine et montants
        for (var g = 0; g <= e.max + 1e-6; g += e.pas) {
            el('line', {x1: L, x2: W - R + 6, y1: Y(g), y2: Y(g), stroke: g === 0 ? '#cbd5e1' : '#eef2f7', 'stroke-width': 1});
            el('text', {x: L - 8, y: Y(g) + 3.5, fill: '#64748b', 'font-size': 10.5, 'text-anchor': 'end'}).textContent = court(g);
        }
        // dates (une sur k pour rester lisible)
        var k = Math.max(1, Math.ceil(n / 12));
        D.forEach(function(p, i){ if ((i % k === 0 && n - 1 - i >= Math.max(2, k * .6)) || i === n - 1) el('text', {x: X(i), y: H - B + 17, fill: '#64748b', 'font-size': 10, 'text-anchor': i === 0 ? 'start' : (i === n - 1 ? 'end' : 'middle')}).textContent = p.c; });

        var defs = el('defs', {});
        var grad = el('linearGradient', {id: @js($cle . '-voile'), x1: 0, x2: 0, y1: 0, y2: 1}, defs);
        el('stop', {offset: '0%', 'stop-color': COUL.recette, 'stop-opacity': .16}, grad);
        el('stop', {offset: '100%', 'stop-color': COUL.recette, 'stop-opacity': .02}, grad);

        var fins = [];
        ['objectif', 'depenses', 'recette'].forEach(function(s){
            if (!visibles[s] || !S[s].some(function(v){ return v > 0; })) return;
            if (mode !== 'cumul' && s === 'recette') {
                // voile léger sous les recettes de chaque jour saisi
                S[s].forEach(function(v, i){ if (v !== null && v > 0 && n > 1) el('rect', {x: X(i) - Math.min(9, (W - L - R) / (n - 1) * .3), y: Y(v), width: Math.min(18, (W - L - R) / (n - 1) * .6), height: Y(0) - Y(v), rx: 3, fill: COUL.recette, 'fill-opacity': .08}); });
            }
            var P = S[s].map(function(v, i){ return [X(i), v === null ? null : Y(v)]; }), d = chemin(P);
            if (s === 'recette' && mode === 'cumul') el('path', {d: d + 'L' + X(n - 1) + ',' + base + 'L' + X(0) + ',' + base + 'Z', fill: @js('url(#' . $cle . '-voile)')});
            el('path', {d: d, fill: 'none', stroke: COUL[s], 'stroke-width': s === 'objectif' ? 1.6 : 2.2, 'stroke-dasharray': s === 'objectif' ? '5 4' : null, 'stroke-linecap': 'round', 'stroke-linejoin': 'round'});
            if (mode !== 'cumul' && s !== 'objectif' && n <= 62) S[s].forEach(function(v, i){ if (v !== null && (v > 0 || s === 'recette')) el('circle', {cx: X(i), cy: Y(v), r: 3, fill: COUL[s], stroke: '#fff', 'stroke-width': 1.5}); });
            if (mode === 'cumul') {
                el('circle', {cx: X(n - 1), cy: Y(S[s][n - 1]), r: 4, fill: COUL[s], stroke: '#fff', 'stroke-width': 2});
                fins.push({s: s, y: Y(S[s][n - 1]), v: S[s][n - 1]});
            }
        });
        // valeurs en fin de courbe (sans chevauchement)
        fins.sort(function(a, b){ return a.y - b.y; });
        for (var i = 1; i < fins.length; i++) if (fins[i].y - fins[i - 1].y < 26) fins[i].y = fins[i - 1].y + 26;
        // une étiquette ne descend pas sous l'axe : la pile remonte
        var exces = fins.length ? fins[fins.length - 1].y - (base - 8) : 0;
        if (exces > 0) fins.forEach(function(f){ f.y -= exces; });
        fins.forEach(function(f){
            el('text', {x: W - R + 12, y: f.y - 1, fill: '#1e293b', 'font-size': 11.5, 'font-weight': 600}).textContent = fmt(f.v);
            el('text', {x: W - R + 12, y: f.y + 11, fill: '#64748b', 'font-size': 10}).textContent = (mode === 'cumul' ? FIN_CUMUL : NOMS)[f.s];
        });

        // repère de survol
        var curseur = el('g', {style: 'display:none', 'pointer-events': 'none'});
        el('line', {y1: T, y2: base, stroke: '#94a3b8', 'stroke-width': 1}, curseur);
        var points = {}; ['recette', 'objectif', 'depenses'].forEach(function(s){ points[s] = el('circle', {r: 4.5, fill: COUL[s], stroke: '#fff', 'stroke-width': 2}, curseur); });
        var zone = el('rect', {x: L - 10, y: T, width: W - L - R + 20, height: base - T, fill: 'transparent'});
        zone.addEventListener('mousemove', function(ev){
            var r = svg.getBoundingClientRect(), x = (ev.clientX - r.left) / r.width * W;
            var i = n === 1 ? 0 : Math.max(0, Math.min(n - 1, Math.round((x - L) / ((W - L - R) / (n - 1)))));
            curseur.style.display = ''; curseur.querySelector('line').setAttribute('x1', X(i)); curseur.querySelector('line').setAttribute('x2', X(i));
            ['recette', 'objectif', 'depenses'].forEach(function(s){ var c = points[s]; var vis = visibles[s] && S[s].some(function(v){ return v > 0; }); c.style.display = vis && S[s][i] !== null ? '' : 'none'; c.setAttribute('cx', X(i)); c.setAttribute('cy', Y(S[s][i] || 0)); });
            montrer(i, X(i) / W * r.width, r.width);
        });
        zone.addEventListener('mouseleave', function(){ curseur.style.display = 'none'; bulle.classList.remove('on'); });
    }

    function montrer(i, px, largeur){
        var p = D[i], cumul = mode === 'cumul';
        var ligne = function(c, lib, v, extra, pointille){ return '<div><i style="background:' + c + (pointille ? ';height:2px;border-radius:1px' : '') + '"></i><span></span><b>' + (v === null ? '—' : fmt(v)) + '</b>' + (extra || '') + '</div>'; };
        var html = '<strong></strong>', libs = [];
        var taux = function(r, o){ return o > 0 ? '<em class="' + (r >= o ? 'ok' : 'ko') + '">' + Math.round(r / o * 100) + ' %</em>' : ''; };
        if (visibles.recette) { html += ligne(COUL.recette, '', cumul ? S.recette[i] : (p.s ? p.r : null), taux(cumul ? S.recette[i] : p.r, cumul ? S.objectif[i] : p.o)); libs.push(cumul ? 'Recettes cumulées' : (p.s ? 'Recettes' : 'Recettes non saisies')); }
        if (visibles.objectif) { html += ligne(COUL.objectif, '', cumul ? S.objectif[i] : (p.s ? p.o : null), '', true); libs.push(cumul ? 'Objectif cumulé' : 'Objectif'); }
        if (visibles.depenses) { html += ligne(COUL.depenses, '', cumul ? S.depenses[i] : p.v + p.b); libs.push(cumul ? 'Dépenses cumulées' : 'Dépenses'); }
        if (!cumul && (p.v || p.b)) { html += '<div class="det">véhicules ' + fmt(p.v) + ' · bureau ' + fmt(p.b) + '</div>'; }
        if (cumul) { var sol = S.recette[i] - S.depenses[i]; html += '<div class="sol">Solde à date<b class="' + (sol >= 0 ? 'ok' : 'ko') + '">' + (sol >= 0 ? '+' : '−') + fmt(Math.abs(sol)) + '</b></div>'; }
        bulle.innerHTML = html;
        bulle.querySelector('strong').textContent = p.d;
        bulle.querySelectorAll('div > span').forEach(function(s, k){ s.textContent = libs[k]; });
        bulle.style.left = Math.min(Math.max(px + 14, 0), largeur - 274) + 'px';
        if (px + 14 + 268 > largeur) bulle.style.left = Math.max(0, px - 282) + 'px';
        bulle.classList.add('on');
    }

    document.querySelectorAll(@js('#' . $cle . '-legende button')).forEach(function(b){
        b.addEventListener('click', function(){ b.classList.toggle('on'); visibles[b.dataset.serie] = b.classList.contains('on'); dessiner(); });
    });
    racine.querySelectorAll('.evo-mode button').forEach(function(b){
        b.addEventListener('click', function(){ mode = b.dataset.mode; racine.querySelectorAll('.evo-mode button').forEach(function(x){ x.classList.toggle('on', x === b); }); dessiner(); });
    });
    dessiner();
})();
</script>
@endif
</div>
