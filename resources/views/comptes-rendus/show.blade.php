@extends('layouts.app')
@section('sousTitre', 'Comptes rendus')
@section('titre', $cr->titre)
@section('contenu')
<style>
.cr-show{max-width:920px;margin:0 auto}
.cr-show h2{font-size:1.3rem;font-weight:700;color:var(--bleu,#1F4E79);margin-bottom:4px}
.cr-show .sub{font-size:.85rem;color:#64748b;margin-bottom:20px}
.section{background:#fff;border-radius:12px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden}
.section h3{font-size:1rem;font-weight:700;color:var(--bleu,#1F4E79);margin-bottom:12px;padding-bottom:6px;border-bottom:2px solid #DDEAF5}
.section .cr-contenu{font-size:.9rem;color:#334155;line-height:1.6;padding:0 8px;overflow-wrap:break-word;word-break:break-word;max-width:100%;text-align:justify}
.section .cr-contenu p,.section .cr-contenu div,.section .cr-contenu ul,.section .cr-contenu ol{max-width:100%;overflow-wrap:break-word;word-break:break-word}
.tbl{width:100%;border-collapse:collapse;font-size:.85rem;table-layout:fixed}
.tbl th{background:var(--bleu,#1F4E79);color:#fff;padding:8px 10px;text-align:left;font-weight:600}
.tbl td{padding:8px 10px;border-bottom:1px solid #e5e7eb;overflow-wrap:break-word;word-break:break-word}
.tbl tr:nth-child(even) td{background:#f8fafc}
.badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.75rem;font-weight:600}
.badge-ok{background:#d1fae5;color:#065f46}
.badge-brouillon{background:#fef3c7;color:#92400e}
.info-grid{display:grid;grid-template-columns:160px 1fr;gap:6px 12px;font-size:.88rem}
.info-grid dt{font-weight:600;color:#475569}
.info-grid dd{color:var(--bleu,#1F4E79)}
.presence-ok{color:#059669;font-weight:700}
.presence-no{color:#dc2626;font-weight:700}
.cr-bar{display:flex;gap:12px;flex-wrap:wrap;margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0}
.cr-bar .btn-retour{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;font-size:.85rem;font-weight:500;text-decoration:none;color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0;transition:all .15s}
.cr-bar .btn-retour:hover{background:#e2e8f0;color:#334155}
.cr-bar .btn-modifier{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;color:var(--bleu,#1F4E79);background:#EFF4FB;border:1px solid var(--bleu,#1F4E79);transition:all .15s}
.cr-bar .btn-modifier:hover{background:var(--bleu,#1F4E79);color:#fff}
.cr-bar .btn-docx{display:inline-flex;align-items:center;gap:6px;padding:9px 24px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;color:#fff;background:var(--bleu,#1F4E79);box-shadow:0 1px 3px rgba(31,78,121,.2);transition:all .15s}
.cr-bar .btn-docx:hover{background:var(--accent,#2E74B5)}
.cr-bar .btn-suppr{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:8px;font-size:.85rem;font-weight:600;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;cursor:pointer;transition:all .15s}
.cr-bar .btn-suppr:hover{background:#dc2626;color:#fff;border-color:#dc2626}
.tbl .num-col{width:45px;text-align:center;color:#94a3b8;font-weight:600}
@media(max-width:900px){.tbl{display:block;overflow-x:auto}.info-grid{grid-template-columns:1fr}.cr-bar{flex-wrap:wrap}}
@media(max-width:600px){.cr-show{margin:0}.section{padding:14px 12px}.cr-bar a,.cr-bar button,.cr-bar form{flex:1}.cr-bar a,.cr-bar button{text-align:center;justify-content:center}}
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
            <dt>Sprint</dt><dd>Sprint {{ $cr->sprint->numero }} — {{ $cr->sprint->projet?->libelle ?? '' }}</dd>
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
                <thead><tr><th class="num-col">N°</th><th>Collaborateur</th><th style="width:100px;text-align:center">Présence</th></tr></thead>
                <tbody>
                @foreach($d['presences'] as $i => $p)
                <tr>
                    <td class="num-col">{{ $i + 1 }}</td>
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
            <div class="cr-contenu">{!! $d['contexte'] ?? '—' !!}</div>

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
                <strong style="font-size:.88rem;color:var(--bleu,#1F4E79)">2.{{ $k + 1 }}. {{ $odj['point'] }}</strong>
                <div class="cr-contenu" style="margin-top:4px">{!! $odj['contenu'] !!}</div>
            </div>
            @endif
            @endforeach
        </div>
        @endif

        {{-- 3. DECISIONS --}}
        @if(!empty($d['decisions']))
        <div class="section">
            <h3>3. Décisions prises / Actions à mener</h3>
            <table class="tbl">
                <thead><tr><th style="width:40px">N°</th><th>Décision / Action</th><th>Responsable(s)</th><th style="width:120px">Échéance</th></tr></thead>
                <tbody>
                @foreach($d['decisions'] as $i => $dec)
                @php $rDec = $dec['responsables'] ?? (isset($dec['responsable']) && $dec['responsable'] ? [$dec['responsable']] : []); @endphp
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $dec['action'] ?? '' }}</td>
                    <td>{{ implode(', ', $rDec) ?: '—' }}</td>
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
            <div class="cr-contenu">{!! $d['conclusion'] !!}</div>
        </div>
        @endif

    @else
        {{-- ===== DEMO SPRINT ===== --}}
        @if(!empty($d['taches']))
        <div class="section">
            <h3>Résultats par tâche</h3>
            <div style="font-size:.85rem;color:#64748b;margin-bottom:10px">Sprint : <strong>{{ $d['sprint'] ?? '—' }}</strong> — Projet : <strong>{{ $d['projet'] ?? '—' }}</strong></div>
            <table class="tbl">
                <thead><tr><th style="width:40px">N°</th><th>Tâche</th><th style="width:50px">Pts</th><th>Attribution</th><th style="width:50px">OK</th><th>Observation</th></tr></thead>
                <tbody>
                @foreach($d['taches'] as $i => $t)
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
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
            <div style="font-size:1.3rem;font-weight:700;color:var(--bleu,#1F4E79)">{{ $d['points_realises'] ?? 0 }} / {{ $d['total_points'] ?? 0 }} points ({{ $d['score_pourcent'] ?? 0 }}%)</div>
        </div>

        @if(!empty($d['actions_a_mener']))
        <div class="section">
            <h3>Actions à mener</h3>
            <table class="tbl">
                <thead><tr><th style="width:40px">N°</th><th>Action</th><th>Responsable(s)</th><th style="width:120px">Échéance</th></tr></thead>
                <tbody>
                @foreach($d['actions_a_mener'] as $i => $a)
                @php $rAct = $a['responsables'] ?? (isset($a['responsable']) && $a['responsable'] ? [$a['responsable']] : []); @endphp
                <tr>
                    <td style="text-align:center">{{ $i + 1 }}</td>
                    <td>{{ $a['action'] ?? '' }}</td>
                    <td>{{ implode(', ', $rAct) ?: '—' }}</td>
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
            <div class="cr-contenu">{!! $d['note_sprint'] !!}</div>
        </div>
        @endif
    @endif

    <div class="cr-bar">
        <a href="{{ route('comptes-rendus.index') }}" class="btn-retour">&larr; Retour</a>
        @if(auth()->user()->estAdmin() || ($cr->estBrouillon() && auth()->id() === $cr->redacteur_id))
        <a href="{{ route('comptes-rendus.edit', $cr) }}" class="btn-modifier">Modifier</a>
        @endif
        @if($cr->estConfirme() && $cr->fichier_path)
        <a href="{{ route('comptes-rendus.telecharger', $cr) }}" class="btn-docx">&#8681; Télécharger DOCX</a>
        @endif
        <a href="{{ route('comptes-rendus.imprimer', $cr) }}" class="btn-docx" style="background:#1F4E79" target="_blank">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Version PDF
        </a>
        @if(auth()->user()->estPilote())
        <form method="POST" action="{{ route('comptes-rendus.destroy', $cr) }}" style="margin:0" onsubmit="return demanderMotifSuppression(this,'ce compte rendu')">
            @csrf @method('DELETE')
            <input type="hidden" name="motif_suppression" class="motif-suppr-input">
            <button type="submit" class="btn-suppr">&#128465; Supprimer</button>
        </form>
        @endif
    </div>
</div>
@endsection
