@extends('layouts.app')
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
@media(max-width:768px){.grille{grid-template-columns:1fr}.tache-confirm{flex-direction:column}}
</style>
@endpush
@section('contenu')
<h2>Suivi de saisie</h2>
<div class="crumb">{{ now()->translatedFormat('l j F Y') }} &mdash; confirmation quotidienne</div>

@if(session('succes'))
<div class="alerte ok">{{ session('succes') }}</div>
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
                    @if($t->echeance) &middot; &Eacute;ch. {{ $t->echeance->format('d/m') }} @endif
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

<div class="grille">
    @if(auth()->user()->estPilote())
    <div class="section">
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            &Eacute;v&eacute;nements du jour
        </div>
        @if($evenementsDuJour->count() > 0)
        <div class="carte">
            @foreach($evenementsDuJour as $e)
            @php $c = $e->couleurType(); @endphp
            <div style="padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:12px">
                <span class="evt-type" style="background:{{ $c['fond'] }};color:{{ $c['texte'] }}">{{ $e->labelType() }}</span>
                <span style="margin-left:6px">{{ $e->description }}</span>
                @if($e->action_engagee)
                <div style="font-size:11px;color:var(--gris);margin-top:2px">Action : {{ $e->action_engagee }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
        <button class="btn sm sec" onclick="document.getElementById('dlg-evt').showModal()" style="display:inline-flex;align-items:center;gap:5px;font-size:12px"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>Signaler</button>
    </div>
    @endif

    <div class="section">
        <div class="section-titre">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Jours &agrave; justifier ({{ $jours->count() }})
        </div>
        @if($jours->count() > 0)
        <div class="carte">
            @foreach($jours as $j)
            <form method="POST" action="{{ route('saisie.justifier', $j) }}" style="display:flex;gap:8px;align-items:flex-end;padding:8px 0;border-bottom:1px solid #f1f5f9;flex-wrap:wrap">
                @csrf
                @method('PATCH')
                <div style="flex:none;font-size:13px;font-weight:600;min-width:70px">{{ $j->date->format('d/m/Y') }}</div>
                <select name="motif_type" style="flex:none;width:110px;padding:6px 8px;border:1px solid var(--bord);border-radius:8px;font-size:12px">
                    <option value="autre">Autre</option>
                    <option value="conge">Cong&eacute;</option>
                    <option value="mission">Mission</option>
                    <option value="maladie">Maladie</option>
                </select>
                <input name="justification" placeholder="Précisez..." required style="flex:1;padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px">
                <button type="submit" class="btn-ic-s accent"><svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>Envoyer</button>
            </form>
            @endforeach
        </div>
        @else
        <div class="carte" style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Aucun jour &agrave; justifier.</div>
        @endif
    </div>
</div>

@if(auth()->user()->estPilote() && isset($sanseSaisie) && $sanseSaisie->count() > 0)
<div class="section">
    <div class="section-titre">
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        Collaborateurs sans saisie hier ({{ $sanseSaisie->count() }})
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

{{-- Section Absences --}}
<div class="section">
    <div class="section-titre" style="justify-content:space-between">
        <span style="display:flex;align-items:center;gap:8px">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="4" width="18" height="18" rx="4"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            Absences d&eacute;clar&eacute;es
        </span>
        <button class="btn sm sec" onclick="document.getElementById('dlg-abs').showModal()" style="font-size:12px">+ D&eacute;clarer</button>
    </div>
    @if(isset($absences) && $absences->count() > 0)
    <div class="carte">
        @foreach($absences as $a)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px">
            <div>
                <span style="font-weight:600">{{ $a->labelType() }}</span>
                <span style="color:var(--gris);margin-left:6px">du {{ $a->date_debut->format('d/m/Y') }} au {{ $a->date_fin->format('d/m/Y') }}</span>
                @if($a->motif) <span style="font-size:11px;color:var(--gris);margin-left:6px">&mdash; {{ $a->motif }}</span> @endif
            </div>
            @if($a->date_debut->isFuture())
            <form method="POST" action="{{ route('saisie.absence.annuler', $a) }}" style="display:inline">@csrf @method('DELETE')
                <button type="submit" class="btn-ic-s ko" style="font-size:10px">Annuler</button>
            </form>
            @endif
        </div>
        @endforeach
    </div>
    @else
    <div class="carte" style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Aucune absence d&eacute;clar&eacute;e.</div>
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
            <span><strong>{{ $ae->user->nomComplet() }}</strong> &mdash; {{ $ae->labelType() }} &middot; {{ $ae->date_debut->format('d/m') }} &rarr; {{ $ae->date_fin->format('d/m') }}</span>
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
            $nbConf = \App\Models\ConfirmationTache::where('user_id', $h->user_id)->whereDate('date', $h->date)->where('statut', 'confirmee')->count();
            $nbInf = \App\Models\ConfirmationTache::where('user_id', $h->user_id)->whereDate('date', $h->date)->where('statut', 'infirmee')->count();
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

<dialog id="dlg-abs">
    <h3 style="color:var(--bleu);margin-bottom:4px">D&eacute;clarer une absence</h3>
    <form method="POST" action="{{ route('saisie.absence') }}">
        @csrf
        @if(auth()->user()->estPilote())
        <label>Collaborateur</label>
        <select name="user_id">
            <option value="">Moi-m&ecirc;me</option>
            @foreach(\App\Models\User::where('actif',true)->whereNotIn('role',['direction','invite'])->orderBy('name')->get() as $collab)
            <option value="{{ $collab->id }}">{{ $collab->nomComplet() }}</option>
            @endforeach
        </select>
        @endif
        <label>Type <span class="req">*</span></label>
        <select name="type" required>
            <option value="conge">Cong&eacute;</option>
            <option value="mission">Mission</option>
            <option value="maladie">Maladie</option>
            <option value="formation">Formation</option>
            <option value="autre">Autre</option>
        </select>
        <label>Du <span class="req">*</span></label>
        <input type="date" name="date_debut" required min="{{ today()->format('Y-m-d') }}">
        <label>Au <span class="req">*</span></label>
        <input type="date" name="date_fin" required min="{{ today()->format('Y-m-d') }}">
        <label>Motif (optionnel)</label>
        <textarea name="motif" placeholder="Pr&eacute;cisez si n&eacute;cessaire..."></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">D&eacute;clarer</button>
        </div>
    </form>
</dialog>

@if(auth()->user()->estPilote())
<dialog id="dlg-evt">
    <h3 style="color:var(--bleu);margin-bottom:4px">Signaler un &eacute;v&eacute;nement</h3>
    <form method="POST" action="{{ route('saisie.evenement') }}">
        @csrf
        <label>Type <span class="req">*</span></label>
        <select name="type" required>
            @if(isset($typesEvenement) && $typesEvenement->count() > 0)
                @foreach($typesEvenement as $te)
                <option value="{{ $te->code }}">{{ $te->libelle }}</option>
                @endforeach
            @else
                <option value="interruption_service">Interruption de service</option>
                <option value="absence">Absence</option>
                <option value="condoleance">Condol&eacute;ances</option>
                <option value="reunion_exceptionnelle">R&eacute;union exceptionnelle</option>
                <option value="autre">Autre</option>
            @endif
        </select>
        <label>Description <span class="req">*</span></label>
        <textarea name="description" required placeholder="Ex : Coupure internet de 10h &agrave; 14h..."></textarea>
        <label>Action engag&eacute;e (optionnel)</label>
        <textarea name="action_engagee" placeholder="Ex : Mise en place d'un plan B, contact fournisseur..."></textarea>
        <label>Impact (optionnel)</label>
        <input name="impact" placeholder="Ex : 3 collaborateurs impact&eacute;s">
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
function infirmer(id, label) {
    document.getElementById('infirm-label').textContent = label;
    document.getElementById('infirm-form').action = '/saisie/' + id + '/confirmer';
    document.getElementById('dlg-infirm').showModal();
}
</script>
@endpush
