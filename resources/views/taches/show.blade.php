@extends('layouts.app')
@section('titre', $tache->intitule)
@push('styles')
<style>
.detail-header{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
.detail-meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;margin:14px 0}
.detail-meta .item{font-size:12.5px}.detail-meta .lbl{color:var(--gris);font-size:11px;text-transform:uppercase}
.section{margin-top:20px}
.section h3{font-size:15px;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:8px}
.blocage-card{background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px;margin-bottom:8px;font-size:13px}
.blocage-card.leve{background:#f0fdf4;border-color:#bbf7d0}
.blocage-card .meta-b{font-size:11px;color:var(--gris);margin-top:6px;display:flex;justify-content:space-between}
.com{border-bottom:1px solid var(--bord);padding:10px 0;font-size:13px}
.com:last-child{border:none}
.com .auteur{font-weight:600;color:var(--bleu);font-size:12px}
.com .date{font-size:11px;color:var(--gris);margin-left:8px}
.com .contenu{margin-top:4px;line-height:1.5}
.form-inline{display:flex;gap:8px;margin-top:10px}
.form-inline input,.form-inline textarea{flex:1;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;resize:vertical}
.form-inline textarea{min-height:60px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.danger{background:var(--ko)}
.btn.ok{background:var(--ok)}
.btn-ic{display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border-radius:10px;border:1.5px solid var(--bord);background:#fff;cursor:pointer;transition:all .15s;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap}
.btn-ic:hover{transform:scale(1.03);box-shadow:0 2px 8px rgba(0,0,0,.1)}
.btn-ic svg{flex:none;pointer-events:none}
.pill{padding:2px 9px;border-radius:10px;font-size:10.5px;font-weight:600}
.p-ok{background:#dcfce7;color:var(--ok)}.p-cours{background:#dbeafe;color:var(--accent)}.p-bloque{background:#fee2e2;color:var(--ko)}.p-attente{background:#fef3c7;color:#b45309}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
dialog{border:none;border-radius:14px;padding:24px;width:430px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
dialog input,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog textarea{min-height:70px;resize:vertical}
@media(max-width:900px){.detail-meta{grid-template-columns:1fr 1fr}}
@media(max-width:500px){.detail-meta{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte" style="background:#fef2f2;border:1px solid #fecaca;color:var(--ko)">{{ session('erreur') }}</div>@endif

{{-- Bandeau validation pour pilotes --}}
@if($tache->statut === 'a_valider' && auth()->user()->estPilote())
<div style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #93c5fd;border-radius:12px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div>
        <div style="font-weight:700;color:#1d4ed8;font-size:14px">En attente de votre validation</div>
        <div style="font-size:12px;color:#475569;margin-top:2px">{{ $tache->user?->nomComplet() }} a termin&eacute; cette t&acirc;che de sprint et demande votre validation.</div>
    </div>
    <div style="display:flex;gap:8px">
        <form method="POST" action="{{ route('taches.valider', $tache) }}" style="margin:0">@csrf
            <input type="hidden" name="decision" value="valider">
            <button class="btn ok" style="background:var(--ok)">Valider</button>
        </form>
        <button class="btn danger" onclick="document.getElementById('dlg-rejet').showModal()">Rejeter</button>
    </div>
</div>
@endif

@if($tache->statut === 'a_valider' && $tache->user_id === auth()->id())
<div style="background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;margin-bottom:16px;font-size:13px;color:#854d0e;display:flex;align-items:center;gap:10px">
    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    <span>En attente de validation par le pilote ou le DG.</span>
</div>
@endif

<div class="detail-header">
    <div>
        <h2><span class="typ {{ strtolower($tache->type) }}">{{ $tache->type }}</span> {{ $tache->intitule }}</h2>
        <div class="crumb">Mes activités &rsaquo; Détail</div>
    </div>
    <div style="display:flex;gap:5px;flex-wrap:wrap">
        <a href="{{ route('taches.index') }}" class="btn-ic" style="color:var(--accent);border-color:var(--accent)">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>Kanban
        </a>
        @php $sprintActif = !$tache->sprint_id || $tache->sprint?->statut === 'en_cours'; @endphp
        @if($sprintActif)
            @if(in_array($tache->statut, ['a_faire', 'en_cours']))
            <button class="btn-ic" id="btn-terminer" onclick="marquerTermine()" style="color:var(--ok);border-color:var(--ok);background:#f0fdf4">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg>Termin&eacute;
            </button>
            @endif
            @if($tache->statut !== 'bloque' && $tache->statut !== 'termine' && $tache->statut !== 'a_valider')
            <button class="btn-ic" onclick="document.getElementById('dlg-blocage').showModal()" style="color:var(--ko);border-color:var(--ko);background:#fef2f2">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>Blocage
            </button>
            @endif
        @else
            <span style="font-size:12px;color:var(--gris);padding:4px 10px;background:#f1f5f9;border-radius:6px">Sprint {{ $tache->sprint?->statut === 'planifie' ? 'non d&eacute;marr&eacute;' : 'termin&eacute;' }} — consultation uniquement</span>
        @endif
    </div>
</div>

<div class="carte">
    <div class="detail-meta">
        <div class="item"><div class="lbl">Assign&eacute; &agrave;</div><strong>{{ $tache->user?->nomComplet() ?? '—' }}</strong></div>
        <div class="item"><div class="lbl">Projet</div>{{ $tache->projet?->libelle ?? '—' }}</div>
        <div class="item"><div class="lbl">Sprint</div>{{ $tache->sprint ? 'Sprint ' . $tache->sprint->numero : '—' }}</div>
        <div class="item"><div class="lbl">Priorit&eacute;</div>{{ ucfirst($tache->priorite) }}</div>
        @php $statutPill = match($tache->statut) { 'termine' => 'p-ok', 'bloque' => 'p-bloque', 'a_valider' => 'p-attente', default => 'p-cours' }; @endphp
        <div class="item"><div class="lbl">Statut</div><span class="pill {{ $statutPill }}">{{ $tache->labelStatut() }}</span></div>
        <div class="item"><div class="lbl">Début</div>{{ $tache->date_debut?->format('d/m/Y') ?? '—' }}</div>
        <div class="item"><div class="lbl">Échéance</div>{{ $tache->echeance?->format('d/m/Y') ?? 'Non définie' }}</div>
        @if($tache->date_fin_reelle)
        <div class="item"><div class="lbl">Terminée le</div>{{ $tache->date_fin_reelle->format('d/m/Y') }}</div>
        <div class="item"><div class="lbl">Qualification</div>{{ ucfirst(str_replace('_',' ',$tache->qualification ?? '—')) }}</div>
        @endif
        @if($tache->effort_volontaire)
        <div class="item"><div class="lbl">Effort volontaire</div>Week-end/férié</div>
        @endif
    </div>
</div>

{{-- Blocages --}}
<div class="section">
    <h3><svg class="ic" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> Blocages ({{ $blocagesActifs }})</h3>
    @forelse($tache->blocages->sortByDesc('created_at') as $b)
    <div class="blocage-card {{ $b->date_levee ? 'leve' : '' }}">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;flex-wrap:wrap">
            <strong>{{ $b->motif }}</strong>
            @if($b->type)<span class="pill" style="background:#f1f5f9;color:#475569;font-size:10px">{{ $b->type }}</span>@endif
        </div>
        @if($b->intervenant)
        <div style="font-size:11.5px;margin-top:4px;color:#1d4ed8">
            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4-4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Intervenant proposé : <strong>{{ $b->intervenant->nomComplet() }}</strong>
        </div>
        @endif
        @if($b->recommandation)
        <div style="font-size:12px;margin-top:4px;color:#475569;background:#f8fafc;padding:6px 8px;border-radius:6px">
            <strong style="font-size:10px;text-transform:uppercase;color:var(--gris)">Recommandation</strong><br>{{ $b->recommandation }}
        </div>
        @endif
        @if($b->leveur_pressenti)<div style="font-size:11.5px;margin-top:3px;color:var(--gris)">Leveur pressenti : {{ $b->leveur_pressenti }}</div>@endif
        <div class="meta-b">
            <span>Signalé le {{ $b->date_blocage->format('d/m/Y H:i') }}</span>
            @if($b->date_levee)
                <span style="color:var(--ok)">Levé le {{ $b->date_levee->format('d/m/Y H:i') }} ({{ round($b->delaiHeures(), 1) }}h)</span>
            @else
                <form method="POST" action="{{ route('blocages.lever', $b) }}" style="display:inline">@csrf @method('PATCH')
                    <button class="btn sm ok">Lever ce blocage</button>
                </form>
            @endif
        </div>
    </div>
    @empty
    <p style="color:var(--gris);font-size:13px">Aucun blocage signalé.</p>
    @endforelse
</div>

{{-- Journal d'activité --}}
<div class="section">
    <h3><svg class="ic" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg> Journal d'activité ({{ $tache->commentaires->count() }})</h3>
    <div class="carte" style="padding:10px 16px">
        @forelse($tache->commentaires->sortByDesc('created_at') as $c)
        @php
            $ctx = $c->contexte ? (\App\Models\Commentaire::CONTEXTES[$c->contexte] ?? null) : null;
        @endphp
        <div class="com" style="{{ $ctx ? 'border-left:3px solid '.$ctx['color'].';padding-left:12px' : '' }}">
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">
                @if($ctx)<span style="font-size:11px;font-weight:700;color:{{ $ctx['color'] }}">{{ $ctx['icon'] }} {{ $ctx['label'] }}</span>@endif
                <span class="auteur">{{ $c->user?->nomComplet() ?? 'Système' }}</span>
                <span class="date">{{ $c->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="contenu">{{ $c->contenu }}</div>
        </div>
        @empty
        <p style="color:var(--gris);font-size:13px;padding:8px 0">Aucune activité enregistrée.</p>
        @endforelse

        <form method="POST" action="{{ route('commentaires.store', $tache) }}" class="form-inline">@csrf
            <textarea name="contenu" placeholder="Ajouter un commentaire…" required></textarea>
            <button class="btn" style="align-self:flex-end">Envoyer</button>
        </form>
    </div>
</div>

{{-- Dialog rejet validation --}}
@if($tache->statut === 'a_valider' && auth()->user()->estPilote())
<dialog id="dlg-rejet">
    <h3 style="color:var(--ko);margin-bottom:4px">Rejeter la validation</h3>
    <form method="POST" action="{{ route('taches.valider', $tache) }}">@csrf
        <input type="hidden" name="decision" value="rejeter">
        <label>Motif du rejet <span style="color:var(--ko)">*</span></label>
        <textarea name="motif" required placeholder="Expliquez pourquoi la t&acirc;che n'est pas valid&eacute;e…"></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn danger">Rejeter</button>
        </div>
    </form>
</dialog>
@endif

{{-- Dialog blocage --}}
<dialog id="dlg-blocage">
    <h3 style="color:var(--ko);margin-bottom:4px">Signaler un blocage</h3>
    <form method="POST" action="{{ route('blocages.store', $tache) }}">@csrf
        <label>Type de blocage <span style="color:var(--ko)">*</span></label>
        <select name="type" required style="width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px">
            @if(isset($typesBlocage) && $typesBlocage->count() > 0)
                @foreach($typesBlocage as $tb)
                <option value="{{ $tb->code }}">{{ $tb->libelle }}</option>
                @endforeach
            @else
                <option value="technique">Technique</option>
                <option value="ressource">Ressource</option>
                <option value="dependance">Dépendance</option>
                <option value="decision">Décision</option>
                <option value="externe">Externe</option>
            @endif
        </select>
        <label>Motif du blocage <span style="color:var(--ko)">*</span></label>
        <textarea name="motif" required placeholder="Décrivez ce qui vous bloque…"></textarea>
        <label>Intervenant proposé (optionnel)</label>
        <select name="intervenant_id" style="width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px">
            <option value="">— Aucun —</option>
            @foreach($utilisateurs as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        <div style="font-size:10.5px;color:var(--gris);margin-top:2px">L'intervenant sera notifié. Le DG ou le chargé des opérations appuiera la sollicitation.</div>
        <label>Recommandation (optionnel)</label>
        <textarea name="recommandation" placeholder="Suggestion pour résoudre ce blocage…" style="min-height:50px"></textarea>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn danger">Signaler</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
async function marquerTermine() {
    const btn = document.getElementById('btn-terminer');
    btn.disabled = true;
    btn.textContent = 'En cours…';
    try {
        const rep = await fetch('{{ route("taches.statut", $tache) }}', {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({statut: 'termine'})
        });
        const d = await rep.json();
        if (d.ok) {
            location.reload();
        } else {
            btn.textContent = 'Erreur';
        }
    } catch(e) {
        btn.textContent = 'Erreur réseau';
    }
}
</script>
@endpush
@endsection
