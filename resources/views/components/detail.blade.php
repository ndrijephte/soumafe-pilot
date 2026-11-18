{{--
    Accès au détail d'un bloc du tableau de bord : petite icône discrète, en clair au survol.
    <x-detail :href="route('taches.pilote', ['statut' => 'en_retard'])" titre="tâches en retard" />
--}}
@props(['href', 'titre' => null])
@php $libelle = 'Voir le détail' . ($titre ? ' : ' . $titre : ''); @endphp
@once
@push('styles')
<style>
.detail{display:inline-flex;align-items:center;justify-content:center;flex:none;width:26px;height:26px;border-radius:8px;border:1px solid transparent;color:#94a3b8;text-decoration:none;transition:color .15s,background .15s,border-color .15s}
.detail svg{width:15px;height:15px;transition:transform .15s}
.detail:hover,.detail:focus-visible{color:#2e74b5;background:#eef4fb;border-color:#d6e4f3;outline:none}
.detail:hover svg,.detail:focus-visible svg{transform:translate(1px,-1px)}
</style>
@endpush
@endonce
<a href="{{ $href }}" {{ $attributes->merge(['class' => 'detail']) }} title="{{ $libelle }}" aria-label="{{ $libelle }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7"/><path d="M8 7h9v9"/></svg></a>
