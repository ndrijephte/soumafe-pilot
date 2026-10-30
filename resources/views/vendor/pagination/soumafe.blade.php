@if ($paginator->hasPages())
@once
<style>
.pag-sm{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:14px;font-size:12.5px}
.pag-sm .pag-info{color:var(--gris,#64748b)}
.pag-sm ul{display:flex;align-items:center;gap:4px;list-style:none;margin:0;padding:0;flex-wrap:wrap}
.pag-sm li{list-style:none;margin:0}
.pag-sm a,.pag-sm span.pg{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 9px;border:1px solid var(--bord,#e2e8f0);border-radius:8px;background:#fff;color:#334155;text-decoration:none;box-sizing:border-box;transition:background .12s,border-color .12s}
.pag-sm a:hover{background:#eef4fb;border-color:#bfdbfe;color:#2e74b5}
.pag-sm .actif span.pg{background:#2e74b5;border-color:#2e74b5;color:#fff;font-weight:600}
.pag-sm .off span.pg{color:#cbd5e1;background:#f8fafc}
.pag-sm .ell span.pg{border:none;background:none;min-width:18px;padding:0;color:#94a3b8}
@media(max-width:600px){.pag-sm{justify-content:center}.pag-sm .pag-info{width:100%;text-align:center}.pag-sm li.num:not(.actif){display:none}.pag-sm .ell{display:none}}
</style>
@endonce
<nav class="pag-sm" role="navigation" aria-label="Pagination">
    <div class="pag-info">
        Page {{ $paginator->currentPage() }} sur {{ $paginator->lastPage() }}
        @if(method_exists($paginator, 'total')) · {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }}@endif
    </div>
    <ul>
        @if ($paginator->onFirstPage())
            <li class="off"><span class="pg" aria-hidden="true">‹ Précédent</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Précédent</a></li>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="ell"><span class="pg">{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="num actif" aria-current="page"><span class="pg">{{ $page }}</span></li>
                    @else
                        <li class="num"><a href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant ›</a></li>
        @else
            <li class="off"><span class="pg" aria-hidden="true">Suivant ›</span></li>
        @endif
    </ul>
</nav>
@endif
