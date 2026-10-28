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
.module-titre{font-size:12px;font-weight:700;color:var(--bleu);text-transform:uppercase;letter-spacing:.5px;margin:18px 0 8px;display:flex;align-items:center;gap:8px}
.module-titre:first-child{margin-top:4px}
.us{background:#fff;border:1px solid var(--bord);border-radius:10px;padding:12px 14px;margin-bottom:8px}
.us-tete{display:flex;gap:10px;align-items:flex-start;justify-content:space-between}
.us-num{font-weight:700;color:var(--accent);white-space:nowrap;font-size:12.5px}
.us-txt{font-size:13px;color:#1e293b;flex:1}
.us-meta{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:6px;font-size:11.5px;color:var(--gris)}
.us-detail{display:none;margin-top:8px;font-size:12.5px;color:#334155;border-top:1px dashed var(--bord);padding-top:8px}
.us-detail strong{display:block;color:var(--bleu);font-size:11.5px;text-transform:uppercase;margin-top:4px}
.us.ouvert .us-detail{display:block}
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
    $statutCls = fn($s) => match($s) { 'terminee' => 'p-ok', 'en_cours' => 'p-cours', 'planifiee' => 'p-plan', default => 'p-attente' };
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
            @if($canEdit)
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
    <div class="filtre">
        <select id="fb-module" onchange="filtrerBacklog()"><option value="">Tous les modules</option>@foreach($modules as $m)<option>{{ $m }}</option>@endforeach</select>
        <select id="fb-statut" onchange="filtrerBacklog()"><option value="">Tous les statuts</option>@foreach(\App\Models\UserStory::STATUTS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <input type="search" id="fb-q" placeholder="Rechercher…" oninput="filtrerBacklog()">
        <span id="fb-nb" style="margin-left:auto;font-size:12px;color:var(--gris)"></span>
    </div>
    @foreach($stories->groupBy(fn($s) => $s->module ?: 'Sans module') as $module => $groupe)
    <div class="module-bloc" data-module="{{ $module === 'Sans module' ? '' : $module }}">
        <div class="module-titre">{{ $module }} <span class="pill p-attente">{{ $groupe->count() }}</span></div>
        @foreach($groupe as $us)
        <div class="us" data-us data-statut="{{ $us->statut }}" data-texte="{{ mb_strtolower($us->numero . ' ' . $us->module . ' ' . $us->intitule . ' ' . \App\Services\TexteRiche::texte($us->exigences)) }}">
            <div class="us-tete">
                <span class="us-num">{{ $us->numero }}</span>
                <span class="us-txt">{{ $us->intitule }}</span>
                <span style="display:flex;gap:4px;flex:none">
                    @if($us->exigences)<button class="btn sm sec" onclick="this.closest('.us').classList.toggle('ouvert')">Détails</button>@endif
                    @if($canEdit)
                    @php $usJs = $us->only(['id', 'numero', 'module', 'intitule']) + ['exigences' => \App\Services\TexteRiche::html($us->exigences)]; @endphp
                    <button class="btn sm sec" onclick='ouvrirStory(@json($usJs))'>Modifier</button>
                    <form method="POST" action="{{ route('user-stories.destroy', $us) }}" style="margin:0" onsubmit="return demanderMotifSuppression(this,'cette user story')">@csrf @method('DELETE')
                        <input type="hidden" name="motif_suppression" class="motif-suppr-input"><button class="btn sm ko" title="Supprimer">✕</button>
                    </form>
                    @endif
                </span>
            </div>
            <div class="us-meta">
                <span class="pill {{ $statutCls($us->statut) }}">{{ $us->statutLibelle() }}</span>
                @if(($us->origine ?? 'manuel') !== 'manuel')<span class="pill {{ $us->origine === 'review' ? 'p-cours' : ($us->origine === 'retrospective' ? 'p-haute' : 'p-plan') }}" title="Origine">{{ \App\Models\UserStory::ORIGINES[$us->origine] ?? $us->origine }}</span>@endif
                @if($us->sprints->isNotEmpty())<span>· Sprint(s) {{ $us->sprints->pluck('numero')->sort()->join(', ') }}</span>@endif
                @if($us->taches_count)<span>· {{ $us->taches_terminees_count }}/{{ $us->taches_count }} tâche(s) terminée(s)</span>@endif
            </div>
            <div class="us-detail">
                @if($us->exigences)<strong>Exigences fonctionnelles</strong><x-redaction lecture :value="$us->exigences" />@endif
            </div>
        </div>
        @endforeach
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
                <a href="{{ route('sprints.show', $sp) }}">Planning</a>
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

<dialog id="dlg-import">
    <h3 style="color:var(--bleu);margin-bottom:6px">Importer un Product Backlog (.xlsx)</h3>
    <p style="font-size:12.5px;color:#334155;margin-bottom:6px">Colonnes lues : <strong>N°, Module, User Story, Exigences fonctionnelles</strong>.</p>
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
        <label>User story <span class="req">*</span></label>
        <textarea name="intitule" id="st-intitule" rows="2" required maxlength="2000" placeholder="En tant que …, je veux … afin de …"></textarea>
        <label>Exigences fonctionnelles</label>
        <x-redaction name="exigences" placeholder="Une exigence par puce" />
        <div class="aide" style="margin-top:8px">Les tâches techniques et les story points se renseignent au Sprint Planning ; le statut suit l'avancement dans les sprints.</div>
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

function filtrerBacklog(){
    const mod = document.getElementById('fb-module').value, st = document.getElementById('fb-statut').value, q = document.getElementById('fb-q').value.toLowerCase().trim();
    let n = 0, tot = 0;
    document.querySelectorAll('.module-bloc').forEach(bloc => {
        let vis = 0;
        bloc.querySelectorAll('[data-us]').forEach(us => {
            tot++;
            const ok = (!mod || bloc.dataset.module === mod) && (!st || us.dataset.statut === st) && (!q || us.dataset.texte.includes(q));
            us.style.display = ok ? '' : 'none'; if (ok) { vis++; n++; }
        });
        bloc.style.display = vis ? '' : 'none';
    });
    const nb = document.getElementById('fb-nb'); if (nb) nb.textContent = n + ' / ' + tot + ' user stories';
}
if (document.getElementById('fb-q')) filtrerBacklog();

@if($canEdit)
function ouvrirStory(us){
    const f = document.getElementById('form-story');
    f.reset();
    document.getElementById('st-titre').textContent = us ? 'Modifier ' + us.numero : 'Nouvelle user story';
    f.action = us ? '{{ url('user-stories') }}/' + us.id : '{{ route('projets.user-stories.store', $projet) }}';
    document.getElementById('st-method').innerHTML = us ? '<input type="hidden" name="_method" value="PUT">' : '';
    const champs = {numero: 'st-numero', module: 'st-module', intitule: 'st-intitule'};
    Object.entries(champs).forEach(([k, id]) => { document.getElementById(id).value = us ? (us[k] ?? '') : ''; });
    Redaction.definir('exigences', us ? (us.exigences ?? '') : '');
    document.getElementById('st-id').value = us && us.id ? us.id : '';
    if (!us) document.getElementById('st-numero').value = @json($prochainNumero);
    document.getElementById('dlg-story').showModal();
}
@if($errors->any() && old('intitule'))
{ const o = @json(old()); ouvrirStory(o.story_id ? Object.assign({id: o.story_id}, o) : null);
  ['numero','module','intitule'].forEach(k => { const el = document.querySelector('#form-story [name="'+k+'"]'); if (el) el.value = o[k] ?? ''; });
  Redaction.definir('exigences', o.exigences ?? ''); }
@endif
@endif
</script>
@endpush
