@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Listes de choix')
@push('styles')
<style>
.pp-aide{font-size:12.5px;color:var(--gris);margin:0 0 14px;line-height:1.5}
.pp-listes{display:grid;grid-template-columns:230px 1fr;gap:16px;align-items:start}
.pp-menu{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:6px}
.pp-menu button,.pp-menu a{display:flex;justify-content:space-between;align-items:center;width:100%;background:none;border:none;border-radius:8px;padding:9px 10px;font-size:13px;color:#334155;cursor:pointer;text-align:left;text-decoration:none;font-family:inherit}
.pp-menu button:hover,.pp-menu a:hover{background:#f1f5f9}
.pp-menu button.actif{background:#e6f0fa;color:var(--accent);font-weight:600}
.pp-menu .c{font-size:11px;color:var(--gris);font-weight:400}
.pp-menu .sep{height:1px;background:var(--bord);margin:6px 4px}
.pp-menu a{color:var(--accent);font-size:12.5px}
.pp-panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px 18px}
.pp-panneau h3{font-size:14px;color:var(--bleu);margin:0 0 4px}
.pp-table{width:100%;border-collapse:collapse;font-size:13px}
.pp-table th{text-align:left;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--gris);padding:7px 8px;border-bottom:1px solid var(--bord)}
.pp-table td{padding:6px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.pp-table tr:last-child td{border-bottom:none}
.pp-lib{width:100%;border:1px solid transparent;border-radius:6px;padding:5px 8px;font-size:13px;background:transparent;font-family:inherit;color:#1e293b}
.pp-lib:hover{border-color:var(--bord)}
.pp-lib:focus{background:#fff;outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.pp-usage{font-size:12px;color:var(--gris);white-space:nowrap}
.pp-ok{font-size:11px;color:var(--ok);margin-left:6px;opacity:0;transition:opacity .3s;white-space:nowrap}
.pp-ok.vu{opacity:1}
.sys{font-size:10.5px;color:var(--gris);background:#f1f5f9;border-radius:6px;padding:1px 7px;white-space:nowrap}
.apercu{display:inline-block;font-size:11px;font-weight:600;padding:2px 9px;border-radius:10px;white-space:nowrap}
.coul{display:flex;gap:4px}
.coul button{width:18px;height:18px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px var(--bord);cursor:pointer;padding:0}
.coul button.actif{box-shadow:0 0 0 2px var(--accent)}
.ib{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:none;background:none;cursor:pointer;color:var(--ko)}
.ib:hover{background:#fee2e2}
.ib:disabled{color:#cbd5e1;cursor:not-allowed;background:none}
.pp-ajout{display:flex;gap:8px;align-items:center;margin:4px 0 6px;padding:10px 12px;background:#f6f9fc;border-radius:10px;flex-wrap:wrap}
.pp-ajout input[name=libelle]{flex:1;min-width:200px;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
.btn{background:var(--accent);color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12.5px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.alerte{border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
@media(max-width:800px){.pp-listes{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
@php
    $active = array_key_exists(request('liste'), $listes) ? request('liste') : array_key_first($listes);
    $icSuppr = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>';
    $couleurDe = fn($t) => collect($couleurs)->search(fn($c) => strcasecmp($c[1], (string) $t->couleur_texte) === 0) ?: 'gris';
@endphp
<h2>Administration</h2>
<div class="crumb">Administration &rsaquo; {{ $infoGroupe[0] }}</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<p class="pp-aide">{{ $infoGroupe[1] }} Cliquez un libellé pour le corriger : l'enregistrement est immédiat.
    Une valeur utilisée ou système ne peut pas être supprimée. @if($groupe === 'listes')Une valeur manquante peut aussi être ajoutée depuis le formulaire lui-même, avec « + Autre… ».@endif</p>

<div class="pp-listes">
    <nav class="pp-menu">
        @foreach($listes as $cle => $l)
        <button type="button" data-sans-icone data-liste="{{ $cle }}" onclick="lcListe('{{ $cle }}')">{{ $l['titre'] }} <span class="c">{{ $l['items']->count() }}</span></button>
        @endforeach
        @if($groupe === 'listes')
        <div class="sep"></div>
        <a href="{{ route('admin.parc-parametres.index', ['tab' => 'listes']) }}">Listes du parc automobile →</a>
        @endif
    </nav>
    <div>
    @foreach($listes as $cle => $l)
    <div class="pp-panneau" id="liste-{{ $cle }}" hidden>
        <h3>{{ $l['titre'] }}</h3>
        <p class="pp-aide" style="margin-bottom:8px">{{ $l['aide'] }}</p>
        <form method="POST" action="{{ route('admin.listes.store') }}" class="pp-ajout haut">
            @csrf
            <input type="hidden" name="liste" value="{{ $cle }}">
            <input name="libelle" required minlength="2" maxlength="80" placeholder="Nouveau libellé" aria-label="Nouveau libellé">
            @if($cle === 'type_evenement')
            <select name="couleur" data-pastilles aria-label="Couleur">
                @foreach($couleurs as $ck => [$fond, $texte, $nom])<option value="{{ $ck }}" data-couleur="{{ $texte }}" @selected($ck === 'gris')>{{ $nom }}</option>@endforeach
            </select>
            @endif
            <button class="btn">Ajouter</button>
        </form>
        <table class="pp-table">
            <thead><tr><th>Libellé</th>@if($cle === 'type_evenement')<th>Couleur</th>@endif<th>Utilisation</th><th></th></tr></thead>
            <tbody>
            @forelse($l['items'] as $t)
                @php $n = $l['usages'][$t->{$l['cleData']}] ?? 0; $sys = !empty($t->systeme); @endphp
                <tr data-id="{{ $t->id }}" data-liste="{{ $cle }}">
                    <td><div style="display:flex;align-items:center;gap:6px">
                        @if($cle === 'type_evenement')<span class="apercu" style="background:{{ $t->couleur_fond }};color:{{ $t->couleur_texte }}">●</span>@endif
                        <input class="pp-lib" value="{{ $t->libelle }}" data-ancien="{{ $t->libelle }}" maxlength="80" data-sans-controle aria-label="Libellé" @if(isset($t->code)) title="Code enregistré : {{ $t->code }}" @endif>
                        <span class="pp-ok">enregistré</span>
                        @if($sys)<span class="sys" title="Valeur prévue par le système : renommable, non supprimable">système</span>@endif
                    </div></td>
                    @if($cle === 'type_evenement')
                    <td><div class="coul">
                        @foreach($couleurs as $ck => [$fond, $texte, $nom])
                        <button type="button" data-sans-icone title="{{ $nom }}" class="{{ $couleurDe($t) === $ck ? 'actif' : '' }}" style="background:{{ $texte }}" onclick="lcCouleur(this, '{{ $ck }}', '{{ $fond }}', '{{ $texte }}')"></button>
                        @endforeach
                    </div></td>
                    @endif
                    <td class="pp-usage">{{ $l['unite'] ? ($n ? $n . ' ' . $l['unite'] . ($n > 1 ? 's' : '') : 'aucun ' . $l['unite']) : ($n ? $n . ' fois' : 'jamais') }}</td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('admin.listes.destroy', [$cle, $t->id]) }}" style="display:inline" onsubmit="return confirm('Supprimer « {{ addslashes($t->libelle) }} » ?')">
                            @csrf @method('DELETE')
                            <button class="ib" data-sans-icone @disabled($n || $sys) title="{{ $sys ? 'Valeur système' : ($n ? 'Utilisé ' . $n . ' fois : renommez-le plutôt' : 'Supprimer') }}" aria-label="Supprimer">{!! $icSuppr !!}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="pp-usage" style="padding:16px 8px">Liste vide : ajoutez une première valeur ci-dessous.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
function lcListe(cle){
    document.querySelectorAll('[id^="liste-"]').forEach(function(p){ p.hidden = p.id !== 'liste-' + cle; });
    document.querySelectorAll('.pp-menu button').forEach(function(b){ b.classList.toggle('actif', b.dataset.liste === cle); });
    var u = new URL(location.href); u.searchParams.set('liste', cle); history.replaceState(null, '', u);
}
function lcEnregistrer(tr, donnees){
    return fetch(@js(url('admin/listes')) + '/' + tr.dataset.liste + '/' + tr.dataset.id, {
        method: 'PATCH', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify(donnees)
    }).then(function(r){ return r.json().then(function(j){ return {ok: r.ok, j: j}; }); }).then(function(res){
        if (!res.ok) throw new Error(res.j.message || 'Enregistrement impossible.');
        var ok = tr.querySelector('.pp-ok'); ok.classList.add('vu'); setTimeout(function(){ ok.classList.remove('vu'); }, 1400);
    });
}
document.querySelectorAll('.pp-lib').forEach(function(el){
    el.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); el.blur(); } if (e.key === 'Escape') { el.value = el.dataset.ancien; el.blur(); } });
    el.addEventListener('change', function(){
        var v = el.value.trim();
        if (v.length < 2 || v === el.dataset.ancien) { el.value = el.dataset.ancien; return; }
        lcEnregistrer(el.closest('tr'), {libelle: v}).then(function(){ el.dataset.ancien = v; }).catch(function(e){ alert(e.message); el.value = el.dataset.ancien; });
    });
});
function lcCouleur(b, cle, fond, texte){
    var tr = b.closest('tr');
    lcEnregistrer(tr, {couleur: cle}).then(function(){
        tr.querySelectorAll('.coul button').forEach(function(x){ x.classList.toggle('actif', x === b); });
        var a = tr.querySelector('.apercu'); a.style.background = fond; a.style.color = texte;
    }).catch(function(e){ alert(e.message); });
}
lcListe(@js($active));
</script>
@endpush
