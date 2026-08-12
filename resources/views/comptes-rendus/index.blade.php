@extends('layouts.app')
@section('titre', 'Comptes rendus')
@section('contenu')
<style>
.cr-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:12px}
.cr-header h2{font-size:1.5rem;font-weight:700;color:var(--bleu,#1F4E79)}
.cr-filtres{display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:14px 20px;background:#fff;border-radius:12px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.cr-filtres select,.cr-filtres input[type=date]{padding:7px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.85rem;background:#fff}
.cr-filtres label{font-size:.78rem;font-weight:600;color:#64748b;margin-right:-4px}
.cr-filtres .sep{width:1px;height:28px;background:#e2e8f0;margin:0 4px}
.btn-filter{padding:7px 16px;border-radius:8px;font-size:.82rem;font-weight:600;color:#fff;background:var(--bleu,#1F4E79);border:none;cursor:pointer;transition:background .15s}
.btn-filter:hover{background:var(--accent,#2E74B5)}
.btn-reset{padding:7px 14px;border-radius:8px;font-size:.82rem;color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0;text-decoration:none;transition:all .15s}
.btn-reset:hover{background:#e2e8f0;color:#334155}
.cr-cards{display:flex;flex-direction:column;gap:14px}
.cr-card{background:#fff;border-radius:12px;padding:18px 22px;box-shadow:0 1px 4px rgba(0,0,0,.06);display:flex;justify-content:space-between;align-items:center;gap:16px;transition:box-shadow .15s}
.cr-card:hover{box-shadow:0 3px 12px rgba(0,0,0,.1)}
.cr-card .left{flex:1}
.cr-card .titre{font-weight:600;font-size:1rem;color:var(--bleu,#1F4E79);margin-bottom:4px}
.cr-card .meta{font-size:.82rem;color:#64748b}
.cr-card .meta span{margin-right:14px}
.badge-type{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;color:#fff}
.badge-type.reunion{background:var(--bleu,#1F4E79)}
.badge-type.demo{background:var(--accent,#2E74B5)}
.badge-statut-cr{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600}
.badge-statut-cr.brouillon{background:#fef3c7;color:#92400e}
.badge-statut-cr.confirme{background:#d1fae5;color:#065f46}
.cr-actions{display:flex;gap:8px}
.cr-actions a{padding:6px 14px;border-radius:8px;font-size:.82rem;font-weight:500;text-decoration:none;transition:background .15s}
.cr-actions .btn-voir{background:#EFF4FB;color:var(--bleu,#1F4E79)}
.cr-actions .btn-voir:hover{background:#DDEAF5}
.cr-actions .btn-dl{background:#EFF4FB;color:var(--accent,#2E74B5)}
.cr-actions .btn-dl:hover{background:#DDEAF5}
.btn-cr-new{display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;color:#fff;background:var(--bleu,#1F4E79);transition:background .15s;box-shadow:0 1px 3px rgba(31,78,121,.15)}
.btn-cr-new:hover{background:var(--accent,#2E74B5)}
.btn-cr-new.sec{background:transparent;color:var(--bleu,#1F4E79);border:1px solid var(--bleu,#1F4E79);box-shadow:none}
.btn-cr-new.sec:hover{background:#EFF4FB}
.empty-state{text-align:center;padding:60px 20px;color:#94a3b8;font-size:.95rem}
.pag{margin-top:20px;display:flex;justify-content:center}
.pag nav{display:flex;gap:4px}
</style>

<div class="cr-header">
    <h2>Comptes rendus</h2>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        @if($user->estPilote())
        <a href="{{ route('comptes-rendus.create', ['type' => 'reunion_ordinaire']) }}" class="btn-cr-new">+ Réunion</a>
        <a href="{{ route('comptes-rendus.create', ['type' => 'demo_sprint']) }}" class="btn-cr-new sec">+ Démo</a>
        @endif
    </div>
</div>

<form class="cr-filtres" method="GET">
    <label>Type</label>
    <select name="type">
        <option value="">Tous</option>
        <option value="reunion_ordinaire" @selected(request('type')==='reunion_ordinaire')>Réunion ordinaire</option>
        <option value="reunion_extraordinaire" @selected(request('type')==='reunion_extraordinaire')>Réunion extraordinaire</option>
        <option value="demo_sprint" @selected(request('type')==='demo_sprint')>Démonstration sprint</option>
    </select>
    <span class="sep"></span>
    <label>Rédacteur</label>
    <select name="redacteur_id">
        <option value="">Tous</option>
        @foreach($redacteurs as $red)
        <option value="{{ $red->id }}" @selected(request('redacteur_id') == $red->id)>{{ $red->nomComplet() }}</option>
        @endforeach
    </select>
    <span class="sep"></span>
    <label>Du</label>
    <input type="date" name="date_du" value="{{ request('date_du') }}">
    <label>Au</label>
    <input type="date" name="date_au" value="{{ request('date_au') }}">
    <span class="sep"></span>
    <button type="submit" class="btn-filter">Filtrer</button>
    @if(request()->hasAny(['type','redacteur_id','date_du','date_au']))
    <a href="{{ route('comptes-rendus.index') }}" class="btn-reset">Réinitialiser</a>
    @endif
</form>

@if(session('ok'))
<div class="alert ok" style="margin-bottom:16px;padding:10px 16px;background:#d1fae5;color:#065f46;border-radius:8px;font-size:.9rem">{{ session('ok') }}</div>
@endif

<div class="cr-cards" data-collapse="8">
@forelse($crs as $cr)
    <div class="cr-card" data-collapse-item>
        <div class="left">
            <div class="titre">{{ $cr->titre }}</div>
            <div class="meta">
                <span class="badge-type {{ $cr->estReunion() ? 'reunion' : 'demo' }}">{{ $cr->typeCourt() }}</span>
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
