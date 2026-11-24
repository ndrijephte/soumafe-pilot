{{--
    Indicateurs du parc, rangés en trois blocs : Flotte, Finances (période affichée) et À traiter.
    À traiter : un voyant par sujet ouvert (toutes périodes) ; au survol, le détail ; au clic, l'onglet concerné.
--}}
@php
    $A = $accueil;
    $fcfa = fn($n) => number_format((int) $n, 0, ',', ' ') . ' F';
    $maintAlerte = collect($echeancier)->whereIn('alerte', ['en_retard', 'proche'])->values();
    $maintRetard = $maintAlerte->where('alerte', 'en_retard');
    $manquants = collect($alertesManquants ?? []);
    $docsExp = $docsARenouveler->filter(fn($d) => $d->date_expiration->lt(today()));
    $soldePenal = $A['penalites']->sum(fn($p) => $p->soldeRestant());
    $lignes = fn($items, $cle, $val, $n = 3) => collect($items)->take($n)->mapWithKeys(fn($x, $i) => [$cle($x, $i) => $val($x)])->all();
    $sujets = array_values(array_filter([
        $manquants->isNotEmpty() ? [
            'lib' => 'Recettes à saisir', 'n' => $manquants->count() . ' j', 'niveau' => 'retard', 'tab' => 'recettes',
            'url' => route('parc.index', ['tab' => 'recettes', 'date_recette' => $manquants->first()['date']]),
            'titre' => $manquants->count() . ' jour(s) incomplet(s) sur 7 jours',
            'lignes' => $lignes($manquants, fn($m) => ucfirst($m['date_label']), fn($m) => $m['count'] . ' véhicule(s)'),
        ] : null,
        $A['pannes']->isNotEmpty() ? [
            'lib' => 'Pannes ouvertes', 'n' => $A['pannes']->count(), 'niveau' => $A['pannes']->contains('statut', 'declaree') ? 'proche' : 'semaine', 'tab' => 'pannes',
            'titre' => $A['pannes']->count() . ' panne(s) à diagnostiquer ou clôturer',
            'lignes' => $lignes($A['pannes'], fn($p, $i) => $p->vehicule?->immatriculation . str_repeat(' ', $i), fn($p) => \Illuminate\Support\Str::limit($p->description, 28) . ' · ' . $p->statutLabel()),
        ] : null,
        $maintAlerte->isNotEmpty() ? [
            'lib' => 'Entretiens', 'n' => $maintAlerte->count(), 'niveau' => $maintRetard->isNotEmpty() ? 'retard' : 'proche', 'tab' => 'maint',
            'titre' => ($maintRetard->count() ? $maintRetard->count() . ' en retard' : '') . ($maintRetard->count() && $maintAlerte->count() > $maintRetard->count() ? ', ' : '') . ($maintAlerte->count() > $maintRetard->count() ? ($maintAlerte->count() - $maintRetard->count()) . ' sous 15 jours' : ''),
            'lignes' => $lignes($maintAlerte, fn($e, $i) => $e['vehicule']->immatriculation . str_repeat(' ', $i), fn($e) => $e['type_label'] . ' · ' . ($e['jours'] < 0 ? abs($e['jours']) . ' j de retard' : 'dans ' . $e['jours'] . ' j')),
        ] : null,
        $docsARenouveler->isNotEmpty() ? [
            'lib' => 'Pièces à renouveler', 'n' => $docsARenouveler->count(), 'niveau' => $docsExp->isNotEmpty() ? 'retard' : 'proche',
            'js' => "ouvrirDocuments('" . $docsARenouveler->first()->documentable_type . "', " . $docsARenouveler->first()->documentable_id . ")",
            'titre' => ($docsExp->count() ? $docsExp->count() . ' expirée(s), ' : '') . 'assurances, visites, permis…',
            'lignes' => $lignes($docsARenouveler, fn($d, $i) => $d->categorieLibelle() . str_repeat(' ', $i), fn($d) => \App\Models\Document::libelleObjet($d->documentable) . ' · ' . ($d->date_expiration->lt(today()) ? 'expirée' : 'le ' . $d->date_expiration->format('d/m'))),
        ] : null,
        $A['penalites']->isNotEmpty() ? [
            'lib' => 'Pénalités à solder', 'n' => $A['penalites']->count(), 'niveau' => 'semaine', 'tab' => 'penal',
            'titre' => 'Reste à payer : ' . $fcfa($soldePenal),
            'lignes' => $lignes($A['penalites'], fn($p, $i) => ($p->vehicule?->immatriculation ?? '—') . str_repeat(' ', $i), fn($p) => ($p->chauffeur ?: '—') . ' · ' . $fcfa($p->soldeRestant())),
        ] : null,
        $A['recouvrements']->isNotEmpty() ? [
            'lib' => 'Recouvrements', 'n' => $A['recouvrements']->count(), 'niveau' => 'semaine', 'tab' => 'recouv',
            'titre' => 'Reste à recouvrer : ' . $fcfa($A['recouvrements']->sum('solde')),
            'lignes' => $lignes($A['recouvrements']->sortByDesc('solde'), fn($r, $i) => ($r->chauffeur_nom ?: '—') . str_repeat(' ', $i), fn($r) => $fcfa($r->solde)),
        ] : null,
    ]));
    $taux = $kpi['taux_recette'];
    $couleurTaux = $taux >= 100 ? 'var(--ok)' : ($taux >= 80 ? '#b45309' : 'var(--ko)');
@endphp
<div class="parc-ind">
    <section class="ind-bloc">
        <h4>Flotte</h4>
        <div class="ind-vals">
            <div><b>{{ $kpi['actifs'] }}<small>/{{ $kpi['total'] }}</small></b><span>en service</span><em>{{ $kpi['taxis'] }} taxi(s) · {{ $kpi['vtcs'] }} VTC</em></div>
            <div><b>{{ $kpi['pannes_mois'] }}</b><span>panne(s)</span><em>sur la période</em></div>
            <div><b>{{ $kpi['delai_moyen'] ? $kpi['delai_moyen'] . ' j' : '—' }}</b><span>délai de réparation</span><em>moyen</em></div>
        </div>
    </section>
    <section class="ind-bloc">
        <h4>Finances <span>période</span></h4>
        <div class="ind-vals">
            <div><b>{{ $fcfa($kpi['recette_totale']) }}</b><span>recettes</span><em>objectif {{ $fcfa($kpi['objectif_mois']) }}</em></div>
            <div><b style="color:{{ $couleurTaux }}">{{ $taux }} %</b><span>de l'objectif</span><i class="ind-jauge"><i style="width:{{ min(100, $taux) }}%;background:{{ $couleurTaux }}"></i></i></div>
            <div><b>{{ $fcfa($kpi['cout_mois']) }}</b><span>coût des pannes</span>@if($kpi['recouvrement_mois'])<em>recouvré {{ $fcfa($kpi['recouvrement_mois']) }}</em>@endif</div>
        </div>
    </section>
    <section class="ind-bloc ind-traiter">
        <h4>À traiter @if($sujets)<span class="pill p-ko">{{ count($sujets) }}</span>@endif</h4>
        @if(!$sujets)
        <div class="ind-ok"><x-voyant niveau="ok" titre="Rien en attente" /> Tout est à jour</div>
        @else
        <ul>
            @foreach($sujets as $s)
            <li>
                <x-voyant :niveau="$s['niveau']" :titre="$s['titre']" :lignes="$s['lignes']" :lien="$s['url'] ?? (isset($s['tab']) ? route('parc.index', ['tab' => $s['tab']]) : null)" lien-texte="Traiter" />
                @if(isset($s['js']))
                <button type="button" class="ind-lien" onclick="{{ $s['js'] }}">{{ $s['lib'] }}</button>
                @elseif(isset($s['url']))
                <a class="ind-lien" href="{{ $s['url'] }}">{{ $s['lib'] }}</a>
                @else
                <a class="ind-lien" href="{{ route('parc.index', ['tab' => $s['tab']]) }}" onclick="changerOnglet('{{ $s['tab'] }}');return false">{{ $s['lib'] }}</a>
                @endif
                <b>{{ $s['n'] }}</b>
            </li>
            @endforeach
        </ul>
        @endif
    </section>
</div>
