{{--
    Thème commun des tableaux de bord : encart « Points d'attention » et indicateurs clés.
    @include('dashboard._theme')                                  styles
    @include('dashboard._attention', ['alertes' => $alertes])     encart d'alertes
--}}
@once
@push('styles')
<style>
/* Indicateurs clés : une rangée, tuiles sobres ; la couleur signale, elle ne décore pas */
.kpis.kpi-v2{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:20px}
.kpi-v2 .kpi{position:relative;background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px 16px 13px;overflow:hidden;transition:box-shadow .15s}
.kpi-v2 .kpi:hover{transform:none;box-shadow:0 4px 14px rgba(15,23,42,.06)}
/* Urgence : un petit voyant dans le coin de la tuile */
.kpi-v2 .kpi>.voyant{position:absolute;top:15px;right:15px}
.kpi-v2 .lbl{display:flex;align-items:center;gap:6px;padding-right:20px;font-size:10.5px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
.kpi-v2 .lbl svg{width:14px;height:14px;color:#94a3b8}
.kpi-v2 .val{font-size:26px;font-weight:700;color:#1f2937;margin:6px 0 2px;line-height:1.1}
.kpi-v2 .kpi.signal-ko .val{color:#b91c1c}.kpi-v2 .kpi.signal-warn .val{color:#b45309}
.kpi-v2 .sub{font-size:11.5px;color:#64748b}
.kpi-v2 .sub a{color:#2e74b5;text-decoration:none;font-weight:600}
.kpi-v2 .barre-k{height:5px;background:#e5eaf1;border-radius:5px;overflow:hidden;margin-top:8px}.kpi-v2 .barre-k>i{display:block;height:100%;background:#2e74b5;border-radius:5px}
/* Points d'attention : un seul encart, une ligne par alerte */
.attention{background:var(--carte);border:1px solid var(--bord);border-radius:12px;margin-bottom:16px;overflow:hidden}
.attention-tete{display:flex;align-items:center;gap:8px;padding:10px 16px;border-bottom:1px solid var(--bord);font-size:12.5px;font-weight:700;color:#1f4e79}
.attention-tete .nb{background:#eef4fb;color:#2e74b5;border-radius:999px;padding:1px 8px;font-size:11px}
.attention ul{list-style:none;margin:0;padding:4px 0}
.attention li{display:flex;align-items:flex-start;gap:10px;padding:7px 16px;font-size:12.5px;color:#334155;line-height:1.45}
.attention li+li{border-top:1px solid #f1f5f9}
.attention li i{flex:none;width:9px;height:9px;border-radius:50%;margin-top:5px;background:#2e74b5}
.attention li.ko i{background:#dc2626}.attention li.warn i{background:#ea580c}
.attention li.ko{color:#7f1d1d}
/* Trois alertes au plus, les plus graves d'abord ; le reste à la demande */
.attention:not(.ouvert) li:nth-child(n+4){display:none}
.attention-plus{margin-left:auto;background:none;border:0;padding:0;font:inherit;font-size:11.5px;font-weight:600;color:#2e74b5;cursor:pointer}
.attention-plus:hover{text-decoration:underline}
.attention-plus .deplie,.attention.ouvert .attention-plus .replie{display:none}
.attention.ouvert .attention-plus .deplie{display:inline}
@media(max-width:1100px){.kpis.kpi-v2{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:700px){.kpis.kpi-v2{grid-template-columns:repeat(2,minmax(0,1fr))}.kpi-v2 .val{font-size:22px}}
</style>
@endpush
@endonce
