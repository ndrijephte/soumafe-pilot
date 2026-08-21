@extends('layouts.app')
@section('titre', 'Parc automobile')
@push('styles')
<style>
.kpi-row{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:12px;margin-bottom:18px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;display:flex;align-items:center;gap:14px}
.kpi-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex:none}
.kpi-icon svg{width:20px;height:20px}
.kpi-body .val{font-size:22px;font-weight:700;line-height:1.2}
.kpi-body .lab{font-size:11px;color:var(--gris);margin-top:1px}
.kpi-ok .kpi-icon{background:#dcfce7;color:var(--ok)}.kpi-ok .val{color:var(--ok)}
.kpi-warn .kpi-icon{background:#fef3c7;color:#b45309}.kpi-warn .val{color:#b45309}
.kpi-ko .kpi-icon{background:#fee2e2;color:var(--ko)}.kpi-ko .val{color:var(--ko)}
.kpi-bleu .kpi-icon{background:#dbeafe;color:var(--accent)}.kpi-bleu .val{color:var(--bleu)}
.kpi-gris .kpi-icon{background:#f1f5f9;color:#475569}.kpi-gris .val{color:#334155}
.tabs{display:flex;gap:0;border-bottom:2px solid var(--bord);margin-bottom:16px}
.tabs a{padding:10px 16px;font-size:13px;font-weight:600;color:var(--gris);text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;display:flex;align-items:center;gap:6px}
.tabs a:hover{color:var(--bleu)}
.tabs a.actif{color:var(--accent);border-bottom-color:var(--accent)}
.tabs a svg{width:15px;height:15px}
.tab-badge{background:var(--accent);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:8px}
.tab-badge.ko{background:var(--ko)}
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
.filtre-bar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.filtre-bar select{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.urgence-faible{color:#475569}.urgence-moyenne{color:var(--warn)}.urgence-haute{color:#ea580c;font-weight:700}.urgence-critique{color:var(--ko);font-weight:700}
.act-row{display:flex;gap:4px;align-items:center}
.act-btn{background:none;border:none;cursor:pointer;padding:3px;border-radius:4px;color:var(--gris)}
.act-btn:hover{background:#f1f5f9;color:var(--bleu)}
.act-btn.del{color:var(--ko)}
.act-btn.del:hover{background:#fef2f2}
.pagination-row{display:flex;justify-content:center;gap:4px;margin-top:12px}
.pagination-row a,.pagination-row span{padding:4px 10px;border:1px solid var(--bord);border-radius:6px;font-size:12px;text-decoration:none;color:var(--gris)}
.pagination-row span.current{background:var(--accent);color:#fff;border-color:var(--accent)}
.param-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.param-item{display:flex;flex-direction:column;gap:2px}
.param-item label{font-size:12px;color:var(--gris);font-weight:600}
.param-item input{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;text-align:right}
.header-actions{display:flex;gap:8px;align-items:center}
@media(max-width:900px){.kpi-row{grid-template-columns:repeat(2,1fr)}.form-row,.form-row3,.param-grid{grid-template-columns:1fr}.tabs{overflow-x:auto}.tabs a{white-space:nowrap;font-size:12px;padding:8px 10px}table{display:block;overflow-x:auto}}
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
$icGear = $ic('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>');
$icEdit = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>';
$icDel = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>';
$icPlus = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;flex-wrap:wrap;gap:8px">
    <h2 style="display:flex;align-items:center;gap:8px">{!! $icCar !!} Parc automobile</h2>
    <div class="header-actions">
        @if(auth()->user()->estPilote())
        <button class="btn sec" onclick="document.getElementById('dlg-params').showModal()" style="font-size:12px">{!! $icGear !!} <span style="display:inline">Paramètres</span></button>
        @endif
        @if($onglet === 'vehicules')
        <button class="btn" onclick="document.getElementById('dlg-vehicule').showModal()">{!! $icPlus !!} Véhicule</button>
        @elseif($onglet === 'pannes')
        <button class="btn" onclick="document.getElementById('dlg-panne').showModal()">{!! $icPlus !!} Panne</button>
        @elseif($onglet === 'recettes')
        <button class="btn" onclick="document.getElementById('dlg-recette').showModal()">{!! $icPlus !!} Recette</button>
        @elseif($onglet === 'maintenance')
        <button class="btn" onclick="document.getElementById('dlg-maint').showModal()">{!! $icPlus !!} Échéance</button>
        @elseif($onglet === 'penalites')
        <button class="btn" onclick="document.getElementById('dlg-penal').showModal()">{!! $icPlus !!} Pénalité</button>
        @endif
    </div>
</div>
<div class="crumb">Opérations &rsaquo; Parc automobile</div>

@if(session('succes'))<div class="alerte ok">{!! $ic('<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',16) !!} {{ session('succes') }}</div>@endif

{{-- KPI --}}
<div class="kpi-row">
    <div class="kpi kpi-bleu">
        <div class="kpi-icon">{!! $ic('<rect x="1" y="6" width="22" height="12" rx="3"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',20) !!}</div>
        <div class="kpi-body"><div class="val">{{ $kpi['actifs'] }}<span style="font-size:13px;color:var(--gris);font-weight:400">/{{ $kpi['total'] }}</span></div><div class="lab">En service · {{ $kpi['taxis'] }}T {{ $kpi['vtcs'] }}V</div></div>
    </div>
    <div class="kpi {{ $kpi['pannes_ouvertes'] > 0 ? 'kpi-warn' : 'kpi-gris' }}">
        <div class="kpi-icon">{!! $ic('<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>',20) !!}</div>
        <div class="kpi-body"><div class="val">{{ $kpi['pannes_ouvertes'] }}</div><div class="lab">Pannes ouvertes</div></div>
    </div>
    <div class="kpi kpi-gris">
        <div class="kpi-icon">{!! $ic('<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>',20) !!}</div>
        <div class="kpi-body"><div class="val">{{ number_format($kpi['cout_mois'], 0, ',', ' ') }}</div><div class="lab">Coût pannes ce mois</div></div>
    </div>
    <div class="kpi {{ $kpi['taux_recette'] >= 100 ? 'kpi-ok' : ($kpi['taux_recette'] >= 80 ? 'kpi-warn' : 'kpi-ko') }}">
        <div class="kpi-icon">{!! $ic('<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M16 12h2"/>',20) !!}</div>
        <div class="kpi-body"><div class="val">{{ $kpi['taux_recette'] }}%</div><div class="lab">Taux recette / objectif</div></div>
    </div>
    <div class="kpi {{ $kpi['echeances_retard'] > 0 ? 'kpi-ko' : 'kpi-ok' }}">
        <div class="kpi-icon">{!! $ic('<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/>',20) !!}</div>
        <div class="kpi-body"><div class="val">{{ $kpi['echeances_retard'] }}</div><div class="lab">Échéances en retard</div></div>
    </div>
    <div class="kpi {{ $kpi['penalites_non_payees'] > 0 ? 'kpi-ko' : 'kpi-gris' }}">
        <div class="kpi-icon">{!! $ic('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',20) !!}</div>
        <div class="kpi-body"><div class="val">{{ $kpi['penalites_non_payees'] }}</div><div class="lab">Pénalités impayées</div></div>
    </div>
</div>

{{-- Onglets --}}
<div class="tabs">
    <a href="{{ route('parc.index', ['onglet' => 'vehicules']) }}" class="{{ $onglet === 'vehicules' ? 'actif' : '' }}">{!! $icCar !!} Véhicules <span class="tab-badge">{{ $vehicules->count() }}</span></a>
    <a href="{{ route('parc.index', ['onglet' => 'pannes']) }}" class="{{ $onglet === 'pannes' ? 'actif' : '' }}">{!! $icWrench !!} Pannes @if($kpi['pannes_ouvertes']>0)<span class="tab-badge ko">{{ $kpi['pannes_ouvertes'] }}</span>@endif</a>
    <a href="{{ route('parc.index', ['onglet' => 'recettes']) }}" class="{{ $onglet === 'recettes' ? 'actif' : '' }}">{!! $icWallet !!} Recettes</a>
    <a href="{{ route('parc.index', ['onglet' => 'maintenance']) }}" class="{{ $onglet === 'maintenance' ? 'actif' : '' }}">{!! $icCalCheck !!} Maintenance @if($kpi['echeances_retard']>0)<span class="tab-badge ko">{{ $kpi['echeances_retard'] }}</span>@endif</a>
    <a href="{{ route('parc.index', ['onglet' => 'penalites']) }}" class="{{ $onglet === 'penalites' ? 'actif' : '' }}">{!! $icAlert !!} Pénalités @if($kpi['penalites_non_payees']>0)<span class="tab-badge ko">{{ $kpi['penalites_non_payees'] }}</span>@endif</a>
</div>

{{-- ==================== ONGLET VÉHICULES ==================== --}}
@if($onglet === 'vehicules')
<div class="carte" style="overflow-x:auto">
<table>
<thead><tr><th>Immatriculation</th><th>Cat.</th><th>Marque / Modèle</th><th>Chauffeur</th><th>Km</th><th>Mise en service</th><th>Obj. recette</th><th>Statut</th><th></th></tr></thead>
<tbody>
@forelse($vehicules as $v)
<tr>
    <td><strong>{{ $v->immatriculation }}</strong></td>
    <td><span class="cat-pill cat-{{ $v->categorie }}">{{ $v->categorieLabel() }}</span></td>
    <td>{{ $v->marque }} {{ $v->modele }}</td>
    <td>{{ $v->chauffeur_affecte ?? '—' }}</td>
    <td>{{ $v->kilometrage ? number_format($v->kilometrage, 0, ',', ' ') : '—' }}</td>
    <td>{{ $v->date_mise_en_service ? $v->date_mise_en_service->format('d/m/Y') : '—' }}</td>
    <td>{{ $v->objectif_recette ? number_format($v->objectif_recette, 0, ',', ' ') : '—' }}</td>
    <td><span class="pill {{ $v->statut === 'en_service' ? 'p-ok' : ($v->statut === 'immobilise' ? 'p-warn' : 'p-ko') }}">{{ $v->statutLabel() }}</span></td>
    <td>
        <div class="act-row">
            <button class="act-btn" title="Modifier" onclick="editVehicule({{ $v->id }})">{!! $icEdit !!}</button>
            @if(auth()->user()->estPilote())
            <form method="POST" action="{{ route('parc.vehicules.destroy', $v) }}" onsubmit="return confirm('Supprimer ce véhicule ?')">@csrf @method('DELETE')
                <button class="act-btn del" title="Supprimer">{!! $icDel !!}</button>
            </form>
            @endif
        </div>
    </td>
</tr>
@empty
<tr><td colspan="9" style="text-align:center;color:var(--gris);padding:30px">Aucun véhicule enregistré.</td></tr>
@endforelse
</tbody>
</table>
</div>

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
        <div><label>Marque</label><input name="marque" id="v-marque"></div>
        <div><label>Modèle</label><input name="modele" id="v-modele"></div>
    </div>
    <div class="dlg-section">Affectation</div>
    <div class="form-row">
        <div><label>Chauffeur affecté</label><input name="chauffeur_affecte" id="v-chauff"></div>
        <div><label>Kilométrage</label><input type="number" name="kilometrage" id="v-km" min="0"></div>
    </div>
    <div class="form-row">
        <div><label>Mise en service</label><input type="date" name="date_mise_en_service" id="v-dms"></div>
        <div><label>Objectif recette spécifique (FCFA)</label><input type="number" name="objectif_recette" id="v-obj" min="0" placeholder="Laisser vide = paramètre global"></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="v-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>
@endif

{{-- ==================== ONGLET PANNES ==================== --}}
@if($onglet === 'pannes')
<div class="filtre-bar">
    <form method="GET" action="{{ route('parc.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="onglet" value="pannes">
        <select name="vehicule" onchange="this.form.submit()">
            <option value="">Tous les véhicules</option>
            @foreach($vehicules as $v)<option value="{{ $v->id }}" @selected(request('vehicule')==$v->id)>{{ $v->immatriculation }} ({{ $v->categorieLabel() }})</option>@endforeach
        </select>
        <select name="statut_panne" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            <option value="declaree" @selected(request('statut_panne')=='declaree')>Déclarée</option>
            <option value="en_diagnostic" @selected(request('statut_panne')=='en_diagnostic')>En diagnostic</option>
            <option value="en_intervention" @selected(request('statut_panne')=='en_intervention')>En intervention</option>
            <option value="cloturee" @selected(request('statut_panne')=='cloturee')>Clôturée</option>
        </select>
    </form>
</div>
<div class="carte" style="overflow-x:auto">
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
            <button class="act-btn" title="Modifier" onclick="editPanne({{ $p->id }})">{!! $icEdit !!}</button>
            <form method="POST" action="{{ route('parc.pannes.destroy', $p) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                <button class="act-btn del" title="Supprimer">{!! $icDel !!}</button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="8" style="text-align:center;color:var(--gris);padding:30px">Aucune panne enregistrée.</td></tr>
@endforelse
</tbody>
</table>
@if($pannes instanceof \Illuminate\Pagination\LengthAwarePaginator && $pannes->hasPages())
<div class="pagination-row">{!! $pannes->onEachSide(1)->links('pagination::simple-default') !!}</div>
@endif
</div>

<dialog id="dlg-panne">
<h3 id="dlg-panne-titre">{!! $icWrench !!} Déclarer une panne</h3>
<form id="form-panne" method="POST" action="{{ route('parc.pannes.store') }}">
    @csrf <div id="panne-method"></div>
    <div class="dlg-section">Identification</div>
    <div class="form-row3">
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="p-vehic" required>
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }}</option>@endforeach
            </select>
        </div>
        <div><label>Date déclaration</label><input type="date" name="date_declaration" id="p-date" value="{{ date('Y-m-d') }}"></div>
        <div><label>Urgence <span class="req">*</span></label>
            <select name="urgence" id="p-urg" required>
                <option value="faible">Faible</option><option value="moyenne" selected>Moyenne</option><option value="haute">Haute</option><option value="critique">Critique</option>
            </select>
        </div>
    </div>
    <div><label>Description <span class="req">*</span></label><textarea name="description" id="p-desc" required rows="2"></textarea></div>
    <div class="dlg-section">Diagnostic & Intervention</div>
    <div><label>Diagnostic</label><textarea name="diagnostic" id="p-diag" rows="2"></textarea></div>
    <div class="form-row3">
        <div><label>Décision</label><input name="decision" id="p-deci"></div>
        <div><label>Coût réel (FCFA)</label><input type="number" name="cout_reel" id="p-cout" min="0"></div>
        <div><label>Statut <span class="req">*</span></label>
            <select name="statut" id="p-statut" required>
                <option value="declaree">Déclarée</option><option value="en_diagnostic">En diagnostic</option><option value="en_intervention">En intervention</option><option value="cloturee">Clôturée</option>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div><label>Début intervention</label><input type="date" name="date_debut_intervention" id="p-ddi"></div>
        <div><label>Date clôture</label><input type="date" name="date_cloture" id="p-dcl"></div>
    </div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>
@endif

{{-- ==================== ONGLET RECETTES ==================== --}}
@if($onglet === 'recettes')
<div class="filtre-bar">
    <form method="GET" action="{{ route('parc.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="onglet" value="recettes">
        <select name="vehicule" onchange="this.form.submit()">
            <option value="">Tous les véhicules</option>
            @foreach($vehicules as $v)<option value="{{ $v->id }}" @selected(request('vehicule')==$v->id)>{{ $v->immatriculation }} ({{ $v->categorieLabel() }})</option>@endforeach
        </select>
        <input type="month" name="mois" value="{{ request('mois') }}" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px">
    </form>
</div>
<div class="carte" style="overflow-x:auto">
<table>
<thead><tr><th>Date</th><th>Jour</th><th>Véhicule</th><th>Chauffeur</th><th>Recette</th><th>Objectif</th><th>Écart</th><th>Mode</th><th>Contrôle</th><th></th></tr></thead>
<tbody>
@forelse($recettes as $rec)
@php $typeJourLabel = match($rec->type_jour ?? 'ouvrable') { 'ferie' => 'Férié', 'dimanche' => 'Dim.', default => 'Ouv.' }; @endphp
<tr>
    <td>{{ $rec->date->format('d/m/Y') }}</td>
    <td><span class="pill {{ ($rec->type_jour ?? 'ouvrable') === 'ouvrable' ? 'p-gris' : 'p-warn' }}">{{ $typeJourLabel }}</span></td>
    <td><strong>{{ $rec->vehicule->immatriculation ?? '?' }}</strong></td>
    <td>{{ $rec->chauffeur ?? '—' }}</td>
    <td style="font-weight:600">{{ number_format($rec->recette, 0, ',', ' ') }}</td>
    <td>{{ number_format($rec->objectif, 0, ',', ' ') }}</td>
    <td style="color:{{ $rec->ecart >= 0 ? 'var(--ok)' : 'var(--ko)' }};font-weight:600">{{ ($rec->ecart >= 0 ? '+' : '') . number_format($rec->ecart, 0, ',', ' ') }}</td>
    <td>{{ $rec->mode_remise ? ucfirst(str_replace('_', ' ', $rec->mode_remise)) : '—' }}</td>
    <td>{{ $rec->controle_par ?? '—' }}</td>
    <td>
        <div class="act-row">
            <button class="act-btn" title="Modifier" onclick="editRecette({{ $rec->id }})">{!! $icEdit !!}</button>
            <form method="POST" action="{{ route('parc.recettes.destroy', $rec) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                <button class="act-btn del" title="Supprimer">{!! $icDel !!}</button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="10" style="text-align:center;color:var(--gris);padding:30px">Aucune recette enregistrée.</td></tr>
@endforelse
</tbody>
</table>
@if($recettes instanceof \Illuminate\Pagination\LengthAwarePaginator && $recettes->hasPages())
<div class="pagination-row">{!! $recettes->onEachSide(1)->links('pagination::simple-default') !!}</div>
@endif
</div>

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
        <div><label>Chauffeur</label><input name="chauffeur" id="r-chauff"></div>
    </div>
    <div class="form-row3">
        <div><label>Recette (FCFA) <span class="req">*</span></label><input type="number" name="recette" id="r-montant" min="0" required></div>
        <div><label>Mode de remise</label>
            <select name="mode_remise" id="r-mode">
                <option value="">—</option><option value="especes">Espèces</option><option value="mobile_money">Mobile Money</option><option value="cheque">Chèque</option><option value="virement">Virement</option><option value="autre">Autre</option>
            </select>
        </div>
        <div><label>Contrôlé par</label><input name="controle_par" id="r-ctrl"></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="r-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>
@endif

{{-- ==================== ONGLET MAINTENANCE ==================== --}}
@if($onglet === 'maintenance')
<div class="filtre-bar">
    <form method="GET" action="{{ route('parc.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="onglet" value="maintenance">
        <select name="vehicule" onchange="this.form.submit()">
            <option value="">Tous les véhicules</option>
            @foreach($vehicules as $v)<option value="{{ $v->id }}" @selected(request('vehicule')==$v->id)>{{ $v->immatriculation }}</option>@endforeach
        </select>
    </form>
</div>
<div class="carte" style="overflow-x:auto">
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
                <button class="act-btn del" title="Supprimer">{!! $icDel !!}</button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="8" style="text-align:center;color:var(--gris);padding:30px">Aucune échéance enregistrée.</td></tr>
@endforelse
</tbody>
</table>
</div>

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
                <option value="vidange">Vidange</option><option value="assurance">Assurance</option><option value="visite_technique">Visite technique</option><option value="patente">Patente</option><option value="carte_stationnement">Carte de stationnement</option><option value="autre">Autre</option>
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
@endif

{{-- ==================== ONGLET PÉNALITÉS ==================== --}}
@if($onglet === 'penalites')
<div class="filtre-bar">
    <form method="GET" action="{{ route('parc.index') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="onglet" value="penalites">
        <select name="vehicule" onchange="this.form.submit()">
            <option value="">Tous les véhicules</option>
            @foreach($vehicules as $v)<option value="{{ $v->id }}" @selected(request('vehicule')==$v->id)>{{ $v->immatriculation }}</option>@endforeach
        </select>
        <select name="statut_pen" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            <option value="non_payee" @selected(request('statut_pen')=='non_payee')>Non payée</option>
            <option value="payee" @selected(request('statut_pen')=='payee')>Payée</option>
            <option value="contestee" @selected(request('statut_pen')=='contestee')>Contestée</option>
        </select>
    </form>
</div>
<div class="carte" style="overflow-x:auto">
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
            <button class="act-btn" title="Modifier" onclick="editPenal({{ $pen->id }})">{!! $icEdit !!}</button>
            <form method="POST" action="{{ route('parc.penalites.destroy', $pen) }}" onsubmit="return confirm('Supprimer ?')">@csrf @method('DELETE')
                <button class="act-btn del" title="Supprimer">{!! $icDel !!}</button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="9" style="text-align:center;color:var(--gris);padding:30px">Aucune pénalité enregistrée.</td></tr>
@endforelse
</tbody>
</table>
@if($penalites instanceof \Illuminate\Pagination\LengthAwarePaginator && $penalites->hasPages())
<div class="pagination-row">{!! $penalites->onEachSide(1)->links('pagination::simple-default') !!}</div>
@endif
</div>

<dialog id="dlg-penal">
<h3 id="dlg-penal-titre">{!! $icAlert !!} Nouvelle pénalité</h3>
<form id="form-penal" method="POST" action="{{ route('parc.penalites.store') }}">
    @csrf <div id="penal-method"></div>
    <div class="dlg-section">Infraction</div>
    <div class="form-row3">
        <div><label>Véhicule <span class="req">*</span></label>
            <select name="vehicule_id" id="pe-vehic" required>
                @foreach($vehicules as $v)<option value="{{ $v->id }}">{{ $v->immatriculation }}</option>@endforeach
            </select>
        </div>
        <div><label>Date <span class="req">*</span></label><input type="date" name="date_infraction" id="pe-date" value="{{ date('Y-m-d') }}" required></div>
        <div><label>Type <span class="req">*</span></label>
            <select name="type" id="pe-type" required>
                <option value="contravention">Contravention</option><option value="exces_vitesse">Excès de vitesse</option><option value="stationnement">Stationnement</option><option value="defaut_document">Défaut de document</option><option value="autre">Autre</option>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div><label>Chauffeur</label><input name="chauffeur" id="pe-chauff"></div>
        <div><label>Lieu</label><input name="lieu" id="pe-lieu"></div>
    </div>
    <div><label>Description</label><textarea name="description" id="pe-desc" rows="2"></textarea></div>
    <div class="dlg-section">Règlement</div>
    <div class="form-row3">
        <div><label>Montant (FCFA) <span class="req">*</span></label><input type="number" name="montant" id="pe-mont" min="0" required></div>
        <div><label>Statut <span class="req">*</span></label>
            <select name="statut" id="pe-statut" required>
                <option value="non_payee">Non payée</option><option value="payee">Payée</option><option value="contestee">Contestée</option>
            </select>
        </div>
        <div><label>Date paiement</label><input type="date" name="date_paiement" id="pe-dpay"></div>
    </div>
    <div><label>Observations</label><textarea name="observations" id="pe-obs" rows="2"></textarea></div>
    <div style="display:flex;gap:10px;margin-top:16px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>
@endif

{{-- ==================== DIALOG PARAMÈTRES ==================== --}}
<dialog id="dlg-params">
<h3>{!! $icGear !!} Paramètres du parc</h3>
<form method="POST" action="{{ route('parc.parametres') }}">
    @csrf
    <p style="font-size:12px;color:var(--gris);margin-bottom:14px">Les objectifs de recette sont appliqués automatiquement selon la catégorie du véhicule et le type de jour (ouvrable / férié-dimanche).</p>
    <div class="param-grid">
    @foreach($parametres as $p)
        <div class="param-item">
            <label>{{ $p->libelle }}</label>
            <input type="number" name="params[{{ $p->id }}]" value="{{ $p->valeur }}" min="0">
        </div>
    @endforeach
    </div>
    <div style="display:flex;gap:10px;margin-top:18px;justify-content:flex-end">
        <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
        <button class="btn">Enregistrer</button>
    </div>
</form>
</dialog>

@endsection

@php
$_vData = [];
foreach($vehicules as $_v) {
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
    ];
}
$_pData = [];
if ($onglet === 'pannes') {
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
}
$_rData = [];
if ($onglet === 'recettes') {
    foreach($recettes as $_r) {
        $_rData[$_r->id] = [
            'date' => $_r->date->format('Y-m-d'),
            'vehicule_id' => $_r->vehicule_id,
            'chauffeur' => $_r->chauffeur,
            'recette' => $_r->recette,
            'mode_remise' => $_r->mode_remise,
            'controle_par' => $_r->controle_par,
            'observations' => $_r->observations,
        ];
    }
}
$_mData = [];
if ($onglet === 'maintenance') {
    foreach($maintenances as $_m) {
        $_mData[$_m->id] = [
            'vehicule_id' => $_m->vehicule_id,
            'type_echeance' => $_m->type_echeance,
            'derniere_realisation' => $_m->derniere_realisation ? $_m->derniere_realisation->format('Y-m-d') : '',
            'prochaine_echeance' => $_m->prochaine_echeance ? $_m->prochaine_echeance->format('Y-m-d') : '',
            'observations' => $_m->observations,
        ];
    }
}
$_peData = [];
if ($onglet === 'penalites') {
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
}
@endphp

@push('scripts')
<script>
var vehiculesData = @json($_vData);
var pannesData = @json($_pData);
var recettesData = @json($_rData);
var maintData = @json($_mData);
var penalData = @json($_peData);

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
    document.getElementById('dlg-vehicule').showModal();
}

function editPanne(id){
    var d=pannesData[id]; if(!d)return;
    document.getElementById('dlg-panne-titre').innerHTML='{!! $icWrench !!} Modifier la panne';
    document.getElementById('form-panne').action='{{ url("parc/pannes") }}/'+id;
    document.getElementById('panne-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('p-vehic').value=d.vehicule_id;
    document.getElementById('p-date').value=d.date_declaration||'';
    document.getElementById('p-desc').value=d.description;
    document.getElementById('p-urg').value=d.urgence;
    document.getElementById('p-statut').value=d.statut;
    document.getElementById('p-diag').value=d.diagnostic||'';
    document.getElementById('p-deci').value=d.decision||'';
    document.getElementById('p-cout').value=d.cout_reel||'';
    document.getElementById('p-ddi').value=d.date_debut_intervention||'';
    document.getElementById('p-dcl').value=d.date_cloture||'';
    document.getElementById('dlg-panne').showModal();
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
    document.getElementById('dlg-penal-titre').innerHTML='{!! $icAlert !!} Modifier la pénalité';
    document.getElementById('form-penal').action='{{ url("parc/penalites") }}/'+id;
    document.getElementById('penal-method').innerHTML='<input type="hidden" name="_method" value="PUT">';
    document.getElementById('pe-vehic').value=d.vehicule_id;
    document.getElementById('pe-date').value=d.date_infraction;
    document.getElementById('pe-type').value=d.type;
    document.getElementById('pe-chauff').value=d.chauffeur||'';
    document.getElementById('pe-lieu').value=d.lieu||'';
    document.getElementById('pe-desc').value=d.description||'';
    document.getElementById('pe-mont').value=d.montant;
    document.getElementById('pe-statut').value=d.statut;
    document.getElementById('pe-dpay').value=d.date_paiement||'';
    document.getElementById('pe-obs').value=d.observations||'';
    document.getElementById('dlg-penal').showModal();
}

document.querySelectorAll('dialog').forEach(function(dlg){
    dlg.addEventListener('close',function(){
        var form=dlg.querySelector('form');
        if(form){form.reset();var m=dlg.querySelector('[id$="-method"]');if(m)m.innerHTML='';}
    });
});
</script>
@endpush
