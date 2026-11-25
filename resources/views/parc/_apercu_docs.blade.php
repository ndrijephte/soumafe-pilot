{{--
    Documents d'un aperçu : les photos en galerie, les autres fichiers en liste ; chacun propose deux actions,
    prévisualiser (PDF, images) et télécharger. Paramètres : $docs, $type, $id, $estDG, $max (nombre affiché).
--}}
@php
    $max ??= 6;
    $photos = $docs->filter(fn($d) => $d->estImage());
    $fichiers = $docs->reject(fn($d) => $d->estImage());
    $ouvrir = fn($d) => "apercuDocument('" . route('parc.documents.apercu', $d) . "', '" . ($d->estPdf() ? 'pdf' : 'img') . "', " . \Illuminate\Support\Js::from($d->libelle) . ", '" . route('parc.documents.telecharger', $d) . "')";
    $icOeil = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    $icDl = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
@endphp
<div class="apx-section">Documents <span>{{ $docs->count() }}</span>
    <button type="button" class="apx-lien" onclick="ouvrirDocuments('{{ $type }}', {{ $id }})">{{ $estDG ? 'Consulter' : 'Gérer' }}</button></div>
@if($docs->isEmpty())
<p class="apx-vide">Aucun document.{{ $estDG ? '' : ' Ajoutez-en depuis « Gérer ».' }}</p>
@endif
@if($photos->isNotEmpty())
<div class="apx-galerie">
    @foreach($photos->take($max) as $d)
    <figure class="apx-vig">
        <button type="button" class="apx-vig-img" style="background-image:url('{{ route('parc.documents.apercu', $d) }}')" onclick="{{ $ouvrir($d) }}" title="{{ $d->libelle }}"></button>
        <figcaption>{{ $d->libelle }}</figcaption>
        <div class="apx-vig-act">
            <button type="button" onclick="{{ $ouvrir($d) }}" title="Prévisualiser" aria-label="Prévisualiser">{!! $icOeil !!}</button>
            <a href="{{ route('parc.documents.telecharger', $d) }}" title="Télécharger" aria-label="Télécharger">{!! $icDl !!}</a>
        </div>
    </figure>
    @endforeach
</div>
@if($photos->count() > $max)<button type="button" class="apx-lien bas" onclick="ouvrirDocuments('{{ $type }}', {{ $id }})">+ {{ $photos->count() - $max }} autre(s) photo(s)</button>@endif
@endif
@foreach($fichiers->take($max) as $d)
@php $vd = $d->validite(); $ext = strtoupper(pathinfo($d->nom_original, PATHINFO_EXTENSION) ?: 'DOC'); @endphp
<div class="apx-doc">
    <span class="apx-ext {{ $d->estPdf() ? 'pdf' : '' }}">{{ $ext }}</span>
    <span class="apx-doc-t"><b>{{ $d->libelle }}</b><span>{{ $d->categorieLibelle() }}{{ $d->date_expiration ? ' · expire le ' . $d->date_expiration->format('d/m/Y') : '' }} · {{ $d->tailleLisible() }}</span></span>
    @if($vd && $vd['niveau'] !== 'ok')<span class="pill {{ $vd['jours'] < 0 ? 'p-ko' : 'p-warn' }}">{{ $vd['jours'] < 0 ? 'Expiré' : 'J-' . $vd['jours'] }}</span>@endif
    <span class="apx-doc-act">
        @if($d->estApercu())<button type="button" onclick="{{ $ouvrir($d) }}" title="Prévisualiser" aria-label="Prévisualiser">{!! $icOeil !!}</button>@endif
        <a href="{{ route('parc.documents.telecharger', $d) }}" title="Télécharger" aria-label="Télécharger">{!! $icDl !!}</a>
    </span>
</div>
@endforeach
@if($fichiers->count() > $max)<button type="button" class="apx-lien bas" onclick="ouvrirDocuments('{{ $type }}', {{ $id }})">+ {{ $fichiers->count() - $max }} autre(s) fichier(s)</button>@endif
