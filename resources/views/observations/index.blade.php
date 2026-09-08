@extends('layouts.app')
@section('sousTitre', 'Suivi Production')
@section('titre', 'Suivi Production')
@push('styles')
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:20px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px;transition:transform .15s}
.kpi:hover{transform:translateY(-2px)}
.kpi .lbl{font-size:11px;color:var(--gris);text-transform:uppercase;letter-spacing:.4px}
.kpi .val{font-size:26px;font-weight:700;color:var(--bleu);margin:4px 0}
.kpi .sub{font-size:11px;color:var(--gris)}
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:16px}
.panneau h3{font-size:14px;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.filtre-bar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.filtre-bar select,.filtre-bar input{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.btn.ok{background:var(--ok)}
.btn.ko{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:10px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.mini-table tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;display:inline-block}
.b-ok{background:#dcfce7;color:var(--ok)}
.b-warn{background:#fef3c7;color:#b45309}
.b-ko{background:#fee2e2;color:var(--ko)}
.b-info{background:#dbeafe;color:#1d4ed8}
.b-gris{background:#e2e8f0;color:#475569}
.b-violet{background:#ede9fe;color:#6d28d9}
dialog{border:none;border-radius:14px;padding:24px;width:520px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
dialog textarea{resize:vertical}
.req{color:var(--ko)}
.act-row{display:flex;gap:4px}
.act-btn{background:none;border:1px solid var(--bord);border-radius:6px;padding:4px 6px;cursor:pointer;display:flex;align-items:center}
.act-btn:hover{background:var(--fond)}
.phase-tag{display:inline-block;padding:1px 6px;border-radius:4px;font-size:9px;font-weight:700;margin-left:6px}
.ph1{background:#dbeafe;color:#1d4ed8}
.ph2{background:#fef3c7;color:#92400e}
.code-obs{font-family:'Courier New',monospace;font-size:11px;font-weight:700;color:var(--accent);background:#eff6ff;padding:2px 6px;border-radius:4px}
.perf-box{background:var(--fond);border-radius:8px;padding:10px 12px;margin:8px 0;display:grid;grid-template-columns:repeat(3,1fr);gap:8px;font-size:11.5px}
.perf-box .perf-item{text-align:center}
.perf-box .perf-val{font-size:18px;font-weight:700;color:var(--bleu)}
.perf-box .perf-lbl{font-size:10px;color:var(--gris);text-transform:uppercase}
@media(max-width:900px){.kpis{grid-template-columns:repeat(2,1fr)}.filtre-bar{flex-direction:column;align-items:stretch}.mini-table{display:block;overflow-x:auto}.perf-box{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.kpis{grid-template-columns:1fr}.kpi .val{font-size:20px}dialog{padding:16px;width:95vw}.perf-box{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
@php
    $ic = fn(string $d, int $s=16) => '<svg viewBox="0 0 24 24" width="'.$s.'" height="'.$s.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$d.'</svg>';
    $icCheck = $ic('<polyline points="20 6 9 17 4 12"/>');
    $icAlert = $ic('<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>');
    $icEdit = $ic('<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>');
    $icTrash = $ic('<polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>');
    $icPlus = $ic('<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>');
    $icBug = $ic('<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M9 9h6M9 13h6M9 17h4"/>');

    $u = auth()->user();
    $isAdmin = $u->estAdmin();
    $isPilote = $u->estPilote();

    $badgeCls = fn($s) => match($s) {
        'nouveau'  => 'b-info',
        'en_cours' => 'b-warn',
        'resolu'   => 'b-ok',
        'bloque'   => 'b-ko',
        'rejete'   => 'b-gris',
        default    => 'b-gris',
    };

    $prioCls = fn($p) => match($p) {
        'critique' => 'b-ko',
        'haute'    => 'b-warn',
        'moyenne'  => 'b-info',
        'basse'    => 'b-gris',
        default    => 'b-gris',
    };

    $types = ['Technique / bug', "Expression d'un nouveau besoin", 'Ergonomie / UX', 'Performance', 'Sécurité'];
    $priorites = ['critique', 'haute', 'moyenne', 'basse'];
    $origines = ['Côté utilisateur', 'Côté développement', 'Demande direction', 'Audit interne'];
    $modes = ['Intégrée à un Sprint', 'Hors Sprint (ponctuel)', 'Maintenance planifiée'];
    $statuts = ['nouveau', 'en_cours', 'resolu', 'bloque', 'rejete'];
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;margin-bottom:4px">
    <h2>Suivi Production</h2>
    @if($isPilote)
    <button class="btn" onclick="document.getElementById('dlg-declaration').showModal()">
        {!! $icPlus !!} Déclarer une observation
    </button>
    @endif
</div>
<div class="crumb">Suivi des observations sur les applications en production</div>

@if(session('succes'))<div class="alerte ok">{!! $icCheck !!} {{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{!! $icAlert !!} {{ session('erreur') }}</div>@endif

{{-- KPIs --}}
<div class="kpis">
    <div class="kpi"><div class="lbl">Total</div><div class="val">{{ $stats['total'] }}</div><div class="sub">observations</div></div>
    <div class="kpi"><div class="lbl">Nouveaux</div><div class="val" style="color:#1d4ed8">{{ $stats['nouveau'] }}</div><div class="sub">à traiter</div></div>
    <div class="kpi"><div class="lbl">En cours</div><div class="val" style="color:#b45309">{{ $stats['en_cours'] }}</div><div class="sub">en résolution</div></div>
    <div class="kpi"><div class="lbl">Résolus</div><div class="val" style="color:var(--ok)">{{ $stats['resolu'] }}</div><div class="sub">traités</div></div>
    <div class="kpi"><div class="lbl">Bloqués</div><div class="val" style="color:var(--ko)">{{ $stats['bloque'] }}</div><div class="sub">en attente</div></div>
</div>

{{-- Filtres --}}
<div class="filtre-bar">
    <form method="GET" action="{{ route('observations.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <select name="projet" onchange="this.form.submit()">
            <option value="">Tous projets</option>
            @foreach($projets as $p)
            <option value="{{ $p->id }}" {{ request('projet') == $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->libelle }}</option>
            @endforeach
        </select>
        <select name="statut" onchange="this.form.submit()">
            <option value="">Tous statuts</option>
            @foreach($statuts as $s)
            <option value="{{ $s }}" {{ request('statut') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
            @endforeach
        </select>
        <select name="priorite" onchange="this.form.submit()">
            <option value="">Toutes priorités</option>
            @foreach($priorites as $p)
            <option value="{{ $p }}" {{ request('priorite') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
        <select name="type" onchange="this.form.submit()">
            <option value="">Tous types</option>
            @foreach($types as $t)
            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
        @if(request()->hasAny(['statut','priorite','type','responsable','projet']))
        <a href="{{ route('observations.index') }}" class="btn sec sm">Réinitialiser</a>
        @endif
    </form>
    <div style="flex:1"></div>
    <input type="text" id="filtreObs" placeholder="Rechercher..." oninput="filtrerObs()" style="min-width:160px;padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px">
</div>

{{-- Liste --}}
<div class="panneau" style="overflow-x:auto">
    <h3>{!! $icBug !!} Observations ({{ $observations->count() }})</h3>
    @if($observations->isEmpty())
        <p style="color:var(--gris);font-size:13px;padding:20px 0;text-align:center">Aucune observation enregistrée.</p>
    @else
    <table class="mini-table" id="tableObs">
        <thead>
        <tr>
            <th>Code</th>
            <th>Projet</th>
            <th>Date</th>
            <th>Description</th>
            <th>Type</th>
            <th>Priorité</th>
            <th>Responsable</th>
            <th>Statut</th>
            @if($isPilote)<th style="width:90px">Actions</th>@endif
        </tr>
        </thead>
        <tbody>
        @foreach($observations as $obs)
        <tr data-search="{{ strtolower($obs->code.' '.$obs->description.' '.($obs->responsable?->nomComplet() ?? '').' '.($obs->projet?->libelle ?? '')) }}">
            <td><span class="code-obs">{{ $obs->code }}</span></td>
            <td style="font-size:11px">{{ $obs->projet?->code ?? '—' }}</td>
            <td style="white-space:nowrap;font-size:11.5px">{{ $obs->date_reception->format('d/m/Y') }}</td>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $obs->description }}">{{ $obs->description }}</td>
            <td style="font-size:11px">{{ $obs->type_difficulte }}</td>
            <td><span class="badge {{ $prioCls($obs->priorite) }}">{{ ucfirst($obs->priorite) }}</span></td>
            <td style="font-size:11.5px">{{ $obs->responsable?->nomComplet() ?? '—' }}</td>
            <td><span class="badge {{ $badgeCls($obs->statut) }}">{{ $obs->statutLibelle() }}</span></td>
            @if($isPilote)
            <td>
                <div class="act-row">
                    <button class="act-btn" title="Détail / Résoudre" onclick="ouvrirResolution({{ $obs->id }})">
                        {!! $icEdit !!}
                    </button>
                    @if($isAdmin)
                    <form method="POST" action="{{ route('observations.destroy', $obs) }}" onsubmit="return demanderMotifSuppression(this,'cette observation')">@csrf @method('DELETE')
                        <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                        <button class="act-btn" title="Supprimer" style="color:var(--ko)">{!! $icTrash !!}</button>
                    </form>
                    @endif
                </div>
            </td>
            @endif
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- ============================================ --}}
{{-- Dialog Phase 1 — Déclaration --}}
{{-- ============================================ --}}
@if($isPilote)
<dialog id="dlg-declaration">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
        <h3 style="color:var(--bleu);margin:0">Déclarer une observation <span class="phase-tag ph1">Phase 1</span></h3>
        <button type="button" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--gris)" onclick="this.closest('dialog').close()">&times;</button>
    </div>
    <p style="font-size:12px;color:var(--gris);margin-bottom:10px">Enregistrez une observation, un bug ou un besoin détecté sur une application en production.</p>
    <form method="POST" action="{{ route('observations.store') }}">@csrf
        <label>Projet / Application <span class="req">*</span></label>
        <select name="projet_id" required>
            <option value="">— Sélectionner le projet —</option>
            @foreach($projets as $p)
            <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->libelle }}</option>
            @endforeach
        </select>

        <label>Description <span class="req">*</span></label>
        <textarea name="description" required rows="3" placeholder="Décrivez l'observation ou le problème constaté..."></textarea>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px">
            <div>
                <label>Type de difficulté <span class="req">*</span></label>
                <select name="type_difficulte" required>
                    @foreach($types as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Priorité <span class="req">*</span></label>
                <select name="priorite" required>
                    @foreach($priorites as $p)
                    <option value="{{ $p }}" {{ $p === 'moyenne' ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Responsable technique</label>
                <select name="responsable_id">
                    <option value="">— Non assigné —</option>
                    @foreach($responsables as $resp)
                    <option value="{{ $resp->id }}">{{ $resp->nomComplet() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Origine <span class="req">*</span></label>
                <select name="origine" required>
                    @foreach($origines as $o)
                    <option value="{{ $o }}">{{ $o }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <label>Commentaires</label>
        <textarea name="commentaires" rows="2" placeholder="Informations complémentaires (optionnel)..."></textarea>

        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Déclarer</button>
        </div>
    </form>
</dialog>
@endif

{{-- ============================================ --}}
{{-- Dialog Phase 2 — Résolution --}}
{{-- ============================================ --}}
@if($isPilote)
<dialog id="dlg-resolution">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
        <h3 style="color:var(--bleu);margin:0">Traitement <span class="phase-tag ph2">Phase 2</span> <span id="res-code" class="code-obs" style="margin-left:6px"></span></h3>
        <button type="button" style="background:none;border:none;cursor:pointer;font-size:18px;color:var(--gris)" onclick="this.closest('dialog').close()">&times;</button>
    </div>

    {{-- Résumé phase 1 --}}
    <div id="res-resume" style="background:var(--fond);border-radius:8px;padding:10px 12px;margin-bottom:8px;font-size:12px;color:var(--gris)"></div>

    {{-- Performance collaborateur --}}
    <div id="res-perf" style="display:none">
        <div style="font-size:11px;color:var(--gris);margin-bottom:4px;font-weight:600">Performance du responsable désigné</div>
        <div class="perf-box">
            <div class="perf-item"><div class="perf-val" id="perf-taches">—</div><div class="perf-lbl">Tâches terminées</div></div>
            <div class="perf-item"><div class="perf-val" id="perf-obj">—</div><div class="perf-lbl">Score objectifs</div></div>
            <div class="perf-item"><div class="perf-val" id="perf-obs">—</div><div class="perf-lbl">Obs. résolues</div></div>
        </div>
    </div>

    <form method="POST" id="form-resolution">@csrf @method('PUT')
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 14px">
            <div>
                <label>Statut <span class="req">*</span></label>
                <select name="statut" id="res-statut" required>
                    @foreach($statuts as $s)
                    <option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Mode d'exécution</label>
                <select name="mode_execution" id="res-mode">
                    <option value="">— Non défini —</option>
                    @foreach($modes as $m)
                    <option value="{{ $m }}">{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Responsable technique</label>
                <select name="responsable_id" id="res-responsable" onchange="chargerPerf(this.value)">
                    <option value="">— Non assigné —</option>
                    @foreach($responsables as $resp)
                    <option value="{{ $resp->id }}">{{ $resp->nomComplet() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Date de livraison</label>
                <input type="date" name="date_livraison" id="res-date-livraison">
            </div>
        </div>

        <label>Commentaire de résolution</label>
        <textarea name="commentaire_resolution" id="res-commentaire" rows="3" placeholder="Décrivez la solution apportée ou le blocage..."></textarea>

        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Mettre à jour</button>
        </div>
    </form>
</dialog>
@endif

@endsection

@push('scripts')
<script>
function escHtml(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function filtrerObs(){
    const txt=document.getElementById('filtreObs').value.toLowerCase();
    document.querySelectorAll('#tableObs tbody tr').forEach(tr=>{
        tr.style.display=!txt||tr.dataset.search.includes(txt)?'':'none';
    });
}

@if($isPilote)
@php
$obsJson = $observations->map(fn($o) => [
    'id'              => $o->id,
    'code'            => $o->code,
    'projet'          => $o->projet ? $o->projet->code . ' — ' . $o->projet->libelle : null,
    'description'     => $o->description,
    'type_difficulte' => $o->type_difficulte,
    'priorite'        => ucfirst($o->priorite),
    'origine'         => $o->origine,
    'date_reception'  => $o->date_reception->format('d/m/Y'),
    'statut'          => $o->statut,
    'responsable_id'  => $o->responsable_id,
    'mode_execution'  => $o->mode_execution,
    'date_livraison'  => $o->date_livraison?->format('Y-m-d'),
    'commentaire_resolution' => $o->commentaire_resolution,
    'commentaires'    => $o->commentaires,
    'declarant'       => $o->declarant?->nomComplet() ?? '—',
])->values();
@endphp
const obsData = @json($obsJson);

function ouvrirResolution(id){
    const o = obsData.find(x => x.id === id);
    if(!o) return;
    document.getElementById('res-code').textContent = o.code;
    let resume = '<strong>'+escHtml(o.description)+'</strong><br>';
    if(o.projet) resume += '<span style="color:var(--accent)">'+escHtml(o.projet)+'</span><br>';
    resume += '<span>Type : '+escHtml(o.type_difficulte)+' · Priorité : '+escHtml(o.priorite)+' · Origine : '+escHtml(o.origine)+'</span><br>';
    resume += '<span>Déclaré le '+escHtml(o.date_reception)+' par '+escHtml(o.declarant)+'</span>';
    if(o.commentaires) resume += '<br><em>'+escHtml(o.commentaires)+'</em>';
    document.getElementById('res-resume').innerHTML = resume;
    document.getElementById('form-resolution').action = '{{ url("observations") }}/'+o.id;
    document.getElementById('res-statut').value = o.statut;
    document.getElementById('res-mode').value = o.mode_execution || '';
    document.getElementById('res-responsable').value = o.responsable_id || '';
    document.getElementById('res-date-livraison').value = o.date_livraison || '';
    document.getElementById('res-commentaire').value = o.commentaire_resolution || '';
    // Charger perf du responsable
    if(o.responsable_id) chargerPerf(o.responsable_id);
    else document.getElementById('res-perf').style.display='none';
    document.getElementById('dlg-resolution').showModal();
}

function chargerPerf(userId){
    const box = document.getElementById('res-perf');
    if(!userId){ box.style.display='none'; return; }
    fetch('{{ route("observations.stats-collaborateur") }}?user_id='+userId)
        .then(r=>r.json())
        .then(d=>{
            if(!d.nom){ box.style.display='none'; return; }
            document.getElementById('perf-taches').textContent = d.taches_terminees+'/'+d.taches_total;
            document.getElementById('perf-obj').textContent = d.obj_score+'%';
            document.getElementById('perf-obs').textContent = d.obs_resolus+'/'+d.obs_total;
            box.style.display='block';
        })
        .catch(()=>{ box.style.display='none'; });
}
@endif
</script>
@endpush
