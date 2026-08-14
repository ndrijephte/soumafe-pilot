@extends('layouts.app')
@section('titre', 'Projets & Sprints')
@push('styles')
<style>
.projet-section{margin-bottom:28px}
.projet-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.projet-code{background:var(--accent);color:#fff;font-weight:700;font-size:12px;padding:4px 10px;border-radius:6px;letter-spacing:.5px}
.projet-nom{font-size:16px;font-weight:600;color:var(--bleu)}
.projet-resp{font-size:12px;color:var(--gris)}
.sprint-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px}
.sprint-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;position:relative;transition:box-shadow .2s}
.sprint-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
.sprint-num{font-size:18px;font-weight:700;color:var(--bleu)}
.sprint-obj{font-size:13px;color:#1e293b;margin:6px 0 10px;line-height:1.4}
.sprint-dates{font-size:11.5px;color:var(--gris);display:flex;align-items:center;gap:6px}
.sprint-bar{height:6px;background:#e2e8f0;border-radius:3px;margin:10px 0 8px;overflow:hidden}
.sprint-bar div{height:100%;border-radius:3px;background:var(--accent);transition:width .4s}
.sprint-stats{display:flex;gap:16px;font-size:12px;color:var(--gris)}
.sprint-stats strong{color:#1e293b}
.sprint-taches{margin-top:10px;border-top:1px solid var(--bord);padding-top:8px}
.sprint-tache-line{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:12px;border-bottom:1px solid #f1f5f9}
.sprint-tache-line:last-child{border:none}
.pill{padding:2px 9px;border-radius:10px;font-size:10.5px;font-weight:600}
.s-planifie{background:#e2e8f0;color:#475569}.s-en_cours{background:#dbeafe;color:#1d4ed8}.s-termine{background:#dcfce7;color:var(--ok)}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:460px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
dialog input,dialog select{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog input:focus,dialog select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.req{color:var(--ko)}
.filtre-bar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.filtre-bar input,.filtre-bar select{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.filtre-bar input{min-width:180px}
@media(max-width:900px){.sprint-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}.filtre-bar{flex-direction:column}}
</style>
@endpush
@section('contenu')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;flex-wrap:wrap;gap:8px">
    <h2>Projets & Sprints</h2>
    @if(auth()->user()->peutGererProjet())
    <div style="display:flex;gap:8px">
        <button class="btn" onclick="document.getElementById('dlg-sprint').showModal()">+ Nouveau sprint</button>
        <button class="btn sec" onclick="document.getElementById('dlg-import').showModal()">Importer (.xlsx)</button>
    </div>
    @endif
</div>
<div class="crumb">Planification des sprints par projet</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

{{-- Barre de filtres --}}
<div class="filtre-bar">
    <input type="text" id="filtreProjet" placeholder="Rechercher un projet..." oninput="filtrer()">
    <select id="filtreStatut" onchange="filtrer()">
        <option value="">Tous statuts sprint</option>
        <option value="planifie">Planifi&eacute;</option>
        <option value="en_cours">En cours</option>
        <option value="termine">Termin&eacute;</option>
    </select>
</div>

@foreach($projets as $p)
@if($p->sprints->count() > 0)
<div class="projet-section" data-projet>
    <div class="projet-header">
        <span class="projet-code">{{ $p->code }}</span>
        <div>
            <div class="projet-nom">{{ $p->libelle }}</div>
            @if($p->responsable)
            <div class="projet-resp" style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#dbeafe,#eff6ff);border:1px solid #93c5fd;color:#1d4ed8;padding:3px 10px;border-radius:6px;font-weight:600;margin-top:4px">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                {{ $p->responsable->nomComplet() }}
            </div>
            @endif
        </div>
    </div>
    <div class="sprint-grid">
        @foreach($p->sprints as $s)
        @php
            $totalTaches = $s->taches->count();
            $terminees = $s->taches->where('statut', 'termine')->count();
            $pct = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;
        @endphp
        <a href="{{ route('sprints.show', $s) }}" class="sprint-card" data-sprint data-statut="{{ $s->statut }}" style="text-decoration:none;color:inherit;display:block">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span class="sprint-num">Sprint {{ $s->numero }}</span>
                <span class="pill s-{{ $s->statut }}">{{ ucfirst(str_replace('_',' ',$s->statut)) }}</span>
            </div>
            <div class="sprint-obj">{{ $s->objectif }}</div>
            <div class="sprint-dates">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ $s->date_debut->format('d/m') }} &rarr; {{ $s->date_fin->format('d/m/Y') }}
            </div>
            <div class="sprint-bar"><div style="width:{{ $pct }}%"></div></div>
            <div class="sprint-stats">
                <div>T&acirc;ches : <strong>{{ $totalTaches }}</strong></div>
                <div>Termin&eacute;es : <strong>{{ $terminees }}</strong></div>
                <div>Points : <strong>{{ $s->pointsLivres() }}</strong>@if($s->engagement_points) / {{ $s->engagement_points }}@endif</div>
            </div>
            <div style="margin-top:8px;font-size:11px;color:var(--accent);font-weight:600">Voir le d&eacute;tail &rarr;</div>
        </a>
        @endforeach
    </div>
</div>
@endif
@endforeach

@if($projets->every(fn ($p) => $p->sprints->isEmpty()))
<div class="carte" style="text-align:center;padding:40px">
    <p style="color:var(--gris);font-size:14px;margin-bottom:12px">Aucun sprint cr&eacute;&eacute; pour le moment.</p>
    @if(auth()->user()->peutGererProjet())
    <button class="btn" onclick="document.getElementById('dlg-sprint').showModal()">Cr&eacute;er le premier sprint</button>
    @else
    <p style="color:var(--gris);font-size:13px">Le responsable projet ou l'administrateur peut cr&eacute;er des sprints.</p>
    @endif
</div>
@endif

{{-- Dialog création sprint --}}
@if(auth()->user()->peutGererProjet())
<dialog id="dlg-sprint">
    <h3 style="color:var(--bleu);margin-bottom:4px">Nouveau sprint</h3>
    <form method="POST" action="{{ route('sprints.store') }}">@csrf
        <label>Projet <span class="req">*</span></label>
        <select name="projet_id" required>
            <option value="">— Choisir —</option>
            @foreach(auth()->user()->projetsGerables() as $pg)<option value="{{ $pg->id }}">{{ $pg->code }} — {{ $pg->libelle }}</option>@endforeach
        </select>
        <div class="form-row">
            <div><label>N° du sprint <span class="req">*</span></label><input type="number" name="numero" min="1" required></div>
            <div><label>Points engag&eacute;s</label><input type="number" name="engagement_points" min="1"></div>
        </div>
        <label>Objectif <span class="req">*</span></label><input name="objectif" required placeholder="Ex : kanban + admin comptes">
        <div class="form-row">
            <div><label>D&eacute;but <span class="req">*</span></label><input type="date" name="date_debut" required></div>
            <div><label>Fin <span class="req">*</span></label><input type="date" name="date_fin" required></div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Cr&eacute;er le sprint</button>
        </div>
    </form>
</dialog>

{{-- Dialog import xlsx --}}
<dialog id="dlg-import">
    <h3 style="color:var(--bleu);margin-bottom:4px">Importer des t&acirc;ches (.xlsx)</h3>
    <div style="font-size:12px;color:var(--gris);margin-bottom:12px;line-height:1.6">
        <div style="background:var(--fond);border:1px solid var(--bord);border-radius:8px;padding:10px 12px;margin-bottom:8px">
            <div style="font-weight:700;color:#1e293b;margin-bottom:4px;font-size:12px">Format attendu (7 colonnes, ordre libre) :</div>
            <div style="font-size:11px"><strong style="color:var(--ko)">Intitul&eacute;</strong> (obligatoire) &bull; <strong style="color:var(--ko)">Assign&eacute; &agrave;</strong> (obligatoire) &bull; Priorit&eacute; &bull; <strong style="color:var(--ko)">Date d&eacute;but</strong> (obligatoire) &bull; <strong style="color:var(--ko)">&Eacute;ch&eacute;ance</strong> (obligatoire) &bull; Points &bull; Projet</div>
            <div style="margin-top:4px;font-size:10.5px;color:#64748b">En-t&ecirc;te en ligne 1. L'ordre des colonnes est libre — le syst&egrave;me reconna&icirc;t les noms.</div>
        </div>
    </div>
    <form method="POST" action="{{ route('sprints.importer') }}" enctype="multipart/form-data">@csrf
        <label>Sprint (optionnel)</label>
        <select name="sprint_id">
            <option value="">— Aucun (cr&eacute;ation libre) —</option>
            @foreach($projets as $p)
                @foreach($p->sprints as $s)
                <option value="{{ $s->id }}">{{ $p->code }} — Sprint {{ $s->numero }}</option>
                @endforeach
            @endforeach
        </select>
        <label>Fichier Excel (.xlsx) <span class="req">*</span></label>
        <input type="file" name="fichier" accept=".xlsx" required style="padding:8px;border:1px dashed var(--bord);border-radius:8px;background:var(--fond);margin-top:4px">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Importer</button>
        </div>
    </form>
</dialog>
@endif
@endsection

@push('scripts')
<script>
@if($errors->any())document.addEventListener('DOMContentLoaded',function(){var d=document.getElementById('dlg-sprint');if(d)d.showModal();});@endif
function filtrer(){
    const txt=document.getElementById('filtreProjet').value.toLowerCase();
    const statut=document.getElementById('filtreStatut').value;
    document.querySelectorAll('[data-projet]').forEach(section=>{
        const nom=section.querySelector('.projet-nom').textContent.toLowerCase();
        const code=section.querySelector('.projet-code').textContent.toLowerCase();
        const matchProjet=!txt||nom.includes(txt)||code.includes(txt);
        if(!matchProjet){section.style.display='none';return;}
        let anyVisible=false;
        section.querySelectorAll('[data-sprint]').forEach(card=>{
            const ok=!statut||card.dataset.statut===statut;
            card.style.display=ok?'':'none';
            if(ok) anyVisible=true;
        });
        section.style.display=anyVisible?'':'none';
    });
}
</script>
@endpush
