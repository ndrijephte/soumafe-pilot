{{--
    Mode nuit (option de l'utilisateur, mémorisée dans le navigateur).
    Les vues ont beaucoup de couleurs claires écrites en dur : plutôt que de les reprendre une à une, la page est inversée
    (clair ↔ sombre, teintes conservées) et ce qui doit garder ses vraies couleurs est ré-inversé :
    barre latérale, photos, aperçus, graphiques, avatars.
--}}
<script>
(function(){ try { if (localStorage.getItem('mode_nuit') === '1') document.documentElement.classList.add('nuit'); } catch(e){} })();
function basculerNuit(){
    var on = document.documentElement.classList.toggle('nuit');
    try { localStorage.setItem('mode_nuit', on ? '1' : '0'); } catch(e){}
    document.querySelectorAll('.bt-nuit').forEach(function(b){ b.title = on ? 'Passer en mode jour' : 'Passer en mode nuit'; b.setAttribute('aria-pressed', on); });
}
</script>
<style>
/* Bouton jour/nuit et cloche : même boîte de 30 px, centrées sur la même ligne */
.bt-nuit,.notif-wrap{width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;flex:none;border-radius:8px}
.bt-nuit{background:none;border:none;cursor:pointer;color:var(--bleu);padding:0}
.bt-nuit:hover,.notif-wrap:hover{background:#eef4fb}
.bt-nuit svg,.notif-wrap>svg{display:block;width:20px;height:20px}
.notif-wrap #notif-badge{top:0!important;right:0!important}
.bt-nuit .soleil{display:none}
html.nuit .bt-nuit .soleil{display:block}
html.nuit .bt-nuit .lune{display:none}
@media screen{
    html.nuit{filter:invert(.9) hue-rotate(180deg);background:#fff;color-scheme:light}
    /* Ce qui garde ses vraies couleurs */
    html.nuit img,html.nuit video,html.nuit canvas,html.nuit iframe,html.nuit embed,html.nuit object,
    html.nuit .avatar,html.nuit .garder-couleurs,
    html.nuit [style*="background-image"]{filter:invert(1) hue-rotate(180deg) contrast(1.25)}
    html.nuit .avatar img,html.nuit .garder-couleurs img,html.nuit .garder-couleurs canvas{filter:none}
    /*
     * Barre latérale : inversée avec le reste de la page, pour avoir exactement la même teinte que le fond.
     * Ses couleurs sont donc écrites « en clair » (comme une barre claire) : l'inversion les rend sombres.
     */
    html.nuit aside{background:var(--fond);color:#1e293b;border-right:1px solid var(--bord)}
    html.nuit aside a,html.nuit aside .lien-off{color:#475569}
    html.nuit aside a:hover{background:#e8eef5}
    html.nuit aside a.actif{background:#dce8f5;color:var(--accent);border-left-color:#1e293b}
    html.nuit .marque,html.nuit .bas-barre{border-color:var(--bord)}
    html.nuit .cercle-logo{background:#e2e8f0;border-color:#94a3b8}
    html.nuit body.reduit aside a:hover::after{background:#334155}
    /* Ombres : une ombre noire inversée deviendrait un halo clair */
    html.nuit *{box-shadow:none!important;text-shadow:none!important}
    /* Les fenêtres modales sont dans la couche supérieure du navigateur : le filtre de la page ne les atteint pas */
    html.nuit dialog:modal{filter:invert(.9) hue-rotate(180deg);outline:1px solid #cbd5e1}
    html.nuit dialog::backdrop{background:rgba(0,0,0,.6)}
    html.nuit .overlay{background:rgba(255,255,255,.4)}
    /* Barres de défilement discrètes */
    html.nuit ::-webkit-scrollbar{width:10px;height:10px}
    html.nuit ::-webkit-scrollbar-thumb{background:#c4ccd6;border-radius:6px}
    html.nuit ::-webkit-scrollbar-track{background:#f1f5f9}
}
@media print{html.nuit{filter:none}}
</style>
