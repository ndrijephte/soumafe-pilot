@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Structure de l\'organisation')
@push('styles')
<style>
.og-aide{font-size:12.5px;color:var(--gris);margin:0 0 14px;line-height:1.5}
.og-panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:8px 18px 14px}
.og-ligne{display:grid;grid-template-columns:minmax(0,1fr) 220px 150px 110px;gap:10px;align-items:center;padding:6px 0;border-bottom:1px solid #f1f5f9}
.og-ligne.entete{font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--gris);border-bottom:1px solid var(--bord);padding:8px 0}
.og-nom{display:flex;align-items:center;gap:8px;min-width:0}
.og-nom .niv{flex:none;font-size:10px;font-weight:600;color:var(--gris);background:#f1f5f9;border-radius:6px;padding:1px 7px;width:58px;text-align:center}
.og-ligne.domaine .niv{background:#e6f0fa;color:var(--accent)}
.og-ligne.service .og-nom{padding-left:26px}
.og-ligne.pole .og-nom{padding-left:52px}
.og-ligne.domaine .og-lib{font-weight:600;color:var(--bleu)}
.og-lib{flex:1;min-width:0;border:1px solid transparent;border-radius:6px;padding:5px 8px;font-size:13px;background:transparent;font-family:inherit;color:#1e293b}
.og-lib:hover,.og-parent:hover{border-color:var(--bord)}
.og-lib:focus,.og-parent:focus{background:#fff;outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.og-parent{width:100%;border:1px solid transparent;border-radius:6px;padding:5px 6px;font-size:12px;background:transparent;color:#475569;font-family:inherit}
.og-compte{font-size:12px;color:var(--gris)}
.og-actions{display:flex;justify-content:flex-end;gap:2px}
.og-ok{font-size:11px;color:var(--ok);opacity:0;transition:opacity .3s;white-space:nowrap}
.og-ok.vu{opacity:1}
.ib{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:8px;border:none;background:none;cursor:pointer;color:var(--accent)}
.ib:hover{background:#e6f0fa}
.ib.ko{color:var(--ko)}.ib.ko:hover{background:#fee2e2}
.ib:disabled{color:#cbd5e1;cursor:not-allowed;background:none}
.og-ajout{display:flex;gap:8px;align-items:center;padding:8px 0}
.og-ajout[hidden]{display:none}
.og-ajout input{flex:1;max-width:380px;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;font-family:inherit}
.og-ajout.enfant{padding-left:26px}.og-ajout.enfant.pole{padding-left:52px}
.og-orphelins{margin-top:14px;font-size:12px;font-weight:600;color:var(--gris);text-transform:uppercase;letter-spacing:.4px}
.btn{background:var(--accent);color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12.5px;cursor:pointer;display:inline-flex;align-items:center;gap:6px}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.alerte{border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
@media(max-width:900px){.og-ligne{grid-template-columns:1fr 1fr}.og-ligne.entete{display:none}.og-ligne.service .og-nom,.og-ligne.pole .og-nom{padding-left:0}}
</style>
@endpush
@section('contenu')
@php
    $icPlus = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
    $icSuppr = '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/></svg>';
    $servicesPar = $services->groupBy(fn($s) => $s->domaine_id ?: 0);
    $polesPar = $poles->groupBy(fn($p) => $p->service_id ?: 0);
    $collabService = fn($s) => ($polesPar[$s->id] ?? collect())->sum('users_count');
    $nbJoint = fn($n, $sing, $plur) => $n ? $n . ' ' . ($n > 1 ? $plur : $sing) : '';
@endphp
<h2>Administration</h2>
<div class="crumb">Administration &rsaquo; Organisation &rsaquo; Structure</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<p class="og-aide">Domaines, services et pôles sur un seul écran. Cliquez un nom pour le corriger, changez le rattachement dans la colonne voisine :
    l'enregistrement est immédiat. Une branche qui contient encore des services, des pôles ou des collaborateurs ne peut pas être supprimée.</p>

@php
    $ligne = function ($niveau, $item, $parents, $parentId, $compte, $plein, $enfant) use ($icPlus, $icSuppr) {
        return view('admin._organisation_ligne', compact('niveau', 'item', 'parents', 'parentId', 'compte', 'plein', 'enfant', 'icPlus', 'icSuppr'))->render();
    };
@endphp
<div class="og-panneau">
    <form method="POST" action="{{ route('admin.organisation.store') }}" class="og-ajout" style="margin:6px 0 4px;padding:10px 12px;background:#f6f9fc;border-radius:10px">
        @csrf <input type="hidden" name="niveau" value="domaine">
        <input name="libelle" required minlength="2" maxlength="100" placeholder="Nouveau domaine d'activité"><button class="btn">Ajouter un domaine</button>
    </form>
    <div class="og-ligne entete"><span>Nom</span><span>Rattaché à</span><span>Contenu</span><span></span></div>
    @foreach($domaines as $d)
        @php $sv = $servicesPar[$d->id] ?? collect(); $nbCollab = $sv->sum(fn($s) => $collabService($s)); @endphp
        {!! $ligne('domaine', $d, null, null, trim(collect([$nbJoint($sv->count(), 'service', 'services'), $nbJoint($nbCollab, 'collaborateur', 'collaborateurs')])->filter()->implode(' · ')), $sv->isNotEmpty(), 'service') !!}
        @foreach($sv as $s)
            @php $pl = $polesPar[$s->id] ?? collect(); @endphp
            {!! $ligne('service', $s, $domaines, $s->domaine_id, trim(collect([$nbJoint($pl->count(), 'pôle', 'pôles'), $nbJoint($collabService($s), 'collaborateur', 'collaborateurs')])->filter()->implode(' · ')), $pl->isNotEmpty(), 'pole') !!}
            @foreach($pl as $p)
                {!! $ligne('pole', $p, $services, $p->service_id, $nbJoint($p->users_count, 'collaborateur', 'collaborateurs'), $p->users_count > 0, null) !!}
            @endforeach
            <form method="POST" action="{{ route('admin.organisation.store') }}" class="og-ajout enfant pole" id="ajout-pole-{{ $s->id }}" hidden>
                @csrf <input type="hidden" name="niveau" value="pole"><input type="hidden" name="parent_id" value="{{ $s->id }}">
                <input name="libelle" required minlength="2" maxlength="100" placeholder="Nouveau pôle dans « {{ $s->libelle }} »"><button class="btn">Ajouter</button>
                <button type="button" class="btn sec" onclick="this.form.hidden=true">Annuler</button>
            </form>
        @endforeach
        <form method="POST" action="{{ route('admin.organisation.store') }}" class="og-ajout enfant" id="ajout-service-{{ $d->id }}" hidden>
            @csrf <input type="hidden" name="niveau" value="service"><input type="hidden" name="parent_id" value="{{ $d->id }}">
            <input name="libelle" required minlength="2" maxlength="100" placeholder="Nouveau service dans « {{ $d->libelle }} »"><button class="btn">Ajouter</button>
            <button type="button" class="btn sec" onclick="this.form.hidden=true">Annuler</button>
        </form>
    @endforeach

    @php $svO = $servicesPar[0] ?? collect(); $plO = $polesPar[0] ?? collect(); @endphp
    @if($svO->isNotEmpty() || $plO->isNotEmpty())
        <div class="og-orphelins">Non rattachés</div>
        @foreach($svO as $s)
            @php $pl = $polesPar[$s->id] ?? collect(); @endphp
            {!! $ligne('service', $s, $domaines, null, $nbJoint($pl->count(), 'pôle', 'pôles'), $pl->isNotEmpty(), null) !!}
            @foreach($pl as $p)
                {!! $ligne('pole', $p, $services, $p->service_id, $nbJoint($p->users_count, 'collaborateur', 'collaborateurs'), $p->users_count > 0, null) !!}
            @endforeach
        @endforeach
        @foreach($plO as $p)
            {!! $ligne('pole', $p, $services, null, $nbJoint($p->users_count, 'collaborateur', 'collaborateurs'), $p->users_count > 0, null) !!}
        @endforeach
    @endif

</div>
@endsection

@push('scripts')
<script>
function ogEnregistrer(el, donnees){
    var l = el.closest('.og-ligne');
    return fetch(@js(url('admin/organisation')) + '/' + l.dataset.niveau + '/' + l.dataset.id, {
        method: 'PATCH', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content},
        body: JSON.stringify(donnees)
    }).then(function(r){ return r.json().then(function(j){ return {ok: r.ok, j: j}; }); }).then(function(res){
        if (!res.ok) throw new Error(res.j.message || 'Enregistrement impossible.');
        var ok = l.querySelector('.og-ok'); ok.classList.add('vu'); setTimeout(function(){ ok.classList.remove('vu'); }, 1400);
    });
}
document.querySelectorAll('.og-lib').forEach(function(el){
    el.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); el.blur(); } if (e.key === 'Escape') { el.value = el.dataset.ancien; el.blur(); } });
    el.addEventListener('change', function(){
        var v = el.value.trim();
        if (v.length < 2 || v === el.dataset.ancien) { el.value = el.dataset.ancien; return; }
        ogEnregistrer(el, {libelle: v}).then(function(){ el.dataset.ancien = v; }).catch(function(e){ alert(e.message); el.value = el.dataset.ancien; });
    });
});
document.querySelectorAll('.og-parent').forEach(function(el){
    el.addEventListener('change', function(){
        ogEnregistrer(el, {parent_id: el.value || null}).then(function(){ location.reload(); }).catch(function(e){ alert(e.message); el.value = el.dataset.ancien; });
    });
});
function ogAjouter(id){ var f = document.getElementById(id); f.hidden = false; f.querySelector('input[name=libelle]').focus(); }
</script>
@endpush
