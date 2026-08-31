@extends('layouts.app')
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
.tab-btn.actif{color:var(--bleu);border-bottom-color:var(--accent)}
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
.filtre-bar select,.filtre-bar input[type="month"],.filtre-bar input[type="text"]{padding:5px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.urgence-faible{color:#475569}.urgence-moyenne{color:var(--warn)}.urgence-haute{color:#ea580c;font-weight:700}.urgence-critique{color:var(--ko);font-weight:700}
.act-row{display:flex;gap:4px;align-items:center}
.act-btn{background:none;border:none;cursor:pointer;padding:3px;border-radius:4px;color:var(--gris)}
.act-btn:hover{background:#f1f5f9;color:var(--bleu)}
.act-btn.del{color:var(--ko)}
.act-btn.del:hover{background:#fef2f2}
.header-actions{display:flex;gap:8px;align-items:center}
.empty-msg{text-align:center;color:var(--gris);padding:24px;font-size:12.5px}
@media(max-width:900px){.kpis{grid-template-columns:repeat(2,1fr)}.form-row,.form-row3{grid-template-columns:1fr}table{display:block;overflow-x:auto}.panel-head{flex-wrap:wrap;gap:8px}.header-actions{flex-wrap:wrap}}
@media(max-width:600px){.kpis{grid-template-columns:1fr}.kpi .val{font-size:22px}.tab-btn{padding:8px 12px;font-size:12px}.tab-btn svg{width:14px;height:14px}.panneau{padding:10px 8px}.panel-head h3{font-size:13px}.btn.sm{padding:4px 8px;font-size:10.5px}dialog{padding:16px;width:95vw}}
@media(max-width:400px){.tab-btn span.pill{display:none}.tab-btn{padding:6px 8px;gap:4px}.kpi{padding:10px}}
/* Grille chauffeurs recettes */
.gc-list{display:flex;flex-direction:column;gap:6px}
.gc-item{background:var(--carte);border:1px solid var(--bord);border-radius:10px;overflow:hidden;transition:box-shadow .15s}
.gc-item:hover{box-shadow:0 2px 8px rgba(0,0,0,.06)}
.gc-item.gc-repos{border-left:3px solid var(--ok);background:#f0fdf4}
.gc-item.gc-inactif{opacity:.65;border-left:3px solid var(--gris)}
.gc-av-off{background:var(--gris)!important;color:#fff!important}
.gc-item.gc-fait{border-left:3px solid var(--accent)}
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
.gc-item.gc-open .gc-detail{max-height:300px}
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
<div class="kpis">
    <div class="kpi"><div class="lbl">En service</div><div class="val">{{ $kpi['actifs'] }}<span style="font-size:13px;color:var(--gris);font-weight:400">/{{ $kpi['total'] }}</span></div><div class="sub">{{ $kpi['taxis'] }} Taxis · {{ $kpi['vtcs'] }} VTC</div></div>
    <div class="kpi" @if($kpi['pannes_ouvertes']>0) style="background:#fff7ed;border-color:#fed7aa" @endif><div class="lbl">Pannes ouvertes</div><div class="val" style="color:{{ $kpi['pannes_ouvertes']>0 ? '#b45309' : 'var(--ok)' }}">{{ $kpi['pannes_ouvertes'] }}</div></div>
    <div class="kpi"><div class="lbl">Coût pannes ce mois</div><div class="val" style="font-size:22px">{{ number_format($kpi['cout_mois'], 0, ',', ' ') }}</div><div class="sub">FCFA</div></div>
    <div class="kpi"><div class="lbl">Taux recette / obj.</div><div class="val" style="color:{{ $kpi['taux_recette'] >= 100 ? 'var(--ok)' : ($kpi['taux_recette'] >= 80 ? '#b45309' : 'var(--ko)') }}">{{ $kpi['taux_recette'] }}%</div></div>
    <div class="kpi" @if($kpi['echeances_retard']>0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">Échéances en retard</div><div class="val" style="color:{{ $kpi['echeances_retard']>0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $kpi['echeances_retard'] }}</div></div>
    <div class="kpi" @if($kpi['penalites_non_payees']>0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">Pénalités impayées</div><div class="val" style="color:{{ $kpi['penalites_non_payees']>0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $kpi['penalites_non_payees'] }}</div></div>
</div>

{{-- Onglets --}}
<div class="tab-bar" id="tab-bar">
    <button class="tab-btn actif" onclick="changerOnglet('vehicules')">{!! $icCar !!} Véhicules <span class="pill p-cours">{{ $vehicules->count() }}</span></button>
    <button class="tab-btn" onclick="changerOnglet('pannes')">{!! $icWrench !!} Pannes @if($kpi['pannes_ouvertes']>0)<span class="pill p-ko">{{ $kpi['pannes_ouvertes'] }}</span>@endif</button>
    <button class="tab-btn" onclick="changerOnglet('recettes')">{!! $icWallet !!} Recettes</button>
    <button class="tab-btn" onclick="changerOnglet('maint')">{!! $icCalCheck !!} Maintenance @if($kpi['echeances_retard']>0)<span class="pill p-ko">{{ $kpi['echeances_retard'] }}</span>@endif</button>
    <button class="tab-btn" onclick="changerOnglet('penal')">{!! $icAlert !!} Pénalités @if($kpi['penalites_non_payees']>0)<span class="pill p-ko">{{ $kpi['penalites_non_payees'] }}</span>@endif</button>
    <button class="tab-btn" onclick="changerOnglet('recouv')">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Recouvrements @if(($recouvrements ?? collect())->where('statut','actif')->count()>0)<span class="pill p-ko">{{ ($recouvrements ?? collect())->where('statut','actif')->count() }}</span>@endif</button>
    <button class="tab-btn" onclick="changerOnglet('chauffeurs')">{!! $icUser !!} Chauffeurs <span class="pill p-cours">{{ $chauffeurs->count() }}</span></button>
</div>

{{-- ==================== TAB VÉHICULES ==================== --}}
<div class="tab-panel actif" id="tab-vehicules">
    <div class="panel-head">
        <h3>{!! $icCar !!} Véhicules <span class="pill p-cours">{{ $vehicules->count() }}</span></h3>
        <button class="btn sm" onclick="document.getElementById('dlg-vehicule').showModal()">{!! $icPlus !!} Véhicule</button>
    </div>
    <div class="panneau" style="overflow-x:auto">
    <table>
    <thead><tr><th>Immatriculation</th><th>Cat.</th><th>Marque / Modèle</th><th>Chauffeur</th><th>Km</th><th>Mise en service</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @forelse($vehicules as $v)
    <tr>
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
                <button class="act-btn" title="Fiche" onclick="ficheVehicule({{ $v->id }})" style="color:var(--accent)">{!! $icEye !!}</button>
                <button class="act-btn" title="Modifier" onclick="editVehicule({{ $v->id }})">{!! $icEdit !!}</button>
                @if(auth()->user()->estPilote())
                <form method="POST" action="{{ route('parc.vehicules.destroy', $v) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" class="empty-msg">Aucun véhicule enregistré.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB PANNES ==================== --}}
<div class="tab-panel" id="tab-pannes">
    <div class="panel-head">
        <h3>{!! $icWrench !!} Pannes @if($kpi['pannes_ouvertes']>0)<span class="pill p-ko">{{ $kpi['pannes_ouvertes'] }} ouvertes</span>@endif</h3>
        <button class="btn sm" onclick="document.getElementById('dlg-panne').showModal()">{!! $icPlus !!} Panne</button>
    </div>
    <div class="panneau" style="overflow-x:auto">
    <table>
    <thead><tr><th>Véhicule</th><th>Date</th><th>Description</th><th>Urgence</th><th>Statut</th><th>Coût</th><th>Délai</th><th></th></tr></thead>
    <tbody>
    @forelse($pannes as $p)
    <tr>
        <td><strong>{{ $p->vehicule->immatriculation ?? '?' }}</strong></td>
        <td>{{ $p->date_declaration ? $p->date_declaration->format('d/m/Y') : '—' }}</td>
        <td style="max-width:200px">{{ Str::limit($p->description, 60) }}</td>
        <td><span class="urgence-{{ $p->urgence }}">{{ ucfirst($p->urgence) }}</span></td>
        <td><span class="pill {{ $p->statut === 'cloturee' ? 'p-ok' : ($p->statut === 'declaree' ? 'p-ko' : 'p-cours') }}">{{ $p->statutLabel() }}</span></td>
        <td>{{ $p->cout_reel ? number_format($p->cout_reel, 0, ',', ' ') : '—' }}</td>
        <td>{{ $p->delai_resolution !== null ? $p->delai_resolution.'j' : '—' }}</td>
        <td>
            <div class="act-row">
                <button class="act-btn" title="Fiche" onclick="ficheVehicule({{ $p->vehicule_id }})" style="color:var(--accent)">{!! $icEye !!}</button>
                @if($p->statut !== 'cloturee')
                <button class="act-btn" title="Statuer" onclick="statuerPanne({{ $p->id }})" style="color:var(--accent)">{!! $icWrench !!}</button>
                @endif
                <button class="act-btn" title="Modifier" onclick="editPanne({{ $p->id }})">{!! $icEdit !!}</button>
                <form method="POST" action="{{ route('parc.pannes.destroy', $p) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" class="empty-msg">Aucune panne enregistrée.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB RECETTES ==================== --}}
<div class="tab-panel" id="tab-recettes">

    {{-- ===== ALERTES JOURS MANQUANTS ===== --}}
    @if(!empty($alertesManquants))
    <div style="margin-bottom:16px">
        @foreach($alertesManquants as $am)
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:10px 14px;font-size:12.5px;color:#991b1b;margin-bottom:6px;display:flex;align-items:center;gap:8px">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span><strong>{{ $am['date_label'] }}</strong> — {{ $am['count'] }} véhicule(s) sans recette : {{ $am['vehicules'] }}</span>
            <a href="{{ route('parc.index', ['tab' => 'recettes', 'date_recette' => $am['date']]) }}" class="btn sm" style="margin-left:auto;padding:3px 10px;font-size:11px">Saisir</a>
        </div>
        @endforeach
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
            @if(!$estAujourdhui)
            <a href="{{ route('parc.index', ['tab' => 'recettes']) }}" class="btn sm sec" style="padding:4px 10px;font-size:11px">Aujourd'hui</a>
            @endif
        </form>
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

    {{-- ===== FORMULAIRE CACHÉ POUR SOUMISSION ===== --}}
    <form method="POST" action="{{ route('parc.recettes.validerJournee') }}" id="form-grille-ind" style="display:none">
        @csrf
        <input type="hidden" name="date_jour" value="{{ $dateJour }}">
        <input type="hidden" name="lignes[0][vehicule_id]" id="gi-vehicule">
        <input type="hidden" name="lignes[0][chauffeur]" id="gi-chauffeur">
        <input type="hidden" name="lignes[0][recette]" id="gi-recette">
        <input type="hidden" name="lignes[0][mode_remise]" id="gi-mode">
        <input type="hidden" name="lignes[0][motif_absence]" id="gi-motif">
        <input type="hidden" name="lignes[0][perspective]" id="gi-perspective">
        <input type="hidden" name="lignes[0][justification_valable]" id="gi-justif" value="1">
        <input type="hidden" name="lignes[0][controle_par]" value="{{ auth()->user()->name }}">
        <input type="hidden" name="lignes[0][est_repos]" id="gi-repos" value="0">
    </form>

    {{-- ===== FILTRE RECHERCHE ===== --}}
    <div style="margin-bottom:12px">
        <input type="text" id="gc-search" placeholder="Rechercher un chauffeur..." oninput="filtrerChauffeurs(this.value)" style="width:100%;max-width:360px;padding:8px 14px;border:1px solid var(--bord);border-radius:8px;font-size:13px;background:var(--carte)">
    </div>

    {{-- ===== GRILLE CHAUFFEURS ===== --}}
    <div class="gc-list" id="gc-list">
    @foreach($grilleJour as $i => $g)
    @php
        $v = $g['vehicule'];
        $deja = $g['deja_saisi'];
        $rec = $g['recette'];
        $repos = $g['est_repos'];
        $nomCh = $g['chauffeur'] ?: 'Sans chauffeur';
        $chActif = $g['chauffeur_actif'] ?? true;
        $montantAff = $deja ? number_format($rec->recette, 0, ',', ' ') . ' F' : '';
        $ecartOk = $deja && $rec->ecart >= 0;
        $sousObjectif = $deja && $rec->ecart < 0 && $rec->recette > 0;
    @endphp
    <div class="gc-item {{ $repos ? 'gc-repos' : ($deja ? 'gc-fait' : '') }}{{ !$chActif ? ' gc-inactif' : '' }}" id="gc-{{ $i }}" data-vid="{{ $v->id }}" data-repos="{{ $repos ? '1' : '0' }}" data-objectif="{{ $g['objectif'] }}" data-nom="{{ mb_strtolower($nomCh) }}">
        <div class="gc-row" onclick="toggleRecette({{ $i }})">
            <div class="gc-avatar{{ !$chActif ? ' gc-av-off' : '' }}">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="gc-info">
                <div class="gc-nom">{{ $nomCh }}@if(!$chActif) <span class="pill p-ko" style="font-size:9px;padding:1px 5px;margin-left:6px">Inactif</span>@endif</div>
                <div class="gc-sub">
                    <span>{{ $v->immatriculation }}</span>
                    <span class="cat-pill {{ $v->categorie === 'taxi' ? 'cat-taxi' : 'cat-vtc' }}" style="font-size:10px;padding:1px 6px">{{ $v->categorieLabel() }}</span>
                    <span>Obj. {{ number_format($g['objectif'], 0, ',', ' ') }} F</span>
                </div>
            </div>
            @if($repos)
            <span class="pill p-ok" style="font-size:11px">Repos</span>
            @elseif($deja)
            <div class="gc-montant {{ $ecartOk ? 'gc-ok' : 'gc-ko' }}">{{ $montantAff }}</div>
            @else
            <span class="pill p-warn" style="font-size:11px">En attente</span>
            @endif
            <svg class="gc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </div>
        <div class="gc-detail" id="gc-detail-{{ $i }}">
            @if($repos)
            <div style="padding:0 16px 12px;font-size:12px;color:var(--ok);font-weight:600">Repos automatique — recette enregistrée à 0 F</div>
            @else
            <div class="gc-form" style="grid-template-columns:1fr 1fr 1fr auto">
                <div>
                    <label>Recette (F)</label>
                    <input type="number" id="gr-rec-{{ $i }}" value="{{ $deja ? $rec->recette : '' }}" min="0" placeholder="Montant" oninput="recetteChange({{ $i }})">
                </div>
                <div>
                    <label>Mode remise</label>
                    <select id="gr-mode-{{ $i }}">
                        <option value="">—</option>
                        <option value="especes"{{ $deja && ($rec->mode_remise ?? '') === 'especes' ? ' selected' : '' }}>Espèces</option>
                        <option value="mobile_money"{{ $deja && ($rec->mode_remise ?? '') === 'mobile_money' ? ' selected' : '' }}>Mobile Money</option>
                        <option value="cheque"{{ $deja && ($rec->mode_remise ?? '') === 'cheque' ? ' selected' : '' }}>Chèque</option>
                        <option value="virement"{{ $deja && ($rec->mode_remise ?? '') === 'virement' ? ' selected' : '' }}>Virement</option>
                        <option value="autre"{{ $deja && ($rec->mode_remise ?? '') === 'autre' ? ' selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div id="gc-motif-wrap-{{ $i }}">
                    <label>Motif</label>
                    <select id="grille-motif-{{ $i }}">
                        <option value="">—</option>
                        <option value="sous_objectif"{{ $deja && ($rec->motif_absence ?? '') === 'sous_objectif' ? ' selected' : '' }}>Sous objectif</option>
                        @foreach($motifsAbsence as $ma)<option value="{{ $ma->code }}"{{ $deja && ($rec->motif_absence ?? '') === $ma->code ? ' selected' : '' }}>{{ $ma->libelle }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label>&nbsp;</label>
                    <button type="button" class="gc-btn-val {{ $deja ? 'maj' : 'nouveau' }}" onclick="validerLigne({{ $i }})">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        {{ $deja ? 'MAJ' : 'Valider' }}
                    </button>
                </div>
            </div>
            {{-- Perspective (visible si recette < objectif) --}}
            <div class="gc-perspective" id="gc-persp-wrap-{{ $i }}" style="padding:0 16px 12px;{{ ($sousObjectif) ? '' : 'display:none' }}">
                <div style="display:flex;gap:16px;align-items:center;margin-bottom:8px">
                    <span style="font-size:10.5px;color:var(--ko);font-weight:600;text-transform:uppercase;letter-spacing:.3px">Justification du déficit</span>
                    <label style="display:inline-flex;align-items:center;gap:4px;font-size:12px;cursor:pointer">
                        <input type="radio" name="justif-{{ $i }}" value="1" checked onchange="toggleJustif({{ $i }})"> Valable
                    </label>
                    <label style="display:inline-flex;align-items:center;gap:4px;font-size:12px;cursor:pointer;color:var(--ko)">
                        <input type="radio" name="justif-{{ $i }}" value="0" onchange="toggleJustif({{ $i }})"> Non valable → Recouvrement
                    </label>
                </div>
                <textarea id="gr-persp-{{ $i }}" rows="2" placeholder="Perspective de rattrapage ou justification..." style="width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;resize:vertical">{{ $deja ? ($rec->perspective ?? '') : '' }}</textarea>
                <div id="gc-recouv-warn-{{ $i }}" style="display:none;margin-top:6px;padding:6px 10px;background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;font-size:11.5px;color:#b91c1c">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Une créance sera automatiquement créée pour le déficit.
                </div>
            </div>
            @endif
        </div>
    </div>
    @endforeach
    </div>

    <div style="display:flex;gap:10px;margin-top:14px;align-items:center;flex-wrap:wrap">
        @if($tousValides)
        <span style="font-size:12px;color:var(--ok);font-weight:600">Journée complète</span>
        @else
        <span style="font-size:12px;color:var(--ko)">{{ $nbTotal - $nbSaisis }} chauffeur(s) en attente</span>
        @endif
        <button type="button" class="btn sm sec" onclick="document.getElementById('dlg-histo-recettes').showModal()" style="margin-left:auto">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Historique
        </button>
        <button type="button" class="btn sm sec" onclick="document.getElementById('dlg-recette').showModal()">{!! $icPlus !!} Saisie individuelle</button>
    </div>

    {{-- ===== DIALOG HISTORIQUE ===== --}}
    <dialog id="dlg-histo-recettes" style="width:95vw;max-width:1000px;border:none;border-radius:14px;padding:24px;box-shadow:0 8px 30px rgba(0,0,0,.15)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <h3 style="font-size:15px;font-weight:700;color:var(--bleu);margin:0">Historique des recettes</h3>
            <button onclick="this.closest('dialog').close()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--gris)">&times;</button>
        </div>
        <div style="overflow-x:auto;max-height:65vh;overflow-y:auto">
        <table>
        <thead><tr><th>Date</th><th>Chauffeur</th><th>Véhicule</th><th>Recette</th><th>Objectif</th><th>Écart</th><th>Mode</th><th>Motif</th><th></th></tr></thead>
        <tbody>
        @forelse($recettes as $rec)
        <tr>
            <td>{{ $rec->date->format('d/m/Y') }}</td>
            <td><strong>{{ $rec->chauffeur ?? '—' }}</strong></td>
            <td>{{ $rec->vehicule->immatriculation ?? '?' }}</td>
            <td style="font-weight:600">{{ number_format($rec->recette, 0, ',', ' ') }}</td>
            <td>{{ number_format($rec->objectif, 0, ',', ' ') }}</td>
            <td style="color:{{ $rec->ecart >= 0 ? 'var(--ok)' : 'var(--ko)' }};font-weight:600">{{ ($rec->ecart >= 0 ? '+' : '') . number_format($rec->ecart, 0, ',', ' ') }}</td>
            <td>{{ $rec->mode_remise ? ucfirst(str_replace('_', ' ', $rec->mode_remise)) : '—' }}</td>
            <td>@if($rec->motif_absence)<span class="pill p-warn">{{ $rec->motif_absence }}</span>@else — @endif</td>
            <td>
                <div class="act-row">
                    <button type="button" class="act-btn" title="Modifier" onclick="editRecette({{ $rec->id }})">{!! $icEdit !!}</button>
                    <form method="POST" action="{{ route('parc.recettes.destroy', $rec) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                        <button class="act-btn del">{!! $icDel !!}</button>
                    </form>
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
</div>

{{-- ==================== TAB MAINTENANCE ==================== --}}
<div class="tab-panel" id="tab-maint">
    <div class="panel-head">
        <h3>{!! $icCalCheck !!} Maintenance @if($kpi['echeances_retard']>0)<span class="pill p-ko">{{ $kpi['echeances_retard'] }} en retard</span>@endif</h3>
        <button class="btn sm" onclick="document.getElementById('dlg-maint').showModal()">{!! $icPlus !!} Échéance</button>
    </div>
    <div class="panneau" style="overflow-x:auto">
    <table>
    <thead><tr><th>Véhicule</th><th>Type</th><th>Dernière</th><th>Prochaine</th><th>Jours</th><th>Alerte</th><th>Observations</th><th></th></tr></thead>
    <tbody>
    @forelse($maintenances as $m)
    @php $al = $m->alerte(); $jr = $m->joursRestants(); @endphp
    <tr>
        <td><strong>{{ $m->vehicule->immatriculation ?? '?' }}</strong></td>
        <td>{{ $m->typeLabel() }}</td>
        <td>{{ $m->derniere_realisation ? $m->derniere_realisation->format('d/m/Y') : '—' }}</td>
        <td>{{ $m->prochaine_echeance ? $m->prochaine_echeance->format('d/m/Y') : '—' }}</td>
        <td style="font-weight:600;color:{{ $al === 'en_retard' ? 'var(--ko)' : ($al === 'proche' ? 'var(--warn)' : 'var(--ok)') }}">{{ $jr !== null ? $jr.'j' : '—' }}</td>
        <td><span class="pill {{ $al === 'en_retard' ? 'p-ko' : ($al === 'proche' ? 'p-warn' : 'p-ok') }}">{{ $al === 'en_retard' ? 'En retard' : ($al === 'proche' ? 'Proche' : 'OK') }}</span></td>
        <td style="max-width:150px">{{ Str::limit($m->observations, 40) }}</td>
        <td>
            <div class="act-row">
                <button class="act-btn" title="Modifier" onclick="editMaint({{ $m->id }})">{!! $icEdit !!}</button>
                <form method="POST" action="{{ route('parc.maintenances.destroy', $m) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="8" class="empty-msg">Aucune échéance enregistrée.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB PÉNALITÉS ==================== --}}
<div class="tab-panel" id="tab-penal">
    <div class="panel-head">
        <h3>{!! $icAlert !!} Pénalités @if($kpi['penalites_non_payees']>0)<span class="pill p-ko">{{ $kpi['penalites_non_payees'] }} impayées</span>@endif</h3>
        <button class="btn sm" onclick="document.getElementById('dlg-penal').showModal()">{!! $icPlus !!} Pénalité</button>
    </div>
    <div class="panneau" style="overflow-x:auto">
    <table>
    <thead><tr><th>Véhicule</th><th>Date</th><th>Type</th><th>Chauffeur</th><th>Lieu</th><th>Montant</th><th>Statut</th><th>Paiement</th><th></th></tr></thead>
    <tbody>
    @forelse($penalites as $pen)
    <tr>
        <td><strong>{{ $pen->vehicule->immatriculation ?? '?' }}</strong></td>
        <td>{{ $pen->date_infraction->format('d/m/Y') }}</td>
        <td>{{ $pen->typeLabel() }}</td>
        <td>{{ $pen->chauffeur ?? '—' }}</td>
        <td>{{ Str::limit($pen->lieu, 30) ?? '—' }}</td>
        <td style="font-weight:600">{{ number_format($pen->montant, 0, ',', ' ') }}</td>
        <td><span class="pill {{ $pen->statut === 'payee' ? 'p-ok' : ($pen->statut === 'contestee' ? 'p-warn' : 'p-ko') }}">{{ $pen->statutLabel() }}</span></td>
        <td>{{ $pen->date_paiement ? $pen->date_paiement->format('d/m/Y') : '—' }}</td>
        <td>
            <div class="act-row">
                <button class="act-btn" title="Fiche" onclick="ficheVehicule({{ $pen->vehicule_id }})" style="color:var(--accent)">{!! $icEye !!}</button>
                @if($pen->statut !== 'payee')
                <button class="act-btn" title="Statuer" onclick="statuerPenal({{ $pen->id }})" style="color:var(--accent)">{!! $icWrench !!}</button>
                @endif
                <button class="act-btn" title="Modifier" onclick="editPenal({{ $pen->id }})">{!! $icEdit !!}</button>
                <form method="POST" action="{{ route('parc.penalites.destroy', $pen) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="9" class="empty-msg">Aucune pénalité enregistrée.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== TAB RECOUVREMENTS ==================== --}}
<div class="tab-panel" id="tab-recouv">
    <div class="panneau">
    <h3 style="display:flex;align-items:center;gap:8px;margin-bottom:14px">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="var(--bleu)" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Recouvrements en cours
    </h3>

    @php $recActifs = ($recouvrements ?? collect())->where('statut', 'actif'); @endphp

    @if($recActifs->count() > 0)
    {{-- KPIs recouvrements --}}
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
        <div class="kpi"><div class="lbl">Créances actives</div><div class="val" style="color:var(--ko)">{{ $recActifs->count() }}</div></div>
        <div class="kpi"><div class="lbl">Total à recouvrer</div><div class="val" style="color:var(--ko)">{{ number_format($recActifs->sum('solde'), 0, ',', ' ') }} F</div></div>
        <div class="kpi"><div class="lbl">Déjà recouvré</div><div class="val" style="color:var(--ok)">{{ number_format($recActifs->sum('montant_recouvre'), 0, ',', ' ') }} F</div></div>
    </div>

    <div style="overflow-x:auto">
    <table>
    <thead><tr><th>Date</th><th>Chauffeur</th><th>Véhicule</th><th>Objectif</th><th>Versé</th><th>Déficit</th><th>Recouvré</th><th>Solde</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    @foreach($recActifs as $rc)
    <tr>
        <td>{{ $rc->date_recette->format('d/m/Y') }}</td>
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
                @if($rc->montant_recouvre == 0)
                <form method="POST" action="{{ route('parc.recouvrements.annuler', $rc) }}" onsubmit="return confirm('Annuler ce recouvrement ?')">@csrf @method('PATCH')
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
    <tr><td colspan="10" style="padding:0">
        <div style="background:#f0fdf4;padding:6px 16px;font-size:11.5px">
            <strong style="color:var(--ok)">Versements :</strong>
            @foreach($rc->versements as $vs)
            <span style="margin-left:8px">{{ $vs->date_versement->format('d/m') }} — {{ number_format($vs->montant, 0, ',', ' ') }} F ({{ $vs->mode_paiement ? ucfirst(str_replace('_',' ',$vs->mode_paiement)) : '—' }})</span>{{ !$loop->last ? ' |' : '' }}
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
        <div style="overflow-x:auto;margin-top:8px">
        <table>
        <thead><tr><th>Date</th><th>Chauffeur</th><th>Déficit</th><th>Recouvré</th><th>Statut</th></tr></thead>
        <tbody>
        @foreach($recSoldes as $rs)
        <tr style="opacity:.7">
            <td>{{ $rs->date_recette->format('d/m/Y') }}</td>
            <td>{{ $rs->chauffeur_nom ?? '—' }}</td>
            <td>{{ number_format($rs->deficit, 0, ',', ' ') }}</td>
            <td>{{ number_format($rs->montant_recouvre, 0, ',', ' ') }}</td>
            <td><span class="pill {{ $rs->statut === 'solde' ? 'p-ok' : 'p-gris' }}">{{ $rs->statut === 'solde' ? 'Soldé' : 'Annulé' }}</span></td>
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
                <div>
                    <label>Mode de paiement</label>
                    <select name="mode_paiement">
                        <option value="">—</option>
                        <option value="especes">Espèces</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="cheque">Chèque</option>
                        <option value="virement">Virement</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div>
                    <label>Observations</label>
                    <textarea name="observations" rows="2" placeholder="Notes..."></textarea>
                </div>
                <button type="submit" class="btn">Enregistrer le versement</button>
            </div>
        </form>
    </dialog>
</div>

{{-- ==================== TAB CHAUFFEURS ==================== --}}
<div class="tab-panel" id="tab-chauffeurs">
    <div class="panel-head">
        <h3>{!! $icUser !!} Chauffeurs <span class="pill p-cours">{{ $chauffeurs->count() }}</span></h3>
        <button class="btn sm" onclick="document.getElementById('dlg-chauffeur').showModal()">{!! $icPlus !!} Chauffeur</button>
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
        <td>@if($affectations->isEmpty()) — @else @foreach($affectations as $aff)<span style="display:inline-flex;align-items:center;gap:3px;margin-right:6px">{{ $aff->immatriculation }}<form method="POST" action="{{ route('parc.chauffeurs.detacher', [$aff, $ch]) }}" style="display:inline" onsubmit="return confirm('Détacher ce véhicule ?')">@csrf @method('DELETE')<button class="act-btn del" title="Détacher" style="padding:1px 3px"><svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button></form></span>@endforeach @endif</td>
        <td>{{ $affectations->map(fn($v) => ucfirst($v->pivot->role))->join(', ') ?: '—' }}</td>
        <td><span class="pill {{ $ch->statut === 'actif' ? 'p-ok' : 'p-gris' }}">{{ ucfirst($ch->statut) }}</span></td>
        <td>
            <div class="act-row">
                <button class="act-btn" title="Affecter" onclick="affecterChauffeur({{ $ch->id }})" style="color:var(--accent)">{!! $icCar !!}</button>
                <button class="act-btn" title="Modifier" onclick="editChauffeur({{ $ch->id }})">{!! $icEdit !!}</button>
                @if(auth()->user()->estPilote())
                <form method="POST" action="{{ route('parc.chauffeurs.destroy', $ch) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                    <button class="act-btn del">{!! $icDel !!}</button>
                </form>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr><td colspan="7" class="empty-msg">Aucun chauffeur enregistré.</td></tr>
    @endforelse
    </tbody>
    </table>
    </div>
</div>

{{-- ==================== DIALOGS ==================== --}}

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
        <div><label>Coût réel (FCFA)</label><input type="number" name="cout_reel" id="s-cout" min="0"></div>
        <div><label>Statut <span class="req">*</span></label>
            <select name="statut" id="s-statut" required>
                <option value="declaree">Déclarée</option><option value="en_diagnostic">En diagnostic</option><option value="en_intervention">En intervention</option><option value="cloturee">Clôturée</option>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div><label>Début intervention</label><input type="date" name="date_debut_intervention" id="s-ddi"></div>
        <div><label>Date clôture</label><input type="date" name="date_cloture" id="s-dcl"></div>
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
<form id="form-recette" method="POST" action="{{ route('parc.recettes.store') }}">
    @csrf <div id="recette-method"></div>
    <div class="dlg-section">Versement</div>
    <div class="form-row3">
        <div><label>Date <span class="req">*</span></label><input type="date" name="date" id="r-date" value="{{ date('Y-m-d') }}" required></div>
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="r-vehic" required>
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }} ({{ $v->categorieLabel() }})</option>@endforeach
            </select>
        </div>
        <div><label>Chauffeur</label>
            <select name="chauffeur" id="r-chauff">
                <option value="">— Chauffeur —</option>
                @foreach($chauffeurs->where('statut','actif') as $ch)<option value="{{ $ch->nomComplet() }}">{{ $ch->nomComplet() }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="form-row3">
        <div><label>Recette (FCFA) <span class="req">*</span></label><input type="number" name="recette" id="r-montant" min="0" required></div>
        <div><label>Mode de remise</label>
            <select name="mode_remise" id="r-mode">
                <option value="">—</option><option value="especes">Espèces</option><option value="mobile_money">Mobile Money</option><option value="cheque">Chèque</option><option value="virement">Virement</option><option value="autre">Autre</option>
            </select>
        </div>
        <div><label>Contrôlé par</label>
            <select name="controle_par" id="r-ctrl">
                <option value="">— Choisir —</option>
                @foreach($collaborateurs as $u)<option value="{{ $u->name }}">{{ $u->name }}</option>@endforeach
            </select>
        </div>
    </div>
    <div id="motif-zone" style="display:none;margin-top:10px;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:10px 12px">
        <label style="color:#92400e;font-weight:700;font-size:11px">JUSTIFICATION OBLIGATOIRE (recette = 0)</label>
        <select name="motif_absence" id="r-motif" style="margin-top:4px">
            <option value="">— Choisir un motif —</option>
            @foreach($motifsAbsence as $ma)<option value="{{ $ma->code }}">{{ $ma->libelle }}</option>@endforeach
        </select>
    </div>
    <div><label>Observations</label><textarea name="observations" id="r-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
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
            <select name="type_echeance" id="m-type" required>
                @foreach($typesMaintenance as $tm)<option value="{{ $tm->code }}">{{ $tm->libelle }}</option>@endforeach
            </select>
        </div>
    </div>
    <div class="form-row">
        <div><label>Dernière réalisation</label><input type="date" name="derniere_realisation" id="m-dr"></div>
        <div><label>Prochaine échéance</label><input type="date" name="prochaine_echeance" id="m-pe"></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="m-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
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
    <input type="hidden" name="observations" id="pe-obs" value="">
    <div class="form-row3">
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="pe-vehic" required>
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
        <div><label>Chauffeur</label>
            <select name="chauffeur" id="pe-chauff">
                <option value="">— Chauffeur —</option>
                @foreach($chauffeurs->where('statut','actif') as $ch)<option value="{{ $ch->nomComplet() }}">{{ $ch->nomComplet() }}</option>@endforeach
            </select>
        </div>
        <div><label>Lieu</label>
            <select name="lieu" id="pe-lieu">
                <option value="">— Lieu —</option>
                @foreach($lieuxSugg as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
            </select>
        </div>
        <div><label>Montant (FCFA) <span class="req">*</span></label><input type="number" name="montant" id="pe-mont" min="0" required></div>
    </div>
    <div><label>Description</label><textarea name="description" id="pe-desc" rows="2" placeholder="Décrivez l'infraction"></textarea></div>
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
                <option value="non_payee">Non payée</option><option value="payee">Payée</option><option value="contestee">Contestée</option>
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
<dialog id="dlg-fiche" style="width:620px">
<h3>{!! $icEye !!} Fiche véhicule</h3>
<div id="fiche-contenu" style="font-size:13px;line-height:1.7">
    <p style="text-align:center;color:var(--gris)">Chargement...</p>
</div>
<div style="display:flex;justify-content:flex-end;margin-top:16px">
    <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Fermer</button>
</div>
</dialog>

@endsection

@php
$_vData = [];
foreach($vehicules as $_v) {
    $_vChauffs = $_v->chauffeurs->where('pivot.actif', true)->map(fn($c) => [
        'id' => $c->id, 'role' => $c->pivot->role,
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
        'date_declaration' => $_p->date_declaration ? $_p->date_declaration->format('Y-m-d') : '',
        'description' => $_p->description,
        'urgence' => $_p->urgence,
        'diagnostic' => $_p->diagnostic,
        'decision' => $_p->decision,
        'date_debut_intervention' => $_p->date_debut_intervention ? $_p->date_debut_intervention->format('Y-m-d') : '',
        'date_cloture' => $_p->date_cloture ? $_p->date_cloture->format('Y-m-d') : '',
        'cout_reel' => $_p->cout_reel,
        'statut' => $_p->statut,
    ];
}
$_rData = [];
foreach($recettes as $_r) {
    $_rData[$_r->id] = [
        'date' => $_r->date->format('Y-m-d'),
        'vehicule_id' => $_r->vehicule_id,
        'chauffeur' => $_r->chauffeur,
        'recette' => $_r->recette,
        'mode_remise' => $_r->mode_remise,
        'controle_par' => $_r->controle_par,
        'observations' => $_r->observations,
        'motif_absence' => $_r->motif_absence,
    ];
}
$_mData = [];
foreach($maintenances as $_m) {
    $_mData[$_m->id] = [
        'vehicule_id' => $_m->vehicule_id,
        'type_echeance' => $_m->type_echeance,
        'derniere_realisation' => $_m->derniere_realisation ? $_m->derniere_realisation->format('Y-m-d') : '',
        'prochaine_echeance' => $_m->prochaine_echeance ? $_m->prochaine_echeance->format('Y-m-d') : '',
        'observations' => $_m->observations,
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
$_vLabels = [];
foreach($vehicules as $_v) {
    $_vLabels[$_v->id] = $_v->immatriculation . ' (' . $_v->categorieLabel() . ')';
}
@endphp

@push('scripts')
<script>
function changerOnglet(nom){
    document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('actif')});
    document.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('actif')});
    document.getElementById('tab-'+nom).classList.add('actif');
    var btns=document.querySelectorAll('.tab-btn');
    var noms=['vehicules','pannes','recettes','maint','penal','chauffeurs'];
    var idx=noms.indexOf(nom);
    if(idx>=0 && btns[idx]) btns[idx].classList.add('actif');
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
var recettesData = @json($_rData);
var maintData = @json($_mData);
var penalData = @json($_peData);
var chauffeurData = @json($_chData);
var vLabels = @json($_vLabels);

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
    document.getElementById('dlg-statuer').showModal();
}

function toggleMotif(){
    var m=document.getElementById('r-montant');
    var z=document.getElementById('motif-zone');
    z.style.display=(parseInt(m.value||1)===0)?'block':'none';
}
document.getElementById('r-montant').addEventListener('input',toggleMotif);

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

// Filtre recherche chauffeur
function filtrerChauffeurs(q){
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
    var objectif=parseFloat(item.getAttribute('data-objectif'))||0;
    var montant=parseFloat(rec?rec.value:0)||0;

    if(montant>0 && montant<objectif){
        if(motifWrap) motifWrap.style.display='';
        if(perspWrap) perspWrap.style.display='';
    } else if(montant===0 || rec.value===''){
        if(motifWrap) motifWrap.style.display='';
        if(perspWrap) perspWrap.style.display='none';
    } else {
        if(perspWrap) perspWrap.style.display='none';
    }
}

function validerLigne(idx){
    var item=document.getElementById('gc-'+idx);
    if(!item) return;
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
    if(!repos && montant===0 && motif && !motif.value){alert('Motif obligatoire lorsque la recette est à 0.');return;}
    if(!repos && montant>0 && montant<objectif && motif && !motif.value){alert('Recette inférieure à l\'objectif : motif obligatoire.');return;}

    document.getElementById('gi-vehicule').value=vid;
    document.getElementById('gi-chauffeur').value=chauffeur;
    document.getElementById('gi-recette').value=repos?0:montant;
    document.getElementById('gi-mode').value=mode?mode.value:'';
    document.getElementById('gi-motif').value=motif?motif.value:'';
    document.getElementById('gi-perspective').value=persp?persp.value:'';
    // Justification valable (radio)
    var justifRadio=item.querySelector('input[name="justif-'+idx+'"]:checked');
    document.getElementById('gi-justif').value=justifRadio?justifRadio.value:'1';
    document.getElementById('gi-repos').value=repos?'1':'0';
    document.getElementById('form-grille-ind').submit();
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
    var d=recettesData[id]; if(!d)return;
    document.getElementById('dlg-recette-titre').innerHTML='{!! $icWallet !!} Modifier la recette';
    document.getElementById('form-recette').action='{{ url("parc/recettes") }}/'+id;
    document.getElementById('recette-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('r-date').value=d.date;
    document.getElementById('r-vehic').value=d.vehicule_id;
    document.getElementById('r-chauff').value=d.chauffeur||'';
    document.getElementById('r-montant').value=d.recette;
    document.getElementById('r-mode').value=d.mode_remise||'';
    document.getElementById('r-ctrl').value=d.controle_par||'';
    document.getElementById('r-obs').value=d.observations||'';
    document.getElementById('r-motif').value=d.motif_absence||'';
    toggleMotif();
    document.getElementById('dlg-recette').showModal();
}

function editMaint(id){
    var d=maintData[id]; if(!d)return;
    document.getElementById('dlg-maint-titre').innerHTML='{!! $icCalCheck !!} Modifier l\'échéance';
    document.getElementById('form-maint').action='{{ url("parc/maintenances") }}/'+id;
    document.getElementById('maint-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('m-vehic').value=d.vehicule_id;
    document.getElementById('m-type').value=d.type_echeance;
    document.getElementById('m-dr').value=d.derniere_realisation||'';
    document.getElementById('m-pe').value=d.prochaine_echeance||'';
    document.getElementById('m-obs').value=d.observations||'';
    document.getElementById('dlg-maint').showModal();
}

function editPenal(id){
    var d=penalData[id]; if(!d)return;
    document.getElementById('dlg-penal-titre').innerHTML='{!! $icAlert !!} Modifier la déclaration';
    document.getElementById('form-penal').action='{{ url("parc/penalites") }}/'+id;
    document.getElementById('penal-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('pe-vehic').value=d.vehicule_id;
    document.getElementById('pe-date').value=d.date_infraction;
    document.getElementById('pe-type').value=d.type;
    document.getElementById('pe-chauff').value=d.chauffeur||'';
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
    fetch('{{ url("parc/vehicules") }}/'+vehiculeId+'/fiche')
        .then(function(r){return r.json()})
        .then(function(d){
            var html='<div style="background:#f8fafc;border:1px solid var(--bord);border-radius:8px;padding:14px;margin-bottom:12px">';
            html+='<div style="font-size:18px;font-weight:700;color:var(--bleu)">'+d.immatriculation+'</div>';
            html+='<div style="font-size:12px;color:var(--gris);margin-top:4px">'+d.marque+' '+d.modele+' · <span class="cat-pill cat-'+d.categorie+'">'+d.categorie_label+'</span> · <span class="pill '+(d.statut==='en_service'?'p-ok':(d.statut==='immobilise'?'p-warn':'p-ko'))+'">'+d.statut_label+'</span></div>';
            if(d.kilometrage) html+='<div style="margin-top:6px;font-size:12px">Kilométrage : <strong>'+new Intl.NumberFormat('fr-FR').format(d.kilometrage)+' km</strong></div>';
            html+='</div>';
            if(d.chauffeurs && d.chauffeurs.length>0){
                html+='<div class="dlg-section" style="margin-top:8px">Chauffeurs affectés</div><ul style="margin:0 0 10px;padding-left:18px;font-size:12.5px">';
                d.chauffeurs.forEach(function(c){html+='<li>'+c.nom+' <span style="color:var(--gris)">('+c.role+')</span></li>'});
                html+='</ul>';
            }
            html+='<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px">';
            html+='<div style="background:#f0fdf4;border-radius:8px;padding:10px;text-align:center"><div style="font-size:18px;font-weight:700;color:var(--ok)">'+d.pannes_total+'</div><div style="font-size:11px;color:var(--gris)">Pannes ('+d.pannes_ouvertes+' ouvertes)</div></div>';
            html+='<div style="background:#dbeafe;border-radius:8px;padding:10px;text-align:center"><div style="font-size:18px;font-weight:700;color:var(--accent)">'+new Intl.NumberFormat('fr-FR').format(d.recette_mois)+' F</div><div style="font-size:11px;color:var(--gris)">Recettes ce mois</div></div>';
            html+='<div style="background:#fef3c7;border-radius:8px;padding:10px;text-align:center"><div style="font-size:18px;font-weight:700;color:#b45309">'+d.echeances_retard+'</div><div style="font-size:11px;color:var(--gris)">Échéances en retard</div></div>';
            html+='<div style="background:#fee2e2;border-radius:8px;padding:10px;text-align:center"><div style="font-size:18px;font-weight:700;color:var(--ko)">'+d.penalites_impayees+'</div><div style="font-size:11px;color:var(--gris)">Pénalités impayées</div></div>';
            html+='</div>';
            contenu.innerHTML=html;
        })
        .catch(function(){contenu.innerHTML='<p style="color:var(--ko)">Erreur de chargement.</p>'});
}

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
        var titles={'dlg-vehicule':'{!! $icCar !!} Nouveau véhicule','dlg-panne':'{!! $icWrench !!} Déclarer une panne','dlg-recette':'{!! $icWallet !!} Nouvelle recette','dlg-maint':'{!! $icCalCheck !!} Nouvelle échéance','dlg-penal':'{!! $icAlert !!} Déclarer une pénalité','dlg-chauffeur':'{!! $icUser !!} Nouveau chauffeur'};
        var t=titles[dlg.id];if(t){var h=dlg.querySelector('h3[id]');if(h)h.innerHTML=t;}
        if(dlg.id==='dlg-vehicule'){if(form)form.action='{{ route("parc.vehicules.store") }}';document.getElementById('v-chauffeurs-list').innerHTML='';chauffRowIdx=0;}
        if(dlg.id==='dlg-panne'&&form)form.action='{{ route("parc.pannes.store") }}';
        if(dlg.id==='dlg-recette'&&form)form.action='{{ route("parc.recettes.store") }}';
        if(dlg.id==='dlg-maint'&&form)form.action='{{ route("parc.maintenances.store") }}';
        if(dlg.id==='dlg-penal'&&form)form.action='{{ route("parc.penalites.store") }}';
        if(dlg.id==='dlg-chauffeur'&&form)form.action='{{ route("parc.chauffeurs.store") }}';
    });
});
</script>
@endpush
