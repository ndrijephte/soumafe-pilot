@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Espace projet - ' . $projet->libelle)

@push('styles')
<style>
.ep-tete{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:14px}
.ep-tete .meta{font-size:13px;color:var(--gris);margin-top:2px}
.ep-tabs{display:flex;gap:4px;border-bottom:2px solid var(--bord);margin-bottom:16px;flex-wrap:wrap}
.ep-tab{background:none;border:none;padding:9px 16px;font-size:13.5px;font-weight:600;color:var(--gris);cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px}
.ep-tab.actif{color:var(--bleu);border-bottom-color:var(--accent)}
.ep-tab .nb{background:#e2e8f0;color:#475569;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px}
.ep-panel{display:none}.ep-panel.actif{display:block}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11.5px}
.btn.ko{background:#fff;color:var(--ko);border:1px solid #fecaca}
.alerte{border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;vertical-align:middle}
.tbl td{padding:7px 10px;border-bottom:1px solid var(--bord);vertical-align:middle}
.tbl tbody tr:hover td{background:#f8fafc}
.tbl td form{margin:0}
.tbl tr.inactif td{opacity:.5}
.pill{display:inline-block;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.p-ok{background:#dcfce7;color:#166534}.p-cours{background:#dbeafe;color:#1d4ed8}.p-plan{background:#ede9fe;color:#6d28d9}.p-attente{background:#f1f5f9;color:#475569}.p-haute{background:#fee2e2;color:#991b1b}.p-moyenne{background:#fef3c7;color:#92400e}.p-basse{background:#f1f5f9;color:#475569}
.module-bloc{background:#fff;border:1px solid var(--bord);border-radius:12px;margin-bottom:14px;overflow:hidden}
.module-titre{display:grid;grid-template-columns:minmax(0,1fr) auto 140px;gap:14px;align-items:center;padding:12px 16px;background:linear-gradient(#fbfcfe,#f3f6fa);border-bottom:1px solid var(--bord)}
.module-titre .nom{font-size:14px;font-weight:700;color:#1f4e79;text-transform:uppercase;letter-spacing:.5px}
.module-titre .stats,.epic-titre .stats{font-size:12px;color:#475569;white-space:nowrap}
.module-corps{padding:10px 14px 4px}
.epic-titre{display:grid;grid-template-columns:minmax(0,1fr) auto 110px;gap:12px;align-items:center;margin:6px 0 8px;padding:6px 0 6px 10px;border-left:3px solid #93c5fd}
.epic-titre .nom{font-size:14.5px;font-weight:700;color:#1f4e79}
.epic-titre .ren{background:none;border:none;padding:2px 4px;margin-left:6px;color:#94a3b8;cursor:pointer;vertical-align:middle;border-radius:5px;opacity:0;transition:opacity .15s}
.epic-titre:hover .ren,.epic-titre .ren:focus-visible{opacity:1}
.epic-titre .ren:hover{color:#2e74b5;background:#eef4fb}
.epic-titre .nom small{font-weight:700;color:#2e74b5;text-transform:uppercase;letter-spacing:.4px;font-size:11px;margin-right:8px}
.prog{height:6px;background:#e5eaf1;border-radius:6px;overflow:hidden}.prog>i{display:block;height:100%;background:#2e74b5;border-radius:6px}
.prog.fini>i{background:#16a34a}
.pct{font-size:11.5px;font-weight:700;color:#1f4e79;margin-left:6px}
.puiser{display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:8px 12px;margin-bottom:12px;background:#f8fafc;border:1px solid var(--bord);border-radius:10px;font-size:12.5px;color:#475569}
.puiser select{padding:5px 8px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.puiser .btn:disabled{opacity:.5;cursor:not-allowed}
.us-coche{flex:none;margin-top:3px;width:15px;height:15px;accent-color:#2e74b5;cursor:pointer}
.us-coche:disabled{cursor:not-allowed;opacity:.4}
.tout-coche{width:15px;height:15px;accent-color:#2e74b5;cursor:pointer;margin:0}
.pb-tbl tr.us.choisie>td{background:#f5f9ff}
.sel-aide{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:8px 12px;margin-bottom:12px;background:#f8fafc;border:1px solid var(--bord);border-radius:10px;font-size:12.5px;color:#475569}
.sel-aide svg{width:15px;height:15px;color:#2e74b5;flex:none}
.sel-aide .lien{background:none;border:0;padding:0;font:inherit;font-weight:600;color:#2e74b5;cursor:pointer}
.sel-aide .lien:hover{text-decoration:underline}
/* Barre d'actions de la sélection */
.barre-us{position:fixed;left:50%;bottom:18px;transform:translate(-50%,160%);z-index:40;width:max-content;display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:center;max-width:calc(100vw - 32px);background:#fff;border:1px solid #cbd5e1;border-radius:14px;box-shadow:0 14px 36px rgba(15,23,42,.2);padding:8px 10px;transition:transform .2s;font-size:12.5px}
.barre-us.visible{transform:translate(-50%,0)}
.barre-us .nb{font-weight:700;color:#1f4e79;padding:0 8px 0 4px;white-space:nowrap}
.barre-us button,.barre-us select{border:1px solid #e2e8f0;background:#fff;color:#334155;border-radius:8px;padding:6px 11px;font:inherit;font-weight:600;cursor:pointer;white-space:nowrap}
.barre-us button:hover{border-color:#93c5fd;background:#f8fafc}
.barre-us button.prim{background:#2e74b5;border-color:#2e74b5;color:#fff}.barre-us button.prim:hover{background:#245f96}
.barre-us button.ko{color:#b91c1c}.barre-us button.ko:hover{border-color:#fecaca;background:#fef2f2}
.barre-us .sep{width:1px;height:22px;background:#e2e8f0;margin:0 2px}
.barre-us .grp{display:inline-flex;align-items:center;gap:4px}
.barre-us button.fermer{border:0;color:#64748b;padding:6px 8px}
#dlg-groupe{width:92vw;max-width:440px;border:none;border-radius:14px;padding:22px;box-shadow:0 12px 40px rgba(15,23,42,.2)}
#dlg-groupe h3{margin:0 0 4px;font-size:16px;color:#1f4e79}
#dlg-groupe .aide{font-size:12.5px;color:var(--gris);margin:0 0 14px}
#dlg-groupe label{display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:10px}
#dlg-groupe input[type=text],#dlg-groupe select,#dlg-groupe textarea{display:block;width:100%;margin-top:4px;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font:inherit;font-size:13px;font-weight:400;box-sizing:border-box}
#dlg-groupe textarea{min-height:80px;resize:vertical}
#dlg-groupe label{text-transform:none;letter-spacing:0}
#dlg-groupe .ligne-case{display:flex;flex-direction:row;align-items:center;justify-content:flex-start;gap:8px;font-size:12.5px;font-weight:600;color:#334155;margin:4px 0 6px;cursor:pointer}
#dlg-groupe .ligne-case input{width:auto;flex:none;margin:0;accent-color:#2e74b5}
#dlg-groupe .liste{max-height:120px;overflow:auto;font-size:12px;color:#475569;background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:6px 10px;margin-bottom:12px}
#dlg-groupe .pied{display:flex;justify-content:flex-end;gap:8px;margin-top:6px}
.ecartees-us{margin:0 0 10px;padding:9px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:12.5px;color:#92400e}
.ecartees-us ul{margin:4px 0 0 18px;padding:0}
.us-prog{display:flex;align-items:center;gap:8px;min-width:150px}.us-prog .prog{flex:1}
@media(max-width:700px){.module-titre,.epic-titre{grid-template-columns:1fr}.module-titre .stats,.epic-titre .stats{white-space:normal}}
.pb-tbl{width:100%;border-collapse:collapse;table-layout:fixed;font-size:13px;margin-bottom:10px;border:1px solid var(--bord)}
.pb-tbl .c-coche{width:30px}.pb-tbl .c-id{width:72px}.pb-tbl .c-profil{width:118px}.pb-tbl .c-moscow{width:98px}.pb-tbl .c-pts{width:44px}.pb-tbl .c-dor{width:48px}.pb-tbl .c-statut{width:108px}.pb-tbl .c-exec{width:176px}.pb-tbl .c-act{width:60px}
.pb-tbl th{text-align:left;padding:8px;font-size:12px;font-weight:700;color:#12355b;background:#e8f0f9;border-bottom:2px solid #bfd3ea;border-right:1px solid #d3e0ef;white-space:nowrap}
.pb-tbl th:last-child{border-right:0}
.pb-tbl td{padding:8px;border-bottom:1px solid #e2e8f0;border-right:1px solid #eef2f7;vertical-align:middle;overflow-wrap:anywhere}
.pb-tbl th{vertical-align:middle}
/* Colonnes courtes centrées (case, ID, MoSCoW, points, DoR, statut, exécution, actions) ; profil et user story à gauche */
@media(min-width:901px){
.pb-tbl th:nth-child(1),.pb-tbl th:nth-child(2),.pb-tbl th:nth-child(n+5),
.pb-tbl td:nth-child(1),.pb-tbl td:nth-child(2),.pb-tbl td:nth-child(n+5){text-align:center}
.pb-tbl td.td-exec>*{margin-left:auto;margin-right:auto}
.pb-tbl td.td-act{text-align:center}
.pb-tbl td.td-act form,.pb-tbl td.td-act .ico{vertical-align:middle}
}
.pb-tbl td:last-child{border-right:0}
.pb-tbl tbody tr:hover td{background:#f8fafc}
.us-num{font-weight:700;color:var(--accent);white-space:nowrap}
.td-profil{color:#334155}.td-pts{font-weight:700;color:#1f4e79}
.us-txt{display:block;color:#1e293b;line-height:1.4}
.us-meta{display:flex;gap:2px 10px;flex-wrap:wrap;margin-top:3px;font-size:11px;color:var(--gris)}
.lien-det{background:none;border:0;padding:0;font:inherit;color:var(--accent);font-weight:600;cursor:pointer}
.us-detail{display:none;margin-top:6px;padding-top:6px;border-top:1px dashed var(--bord);font-size:12px;color:#334155}
.us.ouvert .us-detail{display:block}
.ms{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}.ms-must{background:#fee2e2;color:#991b1b}.ms-should{background:#fef3c7;color:#92400e}.ms-could{background:#f1f5f9;color:#475569}.ms-wont{background:#f8fafc;color:#94a3b8}
.dor{font-size:11.5px;font-weight:600;color:#94a3b8}.dor.oui{color:#166534}
.td-exec .niv .org{display:none}.td-exec .niv .nb{width:46px}
.td-act{white-space:nowrap}.td-act form{display:inline}
.ico{background:none;border:0;padding:3px;color:#64748b;cursor:pointer;border-radius:5px;vertical-align:middle}.ico:hover{background:#eef4fb;color:var(--accent)}.ico.ko:hover{background:#fef2f2;color:var(--ko)}
.td-coche{text-align:center}
.ico:disabled{opacity:.35;cursor:not-allowed}.ico:disabled:hover{background:none;color:#64748b}
.archives-us{background:#fff;border:1px solid var(--bord);border-radius:12px;margin-top:6px}
.archives-us>summary{cursor:pointer;padding:11px 14px;font-size:13px;font-weight:600;color:#475569;list-style:none;display:flex;align-items:center;gap:8px}
.archives-us>summary::-webkit-details-marker{display:none}
.archives-us>summary::before{content:'';width:6px;height:6px;border-right:2px solid #94a3b8;border-bottom:2px solid #94a3b8;transform:rotate(-45deg);transition:transform .15s}
.archives-us[open]>summary::before{transform:rotate(45deg)}
.archives-us>summary .nb{background:#f1f5f9;color:#64748b;border-radius:10px;padding:1px 8px;font-size:11px}
.archives-us>summary small{font-weight:400;color:var(--gris);margin-left:auto}
.arch-tbl{width:100%;border-collapse:collapse;font-size:12.5px;border-top:1px solid var(--bord)}
.arch-tbl th{padding:7px 10px;font-size:11.5px;font-weight:700;color:#12355b;background:#f3f7fc;text-align:center;border-bottom:1px solid #d3e0ef}
.arch-tbl td{padding:8px 10px;border-bottom:1px solid #eef2f7;vertical-align:middle;text-align:center;color:#475569}
.arch-tbl tr:last-child td{border-bottom:0}
.arch-tbl .g{text-align:left}.arch-tbl .us-txt{color:#64748b}
@media(max-width:900px){
.pb-tbl,.pb-tbl tbody{display:block}.pb-tbl thead,.pb-tbl colgroup{display:none}
.pb-tbl tr.us{display:flex;flex-wrap:wrap;gap:6px 14px;padding:10px 0;border-bottom:1px solid var(--bord)}
.pb-tbl td{display:block;padding:0;border:0}.pb-tbl td.td-story{flex:0 0 100%;order:-1}.pb-tbl td.td-coche{order:-3}.pb-tbl td.us-num{order:-2}
.pb-tbl td[data-lib]::before{content:attr(data-lib);display:block;font-size:10px;font-weight:700;color:#94a3b8}
.td-profil{flex:0 0 auto}
}
.filtre{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center}
.filtre select,.filtre input{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.sp-cartes{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
.sp-carte{display:block;background:#fff;border:1px solid var(--bord);border-radius:12px;padding:14px;text-decoration:none;color:inherit}
.sp-carte:hover{border-color:var(--accent);box-shadow:0 2px 8px rgba(46,116,181,.12)}
.sp-lien{display:block;text-decoration:none;color:inherit}
.sp-ceremonies{display:flex;gap:4px;flex-wrap:wrap;margin-top:10px;padding-top:9px;border-top:1px dashed var(--bord)}
.sp-ceremonies a{font-size:11.5px;font-weight:600;color:var(--accent);text-decoration:none;padding:3px 8px;border-radius:6px;background:#eff6ff}
.sp-ceremonies a:hover{background:#dbeafe}
.sp-ceremonies a.xl{margin-left:auto;background:#dcfce7;color:#166534}
/* Blocs de même taille ; un bandeau d'état en tête de chaque bloc. En cours : mis en avant et placé en premier.
   Clôturé (fin de la planification) et arrêté (interruption, avec son motif) se distinguent dans l'historique. */
.sp-carte{display:flex;flex-direction:column;transition:box-shadow .2s,border-color .2s}
.sp-carte .sp-lien{flex:1}
.sp-etat{display:flex;align-items:center;gap:7px;margin:-14px -14px 10px;padding:7px 14px;border-radius:11px 11px 0 0;font-size:10.5px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;border-bottom:1px solid transparent}
.sp-etat svg{width:13px;height:13px;flex:none}
.sp-etat .droite{margin-left:auto;font-weight:700;letter-spacing:.2px;text-transform:none;font-size:11px}
.sp-carte.encours{order:-1;background:#F2F7FD;border:1.5px solid #2e74b5;box-shadow:0 4px 16px -6px rgba(46,116,181,.4)}
.sp-carte.encours .sp-etat{background:#2e74b5;color:#fff}
.sp-carte.encours .sp-etat .droite{background:rgba(255,255,255,.18);padding:2px 9px;border-radius:999px}
.sp-carte.encours .sp-etat .droite.retard{background:#dc2626}
.sp-carte.encours .sp-titre{color:#1f4e79}
.sp-carte.encours .sp-obj{color:#1f4e79!important;font-weight:600}
.sp-carte.encours .barre{background:#dbe7f5}.sp-carte.encours .barre>div{background:#2e74b5}
.sp-carte.encours .sp-ceremonies a{background:#fff;border:1px solid #d6e4f3}
.pouls{position:relative;width:8px;height:8px;border-radius:50%;background:#4ade80;flex:none}
.pouls::after{content:"";position:absolute;inset:0;border-radius:50%;background:#4ade80;animation:sp-pouls 1.8s ease-out infinite}
@keyframes sp-pouls{0%{transform:scale(1);opacity:.7}100%{transform:scale(2.6);opacity:0}}
@media(prefers-reduced-motion:reduce){.pouls::after{animation:none}}
.sp-carte.clos .sp-etat{background:#f0fdf4;color:#15803d;border-bottom-color:#dcfce7}
.sp-carte.clos .sp-etat.rectif{background:#fff7ed;color:#c2410c;border-bottom-color:#fed7aa}
.sp-carte.clos .barre>div{background:#86c9a0}
.sp-carte.arrete{border-color:#fecaca}
.sp-carte.arrete .sp-etat{background:#fef2f2;color:#b91c1c;border-bottom-color:#fee2e2}
.sp-carte.arrete .barre>div{background:#f4a3a3}
.sp-motif{margin:8px 0 0;padding:6px 9px;border-radius:8px;background:#fff5f5;border:1px solid #fee2e2;font-size:11.5px;color:#991b1b;line-height:1.45}
.sp-motif span{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.sp-carte.planifie{border-style:dashed;border-color:#cbd5e1}
.sp-carte.planifie .sp-etat{background:#f8fafc;color:#475569;border-bottom-color:#eef2f7}
.barre{height:7px;background:#e2e8f0;border-radius:6px;overflow:hidden;margin:8px 0 4px}.barre>div{height:100%;background:var(--accent)}
dialog{border:none;border-radius:14px;padding:22px;width:560px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:88vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:11.5px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:700;text-transform:uppercase}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
dialog textarea{resize:vertical}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.form-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.req{color:var(--ko)}
.aide{font-size:11px;color:var(--gris);margin-top:2px}
@media(max-width:700px){.form-row,.form-row3{grid-template-columns:1fr}.tbl{display:block;overflow-x:auto}}
</style>
@endpush

@section('contenu')
@php
    $actifs = $membres->where('actif', true);
    $ongletInitial = in_array(request('onglet'), ['equipe', 'backlog', 'sprints'], true) ? request('onglet') : 'backlog';
@endphp

<div class="ep-tete">
    <div>
        <h2>{{ $projet->libelle }} <span style="font-size:13px;color:var(--gris);font-weight:600">{{ $projet->code }}</span></h2>
        <div class="crumb"><a href="{{ route('sprints.index') }}" style="color:var(--accent);text-decoration:none">Projets / Sprints</a> &rsaquo; Espace projet</div>
        <div class="meta">
            Responsable : <strong>{{ $projet->responsable?->nomComplet() ?? '—' }}</strong>
            @if($projet->client) · Client : {{ $projet->client }} @endif
            @if($projet->date_debut) · Du {{ $projet->date_debut->format('d/m/Y') }}@if($projet->date_cible) au {{ $projet->date_cible->format('d/m/Y') }}@endif @endif
        </div>
    </div>
</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif
@if(session('erreurs_import') && count(session('erreurs_import')))
<div class="alerte" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e"><strong>Remarques de l'import consolidé :</strong><ul style="margin:4px 0 0 18px">@foreach(session('erreurs_import') as $err)<li>{{ $err }}</li>@endforeach</ul></div>
@endif

<div class="ep-tabs" role="tablist">
    <button class="ep-tab" data-onglet="backlog" onclick="ongletEp('backlog')">Product Backlog <span class="nb">{{ $stories->count() }}</span></button>
    <button class="ep-tab" data-onglet="sprints" onclick="ongletEp('sprints')">Sprints <span class="nb">{{ $projet->sprints->count() }}</span></button>
    <button class="ep-tab" data-onglet="equipe" onclick="ongletEp('equipe')">Équipe <span class="nb">{{ $actifs->count() }}</span></button>
</div>

{{-- ═══════════ PRODUCT BACKLOG ═══════════ --}}
<div class="ep-panel" id="ep-backlog">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div style="font-size:12.5px;color:var(--gris)">User stories du produit, regroupées par fonctionnalité.</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            @if($peutAlimenter)
            <button class="btn sec" onclick="document.getElementById('dlg-import').showModal()">Importer (.xlsx)</button>
            <button class="btn sec" onclick="document.getElementById('dlg-import-consolide').showModal()">Import consolidé</button>
            <button class="btn" onclick="ouvrirStory()">+ User story</button>
            @endif
            @if($stories->isNotEmpty())<a class="btn sec" href="{{ route('projets.backlog.export', $projet) }}" title="Exporter le Product Backlog (.xlsx)">Exporter</a>@endif
        </div>
    </div>
    @if(session('remarques_import'))
    <div class="alerte" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e"><strong>Remarques d'import :</strong><ul style="margin:4px 0 0 18px">@foreach(session('remarques_import') as $rq)<li>{{ $rq }}</li>@endforeach</ul></div>
    @endif
    @if($stories->isEmpty())
    <div class="carte" style="text-align:center;color:var(--gris);padding:30px">Le backlog est vide. Ajoutez la première user story du projet.</div>
    @else
    @include('projets._tableau_backlog', ['bilan' => $bilanProjet, 'tableau' => $tableau])
    <div class="filtre">
        <select id="fb-module" onchange="filtrerBacklog()"><option value="">Toutes les fonctionnalités</option>@foreach($modules as $m)<option>{{ $m }}</option>@endforeach</select>
        <select id="fb-statut" onchange="filtrerBacklog()"><option value="">Tous les statuts</option>@foreach(\App\Models\UserStory::STATUTS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <select id="fb-moscow" onchange="filtrerBacklog()"><option value="">Toutes priorités</option>@foreach(\App\Models\UserStory::MOSCOW_BACKLOG as $k)<option value="{{ $k }}">{{ \App\Models\UserStory::MOSCOW[$k] }}</option>@endforeach</select>
        <select id="fb-dor" onchange="filtrerBacklog()"><option value="">DoR : tous</option><option value="1">DoR : Oui</option><option value="0">DoR : Non</option></select>
        <input type="search" id="fb-q" placeholder="Rechercher…" oninput="filtrerBacklog()">
        <span id="fb-nb" style="margin-left:auto;font-size:12px;color:var(--gris)"></span>
    </div>
    @php $selection = $peutAlimenter || $sprintsCibles->isNotEmpty(); @endphp
    @if(session('ecartees_us'))
    <div class="ecartees-us"><strong>User stories écartées :</strong><ul>@foreach(session('ecartees_us') as $num => $raison)<li>{{ $num }} : {{ $raison }}</li>@endforeach</ul></div>
    @endif
    @if($selection)
    <div class="sel-aide">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span>Cochez des user stories pour agir sur la sélection{{ $sprintsCibles->isNotEmpty() ? ' : ajout au sprint' : '' }}{{ $peutAlimenter ? ($sprintsCibles->isNotEmpty() ? ', ' : ' : ') . 'DoR, priorité, story points, classement, statut, retrait' : '' }}.</span>
        <button type="button" class="lien" onclick="toutesVisibles()">Tout cocher (stories affichées)</button>
    </div>
    @elseif($canEdit)
    <div class="puiser">Aucun sprint à préparer : créez le prochain sprint depuis « Projets / Sprints » pour y puiser des user stories.</div>
    @endif
    @foreach($arbre as $noeud)
    @php $module = $noeud['module']; $bm = $noeud['bilan']; @endphp
    <div class="module-bloc" data-module="{{ $module === 'Sans module' ? '' : $module }}">
        <div class="module-titre">
            <span class="nom">{{ $module === 'Sans module' ? 'Sans fonctionnalité' : $module }}</span>
            <span class="stats">{{ $bm['stories_terminees'] }}/{{ $bm['stories'] }} stories @if($bm['points']) · {{ $bm['points_faits'] }}/{{ $bm['points'] }} pts @endif</span>
            <span style="display:flex;align-items:center"><span class="prog {{ $bm['pct'] >= 100 ? 'fini' : '' }}" style="flex:1"><i style="width:{{ $bm['pct'] }}%"></i></span><span class="pct">{{ $bm['pct'] }} %</span></span>
        </div>
        <div class="module-corps">
        @foreach($noeud['epics'] as $ep)
        @if($ep['epic'])
        @php $be = $ep['bilan']; @endphp
        <div class="epic-titre" data-epic>
            <span class="nom"><small>Epic / Module</small>{{ $ep['epic'] }}@if($peutAlimenter)<button type="button" class="ren" data-module="{{ $module === 'Sans module' ? '' : $module }}" data-epic="{{ $ep['epic'] }}" onclick="ouvrirEpic(this.dataset.module, this.dataset.epic)" title="Modifier la formulation de cet epic" aria-label="Modifier la formulation de l'epic {{ $ep['epic'] }}"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>@endif</span>
            <span class="stats">{{ $be['stories_terminees'] }}/{{ $be['stories'] }} stories @if($be['points']) · {{ $be['points_faits'] }}/{{ $be['points'] }} pts @endif</span>
            <span style="display:flex;align-items:center"><span class="prog {{ $be['pct'] >= 100 ? 'fini' : '' }}" style="flex:1"><i style="width:{{ $be['pct'] }}%"></i></span><span class="pct">{{ $be['pct'] }} %</span></span>
        </div>
        @endif
        <table class="pb-tbl">
            <colgroup><col class="c-coche"><col class="c-id"><col class="c-profil"><col><col class="c-moscow"><col class="c-pts"><col class="c-dor"><col class="c-statut"><col class="c-exec"><col class="c-act"></colgroup>
            <thead><tr><th>@if($selection)<input type="checkbox" class="tout-coche" onchange="toutTableau(this)" title="Cocher les stories affichées de ce tableau" aria-label="Cocher les user stories affichées de ce tableau">@endif</th><th>ID</th><th>Profil / Acteur</th><th>User Story</th><th>MoSCoW</th><th title="Story points">Pts</th><th title="Definition of Ready">DoR</th><th>Statut</th><th>Exécution</th><th></th></tr></thead>
            <tbody>
        @foreach($ep['stories'] as $us)
            <tr class="us" data-us data-statut="{{ $us->statut }}" data-moscow="{{ $us->priorite_moscow }}" data-dor="{{ $us->dor ? '1' : '0' }}" data-texte="{{ mb_strtolower($us->numero . ' ' . $us->module . ' ' . $us->epic . ' ' . $us->profil . ' ' . $us->intitule . ' ' . \App\Services\TexteRiche::texte($us->exigences)) }}">
                @php $puisable = $us->dor && !$us->estFinie() && !$us->sprints->contains(fn($sp) => $sp->statut !== 'termine'); @endphp
                <td class="td-coche">@if($selection)
                    <input type="checkbox" class="us-coche" value="{{ $us->id }}" data-num="{{ $us->numero }}" data-puisable="{{ $puisable ? 1 : 0 }}" aria-label="Sélectionner {{ $us->numero }}"
                        @if(!$peutAlimenter && !$puisable) disabled title="{{ $us->dor ? 'Déjà terminée ou engagée dans un sprint ouvert' : 'DoR = Non : cette user story ne peut pas entrer dans un sprint' }}" @endif>
                @endif</td>
                <td class="us-num">{{ $us->numero }}</td>
                <td class="td-profil">{{ $us->profil ?: '-' }}</td>
                <td class="td-story">
                    <span class="us-txt">{{ $us->intitule }}</span>
                    <span class="us-meta">
                        @if(($us->origine ?? 'manuel') === 'review')<span>Issue d'une Review</span>@endif
                        @if($us->sprints->isNotEmpty())<span>Sprint(s) {{ $us->sprints->pluck('numero')->sort()->join(', ') }}</span>@endif
                        @if($us->taches_count)<span>{{ $us->taches_terminees_count }}/{{ $us->taches_count }} tâches</span>@endif
                        @if($us->exigences)<button type="button" class="lien-det" onclick="this.closest('.us').classList.toggle('ouvert')">Exigences</button>@endif
                    </span>
                    @if($us->exigences)<div class="us-detail"><x-redaction lecture :value="$us->exigences" /></div>@endif
                </td>
                <td data-lib="MoSCoW">@if($us->priorite_moscow)<span class="ms ms-{{ $us->priorite_moscow }}">{{ \App\Models\UserStory::MOSCOW[$us->priorite_moscow] ?? $us->priorite_moscow }}</span>@else - @endif</td>
                <td data-lib="Points" class="td-pts" title="{{ $us->complexiteStory()['libelle'] ?? 'Non estimée' }}">{{ $us->effort_estime ?: '-' }}</td>
                <td data-lib="DoR"><span class="dor {{ $us->dor ? 'oui' : '' }}">{{ $us->dor ? 'Oui' : 'Non' }}</span></td>
                <td data-lib="Statut">@include('scrum._statut_us', ['us' => $us, 'peutAlimenter' => $peutAlimenter])</td>
                <td data-lib="Exécution" class="td-exec">@include('scrum._niveau_us', ['us' => $us])</td>
                <td class="td-act">
                    @if($peutAlimenter)
                    @php $usJs = $us->only(['id', 'numero', 'module', 'epic', 'profil', 'intitule', 'priorite_moscow', 'effort_estime', 'dor']) + ['exigences' => \App\Services\TexteRiche::html($us->exigences)]; @endphp
                    <button type="button" class="ico" onclick='ouvrirStory(@json($usJs))' title="Modifier {{ $us->numero }}" aria-label="Modifier {{ $us->numero }}"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                    @php
                        $spEnCours = $us->sprints->firstWhere('statut', 'en_cours');
                        $spClos = $us->sprints->where('statut', 'termine')->pluck('numero')->sort()->join(', ');
                    @endphp
                    @if($spEnCours)
                    <button type="button" class="ico ko" disabled title="Engagée dans le Sprint {{ $spEnCours->numero }} en cours : retirez-la d'abord du Sprint Backlog" aria-label="Suppression impossible : {{ $us->numero }} est engagée dans le Sprint {{ $spEnCours->numero }} en cours"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                    @elseif($spClos !== '')
                    <form method="POST" action="{{ route('user-stories.destroy', $us) }}" style="margin:0" data-msg="{{ $us->numero }} a été engagée dans le Sprint {{ $spClos }}. Elle sera archivée : retirée du Product Backlog, mais conservée dans l'historique du sprint." onsubmit="return confirm(this.dataset.msg) && demanderMotifSuppression(this,'cette user story')">@csrf @method('DELETE')
                        <input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="ico ko" title="Archiver {{ $us->numero }} (engagée dans le Sprint {{ $spClos }})" aria-label="Archiver {{ $us->numero }}"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8"/><path d="M10 12h4"/></svg></button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('user-stories.destroy', $us) }}" style="margin:0" onsubmit="return demanderMotifSuppression(this,'cette user story')">@csrf @method('DELETE')
                        <input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="ico ko" title="Supprimer {{ $us->numero }}" aria-label="Supprimer {{ $us->numero }}"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                    </form>
                    @endif
                    @endif
                </td>
            </tr>
        @endforeach
            </tbody>
        </table>
        @endforeach
        </div>
    </div>
    @endforeach
    @endif
    @if($archivees->isNotEmpty())
    <details class="archives-us">
        <summary>User stories archivées <span class="nb">{{ $archivees->count() }}</span><small>Hors Product Backlog, conservées dans l'historique de leurs sprints</small></summary>
        <div style="overflow-x:auto">
        <table class="arch-tbl">
            <thead><tr><th style="width:72px">ID</th><th class="g">User Story</th><th style="width:90px">Sprint(s)</th><th style="width:96px">Archivée le</th><th class="g" style="width:28%">Motif</th>@if($peutAlimenter)<th style="width:96px"></th>@endif</tr></thead>
            <tbody>
            @foreach($archivees as $us)
            <tr>
                <td class="us-num">{{ $us->numero }}</td>
                <td class="g"><span class="us-txt">{{ $us->intitule }}</span><span class="us-meta">@if($us->module || $us->epic)<span>{{ collect([$us->module, $us->epic])->filter()->join(' › ') }}</span>@endif</span></td>
                <td>{{ $us->sprints->pluck('numero')->sort()->join(', ') ?: '-' }}</td>
                <td>{{ $us->archivee_le->format('d/m/Y') }}</td>
                <td class="g">{{ $us->archive_motif }}</td>
                @if($peutAlimenter)
                <td><form method="POST" action="{{ route('user-stories.restaurer', $us) }}" style="margin:0">@csrf @method('PATCH')<button class="btn sm sec" title="Remettre {{ $us->numero }} dans le Product Backlog">Restaurer</button></form></td>
                @endif
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </details>
    @endif
</div>

@if($selection ?? false)
<div class="barre-us" id="barre-us" role="toolbar" aria-label="Action sur les user stories sélectionnées">
    <span class="nb" id="us-nb">0 user story</span>
    @if($sprintsCibles->isNotEmpty())
    <span class="grp">
        <select id="us-sprint" aria-label="Sprint cible">@foreach($sprintsCibles as $sp)<option value="{{ route('sprints.user-stories.attacher', $sp) }}">Sprint {{ $sp->numero }}{{ $sp->statut === 'en_cours' ? ' (en cours)' : '' }}</option>@endforeach</select>
        <button type="button" class="prim" onclick="puiserSelection()" title="Ajouter au sprint les stories prêtes (DoR = Oui, non achevées, libres)">Ajouter au sprint</button>
    </span>
    @endif
    @if($peutAlimenter)
    <span class="sep"></span>
    <button type="button" onclick="ouvrirGroupe('dor')">DoR</button>
    <button type="button" onclick="ouvrirGroupe('moscow')">Priorité</button>
    <button type="button" onclick="ouvrirGroupe('points')">Story points</button>
    <button type="button" onclick="ouvrirGroupe('classer')">Classer</button>
    <button type="button" onclick="ouvrirGroupe('profil')">Profil</button>
    <button type="button" onclick="ouvrirGroupe('statut')">Statut</button>
    <span class="sep"></span>
    <button type="button" class="ko" onclick="ouvrirGroupe('retirer')">Retirer</button>
    @endif
    <button type="button" class="fermer" onclick="viderSelection()" title="Annuler la sélection (Échap)" aria-label="Annuler la sélection">✕</button>
</div>
<form method="POST" id="f-puiser" action="" style="display:none">@csrf</form>
@if($peutAlimenter)
<dialog id="dlg-groupe" aria-labelledby="dg-titre">
    <form method="POST" action="{{ route('projets.user-stories.groupe', $projet) }}" id="f-groupe" onsubmit="return validerGroupe()">@csrf
        <input type="hidden" name="action" value="">
        <h3 id="dg-titre"></h3>
        <p class="aide" id="dg-aide"></p>
        <div class="liste" id="dg-liste"></div>
        <div data-champ="dor"><label>Definition of Ready<select name="dor"><option value="1">Oui : prête pour un sprint</option><option value="0">Non : à affiner</option></select></label></div>
        <div data-champ="moscow"><label>Priorité MoSCoW<select name="priorite_moscow"><option value="">Non définie</option>@foreach(\App\Models\UserStory::MOSCOW_BACKLOG as $k)<option value="{{ $k }}">{{ \App\Models\UserStory::MOSCOW[$k] }}</option>@endforeach</select></label></div>
        <div data-champ="points"><label>Story points<select name="effort_estime"><option value="">Non estimée</option>@foreach(\App\Models\UserStory::POINTS as $pt)<option value="{{ $pt }}">{{ $pt }} pt{{ $pt > 1 ? 's' : '' }} — {{ \App\Models\UserStory::complexite($pt)['libelle'] }}</option>@endforeach</select></label></div>
        <div data-champ="classer">
            <label class="ligne-case"><input type="checkbox" name="changer_module" value="1" checked onchange="this.form.module.disabled=!this.checked"> Changer la fonctionnalité</label>
            <label><input type="text" name="module" list="st-modules" maxlength="150" placeholder="Vide : sans fonctionnalité"></label>
            <label class="ligne-case"><input type="checkbox" name="changer_epic" value="1" onchange="this.form.epic.disabled=!this.checked"> Changer l'epic</label>
            <label><input type="text" name="epic" maxlength="150" placeholder="Vide : sans epic" disabled></label>
        </div>
        <div data-champ="profil"><label>Profil / acteur<input type="text" name="profil" list="st-profils" maxlength="150" placeholder="Vide : aucun profil"></label></div>
        <div data-champ="statut"><label>Statut<select name="statut">@foreach(\App\Models\UserStory::STATUTS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label></div>
        <div data-champ="retirer"><label>Motif du retrait (obligatoire)<textarea name="motif" maxlength="500" placeholder="Ex. : besoin abandonné par le client, doublon de…"></textarea></label></div>
        <div class="pied">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn" id="dg-ok">Appliquer</button>
        </div>
    </form>
</dialog>
@endif
@endif

{{-- ═══════════ SPRINTS ═══════════ --}}
<div class="ep-panel" id="ep-sprints">
    @if($projet->sprints->isEmpty())
    <div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun sprint pour ce projet. Créez-le depuis « Projets / Sprints ».</div>
    @else
    <div class="sp-cartes {{ $projet->sprints->contains('statut', 'en_cours') ? 'avec-encours' : '' }}">
        @foreach($projet->sprints as $sp)
        @php $pct = $sp->taches_count ? round($sp->taches_terminees_count / $sp->taches_count * 100) : 0; @endphp
        @php $etat = $sp->statut === 'en_cours' ? 'encours' : ($sp->statut === 'planifie' ? 'planifie' : ($sp->arrete_le ? 'arrete' : 'clos')); @endphp
        <div class="sp-carte {{ $etat }}">
            @if($etat === 'encours')
            @php $urg = $sp->urgence(); @endphp
            <div class="sp-etat"><span class="pouls" aria-hidden="true"></span>En cours · {{ $pct }} %<span class="droite {{ $urg['niveau'] === 'retard' ? 'retard' : '' }}" title="{{ $urg['libelle'] }}">{{ $urg['court'] }}</span></div>
            @elseif($etat === 'arrete')
            <div class="sp-etat" title="Sprint interrompu avant son terme"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><rect x="9" y="9" width="6" height="6" rx="1"/></svg>Arrêté<span class="droite">le {{ $sp->arrete_le->format('d/m/Y') }}</span></div>
            @elseif($etat === 'clos' && $sp->enRectification())
            <div class="sp-etat rectif" title="Sprint clôturé rouvert pour harmoniser l'historique : {{ $sp->rectification_motif }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>En rectification<span class="droite">depuis le {{ $sp->rectification_ouverte_le->format('d/m') }}</span></div>
            @elseif($etat === 'clos')
            <div class="sp-etat" title="Sprint mené à son terme : planification effective achevée"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>Clôturé<span class="droite">{{ $sp->date_fin->format('d/m/Y') }}</span></div>
            @else
            <div class="sp-etat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Planifié<span class="droite">démarre le {{ $sp->date_debut->format('d/m') }}</span></div>
            @endif
            <a href="{{ route('sprints.show', $sp) }}" class="sp-lien">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <strong class="sp-titre" style="color:var(--bleu)">Sprint {{ $sp->numero }}</strong>
            </div>
            <div class="sp-obj" style="font-size:12.5px;color:#334155;margin-top:6px">{{ \Illuminate\Support\Str::limit($sp->objectif, 110) }}</div>
            <div class="barre"><div style="width:{{ $pct }}%"></div></div>
            <div style="font-size:11.5px;color:var(--gris)">{{ $sp->date_debut->format('d/m') }} → {{ $sp->date_fin->format('d/m/Y') }} · {{ $sp->dureeJours() }} j ouvrés · {{ $sp->taches_terminees_count }}/{{ $sp->taches_count }} tâches</div>
            @if($etat === 'arrete' && $sp->justification_arret)<div class="sp-motif" title="{{ $sp->justification_arret }}"><span><strong>Motif :</strong> {{ $sp->justification_arret }}</span></div>@endif
            </a>
            <div class="sp-ceremonies">
                <a href="{{ route('sprints.show', $sp) }}">Backlog</a>
                <a href="{{ route('sprints.planning', $sp) }}">Planning</a>
                <a href="{{ route('scrum.dailies', $sp) }}">Daily ({{ $sp->daily_scrums_count }})</a>
                <a href="{{ route('scrum.review', $sp) }}">Review</a>
                <a href="{{ route('scrum.retro', $sp) }}">Rétro</a>
                <a href="{{ route('scrum.archive', $sp) }}" class="xl" title="Exporter archive Scrum (.xlsx)">Excel ⤓</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ═══════════ ÉQUIPE ═══════════ --}}
<div class="ep-panel" id="ep-equipe">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div style="font-size:12.5px;color:var(--gris)">Membres du projet. Un membre retiré garde son historique.</div>
        @if($canEdit)<button class="btn" onclick="document.getElementById('dlg-membre').showModal()" @disabled($disponibles->isEmpty())>+ Ajouter un collaborateur</button>@endif
    </div>
    <div class="carte" style="overflow-x:auto">
    <h3 style="font-size:14px;color:var(--bleu);margin:0 0 10px;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> &Eacute;quipe du projet</h3>
    <table class="tbl">
        <thead><tr><th>Collaborateur</th><th>Fonction</th><th>Rôle dans le projet</th><th>Membre depuis</th><th>État</th>@if($canEdit)<th></th>@endif</tr></thead>
        <tbody>
        @forelse($membres as $m)
        <tr class="{{ $m->actif ? '' : 'inactif' }}">
            <td><strong>{{ $m->user?->nomComplet() }}</strong></td>
            <td style="font-size:12px;color:var(--gris)">{{ $m->user?->roleLibelle() }}</td>
            <td>
                @if($canEdit && $m->actif)
                <form method="POST" action="{{ route('projets.collaborateurs.update', [$projet, $m]) }}" style="margin:0">@csrf @method('PUT')
                    <select name="role_projet" onchange="this.form.submit()" style="padding:4px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px">
                        @foreach(\App\Models\ProjetCollaborateur::ROLES as $k => $l)<option value="{{ $k }}" @selected($m->role_projet === $k)>{{ $l }}</option>@endforeach
                    </select>
                </form>
                @else {{ $m->roleLibelle() }} @endif
            </td>
            <td style="font-size:12.5px">{{ $m->date_ajout?->format('d/m/Y') }}</td>
            <td><span class="pill {{ $m->actif ? 'p-ok' : 'p-attente' }}">{{ $m->actif ? 'Actif' : 'Retiré' . ($m->date_retrait ? ' le ' . $m->date_retrait->format('d/m/Y') : '') }}</span></td>
            @if($canEdit)
            <td style="text-align:right">
                @if($m->actif)
                <form method="POST" action="{{ route('projets.collaborateurs.retirer', [$projet, $m]) }}" style="margin:0" onsubmit="return confirm('Retirer {{ addslashes($m->user?->nomComplet()) }} de l\'équipe ?')">@csrf @method('PATCH')
                    <button class="btn sm ko">Retirer</button>
                </form>
                @else
                <form method="POST" action="{{ route('projets.collaborateurs.store', $projet) }}" style="margin:0">@csrf
                    <input type="hidden" name="user_id" value="{{ $m->user_id }}"><input type="hidden" name="role_projet" value="{{ $m->role_projet }}">
                    <button class="btn sm sec">Réintégrer</button>
                </form>
                @endif
            </td>
            @endif
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;color:var(--gris);padding:20px">Aucun membre dans l'équipe.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($canEdit)
<dialog id="dlg-membre">
    <h3 style="color:var(--bleu);margin:0 0 4px">Ajouter un collaborateur au projet</h3>
    <form method="POST" action="{{ route('projets.collaborateurs.store', $projet) }}">@csrf
        <label>Collaborateur <span class="req">*</span></label>
        <select name="user_id" required>
            <option value="">— Choisir —</option>
            @foreach($disponibles as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }} ({{ $u->roleLibelle() }})</option>@endforeach
        </select>
        <label>Rôle dans le projet <span class="req">*</span></label>
        <select name="role_projet" required>
            @foreach(\App\Models\ProjetCollaborateur::ROLES as $k => $l)<option value="{{ $k }}" @selected($k === 'developpeur')>{{ $l }}</option>@endforeach
        </select>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Ajouter</button>
        </div>
    </form>
</dialog>
@endif

@if($peutAlimenter)
@if($peutAlimenter)
<dialog id="dlg-epic" style="width:480px">
    <h3 style="color:var(--bleu);margin:0 0 4px">Modifier la formulation de l'epic</h3>
    <p id="ep-portee" style="font-size:12.5px;color:var(--gris);margin:0 0 6px"></p>
    <form method="POST" action="{{ route('projets.epics.renommer', $projet) }}">@csrf
        <input type="hidden" name="module" id="ep-module"><input type="hidden" name="ancien" id="ep-ancien">
        <label>Epic / Module</label>
        <input name="nouveau" id="ep-nouveau" required maxlength="150">
        <p style="font-size:11.5px;color:var(--gris);margin:6px 0 0">La nouvelle formulation s'applique à toutes les user stories de cet epic, dans le Product Backlog comme dans les sprints.</p>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endif
<dialog id="dlg-import">
    <h3 style="color:var(--bleu);margin-bottom:6px">Importer un Product Backlog (.xlsx)</h3>
    <p style="font-size:12.5px;color:#334155;margin-bottom:6px">Colonnes lues : <strong>N°, Epic / Module, Fonctionnalité, Profil / Acteur, User Story, Priorité MoSCoW, Story points, DoR, Statut, Exigences</strong>.</p>
    <p style="font-size:12px;margin-bottom:4px"><a href="{{ route('projets.backlog.modele', $projet) }}" style="color:var(--accent);font-weight:600">⤓ Télécharger le modèle d'import</a></p>
    <form method="POST" action="{{ route('projets.backlog.import', $projet) }}" enctype="multipart/form-data">@csrf
        <label>Fichier Excel <span class="req">*</span></label>
        <input type="file" name="fichier" accept=".xlsx" required>
        <label>Si un numéro existe déjà dans le backlog</label>
        <select name="mode">
            <option value="maj">Mettre à jour la user story existante</option>
            <option value="ajout">Ignorer la ligne (ne créer que les nouvelles)</option>
        </select>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Importer</button>
        </div>
    </form>
</dialog>

<dialog id="dlg-import-consolide" style="width:540px">
    <h3 style="color:var(--bleu);margin-bottom:6px">Import consolidé des éléments Scrum</h3>
    <p style="font-size:12.5px;color:#334155;margin-bottom:10px">Un classeur couvrant plusieurs sprints : Product Backlog, puis pour chaque sprint son Sprint Backlog, son planning, ses dailys, sa review et sa rétrospective.</p>
    <form method="POST" action="{{ route('projets.import.consolide', $projet) }}" enctype="multipart/form-data" onsubmit="this.querySelector('button.btn:not(.sec)').disabled=true;this.querySelector('button.btn:not(.sec)').textContent='Import en cours…'">@csrf
        <label>Classeur Excel (.xlsx) <span class="req">*</span></label>
        <input type="file" name="fichier" accept=".xlsx" required>
        <ul style="font-size:11.5px;color:var(--gris);margin:8px 0 0 16px;padding:0;line-height:1.6">
            <li>Onglets lus, dans l'ordre du classeur : <em>Product Backlog, Backlog Sprint N, Sprint N Planning, DAILY n jj-mm-aaaa, REVIEW SPRINT N, RETROSPECTIVE N</em>.</li>
            <li>Les sprints passés sont créés clôturés ; le dernier est en cours (ou planifié s'il n'a pas commencé).</li>
            <li>Les collaborateurs sont reconnus par leur nom ; « Toute l'équipe » donne une tâche à chaque membre du sprint.</li>
            <li>Réimporter le même classeur ne crée pas de doublon ; un sprint déjà clôturé dans l'application n'est pas modifié.</li>
        </ul>
        <label style="display:flex;align-items:flex-start;gap:8px;margin-top:12px;padding:9px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:12px;color:#92400e;cursor:pointer;font-weight:400"><input type="checkbox" name="simulation" value="1" style="width:auto;margin-top:2px"><span><b>Simulation</b> : voir le résultat (sprints, tâches, doublons évités, remarques) sans rien enregistrer. Recommandé avant un import en production.</span></label>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Importer</button>
        </div>
    </form>
</dialog>

<dialog id="dlg-story">
    <h3 id="st-titre" style="color:var(--bleu);margin:0 0 4px">Nouvelle user story</h3>
    <form method="POST" id="form-story" action="{{ route('projets.user-stories.store', $projet) }}">@csrf <span id="st-method"></span>
        <input type="hidden" name="story_id" id="st-id">
        <div class="form-row">
            <div><label>Numéro <span class="req">*</span></label><input name="numero" id="st-numero" required maxlength="20" placeholder="US 1.1"></div>
            <div><label>Fonctionnalité</label><input name="module" id="st-module" maxlength="150" list="st-modules" placeholder="Ex : Gestion des rôles et utilisateurs">
                <datalist id="st-modules">@foreach($modules as $m)<option value="{{ $m }}">@endforeach</datalist></div>
        </div>
        <div class="form-row">
            <div><label>Epic / Module</label><input name="epic" id="st-epic" maxlength="150" list="st-epics" placeholder="Ex : Paiement en ligne">
                <datalist id="st-epics"></datalist></div>
            <div><label>Profil / Acteur</label><input name="profil" id="st-profil" maxlength="150" list="st-profils" placeholder="Ex : Gestionnaire">
                <datalist id="st-profils">@foreach($stories->pluck('profil')->filter()->unique() as $pf)<option value="{{ $pf }}">@endforeach</datalist></div>
        </div>
        <label>User story <span class="req">*</span></label>
        <textarea name="intitule" id="st-intitule" rows="3" required maxlength="2000" placeholder="En tant que …, je veux … afin de …"></textarea>
        <div class="form-row3">
            <div><label>Priorité MoSCoW</label><select name="priorite_moscow" id="st-moscow"><option value="">-</option>@foreach(\App\Models\UserStory::MOSCOW_BACKLOG as $k)<option value="{{ $k }}">{{ \App\Models\UserStory::MOSCOW[$k] }}</option>@endforeach</select></div>
            <div><label>Story points</label><select name="effort_estime" id="st-points"><option value="">-</option>@foreach(\App\Models\UserStory::POINTS as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach</select></div>
            <div><label title="Definition of Ready : la story est prête à entrer dans un sprint">DoR</label><select name="dor" id="st-dor"><option value="0">Non</option><option value="1">Oui</option></select></div>
        </div>
        <label>Exigences fonctionnelles</label>
        <x-redaction name="exigences" placeholder="Une exigence par puce" />
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endif
@endsection

@push('scripts')
<script>
function ongletEp(nom){
    document.querySelectorAll('.ep-tab').forEach(b => b.classList.toggle('actif', b.dataset.onglet === nom));
    document.querySelectorAll('.ep-panel').forEach(p => p.classList.toggle('actif', p.id === 'ep-' + nom));
    const u = new URL(location.href); u.searchParams.set('onglet', nom); history.replaceState(null, '', u);
}
ongletEp(@json($ongletInitial));

function filtrerStatut(s){
    const f = document.getElementById('fb-statut');
    f.value = f.value === s ? '' : s;
    filtrerBacklog();
    f.scrollIntoView({block: 'nearest'});
}
/* ── Sélection multiple de user stories : une barre d'actions commune ── */
const cochees = () => [...document.querySelectorAll('.us-coche:checked')];
function majSelection(){
    const n = cochees().length, barre = document.getElementById('barre-us');
    document.querySelectorAll('.us-coche').forEach(c => c.closest('tr').classList.toggle('choisie', c.checked));
    document.querySelectorAll('.tout-coche').forEach(t => {
        const cases = [...t.closest('table').querySelectorAll('.us-coche:not(:disabled)')].filter(c => c.closest('tr').style.display !== 'none');
        const k = cases.filter(c => c.checked).length;
        t.checked = cases.length > 0 && k === cases.length; t.indeterminate = k > 0 && k < cases.length;
    });
    if (!barre) return;
    document.getElementById('us-nb').textContent = n + ' user stor' + (n > 1 ? 'ies' : 'y');
    barre.classList.toggle('visible', n > 0);
}
document.addEventListener('change', e => { if (e.target.classList.contains('us-coche')) majSelection(); });
function casesVisibles(racine){ return [...racine.querySelectorAll('.us-coche:not(:disabled)')].filter(c => c.closest('tr').style.display !== 'none' && c.closest('.module-bloc').style.display !== 'none'); }
function toutTableau(t){ casesVisibles(t.closest('table')).forEach(c => c.checked = t.checked); majSelection(); }
function toutesVisibles(){ casesVisibles(document.getElementById('ep-backlog')).forEach(c => c.checked = true); majSelection(); }
function viderSelection(){ document.querySelectorAll('.us-coche:checked').forEach(c => c.checked = false); majSelection(); }
document.addEventListener('keydown', e => { if (e.key === 'Escape' && cochees().length && !document.querySelector('dialog[open]')) viderSelection(); });

function puiserSelection(){
    const ok = cochees().filter(c => c.dataset.puisable === '1'), non = cochees().filter(c => c.dataset.puisable !== '1');
    if (!ok.length) { alert('Aucune des user stories sélectionnées ne peut entrer dans un sprint : DoR = Non, déjà terminée ou engagée dans un sprint ouvert.'); return; }
    const sel = document.getElementById('us-sprint');
    if (non.length && !confirm(non.length + ' story(s) écartée(s) (DoR = Non, terminée ou déjà engagée) : ' + non.map(c => c.dataset.num).join(', ') + '.\nAjouter les ' + ok.length + ' autre(s) au ' + sel.selectedOptions[0].text + ' ?')) return;
    const f = document.getElementById('f-puiser');
    f.action = sel.value;
    f.querySelectorAll('input[name="user_stories[]"]').forEach(i => i.remove());
    ok.forEach(c => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'user_stories[]'; i.value = c.value; f.appendChild(i); });
    f.submit();
}

const GROUPE = {
    dor: ['Definition of Ready', 'Une story « DoR = Oui » peut être puisée dans un sprint.'],
    moscow: ['Priorité MoSCoW', 'La priorité choisie remplace celle de chaque story.'],
    points: ['Story points', 'Estimation appliquée à chaque story (les stories achevées gardent la leur).'],
    classer: ['Classer dans le backlog', 'Déplacez les stories vers une fonctionnalité et/ou un epic (existant ou nouveau).'],
    profil: ['Profil / acteur', 'Le profil choisi remplace celui de chaque story.'],
    statut: ['Statut', 'Les stories dont le statut est calculé à partir des tâches ne changent pas (seules la revue et l\'acceptation restent possibles).'],
    retirer: ['Retirer du Product Backlog', 'Les stories passées par un sprint clôturé sont archivées (l\'historique les conserve), les autres supprimées ; celles du sprint en cours sont écartées.'],
};
function ouvrirGroupe(action){
    const d = document.getElementById('dlg-groupe'), f = document.getElementById('f-groupe'), c = cochees();
    if (!c.length) return;
    f.action.value = action;
    document.getElementById('dg-titre').textContent = GROUPE[action][0] + ' — ' + c.length + ' user stor' + (c.length > 1 ? 'ies' : 'y');
    document.getElementById('dg-aide').textContent = GROUPE[action][1];
    document.getElementById('dg-liste').textContent = c.map(x => x.dataset.num).join(', ');
    d.querySelectorAll('[data-champ]').forEach(z => { z.hidden = z.dataset.champ !== action; z.querySelectorAll('input,select,textarea').forEach(i => { if (z.hidden) i.setAttribute('data-off', ''); else i.removeAttribute('data-off'); }); });
    f.querySelectorAll('input[name="ids[]"]').forEach(i => i.remove());
    c.forEach(x => { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'ids[]'; i.value = x.value; f.appendChild(i); });
    const ok = document.getElementById('dg-ok');
    ok.textContent = action === 'retirer' ? 'Retirer' : 'Appliquer';
    ok.classList.toggle('ko', action === 'retirer');
    d.showModal();
    d.querySelector('[data-champ="' + action + '"] select, [data-champ="' + action + '"] input:not([type=checkbox]):not(:disabled), [data-champ="' + action + '"] textarea')?.focus();
}
function validerGroupe(){
    const f = document.getElementById('f-groupe');
    // Seuls les champs de l'action choisie partent
    f.querySelectorAll('[data-off]').forEach(i => i.disabled = true);
    if (f.action.value === 'retirer' && f.motif.value.trim().length < 5) { alert('Le motif est obligatoire (5 caractères minimum).'); f.querySelectorAll('[data-off]').forEach(i => i.disabled = false); f.motif.focus(); return false; }
    if (f.action.value === 'classer' && !f.changer_module.checked && !f.changer_epic.checked) { alert('Cochez au moins « Changer la fonctionnalité » ou « Changer l\'epic ».'); f.querySelectorAll('[data-off]').forEach(i => i.disabled = false); return false; }
    return true;
}
document.getElementById('dlg-groupe')?.addEventListener('close', () => { document.querySelectorAll('#f-groupe [data-off]').forEach(i => { i.disabled = false; }); const f = document.getElementById('f-groupe'); if (f) { f.module.disabled = !f.changer_module.checked; f.epic.disabled = !f.changer_epic.checked; } });

function filtrerBacklog(){
    const mod = document.getElementById('fb-module').value, st = document.getElementById('fb-statut').value, ms = document.getElementById('fb-moscow').value, dr = document.getElementById('fb-dor').value, q = document.getElementById('fb-q').value.toLowerCase().trim();
    let n = 0, tot = 0;
    document.querySelectorAll('.module-bloc').forEach(bloc => {
        let vis = 0;
        bloc.querySelectorAll('[data-us]').forEach(us => {
            tot++;
            const ok = (!mod || bloc.dataset.module === mod) && (!st || us.dataset.statut === st) && (!ms || us.dataset.moscow === ms) && (!dr || us.dataset.dor === dr) && (!q || us.dataset.texte.includes(q));
            us.style.display = ok ? '' : 'none'; if (ok) { vis++; n++; }
        });
        bloc.style.display = vis ? '' : 'none';
        // Un tableau (et le titre de son epic) ne reste affiché que s'il garde une story
        bloc.querySelectorAll('.pb-tbl').forEach(tbl => {
            const v = [...tbl.querySelectorAll('[data-us]')].some(x => x.style.display !== 'none');
            tbl.style.display = v ? '' : 'none';
            const titre = tbl.previousElementSibling;
            if (titre && titre.hasAttribute('data-epic')) titre.style.display = v ? '' : 'none';
        });
    });
    const nb = document.getElementById('fb-nb'); if (nb) nb.textContent = n + ' / ' + tot + ' user stories';
    if (typeof majSelection === 'function') majSelection();
}
if (document.getElementById('fb-q')) filtrerBacklog();

@if($peutAlimenter)
// Epics proposés : ceux du module saisi
const EPICS = @json($epics);
function suggererEpics(){
    const m = document.getElementById('st-module').value.trim();
    document.getElementById('st-epics').innerHTML = (EPICS[m] || []).map(e => '<option value="' + e.replace(/"/g, '&quot;') + '">').join('');
}
document.addEventListener('input', e => { if (e.target.id === 'st-module') suggererEpics(); });
// Le profil amorce la formulation de la user story
document.addEventListener('change', e => {
    if (e.target.id !== 'st-profil' || !e.target.value.trim()) return;
    const t = document.getElementById('st-intitule');
    if (!t.value.trim()) t.value = 'En tant que ' + e.target.value.trim() + ', je veux … afin de …';
});
function ouvrirEpic(module, epic){
    document.getElementById('ep-module').value = module;
    document.getElementById('ep-ancien').value = epic;
    const n = document.getElementById('ep-nouveau'); n.value = epic;
    document.getElementById('ep-portee').textContent = 'Fonctionnalité : ' + (module || 'Sans fonctionnalité');
    document.getElementById('dlg-epic').showModal(); n.select();
}
function ouvrirStory(us){
    const f = document.getElementById('form-story');
    f.reset();
    document.getElementById('st-titre').textContent = us ? 'Modifier ' + us.numero : 'Nouvelle user story';
    f.action = us ? '{{ url('user-stories') }}/' + us.id : '{{ route('projets.user-stories.store', $projet) }}';
    document.getElementById('st-method').innerHTML = us ? '<input type="hidden" name="_method" value="PUT">' : '';
    const champs = {numero: 'st-numero', module: 'st-module', epic: 'st-epic', profil: 'st-profil', intitule: 'st-intitule', priorite_moscow: 'st-moscow', effort_estime: 'st-points'};
    Object.entries(champs).forEach(([k, id]) => { document.getElementById(id).value = us ? (us[k] ?? '') : ''; });
    document.getElementById('st-dor').value = us && (us.dor === true || us.dor === '1' || us.dor === 1) ? '1' : '0';
    Redaction.definir('exigences', us ? (us.exigences ?? '') : '');
    document.getElementById('st-id').value = us && us.id ? us.id : '';
    if (!us) document.getElementById('st-numero').value = @json($prochainNumero);
    suggererEpics();
    document.getElementById('dlg-story').showModal();
}
@if($errors->any() && old('intitule'))
{ const o = @json(old()); ouvrirStory(o.story_id ? Object.assign({id: o.story_id}, o) : null);
  ['numero','module','epic','profil','intitule','priorite_moscow','effort_estime','dor'].forEach(k => { const el = document.querySelector('#form-story [name="'+k+'"]'); if (el) el.value = o[k] ?? ''; });
  Redaction.definir('exigences', o.exigences ?? ''); }
@endif
@endif
</script>
@endpush
