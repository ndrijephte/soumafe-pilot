{{-- Styles communs des écrans d'administration : barre d'outils, tableau sobre, fenêtres de formulaire interactives --}}
<style>
.ad-barre{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.ad-barre .compte{font-size:12.5px;color:var(--gris)}
.ad-barre .espace{flex:1}
.ad-recherche{display:flex;align-items:center;gap:6px;background:#fff;border:1px solid var(--bord);border-radius:9px;padding:0 10px;color:var(--gris)}
.ad-recherche input{border:none;outline:none;padding:7px 0;font-size:12.5px;width:200px;background:none;font-family:inherit}
.ad-filtres{display:flex;gap:4px}
.ad-filtres button{background:#fff;border:1px solid var(--bord);border-radius:8px;padding:5px 11px;font-size:12px;font-weight:600;color:#475569;cursor:pointer}
.ad-filtres button.actif{background:var(--accent);border-color:var(--accent);color:#fff}
.ad-panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:4px 16px 8px;overflow-x:auto}
.ad-table{width:100%;border-collapse:collapse;font-size:13px}
.ad-table th{text-align:left;font-size:10.5px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;color:var(--gris);padding:9px 8px;border-bottom:1px solid var(--bord);white-space:nowrap}
.ad-table td{padding:9px 8px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#1e293b}
.ad-table tr:last-child td{border-bottom:none}
.ad-table tbody tr:hover td{background:#f8fbff}
.ad-table .sec{display:block;font-size:11.5px;color:var(--gris);margin-top:1px}
.ad-table .n{text-align:right;white-space:nowrap}
.ad-table .act{text-align:right;white-space:nowrap;width:1%}
.ad-table tr.inactif td{color:var(--gris)}
.etat{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#334155;white-space:nowrap}
.etat:before{content:"";width:7px;height:7px;border-radius:50%;background:#94a3b8;flex:none}
.etat.ok:before{background:var(--ok)}.etat.warn:before{background:#d97706}.etat.ko:before{background:var(--ko)}.etat.bleu:before{background:var(--accent)}
.etat.mut{color:var(--gris);font-weight:400}
.ad-marque{display:inline-block;font-size:10.5px;font-weight:600;color:var(--accent);background:#e6f0fa;border-radius:6px;padding:1px 7px;margin-right:4px;white-space:nowrap}
.ib{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;border:none;background:none;cursor:pointer;color:var(--accent);text-decoration:none;vertical-align:middle}
.ib:hover{background:#e6f0fa}
.ib.ko{color:var(--ko)}.ib.ko:hover{background:#fee2e2}
.ib.ok{color:var(--ok)}.ib.ok:hover{background:#dcfce7}
.ib.warn{color:#b45309}.ib.warn:hover{background:#fef3c7}
.ib:disabled{color:#cbd5e1;cursor:not-allowed;background:none}
.ib svg{width:16px;height:16px}
.btn-p{background:var(--accent);color:#fff;border:none;padding:8px 15px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.btn-s{background:#fff;color:var(--accent);border:1px solid var(--accent);padding:7px 14px;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.ad-alerte{border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px}
.ad-alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.ad-alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.ad-vide{text-align:center;color:var(--gris);font-size:13px;padding:22px}
/* Fenêtres de formulaire */
.fm-dlg{width:600px;max-width:94vw;border:none;border-radius:14px;padding:22px 24px;box-shadow:0 8px 30px rgba(0,0,0,.18)}
.fm-dlg::backdrop{background:rgba(22,56,92,.35)}
.fm-tete{display:flex;gap:12px;align-items:flex-start;margin-bottom:4px}
.fm-ic{flex:none;width:36px;height:36px;border-radius:10px;background:#e6f0fa;color:var(--accent);display:flex;align-items:center;justify-content:center}
.fm-dlg h3{color:var(--bleu);font-size:16px;margin:2px 0}
.fm-tete p{font-size:12px;color:var(--gris);margin:0;line-height:1.45}
.fm-lab{display:block;font-size:12px;color:#475569;font-weight:600;margin:14px 0 6px;text-transform:none}
.fm-lab .fac{font-weight:400;color:#94a3b8;margin-left:4px}
.fm-champ{width:100%;padding:8px 11px;border:1px solid var(--bord);border-radius:9px;font-size:13px;font-family:inherit;background:#fff;color:#1e293b;transition:border-color .15s,box-shadow .15s}
.fm-champ:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.fm-ligne2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fm-ligne3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px}
.fm-aide{font-size:11.5px;color:var(--gris);margin:4px 0 0;line-height:1.4}
.fm-resume{font-size:12.5px;color:#334155;margin:10px 0 0;padding:8px 11px;background:#f6f9fc;border-radius:9px}
.fm-resume:empty{display:none}
.fm-resume b{color:var(--bleu)}
.fm-section{margin-top:18px;padding-top:4px;border-top:1px solid #eef2f7}
.fm-section>h4{font-size:12.5px;color:var(--bleu);margin:10px 0 0}
.fm-pied{display:flex;gap:8px;justify-content:flex-end;margin-top:20px}
/* Interrupteur avec explication */
.fm-option{display:flex;gap:12px;align-items:flex-start;padding:10px 12px;border:1px solid var(--bord);border-radius:10px;margin-top:8px;cursor:pointer;transition:border-color .15s,background .15s}
.fm-option:hover{border-color:#9fbfe0}
.fm-option.on{border-color:#9fbfe0;background:#f6faff}
.fm-option.off{opacity:.55;cursor:not-allowed}
.fm-option b{display:block;font-size:13px;color:#1e293b;font-weight:600}
.fm-option span{display:block;font-size:11.5px;color:var(--gris);margin-top:2px;line-height:1.4}
.inter{position:relative;flex:none;width:34px;height:19px;margin-top:1px}
.inter input{opacity:0;width:0;height:0;position:absolute}
.inter i{position:absolute;inset:0;background:#cbd5e1;border-radius:19px;transition:background .15s}
.inter i:before{content:"";position:absolute;width:15px;height:15px;left:2px;top:2px;background:#fff;border-radius:50%;transition:transform .15s}
.inter input:checked+i{background:var(--accent)}
.inter input:checked+i:before{transform:translateX(15px)}
@media(max-width:600px){.fm-ligne2,.fm-ligne3{grid-template-columns:1fr}.ad-recherche input{width:130px}}
</style>
