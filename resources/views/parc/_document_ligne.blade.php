@php $v = $d->validite(); @endphp
<div class="doc{{ $ancien ? ' ancien' : '' }}">
    <span class="doc-ic {{ $ic[0] }}" aria-hidden="true">{{ $ic[1] }}</span>
    <div class="doc-corps">
        <div class="doc-titre">
            @if($d->estApercu())
            <button type="button" class="doc-nom" onclick="apercuDocument('{{ route('parc.documents.apercu', $d) }}', '{{ $d->estPdf() ? 'pdf' : 'img' }}', @js($d->libelle), '{{ route('parc.documents.telecharger', $d) }}')">{{ $d->libelle }}</button>
            @else
            <a class="doc-nom" href="{{ route('parc.documents.telecharger', $d) }}">{{ $d->libelle }}</a>
            @endif
            <span class="doc-cat">{{ $d->categorieLibelle() }}</span>
            @if($v && !$ancien)<span class="doc-validite v-{{ $v['niveau'] }}" title="{{ $v['libelle'] }}"><i></i>{{ $v['niveau'] === 'ok' ? 'Valide' : ($v['jours'] < 0 ? 'Expiré' : ($v['jours'] === 0 ? "Expire aujourd'hui" : 'J-' . $v['jours'])) }}</span>@endif
        </div>
        <div class="doc-meta">
            @if($avecObjet)<span class="doc-objet">{{ \App\Models\Document::libelleObjet($d->documentable) }}</span>@endif
            @if($d->reference)<span>N° {{ $d->reference }}</span>@endif
            @if($d->date_document)<span>du {{ $d->date_document->format('d/m/Y') }}</span>@endif
            @if($d->date_expiration)<span>expire le {{ $d->date_expiration->format('d/m/Y') }}</span>@endif
            <span>{{ $d->tailleLisible() }}</span>
            <span>déposé par {{ $d->deposePar?->name ?? '—' }} le {{ $d->created_at->format('d/m/Y à H:i') }}</span>
        </div>
        @if($d->notes)<div class="doc-notes">{{ $d->notes }}</div>@endif
    </div>
    <div class="doc-act">
        @if($d->estApercu())
        <button type="button" class="doc-b" title="Aperçu" aria-label="Aperçu" onclick="apercuDocument('{{ route('parc.documents.apercu', $d) }}', '{{ $d->estPdf() ? 'pdf' : 'img' }}', @js($d->libelle), '{{ route('parc.documents.telecharger', $d) }}')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        @endif
        <a class="doc-b" href="{{ route('parc.documents.telecharger', $d) }}" title="Télécharger" aria-label="Télécharger"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></a>
        @if(!$ancien && !$avecObjet && $peutDeposer && \App\Models\Document::aExpiration($d->categorie))
        <button type="button" class="doc-b" title="Renouveler" aria-label="Renouveler" onclick="renouvelerDocument(this, {{ $d->id }}, @js($d->libelle), '{{ $d->categorie }}')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 11-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg></button>
        @endif
        @if(!$ancien && $peutRetirer($d))
        <button type="button" class="doc-b del" title="Retirer du dossier" aria-label="Retirer du dossier" onclick="retirerDocument(this, '{{ route('parc.documents.destroy', $d) }}')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></button>
        @endif
    </div>
</div>
