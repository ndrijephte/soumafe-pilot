@extends('layouts.app')
@section('titre', 'Exports')
@push('styles')
<style>
.export-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}
@media(max-width:900px){.export-grid{grid-template-columns:1fr}}
.export-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:20px}
.export-card h3{font-size:15px;color:var(--bleu);margin-bottom:4px;display:flex;align-items:center;gap:8px}
.export-card p{font-size:12px;color:var(--gris);margin-bottom:14px;line-height:1.5}
.btn-export{display:inline-flex;align-items:center;gap:6px;background:var(--accent);color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn-export:hover{opacity:.9}
.filtres-export{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
.filtres-export select,.filtres-export input{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px;min-width:140px}
.filtres-export label{font-size:11px;color:var(--gris);font-weight:600;text-transform:uppercase;display:block;margin-bottom:3px}
.filtre-item{flex:1;min-width:130px}
.section-titre{font-size:16px;font-weight:700;color:var(--bleu);margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid var(--bord)}
.ic-export{flex:none}
</style>
@endpush
@section('contenu')
<h2>Exports</h2>
<div class="crumb">Exporter les donn&eacute;es au format Excel (.xlsx)</div>

{{-- Export rapides --}}
<div class="section-titre" style="margin-top:16px">Exports rapides</div>
<div class="export-grid" style="margin-bottom:28px">
    <div class="export-card">
        <h3><svg class="ic-export" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg> Collaborateurs</h3>
        <p>Liste compl&egrave;te : nom, email, r&ocirc;le, profil, p&ocirc;le, statut.</p>
        <a href="{{ route('export.collaborateurs') }}" class="btn-export">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            T&eacute;l&eacute;charger
        </a>
    </div>
    <div class="export-card">
        <h3><svg class="ic-export" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> &Eacute;v&eacute;nements</h3>
        <p>Faits marquants : interruptions, absences, cond. etc.</p>
        <form action="{{ route('export.evenements') }}" method="GET" style="margin-bottom:10px">
            <div class="filtres-export">
                <div class="filtre-item"><label>D&eacute;but</label><input type="date" name="date_debut"></div>
                <div class="filtre-item"><label>Fin</label><input type="date" name="date_fin"></div>
                <div class="filtre-item"><label>Type</label>
                    <select name="type"><option value="">Tous</option>
                        <option value="interruption_service">Interruption</option>
                        <option value="absence">Absence</option>
                        <option value="condoleance">Condol&eacute;ances</option>
                        <option value="reunion_exceptionnelle">R&eacute;union</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-export">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Exporter
            </button>
        </form>
    </div>
</div>

{{-- Export tâches avec filtres --}}
<div class="section-titre">Export t&acirc;ches (avec filtres)</div>
<div class="export-card" style="margin-bottom:28px">
    <form action="{{ route('export.taches') }}" method="GET">
        <div class="filtres-export">
            <div class="filtre-item"><label>Projet</label>
                <select name="projet_id" id="selProjet" onchange="filtrerSprints()">
                    <option value="">Tous</option>
                    @foreach($projets as $p)<option value="{{ $p->id }}">{{ $p->code }} — {{ $p->libelle }}</option>@endforeach
                </select>
            </div>
            <div class="filtre-item"><label>Sprint</label>
                <select name="sprint_id" id="selSprint">
                    <option value="">Tous</option>
                    @foreach($sprints as $s)<option value="{{ $s->id }}" data-projet="{{ $s->projet_id }}">S{{ $s->numero }} — {{ $s->projet?->code }}</option>@endforeach
                </select>
            </div>
            <div class="filtre-item"><label>Collaborateur</label>
                <select name="user_id">
                    <option value="">Tous</option>
                    @foreach($collaborateurs as $c)<option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>@endforeach
                </select>
            </div>
            <div class="filtre-item"><label>Statut</label>
                <select name="statut">
                    <option value="">Tous</option>
                    <option value="a_faire">À faire</option>
                    <option value="en_cours">En cours</option>
                    <option value="termine">Terminé</option>
                    <option value="bloque">Bloqué</option>
                    <option value="annule">Annulé</option>
                </select>
            </div>
        </div>
        <div class="filtres-export">
            <div class="filtre-item"><label>Date d&eacute;but &ge;</label><input type="date" name="date_debut"></div>
            <div class="filtre-item"><label>Date fin &le;</label><input type="date" name="date_fin"></div>
        </div>
        <button type="submit" class="btn-export" style="margin-top:8px">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Exporter les t&acirc;ches
        </button>
    </form>
</div>

{{-- Export sprint individuel --}}
<div class="section-titre">Archivage sprint (export complet)</div>
<div class="export-card">
    <p>Exporter la fiche compl&egrave;te d'un sprint : m&eacute;ta-donn&eacute;es + liste des t&acirc;ches.</p>
    <form action="" id="formSprint" method="GET">
        <div class="filtres-export">
            <div class="filtre-item"><label>Projet</label>
                <select id="selProjet2" onchange="filtrerSprintArchive()">
                    <option value="">— Choisir —</option>
                    @foreach($projets as $p)<option value="{{ $p->id }}">{{ $p->code }} — {{ $p->libelle }}</option>@endforeach
                </select>
            </div>
            <div class="filtre-item"><label>Sprint</label>
                <select id="selSprint2" onchange="updateFormAction()">
                    <option value="">— Choisir —</option>
                    @foreach($sprints as $s)<option value="{{ $s->id }}" data-projet="{{ $s->projet_id }}">Sprint {{ $s->numero }} ({{ $s->statut }})</option>@endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn-export" id="btnArchive" disabled style="margin-top:8px;opacity:.5">
            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Archiver / exporter le sprint
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
function filtrerSprints(){
    const pid=document.getElementById('selProjet').value;
    const sel=document.getElementById('selSprint');
    Array.from(sel.options).forEach(o=>{
        if(!o.value){o.style.display='';return}
        o.style.display=(!pid||o.dataset.projet===pid)?'':'none';
    });
    sel.value='';
}
function filtrerSprintArchive(){
    const pid=document.getElementById('selProjet2').value;
    const sel=document.getElementById('selSprint2');
    Array.from(sel.options).forEach(o=>{
        if(!o.value){o.style.display='';return}
        o.style.display=(!pid||o.dataset.projet===pid)?'':'none';
    });
    sel.value='';updateFormAction();
}
function updateFormAction(){
    const sid=document.getElementById('selSprint2').value;
    const btn=document.getElementById('btnArchive');
    const f=document.getElementById('formSprint');
    if(sid){
        f.action='{{ url("exports/sprint") }}/'+sid;
        btn.disabled=false;btn.style.opacity='1';
    } else {
        btn.disabled=true;btn.style.opacity='.5';
    }
}
</script>
@endpush
