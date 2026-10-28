@extends('layouts.app')
@section('sousTitre', 'Arbitrages')
@section('titre', 'Arbitrages')
@push('styles')
<style>
.arb-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:12px;display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}
.arb-info{flex:1;min-width:200px}
.arb-titre{font-weight:600;font-size:14px;margin-bottom:4px}
.arb-meta{font-size:12px;color:var(--gris)}
.arb-actions{display:flex;gap:8px}
.btn-arb{border:none;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer}
.btn-ok{background:#dcfce7;color:var(--ok)}.btn-ok:hover{background:#bbf7d0}
.btn-ko{background:#fee2e2;color:var(--ko)}.btn-ko:hover{background:#fecaca}
.dec{padding:3px 10px;border-radius:10px;font-size:11px;font-weight:600}
.dec-accorde{background:#dcfce7;color:var(--ok)}
.dec-refuse{background:#fee2e2;color:var(--ko)}
.dec-attente{background:#fef3c7;color:#b45309}
.vide{text-align:center;padding:40px;color:var(--gris);font-size:14px}
.evt-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:10px}
.evt-type{padding:2px 9px;border-radius:10px;font-size:10.5px;font-weight:600}
.evt-traite{opacity:.7;border-left:3px solid var(--ok)}
.evt-non-traite{border-left:3px solid #f59e0b}
.bloc-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:10px;border-left:3px solid var(--ko)}
.reco-zone{margin-top:10px;padding-top:10px;border-top:1px solid var(--bord)}
.reco-texte{background:#f0fdf4;border-radius:8px;padding:8px 12px;font-size:12px;color:#1e293b;margin-top:6px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sm{padding:5px 12px;font-size:11.5px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
dialog{border:none;border-radius:14px;padding:24px;width:480px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:600;text-transform:uppercase}
dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;min-height:80px;resize:vertical}
.typ{font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-right:4px}
.t1{background:#dbeafe;color:#1d4ed8}.t2{background:#e2e8f0;color:#475569}.t3{background:#fee2e2;color:#b91c1c}
.onglets{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap}
.onglet{padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;position:relative}
.onglet.actif{background:var(--accent);color:#fff}
.onglet:not(.actif){background:#e2e8f0;color:var(--gris)}
.badge-onglet{position:absolute;top:-4px;right:-4px;background:var(--ko);color:#fff;border-radius:50%;width:18px;height:18px;font-size:10px;display:inline-flex;align-items:center;justify-content:center;font-weight:700}
.duree-bloc{font-size:11px;padding:2px 8px;border-radius:6px;font-weight:600}
.duree-court{background:#fef3c7;color:#b45309}
.duree-long{background:#fee2e2;color:var(--ko)}
</style>
@endpush
@section('contenu')
<h2>Arbitrages &amp; Actions requises</h2>
<div class="crumb">Pilotage &rsaquo; Arbitrages, blocages &amp; &eacute;v&eacute;nements</div>

@if(session('succes'))
<div style="background:#dcfce7;color:var(--ok);padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px">{{ session('succes') }}</div>
@endif
@if(session('erreur'))
<div style="background:#fee2e2;color:var(--ko);padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px">{{ session('erreur') }}</div>
@endif

@php
    $nbArbAttente = $arbitrages->where('decision','en_attente')->count();
    $nbBlocActifs = isset($blocages) ? $blocages->count() : 0;
    $nbEvtNonTraites = $evenements->filter(fn($e) => !$e->estTraite())->count();
@endphp

<div class="onglets">
    <button class="onglet actif" onclick="showTab('bloc',this)">
        Blocages actifs
        @if($nbBlocActifs > 0)<span class="badge-onglet">{{ $nbBlocActifs }}</span>@endif
    </button>
    <button class="onglet" onclick="showTab('evt',this)">
        &Eacute;v&eacute;nements
        @if($nbEvtNonTraites > 0)<span class="badge-onglet">{{ $nbEvtNonTraites }}</span>@endif
    </button>
    <button class="onglet" onclick="showTab('arb',this)">
        Arbitrages T3
        @if($nbArbAttente > 0)<span class="badge-onglet">{{ $nbArbAttente }}</span>@endif
    </button>
</div>

{{-- TAB Blocages actifs --}}
<div id="tab-bloc">
@if(isset($blocages) && $blocages->count() > 0)
    @foreach($blocages as $b)
    @if($b->tache)
    @php
        $heures = round($b->date_blocage->diffInHours(now()), 1);
        $dureeClass = $heures > 24 ? 'duree-long' : 'duree-court';
    @endphp
    <div class="bloc-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <div style="margin-bottom:6px;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                    <span class="typ {{ strtolower($b->tache->type ?? '') }}">{{ $b->tache->type ?? '—' }}</span>
                    <a href="{{ route('taches.show', $b->tache) }}" style="font-weight:600;font-size:14px;color:#1e293b;text-decoration:none">{{ $b->tache->intitule }}</a>
                    <span class="duree-bloc {{ $dureeClass }}">{{ $heures }}h bloqu&eacute;</span>
                </div>
                <div style="font-size:13px;margin-bottom:4px"><strong>Motif :</strong> {{ $b->motif }}</div>
                @if($b->recommandation)
                <div style="font-size:12px;color:var(--gris);margin-bottom:2px"><strong>Recommandation :</strong> {{ $b->recommandation }}</div>
                @endif
                <div style="font-size:12px;color:var(--gris)">
                    Assign&eacute; &agrave; <strong>{{ $b->tache->user?->nomComplet() ?? '—' }}</strong>
                    @if($b->tache->projet) &middot; {{ $b->tache->projet->libelle }} @endif
                    @if($b->intervenant) &middot; Intervenant : {{ $b->intervenant->nomComplet() }} @endif
                    &middot; {{ $b->date_blocage->format('d/m/Y H:i') }}
                </div>
            </div>
            <div style="display:flex;gap:6px;align-items:center">
                <a href="{{ route('taches.show', $b->tache) }}" class="btn sm">Voir la t&acirc;che</a>
                <form method="POST" action="{{ route('blocages.lever', $b) }}" style="display:inline">
                    @csrf @method('PATCH')
                    <button class="btn-arb btn-ok">Lever le blocage</button>
                </form>
            </div>
        </div>
    </div>
    @endif
    @endforeach
@else
    <div class="carte vide">Aucun blocage actif.</div>
@endif
</div>

{{-- TAB Événements --}}
<div id="tab-evt" style="display:none">
@forelse($evenements as $e)
@php $c = $e->couleurType(); @endphp
<div class="evt-card {{ $e->estTraite() ? 'evt-traite' : 'evt-non-traite' }}">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
        <div style="flex:1;min-width:200px">
            <div style="margin-bottom:4px">
                <span class="evt-type" style="background:{{ $c['fond'] }};color:{{ $c['texte'] }}">{{ $e->labelType() }}</span>
                <span style="font-size:12px;color:var(--gris);margin-left:8px">{{ $e->date->format('d/m/Y') }}</span>
            </div>
            <div style="font-size:14px;font-weight:600;margin-bottom:4px">{{ $e->description }}</div>
            @if($e->action_engagee)
            <div style="font-size:12px;color:#1e293b;margin-bottom:4px"><strong>Action engag&eacute;e :</strong> {{ $e->action_engagee }}</div>
            @endif
            <div style="font-size:12px;color:var(--gris)">
                Signal&eacute; par <strong>{{ $e->user->nomComplet() }}</strong>
                @if($e->impact) &middot; Impact : {{ $e->impact }} @endif
            </div>
        </div>
        <div>
            @if($e->estTraite())
                <span class="dec dec-accorde">Trait&eacute;</span>
            @else
                <button class="btn sm" onclick="ouvrirReco({{ $e->id }}, {{ Js::from($e->description) }})">R&eacute;agir</button>
            @endif
        </div>
    </div>
    @if($e->estTraite())
    <div class="reco-zone">
        <div style="font-size:11px;color:var(--gris);font-weight:600">
            Recommandation de {{ $e->traitePar->nomComplet() }} - {{ $e->traite_le->format('d/m/Y H:i') }}
        </div>
        <div class="reco-texte">{{ $e->recommandation }}</div>
    </div>
    @endif
</div>
@empty
<div class="carte vide">Aucun &eacute;v&eacute;nement signal&eacute;.</div>
@endforelse
</div>

{{-- TAB Arbitrages T3 --}}
<div id="tab-arb" style="display:none">
@forelse($arbitrages as $a)
<div class="arb-card">
    <div class="arb-info">
        <div class="arb-titre">
            <span class="typ t3">T3</span>
            {{ $a->tache?->intitule ?? '—' }}
        </div>
        <div class="arb-meta">
            Demandeur : <strong>{{ $a->demandeur?->nomComplet() ?? '—' }}</strong>
            &middot; {{ $a->created_at->format('d/m/Y H:i') }}
            @if($a->tache?->projet)
            &middot; Projet : {{ $a->tache->projet->libelle ?? '—' }}
            @endif
        </div>
    </div>
    <div>
        @if($a->decision === 'en_attente')
            <span class="dec dec-attente">En attente</span>
            <form method="POST" action="{{ route('arbitrages.decider', $a) }}" style="display:inline-flex;gap:6px;margin-left:8px">
                @csrf @method('PATCH')
                <button name="decision" value="accorde" class="btn-arb btn-ok">Accorder</button>
                <button name="decision" value="refuse" class="btn-arb btn-ko">Refuser</button>
            </form>
        @elseif($a->decision === 'accorde')
            <span class="dec dec-accorde">Accord&eacute;</span>
        @else
            <span class="dec dec-refuse">Refus&eacute;</span>
        @endif
    </div>
</div>
@empty
<div class="carte vide">Aucune demande d'arbitrage T3.</div>
@endforelse
</div>

<dialog id="dlg-reco">
    <h3 style="color:var(--bleu);margin-bottom:4px">R&eacute;agir &agrave; l'&eacute;v&eacute;nement</h3>
    <p id="reco-desc" style="font-size:13px;color:#1e293b;margin-bottom:8px;font-weight:600"></p>
    <form method="POST" id="reco-form">
        @csrf
        <label>Recommandation / Action</label>
        <textarea name="recommandation" required placeholder="Ex : Pr&eacute;voir un plan de rattrapage, informer le client..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="document.getElementById('dlg-reco').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
function showTab(tab, btn) {
    document.getElementById('tab-arb').style.display = tab === 'arb' ? '' : 'none';
    document.getElementById('tab-evt').style.display = tab === 'evt' ? '' : 'none';
    document.getElementById('tab-bloc').style.display = tab === 'bloc' ? '' : 'none';
    document.querySelectorAll('.onglet').forEach(o => o.classList.remove('actif'));
    btn.classList.add('actif');
}
function ouvrirReco(id, desc) {
    document.getElementById('reco-desc').textContent = desc;
    document.getElementById('reco-form').action = '/arbitrages/evenement/' + id + '/recommander';
    document.getElementById('dlg-reco').showModal();
}
</script>
@endpush
