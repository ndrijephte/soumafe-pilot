@extends('layouts.app')
@section('sousTitre', 'Suivi de saisie')
@section('titre', 'Suivi de saisie')
@push('styles')
<style>
.section{margin-bottom:22px}
.section-titre{font-size:15px;font-weight:700;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:8px}
.carte{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:12px}
.tache-confirm{display:flex;justify-content:space-between;align-items:flex-start;padding:12px 0;border-bottom:1px solid var(--bord);gap:12px}
.tache-confirm:last-child{border:none}
.tache-info{flex:1}
.tache-intitule{font-size:13px;font-weight:600;color:#1e293b}
.tache-meta{font-size:11px;color:var(--gris);margin-top:2px}
.typ{font-size:9px;font-weight:700;padding:1px 5px;border-radius:4px;margin-right:4px}
.t1{background:#dbeafe;color:#1d4ed8}.t2{background:#e2e8f0;color:#475569}.t3{background:#fee2e2;color:#b91c1c}
.confirm-actions{display:flex;gap:5px;flex:none;align-items:center}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sm{padding:5px 12px;font-size:11.5px}
.btn.ok{background:var(--ok)}.btn.ko{background:var(--ko)}.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn-ic-s{display:inline-flex;align-items:center;justify-content:center;gap:4px;padding:4px 10px;height:30px;border-radius:8px;border:1.5px solid var(--bord);background:#fff;cursor:pointer;transition:all .15s;font-size:11.5px;font-weight:600;white-space:nowrap}
.btn-ic-s:hover{transform:scale(1.05);box-shadow:0 2px 6px rgba(0,0,0,.1)}
.btn-ic-s.ok{color:var(--ok);border-color:var(--ok);background:#f0fdf4}
.btn-ic-s.ko{color:var(--ko);border-color:var(--ko);background:#fef2f2}
.btn-ic-s.accent{color:var(--accent);border-color:var(--accent);background:#eff6ff}
.btn-ic-s svg{pointer-events:none;flex:none}
.badge-done{padding:3px 10px;border-radius:8px;font-size:11px;font-weight:600}
.badge-confirmee{background:#dcfce7;color:var(--ok)}.badge-infirmee{background:#fee2e2;color:var(--ko)}
.evt-type{padding:2px 9px;border-radius:10px;font-size:10.5px;font-weight:600}
.evt-interruption_service{background:#fee2e2;color:var(--ko)}.evt-absence{background:#fef3c7;color:#b45309}
.evt-condoleance{background:#e2e8f0;color:#475569}.evt-reunion_exceptionnelle{background:#dbeafe;color:#1d4ed8}
.evt-autre{background:#f1f5f9;color:#64748b}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
dialog{border:none;border-radius:14px;padding:24px;width:480px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:600;text-transform:uppercase}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog textarea{min-height:60px;resize:vertical}
.req{color:var(--ko)}
.hist-line{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px}
.hist-line:last-child{border:none}
.st-saisi{color:var(--ok)}.st-justifie{color:var(--accent)}.st-a_justifier{color:var(--ko)}
.grille{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.justif-ligne{display:flex;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9}
.justif-ligne:last-child{border-bottom:none}
.justif-date{flex:none;font-size:12.5px;font-weight:600;min-width:96px;color:var(--bleu)}
.justif-motif{flex:none;width:110px;padding:6px 8px;border:1px solid var(--bord);border-radius:8px;font-size:12px;background:#fff}
.justif-texte{flex:1;min-width:0;padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.etat{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#334155}
.etat:before{content:"";width:7px;height:7px;border-radius:50%;background:#94a3b8;flex:none}
.etat.ok:before{background:var(--ok)}.etat.warn:before{background:#d97706}.etat.bleu:before{background:var(--accent)}
.etat.mut{color:var(--gris);font-weight:400}
/* En-tête : actions de déclaration */
.sa-tete{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.sa-actions{display:flex;gap:8px;flex-wrap:wrap}
.sa-act{display:inline-flex;align-items:center;gap:7px;background:#fff;border:1px solid var(--bord);color:var(--bleu);border-radius:9px;padding:7px 13px;font-size:12.5px;font-weight:600;cursor:pointer;transition:border-color .15s,background .15s,color .15s}
.sa-act svg{color:var(--accent)}
.sa-act:hover{border-color:var(--accent);background:#f3f8fd;color:var(--accent)}
/* Fenêtres de déclaration */
.sa-dlg{width:540px;max-width:94vw;padding:22px 24px}
.sa-dlg-tete{display:flex;gap:12px;align-items:flex-start;margin-bottom:6px}
.sa-dlg-ic{flex:none;width:36px;height:36px;border-radius:10px;background:#e6f0fa;color:var(--accent);display:flex;align-items:center;justify-content:center}
.sa-dlg h3{color:var(--bleu);font-size:16px;margin:2px 0 2px}
.sa-dlg-tete p{font-size:12px;color:var(--gris);margin:0;line-height:1.45}
.sa-lab{display:block;font-size:12px;color:#475569;font-weight:600;margin:14px 0 6px;text-transform:none}
.sa-lab .fac{font-weight:400;color:#94a3b8;margin-left:4px}
.sa-champ{width:100%;padding:8px 11px;border:1px solid var(--bord);border-radius:9px;font-size:13px;font-family:inherit;background:#fff;color:#1e293b;transition:border-color .15s,box-shadow .15s}
.sa-champ:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
textarea.sa-champ{resize:vertical;min-height:44px}
.sa-choix{display:flex;flex-wrap:wrap;gap:6px;align-items:center}
.sa-pastille{position:relative;margin:0!important;cursor:pointer;display:inline-block!important;text-transform:none!important;font-weight:400!important;font-size:12.5px!important}
.sa-dlg .sa-pastille input{width:auto}
.sa-pastille input{position:absolute;opacity:0;pointer-events:none}
.sa-pastille span{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border:1px solid var(--bord);border-radius:20px;font-size:12.5px;color:#334155;background:#fff;transition:all .15s;user-select:none}
.sa-pastille span i{width:7px;height:7px;border-radius:50%;flex:none}
.sa-pastille span svg{color:#94a3b8}
.sa-pastille:hover span{border-color:#9fbfe0}
.sa-pastille input:checked+span{border-color:var(--accent);background:#eaf2fb;color:var(--accent);font-weight:600}
.sa-pastille input:checked+span svg{color:var(--accent)}
.sa-pastille input:focus-visible+span{box-shadow:0 0 0 3px rgba(46,116,181,.2)}
.sa-pastille-ajout{border:1px dashed #b6c6d8;background:none;border-radius:20px;padding:6px 12px;font-size:12.5px;color:var(--accent);cursor:pointer}
.sa-pastille-ajout:hover{border-color:var(--accent);background:#f3f8fd}
.sa-date{width:150px;padding:5px 9px}
.sa-ligne2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.sa-raccourcis{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.sa-raccourcis button{background:#f1f5f9;border:none;border-radius:7px;padding:4px 10px;font-size:11.5px;color:#475569;cursor:pointer}
.sa-raccourcis button:hover,.sa-raccourcis button.actif{background:#e6f0fa;color:var(--accent)}
.sa-resume{font-size:12.5px;color:#334155;margin:10px 0 0;padding:8px 11px;background:#f6f9fc;border-radius:9px}
.sa-resume:empty{display:none}
.sa-resume b{color:var(--bleu)}
.sa-pied{display:flex;gap:8px;justify-content:flex-end;margin-top:18px}
@media(max-width:560px){.sa-ligne2{grid-template-columns:1fr}}
/* Tableau des signalements et absences */
.sg-table{width:100%;border-collapse:collapse;font-size:12.5px}
.sg-table th{text-align:left;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--gris);padding:8px 8px;border-bottom:1px solid var(--bord)}
.sg-table td{padding:9px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#1e293b}
.sg-table tr:last-child td{border-bottom:none}
.sg-table .sec{display:block;font-size:11.5px;color:var(--gris);margin-top:2px}
.sg-table .nw{white-space:nowrap}
.abs-type{background:#e6f0fa;color:var(--accent)}
.ib-ko{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;border:none;background:none;color:var(--ko);cursor:pointer}
.ib-ko:hover{background:#fee2e2}
@media(max-width:600px){.justif-ligne{flex-wrap:wrap}.justif-date{width:100%}.justif-motif{flex:1}.justif-texte{flex-basis:100%;order:3}}
@media(max-width:768px){
    .grille{grid-template-columns:1fr}
    .tache-confirm{flex-direction:column;gap:8px}
    .confirm-actions{align-self:flex-end}
    dialog{width:min(480px,92vw);padding:16px}
    .section-titre{font-size:13px;flex-wrap:wrap}
    .hist-line{flex-direction:column;gap:2px}
}
</style>
@endpush
@section('contenu')
<div class="sa-tete">
    <h2>Mes activités</h2>
    <div class="sa-actions">
        <button type="button" class="sa-act" data-sans-icone onclick="ouvrirEvt()" title="Incident, coupure, imprévu… repris dans le reporting et le Daily">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
            Signaler un événement</button>
        <button type="button" class="sa-act" data-sans-icone onclick="ouvrirAbs()" title="Congé, mission, maladie, formation : repris dans les rapports d'assiduité">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Déclarer une absence</button>
    </div>
</div>
<div class="onglets-page">
    <a href="{{ route('taches.index') }}">Mes tâches</a>
    <a href="{{ route('saisie.index') }}" class="actif">Suivi de saisie</a>
</div>
<div class="crumb">{{ now()->translatedFormat('l j F Y') }} - confirmation quotidienne</div>

@if(session('succes'))
<div class="alerte ok">{{ session('succes') }}</div>
@endif
@if(session('erreur'))
<div class="alerte ko">{{ session('erreur') }}</div>
@endif
@if($errors->any())
<div class="alerte ko">{{ $errors->first() }}</div>
@endif

<div class="section">
    <div class="section-titre">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="4"/><polyline points="8 12 11 15 16 9"/></svg>
        T&acirc;ches planifi&eacute;es aujourd'hui ({{ $tachesDuJour->count() }})
    </div>
    @forelse($tachesDuJour as $t)
        @if($loop->first)
        <div class="carte">
        @endif
        <div class="tache-confirm">
            <div class="tache-info">
                <div class="tache-intitule">
                    <span class="typ {{ strtolower($t->type) }}">{{ $t->type }}</span>
                    {{ $t->intitule }}
                </div>
                <div class="tache-meta">
                    {{ $t->projet->libelle ?? 'Sans projet' }}
                    @if($t->sprint) &middot; Sprint {{ $t->sprint->numero }} @endif
                    &middot; <x-voyant :tache="$t" /> @if($t->echeance) &Eacute;ch. {{ $t->echeance->format('d/m') }} @endif
                    @if($t->points) &middot; {{ $t->points }} pts @endif
                </div>
            </div>
            <div class="confirm-actions">
                @if($confirmations->has($t->id))
                    <span class="badge-done badge-{{ $confirmations->get($t->id) }}">
                        {{ $confirmations->get($t->id) === 'confirmee' ? 'Réalisée' : 'Infirmée' }}
                    </span>
                @else
                    <form method="POST" action="{{ route('saisie.confirmer', $t) }}" style="display:inline-block">
                        @csrf
                        <input type="hidden" name="statut" value="confirmee">
                        <button type="submit" class="btn-ic-s ok"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Fait</button>
                    </form>
                    <button type="button" class="btn-ic-s ko" onclick="infirmer({{ $t->id }}, {{ Js::from($t->intitule) }})"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>Non fait</button>
                @endif
            </div>
        </div>
        @if($loop->last)
        </div>
        @endif
    @empty
        <div class="carte" style="text-align:center;color:var(--gris);font-size:13px;padding:24px">
            Aucune t&acirc;che planifi&eacute;e pour aujourd'hui.
        </div>
    @endforelse
</div>

@if($jours->count() > 0)
<div class="section">
    <div class="section-titre">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Jours &agrave; justifier ({{ $jours->count() }})
    </div>
    @if($jours->count() > 0)
    <div class="carte">
        @foreach($jours as $j)
        <form method="POST" action="{{ route('saisie.justifier', $j) }}" class="justif-ligne">
            @csrf
            @method('PATCH')
            <div class="justif-date">{{ ucfirst($j->date->translatedFormat('D d/m/Y')) }}</div>
            <select name="motif_type" class="justif-motif">
                <option value="autre">Autre</option>
                <option value="conge">Cong&eacute;</option>
                <option value="mission">Mission</option>
                <option value="maladie">Maladie</option>
            </select>
            <input name="justification" placeholder="Précisez…" required maxlength="500" class="justif-texte">
            <button type="submit" class="btn-ic-s accent"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>Envoyer</button>
        </form>
        @endforeach
    </div>
    @else
    <div class="carte" style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Aucun jour &agrave; justifier.</div>
    @endif
</div>
@endif

@if(auth()->user()->estPilote() && isset($sanseSaisie) && $sanseSaisie->count() > 0)
<div class="section">
    <div class="section-titre" style="justify-content:space-between">
        <span style="display:flex;align-items:center;gap:8px">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            Collaborateurs sans saisie hier ({{ $sanseSaisie->count() }})
        </span>
        <button type="button" class="btn-ic-s accent" onclick="document.getElementById('dlg-notif').showModal()">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Notifier
        </button>
    </div>
    <div class="carte">
        @foreach($sanseSaisie as $u)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px">
            <span style="font-weight:600">{{ $u->nomComplet() }}</span>
            <div style="display:flex;align-items:center;gap:8px">
                @if(in_array((int)$u->id, $rappelsAujourdhui, true))
                <span style="display:inline-flex;align-items:center;gap:4px;color:var(--ok);font-size:11px;font-weight:600" title="Rappel envoyé aujourd'hui">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="var(--ok)" stroke="var(--ok)" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                    Envoy&eacute;
                </span>
                @else
                <form method="POST" action="{{ route('saisie.rappeler') }}" style="display:inline">@csrf
                    <input type="hidden" name="user_id" value="{{ $u->id }}">
                    <button type="submit" class="btn-ic-s accent"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>Rappel</button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@if(auth()->user()->estPilote() && $justificationsEnAttente->isNotEmpty())
<div class="section">
    <div class="section-titre">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Justifications re&ccedil;ues ({{ $justificationsEnAttente->count() }})
    </div>
    <div class="carte">
        @foreach($justificationsEnAttente as $js)
        <div style="padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:12.5px">
            <strong>{{ $js->user?->nomComplet() ?? '—' }}</strong>
            <span style="color:var(--gris);margin-left:6px">{{ ucfirst($js->date->translatedFormat('D d/m/Y')) }}</span>
            @if($js->justification)<div style="font-size:12px;color:#475569;margin-top:2px">{{ $js->justification->contenu }}</div>@endif
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Mes signalements et absences (30 derniers jours, à venir) --}}
<div class="section">
    <div class="section-titre">Mes signalements et absences <span style="font-size:12px;font-weight:400;color:var(--gris)">30 derniers jours et à venir</span></div>
    @if($evenementsRecents->isEmpty() && $absences->isEmpty())
        <div class="carte" style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Rien de signalé ni déclaré sur cette période.</div>
    @else
    <div class="carte" style="padding:4px 16px">
        <table class="sg-table">
            <thead><tr><th>Date</th><th>Nature</th><th>Détail</th><th>Suite</th><th></th></tr></thead>
            <tbody>
            @php
                $lignes = $evenementsRecents->map(fn($e) => ['tri' => $e->date->format('Y-m-d') . '1', 'e' => $e])
                    ->concat($absences->map(fn($a) => ['tri' => $a->date_debut->format('Y-m-d') . '0', 'a' => $a]))
                    ->sortByDesc('tri');
            @endphp
            @foreach($lignes as $l)
                @if(isset($l['e']))
                    @php $e = $l['e']; $c = $e->couleurType(); @endphp
                    <tr>
                        <td class="nw">{{ $e->date->translatedFormat('d M Y') }}</td>
                        <td><span class="evt-type" style="background:{{ $c['fond'] }};color:{{ $c['texte'] }}">{{ $e->labelType() }}</span></td>
                        <td>{{ $e->description }}
                            @if($e->action_engagee)<span class="sec">Action engagée : {{ $e->action_engagee }}</span>@endif
                            @if($e->impact)<span class="sec">Impact : {{ $e->impact }}</span>@endif</td>
                        <td>@if($e->estTraite())<span class="etat ok">Traité</span><span class="sec">{{ $e->traitePar?->nomComplet() }} : {{ $e->recommandation }}</span>
                            @else<span class="etat warn">En attente de réaction</span>@endif</td>
                        <td></td>
                    </tr>
                @else
                    @php
                        $a = $l['a'];
                        $etat = $a->date_fin->lt(today()) ? ['mut', 'Terminée'] : ($a->date_debut->gt(today()) ? ['bleu', 'À venir'] : ['ok', 'En cours']);
                        $nbJ = $a->date_debut->diffInDays($a->date_fin) + 1;
                    @endphp
                    <tr>
                        <td class="nw">{{ $a->date_debut->translatedFormat('d M') }} → {{ $a->date_fin->translatedFormat('d M Y') }}</td>
                        <td><span class="evt-type abs-type">Absence · {{ $a->labelType() }}</span></td>
                        <td>{{ $nbJ }} jour{{ $nbJ > 1 ? 's' : '' }}@if($a->motif) · {{ $a->motif }}@endif
                            @if($a->declare_par && (int) $a->declare_par !== (int) $a->user_id)<span class="sec">Déclarée par {{ $a->declarant?->nomComplet() }}</span>@endif</td>
                        <td><span class="etat {{ $etat[0] }}">{{ $etat[1] }}</span></td>
                        <td style="text-align:right">
                            @if($a->date_debut->isFuture())
                            <form method="POST" action="{{ route('saisie.absence.annuler', $a) }}" style="display:inline" onsubmit="return demanderMotifSuppression(this,'cette absence')">@csrf @method('DELETE')
                                <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                                <button type="submit" class="ib-ko" data-sans-icone title="Annuler l'absence" aria-label="Annuler l'absence"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@if(auth()->user()->estPilote() && isset($absencesEquipe) && $absencesEquipe->count() > 0)
<div class="section">
    <div class="section-titre">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Absences &eacute;quipe (&agrave; venir / en cours)
    </div>
    <div class="carte">
        @foreach($absencesEquipe as $ae)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px">
            <span><strong>{{ $ae->user->nomComplet() }}</strong> - {{ $ae->labelType() }} &middot; {{ $ae->date_debut->format('d/m') }} &rarr; {{ $ae->date_fin->format('d/m') }}</span>
            @if($ae->motif)<span style="color:var(--gris);font-size:11px">{{ $ae->motif }}</span>@endif
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="section">
    <div class="section-titre">Historique (30 derniers jours)</div>
    <div class="carte">
        @forelse($historique as $h)
        @php
            $cj = $confParJour->get($h->date->format('Y-m-d'), collect());
            $nbConf = (int) ($cj['confirmee'] ?? 0);
            $nbInf = (int) ($cj['infirmee'] ?? 0);
            $justif = $h->justification;
        @endphp
        <div class="hist-line" style="flex-wrap:wrap">
            <span style="min-width:110px">{{ $h->date->translatedFormat('D d/m/Y') }}</span>
            <span class="st-{{ $h->statut }}" style="font-weight:600">{{ ucfirst(str_replace('_',' ',$h->statut)) }}</span>
            <span style="font-size:11px;color:var(--gris);margin-left:auto">
                @if($nbConf > 0){{ $nbConf }} t&acirc;che(s) r&eacute;alis&eacute;e(s)@endif
                @if($nbInf > 0) &middot; {{ $nbInf }} infirm&eacute;e(s)@endif
                @if($nbConf === 0 && $nbInf === 0 && $h->statut === 'saisi')Signal&eacute;@endif
            </span>
            @if($justif)
            <div style="width:100%;font-size:11px;color:var(--accent);margin-top:2px;padding-left:110px">Motif : {{ $justif->contenu }}</div>
            @endif
        </div>
        @empty
        <div style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Pas encore d'historique.</div>
        @endforelse
    </div>
</div>

<dialog id="dlg-infirm">
    <h3 style="color:var(--bleu);margin-bottom:4px">Infirmer une t&acirc;che</h3>
    <p id="infirm-label" style="font-size:13px;color:#1e293b;margin-bottom:12px;font-weight:600"></p>
    <form method="POST" id="infirm-form">
        @csrf
        <input type="hidden" name="statut" value="infirmee">
        <label>Motif <span class="req">*</span></label>
        <textarea name="motif" required placeholder="Pourquoi cette t&acirc;che n'a pas &eacute;t&eacute; r&eacute;alis&eacute;e ?"></textarea>
        <label>Plan d'action (optionnel)</label>
        <textarea name="plan_action" placeholder="Actions correctives pr&eacute;vues..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn" style="background:var(--ko)">Confirmer l'infirmation</button>
        </div>
    </form>
</dialog>

<dialog id="dlg-abs" class="sa-dlg">
    <div class="sa-dlg-tete">
        <span class="sa-dlg-ic"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="3"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
        <div><h3>Déclarer une absence</h3><p>Les jours déclarés ne comptent pas comme saisies manquantes et apparaissent dans les rapports d'assiduité.</p></div>
    </div>
    <form method="POST" action="{{ route('saisie.absence') }}" id="form-abs">
        @csrf
        @if(auth()->user()->estPilote())
        <label class="sa-lab" for="abs-user">Pour</label>
        <select name="user_id" id="abs-user" class="sa-champ">
            <option value="">Moi-même</option>
            @foreach(\App\Models\User::where('actif',true)->whereNotIn('role',['direction','invite'])->orderBy('name')->get() as $collab)
            <option value="{{ $collab->id }}">{{ $collab->nomComplet() }}</option>
            @endforeach
        </select>
        @endif
@php
            $icAbs = ['conge' => '<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
                      'mission' => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>',
                      'maladie' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
                      'formation' => '<path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
                      'autre' => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>'];
            $natures = $typesAbsence->isNotEmpty() ? $typesAbsence->pluck('libelle', 'code') : collect(['conge' => 'Congé', 'mission' => 'Mission', 'maladie' => 'Maladie', 'formation' => 'Formation', 'autre' => 'Autre']);
        @endphp
        <label class="sa-lab" for="abs-type">Nature <span class="req">*</span></label>
        <select name="type" id="abs-type" required data-pastilles data-pastille-ajout="+ Autre nature…"
            @unless(auth()->user()->aRole('direction')) data-modalite="type_absence" data-modalite-url="{{ route('modalites.store') }}" data-modalite-nom="une nature d'absence" @endunless>
            @foreach($natures as $code => $lib)
            <option value="{{ $code }}" @selected($loop->first) data-icone="{{ $icAbs[$code] ?? '<circle cx="12" cy="12" r="4"/>' }}">{{ $lib }}</option>
            @endforeach
        </select>
        <div class="sa-ligne2">
            <div><label class="sa-lab" for="abs-du">Du <span class="req">*</span></label>
                <input type="date" name="date_debut" id="abs-du" class="sa-champ" required min="{{ today()->format('Y-m-d') }}" value="{{ today()->format('Y-m-d') }}"></div>
            <div><label class="sa-lab" for="abs-au">Au <span class="req">*</span></label>
                <input type="date" name="date_fin" id="abs-au" class="sa-champ" required min="{{ today()->format('Y-m-d') }}" value="{{ today()->format('Y-m-d') }}"></div>
        </div>
        <div class="sa-raccourcis">
            <button type="button" data-sans-icone data-jours="1">1 jour</button>
            <button type="button" data-sans-icone data-jours="2">2 jours</button>
            <button type="button" data-sans-icone data-jours="5">1 semaine</button>
            <button type="button" data-sans-icone data-jours="10">2 semaines</button>
        </div>
        <p class="sa-resume" id="abs-resume"></p>
        <label class="sa-lab" for="abs-motif">Motif <span class="req">*</span></label>
        <textarea name="motif" id="abs-motif" class="sa-champ" rows="2" required minlength="3" maxlength="500" placeholder="Ex. : mission chez le client à Bouaké, congé annuel, arrêt maladie…"></textarea>
        <div class="sa-pied">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn" data-sans-icone style="display:inline-flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>Déclarer l'absence</button>
        </div>
    </form>
</dialog>

<dialog id="dlg-evt" class="sa-dlg">
    <div class="sa-dlg-tete">
        <span class="sa-dlg-ic"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg></span>
        <div><h3>Signaler un événement</h3><p>Tout fait qui a pesé sur la journée. Il est repris dans le reporting de la période et les pilotes en sont prévenus.</p></div>
    </div>
    <form method="POST" action="{{ route('saisie.evenement') }}" id="form-evt">
        @csrf
        @php
            $typesEvt = (isset($typesEvenement) && $typesEvenement->count()) ? $typesEvenement->sortBy(fn($t) => ($t->code === 'autre' ? '1' : '0') . $t->libelle)->values()->map(fn($t) => ['code' => $t->code, 'libelle' => $t->libelle, 'c' => $t->couleur_texte ?: '#64748b'])
                : collect([['code' => 'interruption_service', 'libelle' => 'Interruption de service', 'c' => '#b91c1c'], ['code' => 'reunion_exceptionnelle', 'libelle' => 'Réunion exceptionnelle', 'c' => '#1d4ed8'], ['code' => 'autre', 'libelle' => 'Autre', 'c' => '#64748b']]);
        @endphp
        <label class="sa-lab" for="evt-type">Nature <span class="req">*</span></label>
        <select name="type" id="evt-type" required data-pastilles data-pastille-ajout="+ Autre nature…"
            @unless(auth()->user()->aRole('direction')) data-modalite="type_evenement" data-modalite-url="{{ route('modalites.store') }}" data-modalite-nom="une nature d'événement" @endunless>
            @foreach($typesEvt as $te)
            <option value="{{ $te['code'] }}" @selected($loop->first) data-couleur="{{ $te['c'] }}">{{ $te['libelle'] }}</option>
            @endforeach
        </select>
        <span class="sa-lab">Quand <span class="req">*</span></span>
        <div class="sa-choix">
            <label class="sa-pastille"><input type="radio" name="quand" value="{{ today()->format('Y-m-d') }}" checked><span>Aujourd'hui</span></label>
            <label class="sa-pastille"><input type="radio" name="quand" value="{{ today()->subDay()->format('Y-m-d') }}"><span>Hier</span></label>
            <label class="sa-pastille"><input type="radio" name="quand" value="autre"><span>Autre jour</span></label>
            <input type="date" name="date" id="evt-date" class="sa-champ sa-date" value="{{ today()->format('Y-m-d') }}" max="{{ today()->format('Y-m-d') }}" min="{{ today()->subDays(7)->format('Y-m-d') }}" hidden>
        </div>
        <label class="sa-lab" for="evt-desc">Que s'est-il passé ? <span class="req">*</span></label>
        <textarea name="description" id="evt-desc" class="sa-champ" rows="3" required maxlength="500" placeholder="Ex. : coupure internet de 10 h à 14 h, impossible d'accéder au serveur client."></textarea>
        <div class="sa-ligne2 sa-ligne2-large">
            <div><label class="sa-lab" for="evt-action">Action engagée <span class="fac">facultatif</span></label>
                <input name="action_engagee" id="evt-action" class="sa-champ" maxlength="500" placeholder="Ex. : bascule sur la 4G"></div>
            <div><label class="sa-lab" for="evt-impact">Impact <span class="fac">facultatif</span></label>
                <input name="impact" id="evt-impact" class="sa-champ" maxlength="255" placeholder="Ex. : 3 collaborateurs, 4 h perdues"></div>
        </div>
        <div class="sa-pied">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Signaler</button>
        </div>
    </form>
</dialog>

{{-- Dialog notification multi-collaborateurs (pilotes) --}}
@if(auth()->user()->estPilote() && isset($collaborateurs) && $collaborateurs->count() > 0)
<dialog id="dlg-notif" style="width:min(460px,92vw)">
    <h3 style="color:var(--bleu);margin-bottom:4px;display:flex;align-items:center;gap:8px">
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Notifier des collaborateurs
    </h3>
    <p style="font-size:12px;color:var(--gris);margin-bottom:12px">S&eacute;lectionnez les destinataires et r&eacute;digez votre message.</p>
    <form method="POST" action="{{ route('saisie.notifier') }}" id="form-notif">@csrf
        <div style="display:flex;gap:6px;margin-bottom:8px;flex-wrap:wrap">
            <button type="button" class="btn-ic-s accent" onclick="toggleAllNotif(true)" style="font-size:10px;padding:2px 8px">Tout s&eacute;lectionner</button>
            <button type="button" class="btn-ic-s" onclick="toggleAllNotif(false)" style="font-size:10px;padding:2px 8px">Tout d&eacute;s&eacute;lectionner</button>
        </div>
        <div style="max-height:200px;overflow-y:auto;border:1px solid var(--bord);border-radius:8px;padding:8px 10px;margin-bottom:12px">
            @foreach($collaborateurs as $c)
            <label style="display:flex;align-items:center;gap:8px;padding:5px 0;font-size:13px;cursor:pointer;border-bottom:1px solid #f1f5f9">
                <input type="checkbox" name="user_ids[]" value="{{ $c->id }}" class="notif-cb" style="accent-color:var(--accent);width:16px;height:16px">
                <span style="font-weight:600">{{ $c->nomComplet() }}</span>
                @if($c->titre_poste) <span style="font-size:10.5px;color:var(--gris)">- {{ $c->titre_poste }}</span> @endif
            </label>
            @endforeach
        </div>
        <label style="font-size:12px;color:var(--gris);display:block;margin-bottom:4px">Message <span style="color:var(--ko);font-weight:700">*</span></label>
        <div class="rte-toolbar" style="display:flex;gap:2px;padding:4px 6px;background:#f8fafc;border:1px solid var(--bord);border-bottom:none;border-radius:8px 8px 0 0">
            <button type="button" onmousedown="event.preventDefault()" onclick="rteNotif('bold')" title="Gras" style="background:none;border:1px solid transparent;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:13px;color:var(--bleu);font-weight:700">G</button>
            <button type="button" onmousedown="event.preventDefault()" onclick="rteNotif('italic')" title="Italique" style="background:none;border:1px solid transparent;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:13px;color:var(--bleu);font-style:italic">I</button>
            <button type="button" onmousedown="event.preventDefault()" onclick="rteNotif('underline')" title="Souligné" style="background:none;border:1px solid transparent;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:13px;color:var(--bleu);text-decoration:underline">S</button>
            <button type="button" onmousedown="event.preventDefault()" onclick="rteNotif('insertUnorderedList')" title="Liste à puces" style="background:none;border:1px solid transparent;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:13px;color:var(--bleu)">&#8226;</button>
            <button type="button" onmousedown="event.preventDefault()" onclick="rteNotif('insertOrderedList')" title="Liste numérotée" style="background:none;border:1px solid transparent;border-radius:4px;padding:3px 8px;cursor:pointer;font-size:13px;color:var(--bleu)">1.</button>
        </div>
        <div id="notif-msg-rte" contenteditable="true" placeholder="Ex : Merci de mettre à jour votre saisie quotidienne..." style="min-height:70px;max-height:160px;overflow-y:auto;padding:8px 10px;border:1px solid var(--bord);border-radius:0 0 8px 8px;font-size:13px;line-height:1.5;outline:none;background:#fff"></div>
        <input type="hidden" name="message" id="notif-msg-hidden">
        <style>
        #notif-msg-rte:empty:before{content:attr(placeholder);color:#94a3b8;pointer-events:none}
        #notif-msg-rte:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
        #notif-msg-rte ul,#notif-msg-rte ol{margin:4px 0;padding-left:20px}
        </style>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button type="submit" class="btn" id="btn-notif-submit">Envoyer</button>
        </div>
    </form>
</dialog>
@endif
@endsection

@push('scripts')
<script>
function toggleAllNotif(state){
    document.querySelectorAll('.notif-cb').forEach(cb => cb.checked = state);
}
function rteNotif(cmd){
    document.execCommand(cmd, false, null);
    document.getElementById('notif-msg-rte').focus();
}
document.getElementById('form-notif')?.addEventListener('submit', function(){
    var rte = document.getElementById('notif-msg-rte');
    var txt = rte.innerHTML.replace(/<br\s*\/?>\s*$/i,'').trim();
    if(!txt || txt === '<br>'){rte.focus();event.preventDefault();return;}
    document.getElementById('notif-msg-hidden').value = txt;
});
function ouvrirEvt(){
    var d = document.getElementById('dlg-evt'); d.querySelector('form').reset(); majDateEvt(); d.showModal();
    setTimeout(function(){ document.getElementById('evt-desc').focus(); }, 50);
}
function majDateEvt(){
    var f = document.getElementById('form-evt'), q = f.querySelector('input[name=quand]:checked').value, d = document.getElementById('evt-date');
    d.hidden = q !== 'autre';
    if (q !== 'autre') d.value = q; else d.focus();
}
document.querySelectorAll('#form-evt input[name=quand]').forEach(function(r){ r.addEventListener('change', majDateEvt); });
function ouvrirAbs(){
    var d = document.getElementById('dlg-abs'); d.querySelector('form').reset(); resumeAbs(); d.showModal();
}
/* Résumé de la période d'absence : durée et jours ouvrés (lundi à vendredi) */
function resumeAbs(){
    var du = document.getElementById('abs-du'), au = document.getElementById('abs-au'), r = document.getElementById('abs-resume');
    if (du.value && (!au.value || au.value < du.value)) au.value = du.value;
    au.min = du.value || au.min;
    if (!du.value || !au.value) { r.textContent = ''; return; }
    var a = new Date(du.value + 'T12:00'), b = new Date(au.value + 'T12:00'), n = Math.round((b - a) / 864e5) + 1, ouvres = 0;
    for (var d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) if (d.getDay() % 6) ouvres++;
    var f = function(x){ return x.toLocaleDateString('fr-FR', {weekday: 'short', day: 'numeric', month: 'short'}); };
    r.innerHTML = '<b>' + n + ' jour' + (n > 1 ? 's' : '') + '</b>' + (n > 1 ? ', du ' + f(a) + ' au ' + f(b) : ', le ' + f(a)) + ' · ' + ouvres + ' jour' + (ouvres > 1 ? 's' : '') + ' ouvré' + (ouvres > 1 ? 's' : '');
    document.querySelectorAll('.sa-raccourcis button').forEach(function(x){ x.classList.toggle('actif', +x.dataset.jours === ouvres && n === ouvresVersCalendaire(a, ouvres)); });
}
function ouvresVersCalendaire(a, ouvres){ var n = 0, d = new Date(a), k = 0; while (k < ouvres) { if (d.getDay() % 6) k++; n++; d.setDate(d.getDate() + 1); } return n; }
document.getElementById('abs-du').addEventListener('input', resumeAbs);
document.getElementById('abs-au').addEventListener('input', resumeAbs);
document.querySelectorAll('.sa-raccourcis button').forEach(function(x){ x.addEventListener('click', function(){
    var du = document.getElementById('abs-du'); if (!du.value) return;
    var d = new Date(du.value + 'T12:00'), k = 0, j = +x.dataset.jours;
    while (true) { if (d.getDay() % 6) k++; if (k >= j) break; d.setDate(d.getDate() + 1); }
    document.getElementById('abs-au').value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    resumeAbs();
}); });
function infirmer(id, label) {
    document.getElementById('infirm-label').textContent = label;
    document.getElementById('infirm-form').action = @js(url('saisie')) + '/' + id + '/confirmer';
    document.getElementById('dlg-infirm').showModal();
}
</script>
@endpush
