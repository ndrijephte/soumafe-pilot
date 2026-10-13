@extends('layouts.app')
@section('sousTitre', 'Gestion du parc')
@section('titre', 'Parc automobile')
@push('styles')
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:20px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;transition:transform .15s,box-shadow .15s}
.kpi:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.06)}
.kpi .lbl{font-size:11px;color:var(--gris);text-transform:uppercase;letter-spacing:.4px}
.kpi .val{font-size:28px;font-weight:700;color:var(--bleu);margin:4px 0}
.kpi .sub{font-size:11px;color:var(--gris)}
.tab-bar{display:flex;gap:0;border-bottom:2px solid var(--bord);margin-bottom:0;overflow-x:auto;scrollbar-width:none}
.tab-bar::-webkit-scrollbar{display:none}
.tab-btn{padding:10px 18px;font-size:13px;font-weight:600;color:var(--gris);background:none;border:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;display:flex;align-items:center;gap:6px;white-space:nowrap;transition:all .15s}
.tab-btn:hover{color:var(--bleu);background:#f8fafc}
.tab-btn.actif{color:#fff;background:var(--accent);border-bottom-color:var(--accent);border-radius:8px 8px 0 0}
.tab-btn svg{width:16px;height:16px}
.tab-panel{display:none}
.tab-panel.actif{display:block}
.panel-head{display:flex;justify-content:space-between;align-items:center;padding:14px 0 10px}
.panel-head h3{font-size:15px;font-weight:700;color:var(--bleu);margin:0;display:flex;align-items:center;gap:8px}
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px}
table{width:100%;border-collapse:collapse;font-size:12.5px}
thead th{background:#f8fafc;border-bottom:2px solid var(--bord);padding:8px 10px;text-align:left;font-size:11px;text-transform:uppercase;color:var(--gris);font-weight:700}
tbody td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
tbody tr:hover{background:#f8fafc}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.btn.ko{background:var(--ko)}
.btn svg{width:14px;height:14px}
.pill{padding:2px 9px;border-radius:10px;font-size:10.5px;font-weight:600}
.p-ok{background:#dcfce7;color:var(--ok)}
.p-warn{background:#fef3c7;color:#b45309}
.p-ko{background:#fee2e2;color:var(--ko)}
.p-cours{background:#dbeafe;color:var(--accent)}
.p-gris{background:#e2e8f0;color:#475569}
.cat-pill{padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;text-transform:uppercase}
.cat-taxi{background:#dbeafe;color:#1d4ed8}
.cat-vtc{background:#f3e8ff;color:#7c3aed}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
dialog{border:none;border-radius:14px;padding:24px;width:560px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog h3{margin:0 0 16px;font-size:16px;color:var(--bleu);display:flex;align-items:center;gap:8px}
dialog h3 svg{width:18px;height:18px;color:var(--accent)}
.dlg-section{font-size:11px;font-weight:700;text-transform:uppercase;color:var(--accent);margin:16px 0 6px;padding-bottom:4px;border-bottom:1px solid #e8f0fe;letter-spacing:.5px}
dialog label{font-size:12px;color:var(--gris);display:block;margin:8px 0 3px;font-weight:600}
dialog input,dialog select,dialog textarea{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog textarea{resize:vertical;min-height:50px}
dialog input:focus,dialog select:focus,dialog textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.req{color:var(--ko)}
.filtre-bar{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center}
.maint-alertes{display:grid;gap:6px;margin:2px 0 14px}
.maint-alerte{display:flex;align-items:center;gap:10px;justify-content:space-between;padding:6px 6px 6px 12px;border:1px solid var(--bord);border-left:4px solid var(--gris);border-radius:8px;font-size:12.5px;background:#fff}
.maint-alerte em{color:var(--gris);font-style:normal}
.maint-alerte.ko{border-left-color:var(--ko);background:#fffafa}.maint-alerte.warn{border-left-color:#f59e0b;background:#fffdf5}
.filtre-resultat{margin-left:auto;font-size:12px;color:var(--mute);white-space:nowrap}
.filtre-bar select,.filtre-bar input[type="month"],.filtre-bar input[type="text"],.filtre-bar input[type="search"]{padding:5px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
#form-saisie-recette select,#form-saisie-recette input[type="number"],#form-saisie-recette input[type="date"],#form-saisie-recette textarea{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;background:#fff;color:#1e293b;transition:border-color .15s,box-shadow .15s;-webkit-appearance:none;appearance:none}
#form-saisie-recette select{background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%231F4E79' stroke-width='2.5'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E") no-repeat right 10px center;padding-right:30px}
#form-saisie-recette select:focus,#form-saisie-recette input[type="number"]:focus,#form-saisie-recette textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
#form-saisie-recette select:hover,#form-saisie-recette input[type="number"]:hover{border-color:var(--accent)}
.urgence-faible{color:#475569}.urgence-moyenne{color:var(--warn)}.urgence-haute{color:#ea580c;font-weight:700}.urgence-critique{color:var(--ko);font-weight:700}
.detail-row td{padding:0!important;border-bottom:1px solid #f1f5f9}
.detail-content{background:#f8fafc;padding:8px 16px;font-size:12px;color:#334155;line-height:1.6;border-left:3px solid var(--accent)}
.detail-content strong{color:var(--bleu);font-size:11px;text-transform:uppercase;letter-spacing:.3px}
.act-btn.info{color:var(--accent)}
.act-btn.info:hover{background:rgba(46,116,181,.08)}
.act-row{display:flex;gap:4px;align-items:center}
.act-btn{background:none;border:none;cursor:pointer;padding:3px;border-radius:4px;color:var(--gris)}
.act-btn:hover{background:#f1f5f9;color:var(--bleu)}
.act-btn.del{color:var(--ko)}
.act-btn.del:hover{background:#fef2f2}
.edit-icon-btn{width:32px;height:32px;border-radius:50%;background:rgba(46,116,181,.1);color:var(--accent);display:inline-flex;align-items:center;justify-content:center;transition:all .2s ease;border:none;cursor:pointer;padding:0}
.edit-icon-btn:hover{background:var(--accent);color:#fff;transform:scale(1.1);box-shadow:0 2px 8px rgba(31,78,121,.25)}
.header-actions{display:flex;gap:8px;align-items:center}
.empty-msg{text-align:center;color:var(--gris);padding:24px;font-size:12.5px}
.dg-mode .act-row,.dg-mode .gc-detail,.dg-mode .gc-chevron,.dg-mode #form-grille-ind{display:none!important}
@media(max-width:900px){.kpis{grid-template-columns:repeat(2,1fr)}.form-row,.form-row3{grid-template-columns:1fr}table{display:block;overflow-x:auto}thead{display:table-header-group}tbody{display:table-row-group}.panel-head{flex-wrap:wrap;gap:8px}.header-actions{flex-wrap:wrap}.filtre-bar{flex-direction:column;align-items:stretch}}
@media(max-width:600px){.kpis{grid-template-columns:1fr}.kpi .val{font-size:22px}.tab-btn{padding:8px 12px;font-size:12px}.tab-btn svg{width:14px;height:14px}.panneau{padding:10px 8px}.panel-head h3{font-size:13px}.btn.sm{padding:4px 8px;font-size:10.5px}dialog{padding:16px;width:95vw}.act-row{gap:2px}.type-item{flex-wrap:wrap;gap:6px}.type-item .code{display:none}}
@media(max-width:400px){.tab-btn span.pill{display:none}.tab-btn{padding:6px 8px;gap:4px}.kpi{padding:10px}.gc-row{padding:10px 12px;gap:8px}.gc-montant{font-size:13px;min-width:60px}}
.scroll-wrap{position:relative}
.scroll-wrap .scroll-arrow{position:absolute;top:50%;transform:translateY(-50%);width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;border:none;cursor:pointer;display:none;align-items:center;justify-content:center;z-index:2;box-shadow:0 2px 6px rgba(0,0,0,.15);opacity:.85;transition:opacity .15s}
.scroll-wrap .scroll-arrow:hover{opacity:1}
.scroll-wrap .scroll-arrow.left{left:2px}
.scroll-wrap .scroll-arrow.right{right:2px}
.scroll-wrap .scroll-arrow svg{width:14px;height:14px}
/* Grille chauffeurs recettes */
.gc-list{display:flex;flex-direction:column;gap:6px}
.gc-item{background:var(--carte);border:1px solid var(--bord);border-radius:10px;overflow:hidden;transition:box-shadow .15s}
.gc-item:hover{box-shadow:0 2px 8px rgba(0,0,0,.06)}
.gc-item.gc-repos{border-left:3px solid var(--ok);background:#f0fdf4}
.gc-item.gc-inactif{opacity:.65;border-left:3px solid var(--gris)}
.gc-av-off{background:var(--gris)!important;color:#fff!important}
.gc-item.gc-fait{border-left:3px solid var(--accent)}
.gc-item.gc-bloque{border-left:3px solid var(--ko);background:#fef2f2;opacity:.7}
.gc-row{display:flex;align-items:center;gap:12px;padding:12px 16px;cursor:pointer;user-select:none}
.gc-row:hover{background:rgba(46,116,181,.04)}
.gc-avatar{width:36px;height:36px;border-radius:50%;background:var(--fond);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--bleu)}
.gc-info{flex:1;min-width:0}
.gc-nom{font-size:13.5px;font-weight:700;color:var(--bleu);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.gc-sub{font-size:11.5px;color:var(--gris);display:flex;align-items:center;gap:6px;margin-top:2px}
.gc-montant{font-size:15px;font-weight:700;color:var(--bleu);text-align:right;min-width:80px}
.gc-montant.gc-ok{color:var(--ok)}
.gc-montant.gc-ko{color:var(--ko)}
.gc-chevron{width:20px;height:20px;color:var(--gris);transition:transform .2s;flex-shrink:0}
.gc-item.gc-open .gc-chevron{transform:rotate(180deg)}
.gc-detail{max-height:0;overflow:hidden;transition:max-height .25s ease-out}
.gc-item.gc-open .gc-detail{max-height:600px}
.gc-form{padding:0 16px 14px;display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end}
.gc-form label{font-size:10.5px;color:var(--gris);font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.gc-form input,.gc-form select{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;width:100%}
.gc-form input[type=number]{text-align:right}
.gc-form .gc-btn-val{padding:7px 16px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;border:none;display:flex;align-items:center;gap:4px;white-space:nowrap}
.gc-form .gc-btn-val.nouveau{background:var(--accent);color:#fff}
.gc-form .gc-btn-val.maj{background:var(--ok);color:#fff}
@media(max-width:700px){.gc-form{grid-template-columns:1fr 1fr;gap:8px}.gc-montant{min-width:60px;font-size:13px}}
</style>
@endpush
@section('contenu')
@php
$ic = fn(string $d, int $s=17) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
$icCar = $ic('<rect x="1" y="6" width="22" height="12" rx="3"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/><path d="M5 10h14"/>');
$icWrench = $ic('<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>');
$icWallet = $ic('<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h2"/>');
$icCalCheck = $ic('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/>');
$icAlert = $ic('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>');
$icEdit = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
$icDel = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';
$icPlus = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
$icEye = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
$icUser = $ic('<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>');
$icDepense = $ic('<path d="M2 7h20M5 7v12a2 2 0 002 2h10a2 2 0 002-2V7M9 11v4M15 11v4M10 3h4l1 4H9l1-4z"/>');

$estDG = auth()->user()->aRole('direction');
$estChargeOps = auth()->user()->estAdmin();

$marques = $vehicules->pluck('marque')->filter()->unique()->sort()->values()->toArray();
$marquesSugg = array_unique(array_merge($marques, ['Toyota', 'Hyundai', 'Kia', 'Renault', 'Peugeot', 'Suzuki', 'Nissan', 'Mitsubishi']));
sort($marquesSugg);

$lieuxSugg = ['Abidjan', 'Yamoussoukro', 'Bouaké', 'San Pedro', 'Daloa', 'Korhogo'];
if (\Illuminate\Support\Facades\Schema::hasTable('penalites')) {
    $lieuxExist = \App\Models\Penalite::distinct()->pluck('lieu')->filter()->toArray();
    $lieuxSugg = array_values(array_unique(array_merge($lieuxExist, $lieuxSugg)));
    sort($lieuxSugg);
}
@endphp

<datalist id="dl-marques">@foreach($marquesSugg as $m)<option value="{{ $m }}">@endforeach</datalist>

<div class="{{ $estDG ? 'dg-mode' : '' }}">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;flex-wrap:wrap;gap:8px">
    <h2 style="display:flex;align-items:center;gap:8px">{!! $icCar !!} Parc automobile</h2>
    <div class="header-actions">
        <a href="{{ route('reporting.parc') }}" class="btn sm sec" title="Reporting parc">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg> Reporting
        </a>
        @if(auth()->user()->estAdmin() && auth()->user()->peutGererParc())
        <a href="{{ route('admin.parc-parametres.index') }}" class="btn sm sec" title="Paramétrage du parc">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg> Param.
        </a>
        @endif
    </div>
</div>
<div class="crumb">Opérations &rsaquo; Parc automobile</div>

@if(session('succes'))<div class="alerte ok">{!! $ic('<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',16) !!} {{ session('succes') }}</div>@endif

{{-- KPI --}}
<form method="GET" action="{{ route('parc.index') }}" id="form-exercice" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:4px 0 12px;padding:8px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;font-size:13px;color:#1e40af">
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <label for="sel-exercice" style="font-weight:600">Exercice affiché</label>
    <select name="exercice" id="sel-exercice" onchange="changerExercice()" style="padding:6px 10px;border:1px solid #93c5fd;border-radius:8px;font-size:13px;font-weight:600;color:#1e3a8a;background:#fff">
        @foreach($exercicesParc as $cle => $ex)
        <option value="{{ $cle }}" @selected($exercice === $cle)>{{ $ex['libelle'] }}{{ $cle === $exerciceCourant ? ' — en cours' : '' }}</option>
        @endforeach
        <option value="tous" @selected($exercice === 'tous')>Toutes les périodes</option>
    </select>
    <input type="hidden" name="tab" id="ex-tab" value="{{ request('tab') }}">
    @if(request('date_recette'))<input type="hidden" name="date_recette" value="{{ request('date_recette') }}">@endif
    <span style="color:#475569;font-size:12px">Listes, totaux et indicateurs ci-dessous portent sur cet exercice.</span>
    @if($exercice !== $exerciceCourant)<a href="{{ route('parc.index', ['tab' => request('tab')]) }}" style="margin-left:auto;font-weight:600;color:#1e40af">Revenir à l'exercice en cours</a>@endif
</form>
<div class="kpis">
    <div class="kpi"><div class="lbl">En service</div><div class="val">{{ $kpi['actifs'] }}<span style="font-size:13px;color:var(--gris);font-weight:400">/{{ $kpi['total'] }}</span></div><div class="sub">{{ $kpi['taxis'] }} Taxis · {{ $kpi['vtcs'] }} VTC</div></div>
    <div class="kpi" @if($kpi['pannes_ouvertes']>0) style="background:#fff7ed;border-color:#fed7aa" @endif><div class="lbl">Pannes ouvertes</div><div class="val" style="color:{{ $kpi['pannes_ouvertes']>0 ? '#b45309' : 'var(--ok)' }}">{{ $kpi['pannes_ouvertes'] }}</div></div>
    <div class="kpi"><div class="lbl">Coût pannes (exercice)</div><div class="val" style="font-size:22px">{{ number_format($kpi['cout_mois'], 0, ',', ' ') }}</div><div class="sub">FCFA</div></div>
    <div class="kpi"><div class="lbl">Recettes de l'exercice</div><div class="val" style="font-size:20px">{{ number_format($kpi['recette_totale'], 0, ',', ' ') }}</div><div class="sub">dont recouv. {{ number_format($kpi['recouvrement_mois'], 0, ',', ' ') }} F · obj. {{ number_format($kpi['objectif_mois'], 0, ',', ' ') }}</div></div>
    <div class="kpi"><div class="lbl">Taux recette / obj.</div><div class="val" style="color:{{ $kpi['taux_recette'] >= 100 ? 'var(--ok)' : ($kpi['taux_recette'] >= 80 ? '#b45309' : 'var(--ko)') }}">{{ $kpi['taux_recette'] }}%</div></div>
    <div class="kpi" @if($kpi['echeances_retard']>0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">Échéances en retard</div><div class="val" style="color:{{ $kpi['echeances_retard']>0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $kpi['echeances_retard'] }}</div></div>
    <div class="kpi" @if($kpi['penalites_non_payees']>0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">Pénalités impayées</div><div class="val" style="color:{{ $kpi['penalites_non_payees']>0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $kpi['penalites_non_payees'] }}</div></div>
</div>

{{-- Onglets --}}
<div class="scroll-wrap tab-scroll-wrap">
<button type="button" class="scroll-arrow left" id="tab-arrow-l" onclick="document.getElementById('tab-bar').scrollBy({left:-200,behavior:'smooth'})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg></button>
<div class="tab-bar" id="tab-bar">
    <button class="tab-btn actif" data-onglet="vehicules" onclick="changerOnglet('vehicules')">{!! $icCar !!} Véhicules <span class="pill p-cours">{{ $vehicules->count() }}</span></button>
    <button class="tab-btn" data-onglet="chauffeurs" onclick="changerOnglet('chauffeurs')">{!! $icUser !!} Chauffeurs <span class="pill p-cours">{{ $chauffeurs->count() }}</span></button>
    <button class="tab-btn" data-onglet="recettes" onclick="changerOnglet('recettes')">{!! $icWallet !!} Recettes</button>
    <button class="tab-btn" data-onglet="maint" onclick="changerOnglet('maint')">{!! $icCalCheck !!} Maintenance @if($kpi['echeances_retard']>0)<span class="pill p-ko">{{ $kpi['echeances_retard'] }}</span>@endif</button>
    <button class="tab-btn" data-onglet="pannes" onclick="changerOnglet('pannes')">{!! $icWrench !!} Pannes @if($kpi['pannes_ouvertes']>0)<span class="pill p-ko">{{ $kpi['pannes_ouvertes'] }}</span>@endif</button>
    <button class="tab-btn" data-onglet="recouv" onclick="changerOnglet('recouv')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h2"/></svg>
        Recouvrements @if(($recouvrements ?? collect())->where('statut','actif')->count()>0)<span class="pill p-ko">{{ ($recouvrements ?? collect())->where('statut','actif')->count() }}</span>@endif</button>
    <button class="tab-btn" data-onglet="depenses" onclick="changerOnglet('depenses')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 7h20M5 7v12a2 2 0 002 2h10a2 2 0 002-2V7M9 11v4M15 11v4M10 3h4l1 4H9l1-4z"/></svg>
        Dépenses bureau <span class="pill p-cours">{{ ($depenses ?? collect())->count() }}</span></button>
    <button class="tab-btn" data-onglet="penal" onclick="changerOnglet('penal')">{!! $icAlert !!} Pénalités @if($kpi['penalites_non_payees']>0)<span class="pill p-ko">{{ $kpi['penalites_non_payees'] }}</span>@endif</button>
    <button class="tab-btn" data-onglet="emprunts" onclick="changerOnglet('emprunts')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Emprunts @if(($emprunts ?? collect())->where('statut','en_cours')->count()>0)<span class="pill p-ko">{{ ($emprunts ?? collect())->where('statut','en_cours')->count() }}</span> @endif</button>
    <button class="tab-btn" data-onglet="prets" onclick="changerOnglet('prets')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Prêts @if(($pretsAccordes ?? collect())->where('statut','en_cours')->count()>0)<span class="pill p-ko">{{ ($pretsAccordes ?? collect())->where('statut','en_cours')->count() }}</span> @endif</button>
    <button class="tab-btn" data-onglet="ressources" onclick="changerOnglet('ressources')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        Ressources <span class="pill p-cours">{{ ($autresRessources ?? collect())->count() }}</span></button>
</div>
<button type="button" class="scroll-arrow right" id="tab-arrow-r" onclick="document.getElementById('tab-bar').scrollBy({left:200,behavior:'smooth'})"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 6 15 12 9 18"/></svg></button>
</div>

{{-- ==================== TAB VÉHICULES ==================== --}}
<div class="tab-panel actif" id="tab-vehicules">
    <div class="panel-head">
        <h3>{!! $icCar !!} Véhicules <span class="pill p-cours">{{ $vehicules->count() }}</span></h3>
        @if(!$estDG)<button class="btn sm" onclick="document.getElementById('dlg-vehicule').showModal()">{!! $icPlus !!} Véhicule</button>@endif
    </div>
    @include('parc._filtre', ['cible' => 'tbl-vehicules', 'champs' => [
        'categorie' => ['Toutes catégories', ['taxi' => 'Taxi', 'vtc' => 'VTC']],
        'statut' => ['Tous les statuts', ['en_service' => 'En service', 'immobilise' => 'Immobilisé', 'reforme' => 'Réformé']],
        'chauffeur' => true, 'recherche' => 'Immatriculation, marque…']])
    <div class="panneau" style="overflow-x:auto">
    <table id="tbl-vehicules">
    <thead><tr><th>Immatriculation</th><th>Cat.</th><th>Marque / Modèle</th><th>Chauffeur</th><th>Km</th><th>Mise en service</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($vehicules as $v)
    <tr data-ligne data-categorie="{{ $v->categorie }}" data-statut="{{ $v->statut }}" data-chauffeur="{{ $v->chauffeurs->where('pivot.actif', true)->map(fn($c) => $c->nomComplet())->join('|') }}">
        <td><strong>{{ $v->immatriculation }}</strong></td>
        <td><span class="cat-pill cat-{{ $v->categorie }}">{{ $v->categorieLabel() }}</span></td>
        <td>{{ $v->marque }} {{ $v->modele }}</td>
        @php $chAff = $v->chauffeurs->where('pivot.actif', true); @endphp
        <td>{{ $chAff->isNotEmpty() ? $chAff->map(fn($c) => $c->nomComplet())->join(', ') : '—' }}</td>
        <td>{{ $v->kilometrage ? number_format($v->kilometrage, 0, ',', ' ') : '—' }}</td>
        <td>{{ $v->date_mise_en_service ? $v->date_mise_en_service->format('d/m/Y') : '—' }}</td>
        <td><span class="pill {{ $v->statut === 'en_service' ? 'p-ok' : ($v->statut === 'immobilise' ? 'p-warn' : 'p-ko') }}">{{ $v->statutLabel() }}</span></td>
        <td>
            <div class="act-row">
                @if($v->observations)
                <button class="act-btn info" title="Détails" onclick="toggleDetail('veh-{{ $v->id }}')">{!! $icEye !!}</button>
                @endif
                <button class="act-btn" title="Fiche" onclick="ficheVehicule({{ $v->id }})" style="color:var(--accent)"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg></button>
                <button class="act-btn" title="Modifier" onclick="editVehicule({{ $v->id }})">{!! $icEdit !!}</button>
                @if(auth()->user()->estAdmin())
                <form method="POST" action="{{ route('parc.vehicules.destroy', $v) }}" onsubmit="return demanderMotifSuppression(this,'ce véhicule')">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @if($v->observations)
    <tr class="detail-row" id="veh-{{ $v->id }}" style="display:none"><td colspan="8"><div class="detail-content"><strong>Observations :</strong> {{ $v->observations }}</div></td></tr>
    @endif
    @empty
    <tr><td colspan="8" class="empty-msg">Aucun véhicule enregistré.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB PANNES ==================== --}}
<div class="tab-panel" id="tab-pannes">
    @include('parc._reports', ['onglet' => 'pannes'])
    <div class="panel-head">
        <h3>{!! $icWrench !!} Pannes @if($kpi['pannes_ouvertes']>0)<span class="pill p-ko">{{ $kpi['pannes_ouvertes'] }} ouvertes</span>@endif</h3>
        @if(!$estDG)<button class="btn sm" onclick="document.getElementById('dlg-panne').showModal()">{!! $icPlus !!} Panne</button>@endif
    </div>
    @php $coutTotalPannes = $pannes->sum('cout_reel'); @endphp
    @if($coutTotalPannes > 0)
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:8px 14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;font-size:12.5px">
        <span style="font-weight:700;color:var(--ko)">Coût total pannes : {{ number_format($coutTotalPannes, 0, ',', ' ') }} F</span>
        <span style="color:var(--gris)">({{ $pannes->count() }} panne(s))</span>
    </div>
    @endif
    @include('parc._filtre', ['cible' => 'tbl-pannes', 'champs' => [
        'vehicule' => true,
        'statut' => ['Tous les statuts', ['ouverte' => 'Non clôturées', 'declaree' => 'Déclarée', 'en_diagnostic' => 'En diagnostic', 'en_intervention' => 'En intervention', 'cloturee' => 'Clôturée']],
        'type' => ['Toutes urgences', ['critique' => 'Critique', 'haute' => 'Haute', 'moyenne' => 'Moyenne', 'faible' => 'Faible']],
        'recherche' => 'Description, diagnostic…']])
    <div class="panneau" style="overflow-x:auto">
    <table id="tbl-pannes">
    <thead><tr><th>Véhicule</th><th>Date</th><th>Description</th><th>Urgence</th><th>Statut</th><th>Coût</th><th>Délai</th><th></th></tr></thead>
    <tbody>
    @forelse($pannes as $p)
    <tr data-ligne data-date="{{ $p->date_declaration?->format('Y-m-d') }}" data-vehicule="{{ $p->vehicule_id }}" data-statut="{{ $p->statut }}{{ $p->statut !== 'cloturee' ? '|ouverte' : '' }}" data-type="{{ $p->urgence }}" data-montant="{{ (int) $p->cout_reel }}">
        <td><strong>{{ $p->vehicule->immatriculation ?? '?' }}</strong></td>
        <td>{{ $p->date_declaration ? $p->date_declaration->format('d/m/Y') : '—' }}</td>
        <td style="max-width:200px">{{ Str::limit($p->description, 60) }}</td>
        <td><span class="urgence-{{ $p->urgence }}">{{ ucfirst($p->urgence) }}</span></td>
        <td><span class="pill {{ $p->statut === 'cloturee' ? 'p-ok' : ($p->statut === 'declaree' ? 'p-ko' : 'p-cours') }}">{{ $p->statutLabel() }}</span></td>
        <td>{{ $p->cout_reel ? number_format($p->cout_reel, 0, ',', ' ') : '—' }} @if($p->emprunt_id) <span class="pill p-ko" style="font-size:9px">Emprunt</span> @endif</td>
        <td>{{ $p->delai_resolution !== null ? $p->delai_resolution.'j' : '—' }}</td>
        <td>
            <div class="act-row">
                @if($p->description || $p->diagnostic || $p->decision || $p->lignes->count())
                <button class="act-btn info" title="Détails" onclick="toggleDetail('panne-{{ $p->id }}')">{!! $icEye !!}</button>
                @endif
                <button class="act-btn" title="Fiche" onclick="ficheVehicule({{ $p->vehicule_id }})" style="color:var(--accent)"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg></button>
                @if($p->statut !== 'cloturee')
                <button class="act-btn" title="Statuer" onclick="statuerPanne({{ $p->id }})" style="color:var(--accent)">{!! $icWrench !!}</button>
                @endif
                <button class="act-btn" title="Modifier" onclick="editPanne({{ $p->id }})">{!! $icEdit !!}</button>
                @if(auth()->user()->estAdmin())
                <form method="POST" action="{{ route('parc.pannes.destroy', $p) }}" onsubmit="return demanderMotifSuppression(this,'cette panne')">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @if($p->description || $p->diagnostic || $p->decision || $p->lignes->count())
    <tr class="detail-row" id="panne-{{ $p->id }}" style="display:none"><td colspan="8"><div class="detail-content">
        @if($p->description)<strong>Description :</strong> {{ $p->description }}<br>@endif
        @if($p->diagnostic)<strong>Diagnostic :</strong> {{ $p->diagnostic }}<br>@endif
        @if($p->decision)<strong>Decision :</strong> {{ $p->decision }}<br>@endif
        @if($p->lignes->count())
        <div style="margin-top:8px">
            <strong>Details intervention :</strong>
            <table style="width:100%;max-width:500px;border-collapse:collapse;font-size:12px;margin-top:4px">
                <thead><tr style="background:var(--fond)"><th style="text-align:left;padding:3px 6px">Designation</th><th style="text-align:center;padding:3px 4px">Qte</th><th style="text-align:right;padding:3px 4px">P.U.</th><th style="text-align:right;padding:3px 4px">Montant</th></tr></thead>
                <tbody>
                @foreach($p->lignes as $li)
                <tr style="border-bottom:1px solid var(--bord)">
                    <td style="padding:3px 6px">{{ $li->designation }}</td>
                    <td style="text-align:center;padding:3px 4px">{{ $li->quantite }}</td>
                    <td style="text-align:right;padding:3px 4px">{{ number_format($li->prix_unitaire, 0, ',', ' ') }}</td>
                    <td style="text-align:right;padding:3px 4px">{{ number_format($li->montant, 0, ',', ' ') }}</td>
                </tr>
                @endforeach
                </tbody>
                <tfoot><tr style="border-top:2px solid var(--accent)"><td colspan="3" style="text-align:right;padding:3px 6px;font-weight:700">Total</td><td style="text-align:right;padding:3px 4px;font-weight:700">{{ number_format($p->lignes->sum('montant'), 0, ',', ' ') }} F</td></tr></tfoot>
            </table>
        </div>
        @endif
    </div></td></tr>
    @endif
    @empty
    <tr><td colspan="8" class="empty-msg">Aucune panne enregistrée.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB RECETTES ==================== --}}
<div class="tab-panel" id="tab-recettes">

    {{-- ===== ALERTES JOURS MANQUANTS (compact) ===== --}}
    @if(!empty($alertesManquants))
    <div style="margin-bottom:12px">
        <div onclick="document.getElementById('alertes-detail').style.display=document.getElementById('alertes-detail').style.display==='none'?'':'none'" style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:5px 12px;font-size:12px;color:#991b1b">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <strong>{{ count($alertesManquants) }} jour(s) incomplet(s)</strong>
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div id="alertes-detail" style="display:none;margin-top:6px">
            @foreach($alertesManquants as $am)
            <div style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:12px;color:#991b1b;border-bottom:1px solid #fef2f2">
                <span style="min-width:90px;font-weight:600">{{ $am['date_label'] }}</span>
                <span style="flex:1;color:#6b7280">{{ $am['count'] }} véh. : {{ $am['vehicules'] }}</span>
                <a href="{{ route('parc.index', ['tab' => 'recettes', 'date_recette' => $am['date']]) }}" class="btn sm" style="padding:2px 8px;font-size:10px">Saisir</a>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ===== SÉLECTION DATE ===== --}}
    @php
        $dateJourCarbon = \Carbon\Carbon::parse($dateJour);
        $jourLabel = $dateJourCarbon->translatedFormat('l d F Y');
        $estAujourdhui = $dateJourCarbon->isToday();
        $typeJourGrille = $dateJourCarbon->isSunday() ? 'dimanche' : 'ouvrable';
        $nbSaisis = collect($grilleJour)->where('deja_saisi', true)->count();
        $nbTotal = count($grilleJour);
        $tousValides = $nbSaisis === $nbTotal && $nbTotal > 0;
    @endphp

    <div class="panel-head" style="flex-wrap:wrap;gap:10px">
        <h3>{!! $icWallet !!} Recettes du jour</h3>
        <form method="GET" action="{{ route('parc.index') }}" style="display:flex;align-items:center;gap:8px">
            <input type="hidden" name="tab" value="recettes">
            <a href="{{ route('parc.index', ['tab' => 'recettes', 'date_recette' => $dateJourCarbon->copy()->subDay()->format('Y-m-d')]) }}" class="btn sm sec" style="padding:4px 10px">&larr;</a>
            <input type="date" name="date_recette" value="{{ $dateJour }}" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px">
            <a href="{{ route('parc.index', ['tab' => 'recettes', 'date_recette' => $dateJourCarbon->copy()->addDay()->format('Y-m-d')]) }}" class="btn sm sec" style="padding:4px 10px">&rarr;</a>
            @php $hierDate = \Carbon\Carbon::yesterday(); @endphp
            @if(!$dateJourCarbon->isSameDay($hierDate))
            <a href="{{ route('parc.index', ['tab' => 'recettes']) }}" class="btn sm sec" style="padding:4px 10px;font-size:11px">Hier</a>
            @endif
        </form>
        <div style="display:flex;gap:8px;align-items:center;margin-left:auto">
            @if(!$estDG)
            <button type="button" class="btn sm" onclick="ouvrirSaisieIndiv()" style="background:var(--accent);color:#fff">
                {!! $ic('M12 5v14 M5 12h14', 14) !!} Saisie individuelle
            </button>
            @endif
            <button type="button" class="btn sm sec" onclick="ouvrirHistoRecettes()">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Historique
            </button>
        </div>
    </div>

    @php
        $hier = \Carbon\Carbon::yesterday();
        $estHier = $dateJourCarbon->isSameDay($hier);
    @endphp
    <div style="margin-bottom:10px;padding:8px 14px;background:{{ $estHier ? '#eff6ff' : '#fef3c7' }};border:1px solid {{ $estHier ? '#bfdbfe' : '#fcd34d' }};border-radius:8px;font-size:12.5px;color:{{ $estHier ? '#1e40af' : '#92400e' }}">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="{{ $estHier ? '#2563eb' : '#d97706' }}" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Recettes du <strong>{{ $dateJourCarbon->translatedFormat('l d F Y') }}</strong>
        @if($estHier) (veille — par défaut) @endif
        @if(!$estHier)
        — <a href="{{ route('parc.index', ['tab' => 'recettes']) }}" style="color:#1e40af;text-decoration:underline;margin-left:4px">Revenir à hier</a>
        @endif
    </div>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap">
        <span style="font-size:14px;font-weight:700;color:var(--bleu);text-transform:capitalize">{{ $jourLabel }}</span>
        @if($typeJourGrille === 'dimanche')
        <span class="pill p-warn">Dimanche — VTC repos / Taxi 5 000 F</span>
        @endif
        <span class="pill {{ $tousValides ? 'p-ok' : ($nbSaisis > 0 ? 'p-cours' : 'p-ko') }}">{{ $nbSaisis }}/{{ $nbTotal }} saisi(s)</span>
    </div>

    {{-- Erreurs de validation --}}
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;margin-bottom:12px">
        @foreach($errors->all() as $err)
        <div style="font-size:12.5px;color:#b91c1c">{{ $err }}</div>
        @endforeach
    </div>
    @endif

    {{-- ===== FORMULAIRE + RECAPITULATIF RECETTES ===== --}}
    @php
        $chauffeursPending = collect($grilleJour)->filter(fn($g) => !$g['deja_saisi'] && ($g['chauffeur_actif'] ?? true) && !($g['vehicule_pris_par'] ?? null));
        $chauffeursFaits = collect($grilleJour)->filter(fn($g) => $g['deja_saisi']);
        $totalRecJour = $chauffeursFaits->sum(fn($g) => $g['recette']->recette ?? 0);
        $totalObjJour = $chauffeursFaits->sum(fn($g) => $g['recette']->objectif ?? 0);
        $totalPtgJour = $chauffeursFaits->sum(fn($g) => $g['recette']->pointage ?? 0);
    @endphp

    {{-- ===== RECAPITULATIF DU JOUR (toujours visible, en premier) ===== --}}
    @if($chauffeursFaits->count() > 0)
    <div style="margin-bottom:16px">
        {{-- En-tete recap avec totaux --}}
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap">
            {!! $ic('M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z M14 2v6h6 M16 13H8 M16 17H8', 16) !!}
            <span style="font-size:14px;font-weight:700;color:var(--bleu)">Saisies du jour</span>
            <span class="pill {{ $tousValides ? 'p-ok' : 'p-cours' }}">{{ $nbSaisis }}/{{ $nbTotal }}</span>
            <span style="margin-left:auto;font-size:12px;color:var(--mute)">
                Total : <strong style="color:var(--bleu)">{{ number_format($totalRecJour, 0, ',', ' ') }} F</strong>
                @if($totalPtgJour > 0) + {{ number_format($totalPtgJour, 0, ',', ' ') }} F ptg @endif
            </span>
        </div>

        @include('parc._filtre', ['cible' => 'grille-saisies', 'champs' => [
            'vehicule' => true,
            'statut' => ['Tous les résultats', ['atteint' => 'Objectif atteint', 'justifie' => 'Écart justifié', 'non_atteint' => 'Objectif non atteint']],
            'recherche' => 'Chauffeur, véhicule…']])
        {{-- Cards recettes saisies --}}
        <div id="grille-saisies" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:10px">
        @foreach($grilleJour as $i => $g)
            @if($g['deja_saisi'])
            @php
                $rv = $g['vehicule'];
                $rc = $g['recette'];
                $ecOk = ($rc->ecart ?? 0) >= 0;
                $statutMotif = $rc->statut_motif ?? (($rc->justification_valable ?? false) ? 'valide' : 'en_attente');
                $borderColor = $ecOk ? '#86efac' : ($statutMotif === 'valide' ? '#fde68a' : '#fca5a5');
            @endphp
            <div data-ligne data-vehicule="{{ $rv->id }}" data-statut="{{ $ecOk ? 'atteint' : ($statutMotif === 'valide' ? 'justifie' : 'non_atteint') }}" data-montant="{{ (int) ($rc->recette ?? 0) }}" style="background:var(--carte);border:1px solid {{ $borderColor }};border-radius:10px;padding:12px 14px;position:relative;transition:box-shadow .15s" onmouseenter="this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'" onmouseleave="this.style.boxShadow='none'">
                {{-- Ligne 1 : chauffeur + statut --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                    <div style="display:flex;align-items:center;gap:6px">
                        <div style="width:28px;height:28px;border-radius:50%;background:{{ $ecOk ? '#dcfce7' : '#fef2f2' }};display:flex;align-items:center;justify-content:center">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="{{ $ecOk ? '#16a34a' : '#dc2626' }}" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:var(--texte)">{{ $g['chauffeur'] ?: 'Sans chauffeur' }}</div>
                            <div style="font-size:11px;color:var(--mute)">{{ $rv->immatriculation }}</div>
                        </div>
                    </div>
                    @if($ecOk)
                    <span class="pill p-ok" style="font-size:10px">Atteint</span>
                    @elseif($statutMotif === 'valide')
                    <span class="pill p-warn" style="font-size:10px">Motif valide</span>
                    @else
                    <span class="pill p-ko" style="font-size:10px">Deficit</span>
                    @endif
                </div>
                {{-- Ligne 2 : montants --}}
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:4px;text-align:center;padding:6px 0;border-top:1px solid var(--bord);border-bottom:1px solid var(--bord)">
                    <div>
                        <div style="font-size:10px;color:var(--mute);text-transform:uppercase;letter-spacing:.3px">Recette</div>
                        <div style="font-size:14px;font-weight:700;color:var(--bleu)">{{ number_format($rc->recette, 0, ',', ' ') }}</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--mute);text-transform:uppercase;letter-spacing:.3px">Objectif</div>
                        <div style="font-size:13px;font-weight:600;color:var(--texte)">{{ number_format($rc->objectif, 0, ',', ' ') }}</div>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--mute);text-transform:uppercase;letter-spacing:.3px">Ecart</div>
                        <div style="font-size:13px;font-weight:700;color:{{ $ecOk ? 'var(--ok)' : 'var(--ko)' }}">{{ ($rc->ecart >= 0 ? '+' : '') . number_format($rc->ecart, 0, ',', ' ') }}</div>
                    </div>
                </div>
                {{-- Ligne 3 : details + bouton --}}
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:6px">
                    <div style="font-size:11px;color:var(--mute)">
                        @if(($rc->pointage ?? 0) > 0)<span style="margin-right:8px">Ptg: {{ number_format($rc->pointage, 0, ',', ' ') }} F</span> @endif
                        @if($rc->mode_remise)<span>{{ ucfirst(str_replace('_', ' ', $rc->mode_remise)) }}</span> @endif
                    </div>
                    @if(auth()->user()->estAdmin())
                    <button class="act-btn edit-icon-btn" title="Modifier cette recette" onclick="editRecette({{ $rc->id }})">{!! $ic('M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7 M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z', 15) !!}</button>
                    @endif
                </div>
            </div>
            @endif
        @endforeach
        </div>
    </div>
    @endif

    {{-- ===== FORMULAIRE DE SAISIE ===== --}}
    @if(!$estDG)
    @if($chauffeursPending->isEmpty() && $nbTotal > 0)
    <div style="text-align:center;padding:20px 16px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #86efac;border-radius:12px;margin-bottom:14px">
        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="#16a34a" stroke-width="2" style="margin-bottom:4px"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <div style="font-size:16px;font-weight:700;color:#166534;margin-top:2px">Journee complete</div>
        <div style="font-size:12px;color:#15803d;margin-top:2px">{{ $nbTotal }} recette(s) enregistree(s) — Total : {{ number_format($totalRecJour, 0, ',', ' ') }} F</div>
    </div>
    @else
    <div style="margin-bottom:16px;border-radius:12px;overflow:hidden;border:1px solid var(--bord);background:var(--carte)">
        {{-- En-tete formulaire --}}
        <div style="background:linear-gradient(135deg,var(--bleu),var(--accent));padding:12px 18px;display:flex;align-items:center;gap:8px">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span style="font-size:14px;font-weight:700;color:#fff">Saisie de recette</span>
            <span style="margin-left:auto;font-size:11px;color:rgba(255,255,255,.8);background:rgba(255,255,255,.15);padding:2px 10px;border-radius:20px">{{ $chauffeursPending->count() }} en attente</span>
        </div>

        <form method="POST" action="{{ route('parc.recettes.store') }}" id="form-saisie-recette" onsubmit="return validerFormSaisie()" style="padding:16px 18px">
            @csrf
            <input type="hidden" name="vehicule_id" id="sr-vehic">

            {{-- Section 0 : Date de la recette --}}
            <div style="margin-bottom:12px">
                <label style="font-size:12px;font-weight:600;color:var(--bleu)">Date de la recette <span class="req">*</span></label>
                <input type="date" name="date" id="sr-date" value="{{ $dateJour }}" required max="{{ date('Y-m-d') }}" style="margin-top:4px;max-width:200px" onchange="onSrDateChange()">
                <span style="font-size:11px;color:var(--mute);margin-left:8px">Par defaut : veille. Modifiable pour saisir des recettes anterieures.</span>
            </div>

            {{-- Section 1 : Identification --}}
            <div style="display:grid;grid-template-columns:2fr 1fr;gap:12px">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--bleu)">Chauffeur <span class="req">*</span></label>
                    <select name="chauffeur" id="sr-chauff" required onchange="onSrChauffeurChange()" style="margin-top:4px">
                        <option value="">-- Choisir un chauffeur --</option>
                        @php $nomsGrille = []; @endphp
                        @foreach($grilleJour as $i => $g)
                            @if(!$g['deja_saisi'] && ($g['chauffeur_actif'] ?? true) && !($g['vehicule_pris_par'] ?? null) && $g['chauffeur'])
                            <option value="{{ $g['chauffeur'] }}" data-vid="{{ $g['vehicule']->id }}" data-vimmat="{{ $g['vehicule']->immatriculation }}" data-objectif="{{ $g['objectif'] }}" data-repos="{{ $g['est_repos'] ? '1' : '0' }}">{{ $g['chauffeur'] }}</option>
                            @php $nomsGrille[] = $g['chauffeur']; @endphp
                            @endif
                        @endforeach
                        @php
                            // Chauffeurs du formulaire Chauffeurs absents de la grille (nom complet = prénom + nom), hors ceux déjà saisis ce jour
                            $dejaSaisis = collect($grilleJour)->where('deja_saisi', true)->pluck('chauffeur')->all();
                            $autresChauff = $chauffeurs->where('statut', 'actif')
                                ->reject(fn($c) => in_array($c->nomComplet(), $nomsGrille, true) || in_array($c->nomComplet(), $dejaSaisis, true));
                        @endphp
                        @if($autresChauff->isNotEmpty())
                        <option disabled>── Autres chauffeurs ──</option>
                        @foreach($autresChauff as $ac)
                            @php $acVeh = $ac->vehicules->first(); @endphp
                            <option value="{{ $ac->nomComplet() }}" data-vid="{{ $acVeh->id ?? '' }}" data-vimmat="{{ $acVeh->immatriculation ?? '' }}" data-objectif="0" data-repos="0">{{ $ac->nomComplet() }}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--bleu)">Vehicule</label>
                    <input type="text" id="sr-vehic-label" readonly style="margin-top:4px;background:var(--fond);font-weight:600;color:var(--bleu);border:1px dashed var(--bord)" placeholder="Automatique">
                </div>
            </div>

            {{-- Indicateur objectif --}}
            <div id="sr-objectif-info" style="display:none;margin:10px 0;padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600;border:1px solid #bfdbfe;background:#eff6ff;color:#1e40af;align-items:center;gap:6px">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#2563eb" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                <span id="sr-obj-label"></span>
            </div>

            {{-- Section 2 : Montants --}}
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-top:12px">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--bleu)">Recette (FCFA) <span class="req">*</span></label>
                    <input type="number" name="recette" id="sr-recette" min="0" required placeholder="Montant" style="margin-top:4px;font-size:15px;font-weight:700">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--bleu)">Pointage (FCFA) <span class="req">*</span></label>
                    <input type="number" name="pointage" id="sr-pointage" min="0" value="0" required style="margin-top:4px">
                </div>
                <div id="sr-mode-wrap">
                    <label style="font-size:12px;font-weight:600;color:var(--bleu)">Mode remise <span class="req">*</span></label>
                    <select name="mode_remise" id="sr-mode" style="margin-top:4px">
                        <option value="">--</option><option value="especes">Especes</option><option value="mobile_money">Mobile Money</option><option value="cheque">Cheque</option><option value="virement">Virement</option><option value="autre">Autre</option>
                    </select>
                </div>
            </div>

            {{-- Ecart temps reel --}}
            <div id="sr-ecart-info" style="display:none;margin:8px 0;padding:8px 14px;border-radius:8px;font-size:12.5px;font-weight:600"></div>

            {{-- Section 3 : Controle --}}
            <div style="margin-top:10px">
                <label style="font-size:12px;font-weight:600;color:var(--bleu)">Controle par</label>
                <select name="controle_par" id="sr-ctrl" style="margin-top:4px;max-width:260px">
                    <option value="">-- Choisir --</option>
                    @foreach($collaborateurs as $u)<option value="{{ $u->name }}"{{ $u->id === auth()->id() ? ' selected' : '' }}>{{ $u->name }}</option> @endforeach
                </select>
            </div>

            {{-- Zone motif (conditionnelle) --}}
            <div id="sr-motif-zone" style="display:none;margin-top:12px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:12px 14px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#d97706" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <label id="sr-motif-label" style="color:#92400e;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px">JUSTIFICATION OBLIGATOIRE</label>
                </div>
                <select name="motif_absence" id="sr-motif" style="margin-bottom:4px">
                    <option value="">-- Choisir un motif --</option>
                    @foreach($motifsAbsence as $ma)<option value="{{ $ma->code }}">{{ $ma->libelle }}</option> @endforeach
                </select>
                <div id="sr-perspective-zone" style="margin-top:8px;display:none">
                    <label style="color:#92400e;font-weight:600;font-size:11px">Perspective / Strategie</label>
                    <textarea name="perspective" id="sr-perspective" rows="2" style="margin-top:4px" placeholder="Comment rattraper ce deficit ?"></textarea>
                </div>
            </div>

            {{-- Zone validation gestionnaire --}}
            <div id="sr-validation-zone" style="display:none;margin-top:10px;background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:12px 14px">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:6px">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <label style="color:#991b1b;font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.3px">VALIDATION DU GESTIONNAIRE</label>
                </div>
                <div style="font-size:11.5px;color:#92400e;margin-bottom:8px">La justification est-elle valable ?</div>
                <div style="display:flex;gap:12px;align-items:center">
                    <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;padding:5px 10px;border-radius:6px;border:1px solid #fca5a5;background:#fff">
                        <input type="radio" name="justification_valable" value="0" checked> Non valable
                    </label>
                    <label style="display:flex;align-items:center;gap:5px;font-size:12px;cursor:pointer;padding:5px 10px;border-radius:6px;border:1px solid #86efac;background:#fff">
                        <input type="radio" name="justification_valable" value="1"> Valable
                    </label>
                </div>
            </div>

            {{-- Observations --}}
            <div style="margin-top:10px">
                <label style="font-size:12px;font-weight:600;color:var(--bleu)">Observations</label>
                <textarea name="observations" id="sr-obs" rows="2" style="margin-top:4px" placeholder="Notes complementaires (optionnel)"></textarea>
            </div>

            {{-- Bouton soumettre --}}
            <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end;align-items:center">
                <span style="font-size:11px;color:var(--mute)">{{ $chauffeursPending->count() }} chauffeur(s) restant(s)</span>
                <button class="btn" id="sr-btn-submit" style="padding:8px 20px;font-size:13px;border-radius:8px">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
    @endif
    @endif

</div>

{{-- ==================== TAB MAINTENANCE ==================== --}}
<div class="tab-panel" id="tab-maint">
    <div class="panel-head">
        <h3>{!! $icCalCheck !!} Maintenance @if($kpi['echeances_retard']>0)<span class="pill p-ko">{{ $kpi['echeances_retard'] }} en retard</span>@endif</h3>
        @if(!$estDG)<button class="btn sm" onclick="document.getElementById('dlg-maint').showModal()">{!! $icPlus !!} Échéance</button>@endif
    </div>
    @php $aSuivre = collect($echeancier)->where('alerte', '!=', 'ok'); @endphp
    @if($aSuivre->isNotEmpty())
    <div class="maint-alertes">
        @foreach($aSuivre as $ech)
        @php
            $cls = ['en_retard' => 'ko', 'non_planifiee' => 'ko', 'aucune' => 'gris', 'proche' => 'warn'][$ech['alerte']];
            $txt = match($ech['alerte']) {
                'en_retard' => 'en retard de ' . abs($ech['jours']) . ' j (prévue le ' . $ech['prochaine']->format('d/m') . ')',
                'proche' => 'prévue le ' . $ech['prochaine']->format('d/m') . ' (dans ' . $ech['jours'] . ' j)',
                'non_planifiee' => 'aucune prochaine échéance',
                default => 'aucune maintenance planifiée',
            };
        @endphp
        <div class="maint-alerte {{ $cls }}">
            <span><strong>{{ $ech['vehicule']->immatriculation }}</strong>@if($ech['type_label']) · {{ $ech['type_label'] }}@endif : {{ $txt }}@if($ech['derniere']) <em>— dernière réalisation le {{ $ech['derniere']->format('d/m/Y') }}</em>@endif</span>
            @if(!$estDG)
                @if($ech['prochaine_id'])<button class="btn sm" onclick="realiserMaint({{ $ech['prochaine_id'] }})" style="background:var(--ok)">Réaliser</button>
                @else<button class="btn sm sec" onclick="planifierMaint({{ $ech['vehicule']->id }}, '{{ $ech['type'] }}', '{{ $ech['derniere']?->format('Y-m-d') }}')">Planifier</button>@endif
            @endif
        </div>
        @endforeach
    </div>
    @endif

    @php $coutTotalMaint = $maintenances->sum('cout'); @endphp
    @if($coutTotalMaint > 0)
    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 14px;margin-bottom:10px;display:flex;align-items:center;gap:8px;font-size:12.5px">
        <span style="font-weight:700;color:#92400e">Coût total maintenance : {{ number_format($coutTotalMaint, 0, ',', ' ') }} F</span>
        <span style="color:var(--gris)">({{ $maintenances->whereNotNull('cout')->where('cout', '>', 0)->count() }} intervention(s))</span>
    </div>
    @endif
    @include('parc._filtre', ['cible' => 'tbl-maint', 'champs' => [
        'vehicule' => true,
        'type' => ['Tous les types', $typesMaintenance->pluck('libelle', 'code')->all()],
        'statut' => ['Toutes les alertes', ['en_retard' => 'En retard', 'proche' => 'Proche', 'ok' => 'À jour', 'fait' => 'Réalisée']],
        'recherche' => 'Observations, prestataire…']])
    <div class="panneau" style="overflow-x:auto">
    <table id="tbl-maint">
    <thead><tr><th>Véhicule</th><th>Type</th><th>Statut</th><th>Dernière réalisation</th><th>Échéance prévue</th><th>Jours</th><th>Alerte</th><th>Coût</th><th>Observations</th><th></th></tr></thead>
    <tbody>
    @forelse($maintenances as $m)
    @php $al = $m->alerte(); $jr = $m->joursRestants(); $estReal = $m->estRealisee(); @endphp
    <tr data-ligne data-date="{{ ($estReal ? $m->date_realisation : $m->prochaine_echeance)?->format('Y-m-d') }}" data-vehicule="{{ $m->vehicule_id }}" data-type="{{ $m->type_echeance }}" data-statut="{{ $estReal ? 'fait' : $al }}" data-montant="{{ (int) $m->cout }}">
        <td><strong>{{ $m->vehicule->immatriculation ?? '?' }}</strong></td>
        <td>{{ $m->typeLabel() }}</td>
        <td><span class="pill {{ $estReal ? 'p-ok' : 'p-warn' }}">{{ $m->statutLabel() }}</span></td>
        {{-- Réalisée : date effective ; planifiée : date de la réalisation précédente --}}
        <td>{{ ($estReal ? ($m->date_realisation ?? $m->derniere_realisation) : $m->derniere_realisation)?->format('d/m/Y') ?? '—' }}@if($estReal)<span style="display:block;font-size:10.5px;color:var(--ok)">réalisée</span>@endif</td>
        <td>{{ $m->prochaine_echeance ? $m->prochaine_echeance->format('d/m/Y') : '—' }}</td>
        <td style="font-weight:600;color:{{ $estReal ? 'var(--ok)' : ($al === 'en_retard' ? 'var(--ko)' : ($al === 'proche' ? 'var(--warn)' : 'var(--ok)')) }}">{{ $estReal ? '—' : ($jr !== null ? $jr.'j' : '—') }}</td>
        <td>@if($estReal)<span class="pill p-ok">Fait</span>@else<span class="pill {{ $al === 'en_retard' ? 'p-ko' : ($al === 'proche' ? 'p-warn' : 'p-ok') }}">{{ $al === 'en_retard' ? 'En retard' : ($al === 'proche' ? 'Proche' : 'OK') }}</span>@endif</td>
        <td style="font-weight:600;color:var(--ko)">{{ $m->cout ? number_format($m->cout, 0, ',', ' ').' F' : '—' }}</td>
        <td style="max-width:150px">{{ Str::limit($m->observations, 40) }}</td>
        <td>
            <div class="act-row">
                @if($m->observations || $m->prestataire || $m->lignes->count())
                <button class="act-btn info" title="Détails / facture" onclick="toggleDetail('maint-{{ $m->id }}')">{!! $icEye !!}</button>
                @endif
                @if(!$estReal && !$estDG)
                <button class="act-btn" title="Réaliser" onclick="realiserMaint({{ $m->id }})" style="color:var(--ok)">{!! $icWrench !!}</button>
                @endif
                @if(!$estDG)
                <button class="act-btn" title="Modifier" onclick="editMaint({{ $m->id }})">{!! $icEdit !!}</button>
                @if(auth()->user()->estAdmin())
                <form method="POST" action="{{ route('parc.maintenances.destroy', $m) }}" onsubmit="return demanderMotifSuppression(this,'cette maintenance')">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
                @endif
            </div>
        </td>
    </tr>
    @if($m->observations || $m->prestataire || $m->lignes->count())
    <tr class="detail-row" id="maint-{{ $m->id }}" style="display:none"><td colspan="10"><div class="detail-content">
        @if($m->observations)<strong>Observations :</strong> {{ $m->observations }}<br>@endif
        @if($m->prestataire)<strong>Prestataire :</strong> {{ $m->prestataire }}<br>@endif
        @if($m->lignes->count())
        <div style="margin-top:8px">
            <strong>Facture de maintenance :</strong>
            <table style="width:100%;max-width:520px;border-collapse:collapse;font-size:12px;margin-top:4px">
                <thead><tr style="background:var(--fond)"><th style="text-align:left;padding:3px 6px">Désignation</th><th style="text-align:center;padding:3px 4px">Qté</th><th style="text-align:right;padding:3px 4px">P.U.</th><th style="text-align:right;padding:3px 4px">Montant</th></tr></thead>
                <tbody>
                @foreach($m->lignes as $li)
                <tr style="border-bottom:1px solid var(--bord)"><td style="padding:3px 6px">{{ $li->designation }}</td><td style="text-align:center;padding:3px 4px">{{ $li->quantite }}</td><td style="text-align:right;padding:3px 4px">{{ number_format($li->prix_unitaire, 0, ',', ' ') }}</td><td style="text-align:right;padding:3px 4px">{{ number_format($li->montant, 0, ',', ' ') }}</td></tr>
                @endforeach
                </tbody>
                <tfoot><tr style="border-top:2px solid var(--accent)"><td colspan="3" style="text-align:right;padding:3px 6px;font-weight:700">Total</td><td style="text-align:right;padding:3px 4px;font-weight:700">{{ number_format($m->lignes->sum('montant'), 0, ',', ' ') }} F</td></tr></tfoot>
            </table>
        </div>
        @elseif($m->cout)<strong>Coût :</strong> {{ number_format($m->cout, 0, ',', ' ') }} F (saisi sans facture détaillée)@endif
    </div></td></tr>
    @endif
    @empty
    <tr><td colspan="9" class="empty-msg">Aucune échéance enregistrée.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB PÉNALITÉS ==================== --}}
<div class="tab-panel" id="tab-penal">
    @include('parc._reports', ['onglet' => 'penal'])
    <div class="panel-head">
        <h3>{!! $icAlert !!} Pénalités @if($kpi['penalites_non_payees']>0)<span class="pill p-ko">{{ $kpi['penalites_non_payees'] }} impayées</span>@endif</h3>
        @if(!$estDG)<button class="btn sm" onclick="document.getElementById('dlg-penal').showModal()">{!! $icPlus !!} Pénalité</button>@endif
    </div>
    @include('parc._filtre', ['cible' => 'tbl-penal', 'champs' => [
        'vehicule' => true, 'chauffeur' => true,
        'type' => ['Tous les types', $typesPenalite->pluck('libelle', 'code')->all()],
        'statut' => ['Tous les statuts', ['impayee' => 'Non soldées', 'non_payee' => 'Non payée', 'en_cours' => 'En cours', 'payee' => 'Payée', 'contestee' => 'Contestée']],
        'recherche' => 'Lieu, description…']])
    <div class="panneau" style="overflow-x:auto">
    <table id="tbl-penal">
    <thead><tr><th>Véhicule</th><th>Date</th><th>Type</th><th>Chauffeur</th><th>Lieu</th><th>Montant</th><th>Payé</th><th>Solde</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($penalites as $pen)
    @php $soldePen = $pen->soldeRestant(); @endphp
    <tr data-ligne data-date="{{ $pen->date_infraction->format('Y-m-d') }}" data-vehicule="{{ $pen->vehicule_id }}" data-chauffeur="{{ $pen->chauffeur }}" data-type="{{ $pen->type }}" data-statut="{{ $pen->statut }}{{ $pen->statut !== 'payee' ? '|impayee' : '' }}" data-montant="{{ (int) $pen->montant }}">
        <td><strong>{{ $pen->vehicule->immatriculation ?? '?' }}</strong></td>
        <td>{{ $pen->date_infraction->format('d/m/Y') }}</td>
        <td>{{ $pen->typeLabel() }}</td>
        <td>{{ $pen->chauffeur ?? '—' }}</td>
        <td>{{ Str::limit($pen->lieu, 30) ?? '—' }}</td>
        <td style="font-weight:600">{{ number_format($pen->montant, 0, ',', ' ') }}</td>
        <td style="color:var(--ok)">{{ number_format($pen->montant_paye ?? 0, 0, ',', ' ') }}</td>
        <td style="font-weight:700;color:{{ $soldePen > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ number_format($soldePen, 0, ',', ' ') }} F</td>
        <td><span class="pill {{ $pen->statut === 'payee' ? 'p-ok' : ($pen->statut === 'en_cours' ? 'p-warn' : ($pen->statut === 'contestee' ? 'p-warn' : 'p-ko')) }}">{{ $pen->statutLabel() }}</span></td>
        <td>
            <div class="act-row">
                @if($pen->statut !== 'payee' && !$estDG)
                <button class="act-btn" title="Enregistrer un paiement" onclick="ouvrirPaiementPenal({{ $pen->id }}, '{{ addslashes($pen->chauffeur ?? $pen->vehicule?->immatriculation ?? '-') }}', {{ $soldePen }})" style="color:var(--ok)">{!! $ic('<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>',14) !!}</button>
                @endif
                @if($pen->description || $pen->observations)
                <button class="act-btn info" title="Détails" onclick="toggleDetail('penal-{{ $pen->id }}')">{!! $icEye !!}</button>
                @endif
                <button class="act-btn" title="Fiche" onclick="ficheVehicule({{ $pen->vehicule_id }})" style="color:var(--accent)"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg></button>
                @if(!$estDG)
                <button class="act-btn" title="Modifier" onclick="editPenal({{ $pen->id }})">{!! $icEdit !!}</button>
                @if(auth()->user()->estAdmin())
                <form method="POST" action="{{ route('parc.penalites.destroy', $pen) }}" onsubmit="return demanderMotifSuppression(this,'cette pénalité')">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
                @endif
            </div>
        </td>
    </tr>
    {{-- Historique paiements --}}
    @if($pen->paiements->count())
    <tr><td colspan="11" style="padding:0">
        <div style="background:#f0fdf4;padding:6px 16px;font-size:11.5px">
            <strong style="color:var(--ok)">Paiements :</strong>
            @foreach($pen->paiements as $pp)
            <span style="margin-left:8px">{{ $pp->date_paiement->format('d/m') }} — {{ number_format($pp->montant, 0, ',', ' ') }} F ({{ $pp->mode_paiement ? ucfirst(str_replace('_',' ',$pp->mode_paiement)) : '—' }})@if($pp->source_paiement && $pp->source_paiement !== 'chauffeur') <span class="pill {{ $pp->source_paiement === 'pointage' ? 'p-cours' : 'p-gris' }}" style="font-size:9px">{{ ucfirst($pp->source_paiement) }}</span> @endif{{ $pp->recu_par ? ' par '.$pp->recu_par : '' }}</span>{{ !$loop->last ? ' |' : '' }}
            @endforeach
        </div>
    </td></tr>
    @endif
    @if($pen->description || $pen->observations)
    <tr class="detail-row" id="penal-{{ $pen->id }}" style="display:none"><td colspan="11"><div class="detail-content">
        @if($pen->description)<strong>Description :</strong> {{ $pen->description }}<br> @endif
        @if($pen->observations)<strong>Observations :</strong> {{ $pen->observations }} @endif
    </div></td></tr>
    @endif
    @empty
    <tr><td colspan="11" class="empty-msg">Aucune pénalité enregistrée.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB RECOUVREMENTS ==================== --}}
<div class="tab-panel" id="tab-recouv">
    @include('parc._reports', ['onglet' => 'recouv'])
    <div class="panel-head">
        <h3><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h2"/></svg> Recouvrements en cours</h3>
    </div>
    <div class="panneau">

    @php $recActifs = ($recouvrements ?? collect())->where('statut', 'actif'); @endphp

    @if($recActifs->count() > 0)
    {{-- KPIs recouvrements --}}
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
        <div class="kpi"><div class="lbl">Créances actives</div><div class="val" style="color:var(--ko)">{{ $recActifs->count() }}</div></div>
        <div class="kpi"><div class="lbl">Total à recouvrer</div><div class="val" style="color:var(--ko)">{{ number_format($recActifs->sum('solde'), 0, ',', ' ') }} F</div></div>
        <div class="kpi"><div class="lbl">Déjà recouvré</div><div class="val" style="color:var(--ok)">{{ number_format($recActifs->sum('montant_recouvre'), 0, ',', ' ') }} F</div></div>
    </div>

    @include('parc._filtre', ['cible' => 'tbl-recouv', 'champs' => [
        'vehicule' => true, 'chauffeur' => true,
        'type' => ['Tous les types', ['recette' => 'Recette', 'penalite' => 'Pénalité']],
        'recherche' => 'Rechercher…']])
    <div style="overflow-x:auto">
    <table id="tbl-recouv">
    <thead><tr><th>Date</th><th>Type</th><th>Chauffeur</th><th>Véhicule</th><th>Objectif</th><th>Versé</th><th>Déficit</th><th>Recouvré</th><th>Solde</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @foreach($recActifs as $rc)
    <tr data-ligne data-date="{{ $rc->date_recette->format('Y-m-d') }}" data-vehicule="{{ $rc->vehicule_id }}" data-chauffeur="{{ $rc->chauffeur_nom }}" data-type="{{ $rc->type_recouvrement ?? 'recette' }}" data-montant="{{ (int) $rc->solde }}">
        <td>{{ $rc->date_recette->format('d/m/Y') }}</td>
        <td><span class="pill {{ ($rc->type_recouvrement ?? 'recette') === 'penalite' ? 'p-ko' : 'p-cours' }}" style="font-size:10px">{{ ($rc->type_recouvrement ?? 'recette') === 'penalite' ? 'Pénalité' : 'Recette' }}</span></td>
        <td><strong>{{ $rc->chauffeur_nom ?? '—' }}</strong></td>
        <td>{{ $rc->vehicule?->immatriculation ?? '—' }}</td>
        <td>{{ number_format($rc->objectif, 0, ',', ' ') }}</td>
        <td>{{ number_format($rc->montant_verse, 0, ',', ' ') }}</td>
        <td style="color:var(--ko);font-weight:600">{{ number_format($rc->deficit, 0, ',', ' ') }}</td>
        <td style="color:var(--ok)">{{ number_format($rc->montant_recouvre, 0, ',', ' ') }}</td>
        <td style="font-weight:700;color:{{ $rc->solde > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ number_format($rc->solde, 0, ',', ' ') }} F</td>
        <td><span class="pill p-ko">Actif</span></td>
        <td>
            <div class="act-row">
                <button type="button" class="act-btn" title="Enregistrer un versement" onclick="ouvrirVersement({{ $rc->id }}, '{{ $rc->chauffeur_nom }}', {{ $rc->solde }})">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                @if($rc->montant_recouvre == 0 && auth()->user()->estAdmin())
                <form method="POST" action="{{ route('parc.recouvrements.annuler', $rc) }}" onsubmit="return demanderMotifAnnulation(this)">@csrf @method('PATCH')
                    <input type="hidden" name="motif_annulation" class="motif-annul-input">
                    <button class="act-btn del" title="Annuler">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    {{-- Versements existants --}}
    @if($rc->versements->count())
    <tr><td colspan="11" style="padding:0">
        <div style="background:#f0fdf4;padding:6px 16px;font-size:11.5px">
            <strong style="color:var(--ok)">Versements :</strong>
            @foreach($rc->versements as $vs)
            <span style="margin-left:8px">{{ $vs->date_versement->format('d/m') }} — {{ number_format($vs->montant, 0, ',', ' ') }} F ({{ $vs->mode_paiement ? ucfirst(str_replace('_',' ',$vs->mode_paiement)) : '—' }})@if(($vs->source_paiement ?? 'chauffeur') === 'entreprise') <span class="pill p-cours" style="font-size:9px">Entreprise</span>@endif</span>{{ !$loop->last ? ' |' : '' }}
            @endforeach
        </div>
    </td></tr>
    @endif
    @endforeach
    </tbody>
    </table>
    </div>
    @else
    <div class="empty-msg" style="text-align:center;padding:30px 0">Aucun recouvrement actif.</div>
    @endif

    {{-- Recouvrements soldés --}}
    @php $recSoldes = ($recouvrements ?? collect())->whereIn('statut', ['solde', 'annule']); @endphp
    @if($recSoldes->count() > 0)
    <details style="margin-top:16px">
        <summary style="cursor:pointer;font-size:13px;font-weight:700;color:var(--bleu);padding:6px 0">Historique soldés / annulés ({{ $recSoldes->count() }})</summary>
        <div style="margin-top:8px">
        @include('parc._filtre', ['cible' => 'tbl-recouv-soldes', 'champs' => [
            'chauffeur' => true,
            'statut' => ['Tous', ['solde' => 'Soldé', 'annule' => 'Annulé']]]])
        </div>
        <div style="overflow-x:auto">
        <table id="tbl-recouv-soldes">
        <thead><tr><th>Date</th><th>Chauffeur</th><th>Déficit</th><th>Recouvré</th><th>Statut</th>@if(auth()->user()->estAdmin())<th></th> @endif</tr></thead>
        <tbody>
        @foreach($recSoldes as $rs)
        <tr style="opacity:.7" data-ligne data-date="{{ $rs->date_recette->format('Y-m-d') }}" data-chauffeur="{{ $rs->chauffeur_nom }}" data-statut="{{ $rs->statut }}" data-montant="{{ (int) $rs->montant_recouvre }}">
            <td>{{ $rs->date_recette->format('d/m/Y') }}</td>
            <td>{{ $rs->chauffeur_nom ?? '—' }}</td>
            <td>{{ number_format($rs->deficit, 0, ',', ' ') }}</td>
            <td>{{ number_format($rs->montant_recouvre, 0, ',', ' ') }}</td>
            <td><span class="pill {{ $rs->statut === 'solde' ? 'p-ok' : 'p-gris' }}">{{ $rs->statut === 'solde' ? 'Soldé' : 'Annulé' }}</span></td>
            @if(auth()->user()->estAdmin())
            <td>
                <form method="POST" action="{{ route('parc.recouvrements.destroy', $rs) }}" onsubmit="return demanderMotifSuppression(this)">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="act-btn del" title="Supprimer">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                    </button>
                </form>
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>
        </table>
        </div>
    </details>
    @endif
    </div>

    {{-- Dialog versement --}}
    <dialog id="dlg-versement" style="width:90vw;max-width:440px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 id="dlg-vers-titre" style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Versement</h3>
            <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
        </div>
        <form method="POST" id="form-versement">
            @csrf
            <div style="display:grid;gap:12px">
                <div>
                    <label>Montant (F)</label>
                    <input type="number" name="montant" id="vs-montant" required min="1" placeholder="Montant du versement">
                    <div id="vs-solde-info" style="font-size:11px;color:var(--gris);margin-top:2px"></div>
                </div>
                <div>
                    <label>Date versement</label>
                    <input type="date" name="date_versement" id="vs-date" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-row" style="grid-template-columns:1fr 1fr">
                    <div>
                        <label>Payeur <span class="req">*</span></label>
                        <select name="source_paiement" id="vs-source" required>
                            <option value="">— Payeur —</option>
                            <option value="chauffeur">Chauffeur</option>
                            <option value="entreprise">Entreprise</option>
                        </select>
                    </div>
                    <div>
                        <label>Mode de paiement <span class="req">*</span></label>
                        <select name="mode_paiement" required>
                            <option value="">— Mode —</option>
                            <option value="especes">Espèces</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="cheque">Chèque</option>
                            <option value="virement">Virement</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                </div>
                <div id="vs-entreprise-info" style="display:none;background:#dbeafe;border:1px solid #93c5fd;border-radius:8px;padding:8px 12px;font-size:11.5px;color:var(--accent)">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Ce versement sera automatiquement enregistré en dépense entreprise.
                </div>
                <div>
                    <label>Observations</label>
                    <textarea name="observations" rows="2" placeholder="Notes..."></textarea>
                </div>
                <button type="submit" class="btn">Enregistrer le versement</button>
            </div>
        </form>
    </dialog>

    </dialog>
</div>

{{-- ==================== TAB CHAUFFEURS ==================== --}}
<div class="tab-panel" id="tab-chauffeurs">
    <div class="panel-head">
        <h3>{!! $icUser !!} Chauffeurs <span class="pill p-cours">{{ $chauffeurs->count() }}</span></h3>
        @if(!$estDG)<button class="btn sm" onclick="document.getElementById('dlg-chauffeur').showModal()">{!! $icPlus !!} Chauffeur</button>@endif
    </div>
    <div class="filtre-bar" id="filtre-chauffeurs">
        <select id="fc-statut" onchange="filtrerChauffeurs()">
            <option value="">Tous les statuts</option>
            <option value="actif">Actif</option>
            <option value="inactif">Inactif</option>
        </select>
        <select id="fc-vehicule" onchange="filtrerChauffeurs()">
            <option value="">Tous les véhicules</option>
            <option value="affecte">Affecté</option>
            <option value="non_affecte">Non affecté</option>
        </select>
        <input type="text" id="fc-search" placeholder="Rechercher un chauffeur..." onkeyup="filtrerChauffeurs()" style="min-width:180px">
    </div>
    <div class="panneau" style="overflow-x:auto">
    <table id="tbl-chauffeurs">
    <thead><tr><th>Nom complet</th><th>N° Permis</th><th>Téléphone</th><th>Véhicule(s)</th><th>Rôle</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($chauffeurs as $ch)
    @php
        $affectations = $ch->vehicules->where('pivot.actif', true);
    @endphp
    <tr data-statut="{{ $ch->statut }}" data-affecte="{{ $affectations->count() > 0 ? '1' : '0' }}" data-nom="{{ strtolower($ch->nomComplet()) }}">
        <td><strong>{{ $ch->nomComplet() }}</strong></td>
        <td>{{ $ch->numero_permis ?? '—' }}</td>
        <td>{{ $ch->telephone ?? '—' }}</td>
        <td>@if($affectations->isEmpty()) — @else @foreach($affectations as $aff)<span style="display:inline-flex;align-items:center;gap:3px;margin-right:6px">{{ $aff->immatriculation }}@if(!auth()->user()->aRole('direction'))<form method="POST" action="{{ route('parc.chauffeurs.detacher', [$aff, $ch]) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'ce détachement')">@csrf @method('DELETE')<input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="act-btn del" title="Détacher" style="padding:1px 3px"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></form>@endif</span>@endforeach @endif</td>
        <td>{{ $affectations->map(fn($v) => ucfirst($v->pivot->role))->join(', ') ?: '—' }}</td>
        <td><span class="pill {{ $ch->statut === 'actif' ? 'p-ok' : 'p-gris' }}">{{ ucfirst($ch->statut) }}</span></td>
        <td>
            <div class="act-row">
                @if($ch->observations)
                <button class="act-btn info" title="Détails" onclick="toggleDetail('ch-{{ $ch->id }}')">{!! $icEye !!}</button>
                @endif
                <button class="act-btn" title="Résumé" onclick="ficheChauffeur({{ $ch->id }})" style="color:var(--accent)"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/></svg></button>
                <button class="act-btn" title="Affecter" onclick="affecterChauffeur({{ $ch->id }})" style="color:var(--accent)">{!! $icCar !!}</button>
                <button class="act-btn" title="Modifier" onclick="editChauffeur({{ $ch->id }})">{!! $icEdit !!}</button>
                @if(auth()->user()->estAdmin())
                <form method="POST" action="{{ route('parc.chauffeurs.destroy', $ch) }}" onsubmit="return demanderMotifSuppression(this,'ce chauffeur')">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @if($ch->observations)
    <tr class="detail-row" id="ch-{{ $ch->id }}" style="display:none"><td colspan="7"><div class="detail-content"><strong>Observations :</strong> {{ $ch->observations }}</div></td></tr>
    @endif
    @empty
    <tr><td colspan="7" class="empty-msg">Aucun chauffeur enregistré.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== DIALOGS ==================== --}}

{{-- Dialog Historique recettes --}}
<dialog id="dlg-histo-recettes" style="width:95vw;max-width:1000px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
        <h3 style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Historique des recettes</h3>
        <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
    </div>
    @include('parc._filtre', ['cible' => 'tbl-histo-recettes', 'champs' => [
        'vehicule' => true, 'chauffeur' => true,
        'statut' => ['Tous les résultats', ['atteint' => 'Objectif atteint', 'non_atteint' => 'Objectif non atteint', 'absence' => "Avec motif d'absence"]],
        'recherche' => 'Rechercher…']])
    <div style="overflow-x:auto;max-height:60vh;overflow-y:auto">
    <table id="tbl-histo-recettes">
    <thead><tr><th>Date</th><th>Chauffeur</th><th>Véhicule</th><th>Recette</th><th>Objectif</th><th>Écart</th><th>Mode</th><th>Motif</th><th></th></tr></thead>
    <tbody id="histo-tbody">
    @forelse($recettes as $rec)
    <tr data-ligne data-date="{{ $rec->date->format('Y-m-d') }}" data-vehicule="{{ $rec->vehicule_id }}" data-chauffeur="{{ $rec->chauffeur }}" data-statut="{{ $rec->ecart >= 0 ? 'atteint' : 'non_atteint' }}{{ $rec->motif_absence ? '|absence' : '' }}" data-montant="{{ (int) $rec->recette }}">
        <td>{{ $rec->date->format('d/m/Y') }}</td>
        <td><strong>{{ $rec->chauffeur ?? '—' }}</strong></td>
        <td>{{ $rec->vehicule->immatriculation ?? '?' }}</td>
        <td style="font-weight:600">{{ number_format($rec->recette, 0, ',', ' ') }}</td>
        <td>{{ number_format($rec->objectif, 0, ',', ' ') }}</td>
        <td style="color:{{ $rec->ecart >= 0 ? 'var(--ok)' : 'var(--ko)' }};font-weight:600">{{ ($rec->ecart >= 0 ? '+' : '') . number_format($rec->ecart, 0, ',', ' ') }}</td>
        <td>{{ $rec->mode_remise ? ucfirst(str_replace('_', ' ', $rec->mode_remise)) : '—' }}</td>
        <td>@if($rec->motif_absence)<span class="pill p-warn">{{ \App\Models\TypeParc::libelleMotif($rec->motif_absence) }}</span>@else — @endif</td>
        <td>
            <div class="act-row">
                @if(auth()->user()->estAdmin())
                <button type="button" class="act-btn edit-icon-btn" title="Modifier cette recette" onclick="editRecette({{ $rec->id }})">{!! $icEdit !!}</button>
                @endif
                @if(auth()->user()->estAdmin())
                <form method="POST" action="{{ route('parc.recettes.destroy', $rec) }}" onsubmit="return demanderMotifSuppression(this,'cette recette')">@csrf @method('DELETE')
                    <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="9" class="empty-msg">Aucune recette enregistrée.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</dialog>

{{-- Dialog paiement pénalité --}}
<dialog id="dlg-paie-penal" style="width:90vw;max-width:440px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
        <h3 id="dlg-pp-titre" style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Paiement pénalité</h3>
        <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
    </div>
    <form method="POST" id="form-paie-penal">
        @csrf
        <div style="display:grid;gap:12px">
            <div>
                <label>Montant (F)</label>
                <input type="number" name="montant" id="pp-montant" required min="1" placeholder="Montant du paiement">
                <div id="pp-solde-info" style="font-size:11px;color:var(--gris);margin-top:2px"></div>
            </div>
            <div>
                <label>Date paiement</label>
                <input type="date" name="date_paiement" id="pp-date" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="form-row" style="grid-template-columns:1fr 1fr">
                <div>
                    <label>Source du paiement <span class="req">*</span></label>
                    <select name="source_paiement" id="pp-source" required onchange="toggleFinancementPenal(this.value)">
                        <option value="">— Source —</option>
                        <option value="pointage">Pointage</option>
                        <option value="chauffeur">Chauffeur</option>
                        <option value="entreprise">Entreprise</option>
                    </select>
                </div>
                <div>
                    <label>Mode de paiement <span class="req">*</span></label>
                    <select name="mode_paiement" required>
                        <option value="">— Mode —</option>
                        <option value="especes">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="cheque">Chèque</option>
                        <option value="virement">Virement</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
            </div>
            <div id="pp-financement-wrap" style="display:none">
                <label>Financement <span class="req">*</span></label>
                <select name="source_financement" onchange="toggleEmpruntFields(this)">
                    <option value="entreprise">Fonds propres</option>
                    <option value="emprunt">Emprunt</option>
                </select>
            </div>
            <div class="emprunt-fields" style="display:none">
                <label>Prêteur</label>
                <input type="text" name="preteur" maxlength="150" placeholder="Nom du prêteur">
            </div>
            <div>
                <label>Observations</label>
                <textarea name="observations" rows="2" placeholder="Notes..."></textarea>
            </div>
            <button type="submit" class="btn">Enregistrer le paiement</button>
        </div>
    </form>
</dialog>

{{-- Dialog Véhicule --}}
<dialog id="dlg-vehicule">
<h3 id="dlg-vehicule-titre">{!! $icCar !!} Nouveau véhicule</h3>
<form id="form-vehicule" method="POST" action="{{ route('parc.vehicules.store') }}">
    @csrf <div id="vehicule-method"></div>
    <div class="dlg-section">Identification</div>
    <div class="form-row3">
        <div><label>Immatriculation <span class="req">*</span></label><input name="immatriculation" id="v-immat" required></div>
        <div><label>Catégorie <span class="req">*</span></label>
            <select name="categorie" id="v-cat"><option value="taxi">Taxi</option><option value="vtc">VTC</option></select>
        </div>
        <div><label>Statut <span class="req">*</span></label>
            <select name="statut" id="v-statut"><option value="en_service">En service</option><option value="immobilise">Immobilisé</option><option value="reforme">Réformé</option></select>
        </div>
    </div>
    <div class="form-row">
        <div><label>Marque</label><input name="marque" id="v-marque" list="dl-marques" autocomplete="off"></div>
        <div><label>Modèle</label><input name="modele" id="v-modele"></div>
    </div>
    <div class="dlg-section">Chauffeurs affectés</div>
    <input type="hidden" name="chauffeur_affecte" id="v-chauff" value="">
    <div id="v-chauffeurs-list"></div>
    <button type="button" class="btn sm sec" onclick="ajouterChauffeurRow()" style="margin-top:6px">{!! $icPlus !!} Ajouter un chauffeur</button>

    <div class="dlg-section">Exploitation</div>
    <div class="form-row3">
        <div><label>Kilométrage</label><input type="number" name="kilometrage" id="v-km" min="0"></div>
        <div><label>Mise en service</label><input type="date" name="date_mise_en_service" id="v-dms"></div>
        <div><label>Objectif recette (FCFA)</label><input type="number" name="objectif_recette" id="v-obj" min="0" placeholder="Vide = paramètre global"></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="v-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Resume Recette --}}
<dialog id="dlg-resume-recette" style="max-width:420px;width:calc(100% - 32px);border-radius:14px;border:none;box-shadow:0 12px 40px rgba(0,0,0,.2);padding:0;overflow:hidden">
<div id="res-header" style="background:linear-gradient(135deg,var(--bleu),var(--accent));padding:16px 20px;color:#fff;text-align:center">
    <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="#fff" stroke-width="2" style="margin-bottom:4px"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <div style="font-size:16px;font-weight:700">Recette enregistree</div>
    <div id="res-header-sub" style="font-size:12px;opacity:.8;margin-top:2px"></div>
</div>
<div style="padding:18px 20px">
    {{-- Bloc montant principal --}}
    <div style="text-align:center;margin-bottom:14px">
        <div style="font-size:11px;color:var(--mute);text-transform:uppercase;letter-spacing:.5px">Montant verse</div>
        <div id="res-montant" style="font-size:28px;font-weight:800;color:var(--bleu);line-height:1.2"></div>
    </div>
    {{-- Grille details --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:var(--bord);border-radius:10px;overflow:hidden;border:1px solid var(--bord);margin-bottom:12px">
        <div style="background:var(--carte);padding:10px 12px;text-align:center">
            <div style="font-size:10px;color:var(--mute);text-transform:uppercase">Chauffeur</div>
            <div id="res-chauffeur" style="font-size:13px;font-weight:600;margin-top:2px"></div>
        </div>
        <div style="background:var(--carte);padding:10px 12px;text-align:center">
            <div style="font-size:10px;color:var(--mute);text-transform:uppercase">Vehicule</div>
            <div id="res-vehicule" style="font-size:13px;font-weight:600;margin-top:2px"></div>
        </div>
        <div style="background:var(--carte);padding:10px 12px;text-align:center">
            <div style="font-size:10px;color:var(--mute);text-transform:uppercase">Objectif</div>
            <div id="res-objectif" style="font-size:13px;font-weight:600;margin-top:2px"></div>
        </div>
        <div style="background:var(--carte);padding:10px 12px;text-align:center">
            <div style="font-size:10px;color:var(--mute);text-transform:uppercase">Ecart</div>
            <div id="res-ecart" style="font-size:13px;font-weight:700;margin-top:2px"></div>
        </div>
    </div>
    <div style="text-align:center;margin-bottom:6px">
        <span id="res-date" style="font-size:11px;color:var(--mute)"></span>
    </div>
    <div style="text-align:center;margin-bottom:10px" id="res-statut"></div>

    {{-- Actions motif --}}
    <div id="res-motif-actions" style="display:none;padding-top:12px;border-top:1px solid var(--bord)">
        <div style="font-size:11px;color:#92400e;font-weight:700;margin-bottom:10px;text-align:center;text-transform:uppercase;letter-spacing:.3px">Validation du motif de deficit</div>
        <div style="display:flex;gap:10px">
            <form method="POST" id="res-form-valider" style="flex:1">
                @csrf <input type="hidden" name="_method" value="PATCH">
                <button class="btn sm" style="width:100%;background:#16a34a;border-color:#16a34a;border-radius:8px;padding:8px 0;font-size:12px">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#fff" stroke-width="2.5" style="vertical-align:-2px;margin-right:3px"><polyline points="20 6 9 17 4 12"/></svg>
                    Valider motif
                </button>
            </form>
            <form method="POST" id="res-form-invalider" style="flex:1">
                @csrf <input type="hidden" name="_method" value="PATCH">
                <button class="btn sm" style="width:100%;background:#dc2626;border-color:#dc2626;border-radius:8px;padding:8px 0;font-size:12px">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="#fff" stroke-width="2.5" style="vertical-align:-2px;margin-right:3px"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    Invalider
                </button>
            </form>
        </div>
    </div>
</div>
<div style="padding:0 20px 16px;text-align:center">
    <button type="button" class="btn sec sm" onclick="this.closest('dialog').close()" style="border-radius:8px;padding:7px 24px">Fermer</button>
</div>
</dialog>

{{-- Dialog Déclaration panne --}}
<dialog id="dlg-panne">
<h3 id="dlg-panne-titre">{!! $icWrench !!} Déclarer une panne</h3>
<form id="form-panne" method="POST" action="{{ route('parc.pannes.store') }}">
    @csrf <div id="panne-method"></div>
    <input type="hidden" name="statut" id="p-statut" value="declaree">
    <input type="hidden" name="diagnostic" id="p-diag" value="">
    <input type="hidden" name="decision" id="p-deci" value="">
    <input type="hidden" name="cout_reel" id="p-cout" value="">
    <input type="hidden" name="date_debut_intervention" id="p-ddi" value="">
    <input type="hidden" name="date_cloture" id="p-dcl" value="">
    <div class="form-row3">
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="p-vehic" required>
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }} ({{ $v->categorieLabel() }})</option>@endforeach
            </select>
        </div>
        <div><label>Date déclaration</label><input type="date" name="date_declaration" id="p-date" value="{{ date('Y-m-d') }}"></div>
        <div><label>Urgence <span class="req">*</span></label>
            <select name="urgence" id="p-urg" required>
                <option value="faible">Faible</option><option value="moyenne" selected>Moyenne</option><option value="haute">Haute</option><option value="critique">Critique</option>
            </select>
        </div>
    </div>
    <div><label>Description <span class="req">*</span></label><textarea name="description" id="p-desc" required rows="3" placeholder="Décrivez le problème constaté"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Déclarer</button>
    </div>
</form>
</dialog>

{{-- Dialog Statuer panne --}}
<dialog id="dlg-statuer">
<h3 id="dlg-statuer-titre">{!! $icWrench !!} Statuer sur la panne</h3>
<form id="form-statuer" method="POST" action="">
    @csrf <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="vehicule_id" id="s-vehic">
    <input type="hidden" name="date_declaration" id="s-date">
    <input type="hidden" name="description" id="s-desc">
    <input type="hidden" name="urgence" id="s-urg">
    <div class="dlg-section" style="margin-top:0">Informations de la panne</div>
    <div style="background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12.5px;line-height:1.6">
        <strong id="s-info-vehic"></strong> — <span id="s-info-date"></span><br>
        <span id="s-info-desc" style="color:var(--gris)"></span>
    </div>
    <div class="dlg-section">Diagnostic & Intervention</div>
    <div><label>Diagnostic</label><textarea name="diagnostic" id="s-diag" rows="2" placeholder="Résultat du diagnostic"></textarea></div>
    <div class="form-row3">
        <div><label>Décision</label>
            <select name="decision" id="s-deci">
                <option value="">— Choisir —</option><option value="Réparer">Réparer</option><option value="Remplacer pièce">Remplacer pièce</option><option value="Sous-traitance">Sous-traitance</option><option value="Réforme">Réforme</option><option value="Autre">Autre</option>
            </select>
        </div>
        <div><label>Coût réel (FCFA)</label><input type="number" name="cout_reel" id="s-cout" min="0" readonly style="background:#f0f0f0;cursor:not-allowed" title="Calculé automatiquement depuis les lignes d'intervention"></div>
        <div><label>Statut <span class="req">*</span></label>
            <select name="statut" id="s-statut" required>
                <option value="declaree">Déclarée</option><option value="en_diagnostic">En diagnostic</option><option value="en_intervention">En intervention</option><option value="cloturee">Clôturée</option>
            </select>
        </div>
    </div>
    @if(!$estDG)
    <div class="dlg-section">Lignes d'intervention (facture)</div>
    <div id="s-lignes-wrap" style="margin-bottom:14px">
        <table id="s-lignes-table" style="width:100%;border-collapse:collapse;font-size:12.5px">
            <thead><tr style="background:var(--fond);border-bottom:2px solid var(--bord)">
                <th style="text-align:left;padding:6px 8px">Designation</th>
                <th style="text-align:center;padding:6px 4px;width:60px">Qte</th>
                <th style="text-align:right;padding:6px 4px;width:90px">P.U. (F)</th>
                <th style="text-align:right;padding:6px 4px;width:90px">Montant</th>
                <th style="width:60px;padding:6px 4px"></th>
            </tr></thead>
            <tbody id="s-lignes-body"></tbody>
            <tfoot><tr style="border-top:2px solid var(--accent)">
                <td colspan="3" style="text-align:right;padding:6px 8px;font-weight:700">Total</td>
                <td style="text-align:right;padding:6px 4px;font-weight:700" id="s-lignes-total">0</td>
                <td></td>
            </tr></tfoot>
        </table>
        <div style="margin-top:8px;display:flex;gap:6px;align-items:flex-end" id="s-ligne-add-row">
            <input type="text" id="sl-desig" placeholder="Designation" style="flex:2;padding:6px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px">
            <input type="number" id="sl-qte" placeholder="Qte" value="1" min="1" style="width:55px;padding:6px 4px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px;text-align:center">
            <input type="number" id="sl-pu" placeholder="P.U." min="0" style="width:80px;padding:6px 4px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px;text-align:right">
            <button type="button" class="btn sm" onclick="ajouterLigneIntervention()" style="white-space:nowrap">+ Ajouter</button>
        </div>
    </div>
    @endif

    <div class="form-row">
        <div><label>Debut intervention</label><input type="date" name="date_debut_intervention" id="s-ddi"></div>
        <div><label>Date cloture</label><input type="date" name="date_cloture" id="s-dcl"></div>
    </div>
    <div id="s-financement-info" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:7px 12px;font-size:12px;color:#1e40af;margin-bottom:8px">Cette intervention est financée par un emprunt : son montant suit le total de la facture.</div>
    <div class="form-row" id="s-financement">
        <div><label>Financement</label>
            <select name="source_financement" onchange="toggleEmpruntFields(this)">
                <option value="entreprise">Fonds propres</option>
                <option value="emprunt">Emprunt</option>
            </select>
        </div>
        <div class="emprunt-fields" style="display:none"><label>Prêteur <span class="req">*</span></label><input type="text" name="preteur" maxlength="150" placeholder="Nom du prêteur" data-requis-emprunt></div>
    </div>
    <div class="form-row emprunt-fields" style="display:none">
        <div><label>Remboursement prévu le <span class="req">*</span></label><input type="date" name="date_echeance_emprunt" data-requis-emprunt></div>
        <div><label>Modalités de remboursement</label><input type="text" name="modalites_emprunt" maxlength="500" placeholder="Ex. : 3 versements mensuels"></div>
    </div>
    <div class="emprunt-fields" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:7px 12px;font-size:12px;color:#1e40af;margin-top:6px">
        L'emprunt est créé pour le montant de la facture et devra être remboursé (onglet Emprunts).
    </div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Recette --}}
<dialog id="dlg-recette">
<h3 id="dlg-recette-titre">{!! $icWallet !!} Nouvelle recette</h3>
<form id="form-recette" method="POST" action="{{ route('parc.recettes.store') }}" onsubmit="return validerFormRecette()">
    @csrf <div id="recette-method"></div>
    <div class="dlg-section">Versement</div>
    <div class="form-row3">
        <div><label>Date <span class="req">*</span></label><input type="date" name="date" id="r-date" value="{{ $dateJour }}" required max="{{ date('Y-m-d') }}" onchange="onRDateChange()">
            <div id="r-date-retro" style="display:none;margin-top:4px;padding:4px 8px;border-radius:6px;font-size:11px;font-weight:600;background:#fef3c7;color:#92400e;border:1px solid #fde68a">Saisie retroactive</div>
        </div>
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="r-vehic" required onchange="onVehiculeChange()">
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }} ({{ $v->categorieLabel() }})</option> @endforeach
            </select>
        </div>
        <div><label>Chauffeur</label>
            <select name="chauffeur" id="r-chauff">
                <option value="">-- Chauffeur --</option>
            </select>
        </div>
    </div>
    {{-- Indicateur dynamique objectif --}}
    <div id="r-objectif-info" style="display:none;margin:6px 0 10px;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid #bfdbfe;background:#eff6ff;color:#1e40af">
        <span id="r-obj-label"></span>
    </div>
    <div class="form-row3">
        <div><label>Recette (FCFA) <span class="req">*</span></label><input type="number" name="recette" id="r-montant" min="0" required placeholder="Montant versé"></div>
        <div><label>Pointage (FCFA) <span class="req">*</span></label><input type="number" name="pointage" id="r-pointage" min="0" value="0" required></div>
        <div id="r-mode-wrap"><label>Mode de remise <span class="req">*</span></label>
            <select name="mode_remise" id="r-mode">
                <option value="">--</option><option value="especes">Especes</option><option value="mobile_money">Mobile Money</option><option value="cheque">Cheque</option><option value="virement">Virement</option><option value="autre">Autre</option>
            </select>
        </div>
    </div>
    {{-- Indicateur écart temps réel --}}
    <div id="r-ecart-info" style="display:none;margin:4px 0 8px;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600"></div>
    <div class="form-row">
        <div><label>Controle par</label>
            <select name="controle_par" id="r-ctrl">
                <option value="">-- Choisir --</option>
                @foreach($collaborateurs as $u)<option value="{{ $u->name }}">{{ $u->name }}</option> @endforeach
            </select>
        </div>
    </div>
    <div id="motif-zone" style="display:none;margin-top:10px;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 12px">
        <label id="motif-label" style="color:#92400e;font-weight:700;font-size:11px">JUSTIFICATION OBLIGATOIRE</label>
        <select name="motif_absence" id="r-motif" style="margin-top:4px">
            <option value="">-- Choisir un motif --</option>
            @foreach($motifsAbsence as $ma)<option value="{{ $ma->code }}">{{ $ma->libelle }}</option> @endforeach
        </select>
        <div id="perspective-zone" style="margin-top:6px;display:none">
            <label style="color:#92400e;font-weight:700;font-size:11px">Perspective / Strategie</label>
            <textarea name="perspective" id="r-perspective" rows="2" style="margin-top:4px"></textarea>
        </div>
    </div>
    <div id="validation-zone" style="display:none;margin-top:10px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:10px 12px">
        <label style="color:#991b1b;font-weight:700;font-size:11px">VALIDATION DU GESTIONNAIRE</label>
        <div style="font-size:11.5px;color:#92400e;margin:4px 0 8px">Deficit detecte -- la justification du chauffeur est-elle valable ?</div>
        <div style="display:flex;gap:16px;align-items:center">
            <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer">
                <input type="radio" name="justification_valable" value="0" checked> Non valable (recouvrement)
            </label>
            <label style="display:flex;align-items:center;gap:4px;font-size:12px;cursor:pointer">
                <input type="radio" name="justification_valable" value="1"> Valable (pas de recouvrement)
            </label>
        </div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="r-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn" id="r-btn-submit">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Maintenance --}}
<dialog id="dlg-maint">
<h3 id="dlg-maint-titre">{!! $icCalCheck !!} Nouvelle échéance</h3>
<form id="form-maint" method="POST" action="{{ route('parc.maintenances.store') }}">
    @csrf <div id="maint-method"></div>
    <div class="form-row">
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="m-vehic" required>
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }}</option>@endforeach
            </select>
        </div>
        <div><label>Type <span class="req">*</span></label>
            <select name="type_echeance" id="m-type" required onchange="calcProchaineEcheanceCreation()">
                @foreach($typesMaintenance as $tm)<option value="{{ $tm->code }}">{{ $tm->libelle }}{{ $tm->intervalle_jours ? ' ('.$tm->intervalle_jours.'j)' : '' }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="form-row">
        <div><label>Dernière réalisation</label><input type="date" name="derniere_realisation" id="m-dr" onchange="calcProchaineEcheanceCreation()"></div>
        <div><label>Prochaine échéance</label><input type="date" name="prochaine_echeance" id="m-pe" onchange="this._manuallySet=true" onfocus="this._manuallySet=true"><small style="color:var(--gris);font-size:11px">Calculee automatiquement selon le type</small></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="m-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Réaliser maintenance --}}
<dialog id="dlg-realiser-maint">
<h3>{!! $icCalCheck !!} Réaliser la maintenance</h3>
<form id="form-realiser-maint" method="POST" action="" onsubmit="return validerRealisationMaint()">
    @csrf <input type="hidden" name="_method" value="PUT">
    <div class="dlg-section" style="margin-top:0">Échéance planifiée</div>
    <div style="background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12.5px;line-height:1.6">
        <strong id="rm-info-vehic"></strong> — <span id="rm-info-type"></span><br>
        <span id="rm-info-echeance" style="color:var(--gris)"></span>
    </div>
    <div class="dlg-section">Réalisation</div>
    <div class="form-row3">
        <div><label>Date réalisation <span class="req">*</span></label><input type="date" name="date_realisation" id="rm-date" value="{{ date('Y-m-d') }}" required onchange="calcProchaineEcheanceRealisation(document.getElementById('rm-type-code').value, this.value)"></div>
        <input type="hidden" id="rm-type-code" value="">
        <div><label>Prestataire <span class="req">*</span></label><input type="text" name="prestataire" id="rm-prest" maxlength="150" placeholder="Nom du prestataire" required></div>
        <div><label>Coût (F)</label><input type="number" id="rm-cout" readonly style="background:#f0f0f0;cursor:not-allowed" title="Calculé depuis la facture"></div>
    </div>
    <div class="dlg-section">Facture de maintenance <span class="req">*</span></div>
    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
        <thead><tr style="background:var(--fond);border-bottom:2px solid var(--bord)">
            <th style="text-align:left;padding:6px 8px">Désignation</th>
            <th style="text-align:center;padding:6px 4px;width:60px">Qté</th>
            <th style="text-align:right;padding:6px 4px;width:90px">P.U. (F)</th>
            <th style="text-align:right;padding:6px 4px;width:90px">Montant</th>
            <th style="width:36px"></th>
        </tr></thead>
        <tbody id="rm-lignes-body"></tbody>
        <tfoot><tr style="border-top:2px solid var(--accent)">
            <td colspan="3" style="text-align:right;padding:6px 8px;font-weight:700">Total</td>
            <td style="text-align:right;padding:6px 4px;font-weight:700" id="rm-lignes-total">0</td><td></td>
        </tr></tfoot>
    </table>
    <div style="margin:8px 0 12px;display:flex;gap:6px;align-items:flex-end">
        <input type="text" id="rml-desig" placeholder="Désignation (pièce, main d'œuvre…)" style="flex:2;padding:6px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px">
        <input type="number" id="rml-qte" placeholder="Qté" value="1" min="1" style="width:55px;padding:6px 4px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px;text-align:center">
        <input type="number" id="rml-pu" placeholder="P.U." min="1" style="width:80px;padding:6px 4px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px;text-align:right">
        <button type="button" class="btn sm" onclick="ajouterLigneMaint()" style="white-space:nowrap">+ Ajouter</button>
    </div>
    <div><label>Observations</label><textarea name="observations" id="rm-obs" rows="2" placeholder="Détails de l'intervention"></textarea></div>
    <div class="dlg-section">Prochaine échéance</div>
    <div class="form-row">
        <div><label>Prochaine échéance</label><input type="date" name="prochaine_echeance_suivante" id="rm-pe-suivante"><small style="color:var(--gris);font-size:11px">Calculee automatiquement. Modifiable manuellement.</small></div>
    </div>
    <div class="dlg-section">Financement</div>
    <div class="form-row">
        <div><label>Financement <span class="req">*</span></label>
            <select name="source_financement" onchange="toggleEmpruntFields(this)">
                <option value="entreprise">Fonds propres</option>
                <option value="emprunt">Emprunt</option>
            </select>
        </div>
        <div class="emprunt-fields" style="display:none"><label>Prêteur <span class="req">*</span></label><input type="text" name="preteur" maxlength="150" placeholder="Nom du prêteur" data-requis-emprunt></div>
    </div>
    <div class="form-row emprunt-fields" style="display:none">
        <div><label>Remboursement prévu le <span class="req">*</span></label><input type="date" name="date_echeance_emprunt" data-requis-emprunt></div>
        <div><label>Modalités de remboursement</label><input type="text" name="modalites_emprunt" maxlength="500" placeholder="Ex. : 3 versements mensuels"></div>
    </div>
    <div class="emprunt-fields" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:7px 12px;font-size:12px;color:#1e40af;margin-top:6px">
        L'emprunt est créé pour le montant de la facture et devra être remboursé (onglet Emprunts).
    </div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Valider la réalisation</button>
    </div>
</form>
</dialog>

{{-- Dialog Déclaration pénalité --}}
<dialog id="dlg-penal">
<h3 id="dlg-penal-titre">{!! $icAlert !!} Déclarer une pénalité</h3>
<form id="form-penal" method="POST" action="{{ route('parc.penalites.store') }}">
    @csrf <div id="penal-method"></div>
    <input type="hidden" name="statut" id="pe-statut" value="non_payee">
    <input type="hidden" name="date_paiement" id="pe-dpay" value="">
    <div class="form-row3">
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="pe-vehic" required onchange="majChauffeurs(this.value,'pe-chauff')">
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }} ({{ $v->categorieLabel() }})</option>@endforeach
            </select>
        </div>
        <div><label>Date <span class="req">*</span></label><input type="date" name="date_infraction" id="pe-date" value="{{ date('Y-m-d') }}" required></div>
        <div><label>Type <span class="req">*</span></label>
            <select name="type" id="pe-type" required>
                @foreach($typesPenalite as $tp)<option value="{{ $tp->code }}">{{ $tp->libelle }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="form-row3">
        <div><label>Chauffeur <span class="req">*</span></label>
            <select name="chauffeur" id="pe-chauff" required>
                <option value="">— Chauffeur —</option>
            </select>
        </div>
        <div><label>Lieu <span class="req">*</span></label>
            <select name="lieu" id="pe-lieu" required>
                <option value="">— Lieu —</option>
                @foreach($lieuxSugg as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
            </select>
        </div>
        <div><label>Montant (FCFA) <span class="req">*</span></label><input type="number" name="montant" id="pe-mont" min="0" required></div>
    </div>
    <div><label>Description <span class="req">*</span></label><textarea name="description" id="pe-desc" rows="2" placeholder="Décrivez l'infraction" required></textarea></div>
    <div><label>Observations</label><textarea name="observations" id="pe-obs" rows="2" placeholder="Observations complémentaires"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Déclarer</button>
    </div>
</form>
</dialog>

{{-- Dialog Statuer pénalité --}}
<dialog id="dlg-statuer-penal">
<h3>{!! $icAlert !!} Statuer sur la pénalité</h3>
<form id="form-statuer-penal" method="POST" action="">
    @csrf <input type="hidden" name="_method" value="PUT">
    <input type="hidden" name="vehicule_id" id="sp-vehic">
    <input type="hidden" name="date_infraction" id="sp-date">
    <input type="hidden" name="type" id="sp-type">
    <input type="hidden" name="chauffeur" id="sp-chauff">
    <input type="hidden" name="lieu" id="sp-lieu">
    <input type="hidden" name="description" id="sp-desc">
    <input type="hidden" name="montant" id="sp-mont">
    <div style="background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12.5px;line-height:1.6">
        <strong id="sp-info-vehic"></strong> — <span id="sp-info-date"></span> — <span id="sp-info-type"></span><br>
        <span style="color:var(--gris)" id="sp-info-desc"></span><br>
        Montant : <strong id="sp-info-mont"></strong> FCFA
    </div>
    <div class="dlg-section" style="margin-top:0">Règlement</div>
    <div class="form-row3">
        <div><label>Statut <span class="req">*</span></label>
            <select name="statut" id="sp-statut" required>
                <option value="non_payee">Non payée</option><option value="en_cours">En cours</option><option value="payee">Payée</option><option value="contestee">Contestée</option>
            </select>
        </div>
        <div><label>Date paiement</label><input type="date" name="date_paiement" id="sp-dpay"></div>
        <div></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="sp-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Chauffeur --}}
<dialog id="dlg-chauffeur">
<h3 id="dlg-chauffeur-titre">{!! $icUser !!} Nouveau chauffeur</h3>
<form id="form-chauffeur" method="POST" action="{{ route('parc.chauffeurs.store') }}">
    @csrf <div id="chauffeur-method"></div>
    <div class="form-row">
        <div><label>Prénom <span class="req">*</span></label><input name="prenom" id="ch-prenom" required></div>
        <div><label>Nom <span class="req">*</span></label><input name="nom" id="ch-nom" required></div>
    </div>
    <div class="form-row">
        <div><label>N° Permis</label><input name="numero_permis" id="ch-permis"></div>
        <div><label>Téléphone</label><input name="telephone" id="ch-tel"></div>
    </div>
    <div class="form-row">
        <div><label>Statut</label>
            <select name="statut" id="ch-statut"><option value="actif">Actif</option><option value="inactif">Inactif</option></select>
        </div>
        <div></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="ch-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Affectation --}}
<dialog id="dlg-affectation">
<h3>{!! $icCar !!} Affecter un véhicule</h3>
<div id="af-nom-chauffeur" style="background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:8px 14px;margin-bottom:14px;font-size:13px;font-weight:600;color:var(--bleu)"></div>
<form id="form-affectation" method="POST" action="{{ route('parc.chauffeurs.affecter') }}">
    @csrf
    <input type="hidden" name="chauffeur_id" id="af-chauffeur">
    <div class="form-row">
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="af-vehic" required>
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }} ({{ $v->categorieLabel() }})</option>@endforeach
            </select>
        </div>
        <div><label>Rôle <span class="req">*</span></label>
            <select name="role" id="af-role" required>
                <option value="principal">Principal</option><option value="alternant">Alternant</option>
            </select>
        </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Affecter</button>
    </div>
</form>
</dialog>

{{-- Dialog Fiche véhicule --}}
<dialog id="dlg-fiche" style="width:820px;max-width:96vw;max-height:90vh;overflow-y:auto">
<h3>{!! $icCar !!} Fiche véhicule</h3>
<div id="fiche-contenu" style="font-size:13px;line-height:1.7">
    <p style="text-align:center;color:var(--gris)">Chargement...</p>
</div>
<div style="display:flex;justify-content:flex-end;margin-top:16px">
    <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Fermer</button>
</div>
</dialog>

{{-- Dialog Fiche chauffeur --}}
<dialog id="dlg-fiche-ch" style="width:780px;max-width:96vw;max-height:90vh;overflow-y:auto">
<h3>{!! $icUser !!} Fiche chauffeur</h3>
<div id="fiche-ch-contenu" style="font-size:13px;line-height:1.7">
    <p style="text-align:center;color:var(--gris)">Chargement...</p>
</div>
<div style="display:flex;justify-content:flex-end;margin-top:16px">
    <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Fermer</button>
</div>
</dialog>

</div>{{-- /dg-mode --}}

{{-- ==================== TAB DÉPENSES ==================== --}}
<div class="tab-panel" id="tab-depenses">
<div class="panel-head">
    <h3><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 7h20M5 7v12a2 2 0 002 2h10a2 2 0 002-2V7M9 11v4M15 11v4M10 3h4l1 4H9l1-4z"/></svg> Autres dépenses <span class="pill p-cours">{{ ($depenses ?? collect())->count() }}</span></h3>
    @if(!auth()->user()->aRole('direction'))
    <button class="btn sm" onclick="document.getElementById('dlg-depense').showModal()">{!! $icPlus !!} Dépense</button>
    @endif
</div>

@php
    $totalDepenses = ($depenses ?? collect())->sum('montant');
    $typesLabels = ($typesDepense ?? collect())->pluck('libelle', 'code')->toArray();
@endphp

<div style="margin-bottom:12px;font-size:13px;color:var(--gris)">
    Total dépenses : <strong style="color:var(--ko)">{{ number_format($totalDepenses, 0, ',', ' ') }} F</strong>
</div>

@include('parc._filtre', ['cible' => 'tbl-depenses', 'champs' => [
    'type' => ['Tous les types', ($typesDepense ?? collect())->pluck('libelle', 'code')->all()],
    'recherche' => 'Objet, saisi par…']])
<div class="panneau" style="overflow-x:auto">
<table class="tableau" id="tbl-depenses" style="width:100%">
<thead><tr>
    <th style="width:100px">Date</th>
    <th>Type</th>
    <th style="text-align:right;width:120px">Montant</th>
    <th>Objet de la dépense</th>
    <th style="width:80px">Par</th>
    @if(!auth()->user()->aRole('direction'))<th style="width:120px">Actions</th>@endif
</tr></thead>
<tbody>
@forelse($depenses ?? [] as $dep)
<tr id="dep-row-{{ $dep->id }}" data-ligne data-date="{{ $dep->date_depense->format('Y-m-d') }}" data-type="{{ $dep->type_depense }}" data-montant="{{ (int) $dep->montant }}">
    <td>{{ $dep->date_depense->format('d/m/Y') }}</td>
    <td>{{ $typesLabels[$dep->type_depense] ?? ucfirst(str_replace('_', ' ', $dep->type_depense)) }}</td>
    <td style="text-align:right;font-weight:600;color:var(--ko)">{{ number_format($dep->montant, 0, ',', ' ') }} F</td>
    <td style="font-size:12px;color:var(--gris)">{{ $dep->commentaire ?? '—' }}</td>
    <td style="font-size:11px;color:var(--gris)">{{ $dep->cree_par }}</td>
    @if(!auth()->user()->aRole('direction'))
    <td>
        <div class="act-row">
            @if($dep->commentaire)
            <button class="act-btn info" title="Détails" onclick="toggleDetail('dep-{{ $dep->id }}')">{!! $icEye !!}</button>
            @endif
            <button class="act-btn" title="Modifier" onclick="editerDepense({{ $dep->id }})">{!! $icEdit !!}</button>
            @if(auth()->user()->estAdmin())
            <form method="POST" action="{{ route('parc.depenses.destroy', $dep) }}" onsubmit="return demanderMotifSuppression(this,'cette dépense')" style="display:inline">@csrf @method('DELETE')<input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="act-btn del" title="Supprimer"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></button></form>
            @endif
        </div>
    </td>
    @endif
</tr>
@if($dep->commentaire)
<tr class="detail-row" id="dep-{{ $dep->id }}" style="display:none"><td colspan="{{ auth()->user()->aRole('direction') ? 5 : 6 }}"><div class="detail-content"><strong>Objet de la dépense :</strong> {{ $dep->commentaire }}</div></td></tr>
@endif
@empty
<tr><td colspan="{{ auth()->user()->aRole('direction') ? 5 : 6 }}" style="text-align:center;color:var(--gris);padding:20px">Aucune dépense enregistrée</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>

{{-- ==================== TAB EMPRUNTS ==================== --}}
<div class="tab-panel" id="tab-emprunts">
    @include('parc._reports', ['onglet' => 'emprunts'])
<div class="panel-head">
    <h3><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg> Emprunts <span class="pill p-cours">{{ ($emprunts ?? collect())->count() }}</span></h3>
</div>
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:8px 14px;margin-bottom:12px;font-size:12.5px;color:#1e40af">
    Un emprunt finance toujours une intervention : il se crée depuis la panne (« Statuer ») ou la maintenance (« Réaliser ») en choisissant le financement « Emprunt », puis doit être remboursé ici.
</div>

@php
    $empruntsEnCours = ($emprunts ?? collect())->where('statut', 'en_cours');
    $totalEmprunts = $empruntsEnCours->sum('montant');
    $totalRembourse = $empruntsEnCours->sum('montant_rembourse');
    $soldeTotal = $totalEmprunts - $totalRembourse;
@endphp

<div style="display:flex;gap:20px;margin-bottom:12px;font-size:13px;flex-wrap:wrap">
    <span>Total emprunté : <strong style="color:var(--ko)">{{ number_format($totalEmprunts, 0, ',', ' ') }} F</strong></span>
    <span>Remboursé : <strong style="color:var(--ok)">{{ number_format($totalRembourse, 0, ',', ' ') }} F</strong></span>
    <span>Solde dû : <strong style="color:{{ $soldeTotal > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ number_format($soldeTotal, 0, ',', ' ') }} F</strong></span>
</div>

@include('parc._filtre', ['cible' => 'tbl-emprunts', 'champs' => [
    'vehicule' => true,
    'statut' => ['Tous les statuts', ['en_cours' => 'En cours', 'rembourse' => 'Remboursé']],
    'recherche' => 'Prêteur, motif…']])
<div class="panneau" style="overflow-x:auto">
<table class="tableau" id="tbl-emprunts" style="width:100%">
<thead><tr>
    <th style="width:90px">Date</th>
    <th>Prêteur</th>
    <th>Intervention financée</th>
    <th>Véhicule</th>
    <th style="text-align:right;width:100px">Montant</th>
    <th style="text-align:right;width:100px">Remboursé</th>
    <th style="text-align:right;width:100px">Solde</th>
    <th>À rembourser le</th>
    <th>Statut</th>
    @if(!$estDG)<th style="width:130px">Actions</th> @endif
</tr></thead>
<tbody>
@forelse($emprunts ?? [] as $emp)
@php $soldeEmp = $emp->soldeRestant(); @endphp
<tr data-ligne data-date="{{ $emp->date_emprunt->format('Y-m-d') }}" data-vehicule="{{ $emp->vehicule_id }}" data-statut="{{ $emp->statut === 'rembourse' ? 'rembourse' : 'en_cours' }}" data-montant="{{ (int) $emp->montant }}">
    <td>{{ $emp->date_emprunt->format('d/m/Y') }}</td>
    <td style="font-weight:600">{{ $emp->preteur }}</td>
    <td><span class="pill p-cours" style="font-size:11px">{{ $emp->sourceLabel() }}</span>@if(!$emp->source_id && $emp->source_type === 'autre')<span style="display:block;font-size:10.5px;color:var(--ko)">non rattaché à une intervention</span>@endif</td>
    <td style="font-size:12px">{{ $emp->vehicule?->immatriculation ?? '—' }}</td>
    <td style="text-align:right;font-weight:600;color:var(--ko)">{{ number_format($emp->montant, 0, ',', ' ') }} F</td>
    <td style="text-align:right;color:var(--ok)">{{ number_format($emp->montant_rembourse, 0, ',', ' ') }} F</td>
    <td style="text-align:right;font-weight:700;color:{{ $soldeEmp > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ number_format($soldeEmp, 0, ',', ' ') }} F</td>
    @php $empRetard = $emp->statut !== 'rembourse' && $emp->date_echeance && $emp->date_echeance->lt(today()); @endphp
    <td>{{ $emp->date_echeance?->format('d/m/Y') ?? 'Non définie' }}@if($empRetard)<span class="pill p-ko" style="display:inline-block;margin-left:4px;font-size:10px">En retard</span>@endif</td>
    <td><span class="pill {{ $emp->statut === 'rembourse' ? 'p-ok' : 'p-ko' }}">{{ $emp->statutLabel() }}</span></td>
    @if(!$estDG)
    <td>
        <div class="act-row">
            @if($emp->statut !== 'rembourse')
            <button class="act-btn" title="Rembourser" onclick="ouvrirRemboursement({{ $emp->id }}, '{{ addslashes($emp->preteur) }}', {{ $soldeEmp }})" style="color:var(--ok)">{!! $ic('<path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>',14) !!}</button>
            @endif
            @if($emp->motif)
            <button class="act-btn info" title="Détails" onclick="toggleDetail('emp-{{ $emp->id }}')">{!! $icEye !!}</button>
            @endif
            @if(auth()->user()->estAdmin())
            <form method="POST" action="{{ route('parc.emprunts.destroy', $emp) }}" onsubmit="return demanderMotifSuppression(this,'cet emprunt')" style="display:inline">@csrf @method('DELETE')<input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="act-btn del" title="Supprimer"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg></button></form>
            @endif
        </div>
    </td>
    @endif
</tr>
@if($emp->motif)
<tr class="detail-row" id="emp-{{ $emp->id }}" style="display:none"><td colspan="{{ $estDG ? 9 : 10 }}"><div class="detail-content"><strong>Motif :</strong> {{ $emp->motif }}@if($emp->modalites)<br><strong>Modalités :</strong> {{ $emp->modalites }}@endif
@if($emp->remboursements->count() > 0)
<div style="margin-top:8px"><strong>Historique remboursements :</strong>
<table style="width:100%;font-size:12px;margin-top:4px"><thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Observations</th></tr></thead><tbody>
@foreach($emp->remboursements->sortByDesc('date_remboursement') as $rb)
<tr><td>{{ $rb->date_remboursement->format('d/m/Y') }}</td><td style="font-weight:600;color:var(--ok)">{{ number_format($rb->montant, 0, ',', ' ') }} F</td><td>{{ ucfirst(str_replace('_', ' ', $rb->mode_paiement ?? '—')) }}</td><td style="color:var(--gris)">{{ $rb->observations ?? '—' }}</td></tr>
@endforeach
</tbody></table></div>
@endif
</div></td></tr>
@endif
@empty
<tr><td colspan="{{ $estDG ? 9 : 10 }}" style="text-align:center;color:var(--gris);padding:20px">Aucun emprunt enregistré</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>


{{-- ==================== TAB PRÊTS ACCORDÉS ==================== --}}
<div class="tab-panel" id="tab-prets">
    @include('parc._reports', ['onglet' => 'prets'])
<div class="panel-head">
    <h3><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg> Prêts accordés <span class="pill p-cours">{{ ($pretsAccordes ?? collect())->count() }}</span></h3>
    @if(!$estDG)
    <button class="btn sm" onclick="document.getElementById('dlg-pret').showModal()">{!! $icPlus !!} Prêt</button>
    @endif
</div>

@php
    $pretsEnCours = ($pretsAccordes ?? collect())->where('statut', 'en_cours');
    $totalPrets = $pretsEnCours->sum('montant');
    $totalRembPrets = $pretsEnCours->sum('montant_rembourse');
    $soldePrets = $totalPrets - $totalRembPrets;
@endphp

<div style="display:flex;gap:20px;margin-bottom:12px;font-size:13px;flex-wrap:wrap">
    <span>Total prêté : <strong style="color:var(--ko)">{{ number_format($totalPrets, 0, ',', ' ') }} F</strong></span>
    <span>Remboursé : <strong style="color:var(--ok)">{{ number_format($totalRembPrets, 0, ',', ' ') }} F</strong></span>
    <span>Créances : <strong style="color:{{ $soldePrets > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ number_format($soldePrets, 0, ',', ' ') }} F</strong></span>
</div>

@include('parc._filtre', ['cible' => 'tbl-prets', 'champs' => [
    'chauffeur' => true,
    'statut' => ['Tous les statuts', ['en_cours' => 'En cours', 'rembourse' => 'Remboursé', 'irrécouvrable' => 'Irrécouvrable']],
    'recherche' => 'Référence, bénéficiaire…']])
<div class="panneau" style="overflow-x:auto">
<table class="tableau" id="tbl-prets" style="width:100%">
<thead><tr>
    <th style="width:90px">Date</th>
    <th>Référence</th>
    <th>Bénéficiaire</th>
    <th style="text-align:right;width:100px">Montant</th>
    <th style="text-align:right;width:100px">Remboursé</th>
    <th style="text-align:right;width:100px">Solde</th>
    <th style="width:90px">Statut</th>
    @if(!$estDG)<th style="width:140px">Actions</th>@endif
</tr></thead>
<tbody>
@forelse($pretsAccordes ?? [] as $prt)
<tr data-ligne data-date="{{ $prt->date_pret->format('Y-m-d') }}" data-chauffeur="{{ $prt->beneficiaire }}" data-statut="{{ in_array($prt->statut, ['rembourse', 'irrécouvrable']) ? $prt->statut : 'en_cours' }}" data-montant="{{ (int) $prt->montant }}" onclick="var d=document.getElementById('prt-{{ $prt->id }}');d.style.display=d.style.display=='none'?'table-row':'none'" style="cursor:pointer">
    <td>{{ $prt->date_pret->format('d/m/Y') }}</td>
    <td style="font-weight:600;font-size:12px">{{ $prt->reference }}</td>
    <td>{{ $prt->beneficiaire }}</td>
    <td style="text-align:right;font-weight:600">{{ number_format($prt->montant, 0, ',', ' ') }} F</td>
    <td style="text-align:right;color:var(--ok)">{{ number_format($prt->montant_rembourse ?? 0, 0, ',', ' ') }} F</td>
    <td style="text-align:right;font-weight:700;color:{{ $prt->soldeRestant() > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ number_format($prt->soldeRestant(), 0, ',', ' ') }} F</td>
    <td><span class="pill {{ $prt->statut == 'rembourse' ? 'p-ok' : 'p-ko' }}">{{ $prt->statutLabel() }}</span></td>
    @if(!$estDG)
    <td onclick="event.stopPropagation()">
        <div style="display:flex;gap:4px">
            @if($prt->soldeRestant() > 0)
            <button class="btn sm" onclick="ouvrirRembPret({{ $prt->id }}, '{{ $prt->beneficiaire }}', {{ $prt->soldeRestant() }})">Rembourser</button>
            @endif
            @if($estChargeOps)
            <form method="POST" action="{{ route('parc.prets.destroy', $prt) }}" onsubmit="return demanderMotifSuppression(this, 'ce prêt')">
                @csrf @method('DELETE')
                <button class="btn sm ko">{!! $icDel !!}</button>
            </form>
            @endif
        </div>
    </td>
    @endif
</tr>
@if($prt->motif || $prt->remboursements->count() > 0)
<tr class="detail-row" id="prt-{{ $prt->id }}" style="display:none"><td colspan="{{ $estDG ? 7 : 8 }}"><div class="detail-content">
@if($prt->motif)<strong>Motif :</strong> {{ $prt->motif }}<br>@endif
@if($prt->modalites)<strong>Modalités :</strong> {{ $prt->modalites }}<br>@endif
@if($prt->date_echeance)<strong>Échéance :</strong> {{ $prt->date_echeance->format('d/m/Y') }}<br>@endif
@if($prt->remboursements->count() > 0)
<div style="margin-top:8px"><strong>Historique remboursements :</strong>
<table style="width:100%;font-size:12px;margin-top:4px"><thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Observations</th></tr></thead><tbody>
@foreach($prt->remboursements->sortByDesc('date_remboursement') as $rb)
<tr><td>{{ $rb->date_remboursement->format('d/m/Y') }}</td><td style="font-weight:600;color:var(--ok)">{{ number_format($rb->montant, 0, ',', ' ') }} F</td><td>{{ ucfirst(str_replace('_', ' ', $rb->mode_paiement ?? '—')) }}</td><td style="color:var(--gris)">{{ $rb->observations ?? '—' }}</td></tr>
@endforeach
</tbody></table></div>
@endif
</div></td></tr>
@endif
@empty
<tr><td colspan="{{ $estDG ? 7 : 8 }}" style="text-align:center;color:var(--gris);padding:20px">Aucun prêt accordé</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>

{{-- ==================== TAB AUTRES RESSOURCES ==================== --}}
<div class="tab-panel" id="tab-ressources">
<div class="panel-head">
    <h3><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg> Autres ressources <span class="pill p-cours">{{ ($autresRessources ?? collect())->count() }}</span></h3>
    @if(!$estDG)
    <button class="btn sm" onclick="document.getElementById('dlg-ressource').showModal()">{!! $icPlus !!} Ressource</button>
    @endif
</div>

@php $totalRessources = ($autresRessources ?? collect())->sum('montant'); @endphp
<div style="margin-bottom:12px;font-size:13px">
    Total : <strong style="color:var(--ok)">{{ number_format($totalRessources, 0, ',', ' ') }} F</strong>
</div>

@include('parc._filtre', ['cible' => 'tbl-ressources', 'champs' => [
    'vehicule' => true,
    'type' => ['Tous les types', ($typesRessource ?? collect())->pluck('libelle', 'code')->all()],
    'recherche' => 'Source, description…']])
<div class="panneau" style="overflow-x:auto">
<table class="tableau" id="tbl-ressources" style="width:100%">
<thead><tr>
    <th style="width:90px">Date</th>
    <th>Type</th>
    <th>Source</th>
    <th>Véhicule</th>
    <th style="text-align:right;width:100px">Montant</th>
    <th>Description</th>
    @if(!$estDG)<th style="width:80px">Actions</th>@endif
</tr></thead>
<tbody>
@forelse($autresRessources ?? [] as $res)
<tr data-ligne data-date="{{ $res->date_ressource->format('Y-m-d') }}" data-vehicule="{{ $res->vehicule_id }}" data-type="{{ $res->type_ressource }}" data-montant="{{ (int) $res->montant }}">
    <td>{{ $res->date_ressource->format('d/m/Y') }}</td>
    <td><span class="pill p-cours">{{ $res->typeLibelle() }}</span></td>
    <td>{{ $res->source ?? '—' }}</td>
    <td>{{ $res->vehicule->immatriculation ?? '—' }}</td>
    <td style="text-align:right;font-weight:600;color:var(--ok)">{{ number_format($res->montant, 0, ',', ' ') }} F</td>
    <td style="font-size:12px;color:var(--gris)">{{ $res->description ?? '—' }}</td>
    @if(!$estDG)
    <td>
        <div style="display:flex;gap:4px">
            <button class="btn sm sec" title="Modifier" onclick="ouvrirEditRessource({{ $res->id }})">{!! $icEdit !!}</button>
            @if($estChargeOps)
            <form method="POST" action="{{ route('parc.ressources.destroy', $res) }}" onsubmit="return demanderMotifSuppression(this, 'cette ressource')">
                @csrf @method('DELETE')
                <button class="btn sm ko">{!! $icDel !!}</button>
            </form>
            @endif
        </div>
    </td>
    @endif
</tr>
@empty
<tr><td colspan="{{ $estDG ? 6 : 7 }}" style="text-align:center;color:var(--gris);padding:20px">Aucune ressource enregistrée</td></tr>
@endforelse
</tbody>
</table>
</div>
</div>

{{-- Dialog Emprunt --}}
{{-- Dialog Remboursement emprunt --}}
<dialog id="dlg-remb-emprunt" style="width:90vw;max-width:440px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <h3 id="dlg-re-titre" style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Remboursement</h3>
    <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
</div>
<form method="POST" id="form-remb-emprunt">
    @csrf
    <div style="display:grid;gap:12px">
        <div>
            <label>Montant (F) <span class="req">*</span></label>
            <input type="number" name="montant" id="re-montant" required min="1" placeholder="Montant du remboursement">
            <div id="re-solde-info" style="font-size:11px;color:var(--gris);margin-top:2px"></div>
        </div>
        <div><label>Date remboursement <span class="req">*</span></label><input type="date" name="date_remboursement" id="re-date" value="{{ date('Y-m-d') }}" required></div>
        <div><label>Mode de paiement</label>
            <select name="mode_paiement">
                <option value="">—</option>
                <option value="especes">Espèces</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="cheque">Chèque</option>
                <option value="virement">Virement</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div><label>Observations</label><textarea name="observations" rows="2" placeholder="Notes..."></textarea></div>
        <button type="submit" class="btn">Enregistrer le remboursement</button>
    </div>
</form>
</dialog>

{{-- Dialog Dépense --}}
<dialog id="dlg-depense">
<h3 id="dlg-depense-titre">{!! $icDepense !!} Nouvelle dépense</h3>
<form id="form-depense" method="POST" action="{{ route('parc.depenses.store') }}">
    @csrf <div id="depense-method"></div>
    <div class="form-row3">
        <div><label>Date <span class="req">*</span></label><input type="date" name="date_depense" id="de-date" value="{{ date('Y-m-d') }}" required></div>
        <div><label>Type <span class="req">*</span></label>
            <select name="type_depense" id="de-type" required>
                <option value="">— Choisir —</option>
                @foreach($typesDepense ?? [] as $td)
                <option value="{{ $td->code }}">{{ $td->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div><label>Montant (F) <span class="req">*</span></label><input type="number" name="montant" id="de-montant" min="1" required placeholder="0"></div>
    </div>
    <div><label>Objet de la dépense</label><textarea name="commentaire" id="de-comment" rows="2" maxlength="500" placeholder="Ex. : facture SODECI de septembre, achat de fournitures de bureau…"></textarea>
        <small style="color:var(--gris);font-size:11px">Dépense de l'entreprise : elle n'est rattachée à aucun véhicule et n'entre pas dans la rentabilité par véhicule.</small></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>


{{-- Dialog Prêt accordé --}}
<dialog id="dlg-pret" style="width:90vw;max-width:480px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <h3 style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Nouveau prêt accordé</h3>
    <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
</div>
<form method="POST" action="{{ route('parc.prets.store') }}">
    @csrf
    <div style="display:grid;gap:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div><label>Montant (F) <span class="req">*</span></label><input type="number" name="montant" min="1" required placeholder="0"></div>
            <div><label>Date <span class="req">*</span></label><input type="date" name="date_pret" value="{{ date('Y-m-d') }}" required></div>
        </div>
        <div><label>Bénéficiaire <span class="req">*</span></label><input type="text" name="beneficiaire" required maxlength="150" placeholder="Nom du bénéficiaire"></div>
        <div><label>Chauffeur (si applicable)</label>
            <select name="chauffeur_id">
                <option value="">— Aucun —</option>
                @foreach($chauffeurs as $ch)<option value="{{ $ch->id }}">{{ $ch->nomComplet() }}</option>@endforeach
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div><label>Caisse source</label>
                <select name="caisse_source">
                    @foreach($caisses ?? [] as $ca)<option value="{{ $ca->code }}">{{ $ca->libelle }}</option>@endforeach
                </select>
            </div>
            <div><label>Échéance</label><input type="date" name="date_echeance"></div>
        </div>
        <div><label>Motif</label><textarea name="motif" rows="2" maxlength="500" placeholder="Raison du prêt"></textarea></div>
        <div><label>Modalités de remboursement</label><textarea name="modalites" rows="2" maxlength="500" placeholder="Retenues sur recettes, versements, etc."></textarea></div>
        <button type="submit" class="btn" style="justify-content:center">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Remboursement prêt --}}
<dialog id="dlg-remb-pret" style="width:90vw;max-width:440px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <h3 id="dlg-rp-titre" style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Remboursement prêt</h3>
    <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
</div>
<form method="POST" id="form-remb-pret">
    @csrf
    <div style="display:grid;gap:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div><label>Montant (F) <span class="req">*</span></label><input type="number" name="montant" id="rp-montant" min="1" required></div>
            <div><label>Date <span class="req">*</span></label><input type="date" name="date_remboursement" value="{{ date('Y-m-d') }}" required></div>
        </div>
        <div><label>Mode paiement</label>
            <select name="mode_paiement">
                <option value="especes">Espèces</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="cheque">Chèque</option>
                <option value="virement">Virement</option>
                <option value="autre">Autre</option>
            </select>
        </div>
        <div><label>Caisse destination</label>
            <select name="caisse_destination">
                @foreach($caisses ?? [] as $ca)<option value="{{ $ca->code }}">{{ $ca->libelle }}</option>@endforeach
            </select>
        </div>
        <div><label>Observations</label><textarea name="observations" rows="2" maxlength="500"></textarea></div>
        <button type="submit" class="btn" style="justify-content:center">Enregistrer</button>
    </div>
</form>
</dialog>

{{-- Dialog Autre ressource --}}
<dialog id="dlg-ressource" style="width:90vw;max-width:480px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
    <h3 style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Nouvelle ressource</h3>
    <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
</div>
<form method="POST" action="{{ route('parc.ressources.store') }}" id="form-ressource">
    @csrf <div id="ressource-method"></div>
    <div style="display:grid;gap:12px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div><label>Date <span class="req">*</span></label><input type="date" name="date_ressource" id="rs-date" value="{{ date('Y-m-d') }}" required></div>
            <div><label>Type <span class="req">*</span></label>
                <select name="type_ressource" id="rs-type" required>
                    <option value="">— Choisir —</option>
                    @foreach($typesRessource ?? [] as $tr)<option value="{{ $tr->code }}">{{ $tr->libelle }}</option>@endforeach
                </select>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div><label>Montant (F) <span class="req">*</span></label><input type="number" name="montant" id="rs-montant" min="1" required placeholder="0"></div>
            <div><label>Source</label><input type="text" name="source" id="rs-source" maxlength="150" placeholder="Origine"></div>
        </div>
        <div><label>Véhicule (optionnel)</label>
            <select name="vehicule_id" id="rs-vehicule">
                <option value="">— Aucun —</option>
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }}</option>@endforeach
            </select>
        </div>
        <div><label>Caisse destination</label>
            <select name="caisse_destination" id="rs-caisse">
                @foreach($caisses ?? [] as $ca)<option value="{{ $ca->code }}">{{ $ca->libelle }}</option>@endforeach
            </select>
        </div>
        <div><label>Description</label><textarea name="description" id="rs-desc" rows="2" maxlength="500"></textarea></div>
        <div style="display:flex;gap:10px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </div>
</form>
</dialog>

@endsection

@php
$_vData = [];
foreach($vehicules as $_v) {
    $_vChauffs = $_v->chauffeurs->where('pivot.actif', true)->where('statut', 'actif')->map(fn($c) => [
        'id' => $c->id, 'nom' => $c->nomComplet(), 'role' => $c->pivot->role,
    ])->values()->toArray();
    $_vData[$_v->id] = [
        'immatriculation' => $_v->immatriculation,
        'categorie' => $_v->categorie ?? 'taxi',
        'marque' => $_v->marque,
        'modele' => $_v->modele,
        'chauffeur_affecte' => $_v->chauffeur_affecte,
        'kilometrage' => $_v->kilometrage,
        'date_mise_en_service' => $_v->date_mise_en_service ? $_v->date_mise_en_service->format('Y-m-d') : '',
        'statut' => $_v->statut,
        'objectif_recette' => $_v->objectif_recette,
        'observations' => $_v->observations,
        'chauffeurs' => $_vChauffs,
    ];
}
$_pData = [];
foreach($pannes as $_p) {
    $_pData[$_p->id] = [
        'vehicule_id' => $_p->vehicule_id,
        'emprunt_id' => $_p->emprunt_id,
        'date_declaration' => $_p->date_declaration ? $_p->date_declaration->format('Y-m-d') : '',
        'description' => $_p->description,
        'urgence' => $_p->urgence,
        'diagnostic' => $_p->diagnostic,
        'decision' => $_p->decision,
        'date_debut_intervention' => $_p->date_debut_intervention ? $_p->date_debut_intervention->format('Y-m-d') : '',
        'date_cloture' => $_p->date_cloture ? $_p->date_cloture->format('Y-m-d') : '',
        'cout_reel' => $_p->cout_reel,
        'statut' => $_p->statut,
        'lignes' => $_p->lignes->map(fn($l) => [
            'id' => $l->id, 'designation' => $l->designation,
            'quantite' => $l->quantite, 'prix_unitaire' => $l->prix_unitaire,
            'montant' => $l->montant,
        ])->values()->toArray(),
    ];
}
$_rData = [];
foreach($recettes as $_r) {
    $_rData[$_r->id] = [
        'date' => $_r->date->format('Y-m-d'),
        'vehicule_id' => $_r->vehicule_id,
        'chauffeur' => $_r->chauffeur,
        'recette' => $_r->recette,
        'pointage' => $_r->pointage ?? 0,
        'mode_remise' => $_r->mode_remise,
        'controle_par' => $_r->controle_par,
        'observations' => $_r->observations,
        'motif_absence' => $_r->motif_absence,
        'perspective' => $_r->perspective ?? '',
        'valide_par' => $_r->valide_par,
        'justification_valable' => $_r->justification_valable ?? null,
    ];
}
// Fusionner les recettes de la grille du jour (garantit que les records visibles sont toujours editables)
foreach($grilleJour as $_g) {
    if (!empty($_g['recette']) && !isset($_rData[$_g['recette']->id])) {
        $_r = $_g['recette'];
        $_rData[$_r->id] = [
            'date' => $_r->date->format('Y-m-d'),
            'vehicule_id' => $_r->vehicule_id,
            'chauffeur' => $_r->chauffeur,
            'recette' => $_r->recette,
            'pointage' => $_r->pointage ?? 0,
            'mode_remise' => $_r->mode_remise,
            'controle_par' => $_r->controle_par,
            'observations' => $_r->observations,
            'motif_absence' => $_r->motif_absence,
            'perspective' => $_r->perspective ?? '',
            'valide_par' => $_r->valide_par,
            'justification_valable' => $_r->justification_valable ?? null,
        ];
    }
}
$_mData = [];
foreach($maintenances as $_m) {
    $_mData[$_m->id] = [
        'vehicule_id' => $_m->vehicule_id,
        'type_echeance' => $_m->type_echeance,
        'derniere_realisation' => $_m->derniere_realisation ? $_m->derniere_realisation->format('Y-m-d') : '',
        'prochaine_echeance' => $_m->prochaine_echeance ? $_m->prochaine_echeance->format('Y-m-d') : '',
        'observations' => $_m->observations,
        'statut' => $_m->statut ?? 'planifiee',
        'date_realisation' => $_m->date_realisation ? $_m->date_realisation->format('Y-m-d') : '',
        'cout' => $_m->cout,
        'prestataire' => $_m->prestataire,
        'vehicule_label' => $_m->vehicule->immatriculation ?? '?',
        'type_label' => $_m->typeLabel(),
    ];
}
$_peData = [];
foreach($penalites as $_pe) {
    $_peData[$_pe->id] = [
        'vehicule_id' => $_pe->vehicule_id,
        'date_infraction' => $_pe->date_infraction->format('Y-m-d'),
        'type' => $_pe->type,
        'chauffeur' => $_pe->chauffeur,
        'lieu' => $_pe->lieu,
        'description' => $_pe->description,
        'montant' => $_pe->montant,
        'statut' => $_pe->statut,
        'date_paiement' => $_pe->date_paiement ? $_pe->date_paiement->format('Y-m-d') : '',
        'observations' => $_pe->observations,
    ];
}
$_chData = [];
foreach($chauffeurs as $_ch) {
    $_chData[$_ch->id] = [
        'nom' => $_ch->nom,
        'prenom' => $_ch->prenom,
        'numero_permis' => $_ch->numero_permis,
        'telephone' => $_ch->telephone,
        'statut' => $_ch->statut,
        'observations' => $_ch->observations,
    ];
}
$_depData = [];
foreach($depenses ?? [] as $_dep) {
    $_depData[$_dep->id] = [
        'date_depense' => $_dep->date_depense->format('Y-m-d'),
        'type_depense' => $_dep->type_depense,
        'vehicule_id' => $_dep->vehicule_id,
        'montant' => $_dep->montant,
        'commentaire' => $_dep->commentaire,
    ];
}
$_vLabels = [];
foreach($vehicules as $_v) {
    $_vLabels[$_v->id] = $_v->immatriculation . ' (' . $_v->categorieLabel() . ')';
}
@endphp

@push('scripts')
<script>
function changerExercice(){
    var actif=document.querySelector('.tab-panel.actif');
    if(actif) document.getElementById('ex-tab').value=actif.id.replace('tab-','');
    document.getElementById('form-exercice').submit();
}
function changerOnglet(nom){
    document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('actif')});
    document.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('actif')});
    document.getElementById('tab-'+nom).classList.add('actif');
    var btn=document.querySelector('.tab-btn[data-onglet="'+nom+'"]');
    if(btn) btn.classList.add('actif');
}
// Auto-activation onglet via ?tab=
(function(){
    var p=new URLSearchParams(window.location.search);
    var t=p.get('tab');
    if(t && document.getElementById('tab-'+t)) changerOnglet(t);
})();

@php
$_chauffActifs = $chauffeurs->where('statut','actif')->map(fn($c) => ['id'=>$c->id,'nom'=>$c->nomComplet()])->values()->toArray();
@endphp
var chauffeursActifs = @json($_chauffActifs);
var chauffRowIdx = 0;

function ajouterChauffeurRow(chauffeurId, role){
    chauffeurId = chauffeurId || '';
    role = role || 'principal';
    var idx = chauffRowIdx++;
    var html = '<div class="form-row" style="margin-bottom:6px;align-items:flex-end" id="ch-row-'+idx+'">';
    html += '<div><label>Chauffeur</label><select name="chauffeurs_ids[]" style="width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px">';
    html += '<option value="">— Choisir —</option>';
    chauffeursActifs.forEach(function(c){ html += '<option value="'+c.id+'"'+(c.id==chauffeurId?' selected':'')+'>'+c.nom+'</option>'; });
    html += '</select></div>';
    html += '<div><label>Rôle</label><select name="chauffeurs_roles[]" style="width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px">';
    html += '<option value="principal"'+(role==='principal'?' selected':'')+'>Principal</option>';
    html += '<option value="alternant"'+(role==='alternant'?' selected':'')+'>Alternant</option>';
    html += '</select></div>';
    html += '<div><button type="button" class="btn sm ko" onclick="document.getElementById(\'ch-row-'+idx+'\').remove()" style="margin-bottom:1px">✕</button></div>';
    html += '</div>';
    document.getElementById('v-chauffeurs-list').insertAdjacentHTML('beforeend', html);
}

var vehiculesData = @json($_vData);
var pannesData = @json($_pData);

/* --- Liaison dynamique véhicule → chauffeur(s) affecté(s) --- */
function majChauffeurs(vehicId, selectId, valeur){
    var sel=document.getElementById(selectId); if(!sel)return;
    sel.innerHTML='<option value="">— Chauffeur —</option>';
    var v=vehiculesData[vehicId];
    var nomsAffectes={};
    if(v && v.chauffeurs && v.chauffeurs.length>0){
        v.chauffeurs.forEach(function(c){
            var opt=document.createElement('option');
            opt.value=c.nom;
            opt.textContent=c.nom+' ('+c.role+')';
            sel.appendChild(opt);
            nomsAffectes[c.nom]=true;
        });
        // Ajouter les autres chauffeurs actifs apres separateur
        var autres=chauffeursActifs.filter(function(c){return !nomsAffectes[c.nom];});
        if(autres.length>0){
            var sep=document.createElement('option');
            sep.disabled=true;sep.textContent='── Autres chauffeurs ──';
            sel.appendChild(sep);
            autres.forEach(function(c){
                var opt=document.createElement('option');
                opt.value=c.nom;
                opt.textContent=c.nom;
                sel.appendChild(opt);
            });
        }
    } else {
        chauffeursActifs.forEach(function(c){
            var opt=document.createElement('option');
            opt.value=c.nom;
            opt.textContent=c.nom;
            sel.appendChild(opt);
        });
    }
    if(valeur) sel.value=valeur;
}
// Init au chargement de la page : peupler le premier véhicule sélectionné
document.addEventListener('DOMContentLoaded',function(){
    var rv=document.getElementById('r-vehic');
    if(rv && rv.value) majChauffeurs(rv.value,'r-chauff');
    var pv=document.getElementById('pe-vehic');
    if(pv && pv.value) majChauffeurs(pv.value,'pe-chauff');
});
var recettesData = @json($_rData);
var maintData = @json($_mData);
var maintIntervalles = @json($typesMaintenance->pluck('intervalle_jours', 'code'));
var penalData = @json($_peData);
var chauffeurData = @json($_chData);
var depensesData = @json($_depData);
var vLabels = @json($_vLabels);

function toggleDetail(id){var r=document.getElementById(id);if(r)r.style.display=r.style.display==='none'?'':'none';}

function editVehicule(id){
    var d=vehiculesData[id]; if(!d)return;
    document.getElementById('dlg-vehicule-titre').innerHTML='{!! $icCar !!} Modifier le véhicule';
    document.getElementById('form-vehicule').action='{{ url("parc/vehicules") }}/'+id;
    document.getElementById('vehicule-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('v-immat').value=d.immatriculation;
    document.getElementById('v-cat').value=d.categorie||'taxi';
    document.getElementById('v-marque').value=d.marque||'';
    document.getElementById('v-modele').value=d.modele||'';
    document.getElementById('v-chauff').value=d.chauffeur_affecte||'';
    document.getElementById('v-km').value=d.kilometrage||'';
    document.getElementById('v-dms').value=d.date_mise_en_service||'';
    document.getElementById('v-statut').value=d.statut;
    document.getElementById('v-obj').value=d.objectif_recette||'';
    document.getElementById('v-obs').value=d.observations||'';
    // Charger chauffeurs existants
    document.getElementById('v-chauffeurs-list').innerHTML='';
    chauffRowIdx=0;
    if(d.chauffeurs && d.chauffeurs.length>0){
        d.chauffeurs.forEach(function(c){ ajouterChauffeurRow(c.id, c.role); });
    }
    document.getElementById('dlg-vehicule').showModal();
}

function editPanne(id){
    var d=pannesData[id]; if(!d)return;
    document.getElementById('dlg-panne-titre').innerHTML='{!! $icWrench !!} Modifier la déclaration';
    document.getElementById('form-panne').action='{{ url("parc/pannes") }}/'+id;
    document.getElementById('panne-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('p-vehic').value=d.vehicule_id;
    document.getElementById('p-date').value=d.date_declaration||'';
    document.getElementById('p-desc').value=d.description;
    document.getElementById('p-urg').value=d.urgence;
    document.getElementById('p-statut').value=d.statut||'declaree';
    document.getElementById('p-diag').value=d.diagnostic||'';
    document.getElementById('p-deci').value=d.decision||'';
    document.getElementById('p-cout').value=d.cout_reel||'';
    document.getElementById('p-ddi').value=d.date_debut_intervention||'';
    document.getElementById('p-dcl').value=d.date_cloture||'';
    document.getElementById('dlg-panne').showModal();
}
function statuerPanne(id){
    var d=pannesData[id]; if(!d)return;
    var vLabel=''; document.querySelectorAll('#p-vehic option').forEach(function(o){if(o.value==d.vehicule_id)vLabel=o.textContent});
    document.getElementById('form-statuer').action='{{ url("parc/pannes") }}/'+id;
    // Une panne déjà financée par emprunt ne peut pas en ouvrir un second (l'emprunt suit la facture)
    var fin=document.querySelector('#s-financement select[name="source_financement"]');
    fin.value='entreprise'; toggleEmpruntFields(fin);
    document.getElementById('s-financement').style.display=d.emprunt_id?'none':'';
    document.getElementById('s-financement-info').style.display=d.emprunt_id?'':'none';
    document.getElementById('s-vehic').value=d.vehicule_id;
    document.getElementById('s-date').value=d.date_declaration||'';
    document.getElementById('s-desc').value=d.description;
    document.getElementById('s-urg').value=d.urgence;
    document.getElementById('s-info-vehic').textContent=vLabel;
    document.getElementById('s-info-date').textContent=d.date_declaration||'';
    document.getElementById('s-info-desc').textContent=d.description;
    document.getElementById('s-diag').value=d.diagnostic||'';
    document.getElementById('s-deci').value=d.decision||'';
    document.getElementById('s-cout').value=d.cout_reel||'';
    document.getElementById('s-statut').value=d.statut;
    document.getElementById('s-ddi').value=d.date_debut_intervention||'';
    document.getElementById('s-dcl').value=d.date_cloture||'';
    // Charger les lignes d'intervention existantes
    _currentPanneId = id;
    var tbody=document.getElementById('s-lignes-body');
    if(tbody){
        tbody.innerHTML='';
        if(d.lignes && d.lignes.length>0){
            d.lignes.forEach(function(l){ renderLigneRow(l); });
        }
        recalcTotalLignes();
    }
    document.getElementById('dlg-statuer').showModal();
}

var _currentPanneId = null;

function renderLigneRow(l){
    var tbody=document.getElementById('s-lignes-body'); if(!tbody) return;
    var tr=document.createElement('tr');
    tr.setAttribute('data-ligne-id', l.id);
    tr.style.borderBottom='1px solid var(--bord)';
    tr.innerHTML='<td style="padding:5px 8px">'+escH(l.designation)+'</td>'
        +'<td style="text-align:center;padding:5px 4px">'+l.quantite+'</td>'
        +'<td style="text-align:right;padding:5px 4px">'+formatN(l.prix_unitaire)+'</td>'
        +'<td style="text-align:right;padding:5px 4px">'+formatN(l.montant)+'</td>'
        +'<td style="text-align:center;padding:5px 4px">'
        +'<button type="button" class="btn sm" onclick="editLigneIntervention('+l.id+')" title="Modifier" style="padding:2px 6px;font-size:11px">M</button> '
        +'<button type="button" class="btn sm ko" onclick="supprimerLigneIntervention('+l.id+')" title="Supprimer" style="padding:2px 6px;font-size:11px">X</button>'
        +'</td>';
    tbody.appendChild(tr);
}

function escH(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
function formatN(n){ return Number(n).toLocaleString('fr-FR'); }

function recalcTotalLignes(){
    var rows=document.querySelectorAll('#s-lignes-body tr');
    var total=0;
    rows.forEach(function(tr){
        var cells=tr.querySelectorAll('td');
        if(cells.length>=4){
            var mt=cells[3].textContent.replace(/\s/g,'').replace(/,/g,'');
            total+=parseInt(mt)||0;
        }
    });
    var el=document.getElementById('s-lignes-total');
    if(el) el.textContent=formatN(total);
    var coutEl=document.getElementById('s-cout');
    if(coutEl) coutEl.value=total;
}

function ajouterLigneIntervention(){
    var desig=document.getElementById('sl-desig').value.trim();
    var qte=parseInt(document.getElementById('sl-qte').value)||1;
    var pu=parseInt(document.getElementById('sl-pu').value)||0;
    if(!desig){ alert('Designation obligatoire'); return; }
    if(pu<=0){ alert('Prix unitaire obligatoire'); return; }
    var fd=new FormData();
    fd.append('_token','{{ csrf_token() }}');
    fd.append('designation',desig);
    fd.append('quantite',qte);
    fd.append('prix_unitaire',pu);
    fetch('{{ url("parc/pannes") }}/'+_currentPanneId+'/lignes',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.success){
            renderLigneRow(data.ligne);
            recalcTotalLignes();
            document.getElementById('sl-desig').value='';
            document.getElementById('sl-qte').value='1';
            document.getElementById('sl-pu').value='';
            // Mettre a jour pannesData
            if(pannesData[_currentPanneId]){
                if(!pannesData[_currentPanneId].lignes) pannesData[_currentPanneId].lignes=[];
                pannesData[_currentPanneId].lignes.push(data.ligne);
                pannesData[_currentPanneId].cout_reel=data.cout_reel;
            }
        } else { alert(data.message||'Erreur'); }
    }).catch(function(e){ alert('Erreur reseau'); });
}

function editLigneIntervention(ligneId){
    var tr=document.querySelector('#s-lignes-body tr[data-ligne-id="'+ligneId+'"]');
    if(!tr) return;
    var cells=tr.querySelectorAll('td');
    var oldDesig=cells[0].textContent;
    var oldQte=cells[1].textContent;
    var oldPu=cells[2].textContent.replace(/\s/g,'').replace(/,/g,'');
    cells[0].innerHTML='<input type="text" value="'+escH(oldDesig)+'" style="width:100%;padding:3px 6px;border:1px solid var(--bord);border-radius:4px;font-size:12px" id="el-d-'+ligneId+'">';
    cells[1].innerHTML='<input type="number" value="'+oldQte+'" min="1" style="width:50px;padding:3px 4px;border:1px solid var(--bord);border-radius:4px;font-size:12px;text-align:center" id="el-q-'+ligneId+'">';
    cells[2].innerHTML='<input type="number" value="'+oldPu+'" min="0" style="width:75px;padding:3px 4px;border:1px solid var(--bord);border-radius:4px;font-size:12px;text-align:right" id="el-p-'+ligneId+'">';
    cells[4].innerHTML='<button type="button" class="btn sm" onclick="saveLigneEdit('+ligneId+')" style="padding:2px 6px;font-size:11px">OK</button>';
}

function saveLigneEdit(ligneId){
    var desig=document.getElementById('el-d-'+ligneId).value.trim();
    var qte=parseInt(document.getElementById('el-q-'+ligneId).value)||1;
    var pu=parseInt(document.getElementById('el-p-'+ligneId).value)||0;
    if(!desig){ alert('Designation obligatoire'); return; }
    var fd=new FormData();
    fd.append('_token','{{ csrf_token() }}');
    fd.append('_method','PUT');
    fd.append('designation',desig);
    fd.append('quantite',qte);
    fd.append('prix_unitaire',pu);
    fetch('{{ url("parc/pannes/lignes") }}/'+ligneId,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.success){
            var tr=document.querySelector('#s-lignes-body tr[data-ligne-id="'+ligneId+'"]');
            if(tr){
                tr.innerHTML='<td style="padding:5px 8px">'+escH(data.ligne.designation)+'</td>'
                    +'<td style="text-align:center;padding:5px 4px">'+data.ligne.quantite+'</td>'
                    +'<td style="text-align:right;padding:5px 4px">'+formatN(data.ligne.prix_unitaire)+'</td>'
                    +'<td style="text-align:right;padding:5px 4px">'+formatN(data.ligne.montant)+'</td>'
                    +'<td style="text-align:center;padding:5px 4px">'
                    +'<button type="button" class="btn sm" onclick="editLigneIntervention('+ligneId+')" title="Modifier" style="padding:2px 6px;font-size:11px">M</button> '
                    +'<button type="button" class="btn sm ko" onclick="supprimerLigneIntervention('+ligneId+')" title="Supprimer" style="padding:2px 6px;font-size:11px">X</button>'
                    +'</td>';
            }
            recalcTotalLignes();
            // Mettre a jour pannesData
            if(pannesData[_currentPanneId]){
                pannesData[_currentPanneId].cout_reel=data.cout_reel;
                var lignes=pannesData[_currentPanneId].lignes||[];
                for(var i=0;i<lignes.length;i++){
                    if(lignes[i].id==ligneId){ lignes[i]=data.ligne; break; }
                }
            }
        } else { alert(data.message||'Erreur'); }
    }).catch(function(e){ alert('Erreur reseau'); });
}

function supprimerLigneIntervention(ligneId){
    if(!confirm('Supprimer cette ligne ?')) return;
    var fd=new FormData();
    fd.append('_token','{{ csrf_token() }}');
    fd.append('_method','DELETE');
    fetch('{{ url("parc/pannes/lignes") }}/'+ligneId,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
    .then(function(r){ return r.json(); })
    .then(function(data){
        if(data.success){
            var tr=document.querySelector('#s-lignes-body tr[data-ligne-id="'+ligneId+'"]');
            if(tr) tr.remove();
            recalcTotalLignes();
            if(pannesData[_currentPanneId]){
                pannesData[_currentPanneId].cout_reel=data.cout_reel;
                pannesData[_currentPanneId].lignes=(pannesData[_currentPanneId].lignes||[]).filter(function(l){return l.id!=ligneId;});
            }
        } else { alert(data.message||'Erreur'); }
    }).catch(function(e){ alert('Erreur reseau'); });
}

/* ── Objectifs par véhicule pour saisie individuelle ── */
var vehObjectifs={!! json_encode($vehicules->mapWithKeys(function($v) {
    $cats = ['ouvrable','samedi','dimanche'];
    $objs = [];
    foreach($cats as $c){ $o = \App\Models\ParametreParc::objectifRecette($v->categorie ?? 'taxi', $c); $objs[$c] = $o > 0 ? $o : ($v->objectif_recette ?? 0); }
    return [$v->id => $objs];
})) !!};

function getTypeJour(dateStr){
    var d=new Date(dateStr+'T12:00:00');
    var day=d.getDay();
    if(day===0) return 'dimanche';
    return 'ouvrable'; // samedi = ouvrable
}

/* ── Saisie individuelle : dynamisme complet ── */
function getRecetteObjectif(){
    var vid=document.getElementById('r-vehic').value;
    var dateVal=document.getElementById('r-date').value||'{{ $dateJour }}';
    var typeJ=getTypeJour(dateVal);
    return (vehObjectifs[vid]&&vehObjectifs[vid][typeJ])||0;
}

function onVehiculeChange(){
    var vid=document.getElementById('r-vehic').value;
    majChauffeurs(vid,'r-chauff');
    // Auto-sélection si un seul chauffeur
    var sel=document.getElementById('r-chauff');
    if(sel && sel.options.length===2) sel.selectedIndex=1;
    majObjectifInfo();
    toggleMotif();
}

function majObjectifInfo(){
    var obj=getRecetteObjectif();
    var info=document.getElementById('r-objectif-info');
    var lbl=document.getElementById('r-obj-label');
    if(obj>0){
        lbl.textContent='Objectif attendu : '+obj.toLocaleString('fr-FR')+' F';
        info.style.display='block';
    } else {
        info.style.display='none';
    }
}

function toggleMotif(){
    var m=document.getElementById('r-montant');
    var z=document.getElementById('motif-zone');
    var pz=document.getElementById('perspective-zone');
    var vz=document.getElementById('validation-zone');
    var lbl=document.getElementById('motif-label');
    var modeWrap=document.getElementById('r-mode-wrap');
    var ecartInfo=document.getElementById('r-ecart-info');
    var montant=parseInt(m.value||'')||0;
    var objectif=getRecetteObjectif();

    // Mode remise : masquer si recette=0
    if(m.value!=='' && montant===0){
        modeWrap.style.display='none';
        document.getElementById('r-mode').value='';
    } else {
        modeWrap.style.display='';
    }

    // Indicateur écart temps réel
    if(m.value!=='' && objectif>0){
        var ecart=montant-objectif;
        if(ecart>=0){
            ecartInfo.style.display='block';
            ecartInfo.style.background='#f0fdf4';ecartInfo.style.border='1px solid #86efac';ecartInfo.style.color='#166534';
            ecartInfo.textContent='Objectif atteint'+(ecart>0?' (+'+ecart.toLocaleString('fr-FR')+' F)':'');
        } else {
            ecartInfo.style.display='block';
            ecartInfo.style.background='#fef2f2';ecartInfo.style.border='1px solid #fca5a5';ecartInfo.style.color='#991b1b';
            ecartInfo.textContent='Deficit : '+Math.abs(ecart).toLocaleString('fr-FR')+' F sous l\'objectif';
        }
    } else {
        ecartInfo.style.display='none';
    }

    // Zone motif + validation
    if(montant===0 && m.value!==''){
        z.style.display='block';
        lbl.textContent='JUSTIFICATION OBLIGATOIRE -- recette nulle';
        if(pz) pz.style.display='none';
        if(vz) vz.style.display='none';
    } else if(montant>0 && objectif>0 && montant<objectif){
        z.style.display='block';
        lbl.textContent='MOTIF OBLIGATOIRE -- objectif non atteint ('+objectif.toLocaleString('fr-FR')+' F)';
        if(pz) pz.style.display='block';
        if(vz) vz.style.display='block';
    } else {
        z.style.display='none';
        if(vz) vz.style.display='none';
    }

    majObjectifInfo();
}
document.getElementById('r-montant').addEventListener('input',toggleMotif);
document.getElementById('r-date').addEventListener('change',function(){ majObjectifInfo(); toggleMotif(); });

function validerFormRecette(){
    var montant=parseInt(document.getElementById('r-montant').value||'')||0;
    var mode=document.getElementById('r-mode').value;
    var motif=document.getElementById('r-motif').value;
    var ptgEl=document.getElementById('r-pointage');
    var ptg=ptgEl?ptgEl.value:'';
    var objectif=getRecetteObjectif();

    if(ptg===''){alert('Le pointage (FCFA) est obligatoire.');return false;}
    if(montant>0 && !mode){alert('Mode de remise obligatoire.');return false;}
    if(montant===0 && !motif){alert('Motif obligatoire lorsque la recette est nulle.');return false;}
    if(montant>0 && objectif>0 && montant<objectif && !motif){alert('Motif obligatoire : objectif non atteint ('+objectif.toLocaleString('fr-FR')+' F).');return false;}
    return true;
}

// Grille chauffeurs : toggle expand
function toggleRecette(idx){
    var item=document.getElementById('gc-'+idx);
    if(!item) return;
    // Fermer les autres
    document.querySelectorAll('.gc-item.gc-open').forEach(function(el){
        if(el!==item) el.classList.remove('gc-open');
    });
    item.classList.toggle('gc-open');
}

// Filtre recherche chauffeur (grille recettes)
function filtrerGrilleCh(q){
    q=q.toLowerCase().trim();
    document.querySelectorAll('#gc-list .gc-item').forEach(function(el){
        var nom=el.getAttribute('data-nom')||'';
        el.style.display=(!q||nom.indexOf(q)>-1)?'':'none';
    });
}

// Toggle justification valable / non valable
function toggleJustif(idx){
    var radios=document.querySelectorAll('input[name="justif-'+idx+'"]');
    var val='1';
    radios.forEach(function(r){if(r.checked) val=r.value;});
    var warn=document.getElementById('gc-recouv-warn-'+idx);
    if(warn) warn.style.display=(val==='0')?'':'none';
}

// Grille : toggle perspective + motif selon montant vs objectif
function recetteChange(idx){
    var item=document.getElementById('gc-'+idx);
    if(!item) return;
    var rec=document.getElementById('gr-rec-'+idx);
    var motifWrap=document.getElementById('gc-motif-wrap-'+idx);
    var perspWrap=document.getElementById('gc-persp-wrap-'+idx);
    var modeWrap=document.getElementById('gc-mode-wrap-'+idx);
    var objectif=parseFloat(item.getAttribute('data-objectif'))||0;
    var val=rec?rec.value:'';
    var montant=parseFloat(val)||0;
    var sousObj=(val!=='' && montant<objectif);

    if(modeWrap) modeWrap.style.display=(montant>0)?'':'none';
    if(motifWrap) motifWrap.style.display=sousObj?'':'none';
    if(perspWrap) perspWrap.style.display=(montant>0 && montant<objectif)?'':'none';
}

function validerLigne(idx){
    var item=document.getElementById('gc-'+idx);
    if(!item) return;
    // Bloquer si véhicule pris par un autre chauffeur
    if(item.getAttribute('data-pris')==='1'){alert('Ce véhicule est déjà conduit par un autre chauffeur aujourd\'hui.');return;}
    var vid=item.getAttribute('data-vid');
    var repos=item.getAttribute('data-repos')==='1';
    var objectif=parseFloat(item.getAttribute('data-objectif'))||0;
    var rec=document.getElementById('gr-rec-'+idx);
    var mode=document.getElementById('gr-mode-'+idx);
    var motif=document.getElementById('grille-motif-'+idx);
    var persp=document.getElementById('gr-persp-'+idx);

    var nomEl=item.querySelector('.gc-nom');
    var chauffeur=nomEl?nomEl.textContent.trim():'';
    if(chauffeur==='Sans chauffeur') chauffeur='';

    var montant=parseFloat(rec?rec.value:0)||0;
    if(!repos && rec && rec.value===''){alert('Saisissez le montant de la recette.');return;}
    var ptg=document.getElementById('gr-ptg-'+idx);
    if(!repos && ptg && ptg.value===''){alert('Le pointage (FCFA) est obligatoire.');return;}
    if(!repos && montant>0 && mode && !mode.value){alert('Mode de remise obligatoire.');return;}
    if(!repos && montant<objectif && motif && !motif.value){alert('Recette inférieure à l\'objectif : motif obligatoire.');return;}
    document.getElementById('gi-vehicule').value=vid;
    document.getElementById('gi-chauffeur').value=chauffeur;
    document.getElementById('gi-recette').value=repos?0:montant;
    document.getElementById('gi-pointage').value=ptg?ptg.value||0:0;
    document.getElementById('gi-mode').value=mode?mode.value:'';
    document.getElementById('gi-motif').value=motif?motif.value:'';
    document.getElementById('gi-perspective').value=persp?persp.value:'';
    // Justification valable (radio)
    var justifRadio=item.querySelector('input[name="justif-'+idx+'"]:checked');
    document.getElementById('gi-justif').value=justifRadio?justifRadio.value:'1';
    document.getElementById('gi-repos').value=repos?'1':'0';
    // Motif modification (si recette verrouillée)
    var motifModif=document.getElementById('gr-motif-modif-'+idx);
    document.getElementById('gi-motif-modif').value=motifModif?motifModif.value:'';
    document.getElementById('form-grille-ind').submit();
}

function toggleFinancementPenal(val){
    var w=document.getElementById('pp-financement-wrap');
    var e=w?w.closest('form').querySelector('.emprunt-fields'):null;
    if(w) w.style.display=val==='entreprise'?'block':'none';
    if(e && val!=='entreprise') e.style.display='none';
}
function ouvrirPaiementPenal(penId, chauffeur, solde){
    document.getElementById('dlg-pp-titre').textContent='Paiement — '+chauffeur;
    document.getElementById('form-paie-penal').action='{{ url("parc/penalites") }}/'+penId+'/paiement';
    document.getElementById('pp-montant').max=solde;
    document.getElementById('pp-montant').value='';
    document.getElementById('pp-solde-info').textContent='Solde restant : '+solde.toLocaleString('fr-FR')+' F';
    document.getElementById('pp-date').value=new Date().toISOString().slice(0,10);
    document.getElementById('pp-source').value='';
    toggleFinancementPenal('');
    document.getElementById('dlg-paie-penal').showModal();
}

function ouvrirRemboursement(empId, preteur, solde){
    document.getElementById('dlg-re-titre').textContent='Remboursement — '+preteur;
    document.getElementById('form-remb-emprunt').action='{{ url("parc/emprunts") }}/'+empId+'/remboursement';
    document.getElementById('re-montant').max=solde;
    document.getElementById('re-montant').value='';
    document.getElementById('re-solde-info').textContent='Solde restant : '+solde.toLocaleString('fr-FR')+' F';
    document.getElementById('re-date').value=new Date().toISOString().slice(0,10);
    document.getElementById('dlg-remb-emprunt').showModal();
}

function toggleEmpruntFields(selectEl){
    var form=selectEl.closest('form'), emp=selectEl.value==='emprunt';
    form.querySelectorAll('.emprunt-fields').forEach(function(w){ w.style.display=emp?'':'none'; });
    form.querySelectorAll('[data-requis-emprunt]').forEach(function(i){ i.required=emp; });
}

/* Facture de maintenance : lignes saisies dans le formulaire, coût = total */
var _mLignes=[];
function rendreLignesMaint(){
    var tb=document.getElementById('rm-lignes-body'); tb.innerHTML='';
    var total=0;
    _mLignes.forEach(function(l,i){
        var mt=l.q*l.p; total+=mt;
        var tr=document.createElement('tr'); tr.style.borderBottom='1px solid var(--bord)';
        tr.innerHTML='<td style="padding:5px 8px">'+escH(l.d)+'<input type="hidden" name="lignes['+i+'][designation]"><input type="hidden" name="lignes['+i+'][quantite]" value="'+l.q+'"><input type="hidden" name="lignes['+i+'][prix_unitaire]" value="'+l.p+'"></td>'
            +'<td style="text-align:center;padding:5px 4px">'+l.q+'</td><td style="text-align:right;padding:5px 4px">'+formatN(l.p)+'</td>'
            +'<td style="text-align:right;padding:5px 4px">'+formatN(mt)+'</td>'
            +'<td style="text-align:center"><button type="button" class="btn sm ko" onclick="_mLignes.splice('+i+',1);rendreLignesMaint()" style="padding:2px 6px;font-size:11px">X</button></td>';
        tr.querySelector('input[name$="[designation]"]').value=l.d;
        tb.appendChild(tr);
    });
    document.getElementById('rm-lignes-total').textContent=formatN(total);
    document.getElementById('rm-cout').value=total||'';
}
function ajouterLigneMaint(){
    var d=document.getElementById('rml-desig').value.trim(), q=parseInt(document.getElementById('rml-qte').value)||1, p=parseInt(document.getElementById('rml-pu').value)||0;
    if(!d){ alert('Désignation obligatoire'); return; }
    if(p<=0){ alert('Prix unitaire obligatoire'); return; }
    _mLignes.push({d:d,q:q,p:p}); rendreLignesMaint();
    document.getElementById('rml-desig').value=''; document.getElementById('rml-qte').value='1'; document.getElementById('rml-pu').value='';
    document.getElementById('rml-desig').focus();
}
function validerRealisationMaint(){
    if(!_mLignes.length){ alert('Ajoutez au moins une ligne à la facture de maintenance.'); return false; }
    return true;
}
/* Planifier une échéance depuis l'échéancier (véhicule et type pré-remplis) */
function planifierMaint(vehiculeId, type, derniere){
    var f=document.getElementById('form-maint'); f.reset();
    document.getElementById('maint-method').innerHTML='';
    f.action='{{ route("parc.maintenances.store") }}';
    document.getElementById('m-vehic').value=vehiculeId;
    if(type) document.getElementById('m-type').value=type;
    document.getElementById('m-dr').value=derniere||'';
    document.getElementById('m-pe')._manuallySet=false;
    calcProchaineEcheanceCreation();
    document.getElementById('dlg-maint').showModal();
}

function ouvrirVersement(recId, chauffeur, solde){
    document.getElementById('dlg-vers-titre').textContent='Versement — '+chauffeur;
    document.getElementById('form-versement').action='{{ url("parc/recouvrements") }}/'+recId+'/versement';
    document.getElementById('vs-montant').max=solde;
    document.getElementById('vs-montant').value='';
    document.getElementById('vs-solde-info').textContent='Solde restant : '+solde.toLocaleString('fr-FR')+' F';
    document.getElementById('vs-date').value=new Date().toISOString().slice(0,10);
    document.getElementById('dlg-versement').showModal();
}

function editRecette(id){
    var d=recettesData[id]; if(!d){alert('Donnees de la recette introuvables (ID '+id+'). Rechargez la page.');return;}
    // Fermer le dialog historique s'il est ouvert (evite superposition)
    var histoDlg=document.getElementById('dlg-histo-recettes');
    if(histoDlg && histoDlg.open) histoDlg.close();
    var estAdmin={{ auth()->user()->estAdmin() ? 'true' : 'false' }};
    var verrouille=!!d.valide_par && !estAdmin;
    document.getElementById('dlg-recette-titre').innerHTML='{!! $icWallet !!} '+(d.valide_par && !estAdmin?'Consulter la recette':'Modifier la recette');
    document.getElementById('form-recette').action='{{ url("parc/recettes") }}/'+id;
    document.getElementById('recette-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('r-date').value=d.date;
    document.getElementById('r-vehic').value=d.vehicule_id;
    majChauffeurs(d.vehicule_id,'r-chauff',d.chauffeur||'');
    document.getElementById('r-montant').value=d.recette;
    document.getElementById('r-pointage').value=d.pointage||0;
    document.getElementById('r-mode').value=d.mode_remise||'';
    document.getElementById('r-mode-wrap').style.display=(d.recette>0)?'':'none';
    document.getElementById('r-ctrl').value=d.controle_par||'';
    document.getElementById('r-obs').value=d.observations||'';
    // Motif : s'assurer que la valeur stockee est selectionnable
    var motifSel=document.getElementById('r-motif');
    var motifVal=d.motif_absence||'';
    if(motifVal){
        var motifExiste=false;
        for(var i=0;i<motifSel.options.length;i++){if(motifSel.options[i].value===motifVal){motifExiste=true;break;}}
        if(!motifExiste){
            var opt=document.createElement('option');opt.value=motifVal;opt.textContent=motifVal;
            motifSel.appendChild(opt);
        }
    }
    motifSel.value=motifVal;
    if(document.getElementById('r-perspective'))document.getElementById('r-perspective').value=d.perspective||'';
    // Validation gestionnaire
    if(d.justification_valable!==null && d.justification_valable!==undefined){
        var radios=document.querySelectorAll('input[name="justification_valable"]');
        radios.forEach(function(r){r.checked=(r.value===String(d.justification_valable?1:0))});
    }
    toggleMotif();
    // Verrouiller si deja valide
    var champs=document.querySelectorAll('#form-recette input:not([type="hidden"]),#form-recette select,#form-recette textarea');
    champs.forEach(function(el){el.disabled=verrouille});
    var btnSubmit=document.getElementById('r-btn-submit');
    if(btnSubmit) btnSubmit.style.display=verrouille?'none':'';
    document.getElementById('dlg-recette').showModal();
}
function ouvrirHistoRecettes(){
    try{
        var dlg=document.getElementById('dlg-histo-recettes');
        if(!dlg){console.error('Dialog dlg-histo-recettes introuvable');return;}
        filtrerHistoMois();
        dlg.showModal();
    }catch(e){console.error('Erreur ouverture historique recettes:',e);}
}
function filtrerHistoMois(){ filtrerListe('tbl-histo-recettes'); }
function ouvrirSaisieIndiv(){
    try{
        resetRecetteForm();
        var dlg=document.getElementById('dlg-recette');
        if(!dlg){console.error('Dialog dlg-recette introuvable');return;}
        dlg.showModal();
    }catch(e){console.error('Erreur ouverture saisie individuelle:',e);}
}
function onRDateChange(){
    var d=document.getElementById('r-date');
    var hier='{{ $dateJour }}';
    var retro=document.getElementById('r-date-retro');
    if(d.value && d.value < hier){
        d.style.borderColor='#f59e0b';d.style.background='#fffbeb';
        if(retro) retro.style.display='';
    } else {
        d.style.borderColor='';d.style.background='';
        if(retro) retro.style.display='none';
    }
}
function resetRecetteForm(){
    document.getElementById('dlg-recette-titre').innerHTML='{!! $icWallet !!} Nouvelle recette';
    document.getElementById('form-recette').action='{{ route("parc.recettes.store") }}';
    document.getElementById('recette-method').innerHTML='';
    document.getElementById('form-recette').reset();
    // Retirer les options de motif ajoutees dynamiquement
    var motifSel=document.getElementById('r-motif');
    var motifCodes=@json($motifsAbsence->pluck('code')->toArray());
    for(var i=motifSel.options.length-1;i>=0;i--){
        var v=motifSel.options[i].value;
        if(v!=='' && motifCodes.indexOf(v)===-1) motifSel.remove(i);
    }
    document.getElementById('r-date').value='{{ $dateJour }}';
    document.getElementById('r-date').style.borderColor='';
    document.getElementById('r-date').style.background='';
    var retro=document.getElementById('r-date-retro');
    if(retro) retro.style.display='none';
    var champs=document.querySelectorAll('#form-recette input:not([type="hidden"]),#form-recette select,#form-recette textarea');
    champs.forEach(function(el){el.disabled=false});
    var btnSubmit=document.getElementById('r-btn-submit');
    if(btnSubmit) btnSubmit.style.display='';
    document.getElementById('motif-zone').style.display='none';
    var vz=document.getElementById('validation-zone');
    if(vz) vz.style.display='none';
    document.getElementById('r-objectif-info').style.display='none';
    document.getElementById('r-ecart-info').style.display='none';
    document.getElementById('r-mode-wrap').style.display='';
    // Init vehicule -> chauffeur + objectif
    onVehiculeChange();
}
// Réouvrir le dialog automatiquement après erreur serveur
@if(request()->get('open_dlg')==='recette' && $errors->any())
document.addEventListener('DOMContentLoaded',function(){
    var editId={{ (int) request()->get('edit_recette_id', 0) }};
    if(editId && recettesData[editId]){
        editRecette(editId);
    } else {
        resetRecetteForm();
        document.getElementById('dlg-recette').showModal();
    }
    // Afficher l'erreur de validation
    var errMsg=@json($errors->first());
    if(errMsg){
        setTimeout(function(){alert(errMsg);},300);
    }
});
@endif

/* ── Nouveau formulaire saisie recette (remplace la grille) ── */
function onSrDateChange(){
    // Quand la date change, informer visuellement que la saisie est pour une date differente
    var dateInput=document.getElementById('sr-date');
    var today=new Date().toISOString().split('T')[0];
    var hier=new Date(Date.now()-86400000).toISOString().split('T')[0];
    if(dateInput.value && dateInput.value < hier){
        dateInput.style.borderColor='#f59e0b';
        dateInput.style.background='#fffbeb';
    } else {
        dateInput.style.borderColor='';
        dateInput.style.background='';
    }
}
function onSrChauffeurChange(){
    var sel=document.getElementById('sr-chauff');
    var opt=sel.options[sel.selectedIndex];
    var vid=opt?opt.getAttribute('data-vid'):'';
    var vimmat=opt?opt.getAttribute('data-vimmat'):'';
    var objectif=parseFloat(opt?opt.getAttribute('data-objectif'):'0')||0;
    var repos=opt?opt.getAttribute('data-repos')==='1':false;
    document.getElementById('sr-vehic').value=vid;
    document.getElementById('sr-vehic-label').value=vimmat||'';
    var objInfo=document.getElementById('sr-objectif-info');
    var objLabel=document.getElementById('sr-obj-label');
    if(objectif>0){
        objLabel.textContent='Objectif attendu : '+objectif.toLocaleString('fr-FR')+' F';
        objInfo.style.display='flex';
    } else {
        objInfo.style.display='none';
    }
    // Reset recette + ecart
    document.getElementById('sr-recette').value='';
    document.getElementById('sr-pointage').value='0';
    document.getElementById('sr-mode').value='';
    document.getElementById('sr-obs').value='';
    srToggleMotif();
}

function srGetObjectif(){
    var sel=document.getElementById('sr-chauff');
    var opt=sel.options[sel.selectedIndex];
    return parseFloat(opt?opt.getAttribute('data-objectif'):'0')||0;
}

function srToggleMotif(){
    var m=document.getElementById('sr-recette');
    var z=document.getElementById('sr-motif-zone');
    var pz=document.getElementById('sr-perspective-zone');
    var vz=document.getElementById('sr-validation-zone');
    var lbl=document.getElementById('sr-motif-label');
    var modeWrap=document.getElementById('sr-mode-wrap');
    var ecartInfo=document.getElementById('sr-ecart-info');
    var montant=parseInt(m.value||'')||0;
    var objectif=srGetObjectif();

    if(m.value!=='' && montant===0){
        modeWrap.style.display='none';
        document.getElementById('sr-mode').value='';
    } else {
        modeWrap.style.display='';
    }

    if(m.value!=='' && objectif>0){
        var ecart=montant-objectif;
        if(ecart>=0){
            ecartInfo.style.display='block';
            ecartInfo.style.background='#f0fdf4';ecartInfo.style.border='1px solid #86efac';ecartInfo.style.color='#166534';
            ecartInfo.textContent='Objectif atteint'+(ecart>0?' (+'+ecart.toLocaleString('fr-FR')+' F)':'');
        } else {
            ecartInfo.style.display='block';
            ecartInfo.style.background='#fef2f2';ecartInfo.style.border='1px solid #fca5a5';ecartInfo.style.color='#991b1b';
            ecartInfo.textContent='Deficit : '+Math.abs(ecart).toLocaleString('fr-FR')+' F sous l\'objectif';
        }
    } else {
        ecartInfo.style.display='none';
    }

    if(montant===0 && m.value!==''){
        z.style.display='block';
        lbl.textContent='JUSTIFICATION OBLIGATOIRE -- recette nulle';
        if(pz) pz.style.display='none';
        if(vz) vz.style.display='none';
    } else if(montant>0 && objectif>0 && montant<objectif){
        z.style.display='block';
        lbl.textContent='MOTIF OBLIGATOIRE -- objectif non atteint ('+objectif.toLocaleString('fr-FR')+' F)';
        if(pz) pz.style.display='block';
        if(vz) vz.style.display='block';
    } else {
        z.style.display='none';
        if(vz) vz.style.display='none';
    }
}
var srRec=document.getElementById('sr-recette');
if(srRec) srRec.addEventListener('input',srToggleMotif);

function validerFormSaisie(){
    var chauff=document.getElementById('sr-chauff');
    if(!chauff||!chauff.value){alert('Selectionnez un chauffeur.');return false;}
    var montant=parseInt(document.getElementById('sr-recette').value||'')||0;
    var mode=document.getElementById('sr-mode').value;
    var motif=document.getElementById('sr-motif').value;
    var ptg=document.getElementById('sr-pointage');
    var objectif=srGetObjectif();
    if(!ptg||ptg.value===''){alert('Le pointage (FCFA) est obligatoire.');return false;}
    if(montant>0 && !mode){alert('Mode de remise obligatoire.');return false;}
    if(montant===0 && !motif){alert('Motif obligatoire lorsque la recette est nulle.');return false;}
    if(montant>0 && objectif>0 && montant<objectif && !motif){alert('Motif obligatoire : objectif non atteint ('+objectif.toLocaleString('fr-FR')+' F).');return false;}
    return true;
}

/* ── Resume dialog auto-show ── */
@if(session('resume_recette'))
document.addEventListener('DOMContentLoaded',function(){
    var d=@json(session('resume_recette'));
    document.getElementById('res-header-sub').textContent=d.chauffeur+' — '+d.vehicule;
    document.getElementById('res-chauffeur').textContent=d.chauffeur;
    document.getElementById('res-vehicule').textContent=d.vehicule;
    document.getElementById('res-date').textContent=d.date;
    document.getElementById('res-montant').textContent=parseInt(d.montant).toLocaleString('fr-FR')+' FCFA';
    document.getElementById('res-objectif').textContent=parseInt(d.objectif).toLocaleString('fr-FR')+' F';
    var ecart=parseInt(d.ecart);
    var resEcart=document.getElementById('res-ecart');
    resEcart.textContent=(ecart>=0?'+':'')+ecart.toLocaleString('fr-FR')+' F';
    resEcart.style.color=ecart>=0?'var(--ok)':'var(--ko)';
    var resStatut=document.getElementById('res-statut');
    if(ecart>=0){
        resStatut.innerHTML='<span class="pill p-ok">Objectif atteint</span>';
    } else {
        resStatut.innerHTML='<span class="pill p-ko" style="font-size:11px">Deficit — motif requis</span>';
    }
    // Afficher boutons valider/invalider si deficit
    var actions=document.getElementById('res-motif-actions');
    if(parseInt(d.deficit)>0){
        actions.style.display='block';
        document.getElementById('res-form-valider').action='{{ url("parc/recettes") }}/'+d.recette_id+'/valider-motif';
        document.getElementById('res-form-invalider').action='{{ url("parc/recettes") }}/'+d.recette_id+'/invalider-motif';
    }
    document.getElementById('dlg-resume-recette').showModal();
});
@endif

function editMaint(id){
    var d=maintData[id]; if(!d)return;
    document.getElementById('dlg-maint-titre').innerHTML='{!! $icCalCheck !!} Modifier l\'échéance';
    document.getElementById('form-maint').action='{{ url("parc/maintenances") }}/'+id;
    document.getElementById('maint-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('m-vehic').value=d.vehicule_id;
    document.getElementById('m-type').value=d.type_echeance;
    document.getElementById('m-dr').value=d.derniere_realisation||'';
    var pe=document.getElementById('m-pe');pe.value=d.prochaine_echeance||'';pe._manuallySet=!!d.prochaine_echeance;
    document.getElementById('m-obs').value=d.observations||'';
    document.getElementById('dlg-maint').showModal();
}

function realiserMaint(id){
    var d=maintData[id]; if(!d)return;
    document.getElementById('form-realiser-maint').action='{{ url("parc/maintenances") }}/'+id+'/realiser';
    document.getElementById('rm-info-vehic').textContent=d.vehicule_label||'?';
    document.getElementById('rm-info-type').textContent=d.type_label||d.type_echeance;
    document.getElementById('rm-info-echeance').textContent='Échéance : '+(d.prochaine_echeance||'Non définie');
    document.getElementById('rm-type-code').value=d.type_echeance;
    var today=new Date().toISOString().slice(0,10);
    document.getElementById('rm-date').value=today;
    _mLignes=[]; rendreLignesMaint();
    var fin=document.querySelector('#form-realiser-maint select[name="source_financement"]'); fin.value='entreprise'; toggleEmpruntFields(fin);
    document.getElementById('rm-prest').value='';
    document.getElementById('rm-obs').value='';
    // Auto-suggérer prochaine échéance depuis date_realisation + intervalle du type
    calcProchaineEcheanceRealisation(d.type_echeance, today);
    document.getElementById('dlg-realiser-maint').showModal();
}

function calcProchaineEcheanceRealisation(typeCode, dateReal){
    var jours=maintIntervalles[typeCode]||180;
    if(dateReal){
        var next=new Date(dateReal); next.setDate(next.getDate()+jours);
        document.getElementById('rm-pe-suivante').value=next.toISOString().slice(0,10);
    }
}

function calcProchaineEcheanceCreation(){
    var type=document.getElementById('m-type').value;
    var dr=document.getElementById('m-dr').value;
    var pe=document.getElementById('m-pe');
    if(dr && type && !pe._manuallySet){
        var jours=maintIntervalles[type]||180;
        var next=new Date(dr); next.setDate(next.getDate()+jours);
        pe.value=next.toISOString().slice(0,10);
    }
}

function editPenal(id){
    var d=penalData[id]; if(!d)return;
    document.getElementById('dlg-penal-titre').innerHTML='{!! $icAlert !!} Modifier la déclaration';
    document.getElementById('form-penal').action='{{ url("parc/penalites") }}/'+id;
    document.getElementById('penal-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('pe-vehic').value=d.vehicule_id;
    majChauffeurs(d.vehicule_id,'pe-chauff',d.chauffeur||'');
    document.getElementById('pe-date').value=d.date_infraction;
    document.getElementById('pe-type').value=d.type;
    document.getElementById('pe-lieu').value=d.lieu||'';
    document.getElementById('pe-desc').value=d.description||'';
    document.getElementById('pe-mont').value=d.montant;
    document.getElementById('pe-statut').value=d.statut||'non_payee';
    document.getElementById('pe-dpay').value=d.date_paiement||'';
    document.getElementById('pe-obs').value=d.observations||'';
    document.getElementById('dlg-penal').showModal();
}

function statuerPenal(id){
    var d=penalData[id]; if(!d)return;
    var vLabel=vLabels[d.vehicule_id]||'?';
    document.getElementById('form-statuer-penal').action='{{ url("parc/penalites") }}/'+id;
    document.getElementById('sp-vehic').value=d.vehicule_id;
    document.getElementById('sp-date').value=d.date_infraction;
    document.getElementById('sp-type').value=d.type;
    document.getElementById('sp-chauff').value=d.chauffeur||'';
    document.getElementById('sp-lieu').value=d.lieu||'';
    document.getElementById('sp-desc').value=d.description||'';
    document.getElementById('sp-mont').value=d.montant;
    document.getElementById('sp-info-vehic').textContent=vLabel;
    document.getElementById('sp-info-date').textContent=d.date_infraction;
    var typeLabels=@json($typesPenalite->pluck('libelle','code'));
    document.getElementById('sp-info-type').textContent=typeLabels[d.type]||d.type;
    document.getElementById('sp-info-desc').textContent=d.description||'';
    document.getElementById('sp-info-mont').textContent=new Intl.NumberFormat('fr-FR').format(d.montant);
    document.getElementById('sp-statut').value=d.statut;
    document.getElementById('sp-dpay').value=d.date_paiement||'';
    document.getElementById('sp-obs').value=d.observations||'';
    document.getElementById('dlg-statuer-penal').showModal();
}

function editChauffeur(id){
    var d=chauffeurData[id]; if(!d)return;
    document.getElementById('dlg-chauffeur-titre').innerHTML='{!! $icUser !!} Modifier le chauffeur';
    document.getElementById('form-chauffeur').action='{{ url("parc/chauffeurs") }}/'+id;
    document.getElementById('chauffeur-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('ch-prenom').value=d.prenom||'';
    document.getElementById('ch-nom').value=d.nom||'';
    document.getElementById('ch-permis').value=d.numero_permis||'';
    document.getElementById('ch-tel').value=d.telephone||'';
    document.getElementById('ch-statut').value=d.statut||'actif';
    document.getElementById('ch-obs').value=d.observations||'';
    document.getElementById('dlg-chauffeur').showModal();
}

function affecterChauffeur(id){
    document.getElementById('af-chauffeur').value=id;
    var nom='';
    var d=chauffeurData[id]; if(d) nom=(d.prenom||'')+' '+(d.nom||'');
    document.getElementById('af-nom-chauffeur').textContent=nom.trim()||'Chauffeur #'+id;
    document.getElementById('dlg-affectation').showModal();
}

function ficheVehicule(vehiculeId){
    var dlg=document.getElementById('dlg-fiche');
    var contenu=document.getElementById('fiche-contenu');
    contenu.innerHTML='<p style="text-align:center;color:var(--gris)">Chargement...</p>';
    dlg.showModal();
    var fmt=function(n){return new Intl.NumberFormat('fr-FR').format(n||0)};
    fetch('{{ url("parc/vehicules") }}/'+vehiculeId+'/fiche?exercice={{ $exercice }}')
        .then(function(r){return r.json()})
        .then(function(d){
            var html='';
            /* ── En-tête identité ── */
            html+='<div style="background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:14px;margin-bottom:12px">';
            html+='<div style="font-size:18px;font-weight:700;color:var(--bleu)">'+d.immatriculation+'</div>';
            html+='<div style="font-size:12px;color:var(--gris);margin-top:4px">'+d.marque+' '+d.modele+' &middot; <span class="cat-pill cat-'+d.categorie+'">'+d.categorie_label+'</span> &middot; <span class="pill '+(d.statut==='en_service'?'p-ok':(d.statut==='immobilise'?'p-warn':'p-ko'))+'">'+d.statut_label+'</span></div>';
            if(d.kilometrage) html+='<div style="margin-top:6px;font-size:12px">Kilométrage : <strong>'+fmt(d.kilometrage)+' km</strong></div>';
            if(d.date_mise_en_service) html+='<div style="font-size:11.5px;color:var(--gris);margin-top:2px">En service depuis : '+d.date_mise_en_service+'</div>';
            if(d.observations) html+='<div style="font-size:11.5px;color:var(--gris);margin-top:4px;font-style:italic">'+d.observations+'</div>';
            html+='</div>';

            /* ── KPIs grille ── */
            var tauxCol=d.taux_recette>=100?'var(--ok)':(d.taux_recette>=80?'#b45309':'var(--ko)');
            html+='<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">';
            html+='<div style="background:#dbeafe;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:var(--accent)">'+fmt(d.recette_mois_avec_recouv)+' F</div><div style="font-size:10.5px;color:var(--gris)">Recette+Recouv ('+d.nb_jours+' j.)</div></div>';
            html+='<div style="background:#f0fdf4;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:'+tauxCol+'">'+d.taux_recette+'%</div><div style="font-size:10.5px;color:var(--gris)">Taux objectif</div></div>';
            html+='<div style="background:#eff6ff;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:var(--bleu)">'+fmt(d.pointage_mois)+' F</div><div style="font-size:10.5px;color:var(--gris)">Pointage exercice</div></div>';
            html+='<div style="background:#f0fdf4;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:var(--ok)">'+d.pannes_total+'</div><div style="font-size:10.5px;color:var(--gris)">Pannes ('+d.pannes_ouvertes+' ouv.)</div></div>';
            html+='<div style="background:#fee2e2;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:var(--ko)">'+d.penalites_impayees+'</div><div style="font-size:10.5px;color:var(--gris)">Pénalités ('+fmt(d.penalites_montant)+' F)</div></div>';
            html+='<div style="background:#fef3c7;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:#b45309">'+d.echeances_retard+'</div><div style="font-size:10.5px;color:var(--gris)">Maint. en retard</div></div>';
            html+='</div>';

            /* ── Onglets internes ── */
            var tabs=['recettes','chauffeurs','pannes','penalites','maintenance','recouvrements'];
            var labels=['Recettes','Chauffeurs','Pannes','Pénalités','Maintenance','Recouvrements'];
            html+='<div style="display:flex;gap:2px;border-bottom:2px solid var(--bord);margin-bottom:10px;flex-wrap:wrap">';
            tabs.forEach(function(t,i){
                html+='<button type="button" onclick="fvTab(\''+t+'\')" id="fv-btn-'+t+'" style="padding:5px 10px;font-size:11.5px;border:none;cursor:pointer;border-radius:6px 6px 0 0;background:'+(i===0?'var(--accent)':'#e5e7eb')+';color:'+(i===0?'#fff':'var(--gris)')+';font-weight:'+(i===0?'600':'400')+'">'+labels[i]+'</button>';
            });
            html+='</div>';

            /* ── Panel Recettes ── */
            html+='<div id="fv-pan-recettes" class="fv-panel">';
            if(d.recettes_historique && d.recettes_historique.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th>Chauffeur</th><th style="text-align:right">Recette</th><th style="text-align:right">Objectif</th><th style="text-align:right">Ecart</th><th style="text-align:right">Pointage</th><th>Motif</th></tr></thead><tbody>';
                d.recettes_historique.forEach(function(r){
                    var ec=r.ecart<0?'color:var(--ko)':'color:var(--ok)';
                    html+='<tr><td>'+r.date+'</td><td>'+r.chauffeur+'</td><td style="text-align:right">'+fmt(r.recette)+'</td><td style="text-align:right">'+fmt(r.objectif)+'</td><td style="text-align:right;'+ec+'">'+fmt(r.ecart)+'</td><td style="text-align:right">'+fmt(r.pointage)+'</td><td style="font-size:11px">'+(r.motif||'')+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucune recette enregistrée</p>'; }
            html+='</div>';

            /* ── Panel Chauffeurs ── */
            html+='<div id="fv-pan-chauffeurs" class="fv-panel" style="display:none">';
            html+='<div class="dlg-section">Chauffeurs actuels</div>';
            if(d.chauffeurs_actifs && d.chauffeurs_actifs.length>0){
                html+='<ul style="margin:0 0 10px;padding-left:18px;font-size:12.5px">';
                d.chauffeurs_actifs.forEach(function(c){
                    html+='<li>'+c.nom+' <span style="color:var(--gris)">('+c.role+(c.telephone?' — '+c.telephone:'')+')</span></li>';
                });
                html+='</ul>';
            } else { html+='<p style="color:var(--gris);font-size:12px">Aucun chauffeur affecté</p>'; }
            if(d.chauffeurs_passes && d.chauffeurs_passes.length>0){
                html+='<div class="dlg-section" style="margin-top:10px">Chauffeurs passés</div>';
                html+='<ul style="margin:0;padding-left:18px;font-size:12.5px">';
                d.chauffeurs_passes.forEach(function(c){html+='<li style="color:var(--gris)">'+c.nom+' ('+c.role+')</li>'});
                html+='</ul>';
            }
            html+='</div>';

            /* ── Panel Pannes ── */
            html+='<div id="fv-pan-pannes" class="fv-panel" style="display:none">';
            if(d.pannes && d.pannes.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th>Description</th><th>Urgence</th><th style="text-align:right">Coût</th><th>Statut</th></tr></thead><tbody>';
                d.pannes.forEach(function(pa){
                    html+='<tr><td>'+pa.date+'</td><td>'+pa.description+'</td><td>'+pa.urgence+'</td><td style="text-align:right">'+fmt(pa.cout)+'</td><td>'+pa.statut+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucune panne</p>'; }
            html+='</div>';

            /* ── Panel Pénalités ── */
            html+='<div id="fv-pan-penalites" class="fv-panel" style="display:none">';
            if(d.penalites && d.penalites.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th>Type</th><th>Chauffeur</th><th style="text-align:right">Montant</th><th>Statut</th></tr></thead><tbody>';
                d.penalites.forEach(function(p){
                    var sc=p.statut==='non_payee'?'color:var(--ko)':'color:var(--ok)';
                    html+='<tr><td>'+p.date+'</td><td>'+p.type+'</td><td>'+p.chauffeur+'</td><td style="text-align:right">'+fmt(p.montant)+'</td><td style="'+sc+'">'+p.statut.replace('_',' ')+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucune pénalité</p>'; }
            html+='</div>';

            /* ── Panel Maintenance ── */
            html+='<div id="fv-pan-maintenance" class="fv-panel" style="display:none">';
            if(d.maintenances && d.maintenances.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Type</th><th>Dernière réalisation</th><th>Prochaine échéance</th><th style="text-align:right">Coût</th><th>Statut</th></tr></thead><tbody>';
                d.maintenances.forEach(function(m){
                    var st=m.statut==='realisee'?'<span style="color:var(--ok);font-weight:600">Réalisée</span>':(m.retard?'<span style="color:var(--ko);font-weight:600">En retard</span>':'<span style="color:var(--ok)">À jour</span>');
                    var cout=m.cout>0?fmt(m.cout):'—';
                    html+='<tr><td>'+m.type+'</td><td>'+m.derniere_realisation+'</td><td>'+m.echeance+'</td><td style="text-align:right">'+cout+'</td><td>'+st+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucune maintenance planifiée</p>'; }
            html+='</div>';

            /* Panel Dépenses retiré (correspondait aux dépenses bureau) */

            /* ── Panel Recouvrements ── */
            html+='<div id="fv-pan-recouvrements" class="fv-panel" style="display:none">';
            if(d.recouvrements && d.recouvrements.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th>Chauffeur</th><th style="text-align:right">Déficit</th><th style="text-align:right">Recouvré</th><th style="text-align:right">Solde</th><th>Statut</th></tr></thead><tbody>';
                d.recouvrements.forEach(function(rc){
                    var sc2=rc.statut==='actif'?'color:var(--ko)':'color:var(--ok)';
                    html+='<tr><td>'+rc.date+'</td><td>'+rc.chauffeur+'</td><td style="text-align:right">'+fmt(rc.deficit)+'</td><td style="text-align:right">'+fmt(rc.recouvre)+'</td><td style="text-align:right">'+fmt(rc.solde)+'</td><td style="'+sc2+'">'+rc.statut+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucun recouvrement</p>'; }
            html+='</div>';

            contenu.innerHTML=html;
        })
        .catch(function(){contenu.innerHTML='<p style="color:var(--ko)">Erreur de chargement.</p>'});
}

function ficheChauffeur(chauffeurId){
    var dlg=document.getElementById('dlg-fiche-ch');
    var contenu=document.getElementById('fiche-ch-contenu');
    contenu.innerHTML='<p style="text-align:center;color:var(--gris)">Chargement...</p>';
    dlg.showModal();
    var fmt=function(n){return new Intl.NumberFormat('fr-FR').format(n||0)};
    fetch('{{ url("parc/chauffeurs") }}/'+chauffeurId+'/fiche?exercice={{ $exercice }}')
        .then(function(r){return r.json()})
        .then(function(d){
            var html='';
            /* ── En-tête identité ── */
            html+='<div style="background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:14px;margin-bottom:12px">';
            html+='<div style="font-size:18px;font-weight:700;color:var(--bleu)">'+d.nom+'</div>';
            html+='<div style="font-size:12px;color:var(--gris);margin-top:4px">';
            html+='<span class="pill '+(d.statut==='actif'?'p-ok':'p-gris')+'">'+d.statut.charAt(0).toUpperCase()+d.statut.slice(1)+'</span>';
            if(d.telephone) html+=' &middot; '+d.telephone;
            if(d.permis) html+=' &middot; Permis: '+d.permis;
            if(d.date_embauche) html+=' &middot; Depuis: '+d.date_embauche;
            html+='</div>';
            if(d.observations) html+='<div style="font-size:11.5px;color:var(--gris);margin-top:4px;font-style:italic">'+d.observations+'</div>';
            html+='</div>';

            /* ── KPIs grille ── */
            html+='<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px">';
            var tauxCol=d.taux_recette>=100?'var(--ok)':(d.taux_recette>=80?'#b45309':'var(--ko)');
            html+='<div style="background:#dbeafe;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:var(--accent)">'+fmt(d.recette_totale||d.recette_mois)+' F</div><div style="font-size:10.5px;color:var(--gris)">Recette+Recouv ('+d.jours_saisis+' j.)</div></div>';
            html+='<div style="background:#f0fdf4;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:'+tauxCol+'">'+d.taux_recette+'%</div><div style="font-size:10.5px;color:var(--gris)">Taux objectif</div></div>';
            html+='<div style="background:#eff6ff;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:var(--bleu)">'+d.jours_atteints+'/'+d.jours_saisis+'</div><div style="font-size:10.5px;color:var(--gris)">Jours atteints</div></div>';
            html+='<div style="background:#fef3c7;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:#b45309">'+fmt(d.pointage_mois)+' F</div><div style="font-size:10.5px;color:var(--gris)">Pointage exercice</div></div>';
            html+='<div style="background:#fee2e2;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:var(--ko)">'+d.penalites_impayees+'</div><div style="font-size:10.5px;color:var(--gris)">Pénalités impayées ('+fmt(d.penalites_montant)+' F)</div></div>';
            html+='<div style="background:#fef9c3;border-radius:8px;padding:10px;text-align:center"><div style="font-size:17px;font-weight:700;color:#92400e">'+d.recouvrements_actifs+'</div><div style="font-size:10.5px;color:var(--gris)">Recouvrements ('+fmt(d.solde_recouvrement)+' F)</div></div>';
            html+='</div>';

            /* ── Onglets internes ── */
            var tabs=['recettes','vehicules','penalites','recouvrements','pannes'];
            var labels=['Recettes','Véhicules','Pénalités','Recouvrements','Pannes'];
            html+='<div style="display:flex;gap:2px;border-bottom:2px solid var(--bord);margin-bottom:10px">';
            tabs.forEach(function(t,i){
                html+='<button type="button" onclick="fcTab(\''+t+'\')" id="fc-btn-'+t+'" style="padding:6px 12px;font-size:12px;border:none;cursor:pointer;border-radius:6px 6px 0 0;background:'+(i===0?'var(--accent)':'#e5e7eb')+';color:'+(i===0?'#fff':'var(--gris)')+';font-weight:'+(i===0?'600':'400')+'">'+labels[i]+'</button>';
            });
            html+='</div>';

            /* ── Panel Recettes ── */
            html+='<div id="fc-pan-recettes" class="fc-panel">';
            if(d.recettes_historique && d.recettes_historique.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th>Véhicule</th><th style="text-align:right">Recette</th><th style="text-align:right">Objectif</th><th style="text-align:right">Ecart</th><th style="text-align:right">Pointage</th><th>Motif</th></tr></thead><tbody>';
                d.recettes_historique.forEach(function(r){
                    var ec=r.ecart<0?'color:var(--ko)':'color:var(--ok)';
                    html+='<tr><td>'+r.date+'</td><td>'+r.vehicule+'</td><td style="text-align:right">'+fmt(r.recette)+'</td><td style="text-align:right">'+fmt(r.objectif)+'</td><td style="text-align:right;'+ec+'">'+fmt(r.ecart)+'</td><td style="text-align:right">'+fmt(r.pointage)+'</td><td style="font-size:11px">'+( r.motif||'')+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucune recette enregistrée</p>'; }
            html+='</div>';

            /* ── Panel Véhicules ── */
            html+='<div id="fc-pan-vehicules" class="fc-panel" style="display:none">';
            html+='<div class="dlg-section">Véhicules actuels</div>';
            if(d.vehicules_actifs && d.vehicules_actifs.length>0){
                html+='<ul style="margin:0 0 10px;padding-left:18px;font-size:12.5px">';
                d.vehicules_actifs.forEach(function(v){html+='<li>'+v.immatriculation+' <span style="color:var(--gris)">('+v.categorie+' — '+v.role+')</span></li>'});
                html+='</ul>';
            } else { html+='<p style="color:var(--gris);font-size:12px">Aucun véhicule affecté actuellement</p>'; }
            if(d.vehicules_passes && d.vehicules_passes.length>0){
                html+='<div class="dlg-section" style="margin-top:10px">Véhicules passés</div>';
                html+='<ul style="margin:0 0 10px;padding-left:18px;font-size:12.5px">';
                d.vehicules_passes.forEach(function(v){html+='<li style="color:var(--gris)">'+v.immatriculation+' ('+v.categorie+' — '+v.role+')</li>'});
                html+='</ul>';
            }
            html+='</div>';

            /* ── Panel Pénalités ── */
            html+='<div id="fc-pan-penalites" class="fc-panel" style="display:none">';
            if(d.penalites && d.penalites.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th>Type</th><th>Véhicule</th><th style="text-align:right">Montant</th><th>Statut</th></tr></thead><tbody>';
                d.penalites.forEach(function(p){
                    var sc=p.statut==='non_payee'?'color:var(--ko)':'color:var(--ok)';
                    html+='<tr><td>'+p.date+'</td><td>'+p.type+'</td><td>'+p.vehicule+'</td><td style="text-align:right">'+fmt(p.montant)+'</td><td style="'+sc+'">'+p.statut.replace('_',' ')+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucune pénalité</p>'; }
            html+='</div>';

            /* ── Panel Recouvrements ── */
            html+='<div id="fc-pan-recouvrements" class="fc-panel" style="display:none">';
            if(d.recouvrements && d.recouvrements.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th style="text-align:right">Déficit</th><th style="text-align:right">Recouvré</th><th style="text-align:right">Solde</th><th>Statut</th></tr></thead><tbody>';
                d.recouvrements.forEach(function(rc){
                    var sc2=rc.statut==='actif'?'color:var(--ko)':'color:var(--ok)';
                    html+='<tr><td>'+rc.date+'</td><td style="text-align:right">'+fmt(rc.deficit)+'</td><td style="text-align:right">'+fmt(rc.recouvre)+'</td><td style="text-align:right">'+fmt(rc.solde)+'</td><td style="'+sc2+'">'+rc.statut+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucun recouvrement</p>'; }
            html+='</div>';

            /* ── Panel Pannes ── */
            html+='<div id="fc-pan-pannes" class="fc-panel" style="display:none">';
            if(d.pannes && d.pannes.length>0){
                html+='<div style="overflow-x:auto"><table class="table" style="font-size:12px;width:100%"><thead><tr><th>Date</th><th>Véhicule</th><th>Description</th><th style="text-align:right">Coût</th><th>Statut</th></tr></thead><tbody>';
                d.pannes.forEach(function(pa){
                    html+='<tr><td>'+pa.date+'</td><td>'+pa.vehicule+'</td><td>'+pa.description+'</td><td style="text-align:right">'+fmt(pa.cout)+'</td><td>'+pa.statut+'</td></tr>';
                });
                html+='</tbody></table></div>';
            } else { html+='<p style="color:var(--gris)">Aucune panne liée</p>'; }
            html+='</div>';

            contenu.innerHTML=html;
        })
        .catch(function(){contenu.innerHTML='<p style="color:var(--ko)">Erreur de chargement.</p>'});
}
function fcTab(nom){
    var tabs=['recettes','vehicules','penalites','recouvrements','pannes'];
    tabs.forEach(function(t){
        var pan=document.getElementById('fc-pan-'+t);
        var btn=document.getElementById('fc-btn-'+t);
        if(pan) pan.style.display=(t===nom?'':'none');
        if(btn){btn.style.background=(t===nom?'var(--accent)':'#e5e7eb');btn.style.color=(t===nom?'#fff':'var(--gris)');btn.style.fontWeight=(t===nom?'600':'400')}
    });
}
function fvTab(nom){
    var tabs=['recettes','chauffeurs','pannes','penalites','maintenance','depenses','recouvrements'];
    tabs.forEach(function(t){
        var pan=document.getElementById('fv-pan-'+t);
        var btn=document.getElementById('fv-btn-'+t);
        if(pan) pan.style.display=(t===nom?'':'none');
        if(btn){btn.style.background=(t===nom?'var(--accent)':'#e5e7eb');btn.style.color=(t===nom?'#fff':'var(--gris)');btn.style.fontWeight=(t===nom?'600':'400')}
    });
}

/* Filtres de liste : éléments [data-ligne] du conteneur cible ; les data-* peuvent contenir plusieurs valeurs séparées par « | » */
function filtrerListe(cible){
    var bar=document.querySelector('.filtre-bar[data-cible="'+cible+'"]'), cont=document.getElementById(cible);
    if(!bar||!cont) return;
    var crit={};
    bar.querySelectorAll('[data-f]').forEach(function(el){ crit[el.dataset.f]=el.value.trim(); });
    var d1='',d2='', ex=bar.querySelector('[data-f="exercice"]');
    if(ex&&ex.value){ var o=ex.options[ex.selectedIndex]; d1=o.dataset.debut; d2=o.dataset.fin; }
    var q=(crit.q||'').toLowerCase(), n=0, tot=0, somme=0, aMontant=false;
    cont.querySelectorAll('[data-ligne]').forEach(function(el){
        tot++;
        var ok=true;
        if(d1){ var d=el.dataset.date||''; if(!d||d<d1||d>d2) ok=false; }
        ['vehicule','chauffeur','statut','type','categorie'].forEach(function(k){
            if(crit[k] && ('|'+(el.dataset[k]||'')+'|').indexOf('|'+crit[k]+'|')===-1) ok=false;
        });
        if(q && el.textContent.toLowerCase().indexOf(q)===-1) ok=false;
        el.style.display=ok?'':'none';
        if(ok){ n++; if(el.dataset.montant!==undefined){ aMontant=true; somme+=parseFloat(el.dataset.montant)||0; } }
        if(el.tagName==='TR'){
            // Lignes rattachées (détails, paiements) : suivent la ligne principale
            var s=el.nextElementSibling;
            while(s && !s.hasAttribute('data-ligne')){
                if(!ok){ s.dataset.fMasque='1'; s.style.display='none'; }
                else if(s.dataset.fMasque){ delete s.dataset.fMasque; s.style.display=s.classList.contains('detail-row')?'none':''; }
                s=s.nextElementSibling;
            }
        }
    });
    var res=bar.querySelector('[data-resultat]');
    if(res) res.innerHTML=tot ? '<strong>'+n+'</strong> / '+tot+' affiché(s)'+(aMontant?' · Total : <strong>'+Math.round(somme).toLocaleString('fr-FR')+' F</strong>':'') : '';
}
function effacerFiltres(cible){
    var bar=document.querySelector('.filtre-bar[data-cible="'+cible+'"]');
    if(!bar) return;
    bar.querySelectorAll('[data-f]').forEach(function(el){ el.value=''; });
    filtrerListe(cible);
}
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('.filtre-bar[data-cible]').forEach(function(b){ filtrerListe(b.dataset.cible); });
});

function filtrerChauffeurs(){
    var statut=document.getElementById('fc-statut').value;
    var vehic=document.getElementById('fc-vehicule').value;
    var search=document.getElementById('fc-search').value.toLowerCase().trim();
    var rows=document.querySelectorAll('#tbl-chauffeurs tbody tr[data-nom]');
    rows.forEach(function(tr){
        var show=true;
        if(statut && tr.dataset.statut!==statut) show=false;
        if(vehic==='affecte' && tr.dataset.affecte!=='1') show=false;
        if(vehic==='non_affecte' && tr.dataset.affecte!=='0') show=false;
        if(search && tr.dataset.nom.indexOf(search)===-1) show=false;
        tr.style.display=show?'':'none';
    });
}

document.querySelectorAll('dialog').forEach(function(dlg){
    dlg.addEventListener('close',function(){
        var form=dlg.querySelector('form');
        if(form){form.reset();var m=dlg.querySelector('[id$="-method"]');if(m)m.innerHTML='';}
        var titles={'dlg-vehicule':'{!! $icCar !!} Nouveau véhicule','dlg-panne':'{!! $icWrench !!} Déclarer une panne','dlg-recette':'{!! $icWallet !!} Nouvelle recette','dlg-maint':'{!! $icCalCheck !!} Nouvelle échéance','dlg-penal':'{!! $icAlert !!} Déclarer une pénalité','dlg-chauffeur':'{!! $icUser !!} Nouveau chauffeur','dlg-depense':'{!! $icDepense !!} Nouvelle dépense'};
        var t=titles[dlg.id];if(t){var h=dlg.querySelector('h3[id]');if(h)h.innerHTML=t;}
        if(dlg.id==='dlg-vehicule'){if(form)form.action='{{ route("parc.vehicules.store") }}';document.getElementById('v-chauffeurs-list').innerHTML='';chauffRowIdx=0;}
        if(dlg.id==='dlg-panne'&&form)form.action='{{ route("parc.pannes.store") }}';
        if(dlg.id==='dlg-recette'&&form){form.action='{{ route("parc.recettes.store") }}';onVehiculeChange();}
        if(dlg.id==='dlg-maint'&&form){form.action='{{ route("parc.maintenances.store") }}';var pe=document.getElementById('m-pe');if(pe)pe._manuallySet=false;}
        if(dlg.id==='dlg-penal'&&form){form.action='{{ route("parc.penalites.store") }}';var pv=document.getElementById('pe-vehic');if(pv&&pv.value)majChauffeurs(pv.value,'pe-chauff');}
        if(dlg.id==='dlg-chauffeur'&&form)form.action='{{ route("parc.chauffeurs.store") }}';
        if(dlg.id==='dlg-depense'&&form)form.action='{{ route("parc.depenses.store") }}';
    });
});

function demanderMotifAnnulation(form){
    var motif=prompt('Motif de l\'annulation (obligatoire) :');
    if(!motif||motif.trim().length<5){alert('Le motif est obligatoire (5 caractères minimum).');return false;}
    form.querySelector('.motif-annul-input').value=motif.trim();
    return true;
}
// Toggle info entreprise dans dialog versement
(function(){
    var src=document.getElementById('vs-source');
    if(src) src.addEventListener('change',function(){
        document.getElementById('vs-entreprise-info').style.display=(this.value==='entreprise')?'':'none';
    });
})();

function editerDepense(id){
    var d=depensesData[id]; if(!d)return;
    document.getElementById('dlg-depense-titre').innerHTML='{!! $icDepense !!} Modifier la dépense';
    document.getElementById('form-depense').action='{{ url("parc/depenses") }}/'+id;
    document.getElementById('depense-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('de-date').value=d.date_depense;
    document.getElementById('de-type').value=d.type_depense;
    document.getElementById('de-montant').value=d.montant;
    document.getElementById('de-comment').value=d.commentaire||'';
    document.getElementById('dlg-depense').showModal();
}

// === Planning ===
// === Prêts ===
function ouvrirRembPret(pretId, beneficiaire, solde){
    document.getElementById('dlg-rp-titre').textContent = 'Remboursement — ' + beneficiaire;
    document.getElementById('form-remb-pret').action = '/parc/prets/' + pretId + '/remboursement';
    document.getElementById('rp-montant').max = solde;
    document.getElementById('rp-montant').placeholder = 'Max ' + solde.toLocaleString('fr-FR') + ' F';
    document.getElementById('dlg-remb-pret').showModal();
}

// === Ressources ===
@php
$_resData = [];
foreach($autresRessources ?? [] as $_res) {
    $_resData[$_res->id] = [
        'date_ressource' => $_res->date_ressource->format('Y-m-d'),
        'type_ressource' => $_res->type_ressource,
        'montant' => $_res->montant,
        'source' => $_res->source,
        'description' => $_res->description,
        'vehicule_id' => $_res->vehicule_id,
        'caisse_destination' => $_res->caisse_destination,
    ];
}
@endphp
var resData = @json($_resData);
function ouvrirEditRessource(id){
    var d = resData[id];
    if(!d) return;
    document.getElementById('form-ressource').action = '/parc/ressources/' + id;
    document.getElementById('ressource-method').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('rs-date').value = d.date_ressource;
    document.getElementById('rs-type').value = d.type_ressource;
    document.getElementById('rs-montant').value = d.montant;
    document.getElementById('rs-source').value = d.source || '';
    document.getElementById('rs-vehicule').value = d.vehicule_id || '';
    document.getElementById('rs-caisse').value = d.caisse_destination || 'generale';
    document.getElementById('rs-desc').value = d.description || '';
    document.getElementById('dlg-ressource').showModal();
}

// ═══ Boutons défilement horizontal ═══
document.addEventListener('DOMContentLoaded', function() {
    const arrowSvgL = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>';
    const arrowSvgR = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 6 15 12 9 18"/></svg>';

    // Tab-bar scroll arrows
    var tabBar = document.getElementById('tab-bar');
    var tabL = document.getElementById('tab-arrow-l');
    var tabR = document.getElementById('tab-arrow-r');
    if (tabBar && tabL && tabR) {
        function updateTabArrows() {
            tabL.style.display = tabBar.scrollLeft > 5 ? 'flex' : 'none';
            tabR.style.display = tabBar.scrollLeft + tabBar.clientWidth < tabBar.scrollWidth - 5 ? 'flex' : 'none';
        }
        tabBar.addEventListener('scroll', updateTabArrows);
        window.addEventListener('resize', updateTabArrows);
        setTimeout(updateTabArrows, 200);
    }

    document.querySelectorAll('.panneau[style*="overflow-x"], div[style*="overflow-x:auto"]').forEach(function(el) {
        if (el.closest('dialog')) return;
        if (el.scrollWidth <= el.clientWidth + 10) return;

        var wrap = document.createElement('div');
        wrap.className = 'scroll-wrap';
        el.parentNode.insertBefore(wrap, el);
        wrap.appendChild(el);

        var btnL = document.createElement('button');
        btnL.type = 'button';
        btnL.className = 'scroll-arrow left';
        btnL.innerHTML = arrowSvgL;
        btnL.addEventListener('click', function() { el.scrollBy({left: -200, behavior: 'smooth'}); });

        var btnR = document.createElement('button');
        btnR.type = 'button';
        btnR.className = 'scroll-arrow right';
        btnR.innerHTML = arrowSvgR;
        btnR.addEventListener('click', function() { el.scrollBy({left: 200, behavior: 'smooth'}); });

        wrap.insertBefore(btnL, el);
        wrap.appendChild(btnR);

        function updateArrows() {
            btnL.style.display = el.scrollLeft > 5 ? 'flex' : 'none';
            btnR.style.display = el.scrollLeft + el.clientWidth < el.scrollWidth - 5 ? 'flex' : 'none';
        }
        el.addEventListener('scroll', updateArrows);
        window.addEventListener('resize', updateArrows);
        setTimeout(updateArrows, 200);
    });
});
</script>
@endpush
