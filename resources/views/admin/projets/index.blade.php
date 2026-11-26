@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Projets')
@section('contenu')
@include('admin._ui')
@php
    $statuts = ['actif' => ['Actif', 'ok', '#16a34a'], 'en_pause' => ['En pause', 'warn', '#d97706'], 'termine' => ['Terminé', 'mut', '#64748b'], 'annule' => ['Annulé', 'ko', '#dc2626']];
    $icEdit = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    $nb = $projets->countBy('statut');
@endphp
<h2>Administration</h2>
<div class="crumb">Administration &rsaquo; Organisation &rsaquo; Projets</div>
@include('admin._nav')

@if(session('succes'))<div class="ad-alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="ad-alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="ad-alerte ko">{{ $errors->first() }}</div>@endif

<div class="ad-barre">
    <div class="ad-recherche">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="search" id="pj-cherche" placeholder="Projet, client, responsable…" oninput="pjFiltrer()" data-sans-controle>
    </div>
    <div class="ad-filtres">
        <button type="button" data-sans-icone data-f="" class="actif" onclick="pjStatut(this)">Tous {{ $projets->count() }}</button>
        @foreach($statuts as $s => [$lib])
            @if($nb[$s] ?? 0)<button type="button" data-sans-icone data-f="{{ $s }}" onclick="pjStatut(this)">{{ $lib }} {{ $nb[$s] }}</button>@endif
        @endforeach
    </div>
    <span class="espace"></span>
    <button type="button" class="btn-p" data-sans-icone onclick="pjOuvrir()"><svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>Nouveau projet</button>
</div>

<div class="ad-panneau">
<table class="ad-table" id="pj-table">
    <thead><tr><th>Projet</th><th>Client</th><th>Responsable</th><th>Période</th><th>Délégations</th><th>Statut</th><th class="n">Tâches</th><th></th></tr></thead>
    <tbody>
    @foreach($projets as $p)
        @php
            $st = $statuts[$p->statut] ?? [ucfirst(str_replace('_', ' ', $p->statut)), 'mut', '#64748b'];
            $d = $p->date_debut ? \Carbon\Carbon::parse($p->date_debut) : null; $c = $p->date_cible ? \Carbon\Carbon::parse($p->date_cible) : null;
            $donnees = ['id' => $p->id, 'code' => $p->code, 'libelle' => $p->libelle, 'client' => $p->client ?? '', 'resp' => (string) ($p->responsable_projet_id ?? ''),
                'statut' => $p->statut, 'debut' => $d?->format('Y-m-d') ?? '', 'cible' => $c?->format('Y-m-d') ?? '', 'deleg' => (bool) $p->delegation_sprint, 'histo' => (bool) $p->delegation_historique];
        @endphp
        <tr data-statut="{{ $p->statut }}" data-texte="{{ mb_strtolower($p->code . ' ' . $p->libelle . ' ' . $p->client . ' ' . $p->responsable?->nomComplet()) }}">
            <td><b style="font-weight:600">{{ $p->libelle }}</b><span class="sec">{{ $p->code }}</span></td>
            <td>{{ $p->client ?: '—' }}</td>
            <td>{{ $p->responsable?->nomComplet() ?? '—' }}</td>
            <td style="white-space:nowrap">@if($d || $c){{ $d?->translatedFormat('d M Y') ?? '…' }} → {{ $c?->translatedFormat('d M Y') ?? '…' }}@if($c && $p->statut === 'actif' && $c->isPast())<span class="sec" style="color:var(--ko)">échéance dépassée</span>@endif @else<span class="sec">non planifié</span>@endif</td>
            <td>@if($p->delegation_sprint)<span class="ad-marque" title="Le responsable gère les sprints">Sprints</span>@endif @if($p->delegation_historique)<span class="ad-marque" title="Le responsable peut rectifier les sprints clôturés">Rétrospective</span>@endif @if(!$p->delegation_sprint && !$p->delegation_historique)<span class="sec">—</span>@endif</td>
            <td><span class="etat {{ $st[1] }}">{{ $st[0] }}</span></td>
            <td class="n">{{ $p->taches_count }}</td>
            <td class="act"><button type="button" class="ib" data-sans-icone title="Modifier" aria-label="Modifier" data-projet="{{ json_encode($donnees) }}" onclick="pjOuvrir(JSON.parse(this.dataset.projet))">{!! $icEdit !!}</button></td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="ad-vide" id="pj-vide" hidden>Aucun projet ne correspond.</div>
</div>

<dialog id="dlg-projet" class="fm-dlg">
    <div class="fm-tete">
        <span class="fm-ic"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></span>
        <div><h3 id="pj-titre">Nouveau projet</h3><p>Le responsable suit le projet ; les délégations lui ouvrent la gestion des sprints.</p></div>
    </div>
    <form method="POST" id="pj-form" action="{{ route('admin.projets.store') }}">
        @csrf
        <input type="hidden" name="_method" id="pj-methode" value="POST">
        <div class="fm-ligne2" style="grid-template-columns:1fr 170px">
            <div><label class="fm-lab" for="pj-lib">Intitulé du projet <span class="req">*</span></label>
                <input name="libelle" id="pj-lib" class="fm-champ" required maxlength="150" placeholder="Ex. : Gestion logistique (STLE)" oninput="pjCodeAuto()"></div>
            <div><label class="fm-lab" for="pj-code">Code <span class="req">*</span></label>
                <input name="code" id="pj-code" class="fm-champ" required maxlength="20" style="text-transform:uppercase" placeholder="STLE" oninput="this.dataset.manuel = this.value ? '1' : ''"></div>
        </div>
        <label class="fm-lab" for="pj-statut">Statut <span class="req">*</span></label>
        <select name="statut" id="pj-statut" required data-pastilles>
            @foreach($statuts as $s => [$lib, , $coul])<option value="{{ $s }}" data-couleur="{{ $coul }}">{{ $lib }}</option>@endforeach
        </select>
        <div class="fm-ligne2">
            <div><label class="fm-lab" for="pj-client">Client <span class="fac">facultatif</span></label>
                <input name="client" id="pj-client" class="fm-champ" maxlength="100" placeholder="Ex. : Ministère, entreprise…"></div>
            <div><label class="fm-lab" for="pj-resp">Responsable du projet</label>
                <select name="responsable_projet_id" id="pj-resp" class="fm-champ" onchange="pjDeleg()">
                    <option value="">— Aucun —</option>
                    @foreach($responsables as $c)<option value="{{ $c->id }}">{{ $c->nomComplet() }}</option>@endforeach
                </select></div>
        </div>
        <div class="fm-ligne2">
            <div><label class="fm-lab" for="pj-debut">Début</label><input type="date" name="date_debut" id="pj-debut" class="fm-champ" oninput="pjPeriode()"></div>
            <div><label class="fm-lab" for="pj-cible">Date cible</label><input type="date" name="date_cible" id="pj-cible" class="fm-champ" oninput="pjPeriode()"></div>
        </div>
        <p class="fm-resume" id="pj-resume"></p>

        <div class="fm-section"><h4>Délégations au responsable</h4>
            <input type="hidden" name="delegation_sprint" value="0"><input type="hidden" name="delegation_historique" value="0">
            <label class="fm-option" id="pj-opt-deleg"><span class="inter"><input type="checkbox" name="delegation_sprint" id="pj-deleg" value="1" onchange="pjDeleg()"><i></i></span>
                <span><b>Gestion des sprints</b><span>Le responsable crée, planifie et importe les sprints du projet.</span></span></label>
            <label class="fm-option" id="pj-opt-histo"><span class="inter"><input type="checkbox" name="delegation_historique" id="pj-histo" value="1" onchange="pjDeleg()"><i></i></span>
                <span><b>Actions rétrospectives</b><span>Rouvrir un sprint clôturé en rectification (backlog, planning, tâches, Daily, rapports). Chaque rectification est motivée et tracée.</span></span></label>
        </div>
        <div class="fm-pied">
            <button type="button" class="btn-s" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn-p" id="pj-valider">Créer le projet</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
var PJ_URL = @js(url('admin/projets'));
function pjOuvrir(p){
    var f = document.getElementById('pj-form'), d = document.getElementById('dlg-projet');
    f.reset(); document.getElementById('pj-code').dataset.manuel = p ? '1' : '';
    document.getElementById('pj-titre').textContent = p ? 'Modifier le projet' : 'Nouveau projet';
    document.getElementById('pj-valider').textContent = p ? 'Enregistrer' : 'Créer le projet';
    f.action = p ? PJ_URL + '/' + p.id : @js(route('admin.projets.store'));
    document.getElementById('pj-methode').value = p ? 'PUT' : 'POST';
    if (p) {
        f.libelle.value = p.libelle; f.code.value = p.code; f.client.value = p.client;
        document.getElementById('pj-statut').value = p.statut; document.getElementById('pj-resp').value = p.resp;
        f.date_debut.value = p.debut; f.date_cible.value = p.cible;
        document.getElementById('pj-deleg').checked = p.deleg; document.getElementById('pj-histo').checked = p.histo;
    } else { document.getElementById('pj-statut').value = 'actif'; }
    pjPeriode(); pjDeleg(); d.showModal(); f.libelle.focus();
}
/* Code proposé à partir de l'intitulé (sigle ou mot principal), tant qu'il n'a pas été saisi à la main */
function pjCodeAuto(){
    var c = document.getElementById('pj-code'); if (c.dataset.manuel) return;
    var lib = document.getElementById('pj-lib').value.normalize('NFD').replace(/[̀-ͯ]/g, '');
    var paren = lib.match(/\(([A-Za-z0-9]{2,12})\)/);
    var mots = lib.replace(/\(.*?\)/g, '').split(/[^A-Za-z0-9]+/).filter(function(m){ return m.length > 2 || /^[A-Z0-9]+$/.test(m); });
    c.value = (paren ? paren[1] : (mots.length > 1 ? mots.map(function(m){ return m[0]; }).join('') : (mots[0] || ''))).toUpperCase().slice(0, 20);
}
function pjPeriode(){
    var a = document.getElementById('pj-debut').value, b = document.getElementById('pj-cible').value, r = document.getElementById('pj-resume'), t = [];
    var f = function(x){ return new Date(x + 'T12:00').toLocaleDateString('fr-FR', {day: 'numeric', month: 'long', year: 'numeric'}); };
    if (a && b) {
        if (b < a) { r.innerHTML = '<span style="color:var(--ko)">La date cible précède le début.</span>'; return; }
        var j = Math.round((new Date(b) - new Date(a)) / 864e5), mois = Math.round(j / 30.4);
        t.push('<b>' + (j < 60 ? j + ' jours' : mois + ' mois') + '</b> de projet, du ' + f(a) + ' au ' + f(b));
    }
    if (b) { var reste = Math.round((new Date(b + 'T12:00') - new Date()) / 864e5); t.push(reste >= 0 ? 'échéance dans ' + reste + ' jour' + (reste > 1 ? 's' : '') : '<span style="color:var(--ko)">échéance dépassée de ' + (-reste) + ' jours</span>'); }
    r.innerHTML = t.join(' · ');
}
/* Les délégations supposent un responsable ; la rétrospective suppose la gestion des sprints */
function pjDeleg(){
    var resp = !!document.getElementById('pj-resp').value, dg = document.getElementById('pj-deleg'), hi = document.getElementById('pj-histo');
    if (!resp) dg.checked = false;
    if (!dg.checked) hi.checked = false;
    dg.disabled = !resp; hi.disabled = !dg.checked;
    [['pj-opt-deleg', dg], ['pj-opt-histo', hi]].forEach(function(x){ var o = document.getElementById(x[0]); o.classList.toggle('on', x[1].checked); o.classList.toggle('off', x[1].disabled); });
    document.querySelector('#pj-opt-deleg span span').textContent = resp ? 'Le responsable crée, planifie et importe les sprints du projet.' : 'Choisissez d\'abord un responsable du projet.';
}
var pjFiltreStatut = '';
function pjStatut(b){ pjFiltreStatut = b.dataset.f; document.querySelectorAll('.ad-filtres button').forEach(function(x){ x.classList.toggle('actif', x === b); }); pjFiltrer(); }
function pjFiltrer(){
    var q = document.getElementById('pj-cherche').value.trim().toLowerCase(), n = 0;
    document.querySelectorAll('#pj-table tbody tr').forEach(function(tr){
        var v = (!pjFiltreStatut || tr.dataset.statut === pjFiltreStatut) && (!q || tr.dataset.texte.indexOf(q) >= 0);
        tr.hidden = !v; if (v) n++;
    });
    document.getElementById('pj-vide').hidden = n > 0;
}
@if($errors->any() && old('libelle'))
/* Après une erreur, la fenêtre se rouvre avec la saisie */
pjOuvrir(); (function(f){ f.libelle.value = @js(old('libelle')); f.code.value = @js(old('code')); f.client.value = @js(old('client')); f.date_debut.value = @js(old('date_debut')); f.date_cible.value = @js(old('date_cible')); document.getElementById('pj-resp').value = @js((string) old('responsable_projet_id')); document.getElementById('pj-statut').value = @js(old('statut', 'actif')); pjPeriode(); pjDeleg(); })(document.getElementById('pj-form'));
@endif
</script>
@endpush
