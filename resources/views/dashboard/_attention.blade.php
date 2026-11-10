@if(!empty($alertes))
@php $ordre = ['ko' => 0, 'warn' => 1, 'info' => 2]; $triees = collect($alertes)->sortBy(fn($a) => $ordre[$a['type']] ?? 3)->values(); $autres = $triees->count() - 3; @endphp
<div class="attention">
    <div class="attention-tete">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Points d'attention <span class="nb">{{ $triees->count() }}</span>
        @if($autres > 0)
        <button type="button" class="attention-plus" aria-expanded="false" onclick="this.setAttribute('aria-expanded', this.closest('.attention').classList.toggle('ouvert'))">
            <span class="replie">Voir {{ $autres > 1 ? "les $autres autres" : "l'autre" }}</span><span class="deplie">Réduire</span>
        </button>
        @endif
    </div>
    <ul>
        @foreach($triees as $a)
        <li class="{{ $a['type'] }}"><i></i><span>{{ $a['msg'] }}</span></li>
        @endforeach
    </ul>
</div>
@endif
