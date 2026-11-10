{{-- Tâches terminées par semaine : $series [['label','count']], $max, $legende --}}
@php
    $totalT = array_sum(array_column($series, 'count'));
    $moyT = count($series) ? round($totalT / count($series), 1) : 0;
@endphp
@once
@push('styles')
<style>
.tdc{position:relative;display:flex;align-items:stretch;gap:14px;height:120px;padding:12px 4px 0;border-bottom:1px solid #cbd5e1;background:repeating-linear-gradient(to top,transparent 0,transparent 29px,#eef2f7 29px,#eef2f7 30px)}
.tdc .col{flex:1;display:flex;flex-direction:column;align-items:center}
.tdc .reste{flex-basis:0;min-height:0}
.tdc .v{flex:none;font-size:11.5px;font-weight:700;color:#1f4e79;margin-bottom:4px}
.tdc .b{flex-basis:0;min-height:2px;width:100%;max-width:38px;border-radius:5px 5px 0 0;background:#bfd3ea}
.tdc .col.cour .b{background:#2e74b5}
.tdc-axe{display:flex;gap:14px;padding:5px 4px 0}
.tdc-axe span{flex:1;text-align:center;font-size:10.5px;color:#64748b}
.tdc-axe span.cour{color:#1f4e79;font-weight:700}
.tdc-pied{display:flex;justify-content:space-between;gap:10px;margin-top:10px;font-size:11.5px;color:#64748b}
.tdc-pied b{color:#1f2937}
.panneau-tendance{display:flex;flex-direction:column}
.panneau-tendance .tdc,.panneau-tendance .tdc-vide{flex:1;height:auto;min-height:120px;max-height:320px}
.tdc-vide{display:flex;align-items:center;justify-content:center;height:120px;border:1px dashed #cbd5e1;border-radius:10px;color:#64748b;font-size:12.5px;text-align:center;padding:0 16px}
</style>
@endpush
@endonce
@if($totalT === 0)
<div class="tdc-vide">Aucune tâche terminée sur les {{ count($series) }} dernières semaines.</div>
@else
<div class="tdc">
    @foreach($series as $i => $s)
    <div class="col {{ $loop->last ? 'cour' : '' }}" title="{{ $s['label'] }} : {{ $s['count'] }} tâche(s) terminée(s)">
        @php $part = (int) round($s['count'] / max(1, $max) * 100); @endphp
        <span class="reste" style="flex-grow:{{ 100 - $part }}"></span>
        <span class="v">{{ $s['count'] }}</span>
        <span class="b" style="flex-grow:{{ $part }}"></span>
    </div>
    @endforeach
</div>
<div class="tdc-axe">@foreach($series as $s)<span class="{{ $loop->last ? 'cour' : '' }}">{{ $s['label'] }}{{ $loop->last ? ' (en cours)' : '' }}</span>@endforeach</div>
@endif
<div class="tdc-pied"><span>{{ $legende }}</span><span><b>{{ $totalT }}</b> au total · <b>{{ $moyT }}</b> / semaine</span></div>
