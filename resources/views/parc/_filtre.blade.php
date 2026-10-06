{{--
    Barre de filtres d'une liste du parc (filtrage côté navigateur).
    $cible  : id du conteneur dont les éléments [data-ligne] sont filtrés
    $champs : ['exercice' => clé par défaut ou '', 'vehicule' => true, 'chauffeur' => true,
               'statut' => ['Tous les statuts', [code => libellé]], 'type' => [...], 'categorie' => [...],
               'recherche' => 'placeholder']
--}}
<div class="filtre-bar" data-cible="{{ $cible }}">
    @if(array_key_exists('exercice', $champs))
    <select data-f="exercice" onchange="filtrerListe('{{ $cible }}')" title="Exercice : du 05 du mois au 04 du mois suivant">
        <option value="">Toutes les périodes</option>
        @foreach($exercicesParc as $cle => $ex)
        <option value="{{ $cle }}" data-debut="{{ $ex['debut'] }}" data-fin="{{ $ex['fin'] }}" @selected($champs['exercice'] === $cle)>Exercice {{ $ex['libelle'] }}</option>
        @endforeach
    </select>
    @endif
    @if(!empty($champs['vehicule']))
    <select data-f="vehicule" onchange="filtrerListe('{{ $cible }}')">
        <option value="">Tous les véhicules</option>
        @foreach($vehicules as $fv)<option value="{{ $fv->id }}">{{ $fv->immatriculation }}</option>@endforeach
    </select>
    @endif
    @if(!empty($champs['chauffeur']))
    <select data-f="chauffeur" onchange="filtrerListe('{{ $cible }}')">
        <option value="">Tous les chauffeurs</option>
        @foreach($chauffeurs as $fc)<option value="{{ $fc->nomComplet() }}">{{ $fc->nomComplet() }}</option>@endforeach
    </select>
    @endif
    @foreach(['categorie', 'type', 'statut'] as $cleF)
    @if(!empty($champs[$cleF]))
    <select data-f="{{ $cleF }}" onchange="filtrerListe('{{ $cible }}')">
        <option value="">{{ $champs[$cleF][0] }}</option>
        @foreach($champs[$cleF][1] as $code => $lib)<option value="{{ $code }}">{{ $lib }}</option>@endforeach
    </select>
    @endif
    @endforeach
    <input type="search" data-f="q" placeholder="{{ $champs['recherche'] ?? 'Rechercher…' }}" oninput="filtrerListe('{{ $cible }}')" style="min-width:170px">
    <button type="button" class="btn sm sec" onclick="effacerFiltres('{{ $cible }}')" style="padding:4px 10px;font-size:11px">Effacer</button>
    <span class="filtre-resultat" data-resultat></span>
</div>
