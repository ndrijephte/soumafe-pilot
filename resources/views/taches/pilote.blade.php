@extends('layouts.app')
@section('sousTitre', 'Pilotage des tâches')
@section('titre', 'Toutes les tâches - Pilote')
@push('styles')
<style>
.tp-filtres{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px 16px;margin-bottom:12px}
.tp-ligne{display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end}
.tp-ligne + .tp-ligne{margin-top:10px;padding-top:10px;border-top:1px dashed var(--bord)}
.tp-champ label{display:block;font-size:10.5px;font-weight:600;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px}
.tp-champ select,.tp-champ input[type=date],.tp-champ input[type=search]{height:34px;padding:0 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;background:#fff;color:#334155;box-sizing:border-box}
.tp-champ select{min-width:140px}
.tp-champ input[type=search]{min-width:240px}
.tp-periode{display:flex;align-items:center;gap:6px}
.tp-periode span{font-size:12px;color:var(--gris)}
.tp-rac{display:flex;gap:5px;flex-wrap:wrap;align-self:center}
.tp-rac button{border:1px solid var(--bord);background:#fff;color:#475569;border-radius:16px;padding:4px 10px;font-size:11.5px;cursor:pointer}
.tp-rac button:hover{border-color:var(--accent);color:var(--accent)}
.tp-coche{display:flex;align-items:center;gap:6px;font-size:12.5px;color:#334155;height:34px;cursor:pointer}
.tp-coche input{accent-color:var(--accent)}
.tp-btn{height:34px;padding:0 16px;border-radius:8px;font-size:12.5px;cursor:pointer;border:none;background:var(--accent);color:#fff;font-weight:600}
.tp-reset{height:34px;display:inline-flex;align-items:center;padding:0 12px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;background:#f8fafc;color:#64748b;text-decoration:none}
.tp-statuts{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.tp-statut{display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:9px;border:1px solid var(--bord);background:#fff;color:#334155;font-size:12.5px;font-weight:600;text-decoration:none}
.tp-statut b{font-weight:700;font-size:13px}
.tp-statut i{width:8px;height:8px;border-radius:50%}
.tp-statut.actif{background:var(--bleu);border-color:var(--bleu);color:#fff}
.tp-statut.vide{opacity:.5}
.tp-resume{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;font-size:12px;color:var(--gris);margin:0 2px 8px}
.tp-resume strong{color:var(--bleu)}
.tp-resume select{height:28px;border:1px solid var(--bord);border-radius:6px;font-size:12px;padding:0 6px}
.tbl-wrap{background:var(--carte);border:1px solid var(--bord);border-radius:12px;overflow-x:auto}
.tbl-pilote{width:100%;border-collapse:collapse;font-size:12.5px}
.tbl-pilote th{text-align:left;padding:10px 12px;background:#f4f7fb;color:#475569;font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;border-bottom:1px solid var(--bord)}
.tbl-pilote th a{color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:3px}
.tbl-pilote th a:hover,.tbl-pilote th a.trie{color:var(--bleu)}
.tbl-pilote td{padding:9px 12px;border-bottom:1px solid #eef2f7;vertical-align:top}
.tbl-pilote tbody tr:hover td{background:#f8fafc}
.tbl-pilote tr.retard td:first-child{box-shadow:inset 3px 0 0 var(--ko)}
.tbl-pilote tr.ajout td:first-child{box-shadow:inset 3px 0 0 #7c3aed}
.badge{padding:2px 8px;border-radius:8px;font-size:10.5px;font-weight:600;white-space:nowrap;display:inline-block}
.b-ok{background:#dcfce7;color:#15803d}.b-cours{background:#e0ecf8;color:#1f4e79}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}.b-blue{background:#ede9fe;color:#6d28d9}.b-gris{background:#f1f5f9;color:#64748b}
.lien{color:#1e293b;text-decoration:none;font-weight:600}
.lien:hover{color:var(--accent);text-decoration:underline}
.meta{display:flex;gap:6px;flex-wrap:wrap;margin-top:3px}
.us{font-size:10.5px;font-family:ui-monospace,monospace;color:var(--accent);background:#eef5fc;border-radius:5px;padding:1px 6px}
.ajout{font-size:10.5px;font-weight:600;color:#6d28d9;background:#f3e8ff;border-radius:5px;padding:1px 6px}
.qui{display:flex;align-items:center;gap:7px;white-space:nowrap}
.ini{width:24px;height:24px;border-radius:50%;background:#e0ecf8;color:#1f4e79;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.proj{white-space:nowrap}.proj .code{font-family:ui-monospace,monospace;font-size:11.5px;font-weight:600;color:#334155}.proj .sp{color:var(--gris);font-size:11.5px}
.prio{font-size:11.5px;display:inline-flex;align-items:center;gap:5px;white-space:nowrap}.prio i{width:7px;height:7px;border-radius:50%}
.date{font-size:12px;color:#475569;white-space:nowrap;font-variant-numeric:tabular-nums}
.date.ko{color:var(--ko);font-weight:600}
.pagination-wrap{display:flex;justify-content:center;gap:4px;margin-top:14px;flex-wrap:wrap}
.pagination-wrap a,.pagination-wrap span{padding:6px 11px;border:1px solid var(--bord);border-radius:6px;font-size:12px;color:var(--bleu);text-decoration:none;background:#fff}
.pagination-wrap .actif{background:var(--bleu);color:#fff;border-color:var(--bleu)}
.pagination-wrap .off{color:#cbd5e1}
</style>
@endpush
@section('contenu')
@php
    $libStatut = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'a_valider' => 'À valider', 'bloque' => 'Bloqué', 'termine' => 'Terminé', 'en_retard' => 'En retard'];
    $couleurStatut = ['a_faire' => '#94a3b8', 'en_cours' => '#2e74b5', 'a_valider' => '#7c3aed', 'bloque' => '#dc2626', 'termine' => '#16a34a', 'en_retard' => '#b45309'];
    $courant = request()->except('page');
    $url = fn(array $modif) => route('taches.pilote', array_filter(array_merge($courant, $modif), fn($v) => $v !== null && $v !== ''));
    $enTete = function (string $cle, string $libelle) use ($tri, $url) {
        return '<a href="' . e($url(['tri' => $cle === 'statut' ? null : $cle])) . '" class="' . ($tri === $cle ? 'trie' : '') . '">' . e($libelle) . ($tri === $cle ? ' ▾' : '') . '</a>';
    };
    $date = fn($d) => $d ? $d->format($d->year === now()->year ? 'd/m' : 'd/m/Y') : '—';
    $auj = now();
    $presets = [
        "Aujourd'hui" => [$auj, $auj],
        'Cette semaine' => [$auj->copy()->startOfWeek(), $auj->copy()->endOfWeek()],
        'Ce mois' => [$auj->copy()->startOfMonth(), $auj->copy()->endOfMonth()],
        'Mois précédent' => [$auj->copy()->subMonthNoOverflow()->startOfMonth(), $auj->copy()->subMonthNoOverflow()->endOfMonth()],
        'Cette année' => [$auj->copy()->startOfYear(), $auj->copy()->endOfYear()],
    ];
@endphp
<h2>Projets & Sprints</h2>
<div class="onglets-page">
    <a href="{{ route('sprints.index') }}">Projets / Sprints</a>
    <a href="{{ route('taches.pilote') }}" class="actif">Toutes les tâches</a>
</div>

<form class="tp-filtres" method="GET" action="{{ route('taches.pilote') }}" id="tp-form">
    @if($tri !== 'statut')<input type="hidden" name="tri" value="{{ $tri }}">@endif
    @if($parPage !== 50)<input type="hidden" name="par_page" value="{{ $parPage }}">@endif
    @if(request('statut'))<input type="hidden" name="statut" value="{{ request('statut') }}">@endif
    <div class="tp-ligne">
        <div class="tp-champ"><label>Recherche</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Intitulé de la tâche…"></div>
        <div class="tp-champ"><label>Collaborateur</label>
            <select name="collaborateur" onchange="this.form.submit()">
                <option value="">Tous</option>
                @foreach($collaborateurs as $c)
                <option value="{{ $c->id }}" @selected(request('collaborateur') == $c->id)>{{ $c->nomComplet() }}{{ $c->actif ? '' : ' (ancien)' }}</option>
                @endforeach
            </select>
        </div>
        <div class="tp-champ"><label>Projet</label>
            <select name="projet" onchange="this.form.sprint && (this.form.sprint.value='');this.form.submit()">
                <option value="">Tous</option>
                @foreach($projets as $p)
                <option value="{{ $p->id }}" @selected(request('projet') == $p->id)>{{ $p->libelle }}</option>
                @endforeach
            </select>
        </div>
        @if($sprints->isNotEmpty())
        <div class="tp-champ"><label>Sprint</label>
            <select name="sprint" onchange="this.form.submit()" style="min-width:110px">
                <option value="">Tous</option>
                @foreach($sprints as $sp)
                <option value="{{ $sp->id }}" @selected(request('sprint') == $sp->id)>Sprint {{ $sp->numero }}{{ $sp->statut === 'en_cours' ? ' (en cours)' : '' }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="tp-champ"><label>Priorité</label>
            <select name="priorite" onchange="this.form.submit()" style="min-width:110px">
                <option value="">Toutes</option>
                @foreach(['haute' => 'Haute', 'moyenne' => 'Moyenne', 'basse' => 'Basse'] as $k => $v)
                <option value="{{ $k }}" @selected(request('priorite') == $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="tp-champ"><label>Type</label>
            <select name="type" onchange="this.form.submit()" style="min-width:120px">
                <option value="">Tous</option>
                @foreach(['T1' => 'T1 (Sprint)', 'T2' => 'T2 (Récurrente)', 'T3' => 'T3 (Urgente)'] as $k => $v)
                <option value="{{ $k }}" @selected(request('type') == $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="tp-ligne">
        <div class="tp-champ"><label>Période</label>
            <div class="tp-periode">
                <select name="critere" title="Date prise en compte pour la période">
                    <option value="active" @selected($critere === 'active')>Tâches actives</option>
                    <option value="echeance" @selected($critere === 'echeance')>Échéance</option>
                    <option value="debut" @selected($critere === 'debut')>Date de début</option>
                    <option value="achevee" @selected($critere === 'achevee')>Achevées</option>
                </select>
                <span>du</span><input type="date" name="du" value="{{ $du }}">
                <span>au</span><input type="date" name="au" value="{{ $au }}">
            </div>
        </div>
        <div class="tp-rac">
            @foreach($presets as $lib => [$d, $f])
            <button type="button" onclick="tpPeriode('{{ $d->toDateString() }}','{{ $f->toDateString() }}')">{{ $lib }}</button>
            @endforeach
        </div>
        <label class="tp-coche" title="Tâches ajoutées alors que le sprint était déjà en cours"><input type="checkbox" name="ajouts" value="1" @checked(request()->boolean('ajouts')) onchange="this.form.submit()"> Ajoutées en cours de sprint</label>
        <div style="margin-left:auto;display:flex;gap:6px">
            @if($filtres || $tri !== 'statut')<a href="{{ route('taches.pilote') }}" class="tp-reset">Réinitialiser</a>@endif
            <button class="tp-btn">Filtrer</button>
        </div>
    </div>
</form>

<div class="tp-statuts">
    <a href="{{ $url(['statut' => null]) }}" class="tp-statut {{ request('statut') ? '' : 'actif' }}">Toutes <b>{{ $compteurs['tout'] ?? 0 }}</b></a>
    @foreach($libStatut as $k => $lib)
    @php $n = $compteurs[$k] ?? 0; @endphp
    <a href="{{ $url(['statut' => $k]) }}" class="tp-statut {{ request('statut') === $k ? 'actif' : '' }} {{ $n ? '' : 'vide' }}"><i style="background:{{ $couleurStatut[$k] }}"></i>{{ $lib }} <b>{{ $n }}</b></a>
    @endforeach
</div>

<div class="tp-resume">
    <span>
        @if($taches->total())
        <strong>{{ $taches->firstItem() }}–{{ $taches->lastItem() }}</strong> sur <strong>{{ $taches->total() }}</strong> tâche{{ $taches->total() > 1 ? 's' : '' }}
        @else Aucune tâche @endif
        @if($du || $au) · {{ ['active' => 'actives', 'echeance' => 'à échéance', 'debut' => 'débutées', 'achevee' => 'achevées'][$critere] }} {{ $du ? 'du ' . \Carbon\Carbon::parse($du)->format('d/m/Y') : '' }} {{ $au ? 'au ' . \Carbon\Carbon::parse($au)->format('d/m/Y') : '' }}@endif
    </span>
    <span>Par page
        <select onchange="location.href=this.value">
            @foreach([25, 50, 100, 200] as $n)
            <option value="{{ $url(['par_page' => $n === 50 ? null : $n]) }}" @selected($parPage === $n)>{{ $n }}</option>
            @endforeach
        </select>
    </span>
</div>

<div class="tbl-wrap">
<table class="tbl-pilote">
    <thead>
        <tr>
            <th>{!! $enTete('statut', 'Tâche') !!}</th>
            <th>{!! $enTete('collaborateur', 'Collaborateur') !!}</th>
            <th>{!! $enTete('projet', 'Projet') !!}</th>
            <th>Priorité</th>
            <th>{!! $enTete('debut', 'Début') !!}</th>
            <th>{!! $enTete('echeance', 'Échéance') !!}</th>
            <th>Achevée</th>
            <th>{!! $enTete('recent', 'Statut') !!}</th>
        </tr>
    </thead>
    <tbody>
    @forelse($taches as $t)
    @php
        $statutCls = match($t->statut) { 'termine' => 'b-ok', 'en_cours' => 'b-cours', 'bloque' => 'b-ko', 'a_valider' => 'b-blue', 'reporte' => 'b-gris', default => 'b-warn' };
        $prio = match($t->priorite) { 'haute' => ['#dc2626', 'Haute'], 'basse' => ['#94a3b8', 'Basse'], default => ['#f59e0b', 'Moyenne'] };
        $enRetard = $t->estEnRetard();
        $finTardive = $t->date_fin_reelle && $t->echeance && $t->date_fin_reelle->gt($t->echeance);
    @endphp
    <tr class="{{ $enRetard ? 'retard' : ($t->estAjoutEnCours() ? 'ajout' : '') }}">
        <td style="min-width:260px">
            <a href="{{ route('taches.show', $t) }}" class="lien">{{ $t->intitule }}</a>
            <div class="meta">
                <span class="badge b-gris">{{ $t->type }}</span>
                @if($t->userStory)<span class="us">{{ $t->userStory->numero }}</span>@endif
                @if($t->estAjoutEnCours())<span class="ajout" title="Ajoutée au sprint en cours le {{ $t->ajoutee_en_cours_le->format('d/m/Y à H:i') }}">+ Ajout {{ $t->ajoutee_en_cours_le->format('d/m') }}</span>@endif
            </div>
        </td>
        <td><span class="qui"><span class="ini">{{ mb_strtoupper(mb_substr($t->user?->prenom ?: $t->user?->name, 0, 1) . mb_substr($t->user?->name, 0, 1)) }}</span>{{ $t->user?->nomComplet() ?? '—' }}</span></td>
        <td class="proj" title="{{ $t->projet?->libelle }}"><span class="code">{{ $t->projet?->code ?? '—' }}</span>@if($t->sprint) <span class="sp">· Sprint {{ $t->sprint->numero }}</span>@endif</td>
        <td><span class="prio"><i style="background:{{ $prio[0] }}"></i>{{ $prio[1] }}</span></td>
        <td class="date">{{ $date($t->date_debut) }}</td>
        <td class="date {{ $enRetard ? 'ko' : '' }}"><x-voyant :tache="$t" /> {{ $date($t->echeance) }}</td>
        <td class="date {{ $finTardive ? 'ko' : '' }}" @if($finTardive) title="Achevée après l'échéance" @endif>{{ $date($t->date_fin_reelle) }}</td>
        <td><span class="badge {{ $statutCls }}">{{ $t->labelStatut() }}</span></td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;color:var(--gris);padding:28px">Aucune tâche ne correspond à ces critères.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

@if($taches->hasPages())
<div class="pagination-wrap">
    @if($taches->onFirstPage())<span class="off">‹</span>@else<a href="{{ $taches->previousPageUrl() }}">‹</a>@endif
    @foreach($taches->links()->elements as $element)
        @if(is_string($element))<span class="off">{{ $element }}</span>@endif
        @if(is_array($element))
            @foreach($element as $page => $lien)
            <a href="{{ $lien }}" class="{{ $page == $taches->currentPage() ? 'actif' : '' }}">{{ $page }}</a>
            @endforeach
        @endif
    @endforeach
    @if($taches->hasMorePages())<a href="{{ $taches->nextPageUrl() }}">›</a>@else<span class="off">›</span>@endif
</div>
@endif

<script>
function tpPeriode(du, au){
    const f = document.getElementById('tp-form');
    f.du.value = du; f.au.value = au; f.submit();
}
</script>
@endsection
