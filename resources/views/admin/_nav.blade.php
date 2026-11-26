{{--
    Navigation de l'Administration, en groupes (sur le modèle du parc) :
    onglets des groupes, puis les écrans du groupe ouvert. Les listes de choix ont un écran commun.
--}}
@php
    $groupes = [
        'comptes' => ['Comptes et accès', [
            ['admin.utilisateurs', 'Comptes'],
            ['admin.acces', 'Rôles et profils'],
            ['admin.roles', null], ['admin.profils', null],
        ]],
        'organisation' => ['Organisation', [
            ['admin.organisation', 'Structure'],
            ['admin.projets', 'Projets'],
            ['admin.domaines', null], ['admin.services', null], ['admin.poles', null],
        ]],
        'calendrier' => ['Temps de travail', [
            ['admin.horaires', 'Horaires'],
            ['admin.feries', 'Jours fériés'],
        ]],
        'listes' => ['Listes de choix', [
            ['admin.listes', 'Listes de choix'],
            ['admin.types-evenement', null], ['admin.types-blocage', null], ['admin.titres-poste', null],
        ]],
        'parc' => ['Parc automobile', [
            ['admin.parc-parametres', 'Paramètres du parc'],
        ]],
    ];
    $groupeActif = collect($groupes)->search(fn($g) => collect($g[1])->contains(fn($e) => request()->routeIs($e[0] . '.*')));
@endphp
<style>
.adm-nav{margin:2px 0 16px}
.adm-groupes{display:flex;gap:4px;box-shadow:inset 0 -1px 0 var(--bord);overflow-x:auto;scrollbar-width:none}
.adm-groupes a{padding:9px 14px;font-size:13px;font-weight:600;color:var(--gris);text-decoration:none;border-bottom:2px solid transparent;white-space:nowrap}
.adm-groupes a:hover{color:var(--bleu)}
.adm-groupes a.actif{color:var(--accent);border-bottom-color:var(--accent)}
.adm-ecrans{display:flex;gap:6px;flex-wrap:wrap;margin-top:12px}
.adm-ecrans a{padding:5px 12px;font-size:12.5px;font-weight:600;color:#475569;background:#fff;border:1px solid var(--bord);border-radius:8px;text-decoration:none}
.adm-ecrans a:hover{color:var(--accent);border-color:#9fbfe0}
.adm-ecrans a.actif{color:#fff;background:var(--accent);border-color:var(--accent)}
/* Administration, mode jour : contraste légèrement renforcé (textes plus foncés, séparations plus nettes) */
html:not(.nuit) main:has(.adm-nav){--gris:#475569;--bord:#d3dbe6;color:#0f172a}
html:not(.nuit) main:has(.adm-nav) .crumb{color:#475569}
html:not(.nuit) main:has(.adm-nav) :is(.ad-table,.pp-table) td,
html:not(.nuit) main:has(.adm-nav) :is(.pp-lib,.og-lib,.fm-champ){color:#0f172a}
html:not(.nuit) main:has(.adm-nav) :is(.ad-table,.pp-table) th,
html:not(.nuit) main:has(.adm-nav) .og-ligne.entete{color:#334155}
html:not(.nuit) main:has(.adm-nav) :is(.ad-table,.pp-table) td,
html:not(.nuit) main:has(.adm-nav) .og-ligne{border-bottom-color:#e3e8ef}
html:not(.nuit) main:has(.adm-nav) :is(.ad-panneau,.pp-panneau,.pp-menu,.og-panneau,.hr-vigueur,.cp-carte,.cp-apercu,.carte){border-color:#d3dbe6}
html:not(.nuit) main:has(.adm-nav) :is(.ad-table .sec,.pp-usage,.og-compte,.fm-aide,.pp-aide,.og-aide,.hr-note,.compte){color:#475569}
html:not(.nuit) main:has(.adm-nav) :is(.fm-lab .fac,.pp-code){color:#64748b}
html:not(.nuit) main:has(.adm-nav) .adm-groupes a:not(.actif){color:#475569}
html:not(.nuit) main:has(.adm-nav) :is(.pp-menu button,.adm-ecrans a:not(.actif),.fm-lab){color:#1e293b}
html:not(.nuit) main:has(.adm-nav) .etat.mut{color:#64748b}
@media(max-width:900px){.admin-tbl{display:block;overflow-x:auto}.form-row,.form-row3{grid-template-columns:1fr}.form-card input,.form-card select{max-width:none}}
@media(max-width:600px){.form-card{padding:12px 10px}.form-actions{flex-direction:column}.form-actions .btn{width:100%;text-align:center}.admin-tbl th,.admin-tbl td{padding:6px 8px;font-size:12px}}
</style>
<nav class="adm-nav" aria-label="Administration">
    <div class="adm-groupes">
        @foreach($groupes as $cle => [$titre, $ecrans])
            @php $premier = collect($ecrans)->first(fn($e) => $e[1] && Route::has($e[0] . '.index')); @endphp
            @if($premier)
            <a href="{{ route($premier[0] . '.index') }}" class="{{ $groupeActif === $cle ? 'actif' : '' }}">{{ $titre }}</a>
            @endif
        @endforeach
    </div>
    @php $visibles = $groupeActif ? collect($groupes[$groupeActif][1])->filter(fn($e) => $e[1] && Route::has($e[0] . '.index')) : collect(); @endphp
    @if($visibles->count() > 1)
    <div class="adm-ecrans">
        @foreach($visibles as [$route, $label])
        <a href="{{ route($route . '.index') }}" class="{{ request()->routeIs($route . '.*') ? 'actif' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    @endif
</nav>
