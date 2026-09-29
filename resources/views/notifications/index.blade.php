@extends('layouts.app')
@section('sousTitre', 'Mes notifications')
@section('titre', 'Notifications')

@push('styles')
<style>
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;justify-content:center}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.notif-filtres{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.notif-filtres a{padding:6px 14px;border-radius:8px;font-size:12.5px;text-decoration:none;border:1px solid var(--bord);color:var(--gris);background:#fff;font-weight:600;transition:.2s}
.notif-filtres a.actif{background:var(--accent);color:#fff;border-color:var(--accent)}
.notif-filtres a:hover{border-color:var(--accent)}
.notif-card{background:#fff;border:1px solid var(--bord);border-radius:10px;padding:12px 16px;margin-bottom:8px;display:flex;gap:12px;align-items:flex-start;transition:.15s}
.notif-card:hover{border-color:var(--accent);box-shadow:0 2px 8px rgba(0,0,0,.06)}
.notif-card.non-lue{border-left:3px solid var(--accent);background:#f8fafc}
.notif-ico{width:36px;height:36px;border-radius:50%;background:var(--fond);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.notif-ico svg{width:18px;height:18px;stroke:var(--accent);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.notif-body{flex:1;min-width:0}
.notif-txt{font-size:13px;color:#1e293b;line-height:1.5}
.notif-meta{font-size:11px;color:var(--gris);margin-top:4px;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.notif-actions{display:flex;gap:6px;align-items:center;flex-shrink:0}
.notif-lien{font-size:11.5px;color:var(--accent);text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:4px}
.notif-lien:hover{text-decoration:underline}
.pagination-wrap{display:flex;justify-content:center;gap:4px;margin-top:16px}
.pagination-wrap a,.pagination-wrap span{padding:6px 12px;border-radius:6px;font-size:12px;text-decoration:none;border:1px solid var(--bord);color:var(--gris)}
.pagination-wrap a:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
.pagination-wrap .current{background:var(--accent);color:#fff;border-color:var(--accent)}
@media(max-width:600px){
    .notif-card{padding:10px 12px;flex-direction:column;gap:8px}
    .notif-ico{width:28px;height:28px}
    .notif-ico svg{width:14px;height:14px}
    .notif-actions{flex-direction:row;align-self:flex-end}
    .notif-filtres{gap:6px}
    .notif-filtres a{padding:5px 10px;font-size:11px}
    .notif-meta{flex-direction:column;gap:4px}
    .notif-txt{font-size:12.5px}
    .pagination-wrap a,.pagination-wrap span{padding:5px 8px;font-size:11px}
}
</style>
@endpush

@section('contenu')
@php
    $ic = fn(string $d) => '<svg viewBox="0 0 24 24">'.$d.'</svg>';
    $typeIcons = [
        'tache'        => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'observation'  => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
        'sprint'       => '<polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/>',
        'blocage'      => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>',
        'planning'     => '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'parc'         => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'default'      => '<path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/>',
    ];
@endphp

<h2 style="margin-bottom:4px">Mes notifications</h2>
<p style="color:var(--gris);font-size:13px;margin-bottom:16px">Historique complet de vos notifications</p>

<div class="notif-filtres">
    <a href="{{ route('notifications.index', ['filtre' => 'toutes']) }}" class="{{ $filtre === 'toutes' ? 'actif' : '' }}">Toutes</a>
    <a href="{{ route('notifications.index', ['filtre' => 'non_lues']) }}" class="{{ $filtre === 'non_lues' ? 'actif' : '' }}">Non lues</a>
    <a href="{{ route('notifications.index', ['filtre' => 'lues']) }}" class="{{ $filtre === 'lues' ? 'actif' : '' }}">Lues</a>
    <a href="#" onclick="toutMarquerLu();return false" class="btn sm sec" style="margin-left:auto">Tout marquer lu</a>
</div>

@if($notifications->isEmpty())
<div style="text-align:center;padding:60px 20px;color:var(--gris)">
    <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom:12px;opacity:.4"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
    <p style="font-size:14px;font-weight:600">Aucune notification</p>
    <p style="font-size:12px">{{ $filtre === 'non_lues' ? 'Vous avez tout lu.' : 'Rien pour le moment.' }}</p>
</div>
@else
@foreach($notifications as $n)
@php
    $iconKey = 'default';
    foreach (['tache', 'observation', 'sprint', 'blocage', 'planning', 'parc'] as $k) {
        if (str_contains(strtolower($n->type ?? ''), $k)) { $iconKey = $k; break; }
    }
@endphp
<div class="notif-card {{ $n->lu ? '' : 'non-lue' }}">
    <div class="notif-ico">{!! $ic($typeIcons[$iconKey]) !!}</div>
    <div class="notif-body">
        <div class="notif-txt">{!! $n->contenu !!}</div>
        <div class="notif-meta">
            <span>{{ $n->created_at->locale('fr')->diffForHumans() }}</span>
            <span>{{ $n->created_at->format('d/m/Y H:i') }}</span>
            @if($n->type)<span style="background:var(--fond);padding:1px 8px;border-radius:4px;font-size:10px;font-weight:600">{{ $n->type }}</span> @endif
        </div>
    </div>
    <div class="notif-actions">
        @if($n->lien)
        <a href="{{ $n->lien }}" class="notif-lien" onclick="marquerLu({{ $n->id }})">
            Voir <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
        @endif
        @if(!$n->lu)
        <button class="btn sm sec" onclick="marquerLu({{ $n->id }},this)" style="font-size:10px;padding:3px 8px">Lu</button>
        @endif
    </div>
</div>
@endforeach

@if($notifications->hasPages())
<div class="pagination-wrap">
    @if($notifications->onFirstPage())
        <span>&laquo;</span>
    @else
        <a href="{{ $notifications->previousPageUrl() }}">&laquo;</a>
    @endif
    @foreach($notifications->getUrlRange(max(1,$notifications->currentPage()-2), min($notifications->lastPage(),$notifications->currentPage()+2)) as $page => $url)
        @if($page == $notifications->currentPage())
            <span class="current">{{ $page }}</span>
        @else
            <a href="{{ $url }}">{{ $page }}</a>
        @endif
    @endforeach
    @if($notifications->hasMorePages())
        <a href="{{ $notifications->nextPageUrl() }}">&raquo;</a>
    @else
        <span>&raquo;</span>
    @endif
</div>
@endif
@endif
@endsection

@push('scripts')
<script>
function marquerLu(id, btn){
    fetch(`/api/notifications/${id}/lire`,{method:'PATCH',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''}}).then(()=>{
        if(btn){const card=btn.closest('.notif-card');if(card)card.classList.remove('non-lue');btn.remove();}
        chargerNotifs();
    });
}
function toutMarquerLu(){
    fetch('{{ route("api.notifications.tout-lire") }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''}}).then(()=>location.reload());
}
</script>
@endpush
