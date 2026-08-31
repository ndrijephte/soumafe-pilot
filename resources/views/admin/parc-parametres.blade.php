@extends('layouts.app')
@section('titre', 'Paramètres parc automobile')
@push('styles')
<style>
.form-admin{max-width:700px}
.param-section{font-size:12px;font-weight:700;text-transform:uppercase;color:var(--accent);margin:20px 0 10px;padding-bottom:4px;border-bottom:1px solid #e8f0fe;letter-spacing:.5px}
.param-section:first-child{margin-top:0}
.param-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:10px}
.param-item label{display:block;font-size:12px;color:var(--gris);font-weight:600;margin-bottom:4px}
.param-item input{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;text-align:right}
.param-item input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.btn.ko{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:12px;color:#1e40af;margin-bottom:16px;line-height:1.5}
.type-list{list-style:none;padding:0;margin:0}
.type-item{display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:13px}
.type-item:last-child{border-bottom:none}
.type-item .libelle{flex:1}
.type-item.inactif .libelle{color:var(--gris);text-decoration:line-through}
.type-item .code{font-size:11px;color:var(--gris);font-family:monospace}
.add-type-row{display:flex;gap:8px;margin-top:8px;align-items:flex-end}
.add-type-row input{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px;flex:1}
@media(max-width:900px){.param-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
<h2>Paramètres du parc automobile</h2>
<div class="crumb">Administration &rsaquo; Paramètres parc</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif

<div class="carte form-admin">
<div class="info-box">
    Les objectifs de recette sont appliqués automatiquement lors de la saisie d'une recette, selon la catégorie du véhicule et le type de jour.
</div>

<form method="POST" action="{{ route('admin.parc-parametres.update') }}">
    @csrf

    @php
        $parGroupe = $parametres->groupBy('groupe');
        $labelGroupe = ['recettes' => 'Objectifs de recette journalière (FCFA)', 'general' => 'Général'];
    @endphp

    @foreach($parGroupe as $groupe => $items)
    <div class="param-section">{{ $labelGroupe[$groupe] ?? ucfirst($groupe) }}</div>
    <div class="param-grid">
        @foreach($items as $p)
        <div class="param-item">
            <label>{{ $p->libelle }}</label>
            <input type="number" name="params[{{ $p->id }}]" value="{{ $p->valeur }}" min="0">
        </div>
        @endforeach
    </div>
    @endforeach

    <div style="display:flex;gap:10px;margin-top:18px">
        <button class="btn">Enregistrer</button>
        <a href="{{ route('parc.index') }}" class="btn sec">Retour au parc</a>
    </div>
</form>
</div>

{{-- ===== TYPES PÉNALITÉS ===== --}}
<div class="carte form-admin" style="margin-top:20px">
    <div class="param-section" style="margin-top:0">Types de pénalités</div>
    <ul class="type-list">
    @foreach($typesPenalite as $tp)
        <li class="type-item {{ $tp->actif ? '' : 'inactif' }}">
            <span class="code">{{ $tp->code }}</span>
            <span class="libelle">{{ $tp->libelle }}</span>
            <form method="POST" action="{{ route('admin.parc-parametres.types.toggle', $tp) }}" style="display:inline">
                @csrf @method('PUT')
                <button class="btn sm {{ $tp->actif ? 'sec' : '' }}" title="{{ $tp->actif ? 'Désactiver' : 'Activer' }}">{{ $tp->actif ? 'Actif' : 'Inactif' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.parc-parametres.types.destroy', $tp) }}" style="display:inline" onsubmit="return confirm('Supprimer ce type ?')">
                @csrf @method('DELETE')
                <button class="btn sm ko">Suppr.</button>
            </form>
        </li>
    @endforeach
    </ul>
    <form method="POST" action="{{ route('admin.parc-parametres.types.store') }}" class="add-type-row">
        @csrf
        <input type="hidden" name="categorie" value="penalite">
        <input name="code" placeholder="Code (ex: defaut_phare)" required>
        <input name="libelle" placeholder="Libellé (ex: Défaut de phare)" required>
        <button class="btn sm">+ Ajouter</button>
    </form>
</div>

{{-- ===== TYPES MAINTENANCE ===== --}}
<div class="carte form-admin" style="margin-top:20px">
    <div class="param-section" style="margin-top:0">Types de maintenance</div>
    <ul class="type-list">
    @foreach($typesMaintenance as $tm)
        <li class="type-item {{ $tm->actif ? '' : 'inactif' }}">
            <span class="code">{{ $tm->code }}</span>
            <span class="libelle">{{ $tm->libelle }}</span>
            <form method="POST" action="{{ route('admin.parc-parametres.types.toggle', $tm) }}" style="display:inline">
                @csrf @method('PUT')
                <button class="btn sm {{ $tm->actif ? 'sec' : '' }}">{{ $tm->actif ? 'Actif' : 'Inactif' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.parc-parametres.types.destroy', $tm) }}" style="display:inline" onsubmit="return confirm('Supprimer ce type ?')">
                @csrf @method('DELETE')
                <button class="btn sm ko">Suppr.</button>
            </form>
        </li>
    @endforeach
    </ul>
    <form method="POST" action="{{ route('admin.parc-parametres.types.store') }}" class="add-type-row">
        @csrf
        <input type="hidden" name="categorie" value="maintenance">
        <input name="code" placeholder="Code (ex: pneus)" required>
        <input name="libelle" placeholder="Libellé (ex: Changement pneus)" required>
        <button class="btn sm">+ Ajouter</button>
    </form>
</div>
{{-- ===== MOTIFS D'ABSENCE DE RECETTE ===== --}}
<div class="carte form-admin" style="margin-top:20px">
    <div class="param-section" style="margin-top:0">Motifs d'absence de recette</div>
    <div class="info-box" style="background:#fff7ed;border-color:#fed7aa;color:#92400e">
        Lorsqu'un chauffeur ne verse pas de recette (montant = 0), il doit obligatoirement sélectionner un motif dans cette liste.
    </div>
    <ul class="type-list">
    @foreach($motifsAbsence as $ma)
        <li class="type-item {{ $ma->actif ? '' : 'inactif' }}">
            <span class="code">{{ $ma->code }}</span>
            <span class="libelle">{{ $ma->libelle }}</span>
            <form method="POST" action="{{ route('admin.parc-parametres.types.toggle', $ma) }}" style="display:inline">
                @csrf @method('PUT')
                <button class="btn sm {{ $ma->actif ? 'sec' : '' }}">{{ $ma->actif ? 'Actif' : 'Inactif' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.parc-parametres.types.destroy', $ma) }}" style="display:inline" onsubmit="return confirm('Supprimer ce motif ?')">
                @csrf @method('DELETE')
                <button class="btn sm ko">Suppr.</button>
            </form>
        </li>
    @endforeach
    </ul>
    <form method="POST" action="{{ route('admin.parc-parametres.types.store') }}" class="add-type-row">
        @csrf
        <input type="hidden" name="categorie" value="motif_absence">
        <input name="code" placeholder="Code (ex: accident)" required>
        <input name="libelle" placeholder="Libellé (ex: Accident de circulation)" required>
        <button class="btn sm">+ Ajouter</button>
    </form>
</div>
@endsection
