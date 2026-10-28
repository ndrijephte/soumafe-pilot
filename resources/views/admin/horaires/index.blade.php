@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Horaires de travail')
@push('styles')
<style>
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord);vertical-align:top}
.mini-table tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;display:inline-block;white-space:nowrap}
.b-ok{background:#dcfce7;color:var(--ok)}
.b-info{background:#dbeafe;color:#1d4ed8}
.b-gris{background:#e2e8f0;color:#475569}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.hor-actuel{display:flex;gap:22px;flex-wrap:wrap;align-items:center;border:1.5px solid #93c5fd;background:#f8fbff;border-radius:12px;padding:14px 18px;margin-bottom:16px}
.hor-actuel .lab{font-size:10.5px;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;font-weight:600}
.hor-actuel .val{font-size:15px;color:var(--bleu);font-weight:600;margin-top:2px}
.hor-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 16px}
.hor-form label{display:block;font-size:11.5px;color:var(--gris);font-weight:600;margin-bottom:4px}
.hor-form input[type=date],.hor-form input[type=time],.hor-form input[type=text]{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;box-sizing:border-box}
.hor-jours{display:flex;gap:6px;flex-wrap:wrap}
.hor-jours label{display:flex;align-items:center;gap:5px;border:1px solid var(--bord);border-radius:8px;padding:6px 10px;font-size:12.5px;color:var(--texte);font-weight:500;margin:0;cursor:pointer}
.hor-jours input:checked + span{color:var(--accent);font-weight:600}
.aide{font-size:11.5px;color:var(--gris);margin-top:10px}
@media(max-width:900px){.hor-form{grid-template-columns:1fr}.mini-table{display:block;overflow-x:auto}}
</style>
@endpush
@section('contenu')
<h2>Horaires et jours de travail</h2>
<div class="crumb">Administration &rsaquo; Référentiels &rsaquo; Horaires de travail</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<div class="hor-actuel">
    <div><div class="lab">En vigueur depuis</div><div class="val">{{ $enVigueur->date_effet->year <= 2000 ? 'Origine' : $enVigueur->date_effet->format('d/m/Y') }}</div></div>
    <div><div class="lab">Jours travaillés</div><div class="val">{{ $enVigueur->joursLibelle() }}</div></div>
    <div><div class="lab">Horaires</div><div class="val">{{ $enVigueur->arrivee() }} → {{ $enVigueur->depart() }}</div></div>
    <div><div class="lab">Durée / jour</div><div class="val">{{ str_replace('.', ',', $enVigueur->dureeJour()) }} h</div></div>
</div>

<div class="carte" style="margin-bottom:16px">
    <h3 style="margin:0 0 12px;font-size:14px;color:var(--bleu)">Nouvelle règle</h3>
    <form method="POST" action="{{ route('admin.horaires.store') }}">
        @csrf
        <div class="hor-form">
            <div>
                <label>Applicable à partir du</label>
                <input type="date" name="date_effet" required min="{{ today()->toDateString() }}" value="{{ old('date_effet', today()->addDay()->toDateString()) }}">
            </div>
            <div>
                <label>Heure de début (retard au-delà)</label>
                <input type="time" name="heure_arrivee" required value="{{ old('heure_arrivee', $enVigueur->arrivee()) }}">
            </div>
            <div>
                <label>Heure de fin (départ anticipé avant)</label>
                <input type="time" name="heure_depart" required value="{{ old('heure_depart', $enVigueur->depart()) }}">
            </div>
            <div style="grid-column:1/-1">
                <label>Jours travaillés</label>
                @php $coches = array_map('intval', old('jours', $enVigueur->joursTravailles())); @endphp
                <div class="hor-jours">
                    @foreach(\App\Models\HoraireTravail::JOURS as $n => $nom)
                    <label><input type="checkbox" name="jours[]" value="{{ $n }}" @checked(in_array($n, $coches, true))><span>{{ $nom }}</span></label>
                    @endforeach
                </div>
            </div>
            <div style="grid-column:1/-1">
                <label>Motif du changement</label>
                <input type="text" name="motif" required maxlength="255" value="{{ old('motif') }}" placeholder="Ex. : horaires du Ramadan, note de service n°…">
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px"><button class="btn">Enregistrer</button></div>
    </form>
    <div class="aide">
        La règle s'applique à partir de sa date d'effet, sans effet rétroactif : pointages, jours ouvrés, retards et statistiques
        des périodes passées restent calculés avec les horaires de l'époque. Les jours fériés et les jours ouvrables spéciaux s'ajoutent à ces règles.
    </div>
</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="mini-table">
<thead><tr><th>Période d'application</th><th>Jours travaillés</th><th>Horaires</th><th>Durée / jour</th><th>Motif</th><th>Enregistré par</th><th>Statut</th><th></th></tr></thead>
<tbody>
@foreach($regles as $i => $h)
@php
    $suivante = $regles[$i - 1] ?? null;
    $statut = $h->date_effet->isFuture() ? ['À venir', 'b-info'] : ($h->is($enVigueur) ? ['En vigueur', 'b-ok'] : ['Historique', 'b-gris']);
@endphp
<tr>
    <td style="white-space:nowrap">{{ $h->date_effet->year <= 2000 ? 'Origine' : 'Du ' . $h->date_effet->format('d/m/Y') }}{{ $suivante ? ' au ' . $suivante->date_effet->copy()->subDay()->format('d/m/Y') : '' }}</td>
    <td>{{ $h->joursLibelle() }}</td>
    <td style="white-space:nowrap">{{ $h->arrivee() }} → {{ $h->depart() }}</td>
    <td>{{ str_replace('.', ',', $h->dureeJour()) }} h</td>
    <td>{{ $h->motif ?? '-' }}</td>
    <td style="white-space:nowrap">{{ $h->createur?->nomComplet() ?? '-' }}@if($h->createur)<br><span style="font-size:11px;color:var(--gris)">{{ $h->updated_at?->format('d/m/Y H:i') }}</span>@endif</td>
    <td><span class="badge {{ $statut[1] }}">{{ $statut[0] }}</span></td>
    <td>
        @if($h->date_effet->isFuture())
        <form method="POST" action="{{ route('admin.horaires.destroy', $h) }}" onsubmit="return confirm('Annuler cette règle à venir ?')">@csrf @method('DELETE')
            <button class="btn sm danger">Annuler</button>
        </form>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
