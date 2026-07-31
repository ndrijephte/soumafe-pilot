@extends('layouts.app')
@section('titre', $cr->titre)
@section('contenu')
<style>
.cr-show{max-width:900px;margin:0 auto}
.cr-show h2{font-size:1.3rem;font-weight:700;color:#1a1a2e;margin-bottom:4px}
.cr-show .sub{font-size:.85rem;color:#64748b;margin-bottom:20px}
.section{background:#fff;border-radius:12px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.section h3{font-size:1rem;font-weight:700;color:#1e3a5f;margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #e0e7ff}
.tbl{width:100%;border-collapse:collapse;font-size:.85rem}
.tbl th{background:#1F4E79;color:#fff;padding:8px 10px;text-align:left;font-weight:600}
.tbl td{padding:8px 10px;border-bottom:1px solid #e5e7eb}
.tbl tr:nth-child(even) td{background:#f8fafc}
.badge-ok{color:#059669;font-weight:700}
.badge-ko{color:#dc2626;font-weight:700}
.bar{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:20px}
.score-big{font-size:1.4rem;font-weight:800;color:#1e3a5f}
.score-pct{font-size:1rem;color:#64748b;margin-left:8px}
.meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.85rem;color:#475569;margin-bottom:14px}
.meta-grid span{font-weight:600;color:#1a1a2e}
</style>

<div class="cr-show">
    <h2>{{ $cr->titre }}</h2>
    <div class="sub">
        <span class="badge-type {{ $cr->type === 'reunion_hebdo' ? 'reunion' : 'demo' }}" style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.75rem;font-weight:600;color:#fff;background:{{ $cr->type === 'reunion_hebdo' ? '#2563eb' : '#059669' }}">{{ $cr->typeLibelle() }}</span>
        {{ $cr->date_reunion->format('d/m/Y') }}
        @if($cr->lieu) — {{ $cr->lieu }} @endif
        — Statut : <strong>{{ $cr->statut === 'confirme' ? 'Confirmé' : 'Brouillon' }}</strong>
    </div>

    <div class="section">
        <div class="meta-grid">
            <div>Rédacteur : <span>{{ $cr->redacteur?->nomComplet() ?? '—' }}</span></div>
            <div>Désigné par : <span>{{ $cr->designePar?->nomComplet() ?? '—' }}</span></div>
            @if($cr->sprint)
            <div>Sprint : <span>{{ $cr->sprint->libelle ?? "Sprint #{$cr->sprint->id}" }}</span></div>
            @endif
        </div>
    </div>

    @if($cr->type === 'reunion_hebdo')
        {{-- PRÉSENCES --}}
        @if(!empty($donnees['presences']))
        <div class="section">
            <h3>Liste de présence</h3>
            <table class="tbl">
                <thead><tr><th>Collaborateur</th><th style="width:80px">Présent</th></tr></thead>
                <tbody>
                @foreach($donnees['presences'] as $p)
                <tr><td>{{ $p['nom'] }}</td><td class="{{ ($p['present'] ?? false) ? 'badge-ok' : 'badge-ko' }}">{{ ($p['present'] ?? false) ? 'Oui' : 'Non' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- DILIGENCES --}}
        @if(!empty($donnees['diligences']))
        <div class="section">
            <h3>Diligences par projet</h3>
            @foreach($donnees['diligences'] as $dil)
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:10px">
                <div style="font-weight:600;color:#1e3a5f;margin-bottom:6px">{{ $dil['projet'] }} <span style="font-weight:400;color:#64748b;font-size:.82rem">({{ $dil['responsable'] }})</span></div>
                <div style="font-size:.82rem;color:#475569">
                    Tâches terminées : <strong>{{ $dil['taches_terminees'] }}</strong> —
                    Blocages : <strong>{{ $dil['taches_bloquees'] }}</strong>
                </div>
                @if(!empty($dil['evenements']))
                <div style="font-size:.82rem;color:#475569;margin-top:4px">Événements : {{ implode(', ', (array)$dil['evenements']) }}</div>
                @endif
                @if(!empty($dil['observations']))
                <div style="font-size:.82rem;color:#475569;margin-top:4px;font-style:italic">{{ $dil['observations'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- DÉCISIONS --}}
        @if(!empty($donnees['decisions']))
        <div class="section">
            <h3>Décisions et actions</h3>
            <table class="tbl">
                <thead><tr><th>Décision / Action</th><th>Responsable</th><th>Échéance</th></tr></thead>
                <tbody>
                @foreach($donnees['decisions'] as $dec)
                <tr><td>{{ $dec['action'] ?? '' }}</td><td>{{ $dec['responsable'] ?? '' }}</td><td>{{ $dec['echeance'] ?? '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if(!empty($donnees['observations']))
        <div class="section">
            <h3>Observations générales</h3>
            <p style="font-size:.9rem;color:#475569">{{ $donnees['observations'] }}</p>
        </div>
        @endif

    @else
        {{-- DEMO SPRINT --}}
        @if(!empty($donnees['taches']))
        <div class="section">
            <h3>Résultats par tâche</h3>
            <table class="tbl">
                <thead><tr><th>Tâche</th><th>Pts</th><th>Attribution</th><th>État</th><th>Observation</th></tr></thead>
                <tbody>
                @foreach($donnees['taches'] as $t)
                <tr>
                    <td>{{ $t['tache'] }}</td>
                    <td style="text-align:center">{{ $t['points'] }}</td>
                    <td>{{ $t['attribution'] }}</td>
                    <td class="{{ ($t['termine'] ?? false) ? 'badge-ok' : 'badge-ko' }}">{{ ($t['termine'] ?? false) ? '✓' : '✗' }}</td>
                    <td>{{ $t['observation'] ?? '' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="section">
            <h3>Évaluation du sprint</h3>
            <div><span class="score-big">{{ $donnees['points_realises'] ?? 0 }} / {{ $donnees['total_points'] ?? 0 }}</span><span class="score-pct">({{ $donnees['score_pourcent'] ?? 0 }}%)</span></div>

            @if(!empty($donnees['par_dev']))
            <table class="tbl" style="margin-top:14px">
                <thead><tr><th>Développeur</th><th>Réalisé</th><th>Total</th><th>%</th></tr></thead>
                <tbody>
                @foreach($donnees['par_dev'] as $dev)
                <tr><td>{{ $dev['nom'] }}</td><td>{{ $dev['realise'] }}</td><td>{{ $dev['total'] }}</td><td><strong>{{ $dev['pourcent'] }}%</strong></td></tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>

        @if(!empty($donnees['actions_a_mener']))
        <div class="section">
            <h3>Actions à mener</h3>
            <table class="tbl">
                <thead><tr><th>Action</th><th>Responsable</th><th>Échéance</th></tr></thead>
                <tbody>
                @foreach($donnees['actions_a_mener'] as $a)
                <tr><td>{{ $a['action'] ?? '' }}</td><td>{{ $a['responsable'] ?? '' }}</td><td>{{ $a['echeance'] ?? '' }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if(!empty($donnees['note_sprint']))
        <div class="section">
            <h3>Note pour le sprint suivant</h3>
            <p style="font-size:.9rem;color:#475569">{{ $donnees['note_sprint'] }}</p>
        </div>
        @endif
    @endif

    <div class="bar">
        <a href="{{ route('comptes-rendus.index') }}" class="btn sec">Retour</a>
        @if($cr->estBrouillon() && ($user->estPilote() || $cr->redacteur_id === $user->id))
        <a href="{{ route('comptes-rendus.edit', $cr) }}" class="btn" style="background:#f59e0b;color:#fff">Éditer</a>
        @endif
        @if($cr->estConfirme())
        <a href="{{ route('comptes-rendus.telecharger', $cr) }}" class="btn">Télécharger DOCX</a>
        @endif
    </div>
</div>
@endsection
