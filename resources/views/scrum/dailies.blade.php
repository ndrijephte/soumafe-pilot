@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Daily Scrum - Sprint ' . $sprint->numero . ' - ' . $sprint->projet->libelle)

@push('styles')
<style>
.cal{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
.cal-entete{font-size:11px;font-weight:700;color:var(--gris);text-transform:uppercase;text-align:center;padding-bottom:2px}
.jour{background:#fff;border:1px solid var(--bord);border-radius:10px;padding:9px 10px;min-height:96px;display:flex;flex-direction:column;gap:3px;font-size:12px;color:inherit;text-decoration:none;position:relative}
.jour .num{font-size:11px;font-weight:700;color:var(--gris);text-transform:uppercase}
.jour .dt{font-size:13px;font-weight:700;color:#1e293b}
.jour.fait{border-color:#93c5fd;background:#f8fbff}
.jour.fait:hover{border-color:var(--accent);box-shadow:0 2px 8px rgba(46,116,181,.14)}
.jour.fait .num{color:var(--accent)}
.jour.auj{outline:2px solid var(--warn);outline-offset:-1px}
.jour.futur{opacity:.6}
.jour .roles{font-size:11px;color:var(--gris);line-height:1.4}
.jour .roles b{color:#334155;font-weight:600}
.jour .bas{margin-top:auto;display:flex;gap:4px;flex-wrap:wrap;align-items:center}
.jour form{margin:0}
.creer{display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap}
.creer .champ{min-width:150px}
.equipe-liste{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
.equipe-liste span{background:#f1f5f9;border-radius:14px;padding:3px 10px;font-size:12px}
.prog{display:flex;align-items:center;gap:10px;flex-wrap:wrap;font-size:12.5px;color:#334155}
.prog b{color:#2e74b5;font-weight:600}
.prog .sep{color:#cbd5e1}
dialog.dlg{border:none;border-radius:14px;padding:22px;width:520px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog.dlg::backdrop{background:rgba(22,56,92,.35)}
.parts{max-height:220px;overflow-y:auto;border:1px solid var(--bord);border-radius:8px;padding:6px 10px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2px 10px}
.parts label{display:flex;align-items:center;gap:6px;font-size:12.5px;cursor:pointer;text-transform:none;font-weight:400;color:#1e293b;margin:0;padding:3px 0}
.parts input{width:auto}
.jours-choix{display:flex;gap:6px;flex-wrap:wrap}
.jours-choix label{display:flex;align-items:center;gap:5px;border:1px solid var(--bord);border-radius:8px;padding:6px 10px;font-size:12.5px;cursor:pointer;text-transform:none;font-weight:500;color:#1e293b;margin:0}
.jours-choix input{width:auto}
.grille3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:12px}
@media(max-width:800px){.cal{grid-template-columns:repeat(2,1fr)}.cal-entete{display:none}}
</style>
@endpush

@section('contenu')
@include('scrum._entete', ['sprint' => $sprint, 'actif' => 'daily', 'page' => 'Daily Scrum'])

@php $libJours = collect(['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi']); @endphp
<div style="display:grid;grid-template-columns:minmax(0,1fr);gap:14px">
@if($peutCreer && $dateProposee)
<div class="carte">
    <div style="font-weight:700;color:var(--bleu);margin-bottom:4px">Nouveau Daily Scrum</div>
    <div style="font-size:12px;color:var(--gris);margin-bottom:10px">
        Lignes pré-remplies depuis la planification : tâches d'hier, du jour, durée restante et blocages.
    </div>
    <form method="POST" action="{{ route('scrum.daily.store', $sprint) }}" class="creer">@csrf
        <div class="champ"><label>Date</label>
            <select name="date_reunion" required>
                @foreach($jours as $j)
                    @unless($j['daily'])<option value="{{ $j['date']->toDateString() }}" @selected($j['date']->eq($dateProposee))>Jour {{ $j['numero'] }} - {{ ucfirst($j['date']->translatedFormat('l d/m/Y')) }}</option>@endunless
                @endforeach
            </select>
        </div>
        <div class="champ"><label>Heure début</label><input type="time" name="heure_debut" value="{{ old('heure_debut', $heureDefaut) }}"></div>
        <div class="champ"><label>Heure fin</label><input type="time" name="heure_fin" value="{{ old('heure_fin', $config['heure_fin']) }}"></div>
        <button class="btn">Créer et pré-remplir</button>
    </form>
</div>
@endif

<div class="carte">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div style="font-weight:700;color:var(--bleu)"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-3px;margin-right:4px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Calendrier du sprint <span style="font-weight:400;color:var(--gris);font-size:12.5px">- {{ collect($jours)->whereNotNull('daily')->count() }} Daily(s) sur {{ count($jours) }} jour(s) ouvré(s)</span></div>
        @if($peutCreer)<button type="button" class="btn sm sec" onclick="document.getElementById('dlg-hs').showModal()">+ Daily hors sprint</button>@endif
    </div>
    @php
        $nom = fn($id) => $id && isset($users[$id]) ? $users[$id]->nomComplet() : '—';
        $premier = $jours[0]['date'] ?? null;
        $decalage = $premier ? $premier->dayOfWeekIso - 1 : 0;
    @endphp
    <div class="cal">
        @foreach($libJours as $jl)<div class="cal-entete">{{ $jl }}</div>@endforeach
        @for($k = 0; $k < $decalage; $k++)<div></div>@endfor
        @foreach($jours as $j)
            @php $d = $j['daily']; $auj = $j['date']->isToday(); @endphp
            @if($d)
            <a class="jour fait {{ $auj ? 'auj' : '' }}" href="{{ $d->url() }}">
                <span class="num">Daily {{ $d->numero_jour }}</span>
                <span class="dt">{{ ucfirst($j['date']->translatedFormat('D d/m')) }}</span>
                <span class="roles">Facilitateur : <b>{{ $nom($d->roleUserId('facilitateur')) }}</b><br>Scribe : <b>{{ $nom($d->roleUserId('scribe')) }}</b></span>
                @php [$libS, $clsS] = $d->statutPublication(); @endphp
                <span class="bas"><span class="pill {{ $clsS }}">{{ $libS }}</span><span class="pill p-cours">{{ $d->entrees_count }} membre(s)</span>@if($d->defis_count)<span class="pill p-warn">{{ $d->defis_count }} défi(s)</span>@endif</span>
            </a>
            @else
            <div class="jour {{ $auj ? 'auj' : '' }} {{ $j['date']->isFuture() && !$auj ? 'futur' : '' }}">
                <span class="num">Jour {{ $j['numero'] }}</span>
                <span class="dt">{{ ucfirst($j['date']->translatedFormat('D d/m')) }}</span>
                <span class="roles">Pas de Daily</span>
                @if($peutCreer)
                <span class="bas">
                    <form method="POST" action="{{ route('scrum.daily.store', $sprint) }}">@csrf
                        <input type="hidden" name="date_reunion" value="{{ $j['date']->toDateString() }}">
                        <input type="hidden" name="heure_debut" value="{{ $heureDefaut }}">
                        <button class="btn sm sec">+ Créer</button>
                    </form>
                </span>
                @endif
            </div>
            @endif
        @endforeach
    </div>
    @if($horsProgramme->isNotEmpty() || $dailiesHorsSprint->isNotEmpty())
    <div style="margin-top:10px;font-size:12px;color:var(--gris);display:flex;gap:4px;flex-wrap:wrap;align-items:center">Daily hors sprint du projet :
        @foreach($horsProgramme->concat($dailiesHorsSprint) as $hp)
        @php [$libS, $clsS] = $hp->statutPublication(); @endphp
        <a href="{{ $hp->url() }}" class="pill {{ $clsS }}" style="text-decoration:none" title="{{ $libS }}">{{ $hp->objet ?: $hp->libelle() }} · {{ $hp->date_reunion->format('d/m') }}</a>
        @endforeach
    </div>
    @endif
</div>

<div class="carte">
    <div style="font-weight:700;color:var(--bleu)">Participants du sprint </div>
    <div class="equipe-liste">@forelse($membres as $m)<span>{{ $m->nomComplet() }}</span>@empty<span>Aucun membre : ajoutez l'équipe dans l'espace projet.</span>@endforelse</div>
</div>
</div>
@if($peutCreer)
{{-- Daily hors sprint du projet (date libre, participants au choix) --}}
<dialog id="dlg-hs" class="dlg">
    <h3 style="color:var(--bleu);margin:0 0 4px">Daily hors sprint - {{ $sprint->projet->libelle }}</h3>
    <p style="font-size:12px;color:var(--gris);margin:0 0 12px">À n'importe quelle date. Lignes pré-remplies avec les tâches de chaque participant.</p>
    <form method="POST" action="{{ route('scrum.daily.hors-sprint') }}">@csrf
        <input type="hidden" name="projet_id" value="{{ $sprint->projet_id }}">
        <div class="grille3">
            <div class="champ"><label>Date</label><input type="date" name="date_reunion" value="{{ today()->toDateString() }}" required></div>
            <div class="champ"><label>Heure début</label><input type="time" name="heure_debut" value="{{ $config['heure_debut'] }}"></div>
            <div class="champ"><label>Heure fin</label><input type="time" name="heure_fin" value="{{ $config['heure_fin'] }}"></div>
        </div>
        <div class="champ" style="margin-top:10px"><label>Objet</label><input name="objet" maxlength="150" placeholder="Ex. Point d'avancement inter-sprint"></div>
        <div class="champ" style="margin-top:10px"><label>Participants</label>
            <div class="parts">
                @foreach($collaborateurs as $c)<label><input type="checkbox" name="participants[]" value="{{ $c->id }}" @checked($membres->contains('id', $c->id))> {{ $c->nomComplet() }}</label>@endforeach
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Créer et pré-remplir</button>
        </div>
    </form>
</dialog>
@endif
@endsection
