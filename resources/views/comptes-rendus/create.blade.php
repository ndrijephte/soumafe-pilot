@extends('layouts.app')
@section('titre', 'Nouveau compte rendu')
@section('contenu')
<style>
.cr-form{max-width:660px;margin:0 auto;background:#fff;border-radius:14px;padding:32px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.cr-form h2{font-size:1.15rem;font-weight:700;color:var(--bleu,#1F4E79);margin-bottom:4px}
.cr-sub{font-size:.8rem;color:#94a3b8;margin-bottom:22px}
.fg{margin-bottom:16px}
.fg label{display:block;font-size:.85rem;font-weight:600;color:#475569;margin-bottom:4px}
.fg input,.fg select{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem;transition:border-color .15s}
.fg input:focus,.fg select:focus{border-color:var(--bleu,#1F4E79);outline:none;box-shadow:0 0 0 3px rgba(31,78,121,.1)}
.fg .row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.cr-tabs{display:flex;gap:0;margin-bottom:24px;border-radius:10px;overflow:hidden;border:1px solid #d1d5db}
.cr-tabs a{flex:1;text-align:center;padding:10px 16px;font-size:.85rem;font-weight:600;text-decoration:none;transition:background .15s,color .15s;color:#64748b;background:#f8fafc}
.cr-tabs a.active{background:var(--bleu,#1F4E79);color:#fff}
.cr-tabs a:not(.active):hover{background:#e2e8f0}
.cr-bar{display:flex;gap:10px;justify-content:flex-end;margin-top:24px;padding-top:16px;border-top:1px solid #f1f5f9}
.cr-bar .btn-annuler{padding:9px 20px;border-radius:8px;font-size:.85rem;font-weight:500;text-decoration:none;color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0;transition:background .15s}
.cr-bar .btn-annuler:hover{background:#e2e8f0;color:#334155}
.cr-bar .btn-creer{padding:9px 24px;border-radius:8px;font-size:.85rem;font-weight:600;color:#fff;background:var(--bleu,#1F4E79);border:none;cursor:pointer;transition:background .15s;box-shadow:0 1px 3px rgba(31,78,121,.2)}
.cr-bar .btn-creer:hover{background:var(--accent,#2E74B5)}
</style>

<div class="cr-form">
    <div class="cr-tabs">
        <a href="{{ route('comptes-rendus.create', ['type' => 'reunion_ordinaire']) }}" class="{{ $type !== 'demo_sprint' ? 'active' : '' }}">Réunion</a>
        <a href="{{ route('comptes-rendus.create', ['type' => 'demo_sprint']) }}" class="{{ $type === 'demo_sprint' ? 'active' : '' }}">Démo sprint</a>
    </div>

    <h2>Désigner le rédacteur</h2>
    <div class="cr-sub">{{ $type === 'demo_sprint' ? 'Démonstration sprint' : 'Compte rendu de réunion' }}</div>

    @if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.85rem">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('comptes-rendus.designer') }}">
        @csrf

        <div class="fg">
            <label>Titre</label>
            <input name="titre" value="{{ old('titre', match($type) {
                'demo_sprint' => 'Démonstration sprint',
                default => 'Réunion du ' . now()->format('d/m/Y'),
            }) }}" required>
        </div>

        @if($type !== 'demo_sprint')
        <div class="fg">
            <label>Type de réunion</label>
            <select name="type">
                <option value="reunion_ordinaire" @selected(old('type', $type) !== 'reunion_extraordinaire')>Ordinaire (hebdomadaire)</option>
                <option value="reunion_extraordinaire" @selected(old('type', $type) === 'reunion_extraordinaire')>Extraordinaire (stratégique)</option>
            </select>
        </div>
        @else
        <input type="hidden" name="type" value="demo_sprint">
        @endif

        <div class="fg">
            <label>Date</label>
            <input type="date" name="date_reunion" value="{{ old('date_reunion', now()->format('Y-m-d')) }}" required>
        </div>

        <div class="fg">
            <div class="row2">
                <div><label>Heure début</label><input type="time" name="heure_debut" value="{{ old('heure_debut') }}"></div>
                <div><label>Heure fin</label><input type="time" name="heure_fin" value="{{ old('heure_fin') }}"></div>
            </div>
        </div>

        <div class="fg">
            <label>Lieu</label>
            <input name="lieu" value="{{ old('lieu', 'Salle de réunion') }}">
        </div>

        @if($type !== 'demo_sprint')
        <div class="fg">
            <label>Président de séance</label>
            <select name="president_id">
                <option value="">— Choisir —</option>
                @foreach($tousUsers as $u)
                <option value="{{ $u->id }}" @selected(old('president_id') == $u->id)>{{ $u->nomComplet() }} ({{ $u->roleLibelle() }})</option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="fg">
            <label>Rédacteur désigné</label>
            <select name="redacteur_id" required>
                <option value="">— Choisir —</option>
                @foreach($collaborateurs as $c)
                <option value="{{ $c->id }}" @selected(old('redacteur_id') == $c->id)>{{ $c->nomComplet() }} ({{ $c->roleLibelle() }})</option>
                @endforeach
            </select>
        </div>

        @if($type === 'demo_sprint')
        <div class="fg">
            <label>Projet concerné</label>
            <select id="projet-select" onchange="filtrerSprints()">
                <option value="">— Tous les projets —</option>
                @foreach($projets as $p)
                <option value="{{ $p->id }}">{{ $p->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="fg">
            <label>Sprint</label>
            <select name="sprint_id" id="sprint-select">
                <option value="">— Choisir —</option>
                @foreach($sprints as $s)
                <option value="{{ $s->id }}" data-projet="{{ $s->projet_id }}" @selected(old('sprint_id', $sprintId) == $s->id)>
                    Sprint {{ $s->numero }} — {{ $s->projet?->libelle ?? '—' }}
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="cr-bar">
            <a href="{{ route('comptes-rendus.index') }}" class="btn-annuler">Annuler</a>
            <button type="submit" class="btn-creer">Désigner et créer</button>
        </div>
    </form>
</div>

@if($type === 'demo_sprint')
<script>
function filtrerSprints(){
    const pid = document.getElementById('projet-select').value;
    document.querySelectorAll('#sprint-select option[data-projet]').forEach(o => {
        o.style.display = (!pid || o.dataset.projet === pid) ? '' : 'none';
    });
}
</script>
@endif
@endsection
