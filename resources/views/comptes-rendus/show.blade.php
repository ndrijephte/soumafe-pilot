@extends('layouts.app')
@section('titre', $cr->titre)
@section('contenu')
<style>
.cr-show{max-width:920px;margin:0 auto}
.cr-show h2{font-size:1.3rem;font-weight:700;color:#1a1a2e;margin-bottom:4px}
.cr-show .sub{font-size:.85rem;color:#64748b;margin-bottom:20px}
.section{background:#fff;border-radius:12px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.section h3{font-size:1rem;font-weight:700;color:#1e3a5f;margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #e0e7ff}
.tbl{width:100%;border-collapse:collapse;font-size:.85rem}
.tbl th{background:#1F4E79;color:#fff;padding:8px 10px;text-align:left;font-weight:600}
.tbl td{padding:8px 10px;border-bottom:1px solid #e5e7eb}
.tbl tr:nth-child(even) td{background:#f8fafc}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.75rem;font-weight:600}
.badge-ok{background:#d1fae5;color:#065f46}
.badge-brouillon{background:#fef3c7;color:#92400e}
.info-grid{display:grid;grid-template-columns:160px 1fr;gap:6px 12px;font-size:.88rem}
.info-grid dt{font-weight:600;color:#475569}
.info-grid dd{color:#1a1a2e}
.bar{display:flex;gap:10px;flex-wrap:wrap;margin-top:20px}
.presence-ok{color:#059669;font-weight:700}
.presence-no{color:#dc2626;font-weight:700}
</style>

<div class="cr-show">
    <h2>{{ $cr->titre }}</h2>
    <div class="sub">
        {{ $cr->typeCourt() }} —
        <span class="badge {{ $cr->estConfirme() ? 'badge-ok' : 'badge-brouillon' }}">{{ $cr->estConfirme() ? 'Confirmé' : 'Brouillon' }}</span>
    </div>

    {{-- Informations générales --}}
    <div class="section">
        <h3>Informations</h3>
        <dl class="info-grid">
            <dt>Date</dt><dd>{{ $cr->date_reunion->format('d/m/Y') }}</dd>
            <dt>Heure</dt><dd>{{ $cr->heure_debut ?? '—' }} – {{ $cr->heure_fin ?? '—' }}</dd>
            <dt>Lieu</dt><dd>{{ $cr->lieu ?? '—' }}</dd>
            @if($cr->estReunion() && $cr->president)
            <dt>Président</dt><dd>{{ $cr->president->nomComplet() }}</dd>
            @endif
            <dt>Rédacteur</dt><dd>{{ $cr->redacteur?->nomComplet() ?? '—' }}</dd>
            <dt>Désigné par</dt><dd>{{ $cr->designePar?->nomComplet() ?? '—' }}</dd>
            @if($cr->sprint)
            <dt>Sprint</dt><dd>{{ $cr->sprint->libelle ?? "Sprint #{$cr->sprint->id}" }}</dd>
            @endif
        </dl>
    </div>

    @php $d = $cr->getDonnees(); @endphp

    @if($cr->estReunion())
        {{-- PRESENCES --}}
        @if(!empty($d['presences']))
        <div class="section">
            <h3>Liste de présence</h3>
            <table class="tbl">
                <thead><tr><th>Collaborateur</th><th style="width:100px;text-align:center">Présence</th></tr></thead>
                <tbody>
                @foreach($d['presences'] as $p)
                <tr>
                    <td>{{ $p['nom'] }}</td>
                    <td style="text-align:center">
                        @if($p['present'] ?? false)
                        <span class="presence-ok">Présent</span>
                        @else
                        <span class="presence-no">Absent</span>
                        @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- 1. CONTEXTE --}}
        <div class="section">
            <h3>1. Contexte et objectifs de la réunion</h3>
            <p style="white-space:pre-line;font-size:.9rem;color:#334155">{{ $d['contexte'] ?? '—' }}</p>

            @if(!empty($d['ordre_du_jour']))
            <div style="margin-top:14px">
                <strong style="font-size:.88rem;color:#475569">Ordre du jour :</strong>
                <ol style="margin:6px 0 0 20px;font-size:.88rem;color:#1e293b">
                @foreach($d['ordre_du_jour'] as $odj)
                    <li>{{ $odj['point'] }}</li>
                @endforeach
                </ol>
            </div>
            @endif
        </div>

        {{-- 2. DEROULEMENT --}}
        @if(!empty($d['ordre_du_jour']))
        <div class="section">
            <h3>2. Déroulement de la réunion</h3>
            @foreach($d['ordre_du_jour'] as $k => $odj)
            @if(!empty($odj['contenu']))
            <div style="margin-bottom:14px">
                <strong style="font-size:.88rem;color:#1e3a5f">2.{{ $k + 1 }}. {{ $odj['point'] }}</strong>
                <p style="white-space:pre-line;font-size:.88rem;color:#334155;margin-top:4px">{{ $odj['contenu'] }}</p>
            </div>
            @endif
            @endforeach
        </div>
        @endif

        {{-- Diligences --}}
        @if(!empty($d['diligences']))
        <div class="section">
            <h3>Diligences par projet</h3>
            @foreach($d['diligences'] as $dil)
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:10px">
                <div style="font-weight:600;color:#1e3a5f">{{ $dil['projet'] }} <span style="font-weight:400;color:#64748b;font-size:.82rem">({{ $dil['responsable'] }})</span></div>
                <div style="font-size:.82rem;color:#64748b;margin-top:4px">Terminées : <strong>{{ $dil['taches_terminees'] }}</strong> — Blocages : <strong>{{ $dil['taches_bloquees'] }}</strong></div>
                @if(!empty($dil['observations']))
                <div style="font-size:.85rem;color:#334155;margin-top:6px">{{ $dil['observations'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- 3. DECISIONS --}}
        @if(!empty($d['decisions']))
        <div class="section">
            <h3>3. Décisions prises / Actions à mener</h3>
            <table class="tbl">
                <thead><tr><th>Décision / Action</th><th>Responsable</th><th style="width:120px">Échéance</th></tr></thead>
                <tbody>
                @foreach($d['decisions'] as $dec)
                <tr>
                    <td>{{ $dec['action'] ?? '' }}</td>
                    <td>{{ $dec['responsable'] ?? '' }}</td>
                    <td>{{ $dec['echeance'] ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- 4. CONCLUSION --}}
        @if(!empty($d['conclusion']))
        <div class="section">
            <h3>4. Conclusion</h3>
            <p style="white-space:pre-line;font-size:.9rem;color:#334155">{{ $d['conclusion'] }}</p>
        </div>
        @endif

    @else
        {{-- ===== DEMO SPRINT ===== --}}
        @if(!empty($d['taches']))
        <div class="section">
            <h3>Résultats par tâche</h3>
            <div style="font-size:.85rem;color:#64748b;margin-bottom:10px">Sprint : <strong>{{ $d['sprint'] ?? '—' }}</strong> — Projet : <strong>{{ $d['projet'] ?? '—' }}</strong></div>
            <table class="tbl">
                <thead><tr><th>Tâche</th><th style="width:50px">Pts</th><th>Attribution</th><th style="width:50px">OK</th><th>Observation</th></tr></thead>
                <tbody>
                @foreach($d['taches'] as $t)
                <tr>
                    <td>{{ $t['tache'] }}</td>
                    <td style="text-align:center">{{ $t['points'] }}</td>
                    <td>{{ $t['attribution'] }}</td>
                    <td style="text-align:center">{!! ($t['termine'] ?? false) ? '<span class="presence-ok">✓</span>' : '<span class="presence-no">✕</span>' !!}</td>
                    <td>{{ $t['observation'] ?? '' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="section">
            <h3>Score global</h3>
            <div style="font-size:1.3rem;font-weight:700;color:#1e3a5f">{{ $d['points_realises'] ?? 0 }} / {{ $d['total_points'] ?? 0 }} points ({{ $d['score_pourcent'] ?? 0 }}%)</div>
        </div>

        @if(!empty($d['actions_a_mener']))
        <div class="section">
            <h3>Actions à mener</h3>
            <table class="tbl">
                <thead><tr><th>Action</th><th>Responsable</th><th style="width:120px">Échéance</th></tr></thead>
                <tbody>
                @foreach($d['actions_a_mener'] as $a)
                <tr>
                    <td>{{ $a['action'] ?? '' }}</td>
                    <td>{{ $a['responsable'] ?? '' }}</td>
                    <td>{{ $a['echeance'] ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if(!empty($d['note_sprint']))
        <div class="section">
            <h3>Note pour le sprint suivant</h3>
            <p style="white-space:pre-line;font-size:.9rem;color:#334155">{{ $d['note_sprint'] }}</p>
        </div>
        @endif
    @endif

    {{-- Actions --}}
    <div class="bar">
        <a href="{{ route('comptes-rendus.index') }}" class="btn sec">Retour</a>
        @if($cr->estBrouillon() && (auth()->user()->estAdmin() || auth()->id() === $cr->redacteur_id))
        <a href="{{ route('comptes-rendus.edit', $cr) }}" class="btn" style="background:#f59e0b;color:#fff">Modifier</a>
        @endif
        @if($cr->estConfirme() && $cr->fichier_path)
        <a href="{{ route('comptes-rendus.telecharger', $cr) }}" class="btn">Télécharger DOCX</a>
        @endif
    </div>
</div>
@endsection
