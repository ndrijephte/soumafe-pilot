<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reporting SOUMAFE - {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;font-size:11px;color:#1e293b;line-height:1.5;padding:20px 30px}

        .entete{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #16385c;padding-bottom:12px;margin-bottom:16px}
        .entete h1{font-size:18px;color:#16385c;font-weight:700}
        .entete .sous{font-size:11px;color:#64748b;margin-top:2px}
        .entete .meta{text-align:right;font-size:10px;color:#64748b}

        .section{margin-bottom:18px;page-break-inside:avoid}
        .section-titre{font-size:13px;font-weight:700;color:#16385c;padding:6px 0;border-bottom:1px solid #cbd5e1;margin-bottom:8px}

        .kpi-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
        .kpi-box{flex:1;min-width:90px;text-align:center;border:1px solid #e2e8f0;border-radius:6px;padding:8px 4px}
        .kpi-box .val{font-size:18px;font-weight:700;color:#16385c}
        .kpi-box .lab{font-size:9px;text-transform:uppercase;color:#64748b;margin-top:1px}
        .kpi-box.ok .val{color:#16a34a}.kpi-box.ko .val{color:#dc2626}.kpi-box.warn .val{color:#b45309}

        table{width:100%;border-collapse:collapse;font-size:10.5px;margin-bottom:10px}
        th{text-align:left;padding:5px 6px;background:#f1f5f9;color:#475569;font-weight:600;font-size:9.5px;text-transform:uppercase;border-bottom:1px solid #cbd5e1}
        td{padding:5px 6px;border-bottom:1px solid #e2e8f0}
        tr:nth-child(even){background:#fafbfc}

        .badge{padding:1px 6px;border-radius:6px;font-size:9px;font-weight:600}
        .b-ok{background:#dcfce7;color:#16a34a}.b-ko{background:#fee2e2;color:#dc2626}.b-warn{background:#fef3c7;color:#b45309}

        .bar{display:inline-block;height:6px;border-radius:3px;background:#e2e8f0;width:50px;vertical-align:middle}
        .bar-fill{height:100%;border-radius:3px;background:#2e74b5}
        .bar-ok .bar-fill{background:#16a34a}.bar-warn .bar-fill{background:#b45309}.bar-ko .bar-fill{background:#dc2626}

        .pied{border-top:2px solid #16385c;padding-top:8px;margin-top:20px;font-size:9px;color:#64748b;display:flex;justify-content:space-between}

        /* Barre d'options (masquée à l'impression) */
        .no-print{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:16px}
        .no-print h3{font-size:13px;color:#16385c;margin-bottom:8px}
        .opt-grid{display:flex;gap:16px;flex-wrap:wrap;font-size:12px}
        .opt-grid label{display:flex;align-items:center;gap:4px;cursor:pointer}
        .opt-grid input[type=checkbox]{accent-color:#2e74b5}
        .btn-print{background:#16385c;color:#fff;border:none;padding:8px 20px;border-radius:6px;font-size:13px;cursor:pointer;margin-top:10px}

        @media print{
            .no-print{display:none!important}
            body{padding:10px 15px;font-size:10px}
            .entete{margin-bottom:10px;padding-bottom:8px}
            .section{page-break-inside:avoid}
            @page{margin:12mm 10mm;size:A4 landscape}
        }
    </style>
</head>
<body>

{{-- Barre d'options (visible seulement à l'écran) --}}
<div class="no-print">
    <h3>Options d'impression</h3>
    <form method="GET" action="{{ route('reporting.imprimer') }}" id="optForm">
        <input type="hidden" name="periode" value="{{ $periode }}">
        <input type="hidden" name="date_debut" value="{{ $debut->format('Y-m-d') }}">
        <input type="hidden" name="date_fin" value="{{ $fin->format('Y-m-d') }}">

        <div style="margin-bottom:8px;font-size:12px;font-weight:600;color:#475569">Sections à inclure :</div>
        <div class="opt-grid">
            <label><input type="checkbox" name="sections[]" value="kpi" {{ in_array('kpi', $sections) ? 'checked' : '' }}> KPI globaux</label>
            <label><input type="checkbox" name="sections[]" value="collaborateurs" {{ in_array('collaborateurs', $sections) ? 'checked' : '' }}> Collaborateurs</label>
            <label><input type="checkbox" name="sections[]" value="projets" {{ in_array('projets', $sections) ? 'checked' : '' }}> Projets</label>
            <label><input type="checkbox" name="sections[]" value="sprints" {{ in_array('sprints', $sections) ? 'checked' : '' }}> Sprints</label>
            <label><input type="checkbox" name="sections[]" value="absences" {{ in_array('absences', $sections) ? 'checked' : '' }}> Absences</label>
            <label><input type="checkbox" name="sections[]" value="evenements" {{ in_array('evenements', $sections) ? 'checked' : '' }}> &Eacute;v&eacute;nements</label>
        </div>

        <div style="margin:10px 0 4px;font-size:12px;font-weight:600;color:#475569">Colonnes collaborateurs :</div>
        <div class="opt-grid">
            <label><input type="checkbox" name="cols_collab[]" value="nom" {{ in_array('nom', $colsCollab) ? 'checked' : '' }}> Nom</label>
            <label><input type="checkbox" name="cols_collab[]" value="pole" {{ in_array('pole', $colsCollab) ? 'checked' : '' }}> P&ocirc;le</label>
            <label><input type="checkbox" name="cols_collab[]" value="total" {{ in_array('total', $colsCollab) ? 'checked' : '' }}> Total</label>
            <label><input type="checkbox" name="cols_collab[]" value="periode" {{ in_array('periode', $colsCollab) ? 'checked' : '' }}> P&eacute;riode</label>
            <label><input type="checkbox" name="cols_collab[]" value="retard" {{ in_array('retard', $colsCollab) ? 'checked' : '' }}> Retard</label>
            <label><input type="checkbox" name="cols_collab[]" value="bloquees" {{ in_array('bloquees', $colsCollab) ? 'checked' : '' }}> Bloqu&eacute;es</label>
            <label><input type="checkbox" name="cols_collab[]" value="taux" {{ in_array('taux', $colsCollab) ? 'checked' : '' }}> Taux</label>
            <label><input type="checkbox" name="cols_collab[]" value="objectifs" {{ in_array('objectifs', $colsCollab) ? 'checked' : '' }}> Objectifs</label>
            <label><input type="checkbox" name="cols_collab[]" value="assiduite" {{ in_array('assiduite', $colsCollab) ? 'checked' : '' }}> Assiduit&eacute;</label>
        </div>

        <div style="display:flex;gap:8px;margin-top:12px">
            <button type="submit" class="btn-print" style="background:#2e74b5">Appliquer les filtres</button>
            <button type="button" class="btn-print" onclick="window.print()">Imprimer / Exporter PDF</button>
        </div>
    </form>
</div>

{{-- En-tête --}}
<div class="entete">
    <div>
        <h1>SOUMAFE - Reporting d'activit&eacute;</h1>
        <div class="sous">P&eacute;riode : {{ $debut->translatedFormat('d F Y') }} - {{ $fin->translatedFormat('d F Y') }}</div>
    </div>
    <div class="meta">
        <div>G&eacute;n&eacute;r&eacute; le {{ now()->translatedFormat('d/m/Y à H:i') }}</div>
        <div>Par : {{ auth()->user()->nomComplet() }}</div>
    </div>
</div>

{{-- KPI --}}
@if(in_array('kpi', $sections))
<div class="section">
    <div class="section-titre">Indicateurs cl&eacute;s</div>
    <div class="kpi-row">
        <div class="kpi-box"><div class="val">{{ $creees }}</div><div class="lab">T&acirc;ches cr&eacute;&eacute;es</div></div>
        <div class="kpi-box ok"><div class="val">{{ $terminees }}</div><div class="lab">T&acirc;ches termin&eacute;es</div></div>
        <div class="kpi-box"><div class="val">{{ $aTemps }}</div><div class="lab">&Agrave; temps</div></div>
        <div class="kpi-box warn"><div class="val">{{ $anticipees }}</div><div class="lab">Anticip&eacute;es</div></div>
        <div class="kpi-box ko"><div class="val">{{ $enRetardClotures }}</div><div class="lab">Retard (cl&ocirc;t.)</div></div>
        <div class="kpi-box {{ $enRetardOuvert > 0 ? 'ko' : '' }}"><div class="val">{{ $enRetardOuvert }}</div><div class="lab">Retard ouvert</div></div>
        <div class="kpi-box {{ $bloquees > 0 ? 'ko' : '' }}"><div class="val">{{ $bloquees }}</div><div class="lab">Bloqu&eacute;es</div></div>
        <div class="kpi-box"><div class="val">{{ $tauxGlobal }}%</div><div class="lab">Compl&eacute;tion</div></div>
    </div>
</div>
@endif

{{-- Collaborateurs --}}
@if(in_array('collaborateurs', $sections))
<div class="section">
    <div class="section-titre">Performance par collaborateur</div>
    <table>
        <thead><tr>
            @if(in_array('nom', $colsCollab))<th>Collaborateur</th>@endif
            @if(in_array('pole', $colsCollab))<th>P&ocirc;le</th>@endif
            @if(in_array('total', $colsCollab))<th>Total</th>@endif
            @if(in_array('periode', $colsCollab))<th>P&eacute;riode</th>@endif
            @if(in_array('retard', $colsCollab))<th>Retard</th>@endif
            @if(in_array('bloquees', $colsCollab))<th>Bloq.</th>@endif
            @if(in_array('taux', $colsCollab))<th>Taux</th>@endif
            @if(in_array('objectifs', $colsCollab))<th>Objectifs</th>@endif
            @if(in_array('assiduite', $colsCollab))<th>Assiduit&eacute;</th>@endif
        </tr></thead>
        <tbody>
        @foreach($collaborateurs as $c)
        @php $s = $c->stats; @endphp
        <tr>
            @if(in_array('nom', $colsCollab))<td><strong>{{ $c->nomComplet() }}</strong></td>@endif
            @if(in_array('pole', $colsCollab))<td>{{ $c->pole?->libelle ?? '—' }}</td>@endif
            @if(in_array('total', $colsCollab))<td>{{ $s['terminees'] }}/{{ $s['total'] }}</td>@endif
            @if(in_array('periode', $colsCollab))<td><strong>{{ $s['terminees_periode'] }}</strong></td>@endif
            @if(in_array('retard', $colsCollab))<td>@if($s['en_retard'] > 0)<span class="badge b-ko">{{ $s['en_retard'] }}</span>@else - @endif</td>@endif
            @if(in_array('bloquees', $colsCollab))<td>@if($s['bloquees'] > 0)<span class="badge b-warn">{{ $s['bloquees'] }}</span>@else - @endif</td>@endif
            @if(in_array('taux', $colsCollab))
            <td>
                @php $coulBar = $s['taux'] >= 70 ? 'bar-ok' : ($s['taux'] >= 40 ? 'bar-warn' : 'bar-ko'); @endphp
                <span class="bar {{ $coulBar }}"><span class="bar-fill" style="width:{{ $s['taux'] }}%"></span></span>
                <span style="font-size:10px;font-weight:600">{{ $s['taux'] }}%</span>
            </td>@endif
            @if(in_array('objectifs', $colsCollab))
            <td>
                @php $sObj = $s['score_objectifs']; @endphp
                @if($sObj !== null)<span class="badge {{ $sObj >= 70 ? 'b-ok' : ($sObj >= 40 ? 'b-warn' : 'b-ko') }}">{{ $sObj }}%</span>@else - @endif
            </td>@endif
            @if(in_array('assiduite', $colsCollab))
            <td><span class="badge {{ $s['assiduite'] >= 80 ? 'b-ok' : ($s['assiduite'] >= 50 ? 'b-warn' : 'b-ko') }}">{{ $s['assiduite'] }}%</span></td>
            @endif
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Projets --}}
@if(in_array('projets', $sections))
<div class="section">
    <div class="section-titre">R&eacute;partition par projet</div>
    <table>
        <thead><tr><th>Projet</th><th>T&acirc;ches</th><th>Termin.</th><th>Retard</th><th>Bloq.</th><th>Progression</th></tr></thead>
        <tbody>
        @foreach($projets as $p)
        @php $pct = $p->taches_count > 0 ? round(($p->terminees_count / $p->taches_count) * 100) : 0; @endphp
        <tr>
            <td><strong>{{ $p->libelle }}</strong></td>
            <td>{{ $p->taches_count }}</td>
            <td>{{ $p->terminees_count }}</td>
            <td>@if($p->en_retard_count > 0)<span class="badge b-ko">{{ $p->en_retard_count }}</span>@else - @endif</td>
            <td>@if($p->bloquees_count > 0)<span class="badge b-warn">{{ $p->bloquees_count }}</span>@else - @endif</td>
            <td>
                <span class="bar"><span class="bar-fill" style="width:{{ $pct }}%"></span></span>
                <span style="font-size:10px;font-weight:600">{{ $pct }}%</span>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Sprints --}}
@if(in_array('sprints', $sections) && $sprints->count() > 0)
<div class="section">
    <div class="section-titre">Sprints en cours</div>
    <table>
        <thead><tr><th>Sprint</th><th>Projet</th><th>Objectif</th><th>T&acirc;ches</th><th>Progression</th></tr></thead>
        <tbody>
        @foreach($sprints as $sp)
        <tr>
            <td><strong>Sprint {{ $sp->numero }}</strong></td>
            <td>{{ $sp->projet->libelle ?? '' }}</td>
            <td>{{ $sp->objectif ?? '—' }}</td>
            <td>{{ $sp->stats_terminees }}/{{ $sp->stats_total }}</td>
            <td>
                <span class="bar"><span class="bar-fill" style="width:{{ $sp->progression }}%"></span></span>
                <span style="font-size:10px;font-weight:600">{{ $sp->progression }}%</span>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Absences --}}
@if(in_array('absences', $sections) && $absences->count() > 0)
<div class="section">
    <div class="section-titre">Absences signal&eacute;es ({{ $absences->count() }})</div>
    <table>
        <thead><tr><th>Collaborateur</th><th>Type</th><th>Du</th><th>Au</th><th>Motif</th><th>D&eacute;clar&eacute; par</th></tr></thead>
        <tbody>
        @foreach($absences as $abs)
        <tr>
            <td><strong>{{ $abs->user->nomComplet() ?? '—' }}</strong></td>
            <td>{{ $abs->labelType() }}</td>
            <td>{{ $abs->date_debut->format('d/m/Y') }}</td>
            <td>{{ $abs->date_fin->format('d/m/Y') }}</td>
            <td>{{ $abs->motif ?? '—' }}</td>
            <td>{{ $abs->declarant?->nomComplet() ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Événements --}}
@if(in_array('evenements', $sections) && $evenements->count() > 0)
<div class="section">
    <div class="section-titre">&Eacute;v&eacute;nements de la p&eacute;riode ({{ $evenements->count() }})</div>
    <table>
        <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Collaborateur</th><th>Impact</th></tr></thead>
        <tbody>
        @foreach($evenements as $e)
        <tr>
            <td>{{ $e->date->format('d/m') }}</td>
            <td>{{ $e->labelType() }}</td>
            <td>{{ $e->description }}</td>
            <td>{{ $e->user->nomComplet() }}</td>
            <td>{{ $e->impact ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Pied de page --}}
<div class="pied">
    <span>SOUMAFE PILOT - Document confidentiel</span>
    <span>{{ now()->translatedFormat('d/m/Y H:i') }}</span>
</div>

</body>
</html>
