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
.section{margin-bottom:16px;display:flow-root}
.section h2{font-size:12pt;color:#1F4E79;font-weight:700;text-transform:uppercase;letter-spacing:.3px;margin-bottom:8px;padding:6px 10px;background:linear-gradient(90deg,#EFF4FB,transparent);border-left:3px solid #2E74B5}
.section h3{font-size:11pt;color:#2E74B5;font-weight:600;margin:10px 0 6px}
.section .contenu{font-size:10.5pt;line-height:1.6;padding:0 10px;overflow-wrap:break-word;word-break:break-word;max-width:100%;text-align:justify}
.section .contenu ul,.section .contenu ol{margin:4px 0 8px 20px;padding-left:24px}
.section .contenu ul{list-style:disc outside}
.section .contenu ul ul{list-style:circle}
.section .contenu ol{list-style:decimal outside}
.section .contenu li{margin-bottom:3px;display:list-item}
.section .contenu [class^="MsoList"]{margin-left:40px!important}
.section .contenu p{margin-bottom:6px}
.section .contenu *{font-family:inherit!important;font-size:inherit!important;color:inherit!important;background:none!important;line-height:inherit!important}
.section .contenu a{text-decoration:none!important}
.section .contenu .lh-10{line-height:1!important}
.section .contenu .lh-115{line-height:1.15!important}
.section .contenu .lh-15{line-height:1.5!important}
.section .contenu .lh-20{line-height:2!important}
.section .contenu .lh-25{line-height:2.5!important}
.section .contenu font[size="1"]{font-size:.75em!important}
.section .contenu font[size="2"]{font-size:.833em!important}
.section .contenu font[size="4"]{font-size:1.167em!important}
.section .contenu font[size="5"]{font-size:1.333em!important}
.section .contenu font[size="6"]{font-size:1.667em!important}
.section .contenu font[size="7"]{font-size:2em!important}
.section .contenu ol.hier,.section .contenu ol.hier ol{list-style:none!important;counter-reset:hier;padding-left:0!important;margin-left:0!important}
.section .contenu ol.hier ol{margin-left:28px!important}
.section .contenu ol.hier li{counter-increment:hier;display:block}
.section .contenu ol.hier li::before{content:var(--num-prefix,"") counters(hier,".") ". ";font-weight:700;margin-right:4px}
.section .contenu blockquote{margin:0 0 0 28px!important;padding:0!important;border:none!important}
.section .contenu h1,.section .contenu h2,.section .contenu h3{text-transform:uppercase;color:#1F4E79;font-weight:700;margin:10px 0 6px}

/* Tableaux */
.tbl{width:100%;border-collapse:collapse;margin:8px 0 14px;font-size:10pt}
.tbl th{background:#1F4E79;color:#fff;padding:6px 8px;text-align:left;font-weight:600;font-size:9.5pt;text-transform:uppercase;letter-spacing:.3px}
.tbl td{padding:5px 8px;border-bottom:1px solid #e2e8f0;vertical-align:top}
.tbl tr:nth-child(even) td{background:#f8fafc}
.tbl .num{text-align:center;width:35px;color:#64748b;font-weight:600}
.tbl .present{color:#1F4E79;font-weight:600}
.tbl .absent{color:#93B4D6;font-weight:600}
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
@php $mef = $cr->getDonnees()['mise_en_forme'] ?? []; @endphp
<style>
@if(!empty($mef['titre'])).header h1{font-size:{{ (float) $mef['titre'] }}pt}@endif
@if(!empty($mef['section'])).section h2{font-size:{{ (float) $mef['section'] }}pt}@endif
@if(!empty($mef['sous_titre'])).section h3{font-size:{{ (float) $mef['sous_titre'] }}pt}@endif
@if(!empty($mef['texte'])).section .contenu{font-size:{{ (float) $mef['texte'] }}pt}.tbl,.info-tbl{font-size:{{ round((float) $mef['texte'] * 0.95, 2) }}pt}.tbl th{font-size:{{ round((float) $mef['texte'] * 0.9, 2) }}pt}@endif
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
                <thead><tr><th class="num">N°</th><th>Collaborateur</th><th>Fonction</th><th style="width:100px;text-align:center">Présence</th></tr></thead>
                <tbody>
                @foreach($d['presences'] as $i => $p)
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td>{{ $p['nom'] }}</td>
                    <td>{{ ($p['fonction'] ?? '') ?: '—' }}</td>
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
            <div class="contenu" style="--num-prefix:'1.'">{!! $d['contexte'] ?? '—' !!}</div>
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
            <div class="contenu" style="--num-prefix:'2.{{ $k + 1 }}.'">{!! $odj['contenu'] !!}</div>
            @endif
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
            <div class="contenu" style="--num-prefix:'4.'">{!! $d['conclusion'] !!}</div>
        </div>
        @endif

    @elseif(($d['format'] ?? '') === 'blocs_v1' && !empty($d['modules']))
        {{-- ===== DEMO SPRINT — FORMAT BLOCS ===== --}}
        @php
        $refStyles = [
            'rappel' => ['Rappel', '#E8F5E9', '#00897B'], 'fondamental' => ['Fondamental', '#FFF8E1', '#F9A825'],
            'attention' => ['Attention', '#FFF3E0', '#E6A817'], 'remarque' => ['Remarque', '#E8EEF4', '#2E74B5'],
            'methode' => ['Méthode', '#F3E8F9', '#7B2D8E'], 'conseil' => ['Conseil', '#FFF8E1', '#EF6C00'],
            'definition' => ['Définition', '#E3F2FD', '#1565C0'], 'exemple' => ['Exemple', '#F1F8E9', '#558B2F'],
            'bonne_pratique' => ['Bonne pratique', '#E0F2FE', '#0288D1'], 'blocage' => ['Blocage', '#FEECEC', '#CC3333'],
        ];
        @endphp
        @foreach($d['modules'] as $mi => $mod)
        <div class="section">
            <h2>{{ $mi + 1 }}. {{ $mod['titre'] ?? 'Module' }}</h2>
            @if(!empty($mod['objectif']))
            <div class="contenu" style="font-style:italic;color:#64748b;margin-bottom:6px">{{ $mod['objectif'] }}</div>
            @endif

            @foreach($mod['sections'] ?? [] as $si => $sec)
                @if(!empty($sec['titre']))
                <h3>{{ $mi + 1 }}.{{ $si + 1 }}. {{ $sec['titre'] }}</h3>
                @endif

                @foreach($sec['blocs'] ?? [] as $bloc)
                    @if($bloc['type'] === 'texte' && !empty($bloc['contenu']))
                    <div class="contenu" style="margin-bottom:8px;--num-prefix:'{{ $mi + 1 }}.{{ $si + 1 }}.'">{!! $bloc['contenu'] !!}</div>
                    @elseif($bloc['type'] === 'tableau_taches' && !empty($d['taches']))
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
                    @elseif($bloc['type'] === 'score')
                    <div class="score-box">
                        <div class="val">{{ $d['points_realises'] ?? 0 }} / {{ $d['total_points'] ?? 0 }}</div>
                        <div class="lab">Score global — {{ $d['score_pourcent'] ?? 0 }}%</div>
                    </div>
                    @elseif($bloc['type'] === 'actions' && !empty($bloc['items']))
                    <table class="tbl">
                        <thead><tr><th class="num">N°</th><th>Action</th><th>Responsable(s)</th><th style="width:90px">Échéance</th></tr></thead>
                        <tbody>
                        @foreach($bloc['items'] as $ai => $act)
                        <tr>
                            <td class="num">{{ $ai + 1 }}</td>
                            <td>{{ $act['action'] ?? '' }}</td>
                            <td>{{ implode(', ', $act['responsables'] ?? []) ?: '—' }}</td>
                            <td>{{ ($act['echeance'] ?? '') ?: '—' }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @elseif($bloc['type'] === 'reference' && !empty($bloc['contenu']))
                    @php [$rLabel, $rBg, $rBorder] = $refStyles[$bloc['ref_type'] ?? 'remarque'] ?? $refStyles['remarque']; @endphp
                    <div style="background:{{ $rBg }};border-left:4px solid {{ $rBorder }};border-radius:6px;padding:8px 12px;margin:6px 0 10px">
                        <div style="font-weight:700;color:{{ $rBorder }};font-size:9.5pt;margin-bottom:3px;text-transform:uppercase">{{ $rLabel }}</div>
                        <div class="contenu" style="padding:0;--num-prefix:'{{ $mi + 1 }}.{{ $si + 1 }}.'">{!! $bloc['contenu'] !!}</div>
                    </div>
                    @endif
                @endforeach
            @endforeach
        </div>
        @endforeach

    @else
        {{-- ===== DEMO SPRINT (ancien format) ===== --}}
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
