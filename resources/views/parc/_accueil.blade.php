{{--
    Accueil « Aujourd'hui » du parc, sans blocs superflus :
    1. la veille et les saisies rapides ;
    2. un seul panneau « À traiter », une catégorie à la fois ;
    3. la flotte et, à côté, l'aperçu du véhicule choisi (synthèse et documents) ;
    4. l'activité récente, repliable.
--}}
@php
    $A = $accueil;
    $fcfa = fn($n) => number_format((int) $n, 0, ',', ' ') . ' F';
    $jours = fn($d) => $d ? ($d->isToday() ? "aujourd'hui" : (int) $d->copy()->startOfDay()->diffInDays(today()) . ' j') : '—';
    $maintAlerte = collect($echeancier)->whereIn('alerte', ['en_retard', 'proche'])->values();
    $recettesManquantes = collect($alertesManquants ?? []);
    $lien = fn($tab) => route('parc.index', ['tab' => $tab]);
    $ico = fn($d, $t = 17) => '<svg viewBox="0 0 24 24" width="' . $t . '" height="' . $t . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';

    // Catégories « À traiter » : seules celles qui ont du contenu, la plus urgente d'abord
    $categories = array_filter([
        'recettes' => $recettesManquantes->isNotEmpty() ? ['Recettes à saisir', $recettesManquantes->count(), 'p-ko'] : null,
        'pannes' => $A['pannes']->isNotEmpty() ? ['Pannes', $A['pannes']->count(), 'p-warn'] : null,
        'maint' => $maintAlerte->isNotEmpty() ? ['Entretiens', $maintAlerte->count(), $maintAlerte->where('alerte', 'en_retard')->isNotEmpty() ? 'p-ko' : 'p-warn'] : null,
        'docs' => $docsARenouveler->isNotEmpty() ? ['Pièces', $docsARenouveler->count(), $docsARenouveler->contains(fn($d) => $d->date_expiration->lt(today())) ? 'p-ko' : 'p-warn'] : null,
        'penal' => $A['penalites']->isNotEmpty() ? ['Pénalités', $A['penalites']->count(), 'p-warn'] : null,
        'recouv' => $A['recouvrements']->isNotEmpty() ? ['Recouvrements', $A['recouvrements']->count(), 'p-cours'] : null,
    ]);
    $tauxHier = $A['objectif_hier'] > 0 ? round($A['recette_hier'] / $A['objectif_hier'] * 100) : null;

    // Flotte : synthèse par véhicule
    $recParVeh = $recettes->groupBy('vehicule_id');
    $pannesParVeh = $A['pannes']->groupBy('vehicule_id');
    $echParVeh = collect($echeancier)->filter(fn($e) => $e['prochaine'])->groupBy(fn($e) => $e['vehicule']->id);
    $docsRenParVeh = $docsARenouveler->where('documentable_type', 'vehicule')->groupBy('documentable_id');
    $synth = $vehicules->mapWithKeys(function ($v) use ($recParVeh, $pannesParVeh, $echParVeh, $docsRenParVeh) {
        $rv = $recParVeh->get($v->id, collect());
        $obj = (int) $rv->sum('objectif');
        $prochaine = $echParVeh->get($v->id)?->sortBy('jours')->first();
        $np = $pannesParVeh->get($v->id)?->count() ?? 0;
        $nd = $docsRenParVeh->get($v->id)?->count() ?? 0;
        return [$v->id => [
            'recette' => (int) $rv->sum('recette'), 'taux' => $obj > 0 ? round($rv->sum('recette') / $obj * 100) : null, 'jours' => $rv->pluck('date')->unique()->count(),
            'prochaine' => $prochaine, 'pannes' => $np, 'docs' => $nd,
            'alerte' => $v->statut !== 'en_service' || $np || $nd || ($prochaine && $prochaine['alerte'] !== 'ok'),
        ]];
    });
    $vehApercu = $vehicules->first(fn($v) => $synth[$v->id]['alerte']) ?? $vehicules->first();
    $couleurTaux = fn($t) => $t === null ? 'att' : ($t >= 100 ? 'ok' : ($t >= 80 ? 'wa' : 'ko'));
@endphp

<div class="acc">
{{-- 1. Veille et saisies rapides --}}
<div class="grille acc-haut">
    <div class="panneau">
        <h3>{!! $ico('<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>') !!} Recette du {{ $A['hier']->translatedFormat('l j F') }}
            <x-detail :href="route('parc.index', ['tab' => 'recettes', 'date_recette' => $A['hier']->toDateString()])" titre="recettes de la veille" onclick="changerOnglet('recettes');return false" /></h3>
        <div class="acc-veille">
            <div><span class="v">{{ $fcfa($A['recette_hier']) }}</span><span class="l">encaissé</span></div>
            <div><span class="v">{{ $fcfa($A['objectif_hier']) }}</span><span class="l">objectif</span></div>
            <div><span class="v {{ $couleurTaux($tauxHier) }}">{{ $tauxHier !== null ? $tauxHier . ' %' : '—' }}</span><span class="l">taux</span></div>
            <div><span class="v">{{ $A['saisies_hier'] }}</span><span class="l">saisie(s)</span></div>
        </div>
    </div>
    @if(!$estDG)
    <div class="panneau">
        <h3>{!! $ico('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>') !!} Saisies rapides</h3>
        <div class="acc-rapides">
            <button type="button" class="btn sm" onclick="changerOnglet('recettes')">Recettes</button>
            <button type="button" class="btn sm sec" onclick="document.getElementById('dlg-panne').showModal()">Panne</button>
            <button type="button" class="btn sm sec" onclick="document.getElementById('dlg-depense').showModal()">Dépense</button>
            <button type="button" class="btn sm sec" onclick="document.getElementById('dlg-penal').showModal()">Pénalité</button>
            <button type="button" class="btn sm sec" onclick="document.getElementById('dlg-maint').showModal()">Entretien</button>
            <button type="button" class="btn sm sec" onclick="document.getElementById('dlg-vehicule').showModal()">Véhicule</button>
        </div>
    </div>
    @endif
</div>

{{-- 3. Flotte et aperçu du véhicule choisi --}}
<div class="grille acc-flotte">
    <div class="panneau">
        <h3>{!! $icCar !!} La flotte <span class="att" style="font-weight:400;font-size:12px">cliquez un véhicule pour son aperçu</span>
            <x-detail :href="$lien('vehicules')" titre="véhicules" onclick="changerOnglet('vehicules');return false" /></h3>
        <table class="mini-table">
            <thead><tr><th>Véhicule</th><th>Chauffeur(s)</th><th class="dr">Recettes</th><th class="dr">Taux</th><th></th></tr></thead>
            <tbody>
            @foreach($vehicules as $v)
            @php $sv = $synth[$v->id]; @endphp
            <tr class="clic acc-veh {{ $vehApercu && $v->id === $vehApercu->id ? 'actif' : '' }}" data-veh="{{ $v->id }}" onclick="accApercu({{ $v->id }})">
                <td class="nw"><span class="fort">{{ $v->immatriculation }}</span> <span class="att">{{ $v->categorieLabel() }}</span></td>
                <td class="att">{{ $v->chauffeurs->map(fn($c) => $c->nomComplet())->join(', ') ?: '—' }}</td>
                <td class="dr nw">{{ $fcfa($sv['recette']) }}</td>
                <td class="dr nw {{ $couleurTaux($sv['taux']) }}">{{ $sv['taux'] !== null ? $sv['taux'] . ' %' : '—' }}</td>
                <td class="dr"><i class="acc-pt {{ $sv['alerte'] ? 'ko' : 'ok' }}" title="{{ $sv['alerte'] ? 'Points à suivre' : 'En règle' }}"></i></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @if($A['emprunts']->isNotEmpty() || $A['prets']->isNotEmpty())
        <div class="acc-eng">
            @if($A['emprunts']->isNotEmpty())<a href="{{ $lien('emprunts') }}" onclick="changerOnglet('emprunts');return false">{{ $A['emprunts']->count() }} emprunt(s) en cours · <strong>{{ $fcfa($A['emprunts']->sum(fn($e) => $e->soldeRestant())) }}</strong> à rembourser</a>@endif
            @if($A['prets']->isNotEmpty())<a href="{{ $lien('prets') }}" onclick="changerOnglet('prets');return false">{{ $A['prets']->count() }} prêt(s) accordé(s) · <strong>{{ $fcfa($A['prets']->sum(fn($p) => $p->soldeRestant())) }}</strong> à recevoir</a>@endif
        </div>
        @endif
    </div>

    {{-- Aperçu : synthèse et documents du véhicule sélectionné, sans quitter l'écran --}}
    <div class="panneau acc-apercu apx-zone" id="apx-accueil" data-apx-zone data-type="vehicule" @if($vehApercu) data-id="{{ $vehApercu->id }}" @endif>
        <div class="apx-corps"><div class="apx-charge">{{ $vehApercu ? 'Chargement…' : 'Aucun véhicule.' }}</div></div>
    </div>
</div>

{{-- 4. Activité récente, repliable (le choix est retenu) --}}
<details class="panneau acc-activite" id="acc-activite">
    <summary><h3>{!! $ico('<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>') !!} Activité récente
        <span class="att" style="font-weight:400;font-size:12px">{{ $A['activite']->count() }} dernière(s) action(s){{ $A['activite']->first() ? ' · ' . ($A['activite']->first()->created_at->isToday() ? "aujourd'hui à " . $A['activite']->first()->created_at->format('H:i') : 'le ' . $A['activite']->first()->created_at->format('d/m')) : '' }}</span>
        <span class="acc-chev" aria-hidden="true"></span></h3></summary>
    <table class="mini-table">
        <tbody>
        @forelse($A['activite'] as $l)
        <tr>
            <td class="nw att">{{ $l->created_at->isToday() ? $l->created_at->format('H:i') : $l->created_at->format('d/m H:i') }}</td>
            <td><span class="fort">{{ \App\Services\JournalActivite::libelleModule($l->module) }}</span> · {{ \Illuminate\Support\Str::lower(\App\Services\JournalActivite::libelleAction($l->action)) }}@if($l->cible) <span class="att">{{ \Illuminate\Support\Str::limit($l->cible, 70) }}</span>@endif</td>
            <td class="nw att dr">{{ $l->user_name }}</td>
        </tr>
        @empty
        <tr><td class="att" style="text-align:center;padding:14px">Aucune action récente.</td></tr>
        @endforelse
        </tbody>
    </table>
</details>
</div>

<script>
// Aperçu du véhicule choisi dans la flotte
function accApercu(id){
    document.querySelectorAll('.acc-veh').forEach(function(r){ r.classList.toggle('actif', r.dataset.veh == id); });
    ouvrirApercu('vehicule', id, document.getElementById('apx-accueil'));
}
document.addEventListener('DOMContentLoaded', function(){
    var z=document.getElementById('apx-accueil'); if(z && z.dataset.id) ouvrirApercu('vehicule', z.dataset.id, z);
});
// Activité récente : ouverte ou repliée selon le dernier choix
(function(){
    var d=document.getElementById('acc-activite'); if(!d) return;
    try{ d.open = localStorage.getItem('parc-activite') === 'ouverte'; }catch(e){}
    d.addEventListener('toggle', function(){ try{ localStorage.setItem('parc-activite', d.open ? 'ouverte' : 'repliee'); }catch(e){} });
})();
</script>
