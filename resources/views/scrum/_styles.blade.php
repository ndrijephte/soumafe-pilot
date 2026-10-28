{{-- Styles communs du module Scrum (boutons, alertes, tableaux, indicateurs) --}}
@once
@push('styles')
<style>
.sc-tete{display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;margin-bottom:12px}
.sc-tete .meta{font-size:12.5px;color:var(--gris);margin-top:2px}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-family:inherit}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:4px 10px;font-size:11.5px}
.btn.ko{background:#fff;color:var(--ko);border:1px solid #fecaca}
.btn.vert{background:#166534}
.btn:disabled{opacity:.5;cursor:not-allowed}
.alerte{border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.alerte.info{background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af}
.pill{display:inline-block;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
.p-ok{background:#dcfce7;color:#166534}.p-cours{background:#dbeafe;color:#1d4ed8}.p-ko{background:#fee2e2;color:#991b1b}.p-attente{background:#f1f5f9;color:#475569}.p-warn{background:#fef3c7;color:#92400e}
.champ label{display:block;font-size:11px;font-weight:700;color:var(--gris);text-transform:uppercase;margin-bottom:3px}
.champ input,.champ select,.champ textarea,.sc-tbl textarea,.sc-tbl input,.sc-tbl select{width:100%;box-sizing:border-box;padding:6px 9px;border:1px solid var(--bord);border-radius:7px;font-size:12.5px;font-family:inherit;background:#fff}
.sc-tbl input,.sc-tbl select{height:34px}
.sc-tbl textarea{resize:vertical;min-height:54px;line-height:1.45;display:block}
.champ textarea:focus,.sc-tbl textarea:focus,.champ input:focus,.champ select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
/* Modèle : tableau « Liste de présence » du Pointage */
.panneau{background:var(--carte,#fff);border:1px solid var(--bord);border-radius:12px;padding:16px;margin-bottom:16px;overflow-x:auto}
.panneau h3{font-size:14px;color:var(--bleu);margin:0 0 10px;display:flex;align-items:center;gap:6px}
.panneau h3 .h3-info{margin-left:auto;font-size:11.5px;font-weight:400;color:var(--gris)}
.sc-tbl{width:100%;border-collapse:collapse;font-size:12.5px;table-layout:fixed}
.sc-tbl th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px;vertical-align:middle;line-height:1.3}
.sc-tbl td{padding:7px 10px;border-bottom:1px solid var(--bord);vertical-align:middle;color:#1e293b;overflow-wrap:anywhere}
.sc-tbl th.ctr,.sc-tbl td.ctr{text-align:center}
.sc-tbl tr.story td:first-child{white-space:nowrap}
.sc-tbl tbody tr:hover td{background:#f8fafc}
.sc-tbl .lecture{white-space:pre-line;color:#1e293b}
.sc-tbl td.nom{font-weight:600;color:var(--bleu)}
.sc-tbl td.nom small{display:block;font-weight:400;color:var(--gris);font-size:11px}
.sc-tbl tr.module td{background:var(--fond);font-weight:600;color:var(--bleu);font-size:11px;text-transform:uppercase;letter-spacing:.3px}
.sc-tbl tr.story td{font-weight:600;color:var(--bleu)}
.sc-tbl td>.rd,.sc-tbl td>.rd-lecture{margin:0}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;display:inline-block;white-space:nowrap}
.b-ok{background:#dcfce7;color:var(--ok)}.b-tot{background:#e8f5e9;color:#2e7d32}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}.b-gris{background:#e2e8f0;color:#475569}
.indic{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:14px}
.indic div{background:#fff;border:1px solid var(--bord);border-radius:10px;padding:10px 12px}
.indic b{display:block;font-size:20px;color:var(--bleu);line-height:1.2}
.indic span{font-size:11.5px;color:var(--gris)}
.indic .alerte-i b{color:var(--ko)}
.barre-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:14px}
.scc{display:inline-block;min-width:26px;text-align:center;padding:2px 5px;border-radius:6px;font-size:11px;font-weight:700}
.scc.sc-d{background:#e0f2fe;color:#0369a1}.scc.sc-e{background:#dbeafe;color:#1d4ed8}.scc.sc-t{background:#dcfce7;color:#166534}.scc.sc-tt{background:#166534;color:#fff}.scc.sc-er{background:#fee2e2;color:#991b1b}.scc.sc-bloque{background:#fee2e2;color:#991b1b}.scc.sc-autre{background:#f1f5f9;color:#64748b}
@media(max-width:800px){.sc-tbl{display:block;overflow-x:auto}}
</style>
@endpush
@endonce
