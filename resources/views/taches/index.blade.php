@extends('layouts.app')
@section('sousTitre', 'Mes activités')
@section('titre', 'Mes activités')

@push('styles')
<style>
    .kanban{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
    .col{background:#eef2f7;border-radius:10px;padding:12px;min-height:260px;transition:background .15s}
    .col[data-statut="termine"]{background:#f0fdf4}
    .col[data-statut="bloque"]{background:#fef2f2}
    .col:hover{background:#e8edf4}
    .col h4{font-size:11px;color:var(--gris);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center}
    .col h4 span{background:var(--accent);color:#fff;border-radius:50%;width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;font-size:10px}
    .tache{position:relative;background:#fff;border:1px solid var(--bord);border-radius:8px;padding:10px 26px 10px 12px;margin-bottom:8px;font-size:12.5px;cursor:grab;transition:box-shadow .15s,transform .1s;-webkit-user-select:none;user-select:none}
    .tache:hover{box-shadow:0 2px 8px rgba(0,0,0,.08);transform:translateY(-1px)}
    .tache-ghost{position:fixed;pointer-events:none;z-index:999;opacity:.85;box-shadow:0 8px 24px rgba(0,0,0,.18);border-radius:8px;transform:rotate(2deg)}
    .col.drop-target{background:#dbeafe !important;border:2px dashed var(--accent)}
    /* Voyant d'échéance : rouge en retard, orange ≤ 2 j, jaune ≤ 5 j, vert au-delà, gris sans échéance, bleu terminée */
    .du-jour{display:table;margin:0 0 5px;padding:1px 8px;border-radius:999px;background:#eef4fb;border:1px solid #bfdbfe;color:#2e74b5;font-size:10px;font-weight:700;letter-spacing:.3px;text-transform:uppercase}
    .tache>.voyant{position:absolute;top:9px;right:9px}
    /* Légende cliquable : filtre le tableau par niveau d'urgence */
    .urg-legende{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-bottom:12px;font-size:12px;color:#475569}
    .urg-legende button{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border:1px solid var(--bord);border-radius:999px;background:#fff;color:#334155;font:inherit;font-weight:600;cursor:pointer}
    .urg-legende button:hover{border-color:#93c5fd}
    .urg-legende button[aria-pressed=true]{background:#eef4fb;border-color:#2e74b5;color:#1f4e79}
    .urg-legende .pt{width:9px;height:9px;border-radius:50%;background:var(--v)}
    .urg-legende .nb{color:#64748b;font-weight:500}
    .kanban.filtre-actif .tache{display:none!important}
    .kanban.filtre-actif .tache.urg-ok-filtre{display:block!important}
    .kanban.filtre-actif .btn-collapse{display:none}
    .tache .meta{display:flex;justify-content:space-between;margin-top:6px;font-size:10.5px;color:var(--gris)}
    .btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer}
    .btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
    .barre-outils{display:flex;gap:8px;margin-bottom:14px;align-items:center;flex-wrap:wrap}
    dialog{border:none;border-radius:14px;padding:24px;width:430px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
    dialog::backdrop{background:rgba(22,56,92,.35)}
    dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
    dialog input,dialog select{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
    dialog input:focus,dialog select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
    .toast{position:fixed;bottom:18px;right:18px;background:#16385C;color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;display:none;z-index:50}

    /* Selects élégants */
    .filtre-select{appearance:none;-webkit-appearance:none;background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 10px center;padding:7px 30px 7px 12px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;color:#1e293b;cursor:pointer;min-width:130px}
    .filtre-select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
    .filtre-select:hover{border-color:var(--accent)}

    .req{color:var(--ko);font-weight:700}

    /* Sélection multiple : une case par carte, une barre d'actions commune */
    .btn-sel{display:inline-flex;align-items:center;gap:6px}
    .btn-sel[aria-pressed=true]{background:#eef4fb;color:#1f4e79;border-color:#2e74b5}
    .kanban.mode-selection .tache{padding-left:36px;cursor:pointer}
    .kanban.mode-selection .tache a{pointer-events:none}
    .tache .coche{display:none;position:absolute;left:11px;top:11px;width:16px;height:16px;border:1.5px solid #94a3b8;border-radius:4px;background:#fff}
    .kanban.mode-selection .tache .coche{display:block}
    .tache.choisie{border-color:#2e74b5;box-shadow:0 0 0 2px #dbeafe}
    .tache.choisie .coche{background:#2e74b5;border-color:#2e74b5}
    .tache.choisie .coche::after{content:"";position:absolute;left:4.5px;top:1px;width:4px;height:8px;border:solid #fff;border-width:0 2px 2px 0;transform:rotate(45deg)}
    .col .tout{display:none;background:none;border:0;padding:0;font:inherit;font-size:10.5px;font-weight:700;color:#2e74b5;cursor:pointer;text-transform:none;letter-spacing:0;margin-left:auto;margin-right:8px}
    .kanban.mode-selection .col .tout{display:inline}
    .barre-sel{position:fixed;left:50%;bottom:18px;transform:translate(-50%,140%);z-index:40;display:flex;align-items:center;gap:6px;flex-wrap:wrap;max-width:calc(100vw - 32px);background:#fff;border:1px solid #cbd5e1;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,.18);padding:8px 10px;transition:transform .2s;font-size:12.5px}
    .barre-sel.visible{transform:translate(-50%,0)}
    .barre-sel .nb{font-weight:700;color:#1f4e79;padding:0 8px 0 4px;white-space:nowrap}
    .barre-sel button{border:1px solid #e2e8f0;background:#fff;color:#334155;border-radius:8px;padding:6px 11px;font:inherit;font-weight:600;cursor:pointer;white-space:nowrap}
    .barre-sel button:hover{border-color:#93c5fd;background:#f8fafc}
    .barre-sel button.ok{color:#15803d}.barre-sel button.ko{color:#b91c1c}
    .barre-sel .sep{width:1px;height:22px;background:#e2e8f0;margin:0 2px}
    .barre-sel button.fermer{border:0;color:#64748b;padding:6px 8px}
    dialog textarea{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font:inherit;font-size:13px;min-height:90px;resize:vertical}
    .ecartees{margin:0 0 10px;padding:9px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:12.5px;color:#92400e}
    .ecartees ul{margin:4px 0 0 18px;padding:0}

    @media(max-width:900px){
        .kanban{grid-template-columns:1fr 1fr}
        .barre-outils{flex-direction:column;align-items:stretch}
        .barre-outils form[method="GET"]{margin-left:0}
    }
    @media(max-width:600px){
        .kanban{grid-template-columns:1fr}
        dialog{width:min(430px,92vw);padding:16px}
        .tache{font-size:12px;padding:8px 24px 8px 10px}
        .tache .meta{flex-direction:column;gap:2px}
    }
</style>
@endpush

@section('contenu')
<h2>Mes activités</h2>
<div class="onglets-page">
    <a href="{{ route('taches.index') }}" class="actif">Mes tâches</a>
    <a href="{{ route('saisie.index') }}">Suivi de saisie</a>
</div>

@if(session('succes'))<div class="carte" style="background:#f0fdf4;margin-bottom:10px">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="carte" style="background:#fef2f2;color:#991b1b;margin-bottom:10px">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="carte" style="background:#fef2f2;color:#991b1b;margin-bottom:10px">{{ $errors->first() }}</div>@endif
@if(session('ecartees'))<div class="ecartees"><strong>Tâches non modifiées :</strong><ul>@foreach(session('ecartees') as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="barre-outils">
    <button class="btn" onclick="document.getElementById('dlg-tache').showModal()">+ Nouvelle tâche</button>
    @if($affichage !== 'archives')
    <button type="button" class="btn sec btn-sel" id="btn-sel" aria-pressed="false" onclick="modeSelection()" title="Choisir plusieurs tâches pour leur appliquer une même opération">
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Sélectionner
    </button>
    @endif
    <form method="GET" style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
        <select name="affichage" class="filtre-select" onchange="this.form.submit()" title="Les archives regroupent les tâches des sprints clôturés et les tâches terminées avant l'exercice en cours">
            <option value="actives" @selected($affichage === 'actives')>Tâches actives</option>
            <option value="archives" @selected($affichage === 'archives')>Archives ({{ $nbArchivees }})</option>
            <option value="toutes" @selected($affichage === 'toutes')>Toutes les tâches</option>
        </select>
        <select name="projet" class="filtre-select" onchange="this.form.submit()">
            <option value="">Projet : tous</option>
            @foreach($projets as $p)<option value="{{ $p->id }}" @selected(request('projet')==$p->id)>{{ $p->libelle }}</option>@endforeach
        </select>
        <select name="type" class="filtre-select" onchange="this.form.submit()">
            <option value="">Type : tous</option>
            <option value="T1" @selected(request('type')=='T1')>T1 - Sprint</option>
            <option value="T2" @selected(request('type')=='T2')>T2 - Opérationnelle</option>
            <option value="T3" @selected(request('type')=='T3')>T3 - Urgente</option>
        </select>
    </form>
</div>

@if($affichage === 'archives')
<div class="carte" style="margin-bottom:12px;padding:10px 14px;font-size:12.5px;color:#475569;background:#f8fafc;border-color:#e2e8f0">
    Consultation des archives : tâches des sprints clôturés et tâches hors sprint terminées avant le {{ $debutExercice->format('d/m/Y') }} (début de l'exercice en cours). Elles ne sont plus modifiables depuis ce tableau.
</div>
@endif
@php
    $parUrgence = collect($colonnes)->keys()->flatMap(fn($c) => $taches[$c] ?? collect())->countBy(fn($t) => $t->urgence()['niveau']);
@endphp
@if($parUrgence->isNotEmpty())
<div class="urg-legende" id="urg-legende" role="group" aria-label="Filtrer par urgence">
    @foreach(\App\Models\Tache::URGENCES as $niv => $lib)
    @if($parUrgence[$niv] ?? 0)
    <button type="button" class="v-{{ $niv }}" data-niveau="{{ $niv }}" aria-pressed="false"><span class="pt"></span>{{ $lib }} <span class="nb">{{ $parUrgence[$niv] }}</span></button>
    @endif
    @endforeach
</div>
@endif
<div class="kanban" id="kanban">
    @foreach($colonnes as $code => $libelle)
    <div class="col" data-statut="{{ $code }}" ondragover="event.preventDefault()" ondrop="deposer(event,'{{ $code }}')">
        <h4>{{ $libelle }} <button type="button" class="tout" onclick="toutColonne(this)">Tout</button><span>{{ ($taches[$code] ?? collect())->count() }}</span></h4>
        <div data-collapse="8">
        @foreach($taches[$code] ?? [] as $t)
        @php
            $sprintBloque = $t->sprint_id && $t->sprint?->statut !== 'en_cours';
            $peutDrag = $code !== 'termine' && !$sprintBloque && $affichage !== 'archives';
        @endphp
        <div class="tache" data-collapse-item data-id="{{ $t->id }}" data-urgence="{{ $t->urgence()['niveau'] }}"
             draggable="{{ $peutDrag ? 'true' : 'false' }}" @if($peutDrag)ondragstart="event.dataTransfer.setData('id',{{ $t->id }})"@endif style="{{ !$peutDrag ? 'cursor:default;opacity:.55' : '' }}" @if($sprintBloque) title="Sprint {{ $t->sprint?->statut === 'planifie' ? 'non démarré' : 'terminé' }}"@endif>
            <span class="coche" aria-hidden="true"></span>
            <x-voyant :tache="$t" />
            @if($code === 'a_faire' && \App\Http\Controllers\TacheController::estDuJour($t))<span class="du-jour" title="Tâche à réaliser aujourd'hui">Aujourd'hui</span>@endif
            <span class="typ {{ strtolower($t->type) }}">{{ $t->type }}</span><a href="{{ route('taches.show', $t) }}" style="text-decoration:none;color:inherit">{{ $t->intitule }}</a>
            <div class="meta">
                <span>{{ $t->projet?->libelle ?? $t->activite ?? '—' }}</span>
                @if($code === 'termine')<span>Fait le {{ \App\Http\Controllers\TacheController::dateActivite($t)->format('d/m') }}</span>@else<span>{{ $t->echeance?->format('d/m') ?? 'sans échéance' }}</span>@endif
            </div>
        </div>
        @endforeach
        </div>
    </div>
    @endforeach
</div>

{{-- US-06 : formulaire 3 clics --}}
<dialog id="dlg-tache">
    <h3 style="color:var(--bleu);margin-bottom:4px">Nouvelle tâche</h3>
    <form method="POST" action="{{ route('taches.store') }}">@csrf
        <label>Type <span class="req">*</span></label>
        <select name="type" id="sel-type" onchange="basculeType()">
            <option value="T2">T2 - Opérationnelle planifiée</option>
            <option value="T1">T1 - Tâche de sprint</option>
            <option value="T3">T3 - Urgente non planifiée</option>
        </select>
        <label>Intitulé <span class="req">*</span></label><input name="intitule" required autofocus>
        <label>Projet</label>
        <select name="projet_id" id="sel-projet" onchange="chargerSprints()">
            <option value="">— Aucun projet —</option>
            @foreach($projets as $p)<option value="{{ $p->id }}">{{ $p->libelle }}</option>@endforeach
        </select>
        <div id="champs-sprint" style="display:none">
            <label>Sprint <span class="req">*</span></label>
            <select name="sprint_id" id="sel-sprint"><option value="">— Choisir un projet d'abord —</option></select>
            <label>Points <span class="req">*</span></label><input name="points" type="number" min="1" max="21">
        </div>
        <label>Priorité <span class="req">*</span></label>
        <select name="priorite"><option value="moyenne">Moyenne</option><option value="haute">Haute</option><option value="basse">Basse</option></select>
        <label>Début <span class="req">*</span></label><input type="date" name="date_debut" value="{{ today()->toDateString() }}" required>
        <label>Échéance <span class="req" id="echeance-star">*</span></label>
        <input type="date" name="echeance" id="inp-echeance">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>

@if($affichage !== 'archives')
<div class="barre-sel" id="barre-sel" role="toolbar" aria-label="Opération sur les tâches sélectionnées">
    <span class="nb" id="sel-nb">0 tâche</span>
    <button type="button" onclick="operation('demarrer')">Démarrer</button>
    <button type="button" class="ok" onclick="operation('terminer')">Terminer</button>
    <button type="button" onclick="operation('a_faire')">Remettre à faire</button>
    <button type="button" class="ko" onclick="ouvrirTexte('bloquer')">Bloquer…</button>
    <span class="sep"></span>
    <button type="button" onclick="ouvrirTexte('commenter')" title="Ajoute le même commentaire au journal de chaque tâche">Commenter…</button>
    <button type="button" class="fermer" onclick="modeSelection(false)" title="Quitter la sélection">✕</button>
</div>
<form method="POST" action="{{ route('taches.groupe') }}" id="form-groupe" style="display:none">@csrf
    <input type="hidden" name="action"><input type="hidden" name="texte">
    @foreach(['affichage', 'projet', 'type'] as $f)@if(request($f))<input type="hidden" name="{{ $f }}" value="{{ request($f) }}">@endif @endforeach
</form>
<dialog id="dlg-texte">
    <h3 id="dt-titre" style="color:var(--bleu);margin-bottom:4px"></h3>
    <p id="dt-aide" style="font-size:12.5px;color:var(--gris);margin:0 0 6px"></p>
    <textarea id="dt-texte" maxlength="2000"></textarea>
    <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button type="button" class="btn" id="dt-ok">Appliquer</button>
    </div>
</dialog>
@endif

<div class="toast" id="toast"></div>
@endsection

@push('scripts')
<script>
function basculeType(){
    const type = document.getElementById('sel-type').value;
    document.getElementById('champs-sprint').style.display = type === 'T1' ? 'block' : 'none';
    const inp = document.getElementById('inp-echeance');
    const star = document.getElementById('echeance-star');
    if (type === 'T3') {
        inp.required = false;
        star.style.display = 'none';
    } else {
        inp.required = true;
        star.style.display = 'inline';
    }
}
async function chargerSprints(){
    const projetId = document.getElementById('sel-projet').value;
    const sel = document.getElementById('sel-sprint');
    sel.innerHTML = '<option value="">— Chargement… —</option>';
    if (!projetId) { sel.innerHTML = '<option value="">— Choisir un projet d\'abord —</option>'; return; }
    try {
        const rep = await fetch(`/api/projets/${projetId}/sprints`);
        const sprints = await rep.json();
        if (sprints.length === 0) {
            sel.innerHTML = '<option value="">Aucun sprint disponible</option>';
        } else {
            sel.innerHTML = '<option value="">— Choisir —</option>' +
                sprints.map(s => `<option value="${s.id}">Sprint ${s.numero} - ${s.objectif}</option>`).join('');
        }
    } catch(e) { sel.innerHTML = '<option value="">Erreur de chargement</option>'; }
}
function toast(msg, type){
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'warn' ? '#b45309' : type === 'ko' ? 'var(--ko)' : '#16385C';
    t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 4500);
}
// Légende des voyants : un clic filtre le tableau, un second clic retire le filtre
(function () {
    const leg = document.getElementById('urg-legende'), kb = document.getElementById('kanban');
    if (!leg || !kb) return;
    leg.addEventListener('click', e => {
        const b = e.target.closest('button'); if (!b) return;
        const actif = b.getAttribute('aria-pressed') === 'true';
        leg.querySelectorAll('button').forEach(x => x.setAttribute('aria-pressed', 'false'));
        kb.classList.toggle('filtre-actif', !actif);
        kb.querySelectorAll('.tache').forEach(t => t.classList.toggle('urg-ok-filtre', !actif && t.dataset.urgence === b.dataset.niveau));
        if (!actif) b.setAttribute('aria-pressed', 'true');
        kb.querySelectorAll('.col').forEach(c => {
            const nb = c.querySelector('h4 span');
            if (nb) nb.textContent = actif ? c.querySelectorAll('.tache').length : c.querySelectorAll('.tache.urg-ok-filtre').length;
        });
    });
})();

/* ── Sélection multiple ── */
const kanban = document.getElementById('kanban');
function choisies(){ return [...kanban.querySelectorAll('.tache.choisie')]; }
function majBarre(){
    const n = choisies().length, barre = document.getElementById('barre-sel');
    if (!barre) return;
    document.getElementById('sel-nb').textContent = n + ' tâche' + (n > 1 ? 's' : '');
    barre.classList.toggle('visible', n > 0);
}
function modeSelection(actif){
    const btn = document.getElementById('btn-sel');
    actif = actif ?? !kanban.classList.contains('mode-selection');
    kanban.classList.toggle('mode-selection', actif);
    btn?.setAttribute('aria-pressed', actif);
    // Le glisser-déposer est suspendu pendant la sélection
    kanban.querySelectorAll('.tache').forEach(t => {
        if (actif) { t.dataset.drag = t.getAttribute('draggable'); t.setAttribute('draggable', 'false'); }
        else { if (t.dataset.drag) t.setAttribute('draggable', t.dataset.drag); t.classList.remove('choisie'); }
    });
    majBarre();
}
kanban.addEventListener('click', e => {
    if (!kanban.classList.contains('mode-selection')) return;
    const carte = e.target.closest('.tache');
    if (!carte || e.target.closest('.voyant')) return;
    e.preventDefault();
    carte.classList.toggle('choisie');
    majBarre();
});
function toutColonne(btn){
    // Toute la colonne, cartes repliées comprises ; avec un filtre d'urgence actif, seulement les cartes filtrées
    const filtre = kanban.classList.contains('filtre-actif');
    const cartes = [...btn.closest('.col').querySelectorAll('.tache')].filter(t => !filtre || t.classList.contains('urg-ok-filtre'));
    const tout = cartes.every(t => t.classList.contains('choisie'));
    cartes.forEach(t => t.classList.toggle('choisie', !tout));
    majBarre();
}
function operation(action, texte){
    const ids = choisies().map(t => t.dataset.id);
    if (!ids.length) return;
    const libelles = {demarrer: 'Démarrer', terminer: 'Terminer', a_faire: 'Remettre à faire'};
    if (libelles[action] && !confirm(libelles[action] + ' ' + ids.length + ' tâche(s) ?')) return;
    const f = document.getElementById('form-groupe');
    f.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
    ids.forEach(id => f.insertAdjacentHTML('beforeend', `<input type="hidden" name="ids[]" value="${id}">`));
    f.querySelector('[name=action]').value = action; f.querySelector('[name=texte]').value = texte || '';
    f.submit();
}
function ouvrirTexte(action){
    const n = choisies().length, d = document.getElementById('dlg-texte'), zone = document.getElementById('dt-texte');
    document.getElementById('dt-titre').textContent = action === 'bloquer' ? `Bloquer ${n} tâche(s)` : `Commenter ${n} tâche(s)`;
    document.getElementById('dt-aide').textContent = action === 'bloquer'
        ? 'Motif du blocage : il est enregistré sur chaque tâche, les pilotes sont prévenus et il figure dans le rapport de review.'
        : 'Le commentaire est ajouté au journal de chaque tâche ; il est repris dans le rapport de review du sprint.';
    zone.value = '';
    document.getElementById('dt-ok').onclick = () => { if (!zone.value.trim()) { zone.focus(); return; } operation(action, zone.value.trim()); };
    d.showModal(); zone.focus();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape' && kanban.classList.contains('mode-selection') && !document.querySelector('dialog[open]')) modeSelection(false); });

async function deposer(ev, statut){
    const id = ev.dataTransfer.getData('id');
    if (!id) return;
    await deplacerTache(id, statut);
}
async function deplacerTache(id, statut){
    try {
        const rep = await fetch(`/taches/${id}/statut`, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
            body: JSON.stringify({statut})
        });
        if (!rep.ok) {
            let msg = 'Erreur : action non autorisée.';
            try { const err = await rep.json(); if (err.message) msg = err.message; } catch(e){}
            toast(msg, 'ko');
            return;
        }
        const d = await rep.json();
        if (d.justification_requise) {
            toast(d.message + ' → ouvrez la tâche pour commenter.', 'warn');
        } else {
            toast(d.message);
        }
        setTimeout(() => location.reload(), 900);
    } catch(e) {
        toast('Erreur réseau - réessayez.', 'ko');
    }
}

/* ── Touch drag-and-drop mobile ── */
(function(){
    var dragId=null, ghost=null, srcCol=null, startX=0, startY=0, dragging=false;
    var THRESHOLD=10; // px avant d'activer le drag

    document.querySelectorAll('.tache[draggable="true"]').forEach(function(el){
        el.addEventListener('touchstart', onTouchStart, {passive:false});
    });

    function onTouchStart(e){
        var t=e.touches[0];
        startX=t.clientX; startY=t.clientY; dragging=false;
        var card=e.currentTarget;
        // chercher l'id dans ondragstart
        var ds=card.getAttribute('ondragstart');
        if(!ds) return;
        var m=ds.match(/setData\('id',(\d+)\)/);
        if(!m) return;
        dragId=m[1];
        srcCol=card.closest('.col');

        document.addEventListener('touchmove', onTouchMove, {passive:false});
        document.addEventListener('touchend', onTouchEnd, {passive:false});

        // garder ref pour cleanup
        card._touchCard=true;
    }

    function onTouchMove(e){
        var t=e.touches[0];
        var dx=t.clientX-startX, dy=t.clientY-startY;

        if(!dragging){
            if(Math.abs(dx)+Math.abs(dy)<THRESHOLD) return;
            dragging=true;
            // créer ghost
            var src=document.querySelector('.tache[draggable="true"][ondragstart*="'+dragId+'"]');
            if(!src) return;
            ghost=src.cloneNode(true);
            ghost.className='tache tache-ghost';
            ghost.style.width=src.offsetWidth+'px';
            document.body.appendChild(ghost);
            src.style.opacity='.3';
        }

        e.preventDefault(); // empêcher scroll pendant drag

        if(ghost){
            ghost.style.left=(t.clientX-40)+'px';
            ghost.style.top=(t.clientY-20)+'px';
        }

        // highlight colonne cible
        document.querySelectorAll('.col').forEach(function(c){ c.classList.remove('drop-target'); });
        var target=getColUnder(t.clientX, t.clientY);
        if(target && target!==srcCol) target.classList.add('drop-target');
    }

    function onTouchEnd(e){
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);

        if(!dragging){ dragId=null; return; }

        // restaurer opacité source
        var src=document.querySelector('.tache[style*="opacity"]');
        if(src) src.style.opacity='';

        // retirer ghost
        if(ghost){ ghost.remove(); ghost=null; }

        // retirer highlights
        document.querySelectorAll('.col').forEach(function(c){ c.classList.remove('drop-target'); });

        // trouver colonne cible
        var ct=e.changedTouches[0];
        var target=getColUnder(ct.clientX, ct.clientY);
        if(target && target!==srcCol && dragId){
            var statut=target.getAttribute('data-statut');
            deplacerTache(dragId, statut);
        }
        dragId=null; dragging=false;
    }

    function getColUnder(x, y){
        var cols=document.querySelectorAll('.col');
        for(var i=0;i<cols.length;i++){
            var r=cols[i].getBoundingClientRect();
            if(x>=r.left && x<=r.right && y>=r.top && y<=r.bottom) return cols[i];
        }
        return null;
    }
})();
</script>
@endpush
