{{-- Dernières recettes de l'aperçu : date, chauffeur ou véhicule, montant et atteinte de l'objectif --}}
@forelse($lignes as $rv)
@php $ok = ($rv->objectif ?? 0) <= 0 || $rv->recette >= $rv->objectif; @endphp
<div class="apx-ligne">
    <span>{{ \Carbon\Carbon::parse($rv->date)->translatedFormat('D d/m') }}</span>
    <span>{{ $colonne === 'vehicule' ? ($rv->vehicule?->immatriculation ?? '—') : ($rv->chauffeur ?: '—') }}</span>
    <span class="{{ $ok ? 'ok' : 'ko' }}">{{ number_format((int) $rv->recette, 0, ',', ' ') }} F</span>
</div>
@empty
<p class="apx-vide">Aucune recette sur la période.</p>
@endforelse
