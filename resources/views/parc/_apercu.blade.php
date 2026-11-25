{{--
    Aperçu d'un véhicule ou d'un chauffeur (chargé à la demande, sur le même écran) :
    en-tête, informations de base, chiffres de la période, documents, puis « Voir plus » (historique récent, accès à la fiche complète).
--}}
@php
    $fcfa = fn($n) => number_format((int) $n, 0, ',', ' ') . ' F';
    $date = fn($x) => $x ? \Carbon\Carbon::parse($x)->format('d/m/Y') : '—';
    $ico = fn($p, $t = 14) => '<svg viewBox="0 0 24 24" width="' . $t . '" height="' . $t . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $p . '</svg>';
    $recettes ??= collect();
    $obj = (int) $recettes->sum('objectif');
    $taux = $obj > 0 ? round($recettes->sum('recette') / $obj * 100) : null;
    $cTaux = $taux === null ? '' : ($taux >= 100 ? 'ok' : ($taux >= 80 ? 'wa' : 'ko'));
    $photo = $docs->first(fn($x) => $x->estImage() && in_array($x->categorie, ['photo', 'photo_identite'], true)) ?? $docs->first(fn($x) => $x->estImage());
    $periode = $debut->format('d/m') . ' → ' . $fin->format('d/m/Y');
    $idObjet = match ($type) { 'vehicule' => $v->id, 'chauffeur' => $c->id, 'panne' => $pn->id, 'maintenance' => $m->id };
    if ($type === 'panne') {
        $titre = 'Panne · ' . ($pn->vehicule?->immatriculation ?? '?');
        $sous = 'Déclarée le ' . ($pn->date_declaration?->format('d/m/Y') ?? '—') . ' · urgence ' . $pn->urgence;
        [$statutLib, $statutCls] = [$pn->statutLabel(), $pn->statut === 'cloturee' ? 'p-ok' : ($pn->statut === 'declaree' ? 'p-ko' : 'p-cours')];
        $infos = array_filter([
            'Description' => $pn->description, 'Diagnostic' => $pn->diagnostic, 'Décision' => $pn->decision,
            "Début d'intervention" => $pn->date_debut_intervention?->format('d/m/Y'), 'Clôturée le' => $pn->date_cloture?->format('d/m/Y'),
            'Délai' => $pn->delai_resolution !== null ? $pn->delai_resolution . ' j' : null,
            'Coût' => $pn->cout_reel ? $fcfa($pn->cout_reel) . ($pn->emprunt_id ? ' (emprunt)' : '') : null,
        ]);
        $notes = null; $lignes = $pn->lignes;
    } elseif ($type === 'maintenance') {
        $titre = $m->typeLabel() . ' · ' . ($m->vehicule?->immatriculation ?? '?');
        $sous = $m->estRealisee() ? 'Réalisée le ' . ($m->date_realisation?->format('d/m/Y') ?? '—') : 'Prévue le ' . ($m->prochaine_echeance?->format('d/m/Y') ?? '—');
        $enRetard = !$m->estRealisee() && $m->prochaine_echeance && $m->prochaine_echeance->lt(today());
        [$statutLib, $statutCls] = [$m->estRealisee() ? 'Réalisée' : ($enRetard ? 'En retard' : 'Planifiée'), $m->estRealisee() ? 'p-ok' : ($enRetard ? 'p-ko' : 'p-warn')];
        $infos = array_filter([
            'Dernière réalisation' => $m->derniere_realisation?->format('d/m/Y'), 'Prochaine échéance' => $m->prochaine_echeance?->format('d/m/Y'),
            'Prestataire' => $m->prestataire, 'Coût' => $m->cout ? $fcfa($m->cout) : null,
        ]);
        $notes = $m->observations; $lignes = $m->lignes;
    } elseif ($type === 'vehicule') {
        $titre = $v->immatriculation;
        $sous = trim($v->marque . ' ' . $v->modele) . ' · ' . $v->categorieLabel();
        [$statutLib, $statutCls] = [$v->statutLabel(), $v->statut === 'en_service' ? 'p-ok' : ($v->statut === 'immobilise' ? 'p-warn' : 'p-ko')];
        $prochaine = $echeances->first();
        $infos = array_filter([
            'Chauffeur(s)' => $v->chauffeurs->where('pivot.actif', true)->map(fn($c) => $c->nomComplet() . ' (' . ($c->pivot->role ?? 'principal') . ')')->join(', ') ?: '—',
            'Kilométrage' => $v->kilometrage ? number_format($v->kilometrage, 0, ',', ' ') . ' km' : null,
            'Mise en service' => $v->date_mise_en_service?->format('d/m/Y'),
            'Places' => $v->nombre_places,
            'Châssis' => $v->numero_chassis,
            'Propriétaire' => $v->proprietaire,
            'Objectif / jour' => $v->objectif_recette ? $fcfa($v->objectif_recette) : null,
        ]);
        $notes = $v->observations;
    } else {
        $titre = $c->nomComplet();
        $aff = $c->vehicules->where('pivot.actif', true);
        $sous = $aff->isNotEmpty() ? $aff->map(fn($x) => $x->immatriculation . ' (' . ($x->pivot->role ?? 'principal') . ')')->join(', ') : 'Aucun véhicule affecté';
        [$statutLib, $statutCls] = [ucfirst($c->statut), $c->statut === 'actif' ? 'p-ok' : 'p-gris'];
        $infos = array_filter([
            'Téléphone' => $c->telephone,
            'N° de permis' => $c->numero_permis,
            'N° CNI' => $c->numero_cni,
            'Embauché le' => $c->date_embauche?->format('d/m/Y'),
            'Né le' => $c->date_naissance?->format('d/m/Y'),
            'Adresse' => $c->adresse,
            'Contact' => $c->personne_contact,
        ]);
        $notes = $c->observations;
        $resteDu = (int) $recouvrements->sum('solde') + (int) $penalites->sum(fn($p) => $p->soldeRestant());
    }
@endphp
<div class="apx" data-type="{{ $type }}" data-id="{{ $idObjet }}">
    <div class="apx-tete">
        @if($photo)
        <button type="button" class="apx-photo" style="background-image:url('{{ route('parc.documents.apercu', $photo) }}')" title="Agrandir" onclick="apercuDocument('{{ route('parc.documents.apercu', $photo) }}', 'img', @js($photo->libelle), '{{ route('parc.documents.telecharger', $photo) }}')"></button>
        @else
        <span class="apx-photo vide">{!! in_array($type, ['vehicule', 'panne', 'maintenance'], true) ? $ico('<rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H7"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>', 20) : mb_strtoupper(mb_substr($c->prenom ?: $c->nom, 0, 1) . mb_substr($c->nom, 0, 1)) !!}</span>
        @endif
        <div class="apx-id">
            <strong>{{ $titre }}</strong>
            <span>{{ $sous }}</span>
        </div>
        <span class="pill {{ $statutCls }}">{{ $statutLib }}</span>
    </div>

    @if(in_array($type, ['vehicule', 'chauffeur'], true))
    <div class="apx-stats" title="Période : {{ $periode }}">
        <div><b>{{ $fcfa($recettes->sum('recette')) }}</b><span>recettes</span></div>
        <div><b class="{{ $cTaux }}">{{ $taux !== null ? $taux . ' %' : '—' }}</b><span>de l'objectif</span></div>
        @if($type === 'vehicule')
        <div><b class="{{ $pannesOuvertes ? 'wa' : '' }}">{{ $pannesOuvertes }}</b><span>panne(s) ouverte(s)</span></div>
        <div><b class="{{ $prochaine && $prochaine->prochaine_echeance->lt(today()) ? 'ko' : '' }}">{{ $prochaine ? $prochaine->prochaine_echeance->format('d/m') : '—' }}</b><span>{{ $prochaine ? \Illuminate\Support\Str::lower($prochaine->typeLabel()) : 'entretien' }}</span></div>
        @else
        <div><b>{{ $recettes->pluck('date')->unique()->count() }}</b><span>jour(s) travaillé(s)</span></div>
        <div><b class="{{ $resteDu ? 'ko' : '' }}">{{ $fcfa($resteDu) }}</b><span>reste dû</span></div>
        @endif
    </div>
    <div class="apx-periode">{{ $periode }}</div>
    @endif

    @if($infos)
    <dl class="apx-infos">
        @foreach($infos as $lib => $val)<div><dt>{{ $lib }}</dt><dd>{{ $val }}</dd></div>@endforeach
    </dl>
    @endif
    @if($notes)<p class="apx-notes">{{ $notes }}</p>@endif

    @include('parc._apercu_docs', ['docs' => $docs, 'type' => $type, 'id' => $idObjet, 'estDG' => $estDG, 'max' => in_array($type, ['panne', 'maintenance'], true) ? 9 : 4])

    @if(in_array($type, ['panne', 'maintenance'], true))
    @if($lignes->isNotEmpty())
    <div class="apx-section">{{ $type === 'panne' ? "Détail de l'intervention" : 'Facture' }}</div>
    @foreach($lignes as $li)
    <div class="apx-ligne"><span>{{ rtrim(rtrim(number_format((float) $li->quantite, 2, ',', ' '), '0'), ',') }} ×</span><span>{{ $li->designation }}</span><span>{{ $fcfa($li->montant) }}</span></div>
    @endforeach
    <div class="apx-ligne"><span></span><span class="fort">Total</span><span class="fort">{{ $fcfa($lignes->sum('montant')) }}</span></div>
    @endif
    <div class="apx-actions">
        <button type="button" class="btn sm sec" onclick="ouvrirApercu('vehicule', {{ $type === 'panne' ? $pn->vehicule_id : $m->vehicule_id }})">Voir le véhicule</button>
        @if(!$estDG)
        @if($type === 'panne')
        @if($pn->statut !== 'cloturee')<button type="button" class="btn sm" onclick="statuerPanne({{ $pn->id }})">Statuer</button>@endif
        @else
        @if(!$m->estRealisee())<button type="button" class="btn sm" onclick="realiserMaint({{ $m->id }})">Réaliser</button>@endif
        @endif
        @endif
    </div>
    @else
    <details class="apx-plus">
        <summary>Voir plus</summary>
        @if($type === 'vehicule')
            <div class="apx-section">Dernières recettes</div>
            @include('parc._apercu_recettes', ['lignes' => $recettes->take(5), 'colonne' => 'chauffeur'])
            <div class="apx-section">Pannes récentes</div>
            @forelse($pannes as $pn)
            <div class="apx-ligne"><span>{{ $pn->date_declaration?->format('d/m/Y') }}</span><span>{{ \Illuminate\Support\Str::limit($pn->description, 40) }}</span><span class="pill {{ $pn->statut === 'cloturee' ? 'p-ok' : 'p-warn' }}">{{ $pn->statutLabel() }}</span></div>
            @empty<p class="apx-vide">Aucune panne.</p>@endforelse
            <div class="apx-section">Entretiens</div>
            @forelse($echeances as $e)
            <div class="apx-ligne"><span>{{ $e->prochaine_echeance->format('d/m/Y') }}</span><span>{{ $e->typeLabel() }}</span><span class="pill {{ $e->prochaine_echeance->lt(today()) ? 'p-ko' : 'p-cours' }}">{{ $e->prochaine_echeance->lt(today()) ? 'En retard' : 'Prévu' }}</span></div>
            @empty<p class="apx-vide">Aucune échéance planifiée.</p>@endforelse
            @foreach($realisees as $e)
            <div class="apx-ligne"><span>{{ $date($e->date_realisation) }}</span><span>{{ $e->typeLabel() }}</span><span class="pill p-ok">Réalisé</span></div>
            @endforeach
            @if($penalites->isNotEmpty())
            <div class="apx-section">Pénalités à solder</div>
            @foreach($penalites as $pe)
            <div class="apx-ligne"><span>{{ $pe->date_infraction?->format('d/m/Y') }}</span><span>{{ $pe->chauffeur ?: '—' }}</span><span>{{ $fcfa($pe->soldeRestant()) }}</span></div>
            @endforeach
            @endif
            <div class="apx-actions">
                <a class="btn sm sec" href="{{ route('reporting.parc.vehicule', $v) }}">Rapport du véhicule</a>
                <button type="button" class="btn sm" onclick="ficheVehicule({{ $v->id }})">Fiche complète</button>
            </div>
        @else
            <div class="apx-section">Dernières recettes</div>
            @include('parc._apercu_recettes', ['lignes' => $recettes->take(5), 'colonne' => 'vehicule'])
            @if($recouvrements->isNotEmpty())
            <div class="apx-section">Recouvrements en cours</div>
            @foreach($recouvrements as $rc)
            <div class="apx-ligne"><span>{{ $date($rc->date_recette) }}</span><span>{{ $rc->type_recouvrement ? ucfirst(str_replace('_', ' ', $rc->type_recouvrement)) : 'Déficit' }}</span><span>{{ $fcfa($rc->solde) }}</span></div>
            @endforeach
            @endif
            @if($penalites->isNotEmpty())
            <div class="apx-section">Pénalités à solder</div>
            @foreach($penalites as $pe)
            <div class="apx-ligne"><span>{{ $pe->date_infraction?->format('d/m/Y') }}</span><span>{{ $pe->vehicule?->immatriculation }}</span><span>{{ $fcfa($pe->soldeRestant()) }}</span></div>
            @endforeach
            @endif
            <div class="apx-actions">
                <button type="button" class="btn sm" onclick="ficheChauffeur({{ $c->id }})">Résumé complet</button>
            </div>
        @endif
    </details>
    @endif
</div>
