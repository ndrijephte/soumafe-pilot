@extends('layouts.app')
@section('titre', 'Registres opérationnels')
@push('styles')
<style>
.reg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-bottom:18px}
.reg-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;transition:box-shadow .2s}
.reg-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.reg-card h4{font-size:14px;color:var(--bleu);margin:0 0 4px}
.reg-card .desc{font-size:11.5px;color:var(--gris);margin-bottom:8px;line-height:1.4}
.reg-card .meta{display:flex;gap:10px;font-size:11px;color:var(--gris)}
.reg-card .meta strong{color:#1e293b}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-quot{background:#dbeafe;color:#1d4ed8}.b-hebd{background:#dcfce7;color:var(--ok)}.b-mens{background:#fef3c7;color:#b45309}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11px}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
dialog{border:none;border-radius:14px;padding:24px;width:520px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px;font-weight:600;text-transform:uppercase}
dialog input,dialog select,dialog textarea{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
.req{color:var(--ko)}
.col-builder{border:1px solid var(--bord);border-radius:8px;padding:10px;margin-top:6px;background:var(--fond)}
.col-row{display:flex;gap:6px;align-items:center;margin-bottom:6px}
.col-row input,.col-row select{flex:1;padding:5px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12px}
.col-row button{flex:none}
</style>
@endpush
@section('contenu')
<h2>Registres op&eacute;rationnels</h2>
<div class="crumb">Op&eacute;rations &rsaquo; Registres de suivi</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif

<div style="margin-bottom:14px">
    <button class="btn" onclick="document.getElementById('dlg-reg').showModal()">+ Cr&eacute;er un registre</button>
</div>

@if($registres->count() > 0)
<div class="reg-grid">
    @foreach($registres as $reg)
    @php $pBadge = match($reg->periodicite) { 'quotidien' => 'b-quot', 'hebdomadaire' => 'b-hebd', default => 'b-mens' }; @endphp
    <div class="reg-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
            <h4>{{ $reg->libelle }}</h4>
            <span class="badge {{ $pBadge }}">{{ ucfirst($reg->periodicite) }}</span>
        </div>
        @if($reg->description)<div class="desc">{{ $reg->description }}</div>@endif
        <div class="meta">
            <span><strong>{{ count($reg->definition_colonnes ?? []) }}</strong> colonnes</span>
            <span><strong>{{ $reg->entrees_count }}</strong> entr&eacute;es</span>
        </div>
        <div style="margin-top:10px;display:flex;gap:6px">
            <a href="{{ route('registres.show', $reg) }}" class="btn sm">Ouvrir</a>
            <form method="POST" action="{{ route('registres.destroy', $reg) }}" onsubmit="return confirm('Supprimer ce registre et toutes ses entrées ?')">@csrf <input type="hidden" name="_method" value="DELETE"><button class="btn sm danger">Suppr.</button></form>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="carte" style="text-align:center;color:var(--gris);padding:30px">Aucun registre cr&eacute;&eacute;. Commencez par d&eacute;finir la structure d'un registre.</div>
@endif

{{-- Dialog créer registre --}}
<dialog id="dlg-reg">
    <h3 style="color:var(--bleu);margin-bottom:4px">Cr&eacute;er un registre</h3>
    <form method="POST" action="{{ route('registres.store') }}" onsubmit="prepareColonnes()">@csrf
        <label>Libell&eacute; <span class="req">*</span></label>
        <input name="libelle" required placeholder="Ex : Registre des incidents">
        <label>Description</label>
        <textarea name="description" rows="2" placeholder="Objectif du registre..."></textarea>
        <label>P&eacute;riodicit&eacute; <span class="req">*</span></label>
        <select name="periodicite">
            <option value="quotidien">Quotidien</option>
            <option value="hebdomadaire">Hebdomadaire</option>
            <option value="mensuel">Mensuel</option>
        </select>
        <label>Colonnes <span class="req">*</span></label>
        <div class="col-builder" id="col-builder">
            <div class="col-row">
                <input placeholder="Nom de la colonne" class="col-nom">
                <select class="col-type"><option value="texte">Texte</option><option value="nombre">Nombre</option><option value="date">Date</option><option value="oui_non">Oui/Non</option></select>
                <button type="button" class="btn sm danger" onclick="this.closest('.col-row').remove()">&times;</button>
            </div>
        </div>
        <button type="button" class="btn sm sec" style="margin-top:6px" onclick="ajouterColonne()">+ Colonne</button>
        <input type="hidden" name="colonnes" id="colonnes-json">
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Cr&eacute;er</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
function ajouterColonne(){
    const b = document.getElementById('col-builder');
    const d = document.createElement('div'); d.className='col-row';
    d.innerHTML='<input placeholder="Nom de la colonne" class="col-nom"><select class="col-type"><option value="texte">Texte</option><option value="nombre">Nombre</option><option value="date">Date</option><option value="oui_non">Oui/Non</option></select><button type="button" class="btn sm danger" onclick="this.closest(\'.col-row\').remove()">&times;</button>';
    b.appendChild(d);
}
function prepareColonnes(){
    const rows = document.querySelectorAll('.col-row');
    const cols = [];
    rows.forEach(r => {
        const nom = r.querySelector('.col-nom').value.trim();
        const type = r.querySelector('.col-type').value;
        if(nom) cols.push({nom, type});
    });
    document.getElementById('colonnes-json').value = JSON.stringify(cols);
}
</script>
@endpush
