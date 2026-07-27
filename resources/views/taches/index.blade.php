@extends('layouts.app')
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
    .tache{background:#fff;border:1px solid var(--bord);border-radius:8px;padding:10px 12px;margin-bottom:8px;font-size:12.5px;cursor:grab;transition:box-shadow .15s,transform .1s}
    .tache:hover{box-shadow:0 2px 8px rgba(0,0,0,.08);transform:translateY(-1px)}
    .tache.retard{border-left:3px solid var(--ko)}
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

    @media(max-width:900px){
        .kanban{grid-template-columns:1fr 1fr}
        .barre-outils{flex-direction:column;align-items:stretch}
        .barre-outils form[method="GET"]{margin-left:0}
    }
    @media(max-width:600px){
        .kanban{grid-template-columns:1fr}
    }
</style>
@endpush

@section('contenu')
<h2>Mes activités</h2>

@if(session('succes'))<div class="carte" style="background:#f0fdf4;margin-bottom:10px">{{ session('succes') }}</div>@endif

<div class="barre-outils">
    <button class="btn" onclick="document.getElementById('dlg-tache').showModal()">+ Nouvelle tâche</button>
    <form method="POST" action="{{ route('taches.dupliquer-veille') }}">@csrf
        <button class="btn sec">Reprendre la veille</button>
    </form>
    <form method="GET" style="margin-left:auto;display:flex;gap:8px">
        <select name="projet" class="filtre-select" onchange="this.form.submit()">
            <option value="">Projet : tous</option>
            @foreach($projets as $p)<option value="{{ $p->id }}" @selected(request('projet')==$p->id)>{{ $p->libelle }}</option>@endforeach
        </select>
        <select name="type" class="filtre-select" onchange="this.form.submit()">
            <option value="">Type : tous</option>
            <option value="T1" @selected(request('type')=='T1')>T1 — Sprint</option>
            <option value="T2" @selected(request('type')=='T2')>T2 — Opérationnelle</option>
            <option value="T3" @selected(request('type')=='T3')>T3 — Urgente</option>
        </select>
    </form>
</div>

<div class="kanban">
    @foreach($colonnes as $code => $libelle)
    <div class="col" data-statut="{{ $code }}" ondragover="event.preventDefault()" ondrop="deposer(event,'{{ $code }}')">
        <h4>{{ $libelle }} <span>{{ ($taches[$code] ?? collect())->count() }}</span></h4>
        @foreach($taches[$code] ?? [] as $t)
        @php $peutDrag = $code !== 'termine'; @endphp
        <div class="tache {{ $t->echeance?->isPast() && $code !== 'termine' ? 'retard' : '' }}"
             draggable="{{ $peutDrag ? 'true' : 'false' }}" @if($peutDrag)ondragstart="event.dataTransfer.setData('id',{{ $t->id }})"@endif style="{{ !$peutDrag ? 'cursor:default;opacity:.85' : '' }}">
            <span class="typ {{ strtolower($t->type) }}">{{ $t->type }}</span><a href="{{ route('taches.show', $t) }}" style="text-decoration:none;color:inherit">{{ $t->intitule }}</a>
            <div class="meta">
                <span>{{ $t->projet?->libelle ?? $t->activite ?? '—' }}</span>
                <span>{{ $t->echeance?->format('d/m') ?? 'sans échéance' }}</span>
            </div>
        </div>
        @endforeach
    </div>
    @endforeach
</div>

{{-- US-06 : formulaire 3 clics --}}
<dialog id="dlg-tache">
    <h3 style="color:var(--bleu);margin-bottom:4px">Nouvelle tâche</h3>
    <form method="POST" action="{{ route('taches.store') }}">@csrf
        <label>Type <span class="req">*</span></label>
        <select name="type" id="sel-type" onchange="basculeType()">
            <option value="T2">T2 — Opérationnelle planifiée</option>
            <option value="T1">T1 — Tâche de sprint</option>
            <option value="T3">T3 — Urgente non planifiée</option>
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
                sprints.map(s => `<option value="${s.id}">Sprint ${s.numero} — ${s.objectif}</option>`).join('');
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
async function deposer(ev, statut){
    const id = ev.dataTransfer.getData('id');
    if (!id) return;

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
        toast('Erreur réseau — réessayez.', 'ko');
    }
}
</script>
@endpush
