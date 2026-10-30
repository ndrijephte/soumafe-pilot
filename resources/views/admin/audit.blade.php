@extends('layouts.app')
@section('sousTitre', 'Journal d\'audit')
@section('titre', 'Journal d\'audit')
@push('styles')
<style>
.audit-filtres{display:grid;grid-template-columns:repeat(4,minmax(0,1fr)) auto;gap:10px 12px;align-items:end;margin-bottom:16px;padding:14px;background:var(--carte);border:1px solid var(--bord);border-radius:12px}
.audit-filtres .af-actions{display:flex;gap:8px}
.audit-filtres label{display:block;font-size:11px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:3px}
.audit-filtres select,.audit-filtres input{width:100%;box-sizing:border-box;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;min-width:0;background:#fff}
.audit-filtres select:focus,.audit-filtres input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.audit-filtres .btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;text-align:center;white-space:nowrap}
.audit-filtres .btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.audit-tbl{width:100%;border-collapse:collapse;font-size:13px}
.audit-tbl th{text-align:left;color:var(--gris);font-weight:600;padding:8px 10px;border-bottom:2px solid var(--bord);font-size:11.5px;text-transform:uppercase;white-space:nowrap}
.audit-tbl td{padding:8px 10px;border-bottom:1px solid var(--bord);vertical-align:top}
.audit-tbl tr:hover{background:#f1f5f9}
.audit-tbl .td-details{max-width:280px;word-break:break-word;font-size:12px;color:#666}
.badge-action{display:inline-block;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600}
.badge-action.creation{background:#dcfce7;color:#166534}
.badge-action.modification{background:#dbeafe;color:#1e40af}
.badge-action.suppression{background:#fee2e2;color:#991b1b}
.badge-action.connexion{background:#f3e8ff;color:#6b21a8}
.badge-action.deconnexion{background:#fef3c7;color:#92400e}
.badge-action.validation{background:#d1fae5;color:#065f46}
.badge-action.affectation{background:#e0e7ff;color:#3730a3}
.badge-action.detachement{background:#fce7f3;color:#9d174d}
.badge-action.annulation{background:#fef2f2;color:#991b1b}
.audit-pag{display:flex;justify-content:center;gap:4px;margin-top:16px}
.audit-pag a,.audit-pag span{padding:6px 12px;border:1px solid var(--bord);border-radius:8px;font-size:12px;text-decoration:none;color:var(--texte)}
.audit-pag .active span{background:var(--accent);color:#fff;border-color:var(--accent)}
.audit-pag .disabled span{opacity:.4}
.audit-vide{text-align:center;padding:40px;color:var(--gris);font-size:14px}
@media(max-width:900px){
    .audit-filtres{grid-template-columns:repeat(2,minmax(0,1fr))}
    .audit-filtres .af-actions{grid-column:1/-1}
    .audit-filtres .af-actions .btn{flex:1}
}
@media(max-width:480px){
    .audit-filtres .af-large{grid-column:1/-1}
}
@media(max-width:600px){
    .audit-tbl .col-ip{display:none}
    .audit-tbl .td-details{max-width:160px;font-size:11px}
    .audit-tbl td,.audit-tbl th{padding:6px 6px;font-size:11.5px}
    .audit-pag a,.audit-pag span{padding:4px 8px;font-size:11px}
}
</style>
@endpush
@section('contenu')
@php
$ic = fn(string $d, int $s=17) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
@endphp

<div style="display:flex;align-items:center;gap:10px;margin-bottom:16px">
    {!! $ic('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>', 22) !!}
    <h2 style="margin:0;font-size:18px;color:var(--bleu)">Journal d'audit</h2>
    <span style="font-size:12px;color:var(--gris);margin-left:auto">{{ $logs->total() }} entrée(s)</span>
</div>

<form method="GET" action="{{ route('admin.audit.index') }}" class="audit-filtres">
    <div class="af-large">
        <label>Module</label>
        <select name="module">
            <option value="">Tous</option>
            @foreach($modules as $m)
            <option value="{{ $m }}" {{ request('module') === $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
            @endforeach
        </select>
    </div>
    <div class="af-large">
        <label>Action</label>
        <select name="action">
            <option value="">Toutes</option>
            @foreach($actions as $a)
            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Du</label>
        <input type="date" name="date_debut" value="{{ request('date_debut') }}">
    </div>
    <div>
        <label>Au</label>
        <input type="date" name="date_fin" value="{{ request('date_fin') }}">
    </div>
    <div class="af-actions">
        <button type="submit" class="btn">Filtrer</button>
        @if(request()->hasAny(['module','action','date_debut','date_fin']))
        <a href="{{ route('admin.audit.index') }}" class="btn sec">Effacer</a>
        @endif
    </div>
</form>

@if($logs->isEmpty())
<div class="audit-vide">
    {!! $ic('<circle cx="12" cy="12" r="10"/><path d="M12 8v4l2 2"/>', 40) !!}
    <p style="margin-top:8px">Aucune entrée d'audit trouvée</p>
</div>
@else
<div style="overflow-x:auto">
<table class="audit-tbl">
    <thead>
        <tr>
            <th>Date / Heure</th>
            <th>Utilisateur</th>
            <th>Action</th>
            <th>Module</th>
            <th>Cible</th>
            <th>Détails</th>
            <th class="col-ip">IP</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $log)
        <tr>
            <td style="white-space:nowrap;font-size:12px">{{ $log->created_at?->format('d/m/Y H:i') }}</td>
            <td>{{ $log->user_name }}</td>
            <td><span class="badge-action {{ $log->action }}">{{ ucfirst($log->action) }}</span></td>
            <td>{{ ucfirst($log->module) }}</td>
            <td style="font-size:12px">{{ $log->cible }}</td>
            <td class="td-details">{{ $log->details }}</td>
            <td class="col-ip" style="font-size:11px;color:var(--gris)">{{ $log->ip_address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</div>

<div class="audit-pag">
    {{ $logs->links() }}
</div>
@endif
@endsection
