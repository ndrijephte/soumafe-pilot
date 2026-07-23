@extends('layouts.app')
@section('titre', $registre->libelle)
@push('styles')
<style>
.reg-meta{font-size:13px;color:var(--gris);margin-bottom:16px;line-height:1.8}
.reg-meta strong{color:#1e293b}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-quot{background:#dbeafe;color:#1d4ed8}.b-hebd{background:#dcfce7;color:var(--ok)}.b-mens{background:#fef3c7;color:#b45309}
.tbl{width:100%;border-collapse:collapse;font-size:12.5px}
.tbl th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;position:sticky;top:0}
.tbl td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.tbl tr:hover{background:#f8fafc}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11px}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:520px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:600;text-transform:uppercase}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.req{color:var(--ko)}
.pagination{display:flex;gap:4px;margin-top:12px;justify-content:center}
.pagination a,.pagination span{padding:4px 10px;border-radius:6px;font-size:12px;text-decoration:none;border:1px solid var(--bord)}
.pagination .active{background:var(--accent);color:#fff;border-color:var(--accent)}
</style>
@endpush
@section('contenu')
@php $pBadge = match($registre->periodicite) { 'quotidien' => 'b-quot', 'hebdomadaire' => 'b-hebd', default => 'b-mens' }; @endphp

<h2>{{ $registre->libelle }}</h2>
<div class="crumb"><a href="{{ route('registres.index') }}" style="color:var(--accent);text-decoration:none">Registres</a> &rsaquo; {{ $registre->libelle }}</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

<div class="carte" style="margin-bottom:14px">
    <div class="reg-meta">
        @if($registre->description)<div>{{ $registre->description }}</div>@endif
        <div><strong>P&eacute;riodicit&eacute; :</strong> <span class="badge {{ $pBadge }}">{{ ucfirst($registre->periodicite) }}</span></div>
        <div><strong>Colonnes :</strong>
            @foreach($registre->definition_colonnes as $col)
            <span style="background:#f1f5f9;padding:2px 8px;border-radius:6px;font-size:11px;margin-right:4px">{{ $col['nom'] }} <span style="color:var(--gris)">({{ $col['type'] }})</span></span>
            @endforeach
        </div>
    </div>
</div>

<div style="margin-bottom:14px">
    <button class="btn" onclick="document.getElementById('dlg-entree').showModal()">+ Nouvelle entr&eacute;e</button>
</div>

<div class="carte" style="padding:0;overflow-x:auto">
@if($entrees->count() > 0)
<table class="tbl">
<thead>
    <tr>
        <th>Date</th>
        <th>Saisi par</th>
        @foreach($registre->definition_colonnes as $col)
        <th>{{ $col['nom'] }}</th>
        @endforeach
        <th></th>
    </tr>
</thead>
<tbody>
@foreach($entrees as $e)
<tr>
    <td style="font-size:12px;white-space:nowrap">{{ $e->date->format('d/m/Y') }}</td>
    <td style="font-size:12px">{{ $e->user?->nomComplet() ?? '—' }}</td>
    @foreach($registre->definition_colonnes as $i => $col)
    <td>
        @php $v = $e->valeurs[$col['nom']] ?? ($e->valeurs[$i] ?? '—'); @endphp
        @if($col['type'] === 'oui_non')
            @if($v === '1' || $v === 'oui' || $v === true)<span style="color:var(--ok)">Oui</span>@else<span style="color:var(--gris)">Non</span>@endif
        @else
            {{ $v }}
        @endif
    </td>
    @endforeach
    <td>
        <form method="POST" action="{{ route('registres.entrees.destroy', $e) }}" onsubmit="return confirm('Supprimer cette entrée ?')" style="display:inline">@csrf <input type="hidden" name="_method" value="DELETE"><button class="btn sm danger">&times;</button></form>
    </td>
</tr>
@endforeach
</tbody>
</table>
@if($entrees->hasPages())
<div class="pagination">{{ $entrees->links('pagination::simple-default') }}</div>
@endif
@else
<div style="text-align:center;color:var(--gris);padding:30px;font-size:13px">Aucune entr&eacute;e dans ce registre.</div>
@endif
</div>

{{-- Dialog nouvelle entrée --}}
<dialog id="dlg-entree">
    <h3 style="color:var(--bleu);margin-bottom:4px">Nouvelle entr&eacute;e</h3>
    <form method="POST" action="{{ route('registres.entrees.store', $registre) }}">@csrf
        <label>Date <span class="req">*</span></label>
        <input type="date" name="date" value="{{ today()->format('Y-m-d') }}" required>
        @foreach($registre->definition_colonnes as $col)
        <label>{{ $col['nom'] }}</label>
        @if($col['type'] === 'nombre')
        <input type="number" name="valeurs[{{ $col['nom'] }}]" step="any" placeholder="0">
        @elseif($col['type'] === 'date')
        <input type="date" name="valeurs[{{ $col['nom'] }}]">
        @elseif($col['type'] === 'oui_non')
        <select name="valeurs[{{ $col['nom'] }}]"><option value="non">Non</option><option value="oui">Oui</option></select>
        @else
        <input type="text" name="valeurs[{{ $col['nom'] }}]" placeholder="{{ $col['nom'] }}">
        @endif
        @endforeach
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endsection
