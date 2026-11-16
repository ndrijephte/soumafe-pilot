<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{{ $cr->titre }} - SOUMAFÊ</title>
<style>
@page{size:A4;margin:20mm 18mm 22mm 18mm}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:{!! \App\Http\Controllers\CompteRenduController::police($cr->getDonnees()['mise_en_forme'] ?? [])[1] !!};font-size:11pt;color:#1e293b;line-height:1.55;-webkit-print-color-adjust:exact;print-color-adjust:exact}
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
.info-tbl{width:100%;border-collapse:separate;border-spacing:0;margin-bottom:18px;font-size:10pt;border:1px solid #cfdeee;border-radius:6px;overflow:hidden}
.info-tbl td{padding:6px 12px;border-bottom:1px solid #e3ecf6}
.info-tbl tr:last-child td{border-bottom:none}
.info-tbl td:first-child{background:linear-gradient(90deg,#E3EEF9,#EEF4FB);color:#1F4E79;font-weight:600;width:150px;border-right:1px solid #cfdeee}
/* Tableau signalétique du rapport : même disposition que l'éditeur (libellé / valeur sur deux colonnes) */
.info-tbl.syno td{vertical-align:middle}
.info-tbl.syno td.lib{background:linear-gradient(90deg,#E3EEF9,#EEF4FB);color:#1F4E79;font-weight:600;width:130px;white-space:nowrap;border-right:1px solid #cfdeee;border-left:1px solid #cfdeee}
.info-tbl.syno td.lib:first-child{border-left:none}
.info-tbl.syno td:first-child{width:130px}
.info-tbl.syno .gris{color:#64748b}
.grp-tache{font-size:10pt;font-weight:700;color:#2E74B5;margin:10px 0 0;padding-bottom:2px;border-bottom:1px dotted #9FBFE0;page-break-after:avoid}
.grp-tache + .tbl{margin-top:5px}

/* Sections */
.section{margin-bottom:16px;display:flow-root}
/* Grands titres : dégradé bleu clair sobre, angles droits, trait bas bleu */
.section h2{font-size:12pt;color:#1F4E79;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px;padding:7px 12px 6px;background:linear-gradient(180deg,#EAF2FB,#DCE9F7);border-bottom:2px solid #2E74B5;page-break-after:avoid}
.section h3{font-size:11pt;color:#2E74B5;font-weight:600;margin:12px 0 6px;padding-bottom:3px;border-bottom:1px solid #9FBFE0}
.section .contenu{font-size:10.5pt;line-height:1.6;padding:0 10px;overflow-wrap:break-word;word-break:break-word;max-width:100%;text-align:justify}
.section .contenu ul,.section .contenu ol{margin:4px 0 8px 20px;padding-left:24px}
.section .contenu ul{list-style:disc outside}
.section .contenu ul ul{list-style:circle}
.section .contenu ol{list-style:decimal outside}
.section .contenu li{margin-bottom:3px;display:list-item}
.section .contenu [class^="MsoList"]{margin-left:40px!important}
.section .contenu p{margin-bottom:6px}
.section .contenu *{font-family:inherit!important;font-size:inherit!important;color:inherit!important;background:none!important;line-height:inherit!important}
@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex]).section .contenu .tc-{{ $n }}{color:#{{ $hex }}!important}
@endforeach
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
.tbl{width:100%;border-collapse:separate;border-spacing:0;margin:8px 0 14px;font-size:10pt;border:1px solid #cfdeee;border-radius:6px;overflow:hidden}
.tbl th{background:linear-gradient(180deg,#2E74B5,#1F4E79);color:#fff;padding:7px 9px;text-align:left;font-weight:600;font-size:9pt;text-transform:uppercase;letter-spacing:.4px;border-right:1px solid rgba(255,255,255,.14)}
.tbl th:last-child{border-right:none}
.tbl td{padding:6px 9px;border-bottom:1px solid #e3ecf6;vertical-align:middle}
.tbl th{vertical-align:middle}
.tbl tbody tr:last-child td{border-bottom:none}
.tbl tbody tr:nth-child(even) td{background:#F4F8FC}
.tbl tr{page-break-inside:avoid}
.tbl .num{text-align:center;width:35px;color:#64748b;font-weight:600}
.tbl th.num{color:#fff}
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
/* Aperçu intégré à la page de consultation : la feuille seule, sans barre */
@media screen{body.apercu{padding:18px 0 28px;background:#eef2f7}body.apercu .page{background:#fff;max-width:794px;padding:56px 64px;box-shadow:0 2px 14px rgba(15,23,42,.12);border-radius:2px}}
.blocs{display:flex;flex-wrap:wrap;gap:8px 14px;margin-bottom:10px}
.blocs .bloc{width:100%;min-width:0}
.blocs .bloc.demi{width:calc(50% - 7px)}
.blocs .bloc:empty{display:none}
.bloc h4{font-size:10.5pt;color:#2E74B5;margin:4px 0 2px;padding-bottom:2px;border-bottom:1px dotted #9FBFE0}
.commentaire .com-tete{font-size:8.5pt;color:#64748b;font-weight:700;margin-bottom:2px}
.commentaire{font-size:10pt}
.verdict{display:flex;align-items:center;gap:8px;margin:2px 0 6px}
.verdict-lib{font-size:8.5pt;font-weight:700;color:#1F4E79;text-transform:uppercase;letter-spacing:.4px}
.verdict-val{display:inline-block;padding:2px 12px;border-radius:999px;font-size:9.5pt;font-weight:700;border:1px solid}
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:8px;margin:6px 0 10px}
.kpi{background:linear-gradient(180deg,#F5F9FE,#EAF2FB);border:1px solid #dbe7f5;border-radius:6px;padding:7px 10px;page-break-inside:avoid}
.kpi .v{font-size:14pt;font-weight:800;color:#1F4E79}
.kpi .l{font-size:8pt;font-weight:700;color:#2E74B5;text-transform:uppercase;letter-spacing:.3px}
.kpi .d{font-size:8pt;color:#64748b;margin-top:1px}
</style>
@php $mef = $cr->getDonnees()['mise_en_forme'] ?? []; @endphp
<style>
@if($mef['num_page'] ?? true)@page{@bottom-right{content:"Page " counter(page) " / " counter(pages);font-size:8pt;color:#94a3b8}}@endif
@if(!empty($mef['titre'])).header h1{font-size:{{ (float) $mef['titre'] }}pt}@endif
@if(!empty($mef['section'])).section h2{font-size:{{ (float) $mef['section'] }}pt}@endif
@if(!empty($mef['sous_titre'])).section h3{font-size:{{ (float) $mef['sous_titre'] }}pt}@endif
@if(!empty($mef['couleur_section'])).section h2{color:#{{ $mef['couleur_section'] }};border-bottom-color:#{{ $mef['couleur_section'] }}}@endif
@if(!empty($mef['couleur_sous_titre'])).section h3{color:#{{ $mef['couleur_sous_titre'] }}}@endif
@if(!empty($mef['texte'])).section .contenu{font-size:{{ (float) $mef['texte'] }}pt}.tbl,.info-tbl{font-size:{{ round((float) $mef['texte'] * 0.95, 2) }}pt}.tbl th{font-size:{{ round((float) $mef['texte'] * 0.9, 2) }}pt}@endif
</style>
</head>
@php $apercu = request()->boolean('apercu'); @endphp
<body class="{{ $apercu ? 'apercu' : '' }}">

@unless($apercu)
<div class="print-bar no-print">
    <button class="btn-print" onclick="window.print()">Imprimer / Enregistrer PDF</button>
    <a href="{{ route('comptes-rendus.show', $cr) }}" class="btn-retour">Retour</a>
</div>
@endunless

<div class="page">
    {{-- EN-TÊTE --}}
    <div class="header">
        @php $logoPath = public_path('images/white_logo_v.png'); @endphp
        @if(file_exists($logoPath) && ($mef['logo'] ?? true))
        <div class="logo-cercle"><img src="data:image/png;base64,{{ base64_encode(file_get_contents($logoPath)) }}" alt="SOUMAFÊ"></div>
        @endif
        <div class="societe">{{ $mef['entete'] ?? 'SOUMAFÊ - Pilotage des opérations' }}</div>
        @if($cr->estReunion())
        <h1>Compte rendu de {{ $cr->type === 'reunion_ordinaire' ? 'réunion ordinaire' : 'réunion extraordinaire' }}</h1>
        @else
        <h1>Rapport de Review Sprint</h1>
        @endif
        <div class="sous">{{ $cr->titre }}</div>
    </div>

    {{-- INFORMATIONS --}}
    @if($cr->type === 'demo_sprint')
    @php $spI = $cr->sprint; $dI = $cr->getDonnees(); $goalI = trim((string) ($dI['objectif'] ?? $spI?->objectif ?? '')); @endphp
    <table class="info-tbl syno">
        <tr><td class="lib">Sprint</td><td>@if($spI)<strong>Sprint {{ $spI->numero }}</strong> · {{ $spI->projet?->libelle }} <span class="gris">· du {{ $spI->date_debut->format('d/m/Y') }} au {{ $spI->date_fin->format('d/m/Y') }}</span>@else — @endif</td>
            <td class="lib">Score</td><td><strong>{{ $dI['score_pourcent'] ?? 0 }} %</strong> <span class="gris">· {{ $dI['points_realises'] ?? 0 }}/{{ $dI['total_points'] ?? 0 }} pts</span></td></tr>
        <tr><td class="lib">Sprint Goal</td><td colspan="3">@include('comptes-rendus._objectifs', ['texte' => $goalI])</td></tr>
        <tr><td class="lib">Date de la review</td><td>{{ $cr->date_reunion->format('d/m/Y') }}</td>
            <td class="lib">Horaires</td><td>{{ $cr->heure_debut ? substr($cr->heure_debut, 0, 5) : '—' }}{{ $cr->heure_fin ? ' → ' . substr($cr->heure_fin, 0, 5) : '' }}</td></tr>
        <tr><td class="lib">Lieu</td><td>{{ $cr->lieu ?: '—' }}</td>
            <td class="lib">Rédacteur</td><td>{{ $cr->redacteur?->nomComplet() ?? '—' }}</td></tr>
    </table>
    @elseif($cr->estReunion())
    <table class="info-tbl syno">
        <tr><td class="lib">Date</td><td>{{ $cr->date_reunion->format('d/m/Y') }}</td>
            <td class="lib">Type de réunion</td><td>{{ $cr->type === 'reunion_extraordinaire' ? 'Réunion extraordinaire' : 'Réunion ordinaire' }}</td></tr>
        <tr><td class="lib">Présidée par</td><td>{{ $cr->president?->nomComplet() ?? '—' }}</td>
            <td class="lib">Rédigé par</td><td>{{ $cr->redacteur?->nomComplet() ?? '—' }}</td></tr>
        <tr><td class="lib">Horaires</td><td>{{ $cr->heure_debut ? substr($cr->heure_debut, 0, 5) : '—' }}{{ $cr->heure_fin ? ' → ' . substr($cr->heure_fin, 0, 5) : '' }}</td>
            <td class="lib">Lieu</td><td>{{ $cr->lieu ?: '—' }}</td></tr>
    </table>
    @else
    <table class="info-tbl">
        <tr><td>Date</td><td>{{ $cr->date_reunion->format('d/m/Y') }}</td></tr>
        <tr><td>Horaires</td><td>{{ $cr->heure_debut ?? '—' }} - {{ $cr->heure_fin ?? '—' }}</td></tr>
        <tr><td>Lieu</td><td>{{ $cr->lieu ?? '—' }}</td></tr>
        @if($cr->estReunion() && $cr->president)
        <tr><td>Président</td><td>{{ $cr->president->nomComplet() }}</td></tr>
        @endif
        <tr><td>Rédacteur</td><td>{{ $cr->redacteur?->nomComplet() ?? '—' }}</td></tr>
        <tr><td>Désigné par</td><td>{{ $cr->designePar?->nomComplet() ?? '—' }}</td></tr>
        @if($cr->sprint)
        <tr><td>Sprint</td><td>Sprint {{ $cr->sprint->numero }} - {{ $cr->sprint->projet?->libelle ?? '' }}</td></tr>
        @endif
        @if($cr->type === 'demo_sprint' && ($goalInfo = trim((string) ($cr->getDonnees()['objectif'] ?? $cr->sprint?->objectif ?? ''))))
        <tr><td>Sprint Goal</td><td>@include('comptes-rendus._objectifs', ['texte' => $goalInfo])</td></tr>
        @endif
    </table>
    @endif

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
        {{-- ===== DEMO SPRINT - FORMAT BLOCS ===== --}}
        @php
        $refStyles = [
            'rappel' => ['Rappel', '#E8F5E9', '#00897B'], 'fondamental' => ['Fondamental', '#FFF8E1', '#F9A825'],
            'attention' => ['Attention', '#FFF3E0', '#E6A817'], 'remarque' => ['Remarque', '#E8EEF4', '#2E74B5'],
            'methode' => ['Méthode', '#F3E8F9', '#7B2D8E'], 'conseil' => ['Conseil', '#FFF8E1', '#EF6C00'],
            'definition' => ['Définition', '#E3F2FD', '#1565C0'], 'exemple' => ['Exemple', '#F1F8E9', '#558B2F'],
            'bonne_pratique' => ['Bonne pratique', '#E0F2FE', '#0288D1'], 'blocage' => ['Blocage', '#FEECEC', '#CC3333'],
        ];
        @endphp
        {{-- Seules les parties renseignées sont imprimées (numérotation continue) --}}
        @php $indRapport = \App\Services\RapportReview::indicateurs($d, $cr->sprint); @endphp
        @foreach(\App\Services\RapportReview::modulesVisibles($d) as $mi => $mod)
        <div class="section">
            <h2>{{ $mi + 1 }}. {{ $mod['titre'] ?? 'Module' }}</h2>
            @if(!empty($mod['objectif']))
            <div class="contenu" style="font-style:italic;color:#64748b;margin-bottom:6px">{{ $mod['objectif'] }}</div>
            @endif

            @foreach($mod['sections'] ?? [] as $si => $sec)
                @if(!empty($sec['titre']))
                <h3>{{ $mi + 1 }}.{{ $si + 1 }}. {{ $sec['titre'] }}</h3>
                @endif
                @php $k = 0; @endphp
                <div class="blocs">
                @foreach($sec['blocs'] ?? [] as $bloc)
                    @php
                        $coul = \App\Http\Controllers\CompteRenduController::BLOC_COULEURS[$bloc['couleur'] ?? ''] ?? null;
                        $styleBloc = $coul ? "background:#{$coul[1]};border:1px solid #" . \App\Http\Controllers\CompteRenduController::teinte($coul[2]) . ";padding:8px 12px;border-radius:6px" : '';
                        $lignesSt = \App\Http\Controllers\CompteRenduController::lignesModule($d['stories'] ?? [], $bloc['module'] ?? null, false);
                        $lignesT = \App\Http\Controllers\CompteRenduController::lignesModule($d['taches'] ?? [], $bloc['module'] ?? null, true);
                    @endphp
                    <div class="bloc {{ ($bloc['largeur'] ?? '') === 'demi' ? 'demi' : '' }}" style="{{ $styleBloc }}">
                    @if($bloc['type'] === 'verdict')
                        @php [$vLib, $vC, $vB] = \App\Services\RapportReview::VERDICTS[$bloc['verdict'] ?? ''] ?? [null, '', '']; @endphp
                        @if($vLib)<div class="verdict"><span class="verdict-lib">Verdict du client</span><span class="verdict-val" style="color:#{{ $vC }};background:#{{ $vB }};border-color:#{{ \App\Http\Controllers\CompteRenduController::teinte($vC) }}">{{ $vLib }}</span></div>@endif
                        @if(trim(strip_tags($bloc['contenu'] ?? '')) !== '')<div class="contenu" style="--num-prefix:'{{ $mi + 1 }}.{{ $si + 1 }}.'">{!! $bloc['contenu'] !!}</div>@endif
                    @elseif($bloc['type'] === 'indicateurs')
                        <div class="kpis">
                            @foreach(\App\Services\RapportReview::cartes($indRapport) as [$kv, $kl, $kd])
                            <div class="kpi"><div class="v">{{ $kv }}</div><div class="l">{{ $kl }}</div>@if($kd)<div class="d">{{ $kd }}</div>@endif</div>
                            @endforeach
                        </div>
                        @if(count($indRapport['par_dev']) > 1)
                        <table class="tbl">
                            <thead><tr><th>Développeur</th><th style="width:90px;text-align:center">Réalisé</th><th style="width:90px;text-align:center">Engagé</th><th style="width:70px;text-align:center">%</th></tr></thead>
                            <tbody>@foreach($indRapport['par_dev'] as $dv)<tr><td>{{ $dv['nom'] }}</td><td style="text-align:center">{{ $dv['realise'] }} pts</td><td style="text-align:center">{{ $dv['total'] }} pts</td><td style="text-align:center;font-weight:700">{{ $dv['pourcent'] }} %</td></tr>@endforeach</tbody>
                        </table>
                        @endif
                    @elseif($bloc['type'] === 'sous_titre' && trim($bloc['texte'] ?? '') !== '')
                        @php $k++; @endphp
                        <h4 style="{{ $coul ? 'color:#' . $coul[2] : '' }}">{{ $mi + 1 }}.{{ $si + 1 }}.{{ $k }}. {{ $bloc['texte'] }}</h4>
                    @elseif($bloc['type'] === 'commentaire' && trim($bloc['contenu'] ?? '') !== '')
                        <div class="commentaire" style="{{ $coul ? '' : 'background:#f8fafc;border:1px solid #dfe5ec;padding:8px 12px;border-radius:6px' }}">
                            <div class="com-tete">Commentaire · {{ $bloc['auteur'] ?? '' }} · {{ $bloc['date'] ?? '' }}</div>
                            <div style="white-space:pre-line">{{ $bloc['contenu'] }}</div>
                        </div>
                    @elseif($bloc['type'] === 'texte' && !empty($bloc['contenu']))
                        <div class="contenu" style="--num-prefix:'{{ $mi + 1 }}.{{ $si + 1 }}.'">{!! $bloc['contenu'] !!}</div>
                    @elseif($bloc['type'] === 'tableau_stories' && $lignesSt)
                        <table class="tbl">
                            <thead><tr>@unless($bloc['module'] ?? null)<th>Module › Epic</th>@endunless<th>User story</th><th style="width:110px">Exécution</th><th style="width:58px;text-align:center">Tâches</th><th style="width:40px;text-align:center">Pts</th><th style="width:80px">Statut</th></tr></thead>
                            <tbody>
                            @php $grp = null; @endphp
                            @foreach($lignesSt as $st)
                            @php $ctx = \App\Http\Controllers\CompteRenduController::libelleModule($d, $st['module'] ?? '') . (($st['epic'] ?? null) ? ' › ' . $st['epic'] : ''); @endphp
                            <tr>
                                @unless($bloc['module'] ?? null)<td style="color:#475569;font-size:.8rem">{{ $ctx !== $grp ? $ctx : '' }}</td>@endunless
                                <td><strong>{{ $st['numero'] ?? '' }}</strong> {{ $st['intitule'] ?? '' }}@if(($bloc['module'] ?? null) && !empty($st['epic']))<div style="font-size:8.5pt;color:#64748b">{{ $st['epic'] }}</div>@endif</td>
                                <td><span style="display:inline-block;width:40px;height:5px;background:#e5eaf1;border-radius:5px;vertical-align:middle;overflow:hidden"><span style="display:block;height:100%;width:{{ $st['niveau'] ?? 0 }}%;background:{{ ($st['niveau'] ?? 0) >= 100 ? '#16a34a' : '#2e74b5' }}"></span></span> <strong>{{ $st['niveau'] ?? 0 }} %</strong></td>
                                <td style="text-align:center">{{ $st['taches_terminees'] ?? 0 }}/{{ $st['taches'] ?? 0 }}</td>
                                <td style="text-align:center">{{ $st['points'] ?? '-' }}</td>
                                <td>{{ $st['statut'] ?? '' }}</td>
                            </tr>
                            @php $grp = $ctx; @endphp
                            @endforeach
                            </tbody>
                        </table>
                    @elseif($bloc['type'] === 'tableau_taches' && $lignesT)
                        @foreach(\App\Http\Controllers\CompteRenduController::groupesTaches($lignesT, $bloc['module'] ?? null) as $grpNom => $grpLignes)
                        @if($grpNom !== '')<div class="grp-tache">{{ \App\Http\Controllers\CompteRenduController::libelleModule($d, $grpNom) }}</div>@endif
                        <table class="tbl">
                            <thead><tr><th class="num">N°</th><th>Tâche</th><th style="width:36px;text-align:center">Pts</th><th style="width:135px">Attribution</th><th style="width:40px;text-align:center">État</th><th style="width:135px">Observation</th></tr></thead>
                            <tbody>
                            @foreach($grpLignes as $i => $t)
                            <tr>
                                <td class="num">{{ $i + 1 }}</td>
                                <td>{{ $t['tache'] }}@if(!empty($t['us']))<div style="font-size:8.5pt;color:#64748b">{{ $t['us'] }}</div>@endif</td>
                                <td style="text-align:center">{{ $t['points'] }}</td>
                                <td>{{ $t['attribution'] }}</td>
                                <td class="{{ ($t['termine'] ?? false) ? 'ok' : 'ko' }}">{!! ($t['termine'] ?? false) ? '&#10003;' : '&#10007;' !!}</td>
                                <td style="font-size:9.5pt;color:#64748b">{{ \App\Models\Tache::libelleQualification($t['observation'] ?? '') }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                        @endforeach
                    @elseif($bloc['type'] === 'score')
                        <div class="score-box">
                            <div class="val">{{ $d['points_realises'] ?? 0 }} / {{ $d['total_points'] ?? 0 }}</div>
                            <div class="lab">Score global - {{ $d['score_pourcent'] ?? 0 }}%</div>
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
                                <td>{{ !empty($act['echeance']) && strtotime($act['echeance']) ? \Carbon\Carbon::parse($act['echeance'])->format('d/m/Y') : '—' }}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @elseif($bloc['type'] === 'tableau_backlog' && !empty($bloc['items']))
                        <table class="tbl">
                            <thead><tr><th style="width:70px">N°</th><th style="width:22%">Module</th><th>User story</th><th style="width:90px">Priorité</th><th style="width:36px;text-align:center">Pts</th></tr></thead>
                            <tbody>
                            @foreach($bloc['items'] as $it)
                            <tr><td>{{ ($it['numero'] ?? '') ?: '—' }}</td><td>{{ ($it['module'] ?? '') ?: '—' }}</td><td>{{ $it['intitule'] ?? '' }}</td><td>{{ \App\Models\UserStory::MOSCOW[$it['moscow'] ?? ''] ?? '—' }}</td><td style="text-align:center">{{ $it['points'] ?? '—' }}</td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    @elseif($bloc['type'] === 'validation' && !empty($bloc['items']))
                        <table class="tbl">
                            <thead><tr><th style="width:30%">Qualité</th><th>Nom</th><th style="width:90px">Date</th><th style="width:28%">Signature</th></tr></thead>
                            <tbody>
                            @foreach($bloc['items'] as $it)
                            <tr style="height:42px"><td>{{ $it['role'] ?? '' }}</td><td>{{ $it['nom'] ?? '' }}</td><td>{{ !empty($it['date']) ? \Carbon\Carbon::parse($it['date'])->format('d/m/Y') : '' }}</td><td></td></tr>
                            @endforeach
                            </tbody>
                        </table>
                    @elseif($bloc['type'] === 'reference' && !empty($bloc['contenu']))
                        @php [$rLabel, $rBg, $rBorder] = $refStyles[$bloc['ref_type'] ?? 'remarque'] ?? $refStyles['remarque']; @endphp
                        <div style="background:{{ $rBg }};border:1px solid #{{ \App\Http\Controllers\CompteRenduController::teinte($rBorder) }};border-radius:6px;padding:8px 12px">
                            <div style="font-weight:700;color:{{ $rBorder }};font-size:9.5pt;margin-bottom:3px;text-transform:uppercase">{{ $rLabel }}</div>
                            <div class="contenu" style="padding:0;--num-prefix:'{{ $mi + 1 }}.{{ $si + 1 }}.'">{!! $bloc['contenu'] !!}</div>
                        </div>
                    @endif
                    </div>
                @endforeach
                </div>
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
                    <td style="font-size:9.5pt;color:#64748b">{{ \App\Models\Tache::libelleQualification($t['observation'] ?? '') }}</td>
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
                <div class="lab">Score global - {{ $d['score_pourcent'] ?? 0 }}%</div>
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
        <span>{{ $mef['pied'] ?? 'SOUMAFÊ - Document généré le ' . now()->format('d/m/Y à H:i') }}</span>
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
