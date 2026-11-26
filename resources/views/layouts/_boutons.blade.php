{{--
    Boutons du système : rendu harmonisé et icône appropriée.
    L'icône est choisie d'après le libellé (Enregistrer, Annuler, Supprimer, Exporter…) et ajoutée seulement si le bouton
    n'en a pas déjà une ; les boutons créés après coup (fenêtres, listes rechargées) sont traités aussi.
    Un bouton peut s'y soustraire avec data-sans-icone.
--}}
<style>
/* Après les styles des pages : seul l'alignement, la graisse et les états changent (couleurs et tailles de chaque page conservées) */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;font-family:inherit;line-height:1.2;vertical-align:middle;transition:background-color .15s,border-color .15s,box-shadow .15s,filter .15s}
.btn:hover:not(:disabled){filter:brightness(.96)}
.btn.sec:hover:not(:disabled){filter:none;border-color:var(--accent,#2e74b5);background:#f5f9fd}
.btn:focus-visible{outline:2px solid #2e74b5;outline-offset:2px}
.btn:disabled{opacity:.55;cursor:not-allowed}
.btn svg{flex:none;width:15px;height:15px}
.btn.sm svg,.btn-sm svg{width:13px;height:13px}
.btn .btn-ic{flex:none}
</style>
<script>
(function () {
    var P = {
        plus: '<path d="M12 5v14M5 12h14"/>',
        wallet: '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
        save: '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
        check: '<polyline points="20 6 9 17 4 12"/>',
        x: '<path d="M18 6 6 18M6 6l12 12"/>',
        trash: '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        upload: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>',
        printer: '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        filter: '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        reset: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>',
        search: '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        send: '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>',
        back: '<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>',
        next: '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        eye: '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        clock: '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        login: '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>',
        userplus: '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/>',
        lock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        unlock: '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/>',
        copy: '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
        chart: '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        flag: '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
        mail: '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        key: '<path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>',
        list: '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/>',
        settings: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'
    };
    // Règles : du plus précis au plus général (libellé sans accents, en minuscules)
    var R = [
        [/^(enregistrer|sauvegarder)/, 'save'],
        [/^(se connecter|connexion)/, 'login'],
        [/^(mot de passe|reset mdp|reinitialiser le mot)/, 'key'],
        [/^(annuler|fermer|non merci)/, 'x'],
        [/^(supprimer|suppr|retirer|archiver)/, 'trash'],
        [/^(rejeter|refuser|invalider)/, 'x'],
        [/^(modifier|editer|corriger|saisir|renommer|statuer)/, 'edit'],
        [/(exporter|excel|telecharger|\bword\b|\.docx|\.xlsx$)/, 'download'],
        [/^(importer|charger|deposer|joindre)/, 'upload'],
        [/(imprimer|\bpdf\b)/, 'printer'],
        [/^(reinitialiser|effacer|actualiser|recalculer|remettre en brouillon|renvoyer en brouillon|restaurer)/, 'reset'],
        [/^(filtrer|appliquer|afficher)$/, 'filter'],
        [/^rechercher/, 'search'],
        [/^(envoyer|transmettre|publier|notifier|renvoyer|soumettre|partager)/, 'send'],
        [/^(rappeler|relancer)/, 'mail'],
        [/^retour/, 'back'],
        [/(suivant|continuer)$/, 'next'],
        [/^(valider|confirmer|approuver|accepter|realiser|terminer|marquer|tout marquer|reintegrer|enregistrer la|evaluer)/, 'check'],
        [/^(rouvrir|ouvrir la rectification|deverrouiller)/, 'unlock'],
        [/^(cloturer|cloture|fermer la|verrouiller)/, 'lock'],
        [/^(affecter|inviter)/, 'userplus'],
        [/^(dupliquer|copier)/, 'copy'],
        [/^(lier|rattacher)/, 'link'],
        [/^(planifier|programmer)/, 'calendar'],
        [/^(pointer)/, 'clock'],
        [/^(signaler|declarer)/, 'flag'],
        [/(reporting|rapport|statistiques|indicateurs)/, 'chart'],
        [/^(param)/, 'settings'],
        [/^(historique)/, 'clock'],
        [/^(voir|consulter|ouvrir|apercu|detail|resume|fiche)/, 'eye'],
        [/^(tout selectionner|tout deselectionner|selectionner)/, 'list'],
        [/^(ajouter|nouveau|nouvelle|creer|\+)/, 'plus']
    ];
    var norm = function (t) { return t.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').trim(); };
    var svg = function (nom) {
        return '<svg class="btn-ic" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + P[nom] + '</svg>';
    };
    function decorer(b) {
        if (b.dataset.icone || b.dataset.sansIcone !== undefined || b.querySelector('svg,img')) return;
        b.dataset.icone = '1';
        // Libellé sans symbole de tête (« + Ajouter », « ✓ Valider »)
        var texte = (b.textContent || '').trim();
        if (!texte || texte.length < 2 || /^[×✕✓✔✗xX↺⟳→←]$/.test(texte)) return;
        var lib = norm(texte.replace(/^[+✓✔✕×↺⟳]\s*/, ''));
        var plus = /^\+/.test(texte);
        // Icône imposée par la page (data-ic="plus"), sinon déduite du libellé
        var nom = b.dataset.ic && P[b.dataset.ic] ? b.dataset.ic : null;
        for (var i = 0; i < R.length && !nom; i++) if (R[i][0].test(plus ? '+' + lib : lib)) nom = R[i][1];
        if (!nom && plus) nom = 'plus';
        if (!nom) return;
        // Retire le symbole de tête devenu inutile
        var premier = b.firstChild;
        if (premier && premier.nodeType === 3) premier.nodeValue = premier.nodeValue.replace(/^\s*[+✓✔✕×↺⟳]\s*/, '');
        b.insertAdjacentHTML('afterbegin', svg(nom));
    }
    function parcourir(racine) {
        (racine.querySelectorAll ? racine : document).querySelectorAll('.btn, .btn-add, .btn-export').forEach(decorer);
        if (racine.matches && racine.matches('.btn, .btn-add, .btn-export')) decorer(racine);
    }
    document.addEventListener('DOMContentLoaded', function () {
        parcourir(document);
        new MutationObserver(function (ms) {
            ms.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) parcourir(n); }); });
        }).observe(document.body, {childList: true, subtree: true});
    });
})();
</script>
