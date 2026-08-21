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
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:12px;color:#1e40af;margin-bottom:16px;line-height:1.5}
@media(max-width:900px){.param-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
<h2>Paramètres du parc automobile</h2>
<div class="crumb">Administration &rsaquo; Paramètres parc</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif

<div class="carte form-admin">
<div class="info-box">
    Les objectifs de recette sont appliqués automatiquement lors de la saisie d'une recette, selon la catégorie du véhicule (Taxi / VTC) et le type de jour. Le dimanche et les jours fériés (table Jours fériés) sont détectés automatiquement.
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
        <a href="{{ route('admin.utilisateurs.index') }}" class="btn sec">Retour</a>
    </div>
</form>
</div>
@endsection
