{{--
    Formulaires interactifs, pour tout le système (aucune vue à modifier) :
    - champs obligatoires signalés d'un astérisque ;
    - contrôle en direct : le champ fautif est souligné et le message s'affiche dessous, dès qu'on le quitte ;
    - erreurs renvoyées par le serveur placées sous les champs concernés ;
    - compteur de caractères sur les zones de texte limitées ;
    - montants lus en clair pendant la saisie (« 20 000 F ») ;
    - bouton d'envoi verrouillé pendant l'enregistrement (pas de double envoi).
    Exclure un formulaire : data-libre ; un champ : data-sans-controle.
--}}
<style>
.req-auto{color:var(--ko);margin-left:2px;font-weight:600}
.f-cache{position:static!important;display:block!important;width:1px!important;height:1px!important;min-height:0!important;padding:0!important;margin:0!important;border:0!important;opacity:0!important;overflow:hidden!important;pointer-events:none}
.f-pastilles{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.f-pastilles-large{grid-column:1/-1}
.f-pastilles.desactive{opacity:.55;pointer-events:none}
.f-pastille{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--bord);border-radius:20px;font-size:12.5px;color:#334155;background:#fff;cursor:pointer;transition:border-color .15s,background .15s,color .15s;font-family:inherit;line-height:1.3;width:auto!important;margin:0!important}
.f-pastille i{width:7px;height:7px;border-radius:50%;flex:none}
.f-pastille svg{color:#94a3b8;flex:none}
.f-pastille:hover{border-color:#9fbfe0}
.f-pastille.actif{border-color:var(--accent);background:#eaf2fb;color:var(--accent);font-weight:600}
.f-pastille.actif svg{color:var(--accent)}
.f-pastille:focus-visible{outline:none;box-shadow:0 0 0 3px rgba(46,116,181,.2)}
.f-pastille.ajout{border-style:dashed;border-color:#b6c6d8;color:var(--accent);background:none}
.f-pastille.ajout:hover{border-color:var(--accent);background:#f3f8fd}
.f-pastilles:has(+ select.f-ko) .f-pastille:not(.ajout){border-color:#fca5a5}
.dlg-modalite{width:420px;max-width:94vw;border:none;border-radius:14px;padding:22px}
.dlg-modalite::backdrop{background:rgba(22,56,92,.35)}
.dlg-modalite h3{color:var(--bleu);font-size:16px;margin:0 0 4px}
.dlg-modalite .md-aide{font-size:12.5px;color:var(--gris);margin:0 0 12px;line-height:1.45}
.dlg-modalite label{display:block;font-size:12px;color:var(--gris);font-weight:600;margin:10px 0 4px;text-transform:none}
.dlg-modalite input{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
.dlg-modalite input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.dlg-modalite .md-err{color:var(--ko);font-size:12px;margin:8px 0 0}
.dlg-modalite .md-pied{display:flex;gap:8px;justify-content:flex-end;margin-top:16px}
.f-ko{border-color:var(--ko)!important;box-shadow:0 0 0 3px #fee2e2!important}
.f-msg{display:block;font-size:11px;color:var(--ko);margin-top:3px;line-height:1.3;text-transform:none;letter-spacing:0;font-weight:400}
.f-aide{display:block;font-size:11px;color:var(--gris);margin-top:3px;line-height:1.3;text-align:right;min-height:0}
.f-aide.limite{color:var(--ko)}
button.f-envoi,input.f-envoi{opacity:.75;cursor:progress!important;pointer-events:none}
button.f-envoi::before{content:"";display:inline-block;width:11px;height:11px;margin-right:6px;vertical-align:-1px;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;animation:f-tourne .7s linear infinite}
button.f-envoi svg{display:none}
@keyframes f-tourne{to{transform:rotate(360deg)}}
</style>
<script>
(function(){
    var CHAMPS = 'input:not([type=hidden]):not([type=submit]):not([type=button]):not([type=reset]):not([type=file]),select,textarea';
    var MONTANT = /(montant|recette|cout|co[uû]t|prix|objectif|solde|deficit|salaire|pointage|valeur|params\[)/i;
    var lib = function(el){ return el.closest('form') && el.closest('form').hasAttribute('data-libre') || el.hasAttribute('data-sans-controle'); };

    /* Libellé d'un champ : <label for>, label englobant, ou label juste avant */
    function libelle(el){
        if (el.id) { var l = document.querySelector('label[for="' + CSS.escape(el.id) + '"]'); if (l) return l; }
        var e = el.closest('label'); if (e) return e;
        var p = el.previousElementSibling;
        for (var i = 0; p && i < 2; i++, p = p.previousElementSibling) if (p.tagName === 'LABEL') return p;
        var par = el.parentElement, q = par && par.previousElementSibling;
        if (par && par.children.length === 1 && q && q.tagName === 'LABEL') return q;
        return null;
    }
    function marquerObligatoires(racine){
        racine.querySelectorAll(CHAMPS).forEach(function(el){
            if (!el.required || el.type === 'radio' || el.type === 'checkbox' || lib(el) || el.dataset.reqVu) return;
            var f = el.closest('form'); if (!f || (f.getAttribute('method') || 'get').toLowerCase() !== 'post') return; // filtres de recherche : pas d'astérisque
            el.dataset.reqVu = '1';
            var l = libelle(el);
            if (l && l.textContent.indexOf('*') < 0 && !l.querySelector('.req,.req-auto')) l.insertAdjacentHTML('beforeend', '<span class="req-auto" aria-hidden="true">*</span>');
        });
    }

    /* Message sous le champ (créé à la demande) */
    function zone(el, cls){
        var n = el.nextElementSibling;
        if (n && n.classList.contains(cls)) return n;
        var z = document.createElement('span'); z.className = cls; el.insertAdjacentElement('afterend', z); return z;
    }
    function afficherErreur(el, msg){
        el.classList.add('f-ko'); el.setAttribute('aria-invalid', 'true');
        var z = el.nextElementSibling && el.nextElementSibling.classList.contains('f-aide') ? el.nextElementSibling : el;
        var m = z.nextElementSibling && z.nextElementSibling.classList.contains('f-msg') ? z.nextElementSibling : null;
        if (!m) { m = document.createElement('span'); m.className = 'f-msg'; z.insertAdjacentElement('afterend', m); }
        m.textContent = msg;
    }
    function effacerErreur(el){
        el.classList.remove('f-ko'); el.removeAttribute('aria-invalid');
        var z = el.nextElementSibling && el.nextElementSibling.classList.contains('f-aide') ? el.nextElementSibling : el;
        var m = z.nextElementSibling; if (m && m.classList.contains('f-msg')) m.remove();
    }
    function message(el){
        var v = el.validity;
        if (v.valueMissing) return el.tagName === 'SELECT' ? 'Choisissez une valeur.' : 'Ce champ est obligatoire.';
        if (v.tooShort) return 'Au moins ' + el.minLength + ' caractères (' + el.value.length + ' saisis).';
        if (v.rangeUnderflow) return 'Valeur minimale : ' + Number(el.min).toLocaleString('fr-FR') + '.';
        if (v.rangeOverflow) return 'Valeur maximale : ' + Number(el.max).toLocaleString('fr-FR') + '.';
        if (v.typeMismatch && el.type === 'email') return 'Adresse e-mail invalide.';
        if (v.badInput) return 'Valeur invalide.';
        return el.validationMessage;
    }
    function verifier(el){
        if (lib(el) || !el.willValidate) return true;
        if (el.checkValidity()) { effacerErreur(el); return true; }
        afficherErreur(el, message(el)); return false;
    }

    /* Contrôle en direct : à la sortie du champ, puis à chaque frappe tant qu'il est en erreur */
    document.addEventListener('focusout', function(e){ var el = e.target; if (el.matches && el.matches(CHAMPS) && (el.value !== '' || el.dataset.touche)) { el.dataset.touche = '1'; verifier(el); } });
    document.addEventListener('input', function(e){ var el = e.target; if (!el.matches || !el.matches(CHAMPS)) return; el.dataset.touche = '1'; if (el.classList.contains('f-ko')) verifier(el); aide(el); });
    document.addEventListener('change', function(e){ var el = e.target; if (el.matches && el.matches(CHAMPS) && el.classList.contains('f-ko')) verifier(el); });
    /* À l'envoi, le navigateur signale les champs invalides : on les souligne tous */
    /* (la bulle du navigateur, redondante, est remplacée par le message sous le champ ; le curseur va au premier champ fautif) */
    var premier = 0;
    document.addEventListener('invalid', function(e){
        var el = e.target; if (!el.matches || !el.matches(CHAMPS) || lib(el)) return;
        e.preventDefault(); afficherErreur(el, message(el));
        if (Date.now() - premier > 300) { premier = Date.now(); el.focus({preventScroll: true}); el.scrollIntoView({block: 'center', behavior: 'smooth'}); }
    }, true);

    /* Aides de saisie : compteur de caractères, montant en clair */
    function aide(el){
        if (lib(el)) return;
        if (el.tagName === 'TEXTAREA' && el.maxLength >= 50) {
            var z = zone(el, 'f-aide'), n = el.value.length;
            z.textContent = n ? n + ' / ' + el.maxLength : '';
            z.classList.toggle('limite', n >= el.maxLength * 0.9);
        } else if (el.type === 'number' && MONTANT.test(el.name || '') && !(el.step && el.step.indexOf('.') >= 0)) {
            var v = parseFloat(el.value), za = zone(el, 'f-aide');
            za.textContent = !isNaN(v) && Math.abs(v) >= 1000 ? v.toLocaleString('fr-FR') + ' F' : '';
        }
    }

    /* Erreurs du serveur : sous les champs concernés */
    function erreursServeur(){
        var err = window.ERREURS_CHAMPS || {};
        Object.keys(err).forEach(function(cle){
            var p = cle.split('.'), nom = p[0] + p.slice(1).map(function(x){ return '[' + (/^\d+$/.test(x) ? '' : x) + ']'; }).join('');
            var el = document.querySelector('[name="' + CSS.escape(nom) + '"]') || document.querySelector('[name="' + CSS.escape(p[0]) + '[]"]');
            if (el && el.matches(CHAMPS) && !el.closest('dialog:not([open])')) afficherErreur(el, err[cle][0]);
        });
    }

    /* Envoi : verrouiller le bouton (après la constitution des données, pour garder name/value du bouton cliqué) */
    document.addEventListener('submit', function(e){
        var f = e.target;
        if (e.defaultPrevented || f.hasAttribute('data-libre') || (f.method || '').toLowerCase() !== 'post') return;
        var b = e.submitter || f.querySelector('button:not([type=button]),input[type=submit]');
        if (!b) return;
        setTimeout(function(){
            b.classList.add('f-envoi'); b.setAttribute('aria-busy', 'true');
            setTimeout(function(){ b.classList.remove('f-envoi'); b.removeAttribute('aria-busy'); }, f.target ? 1500 : 15000);
        }, 0);
    });
    window.addEventListener('pageshow', function(){ document.querySelectorAll('.f-envoi').forEach(function(b){ b.classList.remove('f-envoi'); b.removeAttribute('aria-busy'); }); });

    /*
     * Saisie manuelle d'une valeur absente d'une liste.
     * - <select data-modalite="categorie" data-modalite-url="…" data-modalite-nom="un type de …"> reçoit une dernière option
     *   « + Ajouter … » ; la valeur saisie est créée dans le paramétrage (data-modalite-url) puis sélectionnée.
     *   Sans data-modalite-url, la saisie reste libre (la valeur est ajoutée à la liste de la page seulement).
     *   data-modalite-intervalle : demande aussi l'intervalle en jours (types d'entretien).
     * - window.ajouterModalite(cfg, siOk, siAnnule) : même fenêtre, pour d'autres présentations (choix en pastilles…).
     */
    function preparerModalites(racine){
        racine.querySelectorAll('select[data-modalite]').forEach(function(sel){
            if (sel.querySelector('option[data-ajout]')) return;
            var o = document.createElement('option'); o.value = '__ajout__'; o.dataset.ajout = '1';
            o.textContent = '+ ' + (sel.dataset.modaliteUrl ? 'Ajouter ' : 'Saisir ') + (sel.dataset.modaliteNom || 'une valeur') + '…';
            sel.appendChild(o);
            sel.dataset.precedent = sel.value;
            sel.addEventListener('focus', function(){ if (sel.value !== '__ajout__') sel.dataset.precedent = sel.value; });
            sel.addEventListener('change', function(e){
                if (sel.value !== '__ajout__') { sel.dataset.precedent = sel.value; return; }
                e.stopImmediatePropagation();
                window.ajouterModalite({categorie: sel.dataset.modalite, url: sel.dataset.modaliteUrl, nom: sel.dataset.modaliteNom, intervalle: sel.hasAttribute('data-modalite-intervalle')},
                    function(t){
                        // ajoutée à toutes les listes de la même catégorie de la page
                        document.querySelectorAll('select[data-modalite="' + sel.dataset.modalite + '"]').forEach(function(autre){
                            if ([].some.call(autre.options, function(x){ return x.value === t.code; })) return;
                            var n = new Option(t.libelle + (t.intervalle_jours && autre.hasAttribute('data-modalite-intervalle') ? ' (' + t.intervalle_jours + 'j)' : ''), t.code);
                            n.dataset.ajoutee = '1';
                            autre.insertBefore(n, autre.querySelector('option[data-ajout]'));
                        });
                        sel.value = t.code; sel.dataset.precedent = t.code;
                        sel.dispatchEvent(new Event('change', {bubbles: true}));
                        sel.focus();
                    },
                    function(){ sel.value = sel.dataset.precedent || ''; sel.focus(); });
            }, true);
        });
    }
    var dlgModalite = null;
    window.ajouterModalite = function(cfg, siOk, siAnnule){
        if (!dlgModalite) {
            dlgModalite = document.createElement('dialog');
            dlgModalite.className = 'dlg-modalite';
            dlgModalite.innerHTML = '<h3>Ajouter à la liste</h3><p class="md-aide"></p>'
                + '<form method="dialog" data-libre>'
                + '<label>Libellé <span style="color:var(--ko)">*</span></label>'
                + '<input name="libelle" required minlength="2" maxlength="80" autocomplete="off">'
                + '<div class="md-int" hidden><label>Intervalle entre deux entretiens (jours)</label><input name="intervalle_jours" type="number" min="1" max="3650" placeholder="ex. 90" style="width:160px"></div>'
                + '<p class="md-err" hidden></p>'
                + '<div class="md-pied"><button type="button" class="btn sec" value="annuler">Annuler</button><button type="submit" class="btn">Ajouter</button></div></form>';
            document.body.appendChild(dlgModalite);
            dlgModalite.querySelector('button[value=annuler]').addEventListener('click', function(){ dlgModalite.close(); });
            dlgModalite.querySelector('form').addEventListener('submit', function(e){
                e.preventDefault();
                var c = dlgModalite._cfg, f = e.target, err = dlgModalite.querySelector('.md-err'), b = f.querySelector('button[type=submit]');
                var lib = f.libelle.value.trim().replace(/\s+/g, ' ');
                var fini = function(t){ dlgModalite._ok = true; document.dispatchEvent(new CustomEvent('modalite-ajoutee', {detail: Object.assign({categorie: c.categorie}, t)})); dlgModalite.close(); if (c.siOk) c.siOk(t); };
                if (!c.url) { fini({code: lib, libelle: lib}); return; }
                b.disabled = true; err.hidden = true;
                fetch(c.url, {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
                    body: JSON.stringify({categorie: c.categorie, libelle: lib, intervalle_jours: f.intervalle_jours.value || null})})
                .then(function(r){ return r.json().then(function(j){ return {ok: r.ok, j: j}; }); })
                .then(function(res){
                    b.disabled = false;
                    if (!res.ok) { err.textContent = res.j.message || 'Ajout impossible.'; err.hidden = false; return; }
                    fini(res.j);
                }).catch(function(){ b.disabled = false; err.textContent = 'Ajout impossible (connexion).'; err.hidden = false; });
            });
            dlgModalite.addEventListener('close', function(){ var c = dlgModalite._cfg; if (!dlgModalite._ok && c && c.siAnnule) c.siAnnule(); });
        }
        dlgModalite._cfg = Object.assign({}, cfg, {siOk: siOk, siAnnule: siAnnule}); dlgModalite._ok = false;
        var f = dlgModalite.querySelector('form'); f.reset();
        dlgModalite.querySelector('h3').textContent = (cfg.url ? 'Ajouter ' : 'Saisir ') + (cfg.nom || 'une valeur');
        dlgModalite.querySelector('.md-aide').textContent = cfg.url
            ? 'Valeur absente de la liste : elle sera sélectionnée ici et enregistrée dans le paramétrage, pour être proposée la prochaine fois.'
            : 'Valeur absente de la liste : saisissez-la librement.';
        dlgModalite.querySelector('.md-int').hidden = !cfg.intervalle;
        dlgModalite.querySelector('.md-err').hidden = true;
        dlgModalite.showModal();
        f.libelle.focus();
    };

    /*
     * Choix en pastilles : <select data-pastilles> est présenté en pastilles cliquables (la liste reste la source de vérité :
     * valeur, validation, envoi, et le code qui la renseigne continue de fonctionner).
     * Options : data-couleur (point de couleur), data-icone (tracés SVG) ; data-pastille-ajout = texte de la pastille « + Autre… ».
     * Une liste facultative avec une option vide se désélectionne en recliquant la pastille active.
     */
    var VALEUR = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    var INDEX = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
    function preparerPastilles(racine){
        racine.querySelectorAll('select[data-pastilles]').forEach(function(sel){
            if (sel._pastilles) return;
            var z = document.createElement('div'); z.className = 'f-pastilles'; z.setAttribute('role', 'radiogroup');
            sel.parentNode.insertBefore(z, sel);
            sel.classList.add('f-cache'); sel.tabIndex = -1; sel._pastilles = z;
            var synchro = function(){
                z.querySelectorAll('.f-pastille').forEach(function(b){ var on = b.dataset.valeur === VALEUR.get.call(sel); b.classList.toggle('actif', on); b.setAttribute('aria-checked', on); });
                z.classList.toggle('desactive', sel.disabled);
            };
            var rendre = function(){
                z.innerHTML = '';
                [].forEach.call(sel.options, function(o){
                    if (o.value === '' || o.hidden) return;
                    var b = document.createElement('button'); b.type = 'button'; b.dataset.sansIcone = ''; b.dataset.valeur = o.value;
                    b.className = 'f-pastille' + (o.dataset.ajout ? ' ajout' : ''); b.setAttribute('role', o.dataset.ajout ? 'button' : 'radio');
                    if (o.dataset.couleur) { var i = document.createElement('i'); i.style.background = o.dataset.couleur; b.append(i); }
                    if (o.dataset.icone) b.insertAdjacentHTML('beforeend', '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + o.dataset.icone + '</svg>');
                    b.append(o.dataset.ajout ? (sel.dataset.pastilleAjout || '+ Autre…') : o.textContent.trim());
                    b.addEventListener('click', function(){
                        if (sel.disabled) return;
                        var actuel = VALEUR.get.call(sel);
                        var v = (actuel === o.value && !sel.required && sel.querySelector('option[value=""]')) ? '' : o.value;
                        sel.value = v; sel.dispatchEvent(new Event('change', {bubbles: true}));
                    });
                    z.append(b);
                });
                // plus de 3 choix : le champ prend toute la largeur de sa grille (sinon les pastilles s'empilent)
                if (sel.parentNode !== sel.form) sel.parentNode.classList.toggle('f-pastilles-large', z.children.length > 3);
                synchro();
            };
            // une valeur posée par le code (modification d'un enregistrement, remise à zéro) met les pastilles à jour
            Object.defineProperty(sel, 'value', {configurable: true, get: function(){ return VALEUR.get.call(sel); }, set: function(v){ VALEUR.set.call(sel, v); synchro(); }});
            Object.defineProperty(sel, 'selectedIndex', {configurable: true, get: function(){ return INDEX.get.call(sel); }, set: function(v){ INDEX.set.call(sel, v); synchro(); }});
            sel.addEventListener('change', synchro);
            new MutationObserver(rendre).observe(sel, {childList: true, subtree: true, attributes: true, attributeFilter: ['disabled']});
            if (sel.form) sel.form.addEventListener('reset', function(){ setTimeout(synchro, 0); });
            rendre();
        });
    }

    function init(racine){ marquerObligatoires(racine); preparerModalites(racine); preparerPastilles(racine); racine.querySelectorAll('textarea[maxlength]').forEach(aide); }
    document.addEventListener('DOMContentLoaded', function(){
        init(document); erreursServeur();
        /* Champs ajoutés plus tard (fenêtres remplies en JavaScript) */
        var t = null;
        new MutationObserver(function(){ clearTimeout(t); t = setTimeout(function(){ marquerObligatoires(document); }, 120); })
            .observe(document.body, {childList: true, subtree: true});
        /* À la fermeture d'une fenêtre, ses messages d'erreur disparaissent */
        document.querySelectorAll('dialog').forEach(function(d){ d.addEventListener('close', function(){
            d.querySelectorAll('.f-ko').forEach(effacerErreur);
            d.querySelectorAll('[data-touche]').forEach(function(x){ delete x.dataset.touche; });
            d.querySelectorAll('.f-aide').forEach(function(x){ x.textContent = ''; });
        }); });
    });
})();
</script>
