@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Espace projet - ' . $projet->libelle)

@push('styles')
<style>
.ep-tete{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;margin-bottom:14px}
.ep-tete .meta{font-size:13px;color:var(--gris);margin-top:2px}
.ep-tabs{display:flex;gap:4px;border-bottom:2px solid var(--bord);margin-bottom:16px;flex-wrap:wrap}
.ep-tab{background:none;border:none;padding:9px 16px;font-size:13.5px;font-weight:600;color:var(--gris);cursor:pointer;border-bottom:3px solid transparent;margin-bottom:-2px}
.ep-tab.actif{color:var(--bleu);border-bottom-color:var(--accent)}
.ep-tab .nb{background:#e2e8f0;color:#475569;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px}
.ep-panel{display:none}.ep-panel.actif{display:block}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11.5px}
.btn.ko{background:#fff;color:var(--ko);border:1px solid #fecaca}
.alerte{border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.tbl{width:100%;border-collapse:collapse;font-size:13px}
.tbl th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;vertical-align:middle}
.tbl td{padding:7px 10px;border-bottom:1px solid var(--bord);vertical-align:middle}
.tbl tbody tr:hover td{background:#f8fafc}
.tbl td form{margin:0}
.tbl tr.inactif td{opacity:.5}
.pill{display:inline-block;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.p-ok{background:#dcfce7;color:#166534}.p-cours{background:#dbeafe;color:#1d4ed8}.p-plan{background:#ede9fe;color:#6d28d9}.p-attente{background:#f1f5f9;color:#475569}.p-haute{background:#fee2e2;color:#991b1b}.p-moyenne{background:#fef3c7;color:#92400e}.p-basse{background:#f1f5f9;color:#475569}
.module-bloc{background:#fff;border:1px solid var(--bord);border-radius:12px;margin-bottom:14px;overflow:hidden}
.module-titre{display:grid;grid-template-columns:minmax(0,1fr) auto 140px;gap:14px;align-items:center;padding:12px 16px;background:linear-gradient(#fbfcfe,#f3f6fa);border-bottom:1px solid var(--bord)}
.module-titre .nom{font-size:14px;font-weight:700;color:#1f4e79;text-transform:uppercase;letter-spacing:.5px}
.module-titre .stats,.epic-titre .stats{font-size:12px;color:#475569;white-space:nowrap}
.module-corps{padding:10px 14px 4px}
.epic-titre{display:grid;grid-template-columns:minmax(0,1fr) auto 110px;gap:12px;align-items:center;margin:6px 0 8px;padding:6px 0 6px 10px;border-left:3px solid #93c5fd}
.epic-titre .nom{font-size:14.5px;font-weight:700;color:#1f4e79}
.epic-titre .nom small{font-weight:700;color:#2e74b5;text-transform:uppercase;letter-spacing:.4px;font-size:11px;margin-right:8px}
.prog{height:6px;background:#e5eaf1;border-radius:6px;overflow:hidden}.prog>i{display:block;height:100%;background:#2e74b5;border-radius:6px}
.prog.fini>i{background:#16a34a}
.pct{font-size:11.5px;font-weight:700;color:#1f4e79;margin-left:6px}
.puiser{display:flex;align-items:center;flex-wrap:wrap;gap:8px;padding:8px 12px;margin-bottom:12px;background:#f8fafc;border:1px solid var(--bord);border-radius:10px;font-size:12.5px;color:#475569}
.puiser select{padding:5px 8px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.puiser .btn:disabled{opacity:.5;cursor:not-allowed}
.us-coche{flex:none;margin-top:3px;width:15px;height:15px;accent-color:#2e74b5;cursor:pointer}
.us-coche:disabled{cursor:not-allowed;opacity:.4}
.us-prog{display:flex;align-items:center;gap:8px;min-width:150px}.us-prog .prog{flex:1}
@media(max-width:700px){.module-titre,.epic-titre{grid-template-columns:1fr}.module-titre .stats,.epic-titre .stats{white-space:normal}}
.pb-tbl{width:100%;border-collapse:collapse;table-layout:fixed;font-size:13px;margin-bottom:10px;border:1px solid var(--bord)}
.pb-tbl .c-coche{width:30px}.pb-tbl .c-id{width:72px}.pb-tbl .c-profil{width:118px}.pb-tbl .c-moscow{width:98px}.pb-tbl .c-pts{width:44px}.pb-tbl .c-dor{width:48px}.pb-tbl .c-statut{width:108px}.pb-tbl .c-exec{width:176px}.pb-tbl .c-act{width:60px}
.pb-tbl th{text-align:left;padding:8px;font-size:12px;font-weight:700;color:#12355b;background:#e8f0f9;border-bottom:2px solid #bfd3ea;border-right:1px solid #d3e0ef;white-space:nowrap}
.pb-tbl th:last-child{border-right:0}
.pb-tbl td{padding:8px;border-bottom:1px solid #e2e8f0;border-right:1px solid #eef2f7;vertical-align:top;overflow-wrap:anywhere}
.pb-tbl td:last-child{border-right:0}
.pb-tbl tbody tr:hover td{background:#f8fafc}
.us-num{font-weight:700;color:var(--accent);white-space:nowrap}
.td-profil{color:#334155}.td-pts{font-weight:700;color:#1f4e79}
.us-txt{display:block;color:#1e293b;line-height:1.4}
.us-meta{display:flex;gap:2px 10px;flex-wrap:wrap;margin-top:3px;font-size:11px;color:var(--gris)}
.lien-det{background:none;border:0;padding:0;font:inherit;color:var(--accent);font-weight:600;cursor:pointer}
.us-detail{display:none;margin-top:6px;padding-top:6px;border-top:1px dashed var(--bord);font-size:12px;color:#334155}
.us.ouvert .us-detail{display:block}
.ms{display:inline-block;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}.ms-must{background:#fee2e2;color:#991b1b}.ms-should{background:#fef3c7;color:#92400e}.ms-could{background:#f1f5f9;color:#475569}.ms-wont{background:#f8fafc;color:#94a3b8}
.dor{font-size:11.5px;font-weight:600;color:#94a3b8}.dor.oui{color:#166534}
.td-exec .niv .org{display:none}.td-exec .niv .nb{width:46px}
.td-act{white-space:nowrap}.td-act form{display:inline}
.ico{background:none;border:0;padding:3px;color:#64748b;cursor:pointer;border-radius:5px;vertical-align:middle}.ico:hover{background:#eef4fb;color:var(--accent)}.ico.ko:hover{background:#fef2f2;color:var(--ko)}
.td-coche{text-align:center}
@media(max-width:900px){
.pb-tbl,.pb-tbl tbody{display:block}.pb-tbl thead,.pb-tbl colgroup{display:none}
.pb-tbl tr.us{display:flex;flex-wrap:wrap;gap:6px 14px;padding:10px 0;border-bottom:1px solid var(--bord)}
.pb-tbl td{display:block;padding:0;border:0}.pb-tbl td.td-story{flex:0 0 100%;order:-1}.pb-tbl td.td-coche{order:-3}.pb-tbl td.us-num{order:-2}
.pb-tbl td[data-lib]::before{content:attr(data-lib);display:block;font-size:10px;font-weight:700;color:#94a3b8}
.td-profil{flex:0 0 auto}
}
.filtre{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center}
.filtre select,.filtre input{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.sp-cartes{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
.sp-carte{display:block;background:#fff;border:1px solid var(--bord);border-radius:12px;padding:14px;text-decoration:none;color:inherit}
.sp-carte:hover{border-color:var(--accent);box-shadow:0 2px 8px rgba(46,116,181,.12)}
.sp-lien{display:block;text-decoration:none;color:inherit}
.sp-ceremonies{display:flex;gap:4px;flex-wrap:wrap;margin-top:10px;padding-top:9px;border-top:1px dashed var(--bord)}
.sp-ceremonies a{font-size:11.5px;font-weight:600;color:var(--accent);text-decoration:none;padding:3px 8px;border-radius:6px;background:#eff6ff}
.sp-ceremonies a:hover{background:#dbeafe}
.sp-ceremonies a.xl{margin-left:auto;background:#dcfce7;color:#166534}
.barre{height:7px;background:#e2e8f0;border-radius:6px;overflow:hidden;margin:8px 0 4px}.barre>div{height:100%;background:var(--accent)}
dialog{border:none;border-radius:14px;padding:22px;width:560px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:88vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:11.5px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:700;text-transform:uppercase}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
dialog textarea{resize:vertical}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.form-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.req{color:var(--ko)}
.aide{font-size:11px;color:var(--gris);margin-top:2px}
@media(max-width:700px){.form-row,.form-row3{grid-template-columns:1fr}.tbl{display:block;overflow-x:auto}}
</style>
@endpush

@section('contenu')
@php
    $actifs = $membres->where('actif', true);
    $ongletInitial = in_array(request('onglet'), ['equipe', 'backlog', 'sprints'], true) ? request('onglet') : 'backlog';
@endphp

<div class="ep-tete">
    <div>
        <h2>{{ $projet->libelle }} <span style="font-size:13px;color:var(--gris);font-weight:600">{{ $projet->code }}</span></h2>
        <div class="crumb"><a href="{{ route('sprints.index') }}" style="color:var(--accent);text-decoration:none">Projets / Sprints</a> &rsaquo; Espace projet</div>
        <div class="meta">
            Responsable : <strong>{{ $projet->responsable?->nomComplet() ?? '—' }}</strong>
            @if($projet->client) · Client : {{ $projet->client }} @endif
            @if($projet->date_debut) · Du {{ $projet->date_debut->format('d/m/Y') }}@if($projet->date_cible) au {{ $projet->date_cible->format('d/m/Y') }}@endif @endif
        </div>
    </div>
</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<div class="ep-tabs" role="tablist">
    <button class="ep-tab" data-onglet="backlog" onclick="ongletEp('backlog')">Product Backlog <span class="nb">{{ $stories->count() }}</span></button>
    <button class="ep-tab" data-onglet="sprints" onclick="ongletEp('sprints')">Sprints <span class="nb">{{ $projet->sprints->count() }}</span></button>
    <button class="ep-tab" data-onglet="equipe" onclick="ongletEp('equipe')">Équipe <span class="nb">{{ $actifs->count() }}</span></button>
</div>

{{-- ═══════════ PRODUCT BACKLOG ═══════════ --}}
<div class="ep-panel" id="ep-backlog">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div style="font-size:12.5px;color:var(--gris)">User stories du produit, regroupées par module.</div>
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            @if($peutAlimenter)
            <button class="btn sec" onclick="document.getElementById('dlg-import').showModal()">Importer (.xlsx)</button>
            <button class="btn" onclick="ouvrirStory()">+ User story</button>
            @endif
            @if($stories->isNotEmpty())<a class="btn sec" href="{{ route('projets.backlog.export', $projet) }}" title="Exporter le Product Backlog (.xlsx)">Exporter</a>@endif
        </div>
    </div>
    @if(session('remarques_import'))
    <div class="alerte" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e"><strong>Remarques d'import :</strong><ul style="margin:4px 0 0 18px">@foreach(session('remarques_import') as $rq)<li>{{ $rq }}</li>@endforeach</ul></div>
    @endif
    @if($stories->isEmpty())
    <div class="carte" style="text-align:center;color:var(--gris);padding:30px">Le backlog est vide. Ajoutez la première user story du projet.</div>
    @else
    @include('projets._tableau_backlog', ['bilan' => $bilanProjet, 'tableau' => $tableau])
    <div class="filtre">
        <select id="fb-module" onchange="filtrerBacklog()"><option value="">Tous les modules</option>@foreach($modules as $m)<option>{{ $m }}</option>@endforeach</select>
        <select id="fb-statut" onchange="filtrerBacklog()"><option value="">Tous les statuts</option>@foreach(\App\Models\UserStory::STATUTS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <select id="fb-moscow" onchange="filtrerBacklog()"><option value="">Toutes priorités</option>@foreach(\App\Models\UserStory::MOSCOW_BACKLOG as $k)<option value="{{ $k }}">{{ \App\Models\UserStory::MOSCOW[$k] }}</option>@endforeach</select>
        <select id="fb-dor" onchange="filtrerBacklog()"><option value="">DoR : tous</option><option value="1">DoR : Oui</option><option value="0">DoR : Non</option></select>
        <input type="search" id="fb-q" placeholder="Rechercher…" oninput="filtrerBacklog()">
        <span id="fb-nb" style="margin-left:auto;font-size:12px;color:var(--gris)"></span>
    </div>
    @if($sprintsCibles->isNotEmpty())
    <form method="POST" id="f-puiser" class="puiser" action="{{ route('sprints.user-stories.attacher', $sprintsCibles->first()) }}">@csrf
        <span>Cochez les user stories prêtes (DoR = Oui) du prochain sprint, puis ajoutez-les à</span>
        <select aria-label="Sprint cible" onchange="this.form.action = this.selectedOptions[0].dataset.url">
            @foreach($sprintsCibles as $sp)<option data-url="{{ route('sprints.user-stories.attacher', $sp) }}">Sprint {{ $sp->numero }}{{ $sp->statut === 'en_cours' ? ' (en cours)' : '' }}</option>@endforeach
        </select>
        <button class="btn sm" id="puiser-btn" disabled>Ajouter <span id="puiser-nb"></span></button>
    </form>
    @elseif($canEdit)
    <div class="puiser">Aucun sprint à préparer : créez le prochain sprint depuis « Projets / Sprints » pour y puiser des user stories.</div>
    @endif
    @foreach($arbre as $noeud)
    @php $module = $noeud['module']; $bm = $noeud['bilan']; @endphp
    <div class="module-bloc" data-module="{{ $module === 'Sans module' ? '' : $module }}">
        <div class="module-titre">
            <span class="nom">{{ $module }}</span>
            <span class="stats">{{ $bm['stories_terminees'] }}/{{ $bm['stories'] }} stories @if($bm['points']) · {{ $bm['points_faits'] }}/{{ $bm['points'] }} pts @endif</span>
            <span style="display:flex;align-items:center"><span class="prog {{ $bm['pct'] >= 100 ? 'fini' : '' }}" style="flex:1"><i style="width:{{ $bm['pct'] }}%"></i></span><span class="pct">{{ $bm['pct'] }} %</span></span>
        </div>
        <div class="module-corps">
        @foreach($noeud['epics'] as $ep)
        @if($ep['epic'])
        @php $be = $ep['bilan']; @endphp
        <div class="epic-titre" data-epic>
            <span class="nom"><small>Epic</small>{{ $ep['epic'] }}</span>
            <span class="stats">{{ $be['stories_terminees'] }}/{{ $be['stories'] }} stories @if($be['points']) · {{ $be['points_faits'] }}/{{ $be['points'] }} pts @endif</span>
            <span style="display:flex;align-items:center"><span class="prog {{ $be['pct'] >= 100 ? 'fini' : '' }}" style="flex:1"><i style="width:{{ $be['pct'] }}%"></i></span><span class="pct">{{ $be['pct'] }} %</span></span>
        </div>
        @endif
        <table class="pb-tbl">
            <colgroup><col class="c-coche"><col class="c-id"><col class="c-profil"><col><col class="c-moscow"><col class="c-pts"><col class="c-dor"><col class="c-statut"><col class="c-exec"><col class="c-act"></colgroup>
            <thead><tr><th></th><th>ID</th><th>Profil / Acteur</th><th>User Story</th><th>MoSCoW</th><th title="Story points">Pts</th><th title="Definition of Ready">DoR</th><th>Statut</th><th>Exécution</th><th></th></tr></thead>
            <tbody>
        @foreach($ep['stories'] as $us)
            <tr class="us" data-us data-statut="{{ $us->statut }}" data-moscow="{{ $us->priorite_moscow }}" data-dor="{{ $us->dor ? '1' : '0' }}" data-texte="{{ mb_strtolower($us->numero . ' ' . $us->module . ' ' . $us->epic . ' ' . $us->profil . ' ' . $us->intitule . ' ' . \App\Services\TexteRiche::texte($us->exigences)) }}">
                <td class="td-coche">@if($sprintsCibles->isNotEmpty() && !$us->estFinie() && !$us->sprints->contains(fn($s) => $s->statut !== 'termine'))
                    @if($us->dor)<input type="checkbox" class="us-coche" form="f-puiser" name="user_stories[]" value="{{ $us->id }}" aria-label="Sélectionner {{ $us->numero }}">
                    @else<input type="checkbox" class="us-coche" disabled title="DoR = Non : cette user story ne peut pas entrer dans un sprint" aria-label="{{ $us->numero }} : DoR = Non, sélection impossible">@endif
                @endif</td>
                <td class="us-num">{{ $us->numero }}</td>
                <td class="td-profil">{{ $us->profil ?: '-' }}</td>
                <td class="td-story">
                    <span class="us-txt">{{ $us->intitule }}</span>
                    <span class="us-meta">
                        @if(($us->origine ?? 'manuel') === 'review')<span>Issue d'une Review</span>@endif
                        @if($us->sprints->isNotEmpty())<span>Sprint(s) {{ $us->sprints->pluck('numero')->sort()->join(', ') }}</span>@endif
                        @if($us->taches_count)<span>{{ $us->taches_terminees_count }}/{{ $us->taches_count }} tâches</span>@endif
                        @if($us->exigences)<button type="button" class="lien-det" onclick="this.closest('.us').classList.toggle('ouvert')">Exigences</button>@endif
                    </span>
                    @if($us->exigences)<div class="us-detail"><x-redaction lecture :value="$us->exigences" /></div>@endif
                </td>
                <td data-lib="MoSCoW">@if($us->priorite_moscow)<span class="ms ms-{{ $us->priorite_moscow }}">{{ \App\Models\UserStory::MOSCOW[$us->priorite_moscow] ?? $us->priorite_moscow }}</span>@else - @endif</td>
                <td data-lib="Points" class="td-pts" title="{{ $us->complexiteStory()['libelle'] ?? 'Non estimée' }}">{{ $us->effort_estime ?: '-' }}</td>
                <td data-lib="DoR"><span class="dor {{ $us->dor ? 'oui' : '' }}">{{ $us->dor ? 'Oui' : 'Non' }}</span></td>
                <td data-lib="Statut">@include('scrum._statut_us', ['us' => $us, 'peutAlimenter' => $peutAlimenter])</td>
                <td data-lib="Exécution" class="td-exec">@include('scrum._niveau_us', ['us' => $us])</td>
                <td class="td-act">
                    @if($peutAlimenter)
                    @php $usJs = $us->only(['id', 'numero', 'module', 'epic', 'profil', 'intitule', 'priorite_moscow', 'effort_estime', 'dor']) + ['exigences' => \App\Services\TexteRiche::html($us->exigences)]; @endphp
                    <button type="button" class="ico" onclick='ouvrirStory(@json($usJs))' title="Modifier {{ $us->numero }}" aria-label="Modifier {{ $us->numero }}"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></button>
                    <form method="POST" action="{{ route('user-stories.destroy', $us) }}" style="margin:0" onsubmit="return demanderMotifSuppression(this,'cette user story')">@csrf @method('DELETE')
                        <input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="ico ko" title="Supprimer {{ $us->numero }}" aria-label="Supprimer {{ $us->numero }}"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>
                    </form>
                    @endif
                </td>
            </tr>
        @endforeach
            </tbody>
        </table>
        @endforeach
        </div>
    </div>
    @endforeach
    @endif
</div>

{{-- ═══════════ SPRINTS ═══════════ --}}
<div class="ep-panel" id="ep-sprints">
    @if($projet->sprints->isEmpty())
    <div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun sprint pour ce projet. Créez-le depuis « Projets / Sprints ».</div>
    @else
    <div class="sp-cartes">
        @foreach($projet->sprints as $sp)
        @php $pct = $sp->taches_count ? round($sp->taches_terminees_count / $sp->taches_count * 100) : 0; @endphp
        <div class="sp-carte">
            <a href="{{ route('sprints.show', $sp) }}" class="sp-lien">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <strong style="color:var(--bleu)">Sprint {{ $sp->numero }}</strong>
                <span class="pill {{ $sp->statut === 'termine' ? 'p-ok' : ($sp->statut === 'en_cours' ? 'p-cours' : 'p-attente') }}">{{ ['planifie' => 'Planifié', 'en_cours' => 'En cours', 'termine' => 'Terminé'][$sp->statut] ?? $sp->statut }}</span>
            </div>
            <div style="font-size:12.5px;color:#334155;margin-top:6px">{{ \Illuminate\Support\Str::limit($sp->objectif, 110) }}</div>
            <div class="barre"><div style="width:{{ $pct }}%"></div></div>
            <div style="font-size:11.5px;color:var(--gris)">{{ $sp->date_debut->format('d/m') }} → {{ $sp->date_fin->format('d/m/Y') }} · {{ $sp->dureeJours() }} j ouvrés · {{ $sp->taches_terminees_count }}/{{ $sp->taches_count }} tâches</div>
            </a>
            <div class="sp-ceremonies">
                <a href="{{ route('sprints.show', $sp) }}">Backlog</a>
                <a href="{{ route('sprints.planning', $sp) }}">Planning</a>
                <a href="{{ route('scrum.dailies', $sp) }}">Daily ({{ $sp->daily_scrums_count }})</a>
                <a href="{{ route('scrum.review', $sp) }}">Review</a>
                <a href="{{ route('scrum.retro', $sp) }}">Rétro</a>
                <a href="{{ route('scrum.archive', $sp) }}" class="xl" title="Exporter archive Scrum (.xlsx)">Excel ⤓</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ═══════════ ÉQUIPE ═══════════ --}}
<div class="ep-panel" id="ep-equipe">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div style="font-size:12.5px;color:var(--gris)">Membres du projet. Un membre retiré garde son historique.</div>
        @if($canEdit)<button class="btn" onclick="document.getElementById('dlg-membre').showModal()" @disabled($disponibles->isEmpty())>+ Ajouter un collaborateur</button>@endif
    </div>
    <div class="carte" style="overflow-x:auto">
    <h3 style="font-size:14px;color:var(--bleu);margin:0 0 10px;display:flex;align-items:center;gap:6px"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg> &Eacute;quipe du projet</h3>
    <table class="tbl">
        <thead><tr><th>Collaborateur</th><th>Fonction</th><th>Rôle dans le projet</th><th>Membre depuis</th><th>État</th>@if($canEdit)<th></th>@endif</tr></thead>
        <tbody>
        @forelse($membres as $m)
        <tr class="{{ $m->actif ? '' : 'inactif' }}">
            <td><strong>{{ $m->user?->nomComplet() }}</strong></td>
            <td style="font-size:12px;color:var(--gris)">{{ $m->user?->roleLibelle() }}</td>
            <td>
                @if($canEdit && $m->actif)
                <form method="POST" action="{{ route('projets.collaborateurs.update', [$projet, $m]) }}" style="margin:0">@csrf @method('PUT')
                    <select name="role_projet" onchange="this.form.submit()" style="padding:4px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12.5px">
                        @foreach(\App\Models\ProjetCollaborateur::ROLES as $k => $l)<option value="{{ $k }}" @selected($m->role_projet === $k)>{{ $l }}</option>@endforeach
                    </select>
                </form>
                @else {{ $m->roleLibelle() }} @endif
            </td>
            <td style="font-size:12.5px">{{ $m->date_ajout?->format('d/m/Y') }}</td>
            <td><span class="pill {{ $m->actif ? 'p-ok' : 'p-attente' }}">{{ $m->actif ? 'Actif' : 'Retiré' . ($m->date_retrait ? ' le ' . $m->date_retrait->format('d/m/Y') : '') }}</span></td>
            @if($canEdit)
            <td style="text-align:right">
                @if($m->actif)
                <form method="POST" action="{{ route('projets.collaborateurs.retirer', [$projet, $m]) }}" style="margin:0" onsubmit="return confirm('Retirer {{ addslashes($m->user?->nomComplet()) }} de l\'équipe ?')">@csrf @method('PATCH')
                    <button class="btn sm ko">Retirer</button>
                </form>
                @else
                <form method="POST" action="{{ route('projets.collaborateurs.store', $projet) }}" style="margin:0">@csrf
                    <input type="hidden" name="user_id" value="{{ $m->user_id }}"><input type="hidden" name="role_projet" value="{{ $m->role_projet }}">
                    <button class="btn sm sec">Réintégrer</button>
                </form>
                @endif
            </td>
            @endif
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;color:var(--gris);padding:20px">Aucun membre dans l'équipe.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($canEdit)
<dialog id="dlg-membre">
    <h3 style="color:var(--bleu);margin:0 0 4px">Ajouter un collaborateur au projet</h3>
    <form method="POST" action="{{ route('projets.collaborateurs.store', $projet) }}">@csrf
        <label>Collaborateur <span class="req">*</span></label>
        <select name="user_id" required>
            <option value="">— Choisir —</option>
            @foreach($disponibles as $u)<option value="{{ $u->id }}">{{ $u->nomComplet() }} ({{ $u->roleLibelle() }})</option>@endforeach
        </select>
        <label>Rôle dans le projet <span class="req">*</span></label>
        <select name="role_projet" required>
            @foreach(\App\Models\ProjetCollaborateur::ROLES as $k => $l)<option value="{{ $k }}" @selected($k === 'developpeur')>{{ $l }}</option>@endforeach
        </select>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Ajouter</button>
        </div>
    </form>
</dialog>
@endif

@if($peutAlimenter)
<dialog id="dlg-import">
    <h3 style="color:var(--bleu);margin-bottom:6px">Importer un Product Backlog (.xlsx)</h3>
    <p style="font-size:12.5px;color:#334155;margin-bottom:6px">Colonnes lues : <strong>N°, Epic, Module, Profil / Acteur, User Story, Priorité MoSCoW, Story points, DoR, Statut, Exigences</strong>.</p>
    <p style="font-size:12px;margin-bottom:4px"><a href="{{ route('projets.backlog.modele', $projet) }}" style="color:var(--accent);font-weight:600">⤓ Télécharger le modèle d'import</a></p>
    <form method="POST" action="{{ route('projets.backlog.import', $projet) }}" enctype="multipart/form-data">@csrf
        <label>Fichier Excel <span class="req">*</span></label>
        <input type="file" name="fichier" accept=".xlsx" required>
        <label>Si un numéro existe déjà dans le backlog</label>
        <select name="mode">
            <option value="maj">Mettre à jour la user story existante</option>
            <option value="ajout">Ignorer la ligne (ne créer que les nouvelles)</option>
        </select>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Importer</button>
        </div>
    </form>
</dialog>

<dialog id="dlg-story">
    <h3 id="st-titre" style="color:var(--bleu);margin:0 0 4px">Nouvelle user story</h3>
    <form method="POST" id="form-story" action="{{ route('projets.user-stories.store', $projet) }}">@csrf <span id="st-method"></span>
        <input type="hidden" name="story_id" id="st-id">
        <div class="form-row">
            <div><label>Numéro <span class="req">*</span></label><input name="numero" id="st-numero" required maxlength="20" placeholder="US 1.1"></div>
            <div><label>Module / fonctionnalité</label><input name="module" id="st-module" maxlength="150" list="st-modules" placeholder="Ex : Gestion des rôles et utilisateurs">
                <datalist id="st-modules">@foreach($modules as $m)<option value="{{ $m }}">@endforeach</datalist></div>
        </div>
        <div class="form-row">
            <div><label>Epic</label><input name="epic" id="st-epic" maxlength="150" list="st-epics" placeholder="Ex : Paiement en ligne">
                <datalist id="st-epics"></datalist></div>
            <div><label>Profil / Acteur</label><input name="profil" id="st-profil" maxlength="150" list="st-profils" placeholder="Ex : Gestionnaire">
                <datalist id="st-profils">@foreach($stories->pluck('profil')->filter()->unique() as $pf)<option value="{{ $pf }}">@endforeach</datalist></div>
        </div>
        <label>User story <span class="req">*</span></label>
        <textarea name="intitule" id="st-intitule" rows="3" required maxlength="2000" placeholder="En tant que …, je veux … afin de …"></textarea>
        <div class="form-row3">
            <div><label>Priorité MoSCoW</label><select name="priorite_moscow" id="st-moscow"><option value="">-</option>@foreach(\App\Models\UserStory::MOSCOW_BACKLOG as $k)<option value="{{ $k }}">{{ \App\Models\UserStory::MOSCOW[$k] }}</option>@endforeach</select></div>
            <div><label>Story points</label><select name="effort_estime" id="st-points"><option value="">-</option>@foreach(\App\Models\UserStory::POINTS as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach</select></div>
            <div><label title="Definition of Ready : la story est prête à entrer dans un sprint">DoR</label><select name="dor" id="st-dor"><option value="0">Non</option><option value="1">Oui</option></select></div>
        </div>
        <label>Exigences fonctionnelles</label>
        <x-redaction name="exigences" placeholder="Une exigence par puce" />
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
function ongletEp(nom){
    document.querySelectorAll('.ep-tab').forEach(b => b.classList.toggle('actif', b.dataset.onglet === nom));
    document.querySelectorAll('.ep-panel').forEach(p => p.classList.toggle('actif', p.id === 'ep-' + nom));
    const u = new URL(location.href); u.searchParams.set('onglet', nom); history.replaceState(null, '', u);
}
ongletEp(@json($ongletInitial));

function filtrerStatut(s){
    const f = document.getElementById('fb-statut');
    f.value = f.value === s ? '' : s;
    filtrerBacklog();
    f.scrollIntoView({block: 'nearest'});
}
// Sélection des user stories à ajouter au sprint
document.addEventListener('change', e => {
    if (!e.target.classList.contains('us-coche')) return;
    const n = document.querySelectorAll('.us-coche:checked').length;
    document.getElementById('puiser-btn').disabled = !n;
    document.getElementById('puiser-nb').textContent = n ? '(' + n + ')' : '';
});

function filtrerBacklog(){
    const mod = document.getElementById('fb-module').value, st = document.getElementById('fb-statut').value, ms = document.getElementById('fb-moscow').value, dr = document.getElementById('fb-dor').value, q = document.getElementById('fb-q').value.toLowerCase().trim();
    let n = 0, tot = 0;
    document.querySelectorAll('.module-bloc').forEach(bloc => {
        let vis = 0;
        bloc.querySelectorAll('[data-us]').forEach(us => {
            tot++;
            const ok = (!mod || bloc.dataset.module === mod) && (!st || us.dataset.statut === st) && (!ms || us.dataset.moscow === ms) && (!dr || us.dataset.dor === dr) && (!q || us.dataset.texte.includes(q));
            us.style.display = ok ? '' : 'none'; if (ok) { vis++; n++; }
        });
        bloc.style.display = vis ? '' : 'none';
        // Un tableau (et le titre de son epic) ne reste affiché que s'il garde une story
        bloc.querySelectorAll('.pb-tbl').forEach(tbl => {
            const v = [...tbl.querySelectorAll('[data-us]')].some(x => x.style.display !== 'none');
            tbl.style.display = v ? '' : 'none';
            const titre = tbl.previousElementSibling;
            if (titre && titre.hasAttribute('data-epic')) titre.style.display = v ? '' : 'none';
        });
    });
    const nb = document.getElementById('fb-nb'); if (nb) nb.textContent = n + ' / ' + tot + ' user stories';
}
if (document.getElementById('fb-q')) filtrerBacklog();

@if($peutAlimenter)
// Epics proposés : ceux du module saisi
const EPICS = @json($epics);
function suggererEpics(){
    const m = document.getElementById('st-module').value.trim();
    document.getElementById('st-epics').innerHTML = (EPICS[m] || []).map(e => '<option value="' + e.replace(/"/g, '&quot;') + '">').join('');
}
document.addEventListener('input', e => { if (e.target.id === 'st-module') suggererEpics(); });
// Le profil amorce la formulation de la user story
document.addEventListener('change', e => {
    if (e.target.id !== 'st-profil' || !e.target.value.trim()) return;
    const t = document.getElementById('st-intitule');
    if (!t.value.trim()) t.value = 'En tant que ' + e.target.value.trim() + ', je veux … afin de …';
});
function ouvrirStory(us){
    const f = document.getElementById('form-story');
    f.reset();
    document.getElementById('st-titre').textContent = us ? 'Modifier ' + us.numero : 'Nouvelle user story';
    f.action = us ? '{{ url('user-stories') }}/' + us.id : '{{ route('projets.user-stories.store', $projet) }}';
    document.getElementById('st-method').innerHTML = us ? '<input type="hidden" name="_method" value="PUT">' : '';
    const champs = {numero: 'st-numero', module: 'st-module', epic: 'st-epic', profil: 'st-profil', intitule: 'st-intitule', priorite_moscow: 'st-moscow', effort_estime: 'st-points'};
    Object.entries(champs).forEach(([k, id]) => { document.getElementById(id).value = us ? (us[k] ?? '') : ''; });
    document.getElementById('st-dor').value = us && (us.dor === true || us.dor === '1' || us.dor === 1) ? '1' : '0';
    Redaction.definir('exigences', us ? (us.exigences ?? '') : '');
    document.getElementById('st-id').value = us && us.id ? us.id : '';
    if (!us) document.getElementById('st-numero').value = @json($prochainNumero);
    suggererEpics();
    document.getElementById('dlg-story').showModal();
}
@if($errors->any() && old('intitule'))
{ const o = @json(old()); ouvrirStory(o.story_id ? Object.assign({id: o.story_id}, o) : null);
  ['numero','module','epic','profil','intitule','priorite_moscow','effort_estime','dor'].forEach(k => { const el = document.querySelector('#form-story [name="'+k+'"]'); if (el) el.value = o[k] ?? ''; });
  Redaction.definir('exigences', o.exigences ?? ''); }
@endif
@endif
</script>
@endpush
