@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Jours fériés')
@push('styles')
<style>
.fr-annee{display:flex;align-items:center;gap:4px;background:#fff;border:1px solid var(--bord);border-radius:9px;padding:2px}
.fr-annee a{display:inline-flex;align-items:center;justify-content:center;min-width:30px;height:28px;border-radius:7px;color:var(--accent);text-decoration:none;font-size:13px}
.fr-annee a:hover{background:#e6f0fa}
.fr-annee b{font-size:14px;color:var(--bleu);padding:0 8px}
.fr-date b{font-weight:600}
.fr-mobile{font-size:10.5px;color:#b45309;background:#fef3c7;border-radius:6px;padding:1px 7px;margin-left:6px;white-space:nowrap}
.ad-table tr.passe td{color:var(--gris)}
.ad-table tr.prochain td{background:#f6faff}
</style>
@endpush
@section('contenu')
@include('admin._ui')
@php
    $icEdit = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    $icSuppr = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>';
    $prochain = $feries->first(fn($f) => $f->date->gte(today()));
    // un férié qui tombe un jour non travaillé (selon les horaires de cette date) ne retire aucun jour de travail
    $travaille = fn($f) => \App\Services\CalendrierTravail::estJourTravaille($f->date);
    $horsTravail = $feries->reject($travaille)->count();
    $joursTravailles = \App\Services\CalendrierTravail::regle()->joursTravailles();
    $estMobile = fn($f) => str_ends_with(trim($f->libelle), '*');
@endphp
<h2>Administration</h2>
<div class="crumb">Administration &rsaquo; Temps de travail &rsaquo; Jours fériés</div>
@include('admin._nav')

@if(session('succes'))<div class="ad-alerte ok">{{ session('succes') }}</div>@endif
@if($errors->any())<div class="ad-alerte ko">{{ $errors->first() }}</div>@endif

<div class="ad-barre">
    <div class="fr-annee">
        <a href="{{ route('admin.feries.index', ['annee' => $annee - 1]) }}" title="Année précédente" aria-label="Année précédente">‹</a>
        <b>{{ $annee }}</b>
        <a href="{{ route('admin.feries.index', ['annee' => $annee + 1]) }}" title="Année suivante" aria-label="Année suivante">›</a>
    </div>
    <span class="compte">{{ $feries->count() }} jour{{ $feries->count() > 1 ? 's' : '' }} férié{{ $feries->count() > 1 ? 's' : '' }}@if($horsTravail) · {{ $horsTravail }} sur un jour non travaillé @endif
        @if($prochain) · prochain : <b style="color:var(--bleu)">{{ rtrim($prochain->libelle, ' *') }}</b>, {{ $prochain->date->isToday() ? "aujourd'hui" : 'dans ' . today()->diffInDays($prochain->date) . ' jours' }}@endif</span>
    <span class="espace"></span>
    <button type="button" class="btn-p" data-sans-icone onclick="frOuvrir()"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Ajouter un jour férié</button>
</div>

<div class="ad-panneau">
@if($feries->isEmpty())
    <div class="ad-vide">Aucun jour férié enregistré pour {{ $annee }}.</div>
@else
<table class="ad-table">
    <thead><tr><th>Date</th><th>Jour férié</th><th>Effet</th><th></th></tr></thead>
    <tbody>
    @foreach($feries as $f)
        @php
            $passe = $f->date->lt(today()); $mobile = $estMobile($f);
            $donnees = ['id' => $f->id, 'date' => $f->date->format('Y-m-d'), 'libelle' => rtrim($f->libelle, ' *'), 'mobile' => $mobile];
        @endphp
        <tr class="{{ $passe ? 'passe' : '' }} {{ $prochain && $prochain->is($f) ? 'prochain' : '' }}">
            <td class="fr-date" style="white-space:nowrap"><b>{{ ucfirst($f->date->translatedFormat('l j F')) }}</b></td>
            <td>{{ rtrim($f->libelle, ' *') }}@if($mobile)<span class="fr-mobile" title="Date fixée chaque année par décret">fête mobile, à confirmer</span>@endif</td>
            <td>@if(!$travaille($f))<span class="etat mut">tombe un {{ $f->date->translatedFormat('l') }} non travaillé</span>@elseif($passe)<span class="etat mut">passé</span>@else<span class="etat bleu">jour ouvré chômé</span>@endif</td>
            <td class="act">
                <button type="button" class="ib" data-sans-icone title="Modifier" aria-label="Modifier" data-ferie="{{ json_encode($donnees) }}" onclick="frOuvrir(JSON.parse(this.dataset.ferie))">{!! $icEdit !!}</button>
                <form method="POST" action="{{ route('admin.feries.destroy', $f) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'ce jour férié')">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="ib ko" data-sans-icone title="Supprimer" aria-label="Supprimer">{!! $icSuppr !!}</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif
</div>
<p class="fm-aide" style="margin:10px 2px 0">Un jour férié n'est pas compté comme jour ouvré (pointage, saisie, objectifs du parc). Les fêtes mobiles (Ascension, Tabaski, Laylat al-Qadr…) sont à confirmer par décret.</p>

<dialog id="dlg-ferie" class="fm-dlg" style="width:480px">
    <div class="fm-tete">
        <span class="fm-ic"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
        <div><h3 id="fr-titre">Ajouter un jour férié</h3><p>Le jour devient non ouvré pour le pointage, la saisie et le parc.</p></div>
    </div>
    <form method="POST" id="fr-form" action="{{ route('admin.feries.store') }}">
        @csrf <input type="hidden" name="_method" id="fr-methode" value="POST">
        <input type="hidden" name="libelle" id="fr-libelle-envoi">
        <label class="fm-lab" for="fr-lib">Nom du jour férié <span class="req">*</span></label>
        <input id="fr-lib" class="fm-champ" required maxlength="98" placeholder="Ex. : Fête de l'Indépendance" list="fr-suggestions" oninput="frResume()">
        <datalist id="fr-suggestions">@foreach(['Jour de l\'An', 'Lundi de Pâques', 'Fête du Travail', 'Ascension', 'Lundi de Pentecôte', 'Fête de l\'Indépendance', 'Assomption', 'Toussaint', 'Journée Nationale de la Paix', 'Noël', 'Fête de fin du Ramadan (Aïd el-Fitr)', 'Fête de la Tabaski (Aïd el-Kebir)', 'Lendemain du Maouloud', 'Lendemain de la Nuit du Destin (Laylat al-Qadr)'] as $s)<option value="{{ $s }}">@endforeach</datalist>
        <label class="fm-lab" for="fr-date">Date <span class="req">*</span></label>
        <input type="date" name="date" id="fr-date" class="fm-champ" required style="max-width:200px" oninput="frResume()">
        <label class="fm-option" id="fr-opt" style="margin-top:12px"><span class="inter"><input type="checkbox" id="fr-mobile" onchange="frResume()"><i></i></span>
            <span><b>Fête mobile, date à confirmer</b><span>La date varie chaque année et doit être confirmée par décret.</span></span></label>
        <p class="fm-resume" id="fr-resume"></p>
        <div class="fm-pied">
            <button type="button" class="btn-s" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn-p" id="fr-valider">Ajouter</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
var FR_JOURS = @json(array_map('intval', $joursTravailles));
function frOuvrir(f){
    var form = document.getElementById('fr-form');
    form.reset();
    document.getElementById('fr-titre').textContent = f ? 'Modifier le jour férié' : 'Ajouter un jour férié';
    document.getElementById('fr-valider').textContent = f ? 'Enregistrer' : 'Ajouter';
    form.action = f ? @js(url('admin/feries')) + '/' + f.id : @js(route('admin.feries.store'));
    document.getElementById('fr-methode').value = f ? 'PUT' : 'POST';
    document.getElementById('fr-lib').value = f ? f.libelle : '';
    document.getElementById('fr-date').value = f ? f.date : '';
    document.getElementById('fr-mobile').checked = f ? f.mobile : false;
    frResume(); document.getElementById('dlg-ferie').showModal(); document.getElementById('fr-lib').focus();
}
function frResume(){
    var d = document.getElementById('fr-date').value, r = document.getElementById('fr-resume'), m = document.getElementById('fr-mobile').checked;
    document.getElementById('fr-opt').classList.toggle('on', m);
    document.getElementById('fr-libelle-envoi').value = document.getElementById('fr-lib').value.trim().replace(/\s*\*$/, '') + (m ? ' *' : '');
    if (!d) { r.innerHTML = ''; return; }
    var x = new Date(d + 'T12:00'), iso = x.getDay() || 7, travaille = FR_JOURS.indexOf(iso) >= 0;
    r.innerHTML = '<b>' + x.toLocaleDateString('fr-FR', {weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'}) + '</b> · '
        + (travaille ? 'un jour de travail en moins' : 'jour déjà non travaillé : aucun jour de travail en moins');
}
document.getElementById('fr-form').addEventListener('submit', frResume, true);
</script>
@endpush
