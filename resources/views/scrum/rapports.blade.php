@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Rapports Daily Scrum')

@push('styles')
<style>
.filtre{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:14px}
.filtre label{display:block;font-size:11px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:3px}
.filtre select,.filtre input{height:34px;padding:5px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;background:#fff;box-sizing:border-box}
.col-dt{width:13%}.col-pj{width:21%}.col-sp{width:8%}.col-dl{width:14%}.col-sc{width:17%}.col-st{width:11%}.col-pa{width:9%}.col-df{width:7%}
.vide{text-align:center;color:var(--gris);padding:30px}
</style>
@endpush

@section('contenu')
@include('scrum._styles')
<h2>Rapports Daily Scrum</h2>
<div class="crumb" style="margin-bottom:12px">Rapports de Daily Scrum publiés, tous projets confondus.</div>

<form method="GET" class="filtre">
    <div><label>Projet</label><select name="projet_id" onchange="this.form.submit()"><option value="">Tous les projets</option>@foreach($projets as $pj)<option value="{{ $pj->id }}" @selected(request('projet_id') == $pj->id)>{{ $pj->libelle }}</option>@endforeach</select></div>
    <div><label>Du</label><input type="date" name="du" value="{{ request('du') }}"></div>
    <div><label>Au</label><input type="date" name="au" value="{{ request('au') }}"></div>
    <button class="btn sm" style="height:34px">Filtrer</button>
    @if(request()->hasAny(['projet_id', 'du', 'au']))<a href="{{ route('scrum.rapports') }}" class="btn sm sec" style="height:34px">Réinitialiser</a>@endif
</form>

<div class="panneau">
<h3><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg> Liste des rapports <span class="h3-info">{{ $dailies->total() }} rapport(s)</span></h3>
<table class="sc-tbl">
    <thead><tr><th class="col-dt">Date</th><th class="col-pj">Projet</th><th class="col-sp ctr">Sprint</th><th class="col-dl">Réunion</th><th class="col-sc">Rédacteur (scribe)</th><th class="col-st ctr">Statut</th><th class="col-pa ctr">Participants</th><th class="col-df ctr">Défis</th></tr></thead>
    <tbody>
    @forelse($dailies as $d)
        <tr style="cursor:pointer" onclick="location.href='{{ route('scrum.daily', [$d->sprint_id, $d]) }}'">
            <td>{{ ucfirst($d->date_reunion->translatedFormat('D d/m/Y')) }}</td>
            <td class="nom">{{ $d->sprint?->projet?->libelle ?? '—' }}</td>
            <td class="ctr">{{ $d->sprint?->numero }}</td>
            <td><a href="{{ route('scrum.daily', [$d->sprint_id, $d]) }}" style="color:var(--accent);font-weight:600;text-decoration:none">Daily - jour {{ $d->numero_jour }}</a></td>
            <td>{{ $scribes[$d->roleUserId('scribe')]?->nomComplet() ?? '—' }}</td>
            @php [$libS, $clsS] = $d->statutPublication(); @endphp
            <td class="ctr"><span class="pill {{ $clsS }}" @if($d->estPublie()) title="Publié le {{ $d->publie_le->format('d/m/Y') }}" @endif>{{ $libS }}</span></td>
            <td class="ctr">{{ $d->entrees_count }}</td>
            <td class="ctr">@if($d->defis_count)<span class="pill p-warn">{{ $d->defis_count }}</span>@else - @endif</td>
        </tr>
    @empty
        <tr><td colspan="8" class="vide">Aucun Daily Scrum enregistré.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $dailies->links() }}</div>
@endsection
