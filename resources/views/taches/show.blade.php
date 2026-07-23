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

<div class="detail-header">
    <div>
        <h2><span class="typ {{ strtolower($tache->type) }}">{{ $tache->type }}</span> {{ $tache->intitule }}</h2>
        <div class="crumb">Mes activités &rsaquo; Détail</div>
    </div>
    <div style="display:flex;gap:6px">
        <a href="{{ route('taches.index') }}" class="btn sec">Retour au kanban</a>
        @if($tache->statut !== 'bloque')
        <button class="btn danger" onclick="document.getElementById('dlg-blocage').showModal()">Signaler un blocage</button>
        @endif
    </div>
</div>

<div class="carte">
    <div class="detail-meta">
        <div class="item"><div class="lbl">Projet</div>{{ $tache->projet?->libelle ?? '—' }}</div>
        <div class="item"><div class="lbl">Sprint</div>{{ $tache->sprint ? 'Sprint ' . $tache->sprint->numero : '—' }}</div>
        <div class="item"><div class="lbl">Priorité</div>{{ ucfirst($tache->priorite) }}</div>
        <div class="item"><div class="lbl">Statut</div><span class="pill p-{{ $tache->statut === 'termine' ? 'ok' : ($tache->statut === 'bloque' ? 'bloque' : 'cours') }}">{{ ucfirst(str_replace('_',' ',$tache->statut)) }}</span></div>
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
    <h3>{!! $svg('<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>') !!} Blocages ({{ $blocagesActifs }})</h3>
    @forelse($tache->blocages->sortByDesc('created_at') as $b)
    <div class="blocage-card {{ $b->date_levee ? 'leve' : '' }}">
        <strong>{{ $b->motif }}</strong>
        @if($b->leveur_pressenti)<br><small>Leveur pressenti : {{ $b->leveur_pressenti }}</small>@endif
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

{{-- Commentaires --}}
<div class="section">
    <h3>{!! $svg('<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>') !!} Commentaires ({{ $tache->commentaires->count() }})</h3>
    <div class="carte" style="padding:10px 16px">
        @forelse($tache->commentaires->sortByDesc('created_at') as $c)
        <div class="com">
            <span class="auteur">{{ $c->user->nomComplet() }}</span>
            <span class="date">{{ $c->created_at->format('d/m/Y H:i') }}</span>
            <div class="contenu">{{ $c->contenu }}</div>
        </div>
        @empty
        <p style="color:var(--gris);font-size:13px;padding:8px 0">Aucun commentaire.</p>
        @endforelse

        <form method="POST" action="{{ route('commentaires.store', $tache) }}" class="form-inline">@csrf
            <textarea name="contenu" placeholder="Ajouter un commentaire…" required></textarea>
            <button class="btn" style="align-self:flex-end">Envoyer</button>
        </form>
    </div>
</div>

{{-- Dialog blocage --}}
<dialog id="dlg-blocage">
    <h3 style="color:var(--ko);margin-bottom:4px">Signaler un blocage</h3>
    <form method="POST" action="{{ route('blocages.store', $tache) }}">@csrf
        <label>Motif du blocage *</label>
        <textarea name="motif" required placeholder="Décrivez ce qui vous bloque…"></textarea>
        <label>Qui pourrait débloquer ? (optionnel)</label>
        <input name="leveur_pressenti" placeholder="Nom ou rôle">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn danger">Signaler</button>
        </div>
    </form>
</dialog>
@endsection
