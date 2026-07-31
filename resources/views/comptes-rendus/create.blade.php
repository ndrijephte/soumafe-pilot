@extends('layouts.app')
@section('titre', 'Nouveau compte rendu')
@section('contenu')
<style>
.cr-form{max-width:660px;margin:0 auto;background:#fff;border-radius:14px;padding:32px;box-shadow:0 1px 6px rgba(0,0,0,.06)}
.cr-form h2{font-size:1.3rem;font-weight:700;color:#1a1a2e;margin-bottom:20px}
.fg{margin-bottom:16px}
.fg label{display:block;font-size:.85rem;font-weight:600;color:#475569;margin-bottom:4px}
.fg input,.fg select{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem}
.fg .row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.actions{display:flex;gap:10px;justify-content:flex-end;margin-top:20px}
.type-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.type-tabs a{padding:8px 16px;border-radius:20px;font-size:.82rem;font-weight:600;text-decoration:none;transition:background .15s}
.type-tabs a.active{background:#2563eb;color:#fff}
.type-tabs a:not(.active){background:#f1f5f9;color:#475569}
.type-tabs a:not(.active):hover{background:#e2e8f0}
</style>

<div class="cr-form">
    <div class="type-tabs">
        <a href="{{ route('comptes-rendus.create', ['type' => 'reunion_ordinaire']) }}" class="{{ $type === 'reunion_ordinaire' ? 'active' : '' }}">Réunion ordinaire</a>
        <a href="{{ route('comptes-rendus.create', ['type' => 'reunion_extraordinaire']) }}" class="{{ $type === 'reunion_extraordinaire' ? 'active' : '' }}">Réunion extraordinaire</a>
        <a href="{{ route('comptes-rendus.create', ['type' => 'demo_sprint']) }}" class="{{ $type === 'demo_sprint' ? 'active' : '' }}">Démo sprint</a>
    </div>

    <h2>Désigner le rédacteur — {{ match($type) {
        'reunion_ordinaire' => 'Réunion ordinaire (hebdomadaire)',
        'reunion_extraordinaire' => 'Réunion extraordinaire (stratégique)',
        'demo_sprint' => 'Démonstration sprint',
    } }}</h2>

    @if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.85rem">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('comptes-rendus.designer') }}">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="fg">
            <label>Titre</label>
            <input name="titre" value="{{ old('titre', match($type) {
                'reunion_ordinaire' => 'Réunion hebdomadaire du ' . now()->format('d/m/Y'),
                'reunion_extraordinaire' => 'Réunion extraordinaire du ' . now()->format('d/m/Y'),
                'demo_sprint' => 'Démonstration sprint',
            }) }}" required>
        </div>

        <div class="fg">
            <label>Date de la réunion</label>
            <input type="date" name="date_reunion" value="{{ old('date_reunion', now()->format('Y-m-d')) }}" required>
        </div>

        <div class="fg">
            <div class="row2">
                <div>
                    <label>Heure début</label>
                    <input type="time" name="heure_debut" value="{{ old('heure_debut') }}">
                </div>
                <div>
                    <label>Heure fin</label>
                    <input type="time" name="heure_fin" value="{{ old('heure_fin') }}">
                </div>
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
            <label>Sprint concerné</label>
            <select name="sprint_id">
                <option value="">— Choisir —</option>
                @foreach($sprints as $s)
                <option value="{{ $s->id }}" @selected(old('sprint_id', $sprintId) == $s->id)>
                    {{ $s->libelle ?? "Sprint #{$s->id}" }} ({{ $s->projet?->libelle ?? '—' }})
                </option>
                @endforeach
            </select>
        </div>
        @endif

        <div class="actions">
            <a href="{{ route('comptes-rendus.index') }}" class="btn sec">Annuler</a>
            <button type="submit" class="btn">Désigner et créer</button>
        </div>
    </form>
</div>
@endsection
