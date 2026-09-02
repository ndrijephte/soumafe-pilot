<style>
@media(max-width:900px){.admin-tbl{display:block;overflow-x:auto}.form-row,.form-row3{grid-template-columns:1fr}.form-card input,.form-card select{max-width:none}}
@media(max-width:600px){.form-card{padding:12px 10px}.form-actions{flex-direction:column}.form-actions .btn{width:100%;text-align:center}.admin-tbl th,.admin-tbl td{padding:6px 8px;font-size:12px}}
</style>
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px">
@php $liens = [
    ['admin.utilisateurs.index', 'Comptes'],
    ['admin.projets.index', 'Projets'],
    ['admin.domaines.index', 'Domaines'],
    ['admin.services.index', 'Services'],
    ['admin.poles.index', 'P&ocirc;les'],
    ['admin.roles.index', 'R&ocirc;les'],
    ['admin.profils.index', 'Profils'],
    ['admin.feries.index', 'Jours f&eacute;ri&eacute;s'],
    ['admin.types-evenement.index', 'Types &eacute;v&eacute;nement'],
    ['admin.types-blocage.index', 'Types blocage'],
    ['admin.titres-poste.index', 'Titres de poste'],
]; @endphp
@foreach($liens as [$route, $label])
@if(Route::has($route))
<a href="{{ route($route) }}" style="padding:6px 14px;border-radius:8px;font-size:12.5px;text-decoration:none;font-weight:600;{{ request()->routeIs(explode('.index',$route)[0].'*') ? 'background:var(--accent);color:#fff' : 'background:#e2e8f0;color:var(--gris)' }}">{!! $label !!}</a>
@endif
@endforeach
</div>
