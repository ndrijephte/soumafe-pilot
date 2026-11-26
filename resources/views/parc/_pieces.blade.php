{{--
    Pièces jointes d'un formulaire (panne, maintenance…) : plusieurs photos ou fichiers, ajoutés en une ou plusieurs fois,
    chacun avec sa vignette, sa catégorie et sa légende. Paramètres : $type (alias documentable), $defaut (catégorie proposée).
--}}
@php $cats = collect(\App\Models\Document::TYPES[$type][2] ?? [])->mapWithKeys(fn($c) => [$c => \App\Models\Document::CATEGORIES[$c][0]]); @endphp
<div class="pj" data-defaut="{{ $defaut ?? 'photo' }}" data-cats='@json($cats)' @if(!empty($photoRequise)) data-photo-requise="1" @endif>
    <div class="pj-tete">
        <span>Pièces jointes @if(!empty($photoRequise))<b class="pj-req">au moins une photo</b>@endif <em>photos, devis, facture…</em></span>
        <label class="pj-btn">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
            Photo
            <input type="file" accept="image/*" capture="environment" multiple hidden onchange="pjAjouter(this)">
        </label>
        <label class="pj-btn">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            Fichiers
            <input type="file" accept="{{ collect(\App\Models\Document::EXTENSIONS)->map(fn($e) => '.' . $e)->join(',') }}" multiple hidden onchange="pjAjouter(this)">
        </label>
    </div>
    <input type="file" name="pieces[]" multiple hidden class="pj-envoi">
    <div class="pj-liste"></div>
</div>
@once
@push('scripts')
<script>
/* Pièces jointes : les fichiers choisis en plusieurs fois s'additionnent ; chaque fichier a sa catégorie et sa légende */
function pjZone(el){ return el.closest('.pj'); }
function pjAjouter(input){
    const z = pjZone(input);
    z._f = z._f || [];
    [...input.files].forEach(f => z._f.push({f: f, cat: f.type.startsWith('image/') && JSON.parse(z.dataset.cats).photo ? 'photo' : z.dataset.defaut, leg: ''}));
    input.value = '';
    z.classList.remove('pj-manque');
    pjRendre(z);
}
function pjRetirer(btn, i){ const z = pjZone(btn); z._f.splice(i, 1); pjRendre(z); }
function pjRendre(z){
    const dt = new DataTransfer();
    (z._f || []).forEach(x => dt.items.add(x.f));
    z.querySelector('.pj-envoi').files = dt.files;
    const cats = JSON.parse(z.dataset.cats);
    const taille = n => n > 1048576 ? (n / 1048576).toFixed(1) + ' Mo' : Math.ceil(n / 1024) + ' Ko';
    z.querySelector('.pj-liste').innerHTML = (z._f || []).map((x, i) => {
        const vig = x.f.type.startsWith('image/') ? '<img src="' + (x.url = x.url || URL.createObjectURL(x.f)) + '" alt="">' : '<span class="pj-ext' + (/pdf$/i.test(x.f.name) ? ' pdf' : '') + '">' + (x.f.name.split('.').pop() || 'doc').toUpperCase() + '</span>';
        const opts = Object.entries(cats).map(([c, l]) => '<option value="' + c + '"' + (c === x.cat ? ' selected' : '') + '>' + l + '</option>').join('');
        return '<div class="pj-item">' + vig
            + '<div class="pj-corps"><div class="pj-nom" title="' + x.f.name.replace(/"/g, '') + '">' + x.f.name.replace(/</g, '&lt;') + ' <span>' + taille(x.f.size) + '</span></div>'
            + '<div class="pj-champs"><select name="pieces_categorie[]" onchange="pjZone(this)._f[' + i + '].cat=this.value">' + opts + '</select>'
            + '<input type="text" name="pieces_legende[]" maxlength="200" placeholder="Légende (ex. vue avant gauche)" value="' + x.leg.replace(/"/g, '&quot;') + '" oninput="pjZone(this)._f[' + i + '].leg=this.value"></div></div>'
            + '<button type="button" class="pj-retirer" onclick="pjRetirer(this,' + i + ')" aria-label="Retirer">&times;</button></div>';
    }).join('');
}
/* L'opération modifiée a-t-elle déjà une photo ? (la photo n'est alors plus exigée) */
function pjPhotoDeja(idDialogue, oui){
    const z = document.querySelector('#' + idDialogue + ' .pj'); if (z) z.dataset.photoDeja = oui ? '1' : '';
}
/* À la fermeture du formulaire, la sélection est vidée */
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.pj').forEach(z => {
    const d = z.closest('dialog');
    if (d) d.addEventListener('close', () => { z._f = []; z.dataset.photoDeja = ''; pjRendre(z); });
    // Photo obligatoire : contrôle avant l'envoi (le serveur le vérifie aussi)
    const f = z.closest('form');
    if (f && z.dataset.photoRequise) f.addEventListener('submit', e => {
        if (z.dataset.photoDeja === '1' || (z._f || []).some(x => x.f.type.startsWith('image/'))) return;
        e.preventDefault(); e.stopImmediatePropagation();
        z.classList.add('pj-manque');
        z.scrollIntoView({block: 'center', behavior: 'smooth'});
        alert('Ajoutez au moins une photo (bouton « Photo » ou « Fichiers »).');
    }, true);
}));
</script>
@endpush
@endonce
