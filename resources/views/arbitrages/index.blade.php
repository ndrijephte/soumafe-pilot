@extends('layouts.app')
@section('sousTitre', 'Arbitrages')
@section('titre', 'Arbitrages')
@push('styles')
<style>
.arb-onglets{display:flex;gap:4px;box-shadow:inset 0 -1px 0 var(--bord);margin-bottom:14px;overflow-x:auto;overflow-y:hidden;scrollbar-width:none}
.arb-onglets button{background:none;border:none;border-bottom:2px solid transparent;padding:9px 14px;font-size:13px;font-weight:600;color:var(--gris);cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:7px}
.arb-onglets button:hover{color:var(--bleu)}
.arb-onglets button.actif{color:var(--accent);border-bottom-color:var(--accent)}
.arb-onglets .n{font-size:10.5px;font-weight:600;padding:1px 7px;border-radius:9px;background:#e2e8f0;color:#475569}
.arb-onglets .n.a-faire{background:#fee2e2;color:var(--ko)}
.arb-panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:6px 16px 10px}
.arb-tete{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:8px 0}
.arb-tete p{font-size:12px;color:var(--gris);margin:0}
.arb-filtre{display:flex;gap:6px}
.arb-filtre button{background:#fff;border:1px solid var(--bord);border-radius:8px;padding:4px 11px;font-size:12px;font-weight:600;color:#475569;cursor:pointer}
.arb-filtre button.actif{background:var(--accent);border-color:var(--accent);color:#fff}
.arb-table{width:100%;border-collapse:collapse;font-size:12.5px}
.arb-table th{text-align:left;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--gris);padding:8px;border-bottom:1px solid var(--bord)}
.arb-table td{padding:10px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#1e293b}
.arb-table tr:last-child td{border-bottom:none}
.arb-table tr:hover td{background:#f8fbff}
.arb-table .sec{display:block;font-size:11.5px;color:var(--gris);margin-top:2px}
.arb-table a.tache{color:#1e293b;font-weight:600;text-decoration:none}
.arb-table a.tache:hover{color:var(--accent);text-decoration:underline}
.arb-table .act{white-space:nowrap;text-align:right;width:1%}
.etat{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;white-space:nowrap;color:#334155}
.etat:before{content:"";width:7px;height:7px;border-radius:50%;background:#94a3b8;flex:none}
.etat.ok:before{background:var(--ok)}.etat.warn:before{background:#d97706}.etat.ko:before{background:var(--ko)}.etat.bleu:before{background:var(--accent)}
.etat.mut{color:var(--gris);font-weight:400}
.ib{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:none;background:none;cursor:pointer;color:var(--accent);text-decoration:none;vertical-align:middle}
.ib:hover{background:#e6f0fa}
.ib.ok{color:var(--ok)}.ib.ok:hover{background:#dcfce7}
.ib.ko{color:var(--ko)}.ib.ko:hover{background:#fee2e2}
.ib svg{width:16px;height:16px}
.reco{margin-top:4px;font-size:12px;color:#334155}
.reco b{font-weight:600;color:var(--gris);font-size:11px;display:block}
.vide{text-align:center;padding:28px 10px;color:var(--gris);font-size:13px}
.vide:before{content:"";display:block;width:8px;height:8px;border-radius:50%;background:var(--ok);margin:0 auto 8px}
.typ{font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-right:6px;vertical-align:1px}
.t1{background:#dbeafe;color:#1d4ed8}.t2{background:#e2e8f0;color:#475569}.t3{background:#fee2e2;color:#b91c1c}
.alerte-flash{padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:13px}
.alerte-flash.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte-flash.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:22px;width:480px;max-width:94vw;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog h3{color:var(--bleu);font-size:16px;margin-bottom:4px}
dialog label{font-size:12px;color:var(--gris);display:block;margin:12px 0 4px;font-weight:600}
dialog textarea{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;min-height:90px;resize:vertical;font-family:inherit}
dialog textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
@media(max-width:800px){.arb-table .cache-m{display:none}}
</style>
@endpush
@section('contenu')
@php
    $icOeil = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    $icOk = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>';
    $icKo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>';
    $icReagir = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>';

    $nbArbAttente = $arbitrages->where('decision', 'en_attente')->count();
    $nbBlocActifs = $blocages->count();
    $nbEvtNonTraites = $evenements->filter(fn($e) => !$e->estTraite())->count();
    // Onglet ouvert : celui demandé, sinon le premier qui attend une action
    $ongletDefaut = in_array(request('tab'), ['bloc', 'evt', 'arb'], true) ? request('tab')
        : ($nbArbAttente ? 'arb' : ($nbBlocActifs ? 'bloc' : ($nbEvtNonTraites ? 'evt' : 'bloc')));
    $duree = function ($debut) {
        $min = (int) $debut->diffInMinutes(now());
        if ($min < 60) return $min . ' min';
        $h = intdiv($min, 60);
        return $h < 24 ? $h . ' h' : intdiv($h, 24) . ' j' . ($h % 24 ? ' ' . ($h % 24) . ' h' : '');
    };
@endphp

<h2>Arbitrages &amp; actions requises</h2>
<div class="crumb">Pilotage &rsaquo; Blocages, événements et arbitrages T3</div>

@if(session('succes'))<div class="alerte-flash ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte-flash ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte-flash ko">{{ $errors->first() }}</div>@endif

<div class="arb-onglets" role="tablist">
    <button type="button" data-sans-icone data-onglet="bloc" onclick="arbOnglet('bloc')">Blocages actifs <span class="n {{ $nbBlocActifs ? 'a-faire' : '' }}">{{ $nbBlocActifs }}</span></button>
    <button type="button" data-sans-icone data-onglet="evt" onclick="arbOnglet('evt')">Événements <span class="n {{ $nbEvtNonTraites ? 'a-faire' : '' }}">{{ $nbEvtNonTraites ?: $evenements->count() }}</span></button>
    <button type="button" data-sans-icone data-onglet="arb" onclick="arbOnglet('arb')">Arbitrages T3 <span class="n {{ $nbArbAttente ? 'a-faire' : '' }}">{{ $nbArbAttente ?: $arbitrages->count() }}</span></button>
</div>

{{-- Blocages actifs --}}
<div class="arb-panneau" id="tab-bloc" hidden>
    <div class="arb-tete"><p>Tâches arrêtées en attente d'une décision ou d'une intervention. Lever le blocage remet la tâche en cours.</p></div>
    @if($blocages->isEmpty())
        <div class="vide">Aucun blocage actif.</div>
    @else
    <div style="overflow-x:auto"><table class="arb-table">
        <thead><tr><th>Tâche</th><th class="cache-m">Collaborateur</th><th>Motif</th><th>Bloquée depuis</th><th class="act"></th></tr></thead>
        <tbody>
        @foreach($blocages as $b)
            @php $heures = $b->date_blocage->diffInHours(now()); @endphp
            <tr>
                <td><span class="typ {{ strtolower($b->tache->type ?? '') }}">{{ $b->tache->type ?? '—' }}</span><a class="tache" href="{{ route('taches.show', $b->tache) }}">{{ $b->tache->intitule }}</a>
                    @if($b->tache->projet)<span class="sec">{{ $b->tache->projet->libelle }}</span>@endif</td>
                <td class="cache-m">{{ $b->tache->user?->nomComplet() ?? '—' }}
                    @if($b->intervenant)<span class="sec">Intervenant : {{ $b->intervenant->nomComplet() }}</span>@endif</td>
                <td>{{ $b->motif }}@if($b->recommandation)<span class="sec">Recommandation : {{ $b->recommandation }}</span>@endif</td>
                <td><span class="etat {{ $heures >= 24 ? 'ko' : 'warn' }}">{{ $duree($b->date_blocage) }}</span><span class="sec">{{ $b->date_blocage->format('d/m/Y H:i') }}</span></td>
                <td class="act">
                    <a class="ib" href="{{ route('taches.show', $b->tache) }}" title="Voir la tâche" aria-label="Voir la tâche">{!! $icOeil !!}</a>
                    <form method="POST" action="{{ route('blocages.lever', $b) }}" style="display:inline" onsubmit="return confirm('Lever le blocage de « {{ addslashes($b->tache->intitule) }} » ? La tâche repassera en cours.')">
                        @csrf @method('PATCH')
                        <button class="ib ok" data-sans-icone title="Lever le blocage" aria-label="Lever le blocage">{!! $icOk !!}</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @endif
</div>

{{-- Événements --}}
<div class="arb-panneau" id="tab-evt" hidden>
    <div class="arb-tete">
        <p>Faits marquants signalés par les collaborateurs. Votre réaction leur est notifiée.</p>
        <div class="arb-filtre">
            <button type="button" data-sans-icone data-filtre="a_traiter" onclick="arbFiltre('a_traiter')">À traiter ({{ $nbEvtNonTraites }})</button>
            <button type="button" data-sans-icone data-filtre="traite" onclick="arbFiltre('traite')">Traités ({{ $evenements->count() - $nbEvtNonTraites }})</button>
        </div>
    </div>
    @if($evenements->isEmpty())
        <div class="vide">Aucun événement signalé.</div>
    @else
    <div style="overflow-x:auto"><table class="arb-table" id="evt-table">
        <thead><tr><th>Date</th><th>Événement</th><th class="cache-m">Signalé par</th><th>Suite donnée</th><th class="act"></th></tr></thead>
        <tbody>
        @foreach($evenements as $e)
            @php $c = $e->couleurType(); @endphp
            <tr data-etat="{{ $e->estTraite() ? 'traite' : 'a_traiter' }}">
                <td style="white-space:nowrap">{{ $e->date->translatedFormat('d M Y') }}</td>
                <td><span class="pill" style="background:{{ $c['fond'] }};color:{{ $c['texte'] }};font-size:10.5px;margin-right:6px">{{ $e->labelType() }}</span><b style="font-weight:600">{{ $e->description }}</b>
                    @if($e->action_engagee)<span class="sec">Action engagée : {{ $e->action_engagee }}</span>@endif
                    @if($e->impact)<span class="sec">Impact : {{ $e->impact }}</span>@endif</td>
                <td class="cache-m">{{ $e->user?->nomComplet() ?? '—' }}</td>
                <td>
                    @if($e->estTraite())
                        <span class="etat ok">Traité</span>
                        <div class="reco"><b>{{ $e->traitePar?->nomComplet() ?? '—' }}{{ $e->traite_le ? ', le ' . $e->traite_le->format('d/m/Y à H:i') : '' }}</b>{{ $e->recommandation }}</div>
                    @else
                        <span class="etat warn">En attente de réaction</span>
                    @endif
                </td>
                <td class="act">
                    @unless($e->estTraite())
                    <button type="button" class="ib" data-sans-icone title="Réagir" aria-label="Réagir" onclick="ouvrirReco({{ $e->id }}, {{ Js::from($e->description) }})">{!! $icReagir !!}</button>
                    @endunless
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <div class="vide" id="evt-vide" hidden></div>
    @endif
</div>

{{-- Arbitrages T3 --}}
<div class="arb-panneau" id="tab-arb" hidden>
    <div class="arb-tete"><p>Demandes d'interruption du sprint par une tâche T3. Accorder neutralise son impact sur le sprint du collaborateur.</p></div>
    @if($arbitrages->isEmpty())
        <div class="vide">Aucune demande d'arbitrage T3.</div>
    @else
    <div style="overflow-x:auto"><table class="arb-table">
        <thead><tr><th>Tâche</th><th class="cache-m">Demandeur</th><th>Demandée le</th><th>Décision</th><th class="act"></th></tr></thead>
        <tbody>
        @foreach($arbitrages as $a)
            <tr>
                <td><span class="typ t3">T3</span><a class="tache" href="{{ route('taches.show', $a->tache) }}">{{ $a->tache->intitule }}</a>
                    @if($a->tache->projet)<span class="sec">{{ $a->tache->projet->libelle }}</span>@endif</td>
                <td class="cache-m">{{ $a->demandeur?->nomComplet() ?? '—' }}</td>
                <td style="white-space:nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    @if($a->decision === 'en_attente')<span class="etat warn">En attente</span>
                    @elseif($a->decision === 'accorde')<span class="etat ok">Accordé</span>
                    @else<span class="etat ko">Refusé</span>@endif
                    @if($a->decision !== 'en_attente' && $a->decideur)<span class="sec">par {{ $a->decideur->nomComplet() }}, le {{ $a->updated_at->format('d/m/Y') }}</span>@endif
                </td>
                <td class="act">
                    @if($a->decision === 'en_attente')
                    <form method="POST" action="{{ route('arbitrages.decider', $a) }}" style="display:inline">
                        @csrf @method('PATCH')
                        <button name="decision" value="accorde" class="ib ok" data-sans-icone title="Accorder" aria-label="Accorder" onclick="return confirm('Accorder l\'arbitrage ? L\'impact sur le sprint sera neutralisé.')">{!! $icOk !!}</button>
                        <button name="decision" value="refuse" class="ib ko" data-sans-icone title="Refuser" aria-label="Refuser" onclick="return confirm('Refuser l\'arbitrage ? La tâche reste à la charge du collaborateur.')">{!! $icKo !!}</button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    @endif
</div>

<dialog id="dlg-reco">
    <h3>Réagir à l'événement</h3>
    <p id="reco-desc" style="font-size:13px;color:#334155;margin:0"></p>
    <form method="POST" id="reco-form">
        @csrf
        <label for="reco-texte">Recommandation ou action</label>
        <textarea id="reco-texte" name="recommandation" required maxlength="1000" placeholder="Ex. : prévoir un plan de rattrapage, informer le client…"></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
function arbOnglet(nom){
    ['bloc','evt','arb'].forEach(function(t){ document.getElementById('tab-'+t).hidden = t !== nom; });
    document.querySelectorAll('.arb-onglets button').forEach(function(b){ b.classList.toggle('actif', b.dataset.onglet === nom); });
    var u = new URL(location.href); u.searchParams.set('tab', nom); history.replaceState(null, '', u);
}
function arbFiltre(etat){
    var t = document.getElementById('evt-table'); if (!t) return;
    var n = 0;
    t.querySelectorAll('tbody tr').forEach(function(tr){ var v = tr.dataset.etat === etat; tr.hidden = !v; if (v) n++; });
    document.querySelectorAll('.arb-filtre button').forEach(function(b){ b.classList.toggle('actif', b.dataset.filtre === etat); });
    t.closest('div').hidden = !n;
    var vide = document.getElementById('evt-vide');
    vide.hidden = !!n; vide.textContent = etat === 'a_traiter' ? 'Aucun événement en attente de réaction.' : 'Aucun événement traité.';
}
function ouvrirReco(id, desc){
    document.getElementById('reco-desc').textContent = desc;
    document.getElementById('reco-form').action = @js(url('arbitrages/evenement')) + '/' + id + '/recommander';
    document.getElementById('reco-form').reset();
    document.getElementById('dlg-reco').showModal();
}
arbOnglet(@js($ongletDefaut));
arbFiltre(@js($nbEvtNonTraites ? 'a_traiter' : 'traite'));
</script>
@endpush
