@extends('layouts.app')
@section('titre', 'Mes activités')

@push('styles')
<style>
    .kanban{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
    .col{background:#eef2f7;border-radius:10px;padding:10px;min-height:200px}
    .col h4{font-size:12px;color:var(--gris);text-transform:uppercase;margin-bottom:8px;display:flex;justify-content:space-between}
    .tache{background:#fff;border:1px solid var(--bord);border-radius:8px;padding:9px 10px;margin-bottom:8px;font-size:12.5px;cursor:grab}
    .tache.retard{border-left:3px solid var(--ko)}
    .tache .meta{display:flex;justify-content:space-between;margin-top:6px;font-size:10.5px;color:var(--gris)}
    .btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer}
    .btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
    .barre-outils{display:flex;gap:8px;margin-bottom:14px;align-items:center}
    dialog{border:1px solid var(--bord);border-radius:12px;padding:20px;width:430px}
    dialog::backdrop{background:rgba(22,56,92,.35)}
    dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
    dialog input,dialog select{width:100%;padding:7px;border:1px solid var(--bord);border-radius:7px;font-size:13px}
    .toast{position:fixed;bottom:18px;right:18px;background:#16385C;color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;display:none;z-index:50}
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
    <form method="GET" style="margin-left:auto;display:flex;gap:6px">
        <select name="projet" onchange="this.form.submit()">
            <option value="">Tous les projets</option>
            @foreach($projets as $p)<option value="{{ $p->id }}" @selected(request('projet')==$p->id)>{{ $p->libelle }}</option>@endforeach
        </select>
        <select name="type" onchange="this.form.submit()">
            <option value="">Tous types</option>
            @foreach(['T1','T2','T3'] as $t)<option @selected(request('type')==$t)>{{ $t }}</option>@endforeach
        </select>
    </form>
</div>

<div class="kanban">
    @foreach($colonnes as $code => $libelle)
    <div class="col" data-statut="{{ $code }}" ondragover="event.preventDefault()" ondrop="deposer(event,'{{ $code }}')">
        <h4>{{ $libelle }} <span>{{ ($taches[$code] ?? collect())->count() }}</span></h4>
        @foreach($taches[$code] ?? [] as $t)
        <div class="tache {{ $t->echeance?->isPast() && $code !== 'termine' ? 'retard' : '' }}"
             draggable="true" ondragstart="event.dataTransfer.setData('id',{{ $t->id }})">
            <span class="typ {{ strtolower($t->type) }}">{{ $t->type }}</span>{{ $t->intitule }}
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
    <h3 style="color:var(--bleu)">Nouvelle tâche</h3>
    <form method="POST" action="{{ route('taches.store') }}">@csrf
        <label>Intitulé *</label><input name="intitule" required autofocus>
        <label>Type *</label>
        <select name="type" id="sel-type" onchange="basculeT1()">
            <option value="T2">T2 — Opérationnelle planifiée</option>
            <option value="T1">T1 — Tâche de sprint</option>
            <option value="T3">T3 — Urgente non planifiée</option>
        </select>
        <div id="champs-t1" style="display:none">
            <label>Projet *</label>
            <select name="projet_id"><option value="">—</option>
                @foreach($projets as $p)<option value="{{ $p->id }}">{{ $p->libelle }}</option>@endforeach
            </select>
            <label>Sprint * <small>(aucune tâche projet hors sprint)</small></label>
            <select name="sprint_id" id="sel-sprint"><option value="">—</option></select>
            <label>Points</label><input name="points" type="number" min="1" max="21">
        </div>
        <label>Priorité</label>
        <select name="priorite"><option value="moyenne">Moyenne</option><option value="haute">Haute</option><option value="basse">Basse</option></select>
        <label>Début</label><input type="date" name="date_debut" value="{{ today()->toDateString() }}">
        <label>Échéance</label><input type="date" name="echeance">
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
function basculeT1(){
    document.getElementById('champs-t1').style.display =
        document.getElementById('sel-type').value === 'T1' ? 'block' : 'none';
}
function toast(msg){
    const t = document.getElementById('toast');
    t.textContent = msg; t.style.display = 'block';
    setTimeout(() => t.style.display = 'none', 4500);
}
async function deposer(ev, statut){
    const id = ev.dataTransfer.getData('id');
    const rep = await fetch(`/taches/${id}/statut`, {
        method: 'PATCH',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        body: JSON.stringify({statut})
    });
    const d = await rep.json();
    toast(d.message);
    if (d.justification_requise) {
        toast(d.message + ' → ouvrez la tâche pour ajouter votre commentaire.');
    }
    setTimeout(() => location.reload(), 900);
}
</script>
@endpush
