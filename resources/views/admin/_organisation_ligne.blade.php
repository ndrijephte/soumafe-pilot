{{-- Une ligne de l'arbre de l'organisation (domaine, service ou pôle) --}}
@php $noms = ['domaine' => 'Domaine', 'service' => 'Service', 'pole' => 'Pôle']; @endphp
<div class="og-ligne {{ $niveau }}" data-niveau="{{ $niveau }}" data-id="{{ $item->id }}">
    <div class="og-nom">
        <span class="niv">{{ $noms[$niveau] }}</span>
        <input class="og-lib" value="{{ $item->libelle }}" data-ancien="{{ $item->libelle }}" maxlength="100" data-sans-controle aria-label="Nom">
        <span class="og-ok">enregistré</span>
    </div>
    <div>
        @if($parents)
        <select class="og-parent" data-ancien="{{ $parentId }}" data-sans-controle aria-label="Rattaché à">
            <option value="">— Non rattaché —</option>
            @foreach($parents as $par)<option value="{{ $par->id }}" @selected((int) $parentId === (int) $par->id)>{{ $par->libelle }}</option>@endforeach
        </select>
        @endif
    </div>
    <div class="og-compte">{{ $compte ?: 'vide' }}</div>
    <div class="og-actions">
        @if($enfant)
        <button type="button" class="ib" data-sans-icone title="Ajouter un {{ $enfant === 'pole' ? 'pôle' : 'service' }}" aria-label="Ajouter" onclick="ogAjouter('ajout-{{ $enfant }}-{{ $item->id }}')">{!! $icPlus !!}</button>
        @endif
        <form method="POST" action="{{ route('admin.organisation.destroy', [$niveau, $item->id]) }}" style="display:inline" onsubmit="return confirm('Supprimer « {{ addslashes($item->libelle) }} » ?')">
            @csrf @method('DELETE')
            <button class="ib ko" data-sans-icone @disabled($plein) title="{{ $plein ? 'Non vide : videz d\'abord cette branche' : 'Supprimer' }}" aria-label="Supprimer">{!! $icSuppr !!}</button>
        </form>
    </div>
</div>
