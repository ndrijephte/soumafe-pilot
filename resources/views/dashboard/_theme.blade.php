{{--
    Thème commun des tableaux de bord : indicateurs clés et accès au détail de chaque bloc.
    @include('dashboard._theme')
    Tuile :    <div class="kpi"><x-voyant … /><x-detail :href="…" titre="…" /> …</div>
    Panneau :  <h3>… Titre <x-detail :href="…" titre="…" /></h3>
--}}
@once
@push('styles')
<style>
/* Indicateurs clés : une rangée, tuiles sobres ; la couleur signale, elle ne décore pas */
.kpis.kpi-v2{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:20px}
.kpi-v2 .kpi{position:relative;background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px 16px 13px;overflow:hidden;transition:box-shadow .15s}
.kpi-v2 .kpi:hover{transform:none;box-shadow:0 4px 14px rgba(15,23,42,.06)}
/* Coin haut droit : le voyant d'urgence, puis l'accès au détail */
.kpi-v2 .kpi>.voyant{position:absolute;top:15px;right:15px}
.kpis .kpi{position:relative}
.kpi>.detail{position:absolute;top:8px;right:8px}
.kpi:has(>.detail)>.voyant{right:42px}
.kpi-v2 .lbl{display:flex;align-items:center;gap:6px;padding-right:20px;font-size:10.5px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
.kpi:has(>.detail) .lbl{padding-right:30px}
.kpi-v2 .kpi:has(>.detail):has(>.voyant) .lbl{padding-right:58px}
.kpi-v2 .lbl svg{width:14px;height:14px;color:#94a3b8}
.kpi-v2 .val{font-size:26px;font-weight:700;color:#1f2937;margin:6px 0 2px;line-height:1.1}
.kpi-v2 .kpi.signal-ko .val{color:#b91c1c}.kpi-v2 .kpi.signal-warn .val{color:#b45309}
.kpi-v2 .sub{font-size:11.5px;color:#64748b}
.kpi-v2 .sub a{color:#2e74b5;text-decoration:none;font-weight:600}
.kpi-v2 .barre-k{height:5px;background:#e5eaf1;border-radius:5px;overflow:hidden;margin-top:8px}.kpi-v2 .barre-k>i{display:block;height:100%;background:#2e74b5;border-radius:5px}
/* Panneaux : l'accès au détail en bout de titre */
.panneau h3>.detail{margin-left:auto;margin-top:-4px;margin-bottom:-4px}
@media(max-width:1100px){.kpis.kpi-v2{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:700px){.kpis.kpi-v2{grid-template-columns:repeat(2,minmax(0,1fr))}.kpi-v2 .val{font-size:22px}}
</style>
@endpush
@endonce
