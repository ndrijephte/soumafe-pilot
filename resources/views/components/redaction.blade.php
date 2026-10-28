{{--
    Cadre de rédaction formatable : gras, italique, souligné, listes à puces / numérotées.
    <x-redaction name="champ" :value="$texte" />            barre d'outils toujours visible
    <x-redaction name="champ" :value="$texte" compact />    barre affichée au-dessus pendant la saisie (cellules de tableau)
    <x-redaction :value="$texte" lecture />                  affichage seul
    Le contenu est envoyé en HTML restreint (nettoyé côté serveur par App\Services\TexteRiche).
--}}
@props(['name' => null, 'value' => null, 'compact' => false, 'lecture' => false, 'placeholder' => '', 'hauteur' => null, 'vide' => '—'])
@php $html = \App\Services\TexteRiche::html($value); @endphp
@once
@push('styles')
<style>
.rd{position:relative;background:#fff;border:1px solid var(--bord);border-radius:8px}
.rd:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.rd-barre{display:flex;gap:2px;padding:3px 5px;background:#f1f5f9;border-bottom:1px solid var(--bord);border-radius:8px 8px 0 0}
.rd-barre button{background:none;border:1px solid transparent;border-radius:4px;padding:2px 7px;cursor:pointer;font-size:11.5px;color:#334155;font-weight:600;font-family:inherit;line-height:1.5}
.rd-barre button:hover{background:#e2e8f0;border-color:#cbd5e1}
.rd-barre button.on{background:#1e293b;color:#fff}
.rd-compact .rd-barre{display:none;position:absolute;bottom:100%;left:-1px;z-index:6;border:1px solid var(--bord);border-radius:7px 7px 0 0;box-shadow:0 -2px 6px rgba(15,23,42,.08)}
.rd-compact:focus-within .rd-barre{display:flex}
.rd-zone{padding:7px 10px;min-height:64px;font-size:12.5px;line-height:1.5;color:#1e293b;outline:none;overflow-wrap:anywhere}
.rd-compact .rd-zone{min-height:54px}
.rd-zone:empty:before{content:attr(data-placeholder);color:#94a3b8}
.rd-zone ul,.rd-zone ol,.rd-lecture ul,.rd-lecture ol{margin:2px 0 2px 18px;padding:0}
.rd-zone *{font-family:inherit!important;font-size:inherit!important;color:inherit!important;background:none!important}
.rd-lecture{font-size:12.5px;line-height:1.5;color:#1e293b;overflow-wrap:anywhere}
.rd-lecture.vide{color:var(--gris)}
</style>
@endpush
@push('scripts')
<script>
(function () {
    const zoneDe = el => el.closest('[data-rd]')?.querySelector('.rd-zone');
    const sync = z => { const v = z.closest('[data-rd]').querySelector('.rd-valeur'); if (v) v.value = z.innerHTML.trim(); };
    const etat = z => z.closest('[data-rd]').querySelectorAll('.rd-barre button').forEach(b => {
        try { b.classList.toggle('on', document.queryCommandState(b.dataset.cmd)); } catch (e) {}
    });
    document.addEventListener('mousedown', e => {
        const b = e.target.closest('.rd-barre button');
        if (!b) return;
        e.preventDefault();
        const z = zoneDe(b);
        z.focus();
        document.execCommand(b.dataset.cmd, false, null);
        sync(z); etat(z);
    });
    document.addEventListener('input', e => { if (e.target.classList && e.target.classList.contains('rd-zone')) sync(e.target); });
    document.addEventListener('keyup', e => { if (e.target.classList && e.target.classList.contains('rd-zone')) etat(e.target); });
    document.addEventListener('mouseup', e => { const z = e.target.closest && e.target.closest('.rd-zone'); if (z) etat(z); });
    // Collage : on ne garde que gras / italique / souligné / listes / sauts de ligne
    const GARDER = {B: 'b', STRONG: 'b', I: 'i', EM: 'i', U: 'u', UL: 'ul', OL: 'ol', LI: 'li', BR: 'br', P: 'div', DIV: 'div'};
    function nettoyer(html) {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        doc.querySelectorAll('style,script,meta,link,title').forEach(n => n.remove());
        const out = document.createElement('div');
        (function copier(src, dest) {
            src.childNodes.forEach(n => {
                if (n.nodeType === 3) { dest.appendChild(document.createTextNode(n.textContent.replace(/ /g, ' '))); return; }
                if (n.nodeType !== 1) return;
                const tag = GARDER[n.tagName];
                if (tag === 'br') { dest.appendChild(document.createElement('br')); return; }
                if (tag) { const el = document.createElement(tag); copier(n, el); dest.appendChild(el); } else copier(n, dest);
            });
        })(doc.body, out);
        return out.innerHTML;
    }
    document.addEventListener('paste', e => {
        const z = e.target.closest && e.target.closest('.rd-zone');
        if (!z) return;
        e.preventDefault();
        const html = e.clipboardData.getData('text/html');
        const txt = e.clipboardData.getData('text/plain').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        document.execCommand('insertHTML', false, html ? nettoyer(html) : txt.split(/\r?\n/).join('<br>'));
        sync(z);
    });
    document.addEventListener('submit', () => document.querySelectorAll('.rd-zone').forEach(sync), true);
    // API pour les formulaires remplis en JavaScript (dialogues)
    window.Redaction = {
        definir(nom, html) {
            const v = document.querySelector(`.rd-valeur[name="${nom}"]`);
            if (!v) return;
            v.value = html || '';
            v.closest('[data-rd]').querySelector('.rd-zone').innerHTML = html || '';
        },
    };
})();
</script>
@endpush
@endonce
@if($lecture)
<div {{ $attributes->merge(['class' => 'rd-lecture' . ($html === '' ? ' vide' : '')]) }}>{!! $html !== '' ? $html : e($vide) !!}</div>
@else
<div {{ $attributes->except(['form'])->merge(['class' => 'rd' . ($compact ? ' rd-compact' : '')]) }} data-rd>
    <div class="rd-barre">
        <button type="button" data-cmd="bold" title="Gras (Ctrl+B)"><b>G</b></button>
        <button type="button" data-cmd="italic" title="Italique (Ctrl+I)"><i>I</i></button>
        <button type="button" data-cmd="underline" title="Souligné (Ctrl+U)"><u>S</u></button>
        <button type="button" data-cmd="insertUnorderedList" title="Liste à puces">&#8226; Liste</button>
        <button type="button" data-cmd="insertOrderedList" title="Liste numérotée">1. Liste</button>
    </div>
    <div class="rd-zone" contenteditable="true" data-placeholder="{{ $placeholder }}" @if($hauteur) style="min-height:{{ $hauteur }}px" @endif>{!! $html !!}</div>
    <input type="hidden" class="rd-valeur" name="{{ $name }}" value="{{ $html }}" @if($attributes->has('form')) form="{{ $attributes->get('form') }}" @endif>
</div>
@endif
