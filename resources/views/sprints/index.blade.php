@extends('layouts.app')
@section('titre', 'Gestion des sprints')
@push('styles')
<style>
.projet-bloc{margin-bottom:24px}
.projet-titre{font-size:15px;color:var(--bleu);font-weight:600;margin-bottom:8px;display:flex;align-items:center;gap:8px}
.sprint-card{background:var(--carte);border:1px solid var(--bord);border-radius:10px;padding:12px 16px;margin-bottom:8px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px}
.sprint-info{font-size:13px}
.sprint-info .num{font-weight:700;color:var(--bleu)}
.sprint-info .obj{color:var(--gris);font-size:12px;margin-top:2px}
.sprint-stats{display:flex;gap:14px;font-size:12px;color:var(--gris)}
.sprint-stats strong{color:#1e293b}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.pill{padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600}
.s-planifie{background:#e2e8f0;color:#475569}.s-en_cours{background:#dbeafe;color:#1d4ed8}.s-termine{background:#dcfce7;color:var(--ok)}
dialog{border:none;border-radius:14px;padding:24px;width:460px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
dialog input,dialog select{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:900px){.sprint-card{flex-direction:column;align-items:flex-start}.form-row{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
    <h2>Gestion des sprints</h2>
    <button class="btn" onclick="document.getElementById('dlg-sprint').showModal()">+ Nouveau sprint</button>
</div>
<div class="crumb">Projets &rsaquo; Sprints</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

@foreach($projets as $p)
<div class="projet-bloc">
    <div class="projet-titre">{{ $p->code }} — {{ $p->libelle }}</div>
    @forelse($p->sprints as $s)
    <div class="sprint-card">
        <div class="sprint-info">
            <div><span class="num">Sprint {{ $s->numero }}</span> <span class="pill s-{{ $s->statut }}">{{ ucfirst(str_replace('_',' ',$s->statut)) }}</span></div>
            <div class="obj">{{ $s->objectif }}</div>
            <div style="font-size:11.5px;color:var(--gris);margin-top:2px">{{ $s->date_debut->format('d/m') }} → {{ $s->date_fin->format('d/m/Y') }}</div>
        </div>
        <div class="sprint-stats">
            <div>Tâches : <strong>{{ $s->taches->count() }}</strong></div>
            <div>Livrés : <strong>{{ $s->pointsLivres() }} pts</strong></div>
            @if($s->engagement_points)<div>Engagement : <strong>{{ $s->engagement_points }} pts</strong></div>@endif
        </div>
    </div>
    @empty
    <p style="color:var(--gris);font-size:13px;margin-left:8px">Aucun sprint.</p>
    @endforelse
</div>
@endforeach

<dialog id="dlg-sprint">
    <h3 style="color:var(--bleu);margin-bottom:4px">Nouveau sprint</h3>
    <form method="POST" action="{{ route('sprints.store') }}">@csrf
        <label>Projet *</label>
        <select name="projet_id" required>
            <option value="">— Choisir —</option>
            @foreach($projets as $p)<option value="{{ $p->id }}">{{ $p->code }} — {{ $p->libelle }}</option>@endforeach
        </select>
        <div class="form-row">
            <div><label>N° du sprint *</label><input type="number" name="numero" min="1" required></div>
            <div><label>Points engagés</label><input type="number" name="engagement_points" min="1"></div>
        </div>
        <label>Objectif *</label><input name="objectif" required placeholder="Ex : kanban + admin comptes">
        <div class="form-row">
            <div><label>Début *</label><input type="date" name="date_debut" required></div>
            <div><label>Fin *</label><input type="date" name="date_fin" required></div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Créer le sprint</button>
        </div>
    </form>
</dialog>
@endsection
