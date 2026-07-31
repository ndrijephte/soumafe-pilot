@extends('layouts.app')
@section('titre', 'Comptes rendus')
@section('contenu')
<style>
.cr-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.cr-header h2{font-size:1.5rem;font-weight:700;color:#1a1a2e}
.cr-filtres{display:flex;gap:8px;align-items:center}
.cr-filtres select{padding:6px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem}
.cr-cards{display:flex;flex-direction:column;gap:14px}
.cr-card{background:#fff;border-radius:12px;padding:18px 22px;box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:center;gap:16px;transition:box-shadow .15s}
.cr-card:hover{box-shadow:0 3px 12px rgba(0,0,0,.1)}
.cr-card .left{flex:1}
.cr-card .titre{font-weight:600;font-size:1rem;color:#1a1a2e;margin-bottom:4px}
.cr-card .meta{font-size:.82rem;color:#64748b}
.cr-card .meta span{margin-right:14px}
.badge-type{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;color:#fff}
.badge-type.reunion{background:#2563eb}
.badge-type.demo{background:#059669}
.badge-statut-cr{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-statut-cr.brouillon{background:#fef3c7;color:#92400e}
.badge-statut-cr.confirme{background:#d1fae5;color:#065f46}
.cr-actions{display:flex;gap:8px}
.cr-actions a{padding:6px 14px;border-radius:8px;font-size:.82rem;font-weight:500;text-decoration:none;transition:background .15s}
.cr-actions .btn-voir{background:#e0e7ff;color:#3730a3}
.cr-actions .btn-voir:hover{background:#c7d2fe}
.cr-actions .btn-dl{background:#ecfdf5;color:#047857}
.cr-actions .btn-dl:hover{background:#d1fae5}
.empty-state{text-align:center;padding:60px 20px;color:#94a3b8;font-size:.95rem}
.pag{margin-top:20px;display:flex;justify-content:center}
.pag nav{display:flex;gap:4px}
</style>

<div class="cr-header">
    <h2>Comptes rendus</h2>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form class="cr-filtres" method="GET">
            <select name="type" onchange="this.form.submit()">
                <option value="">Tous les types</option>
                <option value="reunion_hebdo" @selected(request('type')==='reunion_hebdo')>Réunion hebdomadaire</option>
                <option value="demo_sprint" @selected(request('type')==='demo_sprint')>Démonstration sprint</option>
            </select>
        </form>
        @if($user->estPilote())
        <a href="{{ route('comptes-rendus.create', ['type' => 'reunion_hebdo']) }}" class="btn">+ CR Réunion</a>
        <a href="{{ route('comptes-rendus.create', ['type' => 'demo_sprint']) }}" class="btn sec">+ CR Démo</a>
        @endif
    </div>
</div>

@if(session('ok'))
<div class="alert ok" style="margin-bottom:16px;padding:10px 16px;background:#d1fae5;color:#065f46;border-radius:8px;font-size:.9rem">{{ session('ok') }}</div>
@endif

<div class="cr-cards">
@forelse($crs as $cr)
    <div class="cr-card">
        <div class="left">
            <div class="titre">{{ $cr->titre }}</div>
            <div class="meta">
                <span class="badge-type {{ $cr->type === 'reunion_hebdo' ? 'reunion' : 'demo' }}">{{ $cr->typeLibelle() }}</span>
                <span>{{ $cr->date_reunion->format('d/m/Y') }}</span>
                <span>Réd. : {{ $cr->redacteur?->nomComplet() ?? '—' }}</span>
                <span class="badge-statut-cr {{ $cr->statut }}">{{ $cr->statut === 'confirme' ? 'Confirmé' : 'Brouillon' }}</span>
            </div>
        </div>
        <div class="cr-actions">
            <a href="{{ route('comptes-rendus.show', $cr) }}" class="btn-voir">Consulter</a>
            @if($cr->estBrouillon() && ($user->estPilote() || $cr->redacteur_id === $user->id))
            <a href="{{ route('comptes-rendus.edit', $cr) }}" class="btn-voir" style="background:#fff7ed;color:#c2410c">Éditer</a>
            @endif
            @if($cr->estConfirme())
            <a href="{{ route('comptes-rendus.telecharger', $cr) }}" class="btn-dl">DOCX</a>
            @endif
        </div>
    </div>
@empty
    <div class="empty-state">Aucun compte rendu pour le moment.</div>
@endforelse
</div>

<div class="pag">{{ $crs->links() }}</div>
@endsection
