@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Rapports Daily Scrum')

@push('styles')
<style>
.filtre{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:14px}
.filtre label{display:block;font-size:11px;font-weight:600;color:var(--gris);text-transform:uppercase;margin-bottom:3px}
.filtre select,.filtre input{height:34px;padding:5px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;background:#fff;box-sizing:border-box}
.col-dt{width:12%}.col-pj{width:25%}.col-dl{width:13%}.col-sc{width:17%}.col-st{width:13%}.col-pa{width:10%}.col-df{width:8%}
.vide{text-align:center;color:var(--gris);padding:30px}
/* Rapports du jour : mis en avant (fond, liseré, étiquette) */
.sc-tbl tr.auj td{background:#fff8e6}
.sc-tbl tr.auj td:first-child{box-shadow:inset 4px 0 0 #f59e0b}
.sc-tbl tr.auj:hover td{background:#fff1cc}
.sc-tbl tr.passe td{color:#64748b}
.etq-auj{display:inline-block;font-size:10px;font-weight:700;letter-spacing:.3px;text-transform:uppercase;background:#f59e0b;color:#fff;border-radius:6px;padding:1px 7px;margin-bottom:2px}
.sep-jour td{background:#f8fafc!important;font-size:10.5px;font-weight:700;color:var(--gris);text-transform:uppercase;letter-spacing:.4px;padding:6px 10px!important;box-shadow:none!important}
.du-jour{display:flex;gap:10px;flex-wrap:wrap;align-items:stretch;background:#fffbeb;border:1px solid #fcd34d;border-radius:12px;padding:12px 14px;margin-bottom:14px}
.du-jour .titre{display:flex;flex-direction:column;justify-content:center;min-width:150px}
.du-jour .titre b{font-size:13px;color:#92400e}
.du-jour .titre span{font-size:11.5px;color:#b45309}
.du-jour a.carte-jour{display:flex;flex-direction:column;gap:2px;background:#fff;border:1px solid #fde68a;border-radius:10px;padding:8px 12px;text-decoration:none;color:#1e293b;min-width:190px}
.du-jour a.carte-jour:hover{border-color:#f59e0b;box-shadow:0 2px 8px rgba(245,158,11,.18)}
.du-jour a.carte-jour strong{font-size:12.5px;font-weight:600}
.du-jour a.carte-jour small{font-size:11px;color:var(--gris)}
.du-jour .aucun{font-size:12px;color:#b45309;align-self:center}
.type-hs{display:inline-block;font-size:10px;font-weight:600;background:#fff7ed;color:#c2410c;border-radius:8px;padding:1px 7px;margin-left:4px}
dialog.dlg{border:none;border-radius:14px;padding:22px;width:620px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:90vh;overflow-y:auto}
dialog.dlg::backdrop{background:rgba(22,56,92,.35)}
.grille2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:10px}
.parts{max-height:240px;overflow-y:auto;border:1px solid var(--bord);border-radius:8px;padding:6px 10px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:2px 10px}
.parts label{display:flex;align-items:center;gap:6px;font-size:12.5px;cursor:pointer;text-transform:none;font-weight:400;color:#1e293b;margin:0;padding:3px 0}
.parts input{width:auto}
</style>
@endpush

@section('contenu')
@include('scrum._styles')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap">
    <div>
        <h2>Rapports Daily Scrum</h2>
        <div class="crumb" style="margin-bottom:12px">Daily de sprint et hors sprint, tous projets confondus.</div>
    </div>
    @if($peutCreer)<button type="button" class="btn" onclick="document.getElementById('dlg-hs').showModal()">+ Daily hors sprint</button>@endif
</div>
@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<form method="GET" class="filtre">
    <div><label>Projet</label><select name="projet_id" onchange="this.form.submit()">
        <option value="">Tous</option>
        <option value="transversal" @selected(request('projet_id') === 'transversal')>Transversal (sans projet)</option>
        @foreach($projets as $pj)<option value="{{ $pj->id }}" @selected(request('projet_id') == $pj->id)>{{ $pj->libelle }}</option>@endforeach
    </select></div>
    <div><label>Type</label><select name="type" onchange="this.form.submit()">
        <option value="">Tous</option>
        <option value="sprint" @selected(request('type') === 'sprint')>Daily de sprint</option>
        <option value="hors_sprint" @selected(request('type') === 'hors_sprint')>Daily hors sprint</option>
    </select></div>
    <div><label>Du</label><input type="date" name="du" value="{{ request('du') }}"></div>
    <div><label>Au</label><input type="date" name="au" value="{{ request('au') }}"></div>
    <button class="btn sm" style="height:34px">Filtrer</button>
    @if(request()->hasAny(['projet_id', 'type', 'du', 'au']))<a href="{{ route('scrum.rapports') }}" class="btn sm sec" style="height:34px">Réinitialiser</a>@endif
</form>

@if($duJour !== null)
<div class="du-jour">
    <div class="titre"><b>Aujourd'hui</b><span>{{ ucfirst(today()->translatedFormat('l j F')) }}</span></div>
    @forelse($duJour as $d)
    @php [$libS, $clsS] = $d->statutPublication(); @endphp
    <a href="{{ $d->url() }}" class="carte-jour">
        <strong>{{ $d->contexte() }}</strong>
        <small>Daily - jour {{ $d->numero_jour }}{{ $d->heure_debut ? ' · ' . substr($d->heure_debut, 0, 5) : '' }} · {{ $d->entrees_count }} participant(s)</small>
        <span><span class="pill {{ $clsS }}">{{ $libS }}</span>@if($d->defis_count) <span class="pill p-warn">{{ $d->defis_count }} défi(s)</span>@endif</span>
    </a>
    @empty
    <span class="aucun">Aucun Daily Scrum tenu aujourd'hui pour l'instant.</span>
    @endforelse
</div>
@endif

<div class="panneau">
<h3><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg> Liste des rapports <span class="h3-info">{{ $dailies->total() }} rapport(s)</span></h3>
<table class="sc-tbl">
    <thead><tr><th class="col-dt">Date</th><th class="col-pj">Contexte</th><th class="col-dl">Réunion</th><th class="col-sc">Rédacteur (scribe)</th><th class="col-st ctr">Statut</th><th class="col-pa ctr">Participants</th><th class="col-df ctr">Défis</th></tr></thead>
    <tbody>
    @php $vuAuj = false; $vuAvant = false; @endphp
    @forelse($dailies as $d)
        @php $estAuj = $d->date_reunion->isToday(); @endphp
        @if($estAuj && !$vuAuj)@php $vuAuj = true; @endphp<tr class="sep-jour"><td colspan="7">Aujourd'hui</td></tr>@endif
        @if(!$estAuj && $d->date_reunion->lt(today()) && !$vuAvant)@php $vuAvant = true; @endphp @if($vuAuj)<tr class="sep-jour"><td colspan="7">Jours précédents</td></tr>@endif @endif
        <tr style="cursor:pointer" class="{{ $estAuj ? 'auj' : '' }}" onclick="location.href='{{ $d->url() }}'">
            <td>@if($estAuj)<span class="etq-auj">Aujourd'hui</span><br>@endif{{ ucfirst($d->date_reunion->translatedFormat('D d/m/Y')) }}</td>
            <td class="nom">{{ $d->contexte() }}@if($d->estHorsSprint())<span class="type-hs">Hors sprint</span>@endif
                @if($d->objet)<small>{{ $d->objet }}</small>@endif</td>
            <td><a href="{{ $d->url() }}" style="color:var(--accent);font-weight:600;text-decoration:none">Daily - jour {{ $d->numero_jour }}</a></td>
            <td>{{ $scribes->get($d->roleUserId('scribe'))?->nomComplet() ?? '—' }}</td>
            @php [$libS, $clsS] = $d->statutPublication(); @endphp
            <td class="ctr"><span class="pill {{ $clsS }}" @if($d->estPublie()) title="Publié le {{ $d->publie_le->format('d/m/Y') }}" @endif>{{ $libS }}</span></td>
            <td class="ctr">{{ $d->entrees_count }}</td>
            <td class="ctr">@if($d->defis_count)<span class="pill p-warn">{{ $d->defis_count }}</span>@else - @endif</td>
        </tr>
    @empty
        <tr><td colspan="7" class="vide">Aucun Daily Scrum enregistré.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:12px">{{ $dailies->links() }}</div>

{{-- Daily hors sprint : rattaché à un projet sans sprint, ou transversal --}}
<dialog id="dlg-hs" class="dlg">
    <h3 style="color:var(--bleu);margin:0 0 4px">Nouveau Daily hors sprint</h3>
    <p style="font-size:12px;color:var(--gris);margin:0 0 12px">Lignes pré-remplies avec les tâches de chaque participant (sprints en cours et hors sprint).</p>
    <form method="POST" action="{{ route('scrum.daily.hors-sprint') }}">@csrf
        <div class="grille2">
            <div class="champ"><label>Date</label><input type="date" name="date_reunion" value="{{ old('date_reunion', today()->toDateString()) }}" required></div>
            <div class="champ"><label>Projet</label>
                <select name="projet_id" id="hs-projet" onchange="preselection(this.value)">
                    <option value="">Aucun (transversal)</option>
                    @foreach($projets as $pj)<option value="{{ $pj->id }}" @selected(old('projet_id') == $pj->id)>{{ $pj->libelle }}</option>@endforeach
                </select></div>
            <div class="champ"><label>Heure début</label><input type="time" name="heure_debut" value="{{ old('heure_debut', $heureDefaut['heure_debut']) }}"></div>
            <div class="champ"><label>Heure fin</label><input type="time" name="heure_fin" value="{{ old('heure_fin', $heureDefaut['heure_fin']) }}"></div>
        </div>
        <div class="champ" style="margin-top:10px"><label>Objet</label><input name="objet" value="{{ old('objet') }}" maxlength="150" placeholder="Ex. Daily équipe opérations"></div>
        <div class="champ" style="margin-top:10px"><label>Participants</label>
            <div class="parts">
                @foreach($collaborateurs as $c)<label><input type="checkbox" name="participants[]" value="{{ $c->id }}" @checked(in_array($c->id, old('participants', [])))> {{ $c->nomComplet() }}</label>@endforeach
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Créer et pré-remplir</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
// Choisir un projet pré-coche son équipe
const equipes = @json($equipes);
function preselection(projet) {
    if (!projet) return;
    const ids = (equipes[projet] || []).map(String);
    document.querySelectorAll('.parts input').forEach(c => { c.checked = ids.includes(c.value); });
}
@if($peutCreer && ($errors->any() || session('erreur')))document.getElementById('dlg-hs').showModal();@endif
</script>
@endpush
