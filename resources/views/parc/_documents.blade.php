{{--
    Dossier documentaire d'une opération du parc (fragment chargé dans le panneau « Documents »).
    Variables : $type, $objet, $titre, $enVigueur, $historique, $lies, $categories, $peutDeposer, $peutRetirer (closure).
--}}
@php
    $icone = function ($d) {
        if ($d->estPdf()) return ['pdf', 'PDF'];
        if ($d->estImage()) return ['img', 'IMG'];
        $ext = strtolower(pathinfo($d->nom_original, PATHINFO_EXTENSION));
        return in_array($ext, ['xls', 'xlsx', 'ods'], true) ? ['xls', strtoupper($ext)] : ['doc', strtoupper($ext ?: 'FIC')];
    };
    $expirables = collect($categories)->filter(fn($c) => \App\Models\Document::aExpiration($c))->values();
    $ligne = function ($d, $avecObjet = false, $ancien = false) use ($icone, $peutDeposer, $peutRetirer, $type, $objet) {
        return view('parc._document_ligne', compact('d', 'avecObjet', 'ancien', 'peutDeposer', 'peutRetirer', 'type', 'objet') + ['ic' => $icone($d)])->render();
    };
    $aRenouveler = $enVigueur->filter(fn($d) => in_array($d->validite()['niveau'] ?? 'ok', ['retard', 'proche', 'semaine'], true));
@endphp
<div class="docs" data-type="{{ $type }}" data-id="{{ $objet->getKey() }}">
    <div class="docs-tete">
        <div>
            <div class="docs-sur">Dossier documentaire</div>
            <h3>{{ $titre }}</h3>
        </div>
        <div class="docs-stats">
            <span><strong>{{ $enVigueur->count() }}</strong> document{{ $enVigueur->count() > 1 ? 's' : '' }}</span>
            @if($aRenouveler->isNotEmpty())<span class="docs-alerte">{{ $aRenouveler->count() }} à renouveler</span>@endif
        </div>
    </div>

    @if($peutDeposer)
    <form class="docs-depot" enctype="multipart/form-data" onsubmit="return deposerDocuments(event, this)" data-action="{{ route('parc.documents.store', [$type, $objet->getKey()]) }}">
        <input type="hidden" name="remplace_id" value="">
        <div class="docs-renouv" hidden>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 11-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
            <span>Renouvellement de <strong class="docs-renouv-nom"></strong> — l'ancienne version restera dans l'historique.</span>
            <button type="button" class="docs-lien" onclick="annulerRenouvellement(this.form)">Annuler</button>
        </div>
        <label class="docs-zone" ondragover="event.preventDefault();this.classList.add('survol')" ondragleave="this.classList.remove('survol')" ondrop="deposerGlisser(event, this)">
            <input type="file" name="fichiers[]" multiple accept=".{{ implode(',.', \App\Models\Document::EXTENSIONS) }}" onchange="fichiersChoisis(this)" required>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            <span class="docs-zone-txt"><strong>Choisir des fichiers</strong> ou les glisser ici</span>
            <span class="docs-zone-aide">PDF, images, Word, Excel — {{ \App\Models\Document::TAILLE_MAX_KO / 1024 }} Mo max. par fichier</span>
        </label>
        <div class="docs-champs">
            <label>Catégorie
                <select name="categorie" required onchange="categorieDocChange(this)">
                    @foreach($categories as $c)
                    <option value="{{ $c }}" data-expire="{{ \App\Models\Document::aExpiration($c) ? 1 : 0 }}">{{ \App\Models\Document::CATEGORIES[$c][0] }}</option>
                    @endforeach
                </select>
            </label>
            <label>Libellé <input type="text" name="libelle" maxlength="200" placeholder="Facultatif"></label>
            <label>N° / référence <input type="text" name="reference" maxlength="100" placeholder="Police, n° facture…"></label>
            <label>Date du document <input type="date" name="date_document"></label>
            <label class="docs-exp" @if(!\App\Models\Document::aExpiration($categories[0] ?? 'autre')) hidden @endif>Expire le <input type="date" name="date_expiration"></label>
            <label class="docs-notes">Notes <input type="text" name="notes" maxlength="2000" placeholder="Facultatif"></label>
        </div>
        <div class="docs-actions">
            <span class="docs-msg" role="status" aria-live="polite"></span>
            <button type="submit" class="docs-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                <span>Joindre</span>
            </button>
        </div>
    </form>
    @endif

    <div class="docs-liste">
        @forelse($enVigueur as $d)
            {!! $ligne($d) !!}
        @empty
            <div class="docs-vide">Aucun document dans ce dossier.@if($expirables->isNotEmpty() && $peutDeposer) Pensez aux pièces à validité : {{ $expirables->map(fn($c) => \App\Models\Document::CATEGORIES[$c][0])->join(', ') }}.@endif</div>
        @endforelse
    </div>

    @if($lies->isNotEmpty())
    <div class="docs-section">Pièces des opérations du véhicule <span>{{ $lies->count() }}</span></div>
    <div class="docs-liste">
        @foreach($lies as $d) {!! $ligne($d, true) !!} @endforeach
    </div>
    @endif

    @if($historique->isNotEmpty())
    <details class="docs-histo">
        <summary>Historique des versions remplacées <span>{{ $historique->count() }}</span></summary>
        <div class="docs-liste">
            @foreach($historique as $d) {!! $ligne($d, false, true) !!} @endforeach
        </div>
    </details>
    @endif
</div>
