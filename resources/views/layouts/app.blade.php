<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SOUMAFE PILOT — @yield('titre', 'Tableau de bord')</title>
    <style>
        :root{--bleu:#1F4E79;--accent:#2E74B5;--fond:#F4F7FB;--carte:#fff;--gris:#64748b;--ok:#16a34a;--warn:#f59e0b;--ko:#dc2626;--bord:#e2e8f0}
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Calibri,sans-serif}
        body{background:var(--fond);color:#1e293b;display:flex;min-height:100vh}
        aside{width:230px;background:linear-gradient(180deg,#16385C 0%,#1F4E79 55%,#2E74B5 100%);color:#fff;padding:18px 0;position:fixed;top:0;bottom:0;overflow-y:auto;transition:width .25s ease, transform .25s ease;display:flex;flex-direction:column;z-index:20}
        aside h1{font-size:15px}
        .marque{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding:2px 16px 14px;border-bottom:1px solid rgba(255,255,255,.15);margin-bottom:10px}
        .cercle-logo{width:58px;height:58px;border-radius:50%;background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;flex:none}
        .cercle-logo img{height:34px;width:auto}
        .sub2{font-size:9.5px;opacity:.7}
        aside a,aside .lien-off{display:flex;align-items:center;gap:10px;padding:10px 18px;color:#dbe7f5;text-decoration:none;font-size:13.5px;cursor:pointer}
        aside a:hover{background:rgba(255,255,255,.08)}
        aside a.actif{background:var(--accent);color:#fff;border-left:4px solid #fff;padding-left:14px}
        aside .lien-off{opacity:.38;cursor:default}
        .ic{flex:none}
        .bas-barre{margin-top:auto;border-top:1px solid rgba(255,255,255,.15);padding-top:8px}
        .reduire{opacity:.85}
        main{margin-left:230px;flex:1;padding:22px 26px}
        .topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px}
        .recherche{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--bord);border-radius:8px;padding:0 12px;color:var(--gris);flex:1;max-width:380px}
        .recherche input{border:none;outline:none;padding:8px 0;width:100%;font-size:13px}
        .user{display:flex;align-items:center;gap:8px;font-size:13px;white-space:nowrap}
        .user-info{display:flex;flex-direction:column;align-items:flex-end;line-height:1.3}
        .user-nom{font-weight:600}
        .user-role{font-size:10.5px;color:var(--gris)}
        .avatar{width:34px;height:34px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;flex:none}
        h2{font-size:20px;color:var(--bleu);margin-bottom:4px}
        .crumb{font-size:12px;color:var(--gris);margin-bottom:16px}
        .carte{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px 16px}
        .pill{padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600}
        .p-ok{background:#dcfce7;color:var(--ok)}.p-cours{background:#dbeafe;color:var(--accent)}
        .p-bloque{background:#fee2e2;color:var(--ko)}.p-attente{background:#fef3c7;color:#b45309}
        .typ{font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-right:5px;vertical-align:1px}
        .t1{background:#dbeafe;color:#1d4ed8}.t2{background:#e2e8f0;color:#475569}.t3{background:#fee2e2;color:#b91c1c}
        body.reduit aside{width:64px}
        body.reduit main{margin-left:64px}
        body.reduit .txt,body.reduit .titres{display:none}
        body.reduit aside a,body.reduit aside .lien-off{justify-content:center;padding:12px 0}
        body.reduit aside a.actif{padding-left:0;border-left:4px solid #fff}
        body.reduit .marque{padding:2px 0 14px}
        body.reduit .cercle-logo{width:44px;height:44px}
        body.reduit .cercle-logo img{height:26px}
        body.reduit .chev{transform:rotate(180deg)}
        body.reduit aside a{position:relative}
        body.reduit aside a:hover::after{content:attr(data-tip);position:absolute;left:60px;top:50%;transform:translateY(-50%);background:#16385C;color:#fff;padding:5px 11px;border-radius:6px;font-size:12px;white-space:nowrap;box-shadow:0 2px 8px rgba(0,0,0,.25);z-index:30}

        /* --- Hamburger mobile --- */
        .hamburger{display:none;background:none;border:none;cursor:pointer;padding:6px;color:var(--bleu)}
        .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:15}
        .overlay.visible{display:block}

        @media(max-width:900px){
            aside{transform:translateX(-100%)}
            aside.ouvert{transform:translateX(0)}
            main{margin-left:0}
            body.reduit main{margin-left:0}
            body.reduit aside{transform:translateX(-100%);width:230px}
            body.reduit aside.ouvert{transform:translateX(0)}
            body.reduit .txt,body.reduit .titres{display:inline}
            body.reduit aside a,body.reduit aside .lien-off{justify-content:flex-start;padding:10px 18px}
            .hamburger{display:flex;align-items:center}
            .recherche{max-width:none}
        }
    </style>
    @stack('styles')
</head>
<body>
@php
    $u = auth()->user();
    $initiales = mb_strtoupper(mb_substr($u->name ?? '', 0, 1) . mb_substr($u->prenom ?? '', 0, 1));
    $svg = fn (string $d) => '<svg class="ic" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . $d . '</svg>';
@endphp

<div class="overlay" id="overlay" onclick="fermerMenu()"></div>

<aside id="sidebar">
    <div class="marque">
        <div class="cercle-logo"><img src="{{ asset('images/white_logo_v.png') }}" alt="SOUMAFÊ"></div>
        <div class="titres">
            <h1>SOUMAFE PILOT</h1>
            <div class="sub2">Suivi activités · projets · opérations</div>
        </div>
    </div>
    <a href="{{ route('dashboard') }}" data-tip="Tableau de bord" class="{{ request()->routeIs('dashboard') ? 'actif' : '' }}">
        {!! $svg('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>') !!}<span class="txt">Tableau de bord</span></a>
    @unless($u?->aRole('direction'))
    <a href="{{ route('taches.index') }}" data-tip="Mes activités" class="{{ request()->routeIs('taches.*') ? 'actif' : '' }}">
        {!! $svg('<rect x="3" y="3" width="18" height="18" rx="4"/><polyline points="8 12 11 15 16 9"/>') !!}<span class="txt">Mes activités</span></a>
    @endunless
    <a href="{{ route('sprints.index') }}" data-tip="Projets / Sprints" class="{{ request()->routeIs('sprints.*') ? 'actif' : '' }}">
        {!! $svg('<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/>') !!}<span class="txt">Projets / Sprints</span></a>
    @unless($u?->aRole('direction'))
    <a href="{{ route('saisie.index') }}" data-tip="Suivi saisie" class="{{ request()->routeIs('saisie.*') ? 'actif' : '' }}">
        {!! $svg('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!}<span class="txt">Suivi de saisie</span></a>
    @endunless
    @if($u?->estPilote())
    <a href="{{ route('arbitrages.index') }}" data-tip="Arbitrages" class="{{ request()->routeIs('arbitrages.*') ? 'actif' : '' }}">
        {!! $svg('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>') !!}<span class="txt">Arbitrages</span></a>
    @endif
    @if($u?->estPilote())
    <a href="{{ route('export.index') }}" data-tip="Exports" class="{{ request()->routeIs('export.*') ? 'actif' : '' }}">
        {!! $svg('<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>') !!}<span class="txt">Exports</span></a>
    @endif
    <span class="lien-off">{!! $svg('<circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.2 4.2l2.8 2.8M17 17l2.8 2.8M1 12h4M19 12h4M4.2 19.8L7 17M17 7l2.8-2.8"/>') !!}<span class="txt">Opérations — lot 3</span></span>
    <span class="lien-off">{!! $svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>') !!}<span class="txt">Reporting — lot 3</span></span>
    @if($u?->estAdmin())
    <a href="{{ route('admin.utilisateurs.index') }}" data-tip="Administration" class="{{ request()->is('admin*') ? 'actif' : '' }}">
        {!! $svg('<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>') !!}<span class="txt">Administration</span></a>
    @endif
    <div class="bas-barre">
        <a class="reduire" data-tip="Agrandir" onclick="basculer()">
            {!! $svg('<polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/>') !!}<span class="txt">Réduire</span></a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <a data-tip="Déconnexion" onclick="this.closest('form').submit()">
                {!! $svg('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>') !!}<span class="txt">Déconnexion</span></a>
        </form>
    </div>
</aside>
<main>
    <div class="topbar">
        <button class="hamburger" onclick="ouvrirMenu()" aria-label="Menu">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="recherche">
            {!! $svg('<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>') !!}
            <input placeholder="Rechercher une tâche, un projet, un document…">
        </div>
        <div class="user">
            <div class="notif-wrap" style="position:relative;cursor:pointer" onclick="toggleNotifs()">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                <span id="notif-badge" style="display:none;position:absolute;top:-4px;right:-4px;background:var(--ko);color:#fff;font-size:9px;font-weight:700;min-width:16px;height:16px;border-radius:8px;text-align:center;line-height:16px"></span>
                <div id="notif-panel" style="display:none;position:absolute;right:0;top:32px;width:320px;background:#fff;border:1px solid var(--bord);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:50;max-height:360px;overflow-y:auto">
                    <div style="padding:10px 14px;border-bottom:1px solid var(--bord);display:flex;justify-content:space-between;align-items:center">
                        <strong style="font-size:13px">Notifications</strong>
                        <a href="#" onclick="toutLireNotifs();return false" style="font-size:11px;color:var(--accent);text-decoration:none">Tout marquer lu</a>
                    </div>
                    <div id="notif-list" style="padding:6px 0"></div>
                </div>
            </div>
            <div class="user-info"><span class="user-nom">{{ $u?->nomComplet() }}</span><span class="user-role">{{ ucfirst(str_replace('_', ' ', $u->role ?? '')) }}</span></div>
            <a href="{{ route('profil.index') }}" class="avatar" style="text-decoration:none;color:#fff" title="Mon profil">{{ $initiales }}</a>
        </div>
    </div>
    @yield('contenu')
</main>
<script>
function basculer(){
    document.body.classList.toggle('reduit');
    localStorage.setItem('barre_reduite', document.body.classList.contains('reduit') ? '1' : '0');
}
function ouvrirMenu(){
    document.getElementById('sidebar').classList.add('ouvert');
    document.getElementById('overlay').classList.add('visible');
}
function fermerMenu(){
    document.getElementById('sidebar').classList.remove('ouvert');
    document.getElementById('overlay').classList.remove('visible');
}
if (localStorage.getItem('barre_reduite') === '1') document.body.classList.add('reduit');

// Notifications
function chargerNotifs(){
    fetch('{{ route("api.notifications") }}').then(r=>r.json()).then(data=>{
        const badge=document.getElementById('notif-badge');
        const list=document.getElementById('notif-list');
        if(data.length>0){badge.textContent=data.length;badge.style.display='block'}
        else{badge.style.display='none'}
        list.innerHTML=data.length?data.map(n=>`<div style="padding:8px 14px;border-bottom:1px solid #f1f5f9;font-size:12px;cursor:pointer" onclick="lireNotif(${n.id},this)"><div style="color:#1e293b">${n.contenu}</div><div style="color:var(--gris);font-size:10px;margin-top:2px">${new Date(n.created_at).toLocaleString('fr-FR')}</div></div>`).join(''):'<div style="padding:20px;text-align:center;color:var(--gris);font-size:12px">Aucune notification.</div>';
    }).catch(()=>{});
}
function toggleNotifs(){
    const p=document.getElementById('notif-panel');
    p.style.display=p.style.display==='none'?'block':'none';
    if(p.style.display==='block')chargerNotifs();
}
function lireNotif(id,el){
    fetch(`/api/notifications/${id}/lire`,{method:'PATCH',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''}});
    el.style.opacity='.5';chargerNotifs();
}
function toutLireNotifs(){
    fetch('{{ route("api.notifications.tout-lire") }}',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.content||''}}).then(()=>chargerNotifs());
}
document.addEventListener('click',e=>{if(!e.target.closest('.notif-wrap'))document.getElementById('notif-panel').style.display='none'});
chargerNotifs();setInterval(chargerNotifs,60000);
</script>
@stack('scripts')
</body>
</html>
