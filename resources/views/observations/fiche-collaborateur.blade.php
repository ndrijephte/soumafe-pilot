@extends('layouts.app')
@section('sousTitre', 'Fiche Collaborateur')
@section('titre', 'Fiche Collaborateur')
@push('styles')
<style>
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:16px}
.panneau h3{font-size:14px;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.filtre-bar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.filtre-bar select,.filtre-bar input{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:16px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:12px;text-align:center}
.kpi .val{font-size:22px;font-weight:700;color:var(--bleu)}
.kpi .lbl{font-size:10px;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;margin-top:2px}
.mini-table{width:100%;border-collapse:collapse;font-size:12px}
.mini-table th{text-align:left;padding:7px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:6px 10px;border-bottom:1px solid var(--bord)}
.mini-table tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;display:inline-block}
.b-ok{background:#dcfce7;color:var(--ok)}
.b-warn{background:#fef3c7;color:#b45309}
.b-ko{background:#fee2e2;color:var(--ko)}
.b-info{background:#dbeafe;color:#1d4ed8}
.b-gris{background:#e2e8f0;color:#475569}
.profil-card{display:flex;gap:16px;align-items:center;margin-bottom:16px;background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px}
.profil-card .avatar{width:48px;height:48px;border-radius:50%;background:var(--bleu);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700}
.profil-card .info h3{margin:0;font-size:16px;color:var(--bleu)}
.profil-card .info p{margin:2px 0 0;font-size:12px;color:var(--gris)}
.section-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:900px){.section-grid{grid-template-columns:1fr}.kpis{grid-template-columns:repeat(3,1fr)}}
@media(max-width:600px){.kpis{grid-template-columns:repeat(2,1fr)}}
</style>
@endpush
@section('contenu')
@php
    $ic = fn(string $d, int $s=16) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
    $icUser = $ic('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>');
    $icTask = $ic('<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>');
    $icTarget = $ic('<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>');
    $icBug = $ic('<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/>');
    $icClock = $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>');
    $icBack = $ic('<line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>');

    $badgeStatut = fn($s) => match($s) {
        'a_faire'   => 'b-info',
        'en_cours'  => 'b-warn',
        'termine'   => 'b-ok',
        'bloque'    => 'b-ko',
        'a_valider' => 'b-warn',
        'reporte'   => 'b-gris',
        default     => 'b-gris',
    };
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px">
    <h2>{!! $icUser !!} Fiche Collaborateur</h2>
    <a href="{{ route('observations.index') }}" class="btn sec sm" style="display:flex;align-items:center;gap:4px">{!! $icBack !!} Retour</a>
</div>
<div class="crumb">Historique d'activité sur une période donnée</div>

{{-- Filtre sélection --}}
<div class="panneau">
    <form method="GET" action="{{ route('observations.fiche-collaborateur') }}" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap">
        <div>
            <label style="font-size:11px;color:var(--gris);display:block;margin-bottom:3px">Collaborateur</label>
            <select name="collaborateur_id" required style="padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;min-width:200px">
                <option value="">— Sélectionner —</option>
                @foreach($collaborateurs as $c)
                <option value="{{ $c->id }}" {{ request('collaborateur_id') == $c->id ? 'selected' : '' }}>{{ $c->nomComplet() }} — {{ $c->roleLibelle() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label style="font-size:11px;color:var(--gris);display:block;margin-bottom:3px">Du</label>
            <input type="date" name="debut" value="{{ $debut }}" style="padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px">
        </div>
        <div>
            <label style="font-size:11px;color:var(--gris);display:block;margin-bottom:3px">Au</label>
            <input type="date" name="fin" value="{{ $fin }}" style="padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px">
        </div>
        <button class="btn" style="height:36px">Consulter</button>
    </form>
</div>

@if($collab && $data)
{{-- Profil --}}
<div class="profil-card">
    <div class="avatar">{{ strtoupper(substr($collab->prenom ?? $collab->name, 0, 1)) }}{{ strtoupper(substr($collab->name, 0, 1)) }}</div>
    <div class="info">
        <h3>{{ $collab->nomComplet() }}</h3>
        <p>{{ $collab->roleLibelle() }} · {{ $collab->email }}</p>
    </div>
</div>

{{-- KPIs résumé --}}
@php $s = $data['stats']; @endphp
<div class="kpis">
    <div class="kpi"><div class="val">{{ $s['taches_total'] }}</div><div class="lbl">Tâches</div></div>
    <div class="kpi"><div class="val" style="color:var(--ok)">{{ $s['taux_completion'] }}%</div><div class="lbl">Complétion</div></div>
    <div class="kpi"><div class="val" style="color:var(--ko)">{{ $s['taches_retard'] }}</div><div class="lbl">En retard</div></div>
    <div class="kpi"><div class="val" style="color:var(--accent)">{{ $s['productivite'] }}</div><div class="lbl">Productivité/j</div></div>
    <div class="kpi"><div class="val" style="color:var(--accent)">{{ $s['obj_score'] }}%</div><div class="lbl">Score OKR</div></div>
    <div class="kpi"><div class="val">{{ $s['kr_atteints'] }}/{{ $s['kr_total'] }}</div><div class="lbl">KR atteints</div></div>
    <div class="kpi"><div class="val" style="color:var(--ok)">{{ $s['jours_presents'] }}</div><div class="lbl">Jours présent</div></div>
    <div class="kpi"><div class="val" style="color:var(--ko)">{{ $s['retards'] }}</div><div class="lbl">Retards</div></div>
    <div class="kpi"><div class="val">{{ $s['obs_total'] }}</div><div class="lbl">Observations</div></div>
    <div class="kpi"><div class="val">{{ $s['reunions_total'] }}</div><div class="lbl">Réunions</div></div>
</div>

<div class="section-grid">
{{-- Tâches --}}
<div class="panneau" style="overflow-x:auto">
    <h3>{!! $icTask !!} Tâches ({{ $data['taches']->count() }})</h3>
    @if($data['taches']->isEmpty())
        <p style="color:var(--gris);font-size:12px;text-align:center;padding:10px 0">Aucune tâche sur cette période.</p>
    @else
    <table class="mini-table">
        <thead><tr><th>Intitulé</th><th>Projet</th><th>Échéance</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach($data['taches'] as $t)
        <tr>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $t->intitule }}">{{ $t->intitule }}</td>
            <td style="font-size:11px">{{ $t->projet?->code ?? '—' }}</td>
            <td style="font-size:11px;white-space:nowrap">{{ $t->echeance?->format('d/m/Y') ?? '—' }}</td>
            <td><span class="badge {{ $badgeStatut($t->statut) }}">{{ $t->labelStatut() }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Objectifs --}}
<div class="panneau" style="overflow-x:auto">
    <h3>{!! $icTarget !!} Objectifs ({{ $data['objectifs']->count() }})</h3>
    @if($data['objectifs']->isEmpty())
        <p style="color:var(--gris);font-size:12px;text-align:center;padding:10px 0">Aucun objectif sur cette période.</p>
    @else
    <table class="mini-table">
        <thead><tr><th>Libellé</th><th>Projet</th><th>Pond.</th><th>KR</th><th>Verdict</th></tr></thead>
        <tbody>
        @foreach($data['objectifs'] as $obj)
        @php
            $verdict = $obj->evaluerAuto();
            $badgeV = match($verdict) { 'atteint' => 'b-ok', 'partiel' => 'b-warn', 'non_atteint' => 'b-ko', default => 'b-gris' };
            $labelV = match($verdict) { 'atteint' => 'Atteint', 'partiel' => 'Partiel', 'non_atteint' => 'Non atteint', default => 'En cours' };
            $krCount = $obj->resultats->count();
            $krOk = $obj->resultats->where('valide', true)->count();
        @endphp
        <tr>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $obj->libelle }}">{{ $obj->libelle }}</td>
            <td style="font-size:11px">{{ $obj->projet?->code ?? '—' }}</td>
            <td style="text-align:center;font-size:11px;font-weight:600">{{ $obj->ponderation }}%</td>
            <td style="text-align:center;font-size:11px">@if($krCount) {{ $krOk }}/{{ $krCount }} @else — @endif</td>
            <td><span class="badge {{ $badgeV }}">{{ $labelV }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Observations assignées --}}
<div class="panneau" style="overflow-x:auto">
    <h3>{!! $icBug !!} Observations assignées ({{ $data['observations']->count() }})</h3>
    @if($data['observations']->isEmpty())
        <p style="color:var(--gris);font-size:12px;text-align:center;padding:10px 0">Aucune observation sur cette période.</p>
    @else
    <table class="mini-table">
        <thead><tr><th>Code</th><th>Projet</th><th>Description</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach($data['observations'] as $obs)
        <tr>
            <td style="font-family:monospace;font-size:11px;font-weight:700;color:var(--accent)">{{ $obs->code }}</td>
            <td style="font-size:11px">{{ $obs->projet?->code ?? '—' }}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $obs->description }}">{{ $obs->description }}</td>
            <td><span class="badge {{ match($obs->statut) { 'resolu' => 'b-ok', 'en_cours' => 'b-warn', 'bloque' => 'b-ko', default => 'b-info' } }}">{{ $obs->statutLibelle() }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Pointage --}}
<div class="panneau" style="overflow-x:auto">
    <h3>{!! $icClock !!} Pointage ({{ $data['pointages']->count() }} jours)</h3>
    @if($data['pointages']->isEmpty())
        <p style="color:var(--gris);font-size:12px;text-align:center;padding:10px 0">Aucun pointage sur cette période.</p>
    @else
    <table class="mini-table">
        <thead><tr><th>Date</th><th>Arrivée</th><th>Départ</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach($data['pointages']->take(20) as $pt)
        <tr>
            <td style="font-size:11px;white-space:nowrap">{{ \Carbon\Carbon::parse($pt->date)->format('d/m/Y') }}</td>
            <td style="font-size:11px">{{ $pt->heure_arrivee ? \Carbon\Carbon::parse($pt->heure_arrivee)->format('H:i') : '—' }}</td>
            <td style="font-size:11px">{{ $pt->heure_depart ? \Carbon\Carbon::parse($pt->heure_depart)->format('H:i') : '—' }}</td>
            <td><span class="badge {{ match($pt->statut) { 'present' => 'b-ok', 'retard' => 'b-warn', 'absent' => 'b-ko', 'sorti_tot' => 'b-ok', default => 'b-gris' } }}">{{ ucfirst(str_replace('_',' ',$pt->statut)) }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @if($data['pointages']->count() > 20)
    <p style="color:var(--gris);font-size:11px;text-align:center;margin-top:6px">+ {{ $data['pointages']->count() - 20 }} autres jours</p>
    @endif
    @endif
</div>

{{-- Planning réunions --}}
<div class="panneau" style="overflow-x:auto">
    <h3>{!! $ic('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!} Réunions ({{ $data['reunions']->count() }})</h3>
    @if($data['reunions']->isEmpty())
        <p style="color:var(--gris);font-size:12px;text-align:center;padding:10px 0">Aucune réunion sur cette période.</p>
    @else
    <table class="mini-table">
        <thead><tr><th>Date</th><th>Type</th><th>Rôle</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach($data['reunions']->take(20) as $reu)
        <tr>
            <td style="font-size:11px;white-space:nowrap">{{ $reu->date_reunion->format('d/m/Y') }}</td>
            <td style="font-size:11px">{{ $reu->typeReunion?->libelle ?? '—' }}</td>
            <td style="font-size:11px">{{ $reu->role?->libelle ?? '—' }}</td>
            <td><span class="badge {{ match($reu->statut) { 'present' => 'b-ok', 'absent' => 'b-ko', 'remplace' => 'b-warn', default => 'b-info' } }}">{{ $reu->statutLabel() }}</span></td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @if($data['reunions']->count() > 20)
    <p style="color:var(--gris);font-size:11px;text-align:center;margin-top:6px">+ {{ $data['reunions']->count() - 20 }} autres</p>
    @endif
    @endif
</div>
</div>
@endif
@endsection
