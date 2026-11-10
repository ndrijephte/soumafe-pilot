@extends('layouts.app')
@section('sousTitre', 'Comptes rendus')
@section('titre', 'Comptes rendus')
@section('contenu')
<style>
.cr-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:12px}
.cr-header h2{font-size:1.5rem;font-weight:700;color:var(--bleu,#1F4E79);margin:0}
.btn-cr-new{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;color:#fff;background:var(--bleu,#1F4E79);transition:background .15s}
.btn-cr-new:hover{background:var(--accent,#2E74B5)}
.btn-cr-new.sec{background:#fff;color:var(--bleu,#1F4E79);border:1px solid #bfdbfe}
.btn-cr-new.sec:hover{background:#EFF4FB}
/* Filtres : une ligne sobre */
.cr-filtres{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(0,1.2fr) minmax(0,1fr) minmax(0,1fr) auto;gap:8px;align-items:end;margin-bottom:12px}
.cr-filtres label{display:block;font-size:10.5px;font-weight:600;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px}
.cr-filtres select,.cr-filtres input{width:100%;box-sizing:border-box;height:34px;padding:0 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;background:#fff}
.cr-filtres .acts{display:flex;gap:6px}
.btn-filter{height:34px;padding:0 14px;border-radius:8px;font-size:12.5px;font-weight:600;color:#fff;background:var(--bleu,#1F4E79);border:none;cursor:pointer}
.btn-reset{height:34px;display:inline-flex;align-items:center;padding:0 12px;border-radius:8px;font-size:12.5px;color:#64748b;background:#fff;border:1px solid var(--bord);text-decoration:none}
/* Tableau (modèle Pointage) */
.cr-tbl{width:100%;border-collapse:collapse;font-size:12.5px}
.cr-tbl th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap}
.cr-tbl td{padding:8px 10px;border-bottom:1px solid var(--bord);vertical-align:middle}
.cr-tbl tbody tr:hover{background:#f8fafc}
.cr-tbl .date{white-space:nowrap;color:#334155}
.cr-tbl .date small{display:block;color:var(--gris);font-size:10.5px}
.cr-tbl .titre a{color:var(--bleu,#1F4E79);font-weight:600;text-decoration:none}
.cr-tbl .titre a:hover{text-decoration:underline}
.cr-tbl .titre small{display:block;color:var(--gris);font-size:11px;margin-top:1px}
.cr-type{display:inline-block;padding:2px 8px;border-radius:6px;font-size:10.5px;font-weight:600;background:#eef4fb;color:#2e74b5;white-space:nowrap}
.cr-type.reunion{background:#f1f5f9;color:#334155}
.cr-statut{display:inline-flex;align-items:center;gap:6px;white-space:nowrap;font-size:12px;color:#334155}
.cr-statut i{width:8px;height:8px;border-radius:50%;background:#f59e0b}
.cr-statut.confirme i{background:#16a34a}.cr-statut.en_revision i{background:#2e74b5}
.cr-acts{display:flex;gap:4px;justify-content:flex-end}
.cr-acts a{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;color:#475569;border:1px solid transparent;text-decoration:none}
.cr-acts a:hover{background:#eef4fb;border-color:#bfdbfe;color:#2e74b5}
.cr-acts svg{width:15px;height:15px}
.empty-state{text-align:center;padding:40px 20px;color:#94a3b8;font-size:.9rem}
@media(max-width:900px){.cr-filtres{grid-template-columns:1fr 1fr}.cr-filtres .acts{grid-column:1/-1}.cr-filtres .acts .btn-filter{flex:1}.cr-tbl .col-red,.cr-tbl .col-type{display:none}}
@media(max-width:600px){.cr-header{flex-direction:column;align-items:flex-start}.cr-tbl .col-statut{display:none}.cr-tbl td,.cr-tbl th{padding:7px 6px}}
</style>
@php
    $ic = fn($d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
    $icVoir = $ic('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>');
    $icEditer = $ic('<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>');
    $icDocx = $ic('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/>');
    $icExcel = $ic('<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/>');
    $statuts = ['brouillon' => 'Brouillon', 'en_revision' => 'En relecture', 'confirme' => 'Confirmé'];
@endphp

<div class="cr-header">
    <h2>Comptes rendus</h2>
    @if($user->estPilote())
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('comptes-rendus.create', ['type' => 'reunion_ordinaire']) }}" class="btn-cr-new">+ Réunion</a>
        <a href="{{ route('comptes-rendus.create', ['type' => 'demo_sprint']) }}" class="btn-cr-new sec">+ Démo</a>
    </div>
    @endif
</div>

<form class="cr-filtres" method="GET">
    <div>
        <label>Type</label>
        <select name="type">
            <option value="">Tous</option>
            <option value="reunion_ordinaire" @selected(request('type')==='reunion_ordinaire')>Réunion ordinaire</option>
            <option value="reunion_extraordinaire" @selected(request('type')==='reunion_extraordinaire')>Réunion extraordinaire</option>
            <option value="demo_sprint" @selected(request('type')==='demo_sprint')>Démonstration sprint</option>
            <option value="review_sprint" @selected(request('type')==='review_sprint')>Review de sprint</option>
            <option value="retrospective" @selected(request('type')==='retrospective')>Rétrospective</option>
        </select>
    </div>
    <div>
        <label>Rédacteur</label>
        <select name="redacteur_id">
            <option value="">Tous</option>
            @foreach($redacteurs as $red)
            <option value="{{ $red->id }}" @selected(request('redacteur_id') == $red->id)>{{ $red->nomComplet() }}</option>
            @endforeach
        </select>
    </div>
    <div><label>Du</label><input type="date" name="date_du" value="{{ request('date_du') }}"></div>
    <div><label>Au</label><input type="date" name="date_au" value="{{ request('date_au') }}"></div>
    <div class="acts">
        <button type="submit" class="btn-filter">Filtrer</button>
        @if(request()->hasAny(['type','redacteur_id','date_du','date_au']))
        <a href="{{ route('comptes-rendus.index') }}" class="btn-reset" title="Réinitialiser les filtres">✕</a>
        @endif
    </div>
</form>

@if(session('ok'))
<div style="margin-bottom:12px;padding:10px 14px;background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;border-radius:10px;font-size:.86rem">{{ session('ok') }}</div>
@endif

<div class="carte" style="padding:0;overflow-x:auto">
<table class="cr-tbl">
    <thead><tr>
        <th style="width:96px">Date</th><th>Compte rendu</th><th class="col-type">Type</th><th class="col-red">Rédacteur</th><th class="col-statut">Statut</th><th style="text-align:right">Actions</th>
    </tr></thead>
    <tbody>
    @forelse($crs as $cr)
    @php
        $formel = $cr->estReviewV1();
        $lien = $cr->estScrum() && !$formel ? $cr->lienScrum() : route('comptes-rendus.show', $cr);
        $gestionnaire = $cr->sprint && $user->peutGererProjet($cr->sprint->projet_id);
        $editable = !$cr->estScrum() && ($cr->estBrouillon() ? ($user->estPilote() || $cr->redacteur_id === $user->id) : $user->estAdmin());
        if ($formel) $editable = $cr->estConfirme() ? $user->estAdmin() : ($user->estAdmin() || $gestionnaire || (!$cr->estEnRevision() && $cr->redacteur_id === $user->id));
        $contexte = $cr->sprint ? ($cr->sprint->projet?->libelle . ' · Sprint ' . $cr->sprint->numero) : ($cr->lieu ?: null);
    @endphp
    <tr>
        <td class="date">{{ $cr->date_reunion->format('d/m/Y') }}<small>{{ ucfirst($cr->date_reunion->translatedFormat('l')) }}</small></td>
        <td class="titre"><a href="{{ $lien }}">{{ $cr->titre }}</a>@if($contexte)<small>{{ $contexte }}</small>@endif</td>
        <td class="col-type"><span class="cr-type {{ $cr->estReunion() ? 'reunion' : '' }}">{{ $formel ? 'CR Review sprint' : $cr->typeCourt() }}</span></td>
        <td class="col-red">{{ $cr->redacteur?->nomComplet() ?? '-' }}</td>
        <td class="col-statut"><span class="cr-statut {{ $cr->statut }}"><i></i>{{ $statuts[$cr->statut] ?? ucfirst($cr->statut) }}</span></td>
        <td>
            <div class="cr-acts">
                <a href="{{ $lien }}" title="Consulter">{!! $icVoir !!}</a>
                @if($editable)
                <a href="{{ $formel ? route('comptes-rendus.review-sprint.edit', $cr) : route('comptes-rendus.edit', $cr) }}" title="{{ $cr->estConfirme() ? 'Modifier (réservé aux pilotes)' : 'Éditer' }}">{!! $icEditer !!}</a>
                @endif
                @if($cr->estConfirme() && (!$cr->estScrum() || $formel))
                <a href="{{ route('comptes-rendus.telecharger', $cr) }}" title="Télécharger le DOCX">{!! $icDocx !!}</a>
                @endif
                @if($cr->estScrum() && !$formel && $cr->sprint_id)
                <a href="{{ route('scrum.archive', $cr->sprint_id) }}" title="Archive Scrum du sprint (Excel)">{!! $icExcel !!}</a>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="6" class="empty-state">Aucun compte rendu pour le moment.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

{{ $crs->links() }}
@endsection
