@if($performance['collaborateurs']->sum('taches_total') === 0)
<p class="perf-vide">Aucune tâche rattachée à cette période.</p>
@else
<div data-collapse="5">
@foreach($performance['collaborateurs'] as $c)
@php $barColor = $c->taux >= 70 ? 'var(--ok)' : ($c->taux >= 40 ? 'var(--warn)' : 'var(--ko)'); @endphp
<div class="membre-line" data-collapse-item>
    <div class="membre-avatar">{{ mb_strtoupper(mb_substr($c->name,0,1).mb_substr($c->prenom ?? '',0,1)) }}</div>
    <div style="flex:1">
        <div style="font-size:13px;font-weight:600">{{ $c->nomComplet() }}</div>
        @if($c->taches_total > 0)
        <div style="font-size:10.5px;color:var(--gris)">
            {{ $c->taches_en_cours }} en cours
            @if($c->taches_bloquees > 0)&middot; <span style="color:var(--ko)">{{ $c->taches_bloquees }} bloq.</span>@endif
            @if($c->taches_en_retard > 0)&middot; <span style="color:var(--ko)">{{ $c->taches_en_retard }} retard</span>@endif
            &middot; {{ $c->taches_terminees }}/{{ $c->taches_total }}
            @if($c->points_livres > 0)&middot; <span style="color:#1d4ed8">{{ $c->points_livres }} pts</span>@endif
        </div>
        <div class="jauge-wrap" style="margin-top:3px;height:4px"><div class="jauge-bar" style="width:{{ $c->taux }}%;background:{{ $barColor }}"></div></div>
        @endif
    </div>
    <span style="font-size:12px;font-weight:700;color:var(--bleu)">{{ $c->taux === null ? '-' : $c->taux . '%' }}</span>
</div>
@endforeach
</div>
@endif
