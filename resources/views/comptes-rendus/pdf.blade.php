<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{{ $cr->titre }} — SOUMAFÊ</title>
<style>
@page{size:A4;margin:20mm 18mm 22mm 18mm}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Tahoma,Arial,sans-serif;font-size:11pt;color:#1e293b;line-height:1.55;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.page{max-width:720px;margin:0 auto;padding:30px 20px}
@media print{.page{padding:0;max-width:none}.no-print{display:none!important}}

/* En-tête */
.header{text-align:center;border-bottom:3px solid #1F4E79;padding-bottom:14px;margin-bottom:20px}
.header .logo-cercle{width:58px;height:58px;border-radius:50%;background:#1F4E79;display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;border:2px solid rgba(31,78,121,.3)}
.header .logo-cercle img{height:34px;width:auto}
.header .societe{font-size:9pt;color:#64748b;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:4px}
.header h1{font-size:14pt;font-weight:700;color:#1F4E79;text-transform:uppercase;letter-spacing:.5px}
.header .sous{font-size:10pt;color:#475569;margin-top:2px}

/* Infos */
.info-tbl{width:100%;border-collapse:collapse;margin-bottom:18px;font-size:10pt}
.info-tbl td{padding:5px 10px;border:1px solid #d1d5db}
.info-tbl td:first-child{background:#f0f5fa;color:#1F4E79;font-weight:600;width:140px}

/* Sections */
.section{margin-bottom:16px;overflow:hidden}
.section h2{font-size:12pt;color:#1F4E79;font-weight:700;margin-bottom:8px;padding:6px 10px;background:linear-gradient(90deg,#EFF4FB,transparent);border-left:3px solid #2E74B5}
.section h3{font-size:11pt;color:#2E74B5;font-weight:600;margin:10px 0 6px}
.section .contenu{font-size:10.5pt;line-height:1.6;padding:0 10px;overflow-wrap:break-word;word-break:break-word;max-width:100%}

/* Tableaux */
.tbl{width:100%;border-collapse:collapse;margin:8px 0 14px;font-size:10pt}
.tbl th{background:#1F4E79;color:#fff;padding:6px 8px;text-align:left;font-weight:600;font-size:9.5pt;text-transform:uppercase;letter-spacing:.3px}
.tbl td{padding:5px 8px;border-bottom:1px solid #e2e8f0;vertical-align:top}
.tbl tr:nth-child(even) td{background:#f8fafc}
.tbl .num{text-align:center;width:35px;color:#64748b;font-weight:600}
.tbl .present{color:#059669;font-weight:600}
.tbl .absent{color:#dc2626;font-weight:600}
.tbl .ok{color:#059669;font-weight:700;text-align:center}
.tbl .ko{color:#dc2626;text-align:center}

/* Score */
.score-box{background:#EFF4FB;border:2px solid #2E74B5;border-radius:8px;padding:16px 20px;text-align:center;margin:12px 0 16px}
.score-box .val{font-size:22pt;font-weight:700;color:#1F4E79}
.score-box .lab{font-size:10pt;color:#475569;margin-top:2px}

/* Pied de page */
.footer{margin-top:24px;padding-top:12px;border-top:2px solid #1F4E79;display:flex;justify-content:space-between;font-size:8.5pt;color:#94a3b8}

/* Bouton imprimer */
.print-bar{position:fixed;top:0;left:0;right:0;background:#1F4E79;color:#fff;display:flex;align-items:center;justify-content:center;gap:16px;padding:10px;z-index:999;box-shadow:0 2px 10px rgba(0,0,0,.2)}
.print-bar a,.print-bar button{padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:none}
.print-bar .btn-print{background:#fff;color:#1F4E79}
.print-bar .btn-retour{background:transparent;color:#fff;border:1px solid rgba(255,255,255,.4)}
@media print{.print-bar{display:none}body{padding-top:0}}
@media screen{body{padding-top:52px;background:#f1f5f9}}
</style>
</head>
<body>

<div class="print-bar no-print">
    <button class="btn-print" onclick="window.print()">Imprimer / Enregistrer PDF</button>
    <a href="{{ route('comptes-rendus.show', $cr) }}" class="btn-retour">Retour</a>
</div>

<div class="page">
    {{-- EN-TÊTE --}}
    <div class="header">
        @php $logoPath = public_path('images/white_logo_v.png'); @endphp
        @if(file_exists($logoPath))
        <div class="logo-cercle"><img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="SOUMAFÊ"></div>
        @endif
        <div class="societe">SOUMAFÊ — Pilotage des opérations</div>
        @if($cr->estReunion())
        <h1>Compte rendu de {{ $cr->type === 'reunion_ordinaire' ? 'réunion ordinaire' : 'réunion extraordinaire' }}</h1>
        @else
        <h1>Compte rendu de démonstration sprint</h1>
        @endif
        <div class="sous">{{ $cr->titre }}</div>
    </div>

    {{-- INFORMATIONS --}}
    <table class="info-tbl">
        <tr><td>Date</td><td>{{ $cr->date_reunion->format('d/m/Y') }}</td></tr>
        <tr><td>Horaires</td><td>{{ $cr->heure_debut ?? '—' }} — {{ $cr->heure_fin ?? '—' }}</td></tr>
        <tr><td>Lieu</td><td>{{ $cr->lieu ?? '—' }}</td></tr>
        @if($cr->estReunion() && $cr->president)
        <tr><td>Président</td><td>{{ $cr->president->nomComplet() }}</td></tr>
        @endif
        <tr><td>Rédacteur</td><td>{{ $cr->redacteur?->nomComplet() ?? '—' }}</td></tr>
        <tr><td>Désigné par</td><td>{{ $cr->designePar?->nomComplet() ?? '—' }}</td></tr>
        @if($cr->sprint)
        <tr><td>Sprint</td><td>Sprint {{ $cr->sprint->numero }} — {{ $cr->sprint->projet?->libelle ?? '' }}</td></tr>
        @endif
    </table>

    @php $d = $cr->getDonnees(); @endphp

    @if($cr->estReunion())
        {{-- PRESENCES --}}
        @if(!empty($d['presences']))
        <div class="section">
            <h2>Liste de présence</h2>
            <table class="tbl">
                <thead><tr><th class="num">N°</th><th>Collaborateur</th><th style="width:100px;text-align:center">Présence</th></tr></thead>
                <tbody>
                @foreach($d['presences'] as $i => $p)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $p['nom'] }}</td>
                    <td style="text-align:center" class="{{ ($p['present'] ?? false) ? 'present' : 'absent' }}">{{ ($p['present'] ?? false) ? 'Présent' : 'Absent' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- CONTEXTE --}}
        <div class="section">
            <h2>1. Contexte et objectifs de la réunion</h2>
            <div class="contenu">{!! $d['contexte'] ?? '—' !!}</div>
            @if(!empty($d['ordre_du_jour']))
            <h3>Ordre du jour</h3>
            <ol style="margin:4px 0 0 20px;font-size:10.5pt">
                @foreach($d['ordre_du_jour'] as $odj)
                <li style="margin-bottom:3px">{{ $odj['point'] }}</li>
                @endforeach
            </ol>
            @endif
        </div>

        {{-- DEROULEMENT --}}
        @if(!empty($d['ordre_du_jour']))
        <div class="section">
            <h2>2. Déroulement de la réunion</h2>
            @foreach($d['ordre_du_jour'] as $k => $odj)
            @if(!empty($odj['contenu']))
            <h3>2.{{ $k + 1 }}. {{ $odj['point'] }}</h3>
            <div class="contenu">{!! $odj['contenu'] !!}</div>
            @endif
            @endforeach
        </div>
        @endif

        {{-- DILIGENCES --}}
        @if(!empty($d['diligences']))
        <div class="section">
            <h2>Diligences par projet</h2>
            @foreach($d['diligences'] as $dil)
            <div style="background:#f8fafc;border-radius:6px;padding:8px 12px;margin-bottom:8px;border-left:3px solid #2E74B5">
                <strong style="color:#1F4E79">{{ $dil['projet'] }}</strong> <span style="color:#64748b;font-size:9.5pt">({{ $dil['responsable'] }})</span><br>
                <span style="font-size:9.5pt">Terminées : <strong>{{ $dil['taches_terminees'] }}</strong> — Blocages : <strong>{{ $dil['taches_bloquees'] }}</strong></span>
                @if(!empty($dil['observations']))
                <div style="font-size:10pt;margin-top:4px">{!! $dil['observations'] !!}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        {{-- DECISIONS --}}
        @if(!empty($d['decisions']))
        <div class="section">
            <h2>3. Décisions prises / Actions à mener</h2>
            <table class="tbl">
                <thead><tr><th class="num">N°</th><th>Décision / Action</th><th>Responsable(s)</th><th style="width:90px">Échéance</th></tr></thead>
                <tbody>
                @foreach($d['decisions'] as $i => $dec)
                @php $rDec = $dec['responsables'] ?? (isset($dec['responsable']) && $dec['responsable'] ? [$dec['responsable']] : []); @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $dec['action'] ?? '' }}</td>
                    <td>{{ implode(', ', $rDec) ?: '—' }}</td>
                    <td>{{ $dec['echeance'] ?? '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- CONCLUSION --}}
        @if(!empty($d['conclusion']))
        <div class="section">
            <h2>4. Conclusion</h2>
            <div class="contenu">{!! $d['conclusion'] !!}</div>
        </div>
        @endif

    @else
        {{-- ===== DEMO SPRINT ===== --}}
        @if(!empty($d['taches']))
        <div class="section">
            <h2>1. Résultats par tâche</h2>
            <table class="tbl">
                <thead><tr><th class="num">N°</th><th>Tâche</th><th style="width:40px;text-align:center">Pts</th><th>Attribution</th><th style="width:40px;text-align:center">État</th><th>Observation</th></tr></thead>
                <tbody>
                @foreach($d['taches'] as $i => $t)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $t['tache'] }}</td>
                    <td style="text-align:center">{{ $t['points'] }}</td>
                    <td>{{ $t['attribution'] }}</td>
                    <td class="{{ ($t['termine'] ?? false) ? 'ok' : 'ko' }}">{!! ($t['termine'] ?? false) ? '&#10003;' : '&#10007;' !!}</td>
                    <td style="font-size:9.5pt;color:#64748b">{{ $t['observation'] ?? '' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="section">
            <h2>2. Évaluation du sprint</h2>
            <div class="score-box">
                <div class="val">{{ $d['points_realises'] ?? 0 }} / {{ $d['total_points'] ?? 0 }}</div>
                <div class="lab">Score global — {{ $d['score_pourcent'] ?? 0 }}%</div>
            </div>
            @if(!empty($d['par_dev']))
            <h3>Score par développeur</h3>
            <table class="tbl">
                <thead><tr><th>Développeur</th><th style="text-align:center">Réalisé</th><th style="text-align:center">Total</th><th style="text-align:center">%</th></tr></thead>
                <tbody>
                @foreach($d['par_dev'] as $dev)
                <tr>
                    <td>{{ $dev['nom'] }}</td>
                    <td style="text-align:center;font-weight:600">{{ $dev['realise'] }}</td>
                    <td style="text-align:center">{{ $dev['total'] }}</td>
                    <td style="text-align:center;font-weight:600;color:{{ $dev['pourcent'] >= 70 ? '#059669' : ($dev['pourcent'] >= 40 ? '#b45309' : '#dc2626') }}">{{ $dev['pourcent'] }}%</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif
        </div>

        @if(!empty($d['actions_a_mener']))
        <div class="section">
            <h2>3. Actions à mener</h2>
            <table class="tbl">
                <thead><tr><th class="num">N°</th><th>Action</th><th>Responsable(s)</th><th style="width:90px">Échéance</th></tr></thead>
                <tbody>
                @foreach($d['actions_a_mener'] as $i => $a)
                @php $rAct = $a['responsables'] ?? (isset($a['responsable']) && $a['responsable'] ? [$a['responsable']] : []); @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
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
            <h2>4. Note pour le sprint suivant</h2>
            <div class="contenu">{!! $d['note_sprint'] !!}</div>
        </div>
        @endif
    @endif

    {{-- PIED DE PAGE --}}
    <div class="footer">
        <span>SOUMAFÊ — Document généré le {{ now()->format('d/m/Y à H:i') }}</span>
        <span>{{ $cr->estConfirme() ? 'Document confirmé' : 'Brouillon' }}</span>
    </div>
</div>

<script>
// Auto-print si paramètre ?print=1
if(new URLSearchParams(window.location.search).get('print')==='1'){
    window.addEventListener('load',function(){setTimeout(function(){window.print()},400)});
}
</script>
</body>
</html>
