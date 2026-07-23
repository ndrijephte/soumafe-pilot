@extends('layouts.app')
@section('titre', 'Import de données')
@push('styles')
<style>
.import-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;max-width:800px}
@media(max-width:768px){.import-grid{grid-template-columns:1fr}}
.import-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:20px}
.import-card h3{font-size:15px;color:var(--bleu);margin-bottom:6px}
.import-card p{font-size:12px;color:var(--gris);margin-bottom:14px;line-height:1.5}
.import-card label{display:block;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:4px}
.import-card input[type=file]{width:100%;padding:8px;border:1px dashed var(--bord);border-radius:8px;font-size:12px;margin-bottom:12px;background:var(--fond)}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 18px;border-radius:8px;font-size:13px;cursor:pointer}
.btn:hover{opacity:.9}
.msg{padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}
.msg-ok{background:#dcfce7;color:var(--ok)}
.msg-ko{background:#fee2e2;color:var(--ko)}
.info-box{background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:11.5px;color:#1d4ed8;margin-bottom:16px;line-height:1.5}
</style>
@endpush
@section('contenu')
<h2>Import de données</h2>
<div class="crumb">Administration &rsaquo; Import Excel</div>

@if(session('succes'))<div class="msg msg-ok">{{ session('succes') }}</div>@endif
@if($errors->any())<div class="msg msg-ko">{{ $errors->first() }}</div>@endif

<div class="info-box">
    Formats attendus : fichiers <strong>.xlsx</strong> uniquement. Les doublons sont détectés automatiquement.
</div>

<div class="import-grid">
    {{-- US-19 : Collaborateurs --}}
    <div class="import-card">
        <h3>Collaborateurs</h3>
        <p>Import depuis le fichier personnel (colonnes attendues : Nom, Prénom, Email, Fonction, Département).</p>
        <form method="POST" action="{{ route('admin.import.collaborateurs') }}" enctype="multipart/form-data">
            @csrf
            <label>Fichier Excel (.xlsx) <span style="color:var(--ko)">*</span></label>
            <input type="file" name="fichier" accept=".xlsx" required>
            <button class="btn" type="submit">Importer les collaborateurs</button>
        </form>
    </div>

    {{-- US-20 : Tâches --}}
    <div class="import-card">
        <h3>Tâches (suivi quotidien)</h3>
        <p>Import depuis 01_SUIVI_QUOTIDIEN_ACTIVITES.xlsx (feuille SAISIE, en-tête ligne 4 : Date, Collaborateur, Projet, Tâche, Statut).</p>
        <form method="POST" action="{{ route('admin.import.taches') }}" enctype="multipart/form-data">
            @csrf
            <label>Fichier Excel (.xlsx) <span style="color:var(--ko)">*</span></label>
            <input type="file" name="fichier" accept=".xlsx" required>
            <button class="btn" type="submit">Importer les tâches</button>
        </form>
    </div>
</div>
@endsection
