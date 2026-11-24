{{-- Éléments d'exercices antérieurs encore ouverts pour l'onglet $onglet --}}
@if(!empty($reports[$onglet]))
@php $rp = $reports[$onglet]; @endphp
<div class="reports-parc">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    @php $rpMontant = isset($rp['montant']) ? ' (' . number_format($rp['montant'], 0, ',', ' ') . ' F restant)' : ''; @endphp
    <span>En plus de cet exercice : <strong>{{ $rp['nb'] }} {{ $rp['libelle'] }}</strong> d'exercices antérieurs{{ $rpMontant }}.</span>
    <a href="{{ route('parc.index', ['exercice' => 'tous', 'tab' => $onglet]) }}">Voir toutes les périodes →</a>
</div>
@endif
