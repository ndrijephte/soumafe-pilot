@extends('layouts.app')
@section('sousTitre', 'Paramètres parc')
@section('titre', 'Paramètres parc automobile')
@push('styles')
<style>
.pp-tete{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
.pp-onglets{display:flex;gap:4px;box-shadow:inset 0 -1px 0 var(--bord);margin:4px 0 16px;overflow-x:auto;scrollbar-width:none}
.pp-onglets button{background:none;border:none;border-bottom:2px solid transparent;padding:9px 14px;font-size:13px;font-weight:600;color:var(--gris);cursor:pointer;white-space:nowrap}
.pp-onglets button:hover{color:var(--bleu)}
.pp-onglets button.actif{color:var(--accent);border-bottom-color:var(--accent)}
.pp-panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px 18px}
.pp-panneau h3{font-size:14px;color:var(--bleu);margin:0 0 4px}
.pp-aide{font-size:12.5px;color:var(--gris);margin:0 0 14px;line-height:1.5}
.pp-aide b{color:#334155;font-weight:600}
.pp-champ label{display:block;font-size:12px;color:var(--gris);font-weight:600;margin-bottom:4px}
.pp-champ select,.pp-champ input{padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;background:#fff;font-family:inherit}
.pp-champ select:focus,.pp-champ input:focus,.pp-table input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.pp-table{width:100%;border-collapse:collapse;font-size:13px}
.pp-table th{text-align:left;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--gris);padding:7px 8px;border-bottom:1px solid var(--bord)}
.pp-table td{padding:7px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.pp-table tr:last-child td{border-bottom:none}
.pp-table .n{text-align:right}
.pp-obj input{width:130px;text-align:right;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-variant-numeric:tabular-nums}
.pp-obj td:first-child{font-weight:600;color:#334155}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:6px 12px;font-size:12px}
.alerte{border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
/* Listes de choix : menu des listes à gauche, liste choisie à droite */
.pp-listes{display:grid;grid-template-columns:230px 1fr;gap:16px;align-items:start}
.pp-menu{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:6px}
.pp-menu button{display:flex;justify-content:space-between;align-items:center;width:100%;background:none;border:none;border-radius:8px;padding:9px 10px;font-size:13px;color:#334155;cursor:pointer;text-align:left}
.pp-menu button:hover{background:#f1f5f9}
.pp-menu button.actif{background:#e6f0fa;color:var(--accent);font-weight:600}
.pp-menu .c{font-size:11px;color:var(--gris);font-weight:400}
.pp-lib{width:100%;border:1px solid transparent;border-radius:6px;padding:5px 8px;font-size:13px;background:transparent;font-family:inherit;color:#1e293b}
.pp-lib:hover{border-color:var(--bord)}
.pp-lib:focus{background:#fff}
.pp-int{width:80px;border:1px solid transparent;border-radius:6px;padding:5px 8px;font-size:13px;text-align:right;background:transparent}
.pp-int:hover{border-color:var(--bord)}
tr.inactif .pp-lib,tr.inactif .pp-int{color:var(--gris);text-decoration:line-through}
.pp-code{font-size:11px;color:#94a3b8;font-family:Consolas,monospace}
.pp-usage{font-size:12px;color:var(--gris);white-space:nowrap}
.pp-ok{font-size:11px;color:var(--ok);margin-left:6px;opacity:0;transition:opacity .3s}
.pp-ok.vu{opacity:1}
.inter{position:relative;display:inline-block;width:34px;height:19px;vertical-align:middle;cursor:pointer}
.inter input{opacity:0;width:0;height:0}
.inter span{position:absolute;inset:0;background:#cbd5e1;border-radius:19px;transition:background .15s}
.inter span:before{content:"";position:absolute;width:15px;height:15px;left:2px;top:2px;background:#fff;border-radius:50%;transition:transform .15s}
.inter input:checked+span{background:var(--accent)}
.inter input:checked+span:before{transform:translateX(15px)}
.ib{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:none;background:none;cursor:pointer;color:var(--ko)}
.ib:hover{background:#fee2e2}
.ib:disabled{color:#cbd5e1;cursor:not-allowed;background:none}
.pp-ajout{display:flex;gap:8px;align-items:center;margin:4px 0 6px;padding:10px 12px;background:#f6f9fc;border-radius:10px;flex-wrap:wrap}
.pp-ajout input{padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
.pp-ajout input[name=libelle]{flex:1;min-width:200px}
.pp-ajout input[name=intervalle_jours]{width:150px}
@media(max-width:800px){.pp-listes{grid-template-columns:1fr}.pp-obj input{width:100%}}
</style>
@endpush
@section('contenu')
@php
    use App\Services\ExerciceParc;
    $cleCourante = ExerciceParc::cle();
    $reglesEx = ExerciceParc::regles();
    $choixMois = [];
    for ($i = -6; $i <= 12; $i++) { $c = ExerciceParc::decaler($cleCourante, $i); $choixMois[$c] = ExerciceParc::nom($c); }
    $defautMois = ExerciceParc::decaler($cleCourante, 1);

    // Objectifs de recette : grille catégorie × type de jour (clés objectif_{categorie}_{jour})
    $jours = ['ouvrable' => 'Jour ouvrable', 'dimanche' => 'Dimanche', 'ferie' => 'Jour férié'];
    $objectifs = []; $autres = collect();
    foreach ($parametres->where('groupe', '!=', 'exercice') as $p) {
        if (preg_match('/^objectif_([a-z]+)_(ouvrable|dimanche|ferie)$/', $p->cle, $m)) $objectifs[$m[1]][$m[2]] = $p;
        else $autres->push($p);
    }
    $categories = ['taxi' => 'Taxi', 'vtc' => 'VTC'];
    $onglet = in_array(request('tab'), ['exercice', 'objectifs', 'listes'], true) ? request('tab') : 'listes';
    $listeActive = array_key_exists(request('liste'), $listes) ? request('liste') : array_key_first($listes);
    $icSuppr = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
@endphp

<div class="pp-tete">
    <div>
        <h2>Administration</h2>
        <div class="crumb">Administration &rsaquo; Parc automobile</div>
    </div>
    <a href="{{ route('parc.index') }}" class="btn sec sm">Retour au parc</a>
</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<div class="pp-onglets" role="tablist">
    <button type="button" data-sans-icone data-onglet="listes" onclick="ppOnglet('listes')">Listes de choix</button>
    <button type="button" data-sans-icone data-onglet="objectifs" onclick="ppOnglet('objectifs')">Objectifs de recette</button>
    <button type="button" data-sans-icone data-onglet="exercice" onclick="ppOnglet('exercice')">Exercice mensuel</button>
</div>

{{-- ═══ LISTES DE CHOIX ═══ --}}
<div id="pp-listes" hidden>
    <p class="pp-aide">Valeurs proposées dans les formulaires du parc. Cliquez un libellé pour le corriger : l'enregistrement est immédiat.
        Une valeur déjà utilisée ne peut pas être supprimée, seulement désactivée (elle n'est alors plus proposée).
        Une valeur manquante peut aussi être ajoutée directement depuis un formulaire, avec « + Ajouter… » en bas de la liste.</p>
    <div class="pp-listes">
        <nav class="pp-menu">
            @foreach($listes as $cat => $l)
            <button type="button" data-sans-icone data-liste="{{ $cat }}" onclick="ppListe('{{ $cat }}')">{{ $l['titre'] }} <span class="c">{{ $l['types']->where('actif', true)->count() }}</span></button>
            @endforeach
        </nav>
        <div>
        @foreach($listes as $cat => $l)
        <div class="pp-panneau" id="liste-{{ $cat }}" hidden>
            <h3>{{ $l['titre'] }}</h3>
            <p class="pp-aide" style="margin-bottom:8px">{{ $l['aide'] }}</p>
            <form method="POST" action="{{ route('admin.parc-parametres.types.store') }}" class="pp-ajout haut">
                @csrf
                <input type="hidden" name="categorie" value="{{ $cat }}">
                <input name="libelle" required minlength="2" maxlength="80" placeholder="Nouveau libellé" aria-label="Nouveau libellé">
                @if($cat === 'maintenance')<input name="intervalle_jours" type="number" min="1" max="3650" placeholder="Intervalle (jours)" aria-label="Intervalle en jours">@endif
                <button class="btn sm">Ajouter</button>
            </form>
            <table class="pp-table">
                <thead><tr><th>Libellé</th>@if($cat === 'maintenance')<th class="n">Intervalle</th>@endif<th>Utilisation</th><th>Proposé</th><th></th></tr></thead>
                <tbody>
                @forelse($l['types'] as $t)
                    @php $n = $l['usages'][$t->code] ?? 0; @endphp
                    <tr class="{{ $t->actif ? '' : 'inactif' }}" data-id="{{ $t->id }}">
                        <td><div style="display:flex;align-items:center;gap:6px"><input class="pp-lib" value="{{ $t->libelle }}" data-ancien="{{ $t->libelle }}" maxlength="80" data-champ="libelle" data-sans-controle aria-label="Libellé" title="Code enregistré : {{ $t->code }}"><span class="pp-ok">enregistré</span></div></td>
                        @if($cat === 'maintenance')
                        <td class="n"><input class="pp-int" type="number" min="1" max="3650" value="{{ $t->intervalle_jours }}" data-ancien="{{ $t->intervalle_jours }}" data-champ="intervalle_jours" data-sans-controle placeholder="—" aria-label="Intervalle en jours"> <span class="pp-usage">j</span></td>
                        @endif
                        <td class="pp-usage">{{ $n ? $n . ' fois' : 'jamais' }}</td>
                        <td><label class="inter" title="{{ $t->actif ? 'Proposé dans les formulaires' : 'Non proposé' }}"><input type="checkbox" @checked($t->actif) onchange="ppEnregistrer(this.closest('tr'), {actif: this.checked ? 1 : 0}, this)"><span></span></label></td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('admin.parc-parametres.types.destroy', $t) }}" style="display:inline" onsubmit="return confirm('Supprimer « {{ addslashes($t->libelle) }} » de la liste ?')">
                                @csrf @method('DELETE')
                                <button class="ib" data-sans-icone @disabled($n) title="{{ $n ? 'Utilisé ' . $n . ' fois : désactivez-le plutôt' : 'Supprimer' }}" aria-label="Supprimer">{!! $icSuppr !!}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="pp-usage" style="padding:16px 8px">Liste vide : ajoutez une première valeur ci-dessous.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @endforeach
        </div>
    </div>
</div>

{{-- ═══ OBJECTIFS DE RECETTE ═══ --}}
<div class="pp-panneau" id="pp-objectifs" hidden>
    <h3>Objectifs de recette journalière</h3>
    <p class="pp-aide">Montant attendu d'un véhicule pour une journée, en FCFA. Il est appliqué automatiquement à la saisie d'une recette,
        selon la catégorie du véhicule et le type de jour. Un objectif à 0 signifie qu'aucune recette n'est attendue ce jour-là.</p>
    <form method="POST" action="{{ route('admin.parc-parametres.update') }}">
        @csrf
        <table class="pp-table pp-obj" style="max-width:640px">
            <thead><tr><th>Catégorie</th>@foreach($jours as $j => $lj)<th class="n">{{ $lj }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach($objectifs as $cat => $parJour)
            <tr>
                <td>{{ $categories[$cat] ?? ucfirst($cat) }}</td>
                @foreach($jours as $j => $lj)
                <td class="n">@isset($parJour[$j])<input type="number" name="params[{{ $parJour[$j]->id }}]" value="{{ $parJour[$j]->valeur }}" min="0" step="500" required aria-label="{{ ($categories[$cat] ?? $cat) . ', ' . $lj }}">@else<span class="pp-usage">—</span>@endisset</td>
                @endforeach
            </tr>
            @endforeach
            @foreach($autres as $p)
            <tr><td>{{ $p->libelle }}</td><td class="n" colspan="3" style="text-align:left"><input type="number" name="params[{{ $p->id }}]" value="{{ $p->valeur }}" min="0"></td></tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:14px"><button class="btn">Enregistrer les objectifs</button></div>
    </form>
</div>

{{-- ═══ EXERCICE MENSUEL ═══ --}}
<div class="pp-panneau" id="pp-exercice" hidden>
    <h3>Exercice mensuel</h3>
    <p class="pp-aide">Toutes les périodes du parc (indicateurs, fiches, filtres, reporting) suivent l'exercice mensuel.
        Exercice en cours : <b>{{ ExerciceParc::libelle($cleCourante) }}</b>.
        Un changement s'applique à partir de l'exercice choisi : les exercices antérieurs gardent leurs dates, et celui qui précède
        le changement est raccourci ou allongé pour qu'il n'y ait ni trou ni chevauchement.</p>
    <form method="POST" action="{{ route('admin.parc-parametres.exercice') }}" style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end"
          onsubmit="return document.getElementById('ex-mois').value >= '{{ $cleCourante }}' || confirm('Cet exercice est déjà commencé ou passé : les rapports à partir de cet exercice seront recalculés. Continuer ?')">
        @csrf
        <div class="pp-champ">
            <label for="ex-jour">Jour de début de l'exercice</label>
            <select name="jour_debut" id="ex-jour" onchange="apercuExercice()" style="min-width:220px">
                @for($j = 1; $j <= ExerciceParc::JOUR_MAX; $j++)
                <option value="{{ $j }}" @selected($j === ExerciceParc::jourDebut($defautMois))>{{ sprintf('%02d', $j) }} {{ $j === 1 ? '(mois calendaire)' : '→ ' . sprintf('%02d', $j - 1) . ' du mois suivant' }}</option>
                @endfor
            </select>
        </div>
        <div class="pp-champ">
            <label for="ex-mois">Applicable à partir de l'exercice</label>
            <select name="a_partir_de" id="ex-mois" onchange="apercuExercice()" style="min-width:220px">
                @foreach($choixMois as $c => $nom)
                <option value="{{ $c }}" @selected($c === $defautMois)>{{ $nom }}{{ $c === $cleCourante ? ' (en cours)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn">Appliquer</button>
        <div id="ex-apercu" style="font-size:12px;color:var(--gris);flex-basis:100%"></div>
    </form>
    <table class="pp-table" style="margin-top:16px;max-width:640px">
        <thead><tr><th>Règle en vigueur depuis l'exercice</th><th>Période type</th></tr></thead>
        <tbody>
        @foreach(array_reverse($reglesEx, true) as $depuis => $j)
        <tr>
            <td>{{ $depuis === '2000-01' ? 'Origine' : ExerciceParc::nom($depuis) }}@if($depuis > $cleCourante) <span style="color:var(--accent);font-weight:600">(à venir)</span>@endif</td>
            <td>{{ $j === 1 ? 'Mois calendaire (du 01 au dernier jour)' : 'Du ' . sprintf('%02d', $j) . ' au ' . sprintf('%02d', $j - 1) . ' du mois suivant' }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
function ppOnglet(nom){
    ['listes','objectifs','exercice'].forEach(function(t){ document.getElementById('pp-'+t).hidden = t !== nom; });
    document.querySelectorAll('.pp-onglets button').forEach(function(b){ b.classList.toggle('actif', b.dataset.onglet === nom); });
    var u = new URL(location.href); u.searchParams.set('tab', nom); history.replaceState(null, '', u);
}
function ppListe(cat){
    document.querySelectorAll('[id^="liste-"]').forEach(function(p){ p.hidden = p.id !== 'liste-'+cat; });
    document.querySelectorAll('.pp-menu button').forEach(function(b){ b.classList.toggle('actif', b.dataset.liste === cat); });
    var u = new URL(location.href); u.searchParams.set('liste', cat); history.replaceState(null, '', u);
}
/* Enregistrement immédiat d'une ligne (libellé, intervalle, activation) */
function ppEnregistrer(tr, donnees, champ){
    var ok = tr.querySelector('.pp-ok');
    fetch(@js(url('admin/parc-parametres/types')) + '/' + tr.dataset.id, {
        method: 'PATCH', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify(donnees)
    }).then(function(r){ return r.json().then(function(j){ return {ok: r.ok, j: j}; }); }).then(function(res){
        if (!res.ok) throw new Error(res.j.message || 'Enregistrement impossible.');
        if ('actif' in donnees) { tr.classList.toggle('inactif', !donnees.actif); champ.closest('.inter').title = donnees.actif ? 'Proposé dans les formulaires' : 'Non proposé'; }
        if (champ && champ.dataset.ancien !== undefined) champ.dataset.ancien = champ.value;
        ok.classList.add('vu'); setTimeout(function(){ ok.classList.remove('vu'); }, 1400);
    }).catch(function(e){
        alert(e.message);
        if (champ && champ.type === 'checkbox') champ.checked = !champ.checked;
        else if (champ) champ.value = champ.dataset.ancien;
    });
}
document.querySelectorAll('.pp-lib,.pp-int').forEach(function(el){
    el.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); el.blur(); } if (e.key === 'Escape') { el.value = el.dataset.ancien; el.blur(); } });
    el.addEventListener('change', function(){
        var v = el.value.trim();
        if (el.dataset.champ === 'libelle' && v.length < 2) { el.value = el.dataset.ancien; return; }
        if (v === (el.dataset.ancien || '')) return;
        var d = {}; d[el.dataset.champ] = v === '' ? null : v;
        ppEnregistrer(el.closest('tr'), d, el);
    });
});
function apercuExercice(){
    var j=parseInt(document.getElementById('ex-jour').value,10), sel=document.getElementById('ex-mois'), m=sel.value.split('-');
    var d=new Date(+m[0], +m[1]-1, j), f=new Date(+m[0], +m[1], j-1);
    var fmt=function(x){return ('0'+x.getDate()).slice(-2)+'/'+('0'+(x.getMonth()+1)).slice(-2)+'/'+x.getFullYear();};
    document.getElementById('ex-apercu').textContent='Premier exercice avec cette règle : du '+fmt(d)+' au '+fmt(f)+'. L\'exercice précédent se terminera le '+fmt(new Date(d.getTime()-86400000))+'.';
}
apercuExercice();
ppOnglet(@js($onglet));
ppListe(@js($listeActive));
</script>
@endpush
