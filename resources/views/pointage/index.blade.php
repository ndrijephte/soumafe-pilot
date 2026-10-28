@extends('layouts.app')
@section('sousTitre', 'Pointage & Présence')
@section('titre', 'Pointage')
@push('styles')
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px;transition:transform .15s}
.kpi:hover{transform:translateY(-2px)}
.kpi .lbl{font-size:11px;color:var(--gris);text-transform:uppercase;letter-spacing:.4px}
.kpi .val{font-size:26px;font-weight:700;color:var(--bleu);margin:4px 0}
.kpi .sub{font-size:11px;color:var(--gris)}
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:16px}
.panneau h3{font-size:14px;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.filtre-bar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.filtre-bar input,.filtre-bar select{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.btn.ok{background:var(--ok)}
.btn.ko{background:var(--ko)}
.btn-pointage{display:flex;align-items:center;gap:7px;padding:9px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.btn-pointage.arrivee{background:#f0fdf4;color:#15803d;border:1.5px solid #bbf7d0}
.btn-pointage.arrivee:hover{background:#dcfce7;border-color:#86efac}
.btn-pointage.depart{background:#fffbeb;color:#92400e;border:1.5px solid #fde68a}
.btn-pointage.depart:hover{background:#fef3c7;border-color:#fcd34d}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:10px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.mini-table tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;display:inline-block}
.b-ok{background:#dcfce7;color:var(--ok)}
.b-tot{background:#e8f5e9;color:#2e7d32}
.b-warn{background:#fef3c7;color:#b45309}
.b-ko{background:#fee2e2;color:var(--ko)}
.b-info{background:#dbeafe;color:#1d4ed8}
.b-gris{background:#e2e8f0;color:#475569}
.pointage-banner{display:flex;align-items:center;justify-content:space-between;background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px 20px;margin-bottom:16px;gap:12px;flex-wrap:wrap}
.pointage-banner .pb-left{display:flex;align-items:center;gap:12px}
.pointage-banner .pb-icon{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#eff6ff);display:flex;align-items:center;justify-content:center}
.pointage-banner .pb-text{font-size:13px;color:var(--gris)}
.pointage-banner .pb-text strong{color:var(--bleu);font-weight:600;display:block;font-size:14px}
dialog{border:none;border-radius:14px;padding:24px;width:420px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
dialog textarea{resize:vertical}
.req{color:var(--ko)}
.act-row{display:flex;gap:4px}
.act-btn{background:none;border:1px solid var(--bord);border-radius:6px;padding:4px 6px;cursor:pointer;display:flex;align-items:center}
.act-btn:hover{background:var(--fond)}
@media(max-width:900px){.kpis{grid-template-columns:repeat(2,1fr)}.filtre-bar{flex-direction:column;align-items:stretch}.pointage-banner{flex-direction:column;text-align:center}.mini-table{display:block;overflow-x:auto}}
@media(max-width:600px){.kpis{grid-template-columns:1fr}.kpi .val{font-size:20px}.kpi{padding:10px}dialog{padding:16px}}
</style>
@endpush
@section('contenu')
@php
    $ic = fn(string $d, int $s=16) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
    $icClock = $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>');
    $icCheck = $ic('<polyline points="20 6 9 17 4 12"/>');
    $icX = $ic('<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>');
    $icEdit = $ic('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>');
    $icUser = $ic('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>');
    $icAlert = $ic('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>');

    $u = auth()->user();
    $isAdmin = $u->estAdmin();
    $isDG = $u->aRole('direction');
    $today = today()->toDateString();
    $isToday = $date->toDateString() === $today;

    // Pointage de l'utilisateur connecté
    $monPointage = $pointages[$u->id] ?? null;
    $dejaArrive = $monPointage && $monPointage->heure_arrivee;
    $dejaParti = $monPointage && $monPointage->heure_depart;
@endphp

<h2>Pointage & Présence</h2>

@if(session('succes'))<div class="alerte ok">{!! $icCheck !!} {{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{!! $icAlert !!} {{ session('erreur') }}</div>@endif

{{-- Bannière auto-pointage (jour courant seulement, pas DG) --}}
@if($isToday && !$isDG)
<div class="pointage-banner">
    <div class="pb-left">
        <div class="pb-icon">{!! $ic('<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>', 22) !!}</div>
        <div class="pb-text">
            @if(!$dejaArrive)
                <strong>Bonjour {{ $u->prenom ?? $u->name }}</strong>
                Vous n'avez pas encore pointé aujourd'hui
            @elseif(!$dejaParti)
                <strong>En poste depuis {{ \Carbon\Carbon::parse($monPointage->heure_arrivee)->format('H:i') }}</strong>
                Bonne journée !
            @else
                <strong>Journée terminée</strong>
                {{ \Carbon\Carbon::parse($monPointage->heure_arrivee)->format('H:i') }} - {{ \Carbon\Carbon::parse($monPointage->heure_depart)->format('H:i') }}
                ({{ $monPointage->dureeFormatee() }})
            @endif
        </div>
    </div>
    <div style="display:flex;gap:8px">
        @if(!$dejaArrive)
            <form method="POST" action="{{ route('pointage.arrivee') }}">@csrf
                <button class="btn-pointage arrivee">
                    {!! $ic('<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>') !!}
                    Pointer mon arrivée
                </button>
            </form>
        @elseif(!$dejaParti)
            <form method="POST" action="{{ route('pointage.depart') }}" onsubmit="return confirm('Confirmez-vous votre départ ?')">@csrf
                <button class="btn-pointage depart">
                    {!! $ic('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>') !!}
                    Pointer mon départ
                </button>
            </form>
        @endif
    </div>
</div>
@endif

{{-- KPIs --}}
<div class="kpis">
    <div class="kpi"><div class="lbl">Présents</div><div class="val" style="color:var(--ok)">{{ $stats['pointes'] ?? 0 }}</div><div class="sub">/ {{ $stats['total'] ?? 0 }} collaborateurs</div></div>
    <div class="kpi"><div class="lbl">Retards</div><div class="val" style="color:#b45309">{{ $stats['retards'] ?? 0 }}</div><div class="sub">aujourd'hui</div></div>
    <div class="kpi"><div class="lbl">Absents</div><div class="val" style="color:var(--ko)">{{ $stats['absents'] ?? 0 }}</div><div class="sub">+ {{ $stats['nonPointes'] ?? 0 }} non pointés</div></div>
    <div class="kpi"><div class="lbl">Ponctualité mois</div><div class="val">{{ $stats['tauxPonctualite'] ?? 0 }}%</div><div class="sub">{{ $stats['totalRetardsMois'] ?? 0 }} retard(s) ce mois</div></div>
</div>

{{-- Filtres --}}
<div class="filtre-bar">
    <form method="GET" action="{{ route('pointage.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()">
        <span style="font-size:12px;color:var(--gris)">{{ $date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</span>
    </form>
    <div style="flex:1"></div>
    <input type="text" id="filtreNom" placeholder="Filtrer par nom..." oninput="filtrerPresence()" style="min-width:160px">
</div>

{{-- Liste de présence --}}
<div class="panneau" style="overflow-x:auto">
    <h3>{!! $icUser !!} Liste de présence - {{ $date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</h3>
    <table class="mini-table" id="tablePresence">
        <thead>
        <tr>
            <th>Collaborateur</th>
            <th>Poste</th>
            <th>Arrivée</th>
            <th>Départ</th>
            <th>Durée</th>
            <th>Statut</th>
            <th>Motif</th>
            @if($isAdmin)<th style="width:80px">Actions</th>@endif
        </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
        @php
            $p = $pointages[$user->id] ?? null;
            $statut = $p ? $p->statut : 'non_pointe';
            $badgeCls = match($statut) {
                'present' => 'b-ok',
                'retard' => 'b-warn',
                'sorti_tot' => 'b-tot',
                'absent' => 'b-ko',
                default => 'b-gris',
            };
            $statutLabel = $p ? $p->statutLibelle() : 'Non pointé';
            $motif = $p ? ($p->motif_retard ?: $p->motif_depart ?: $p->motif_absence ?: '') : '';
        @endphp
        <tr data-nom="{{ strtolower($user->nomComplet()) }}">
            <td style="font-weight:600;color:var(--bleu)">{{ $user->nomComplet() }}</td>
            <td style="font-size:11px;color:var(--gris)">{{ $user->roleLibelle() }}</td>
            <td>{{ $p && $p->heure_arrivee ? \Carbon\Carbon::parse($p->heure_arrivee)->format('H:i') : '—' }}</td>
            <td>{{ $p && $p->heure_depart ? \Carbon\Carbon::parse($p->heure_depart)->format('H:i') : '—' }}</td>
            <td>{{ $p ? $p->dureeFormatee() : '—' }}</td>
            <td><span class="badge {{ $badgeCls }}">{{ $statutLabel }}</span>
                @if($p && $p->corrige_par)<span style="font-size:9px;color:var(--gris);margin-left:4px">(corrigé)</span>@endif
            </td>
            <td style="font-size:11px;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $motif }}">{{ $motif ?: '—' }}</td>
            @if($isAdmin)
            <td>
                <div class="act-row">
                    @if($p)
                    <button class="act-btn" title="Corriger" onclick="ouvrirCorrection({{ $p->id }}, '{{ $p->heure_arrivee ? \Carbon\Carbon::parse($p->heure_arrivee)->format('H:i') : '' }}', '{{ $p->heure_depart ? \Carbon\Carbon::parse($p->heure_depart)->format('H:i') : '' }}', '{{ addslashes($user->nomComplet()) }}')">
                        {!! $icEdit !!}
                    </button>
                    @endif
                    @if(!$p || $statut === 'non_pointe')
                    <button class="act-btn" title="Pointer pour" onclick="ouvrirPointerPour({{ $user->id }}, '{{ $date->toDateString() }}', '{{ addslashes($user->nomComplet()) }}')" style="color:var(--ok)">
                        {!! $ic('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',14) !!}
                    </button>
                    <button class="act-btn" title="Marquer absent" onclick="ouvrirAbsence({{ $user->id }}, '{{ $date->toDateString() }}', '{{ addslashes($user->nomComplet()) }}')">
                        {!! $icX !!}
                    </button>
                    @endif
                </div>
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- Dialog motif retard --}}
@if(session('demande_motif_retard'))
<dialog id="dlg-motif-retard">
    <h3 style="color:var(--bleu);margin-bottom:4px">Motif du retard</h3>
    <p style="font-size:12px;color:var(--gris)">Votre arrivée a été enregistrée après 08h00. Veuillez justifier.</p>
    <form method="POST" action="{{ route('pointage.motif', session('demande_motif_retard')) }}">@csrf
        <input type="hidden" name="type" value="retard">
        <label>Motif <span class="req">*</span></label>
        <textarea name="motif" required rows="3" placeholder="Raison du retard..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endif

{{-- Dialog motif départ anticipé (après pointage) --}}
@if(session('demande_motif_depart'))
<dialog id="dlg-motif-depart">
    <h3 style="color:var(--bleu);margin-bottom:4px">Motif du départ anticipé</h3>
    <p style="font-size:12px;color:var(--gris)">Votre départ a été enregistré avant 18h00. Veuillez justifier.</p>
    <form method="POST" action="{{ route('pointage.motif', session('demande_motif_depart')) }}">@csrf
        <input type="hidden" name="type" value="depart">
        <label>Motif <span class="req">*</span></label>
        <textarea name="motif" required rows="3" placeholder="Raison du départ anticipé..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>
@endif

{{-- Dialog motif départ AVANT pointage (avant 18h) --}}
@if(session('demande_motif_depart_avant'))
<dialog id="dlg-motif-depart-avant">
    <h3 style="color:var(--bleu);margin-bottom:4px">Départ avant 18h00</h3>
    <p style="font-size:12px;color:var(--gris)">Il n'est pas encore 18h00. Veuillez justifier votre départ anticipé.</p>
    <form method="POST" action="{{ route('pointage.depart') }}">@csrf
        <label>Motif <span class="req">*</span></label>
        <textarea name="motif_depart" required rows="3" placeholder="Raison du départ anticipé..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Confirmer le départ</button>
        </div>
    </form>
</dialog>
@endif

{{-- Dialog correction (admin) --}}
@if($isAdmin)
<dialog id="dlg-correction">
    <h3 style="color:var(--bleu);margin-bottom:4px">Corriger un pointage</h3>
    <p style="font-size:12px;color:var(--gris)" id="corr-nom"></p>
    <form method="POST" id="form-correction">@csrf @method('PUT')
        <label>Arrivée</label>
        <input type="time" name="heure_arrivee" id="corr-arrivee">
        <label>Motif d'arrivée <span style="font-size:10px;color:var(--gris)">(si retard)</span></label>
        <input type="text" name="motif_retard" id="corr-motif-arrivee" placeholder="Raison du retard..." maxlength="500">
        <label>Départ</label>
        <input type="time" name="heure_depart" id="corr-depart">
        <label>Motif de départ <span style="font-size:10px;color:var(--gris)">(si anticipé)</span></label>
        <input type="text" name="motif_depart" id="corr-motif-depart" placeholder="Raison du départ anticipé..." maxlength="500">
        <label>Motif de correction <span class="req">*</span></label>
        <textarea name="motif_correction" required rows="2" placeholder="Raison de la correction..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Corriger</button>
        </div>
    </form>
</dialog>

{{-- Dialog pointage manuel (admin) --}}
<dialog id="dlg-pointer-pour">
    <h3 style="color:var(--bleu);margin-bottom:4px">Pointer pour un collaborateur</h3>
    <p style="font-size:12px;color:var(--gris)" id="pp-nom"></p>
    <form method="POST" action="{{ route('pointage.pointer-pour') }}">@csrf
        <input type="hidden" name="user_id" id="pp-user-id">
        <input type="hidden" name="date" id="pp-date">
        <label>Arrivée <span class="req">*</span></label>
        <input type="time" name="heure_arrivee" required id="pp-arrivee">
        <label>Motif d'arrivée <span style="font-size:10px;color:var(--gris)">(si retard)</span></label>
        <input type="text" name="motif_retard" placeholder="Raison du retard..." maxlength="500">
        <label>Départ <span style="font-size:10px;color:var(--gris)">(optionnel)</span></label>
        <input type="time" name="heure_depart" id="pp-depart">
        <label>Motif de départ <span style="font-size:10px;color:var(--gris)">(si anticipé)</span></label>
        <input type="text" name="motif_depart" placeholder="Raison du départ anticipé..." maxlength="500">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Enregistrer</button>
        </div>
    </form>
</dialog>

{{-- Dialog absence (admin) --}}
<dialog id="dlg-absence">
    <h3 style="color:var(--bleu);margin-bottom:4px">Marquer absent</h3>
    <p style="font-size:12px;color:var(--gris)" id="abs-nom"></p>
    <form method="POST" action="{{ route('pointage.absent') }}">@csrf
        <input type="hidden" name="user_id" id="abs-user-id">
        <input type="hidden" name="date" id="abs-date">
        <label>Motif d'absence <span class="req">*</span></label>
        <textarea name="motif" required rows="2" placeholder="Raison de l'absence..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn ko">Marquer absent</button>
        </div>
    </form>
</dialog>
@endif

{{-- Jours ouvrables + (gestionnaire) --}}
@if($estGestionnaire ?? false)
<div class="panneau" style="margin-top:20px">
    <h3>
        {!! $ic('<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>') !!}
        Jours ouvrables +
    </h3>
    <p style="font-size:12px;color:var(--gris);margin-bottom:12px">
        Déclarez les jours spéciaux travaillés (samedis, dimanches, fériés). Le pointage sera obligatoire ces jours-là.
    </p>

    <form method="POST" action="{{ route('pointage.jour-special.ajouter') }}" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px">
        @csrf
        <div>
            <label style="font-size:11px;color:var(--gris);display:block;margin-bottom:2px">Date</label>
            <input type="date" name="date" required style="padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit">
        </div>
        <div>
            <label style="font-size:11px;color:var(--gris);display:block;margin-bottom:2px">Motif <span class="req">*</span></label>
            <input type="text" name="motif" required placeholder="Ex: inventaire, permanence, rattrapage" style="padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit;width:240px">
        </div>
        <button type="submit" class="btn sm">Ajouter</button>
    </form>

    {{-- Filtre consultation --}}
    @php $joursSp = $tousJoursSpeciaux ?? collect(); @endphp
    @if($joursSp->isNotEmpty())
    <div style="display:flex;gap:8px;align-items:center;margin-bottom:10px;flex-wrap:wrap">
        <label style="font-size:11px;color:var(--gris)">Filtrer :</label>
        <select id="filtreJourMois" onchange="filtrerJoursSpeciaux()" style="padding:4px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12px">
            <option value="">Tous les mois</option>
            @foreach($joursSp->groupBy(fn($j) => $j->date->format('Y-m'))->keys() as $mois)
            <option value="{{ $mois }}">{{ \Carbon\Carbon::parse($mois.'-01')->isoFormat('MMMM YYYY') }}</option>
            @endforeach
        </select>
        <span style="font-size:11px;color:var(--gris)" id="compteurJours">{{ $joursSp->count() }} jour(s)</span>
    </div>
    <table style="width:100%;font-size:13px;border-collapse:collapse" id="tableJoursSpeciaux">
        <thead>
            <tr style="border-bottom:1px solid var(--bord);text-align:left">
                <th style="padding:6px 8px;font-weight:600;color:var(--bleu)">Date</th>
                <th style="padding:6px 8px;font-weight:600;color:var(--bleu)">Jour</th>
                <th style="padding:6px 8px;font-weight:600;color:var(--bleu)">Motif</th>
                <th style="padding:6px 8px;font-weight:600;color:var(--bleu)">Défini par</th>
                <th style="padding:6px 8px"></th>
            </tr>
        </thead>
        <tbody>
        @foreach($joursSp as $js)
            <tr style="border-bottom:1px solid var(--bord)" data-mois="{{ $js->date->format('Y-m') }}">
                <td style="padding:6px 8px">{{ $js->date->isoFormat('D MMM YYYY') }}</td>
                <td style="padding:6px 8px;color:var(--accent);font-weight:600;font-size:12px">{{ ucfirst($js->date->isoFormat('dddd')) }}</td>
                <td style="padding:6px 8px;color:var(--gris)">{{ $js->motif ?? '—' }}</td>
                <td style="padding:6px 8px;font-size:12px">{{ $js->createur?->nomComplet() ?? '—' }}</td>
                <td style="padding:6px 8px;text-align:right">
                    <button type="button" class="btn sm ko" style="font-size:11px"
                        onclick="ouvrirSupprJour({{ $js->id }}, '{{ $js->date->isoFormat('dddd D MMM YYYY') }}')">
                        Retirer
                    </button>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @else
    <p style="font-size:12px;color:var(--gris);font-style:italic">Aucun jour ouvrable spécial défini.</p>
    @endif
</div>

{{-- Dialog suppression jour spécial --}}
<dialog id="dlg-suppr-jour">
    <h3 style="margin:0 0 12px;font-size:15px;color:var(--bleu)">Retirer un jour ouvrable +</h3>
    <p id="suppr-jour-info" style="font-size:13px;color:var(--gris);margin:0 0 10px"></p>
    <form id="form-suppr-jour" method="POST">
        @csrf
        <label style="font-size:12px;color:var(--gris);display:block;margin-bottom:3px">Motif <span class="req">*</span></label>
        <textarea name="motif_suppression" required minlength="5" rows="2" placeholder="Précisez le motif..." style="width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit"></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:14px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn" style="background:var(--ko)">Retirer</button>
        </div>
    </form>
</dialog>
@endif

@endsection

@push('scripts')
<script>
function ouvrirSupprJour(id, dateStr) {
    document.getElementById('form-suppr-jour').action = '/pointage/jour-special/' + id + '/retirer';
    document.getElementById('suppr-jour-info').textContent = dateStr;
    document.getElementById('form-suppr-jour').querySelector('textarea').value = '';
    document.getElementById('dlg-suppr-jour').showModal();
}
function filtrerJoursSpeciaux() {
    var mois = document.getElementById('filtreJourMois').value;
    var rows = document.querySelectorAll('#tableJoursSpeciaux tbody tr');
    var count = 0;
    rows.forEach(function(tr) {
        var show = !mois || tr.dataset.mois === mois;
        tr.style.display = show ? '' : 'none';
        if (show) count++;
    });
    document.getElementById('compteurJours').textContent = count + ' jour(s)';
}
function filtrerPresence(){
    const txt=document.getElementById('filtreNom').value.toLowerCase();
    document.querySelectorAll('#tablePresence tbody tr').forEach(tr=>{
        tr.style.display=!txt||tr.dataset.nom.includes(txt)?'':'none';
    });
}
function ouvrirCorrection(id, arrivee, depart, nom){
    document.getElementById('corr-nom').textContent=nom;
    document.getElementById('corr-arrivee').value=arrivee;
    document.getElementById('corr-depart').value=depart;
    document.getElementById('form-correction').action='{{ url("pointage") }}/'+id+'/corriger';
    document.getElementById('dlg-correction').showModal();
}
function ouvrirAbsence(userId, date, nom){
    document.getElementById('abs-nom').textContent=nom;
    document.getElementById('abs-user-id').value=userId;
    document.getElementById('abs-date').value=date;
    document.getElementById('dlg-absence').showModal();
}
function ouvrirPointerPour(userId, date, nom){
    document.getElementById('pp-nom').textContent=nom;
    document.getElementById('pp-user-id').value=userId;
    document.getElementById('pp-date').value=date;
    var dlg=document.getElementById('dlg-pointer-pour');
    dlg.querySelectorAll('input[type=text],input[type=time]').forEach(function(i){i.value='';});
    dlg.showModal();
}
// Ouvrir automatiquement les dialogs motif au chargement + bloquer Echap
document.addEventListener('DOMContentLoaded', function(){
    ['dlg-motif-retard','dlg-motif-depart','dlg-motif-depart-avant'].forEach(function(id){
        var d=document.getElementById(id);
        if(d){
            d.showModal();
            d.addEventListener('cancel', function(e){ e.preventDefault(); });
            d.addEventListener('keydown', function(e){
                if(e.key==='Escape') e.preventDefault();
            });
            // Valider motif suffisant (min 5 caractères significatifs)
            var form=d.querySelector('form');
            if(form) form.addEventListener('submit', function(e){
                var ta=form.querySelector('textarea');
                if(ta && ta.value.replace(/[\s.\-_,;!?]+/g,'').length<5){
                    e.preventDefault();
                    ta.style.borderColor='var(--ko)';
                    var msg=ta.parentNode.querySelector('.motif-err');
                    if(!msg){msg=document.createElement('div');msg.className='motif-err';msg.style.cssText='color:var(--ko);font-size:11px;margin-top:4px';ta.parentNode.appendChild(msg);}
                    msg.textContent='Le motif doit contenir au moins 5 caractères significatifs.';
                    ta.focus();
                }
            });
        }
    });
});
</script>
@endpush
