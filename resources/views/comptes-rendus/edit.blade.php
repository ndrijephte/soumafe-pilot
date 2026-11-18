@extends('layouts.app')
@section('sousTitre', 'Comptes rendus')
@section('titre', 'Rédiger - ' . $cr->titre)
@section('contenu')
<style>
.cr-edit{max-width:920px;margin:0 auto}
.cr-edit.demo{max-width:1100px}
.fg .row-reu{display:grid;grid-template-columns:1fr 1fr 2fr;gap:10px}
@media(max-width:600px){.fg .row-reu{grid-template-columns:1fr}}
.cr-edit h2{font-size:1.3rem;font-weight:700;color:var(--bleu,#1F4E79);margin-bottom:6px}
.cr-edit .sub{font-size:.85rem;color:#64748b;margin-bottom:20px}
/* Sections pliables */
.section{background:#fff;border-radius:12px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.section-head{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;cursor:pointer;user-select:none;border-bottom:2px solid #DDEAF5;transition:background .15s;border-radius:12px 12px 0 0}
.section-head:hover{background:#FAFCFF}
.section-head h3{font-size:1rem;font-weight:700;color:var(--bleu,#1F4E79);margin:0}
.section-head .sect-status{display:flex;align-items:center;gap:8px}
.sect-check{width:20px;height:20px;border-radius:50%;border:2px solid #d1d5db;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#fff;transition:all .15s}
.sect-check.done{background:var(--ok,#16a34a);border-color:var(--ok,#16a34a)}
.sect-toggle{font-size:.75rem;color:#94a3b8;transition:transform .2s}
.section-body{padding:20px 24px;transition:max-height .3s ease}
.section.collapsed .section-body{display:none}
.section.collapsed .sect-toggle{transform:rotate(-90deg)}
.section.validated .section-head{background:#F0FDF4}
/* Tables */
.tbl{width:100%;border-collapse:collapse;font-size:.85rem}
.tbl th{background:var(--fond,#f1f5f9);color:#475569;padding:8px 10px;text-align:left;font-weight:600;font-size:.7rem;text-transform:uppercase;letter-spacing:.3px;border-bottom:1px solid #e2e8f0}
.tbl tbody tr:hover td{background:#f8fafc}
.tbl td{padding:8px 10px;border-bottom:1px solid #e5e7eb}
.tbl input[type=text],.tbl select{width:100%;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.82rem}
.chk{width:18px;height:18px;cursor:pointer;accent-color:var(--bleu,#1F4E79)}
/* Fields */
.fg{margin-bottom:14px}
.fg label{font-size:.85rem;font-weight:600;color:#475569;display:block;margin-bottom:4px}
.fg input,.fg select{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem}
.fg .row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
/* Rich editor */
.rich-toolbar{display:flex;gap:2px;padding:4px 6px;background:#f8fafc;border:1px solid #d1d5db;border-bottom:none;border-radius:8px 8px 0 0}
.rich-toolbar button{background:none;border:1px solid transparent;border-radius:4px;padding:4px 8px;cursor:pointer;font-size:.8rem;color:#475569;font-weight:600;transition:all .1s}
.rich-toolbar button:hover{background:#e2e8f0;border-color:#d1d5db}
.lh-wrap{position:relative;display:inline-block}
.lh-menu{display:none;position:fixed;z-index:10000;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.12);padding:3px;flex-direction:column;min-width:80px}
.lh-menu.open{display:flex}
.lh-menu button{text-align:left}
.rich-toolbar button.active{background:var(--accent,#2E74B5);color:#fff}
.rich-editor{border:1px solid #d1d5db;border-radius:0 0 8px 8px;padding:10px 14px;min-height:80px;font-size:.9rem;color:var(--bleu,#1F4E79);line-height:1.6;outline:none;text-align:justify}
.rich-editor:focus{border-color:var(--bleu,#1F4E79);box-shadow:0 0 0 3px rgba(31,78,121,.1)}
.rich-editor ul,.rich-editor ol{margin:4px 0 4px 20px}
.rich-editor *{font-family:inherit!important;font-size:inherit!important;color:inherit!important;background:none!important;line-height:inherit!important}
.rich-editor a{text-decoration:none!important;pointer-events:none}
/* Couleur du texte (palette de l'éditeur) */
.coul-a{display:inline-block;font-weight:700;border-bottom:3px solid #2E74B5;line-height:1;padding:0 1px}
.lh-menu.coul-menu{grid-template-columns:repeat(4,28px);gap:6px;padding:10px;border-radius:10px;box-shadow:0 10px 28px rgba(15,23,42,.16)}
.lh-menu.coul-menu.open{display:grid}
.lh-menu .coul-pastille{width:28px;height:28px;padding:0;border-radius:7px;border:1px solid rgba(0,0,0,.1);background:var(--c)!important;transition:transform .1s}
.lh-menu .coul-pastille:hover{transform:scale(1.12);box-shadow:0 0 0 2px #fff,0 0 0 4px #93c5fd}
.lh-menu .coul-auto{grid-column:1/-1;font-size:.74rem;text-align:center!important;margin-top:2px;border:1px solid #e2e8f0!important;border-radius:6px}
@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex]).rich-editor .tc-{{ $n }}{color:#{{ $hex }}!important}
@endforeach
.rich-editor .lh-10{line-height:1!important}
.rich-editor .lh-115{line-height:1.15!important}
.rich-editor .lh-15{line-height:1.5!important}
.rich-editor .lh-20{line-height:2!important}
.rich-editor .lh-25{line-height:2.5!important}
.rich-editor font[size="1"]{font-size:.75em!important}
.rich-editor font[size="2"]{font-size:.833em!important}
.rich-editor font[size="4"]{font-size:1.167em!important}
.rich-editor font[size="5"]{font-size:1.333em!important}
.rich-editor font[size="6"]{font-size:1.667em!important}
.rich-editor font[size="7"]{font-size:2em!important}
.rich-editor ol.hier,.rich-editor ol.hier ol{list-style:none!important;counter-reset:hier;padding-left:0!important;margin-left:0!important}
.rich-editor ol.hier ol{margin-left:28px!important}
.rich-editor ol.hier li{counter-increment:hier;display:block}
.rich-editor ol.hier li::before{content:var(--num-prefix,"") counters(hier,".") ". ";font-weight:700;margin-right:4px}
.rich-editor blockquote{margin:0 0 0 28px!important;padding:0!important;border:none!important}
/* Buttons */
.btn-add{background:#f1f5f9;color:#475569;border:1px dashed #94a3b8;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.82rem}
.btn-add:hover{background:#e2e8f0}
.btn-rm{background:none;border:none;color:#dc2626;cursor:pointer;font-size:1.1rem;padding:2px 6px}
.btn-validate{display:inline-flex;align-items:center;gap:5px;padding:6px 16px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;border:1px solid var(--bleu,#1F4E79);color:var(--bleu,#1F4E79);background:#fff;transition:all .15s}
.btn-validate:hover{background:var(--bleu,#1F4E79);color:#fff}
.btn-validate.validated{background:var(--ok,#16a34a);border-color:var(--ok,#16a34a);color:#fff}
/* ODJ */
.odj-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:18px 20px 18px 48px;margin-bottom:12px;position:relative;transition:box-shadow .15s}
.odj-item:hover{box-shadow:0 2px 8px rgba(31,78,121,.08)}
.odj-item .num{position:absolute;left:14px;top:20px;background:var(--accent,#2E74B5);color:#fff;font-size:.78rem;font-weight:700;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.odj-item textarea{padding:10px 14px;font-size:.92rem;border-radius:8px;border:1px solid #d1d5db;font-family:inherit}
/* Bottom bar */
.cr-bottom{position:sticky;bottom:0;z-index:20;display:flex;gap:12px;justify-content:flex-end;align-items:center;flex-wrap:wrap;margin-top:24px;padding:12px 16px;background:rgba(255,255,255,.96);backdrop-filter:blur(4px);border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 -6px 18px rgba(15,23,42,.06)}
/* Sommaire des sections */
.cr-sommaire{position:sticky;top:0;z-index:15;display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:0 0 14px;padding:8px 10px;background:rgba(244,247,251,.96);backdrop-filter:blur(4px);border:1px solid #e2e8f0;border-radius:12px}
.cr-sommaire a{display:inline-flex;align-items:center;gap:6px;padding:5px 11px;border-radius:999px;font-size:.78rem;font-weight:600;color:#475569;text-decoration:none;background:#fff;border:1px solid #e2e8f0}
.cr-sommaire a:hover{border-color:#93c5fd;color:#1f4e79}
.cr-sommaire a i{width:7px;height:7px;border-radius:50%;background:#cbd5e1}
.cr-sommaire a.ok i{background:#16a34a}
#cr-form .section{scroll-margin-top:64px}
/* Boutons : sobres, une icône qui dit l'action ; un seul bouton plein, l'action principale */
.cr-bottom .bt{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:38px;padding:0 16px;border-radius:10px;font-family:inherit;font-size:.82rem;font-weight:600;line-height:1;text-decoration:none;cursor:pointer;border:1px solid transparent;transition:background .15s,border-color .15s,color .15s,box-shadow .15s;white-space:nowrap}
.cr-bottom .bt svg{width:16px;height:16px;flex:none}
.cr-bottom .bt-ghost{background:transparent;color:#64748b}
.cr-bottom .bt-ghost:hover{background:#f1f5f9;color:#1e293b}
.cr-bottom .bt-sec{background:#fff;color:#1f4e79;border-color:#cbd5e1}
.cr-bottom .bt-sec:hover{border-color:#2e74b5;background:#f5f9fe}
.cr-bottom .bt-warn{background:#fff;color:#b45309;border-color:#fcd9a8}
.cr-bottom .bt-warn:hover{background:#fffbeb;border-color:#f59e0b}
.cr-bottom .bt-prim{background:#2e74b5;color:#fff;box-shadow:0 1px 2px rgba(31,78,121,.25)}
.cr-bottom .bt-prim:hover{background:#245f96;box-shadow:0 3px 10px rgba(46,116,181,.28)}
.cr-bottom .bt-ok{background:#15803d;color:#fff;box-shadow:0 1px 2px rgba(21,128,61,.25)}
.cr-bottom .bt-ok:hover{background:#166534;box-shadow:0 3px 10px rgba(21,128,61,.28)}
.cr-bottom .bt:focus-visible{outline:2px solid #93c5fd;outline-offset:2px}
.cr-bottom .espace{flex:1}
@media(max-width:900px){.tbl{display:block;overflow-x:auto}.cr-bottom{justify-content:stretch}.cr-bottom .bt{flex:1}.cr-bottom .espace{display:none}}
@media(max-width:600px){.cr-edit{margin:0}.section-body{padding:12px 10px}.fg .row2{grid-template-columns:1fr}.odj-item{padding:14px 12px 14px 42px}.rich-toolbar{flex-wrap:wrap}}
.tbl .num-col{width:45px;text-align:center;color:#94a3b8;font-weight:600}
/* Multi-select responsables */
.ms-wrap{position:relative}
.ms-btn{width:100%;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.82rem;background:#fff;cursor:pointer;text-align:left;min-height:30px;display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.ms-btn:hover{border-color:var(--bleu,#1F4E79)}
.ms-drop{display:none;position:fixed;background:#fff;border:1px solid #d1d5db;border-radius:6px;max-height:200px;overflow-y:auto;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.ms-drop.open{display:block}
.ms-drop label{display:flex;align-items:center;gap:6px;padding:5px 10px;font-size:.82rem;cursor:pointer;white-space:nowrap}
.ms-drop label:hover{background:#f1f5f9}
.ms-tag{background:#dbeafe;color:#1d4ed8;padding:1px 7px;border-radius:4px;font-size:.75rem;white-space:nowrap}
.ms-placeholder{color:#94a3b8;font-size:.82rem}
/* ===== Compte rendu de réunion : même présentation que le rapport de review ===== */
.cr-edit.reu .section{border-radius:14px;box-shadow:0 1px 3px rgba(15,23,42,.06),0 6px 20px rgba(15,23,42,.04)}
.cr-edit.reu .section-head{background:#EAF2FB;border-bottom:2px solid var(--accent,#2E74B5);border-radius:14px 14px 0 0;padding:16px 24px}
.cr-edit.reu .section-head:hover{background:#E3EDF9}
.cr-edit.reu .section.collapsed .section-head{border-radius:14px}
.cr-edit.reu .section.validated .section-head{background:#EAF2FB}
.cr-edit.reu .section-head h3{display:flex;align-items:center;gap:12px;font-size:1.15rem;color:var(--bleu,#1F4E79)}
.cr-edit.reu .section-head h3[data-num]::before{content:attr(data-num);flex:none;width:30px;height:30px;border-radius:50%;background:var(--accent,#2E74B5);color:#fff;font-weight:800;font-size:.95rem;display:flex;align-items:center;justify-content:center}
.cr-edit.reu .btn-validate{border-color:var(--accent,#2E74B5);color:var(--accent,#2E74B5);background:rgba(255,255,255,.85)}
.cr-edit.reu .btn-validate:hover{background:var(--accent,#2E74B5);color:#fff}
.cr-edit.reu .btn-validate.validated{background:var(--ok,#16a34a);border-color:var(--ok,#16a34a);color:#fff}
.cr-edit.reu .btn-add{background:none;border:none;color:var(--accent,#2E74B5);font-weight:600;padding:6px 4px}
.cr-edit.reu .btn-add:hover{text-decoration:underline;background:none}
.cr-edit.reu .derou-item > label,.cr-edit.reu .section-body > label,.cr-edit.reu .section-body > .fg > label{font-size:1rem;font-weight:700;color:#1e3a5f;padding-bottom:5px;margin-bottom:8px;border-bottom:1px solid #9FBFE0}
.cr-edit.reu .tbl{border-collapse:separate;border-spacing:0;border:1px solid #d5e2f0;border-radius:10px;overflow:hidden}
.cr-edit.reu .tbl th{background:linear-gradient(180deg,#EAF2FB,#DCE9F7);color:#1F4E79;font-weight:700;border-bottom:2px solid #9FBFE0;letter-spacing:.4px}
.cr-edit.reu .tbl th.num-col{color:#1F4E79}
.cr-edit.reu .tbl td{border-bottom:1px solid #edf2f8}
.cr-edit.reu .tbl tbody tr:last-child td{border-bottom:none}
.cr-edit.reu .tbl tbody tr:nth-child(even) td{background:#F7FAFD}
.cr-edit.reu .tbl tbody tr:hover td{background:#EEF4FB}
.cr-edit.reu .tbl input[type=text],.cr-edit.reu .tbl input[type=date],.cr-edit.reu .tbl .ms-btn{border-color:transparent;background:transparent}
.cr-edit.reu .tbl input:hover,.cr-edit.reu .tbl .ms-btn:hover{border-color:#e2e8f0;background:#fff}
.cr-edit.reu .tbl input:focus{outline:none;border-color:#93c5fd;background:#fff}
.cr-edit.reu .odj-item{background:#fff;border:1px solid #d5e2f0;border-radius:10px;padding:12px 16px 12px 52px}
.cr-edit.reu .odj-item .num{top:50%;transform:translateY(-50%);left:14px;border-radius:9px}
.cr-edit.reu .odj-item textarea{border-color:transparent!important;background:transparent;padding:6px 8px!important;resize:none!important}
.cr-edit.reu .odj-item textarea:hover{border-color:#e2e8f0!important;background:#fff}
.cr-edit.reu .odj-item textarea:focus{outline:none;border-color:#93c5fd!important;background:#fff}
.cr-edit.reu .odj-item .btn-rm{color:#cbd5e1}
.cr-edit.reu .odj-item:hover .btn-rm{color:#dc2626}
.cr-edit.reu .syno-pied > span{display:inline-flex;align-items:center;gap:4px;margin-right:8px}
/* ===== BLOC EDITOR (Demo WYSIWYG) ===== */
.be-module{background:#fff;border-radius:12px;margin-bottom:20px;box-shadow:0 1px 6px rgba(0,0,0,.08);border:1px solid #e2e8f0;overflow:hidden}
.be-mod-head{display:flex;align-items:stretch;gap:0;background:linear-gradient(135deg,#1F4E79 0%,#2E74B5 100%);color:#fff;position:relative}
.be-mod-head .be-handle{display:flex;align-items:center;padding:0 8px;cursor:grab;color:rgba(255,255,255,.5);font-size:1.1rem}
.be-mod-fields{flex:1;padding:10px 14px;display:flex;flex-direction:column;gap:4px}
.be-mod-fields input{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;padding:5px 10px;border-radius:6px;font-size:.88rem;width:100%}
.be-mod-fields input::placeholder{color:rgba(255,255,255,.55)}
.be-mod-fields input.be-mod-titre{font-weight:700;font-size:1rem}
.be-mod-actions{display:flex;align-items:center;gap:2px;padding:0 10px}
.be-mod-actions button{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:4px;cursor:pointer;padding:3px 7px;font-size:.78rem;transition:all .1s}
.be-mod-actions button:hover{background:rgba(255,255,255,.3)}
.be-mod-body{padding:14px 18px}
.be-section{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:12px;overflow:hidden}
.be-sec-head{display:flex;align-items:center;gap:6px;padding:8px 14px;background:#EFF4FB;border-bottom:1px solid #DDEAF5}
.be-sec-head .be-handle{cursor:grab;color:#94a3b8;font-size:.9rem;padding:0 4px}
.be-sec-head input{border:1px solid #d1d5db;padding:5px 10px;border-radius:6px;font-size:.85rem;font-weight:600;color:var(--bleu,#1F4E79);flex:1;background:#fff}
.be-sec-actions{display:flex;gap:2px;margin-left:auto}
.be-sec-actions button{background:none;border:none;cursor:pointer;padding:2px 6px;color:#94a3b8;font-size:.8rem;border-radius:4px}
.be-sec-actions button:hover{background:#dbeafe;color:#1F4E79}
.be-blocs{padding:10px 14px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-start}
.be-bloc{background:#fff;border:1px solid #e2e8f0;border-radius:8px;position:relative;transition:box-shadow .15s;width:100%;min-width:0}
.be-bloc.demi{width:calc(50% - 4px)}
.be-bloc[data-couleur]{border:1px solid color-mix(in srgb,var(--bc-bd) 35%,#fff);border-radius:8px}
.be-bloc[data-couleur] .be-bloc-body{background:var(--bc-bg)}
.be-bloc.demi .be-larg{color:var(--bleu,#1F4E79);background:#dbeafe}
.be-coul-wrap{position:relative;display:inline-flex}
.be-pastille{display:inline-block;width:11px;height:11px;border-radius:50%;background:var(--bc-bd,#cbd5e1);border:1px solid rgba(0,0,0,.12);vertical-align:-1px}
.be-coul-menu{display:none;position:absolute;right:0;top:calc(100% + 4px);z-index:40;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 20px rgba(15,23,42,.14);padding:6px;gap:5px}
.be-coul-menu.ouvert{display:flex}
.be-coul-menu button{width:20px;height:20px;border-radius:50%;border:2px solid;padding:0;cursor:pointer}
.be-coul-menu button.aucune{border:1px solid #cbd5e1;background:#fff;color:#94a3b8;font-size:.8rem;line-height:1}
.be-filtre-module{margin-left:8px;border:1px solid #dbe3ee;border-radius:6px;padding:2px 6px;font-size:.74rem;color:#1f4e79;background:#fff;max-width:240px}
.be-sstitre{display:flex;align-items:center;gap:8px}
.be-sstitre .be-num{font-weight:700;color:var(--accent,#2E74B5);font-size:.9rem;white-space:nowrap}
.be-sstitre-txt{flex:1;border:none;border-bottom:1px solid #dbe3ee;font-size:.95rem;font-weight:700;color:#1F4E79;padding:4px 2px;background:transparent}
.be-sstitre-txt:focus{outline:none;border-bottom-color:#2e74b5}
.be-com{border-radius:8px}
.be-com-tete{font-size:.76rem;color:#64748b;margin-bottom:5px}
.be-com-tete strong{color:#334155}
.be-com-txt{width:100%;border:1px solid #e2e8f0;border-radius:6px;padding:7px 10px;font-size:.85rem;font-family:inherit;resize:vertical;background:rgba(255,255,255,.8)}
@media(max-width:800px){.be-bloc.demi{width:100%}}
/* ===== Rapport de review : document épuré ===== */
.cr-edit.demo .be-module{background:#fff;border:none;border-radius:14px;box-shadow:0 1px 3px rgba(15,23,42,.06),0 6px 20px rgba(15,23,42,.04);margin-bottom:22px;overflow:visible}
.cr-edit.demo .be-mod-head{background:#EAF2FB;border-bottom:2px solid var(--accent,#2E74B5);color:inherit;align-items:flex-start;padding:18px 26px 12px;border-radius:14px 14px 0 0}
.cr-edit.demo .be-mod-head .be-handle{color:#cbd5e1;padding:8px 6px 0 0;font-size:.85rem;letter-spacing:-3px;opacity:0;transition:opacity .15s}
.cr-edit.demo .be-module:hover > .be-mod-head .be-handle{opacity:1}
.cr-edit.demo .be-num-mod{flex:none;width:34px;height:34px;border-radius:50%;background:var(--accent,#2E74B5);color:#fff;font-weight:800;font-size:1.05rem;display:flex;align-items:center;justify-content:center;margin:2px 14px 0 0}
.cr-edit.demo .be-mod-fields{padding:0;gap:2px}
.cr-edit.demo .be-mod-fields input{background:transparent;border:none;border-radius:0;color:var(--bleu,#1F4E79);padding:2px 0}
.cr-edit.demo .be-mod-fields input.be-mod-titre{font-size:1.4rem;font-weight:800;letter-spacing:-.2px}
.cr-edit.demo .be-mod-fields input.be-mod-obj{font-size:.86rem;font-style:italic;color:#64748b;font-weight:400}
.cr-edit.demo .be-mod-fields input::placeholder{color:#b6c2d1}
.cr-edit.demo .be-mod-fields input:focus{outline:none;box-shadow:inset 0 -2px 0 #93c5fd}
.cr-edit.demo .be-mod-actions{padding:6px 0 0;opacity:0;transition:opacity .15s}
.cr-edit.demo .be-module:hover > .be-mod-head .be-mod-actions{opacity:1}
.cr-edit.demo .be-mod-actions button{background:#f1f5f9;border:none;color:#64748b;border-radius:6px}
.cr-edit.demo .be-mod-actions button:hover{background:#e2e8f0;color:var(--bleu,#1F4E79)}
.cr-edit.demo .be-mod-body{padding:12px 26px 18px}
.cr-edit.demo .be-section{background:none;border:none;border-radius:0;margin:6px 0 18px;overflow:visible}
.cr-edit.demo .be-sec-head{background:none;border:none;border-bottom:1px solid #9FBFE0;padding:12px 0 5px;gap:8px}
.cr-edit.demo .be-sec-head .be-handle{opacity:0;transition:opacity .15s;letter-spacing:-3px;font-size:.8rem;color:#cbd5e1;padding:0}
.cr-edit.demo .be-section:hover > .be-sec-head .be-handle,.cr-edit.demo .be-section:hover > .be-sec-head .be-sec-actions{opacity:1}
.cr-edit.demo .be-num-sec{font-weight:800;color:var(--accent,#2E74B5);font-size:1rem;white-space:nowrap}
.cr-edit.demo .be-sec-head input{border:none;background:transparent;padding:2px 0;border-radius:0;font-size:1.08rem;font-weight:700;color:#1e3a5f}
.cr-edit.demo .be-sec-head input:focus{outline:none;box-shadow:inset 0 -2px 0 #93c5fd}
.cr-edit.demo .be-sec-actions{opacity:0;transition:opacity .15s}
.cr-edit.demo .be-blocs{padding:8px 0 0;gap:10px}
.cr-edit.demo .be-bloc{border:none;background:transparent;border-radius:0 8px 8px 0}
.cr-edit.demo .be-bloc:hover{box-shadow:none;background:#fafcff}
.cr-edit.demo .be-bloc[data-couleur]{border:1px solid color-mix(in srgb,var(--bc-bd) 35%,#fff);border-radius:8px}
.cr-edit.demo .be-bloc[data-couleur] .be-bloc-body{background:var(--bc-bg);border-radius:8px}
.cr-edit.demo .be-bloc-head{position:absolute;top:-22px;right:8px;z-index:6;padding:3px 6px;background:#fff;border:none;border-radius:8px;box-shadow:0 2px 10px rgba(15,23,42,.14);opacity:0;pointer-events:none;transition:opacity .12s}
.cr-edit.demo .be-bloc:hover > .be-bloc-head,.cr-edit.demo .be-bloc:focus-within > .be-bloc-head{opacity:1;pointer-events:auto}
.cr-edit.demo .be-bloc-head .be-type-lbl{font-size:.68rem;color:#94a3b8}
.cr-edit.demo .be-bloc-head .be-handle{letter-spacing:-3px;font-size:.78rem}
.cr-edit.demo .be-bloc-body{padding:8px 10px}
.cr-edit.demo .be-bloc-body .rich-editor{border:none;border-radius:6px;padding:6px 4px;min-height:34px;background:transparent}
.cr-edit.demo .be-bloc-body .rich-editor:focus{background:#fff;box-shadow:0 0 0 1px #dbe3ee}
.cr-edit.demo .be-bloc-body .rich-editor:empty::before{content:'Écrire ici…';color:#b6c2d1}
.cr-edit.demo .be-bloc .rich-toolbar{display:none;border:none;background:#f1f5f9;border-radius:8px;margin-bottom:4px}
.cr-edit.demo .be-bloc:focus-within .rich-toolbar{display:flex}
.cr-edit.demo .be-sstitre-txt{border:none;font-size:.98rem}
.cr-edit.demo .be-sstitre .be-num{color:var(--accent,#2E74B5)}
.cr-edit.demo .be-legende{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;font-size:.74rem;color:#64748b}
.cr-edit.demo .be-legende-lbl{font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#1F4E79}
.cr-edit.demo .be-filtre-module{margin:0;border:none;background:#eef4fb;border-radius:12px;padding:3px 10px;font-weight:600}
.cr-edit.demo .be-actualiser{margin:0 0 0 auto;border:none;background:none;color:#2e74b5;padding:2px 4px}
.cr-edit.demo .be-actualiser:hover{background:#eef4fb}
.cr-edit.demo .be-tab{border-collapse:separate;border-spacing:0;border:1px solid #d5e2f0;border-radius:10px;overflow:hidden}
.cr-edit.demo .be-tab th{background:linear-gradient(180deg,#EAF2FB,#DCE9F7);color:#1F4E79;font-weight:700;border:none;border-bottom:2px solid #9FBFE0;letter-spacing:.4px}
.cr-edit.demo .be-tab td{border-bottom:1px solid #edf2f8}
.cr-edit.demo .be-tab tbody tr:last-child td{border-bottom:none}
.cr-edit.demo .be-tab tbody tr:nth-child(even) td{background:#F7FAFD}
.cr-edit.demo .be-tab tbody tr:hover td{background:#EEF4FB}
.cr-edit.demo .be-tab tr.be-grp td{background:#fff!important;padding:12px 10px 4px;font-weight:700;font-size:.82rem;color:#2E74B5;border-bottom:1px dotted #9FBFE0}
.cr-edit.demo .be-tab input,.cr-edit.demo .be-tab select,.cr-edit.demo .be-tab textarea{border-color:transparent;background:transparent}
.cr-edit.demo .be-tab input:hover,.cr-edit.demo .be-tab select:hover,.cr-edit.demo .be-tab textarea:hover{border-color:#e2e8f0;background:#fff}
.cr-edit.demo .be-tab input:focus,.cr-edit.demo .be-tab select:focus,.cr-edit.demo .be-tab textarea:focus{border-color:#93c5fd;background:#fff}
.cr-edit.demo .be-add-btn{border:none;background:none;color:#2e74b5;padding:4px 2px;margin-top:4px}
.cr-edit.demo .be-add-btn:hover{background:none;text-decoration:underline}
.cr-edit.demo .be-vide{padding:12px;color:#94a3b8;font-size:.82rem;text-align:center;background:#f8fafc;border-radius:8px}
.cr-edit.demo .be-ajout{position:relative;padding:4px 0 0}
.cr-edit.demo .be-ajout-btn{border:none;background:none;color:#94a3b8;font-size:.8rem;font-weight:600;cursor:pointer;padding:6px 8px;border-radius:8px}
.cr-edit.demo .be-ajout-btn:hover,.cr-edit.demo .be-section:hover .be-ajout-btn{color:#2e74b5;background:#f1f6fd}
.cr-edit.demo .be-ajout-menu{display:none;position:absolute;left:0;top:100%;z-index:30;background:#fff;border-radius:12px;box-shadow:0 12px 32px rgba(15,23,42,.18);padding:6px;width:420px;grid-template-columns:1fr 1fr;gap:2px}
.cr-edit.demo .be-ajout-menu.ouvert{display:grid}
.cr-edit.demo .be-ajout-menu button{display:flex;flex-direction:column;align-items:flex-start;text-align:left;border:none;background:none;padding:8px 10px;border-radius:8px;cursor:pointer}
.cr-edit.demo .be-ajout-menu button:hover{background:#f1f6fd}
.cr-edit.demo .be-ajout-menu b{font-size:.84rem;color:#1F4E79}
.cr-edit.demo .be-ajout-menu small{font-size:.72rem;color:#94a3b8}
.cr-edit.demo .be-add-sec{border:none;background:none;color:#94a3b8;text-align:left;padding:6px 0;width:auto}
.cr-edit.demo .be-add-sec:hover{color:#2e74b5;background:none}
.cr-edit.demo .be-add-mod{border:2px dashed #cbd5e1;background:transparent;color:#64748b}
.cr-edit.demo .be-add-mod:hover{border-color:var(--bleu,#1F4E79);background:#fff;color:var(--bleu,#1F4E79)}
/* Tableau synoptique */
.syno{background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(15,23,42,.06),0 6px 20px rgba(15,23,42,.04);padding:20px 24px 16px;margin-bottom:22px}
.syno-lib{font-size:.7rem;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#2e74b5}
.syno-titre{display:flex;align-items:center;gap:10px;margin:2px 0 14px}
.syno-doc{flex:1;border:none;background:transparent;font-size:var(--t-doc,1.35rem);font-weight:700;color:var(--bleu,#1F4E79);padding:2px 0;font-family:inherit}
.syno-doc:focus{outline:none;box-shadow:inset 0 -2px 0 #93c5fd}
.syno-tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:.86rem;border:1px solid #d5e2f0;border-radius:10px;overflow:hidden}
.syno-tbl th{width:150px;text-align:left;font-weight:600;color:#1F4E79;background:linear-gradient(90deg,#E3EEF9,#EEF4FB);padding:8px 12px;border:none;border-bottom:1px solid #e3ecf6;border-right:1px solid #d5e2f0;font-size:.8rem}
.syno-tbl td{padding:6px 12px;border:none;border-bottom:1px solid #edf2f8;color:#1e293b}
.syno-tbl tr:last-child th,.syno-tbl tr:last-child td{border-bottom:none}
.syno-tbl input,.syno-tbl select{border:1px solid transparent;background:transparent;border-radius:6px;padding:4px 6px;font:inherit;color:inherit;max-width:100%}
.syno-tbl input:hover,.syno-tbl select:hover{border-color:#e2e8f0;background:#fff}
.syno-tbl input:focus,.syno-tbl select:focus{outline:none;border-color:#93c5fd;background:#fff}
.syno-tbl input[name=lieu]{width:100%}
.syno-gris{color:#94a3b8;font-size:.8rem}
.syno-alerte{font-size:.76rem;color:#b45309;margin-top:3px}
.syno-ep{margin-top:8px;padding-top:8px;border-top:1px dashed #e2e8f0;font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:6px 14px;flex-wrap:wrap}
.syno-ep .ep-lib{font-weight:600;color:#1F4E79}
.syno-ep .ep-txt{display:inline-flex;align-items:center;gap:6px;flex:1 1 230px}
.syno-ep .ep-txt input{flex:1;min-width:0;border:1px solid #e2e8f0;border-radius:6px;padding:4px 8px;font-size:.78rem;color:#1e293b;font-family:inherit}
.syno-ep .ep-txt input:focus{outline:none;border-color:#93c5fd}
.syno-ep .ep-chk{display:inline-flex;align-items:center;gap:5px;cursor:pointer;white-space:nowrap}
.syno-ep .ep-chk input{accent-color:var(--accent,#2E74B5)}
.syno-pied{margin-top:10px;font-size:.78rem;color:#64748b;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
select.taille{border:1px solid #e2e8f0;background:#fff;border-radius:6px;padding:2px 4px;font-size:.72rem;font-weight:600;color:#64748b;cursor:pointer;flex:none}
select.taille:hover{border-color:#93c5fd;color:#1F4E79}
.cr-edit.demo .be-mod-fields{position:relative}
.cr-edit.demo .be-mod-fields select.taille{position:absolute;right:0;top:6px;opacity:0;transition:opacity .15s}
.cr-edit.demo .be-module:hover > .be-mod-head select.taille,.cr-edit.demo .be-section:hover > .be-sec-head select.taille,.cr-edit.demo select.taille:focus{opacity:1}
.cr-edit.demo .be-sec-head select.taille{order:9;opacity:0;transition:opacity .15s}
.cr-edit.demo .be-mod-fields input.be-mod-titre{font-weight:700;font-size:var(--t-mod,1.4rem);color:var(--c-mod,var(--bleu,#1F4E79))}
.cr-edit.demo .be-sec-head input{font-size:var(--t-sec,1.08rem);color:var(--c-sec,#1e3a5f)}
.cr-edit.demo .be-sstitre{border-bottom:1px dotted #9FBFE0;padding-bottom:4px}
.cr-edit.demo .be-reglages{display:inline-flex;align-items:center;gap:6px}
.cr-edit.demo .be-mod-fields .be-reglages{position:absolute;right:0;top:6px;opacity:0;transition:opacity .15s}
.cr-edit.demo .be-mod-fields .be-reglages select.taille{position:static;opacity:1}
.cr-edit.demo .be-sec-head .be-reglages{order:9;opacity:0;transition:opacity .15s}
.cr-edit.demo .be-sec-head .be-reglages select.taille{opacity:1}
.cr-edit.demo .be-module:hover > .be-mod-head .be-reglages,.cr-edit.demo .be-section:hover > .be-sec-head .be-reglages,.cr-edit.demo .be-reglages:focus-within{opacity:1}
.be-ctitre{position:relative;display:inline-flex}
.be-ctitre-btn{width:26px;height:24px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0}
.be-ctitre-btn span{width:12px;height:12px;border-radius:50%}
.be-ctitre-menu{display:none;position:absolute;right:0;top:calc(100% + 4px);z-index:40;background:#fff;border-radius:8px;box-shadow:0 8px 20px rgba(15,23,42,.16);padding:6px;gap:5px}
.be-ctitre-menu.ouvert{display:flex}
.be-ctitre-menu button{width:20px;height:20px;border-radius:50%;border:none;cursor:pointer;padding:0}
.be-ctitre-menu button.defaut{background:#f1f5f9;color:#64748b;font-size:.8rem}
.cr-edit.demo .be-mod-fields input::placeholder,.cr-edit.demo .be-sec-head input::placeholder,.cr-edit.demo .be-sstitre-txt::placeholder{font-weight:400;font-size:.9rem;color:#b6c2d1;font-style:normal}
@media(max-width:700px){.syno-tbl th{width:110px}.syno-tbl tr{display:grid;grid-template-columns:110px 1fr}}
.cr-edit.demo .be-tab .btn-rm{color:#cbd5e1;font-size:.9rem}
.cr-edit.demo .be-sign{border-bottom:1px dashed #cbd5e1}
.cr-edit.demo .be-tab tr:hover .btn-rm{color:#94a3b8}
.cr-edit.demo .be-tab .btn-rm:hover{color:#dc2626}
.cr-edit.demo .be-blocs{row-gap:18px}
.be-bloc:hover{box-shadow:0 2px 8px rgba(31,78,121,.08)}
.be-bloc-head{display:flex;align-items:center;gap:6px;padding:5px 10px;background:#fafbfc;border-bottom:1px solid #f1f5f9;border-radius:8px 8px 0 0;font-size:.76rem;color:#64748b}
.be-bloc-head .be-handle{cursor:grab;color:#b0b8c4;font-size:.9rem;padding:0 3px}
.be-glisse{opacity:.45;outline:2px dashed #93c5fd;outline-offset:2px}
.be-contrib{background:#eff6ff;border:1px solid #bfdbfe;color:#1e3a8a;border-radius:10px;padding:9px 14px;font-size:.84rem;margin-bottom:12px}
.be-actualiser{margin-left:auto;margin-right:6px;background:#fff;border:1px solid #bfdbfe;color:#1f4e79;border-radius:6px;font-size:.74rem;font-weight:600;padding:3px 9px;cursor:pointer}
.be-actualiser:hover{background:#eff6ff}
.be-tab-aide{font-size:.75rem;color:#64748b;margin-bottom:6px}
.be-tab input,.be-tab select,.be-tab textarea{width:100%;padding:4px 7px;border:1px solid #e2e8f0;border-radius:6px;font-size:.8rem;font-family:inherit;background:#fff}
.be-tab textarea{resize:none;overflow:hidden;min-height:30px;line-height:1.35;margin-top:3px}
.be-tab input:focus,.be-tab select:focus,.be-tab textarea:focus{outline:none;border-color:#93c5fd}
.be-tab textarea.be-tache-obs{resize:none;overflow:hidden;line-height:1.45;min-width:220px}
.be-bloc-head .be-type-lbl{font-weight:700;color:var(--bleu,#1F4E79);text-transform:uppercase;letter-spacing:.3px}
.be-bloc-bar{display:flex;gap:2px;margin-left:auto}
.be-bloc-bar button{background:none;border:none;cursor:pointer;padding:2px 5px;color:#94a3b8;font-size:.82rem;border-radius:4px}
.be-bloc-bar button:hover{background:#f1f5f9;color:#475569}
.be-bloc-body{padding:10px 12px}
.be-bloc-body .rich-editor{min-height:60px;font-size:.88rem}
/* Blocs référence */
.be-ref{border-radius:8px;padding:10px 14px}
.be-ref-label{display:flex;align-items:center;gap:6px;font-weight:700;font-size:.82rem;margin-bottom:6px}
.be-ref-label svg{flex-shrink:0}
.be-ref textarea,.be-ref .rich-editor{width:100%;border:1px solid rgba(0,0,0,.08);border-radius:6px;padding:8px 10px;font-size:.85rem;background:rgba(255,255,255,.7);min-height:48px;resize:vertical}
.be-ref select{border:1px solid rgba(0,0,0,.12);border-radius:5px;padding:4px 8px;font-size:.78rem;background:rgba(255,255,255,.8)}
/* Toolbars */
.be-add-bar{display:flex;gap:4px;flex-wrap:wrap;padding:6px 0}
.be-add-btn{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-size:.76rem;font-weight:600;cursor:pointer;border:1px dashed #94a3b8;background:#fff;color:#475569;transition:all .12s}
.be-add-btn:hover{background:#EFF4FB;border-color:var(--bleu,#1F4E79);color:var(--bleu,#1F4E79)}
.be-add-btn svg{width:13px;height:13px}
.be-add-sec{display:block;width:100%;padding:7px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;border:1px dashed #94a3b8;background:#f8fafc;color:#475569;text-align:center;transition:all .12s}
.be-add-sec:hover{background:#EFF4FB;border-color:var(--bleu,#1F4E79);color:var(--bleu,#1F4E79)}
.be-add-mod{display:block;width:100%;padding:10px;border-radius:10px;font-size:.88rem;font-weight:600;cursor:pointer;border:2px dashed var(--bleu,#1F4E79);background:#EFF4FB;color:var(--bleu,#1F4E79);text-align:center;transition:all .12s;margin-top:12px}
.be-add-mod:hover{background:var(--bleu,#1F4E79);color:#fff;border-style:solid}
/* Score bloc */
.be-score{background:#EFF4FB;border-radius:8px;padding:14px 18px;text-align:center}
.be-score .be-score-val{font-size:1.4rem;font-weight:800;color:var(--bleu,#1F4E79)}
.be-score .be-score-val input{width:70px;padding:2px 6px;font:inherit;color:inherit;text-align:center;border:1px solid #c7d7ea;border-radius:6px;background:#fff}
.be-score .be-score-sub{font-size:.82rem;color:#64748b;margin-top:2px}
/* Circuit du rapport de review */
.be-circuit{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:10px 16px;margin-bottom:14px;font-size:.8rem;color:#475569}
/* Circuit : étapes fines reliées par un trait */
.be-circuit .etapes{display:flex;align-items:center;gap:0;margin:0;padding:0;list-style:none}
.be-circuit .etapes li{display:flex;align-items:center;gap:7px;font-size:.78rem;font-weight:600;color:#94a3b8;white-space:nowrap}
.be-circuit .etapes li+li::before{content:"";width:28px;height:2px;border-radius:2px;background:#e2e8f0;margin:0 10px}
.be-circuit .etapes li b{display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;border:1.5px solid #cbd5e1;font-size:.68rem;color:#94a3b8;background:#fff}
.be-circuit .etapes li.cours{color:#1f4e79}
.be-circuit .etapes li.cours b{border-color:#2e74b5;background:#2e74b5;color:#fff;box-shadow:0 0 0 3px #dbeafe}
.be-circuit .etapes li.fait{color:#15803d}
.be-circuit .etapes li.fait b{border-color:#16a34a;background:#16a34a;color:#fff}
.be-circuit .etapes li.fait+li::before{background:#86efac}
.be-circuit .info{color:#64748b;font-size:.78rem}
.be-circuit .contrib-ok{color:#15803d;font-weight:600}
.be-circuit .main{display:inline-flex;align-items:center;gap:8px;margin-left:auto}
.be-circuit .main form{margin:0}
.be-circuit .main button{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #dbe4ee;color:#1f4e79;border-radius:8px;font-family:inherit;font-size:.76rem;font-weight:600;line-height:1;padding:7px 11px;cursor:pointer;transition:border-color .15s,background .15s}
.be-circuit .main button svg{width:14px;height:14px}
.be-circuit .main button:hover{background:#f5f9fe;border-color:#2e74b5}
.be-circuit .main .delegue{color:#1f4e79;font-weight:600}
.be-renvoi{background:#fffbeb;border:1px solid #fcd34d;color:#92400e;border-radius:10px;padding:9px 14px;margin-bottom:14px;font-size:.82rem}
/* Libellés des fonctionnalités (pilote) */
.be-libs{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:10px 16px;margin-bottom:14px;font-size:.8rem;color:#475569}
.be-libs summary{cursor:pointer;font-weight:600;color:#1F4E79;list-style:none}
.be-libs summary::-webkit-details-marker{display:none}
.be-libs summary::before{content:'›';display:inline-block;margin-right:6px;transition:transform .15s}
.be-libs[open] summary::before{transform:rotate(90deg)}
.be-libs .aide{color:#94a3b8;font-weight:400;margin-left:6px}
.be-libs-grille{display:grid;grid-template-columns:minmax(140px,240px) 1fr;gap:6px 12px;align-items:center;margin-top:10px}
.be-libs-grille .orig{color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.be-libs-grille input{border:1px solid #e2e8f0;border-radius:6px;padding:5px 9px;font-size:.82rem;color:#1e293b;font-family:inherit}
.be-libs-grille input:focus{outline:none;border-color:#93c5fd}
/* Verdict du client */
.be-verdict{display:flex;flex-direction:column;gap:8px}
.be-verdict-choix{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.be-verdict-choix span.lib{font-size:.78rem;font-weight:700;color:#1F4E79;text-transform:uppercase;letter-spacing:.4px;margin-right:4px;padding:0;border:none;background:none;border-radius:0}
.be-verdict-choix label{cursor:pointer}
.be-verdict-choix input{display:none}
.be-verdict-choix span{display:inline-block;padding:4px 12px;border-radius:999px;font-size:.78rem;font-weight:600;border:1px solid #e2e8f0;background:#fff;color:#64748b;transition:all .12s}
.be-verdict-choix input:checked + span{border-color:var(--vc);background:var(--vb);color:var(--vc)}
/* Indicateurs */
.be-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px}
.be-kpi{background:linear-gradient(180deg,#F5F9FE,#EAF2FB);border:1px solid #dbe7f5;border-radius:10px;padding:10px 12px}
.be-kpi .v{font-size:1.25rem;font-weight:800;color:#1F4E79}
.be-kpi .l{font-size:.74rem;font-weight:700;color:#2E74B5;text-transform:uppercase;letter-spacing:.3px}
.be-kpi .d{font-size:.74rem;color:#64748b;margin-top:2px}
.be-kpis-note{font-size:.74rem;color:#94a3b8;margin-top:6px}
/* Actions bloc */
.be-act-row{display:flex;gap:6px;align-items:center;margin-bottom:6px}
.be-act-row input[type=text]{flex:1;padding:5px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.84rem}
.be-act-row input[type=date]{width:130px;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.82rem}
</style>

<div class="cr-edit {{ $cr->type === 'demo_sprint' ? 'demo' : ($cr->estReunion() ? 'reu' : '') }}">
    @if($cr->type !== 'demo_sprint' && !$cr->estReunion())
    <h2>{{ $cr->titre }}</h2>
    <div class="sub">{{ $cr->typeCourt() }} - {{ $cr->date_reunion->format('d/m/Y') }} - Rédacteur : {{ $cr->redacteur?->nomComplet() }}</div>
    @endif

    @if(session('ok'))
    <div style="background:#d1fae5;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.85rem">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.85rem">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    {{-- Sommaire : accès direct aux sections, pastille verte quand la section est validée --}}
    @if($cr->type !== 'demo_sprint')<nav class="cr-sommaire" id="cr-sommaire" aria-label="Sections du compte rendu"></nav>@endif

    <form method="POST" action="{{ route('comptes-rendus.update', $cr) }}" id="cr-form">
        @csrf @method('PUT')

        @php
            $mef = $donnees['mise_en_forme'] ?? [];
            $taillesMef = [9, 10, 10.5, 11, 12, 13, 14, 16, 18, 20, 22, 24, 28];
            // Police de rendu (PDF et Word) : Segoe UI par défaut
            $choixPolice = '<select class="taille police" data-cle="police" title="Police du PDF et du Word">'
                . collect(\App\Http\Controllers\CompteRenduController::POLICES)->map(fn($p, $k) => '<option value="' . ($k === 'segoe' ? '' : $k) . '" style="font-family:' . e($p[1]) . '"' . (($mef['police'] ?? '') === ($k === 'segoe' ? '' : $k) || (($mef['police'] ?? '') === 'segoe' && $k === 'segoe') ? ' selected' : '') . '>' . e($p[0]) . ($k === 'segoe' ? ' (par défaut)' : '') . '</option>')->join('') . '</select>';
            // En-tête et pied de page du PDF et du Word : textes libres (vides = textes par défaut), numéro de page, logo
            $coche = fn(string $cle, string $lib) => '<label class="ep-chk"><input type="hidden" name="mise_en_forme[' . $cle . ']" value="0"><input type="checkbox" name="mise_en_forme[' . $cle . ']" value="1"' . (($mef[$cle] ?? true) ? ' checked' : '') . '> ' . $lib . '</label>';
            $entetePied = '<div class="syno-ep"><span class="ep-lib">En-tête et pied de page</span>'
                . '<label class="ep-txt">En-tête <input name="mise_en_forme[entete]" maxlength="150" value="' . e($mef['entete'] ?? '') . '" placeholder="Par défaut : SOUMAFÊ - Pilotage des opérations"></label>'
                . '<label class="ep-txt">Pied de page <input name="mise_en_forme[pied]" maxlength="150" value="' . e($mef['pied'] ?? '') . '" placeholder="Par défaut : SOUMAFÊ - Document généré le…"></label>'
                . $coche('num_page', 'Numéro de page') . $coche('logo', 'Logo') . '</div>';
            // Lecture seule : valeurs conservées telles quelles à l'enregistrement
            $entetePiedCache = collect(['entete', 'pied'])->map(fn($k) => '<input type="hidden" name="mise_en_forme[' . $k . ']" value="' . e($mef[$k] ?? '') . '">')->join('')
                . collect(['num_page', 'logo'])->map(fn($k) => '<input type="hidden" name="mise_en_forme[' . $k . ']" value="' . (($mef[$k] ?? true) ? 1 : 0) . '">')->join('');
        @endphp
        @if($cr->type === 'demo_sprint')
        {{-- RAPPORT : tableau synoptique (en-tête du document) --}}
        @php
            $sp = $cr->sprint;
            $goal = trim((string) ($donnees['objectif'] ?? $sp?->objectif ?? ''));
            $ecrit = true;
            $pilote = $user->estPilote();
            $taille = fn(string $cle, string $titre) => '<select class="taille" data-cle="' . $cle . '" title="' . e($titre) . '"><option value="">Aa</option>'
                . collect($taillesMef)->map(fn($pt) => '<option value="' . $pt . '"' . ((string) ($mef[$cle] ?? '') === (string) $pt ? ' selected' : '') . '>' . str_replace('.', ',', $pt) . ' pt</option>')->join('') . '</select>';
        @endphp
        <section class="syno" data-sect="entete">
            @foreach(['titre', 'section', 'sous_titre', 'texte', 'couleur_section', 'couleur_sous_titre', 'police'] as $cle)<input type="hidden" name="mise_en_forme[{{ $cle }}]" id="mef-{{ $cle }}" value="{{ $mef[$cle] ?? '' }}">@endforeach
            <div class="syno-tete">
                <span class="syno-lib">Rapport de Review Sprint</span>
                <div class="syno-titre">
                    @if($ecrit)<input name="titre" value="{{ $cr->titre }}" class="syno-doc" placeholder="Titre du document">{!! $taille('titre', 'Taille du titre du document (PDF et Word)') !!}
                    @else<div class="syno-doc">{{ $cr->titre }}</div>@endif
                </div>
            </div>
            <table class="syno-tbl">
                <tr><th>Sprint</th><td>
                    @if($sprintsRattachables->isNotEmpty())
                    <select name="sprint_id"><option value="">— Choisir le sprint : ses user stories et ses tâches seront reprises —</option>
                        @foreach($sprintsRattachables as $s)<option value="{{ $s->id }}">{{ $s->projet?->libelle }} · Sprint {{ $s->numero }} ({{ $s->date_debut->format('d/m') }} → {{ $s->date_fin->format('d/m/Y') }})</option>@endforeach
                    </select><div class="syno-alerte">Ce rapport n'est rattaché à aucun sprint : choisissez-le puis enregistrez.</div>
                    @elseif($sp)<strong>Sprint {{ $sp->numero }}</strong> · {{ $sp->projet?->libelle }} <span class="syno-gris">· du {{ $sp->date_debut->format('d/m/Y') }} au {{ $sp->date_fin->format('d/m/Y') }}</span>
                    @else — @endif
                </td>
                    <th>Score</th><td><strong><span id="be-score-header">{{ $donnees['score_pourcent'] ?? 0 }}%</span></strong> <span class="syno-gris">· {{ $donnees['points_realises'] ?? 0 }}/{{ $donnees['total_points'] ?? 0 }} pts</span></td></tr>
                <tr><th>Sprint Goal</th><td colspan="3">@include('comptes-rendus._objectifs', ['texte' => $goal])</td></tr>
                <tr><th>Date de la review</th><td>@if($pilote)<input type="date" name="date_reunion" value="{{ old('date_reunion', $cr->date_reunion->format('Y-m-d')) }}" required>@else{{ $cr->date_reunion->format('d/m/Y') }}@endif</td>
                    <th>Horaires</th><td>@if($ecrit)<input type="time" name="heure_debut" value="{{ $cr->heure_debut ? substr($cr->heure_debut, 0, 5) : '' }}"> <span class="syno-gris">→</span> <input type="time" name="heure_fin" value="{{ $cr->heure_fin ? substr($cr->heure_fin, 0, 5) : '' }}">@else{{ $cr->heure_debut ? substr($cr->heure_debut, 0, 5) : '—' }}{{ $cr->heure_fin ? ' → ' . substr($cr->heure_fin, 0, 5) : '' }}@endif</td></tr>
                <tr><th>Lieu</th><td>@if($ecrit)<input name="lieu" value="{{ $cr->lieu }}" placeholder="Lieu de la review">@else{{ $cr->lieu ?: '—' }}@endif</td>
                    <th>Rédacteur</th><td>{{ $cr->redacteur?->nomComplet() ?? '—' }}</td></tr>
            </table>
            @if($ecrit)<div class="syno-pied">Police du document : {!! $choixPolice !!} · Taille du texte : {!! $taille('texte', 'Taille du texte (PDF et Word)') !!} <span class="syno-gris">· les tailles des titres se règlent sur les titres eux-mêmes (Aa).</span></div>{!! $entetePied !!}@else{!! $entetePiedCache !!}@endif
        </section>
        @elseif($cr->estReunion())
        {{-- RÉUNION : tableau signalétique (en-tête du document), comme le rapport de review --}}
        @php
            $pilote = $user->estPilote();
            $taille = fn(string $cle, string $titre) => '<select class="taille" data-cle="' . $cle . '" title="' . e($titre) . '"><option value="">Aa</option>'
                . collect($taillesMef)->map(fn($pt) => '<option value="' . $pt . '"' . ((string) ($mef[$cle] ?? '') === (string) $pt ? ' selected' : '') . '>' . str_replace('.', ',', $pt) . ' pt</option>')->join('') . '</select>';
        @endphp
        <section class="syno" data-sect="entete">
            @foreach(['titre', 'section', 'sous_titre', 'texte', 'couleur_section', 'couleur_sous_titre', 'police'] as $cle)<input type="hidden" name="mise_en_forme[{{ $cle }}]" id="mef-{{ $cle }}" value="{{ $mef[$cle] ?? '' }}">@endforeach
            <div class="syno-tete">
                <span class="syno-lib">Compte rendu de réunion</span>
                <div class="syno-titre"><input name="titre" value="{{ $cr->titre }}" class="syno-doc" placeholder="Titre du document">{!! $taille('titre', 'Taille du titre du document (PDF et Word)') !!}</div>
            </div>
            <table class="syno-tbl">
                <tr><th>Date</th><td>@if($pilote)<input type="date" name="date_reunion" value="{{ old('date_reunion', $cr->date_reunion->format('Y-m-d')) }}" required>@else{{ $cr->date_reunion->format('d/m/Y') }}@endif</td>
                    <th>Type de réunion</th><td>@if($pilote)<select name="type">
                        <option value="reunion_ordinaire" @selected(old('type', $cr->type) === 'reunion_ordinaire')>Réunion ordinaire</option>
                        <option value="reunion_extraordinaire" @selected(old('type', $cr->type) === 'reunion_extraordinaire')>Réunion extraordinaire</option>
                    </select>@else{{ $cr->typeCourt() }}@endif</td></tr>
                <tr><th>Présidée par</th><td>@if($pilote)<select name="president_id"><option value="">— Aucun —</option>@foreach($collaborateurs as $c)<option value="{{ $c->id }}" @selected((int) old('president_id', $cr->president_id) === $c->id)>{{ $c->nomComplet() }}</option>@endforeach</select>@else{{ $cr->president?->nomComplet() ?? '—' }}@endif</td>
                    <th>Rédigé par</th><td>@if($pilote)<select name="redacteur_id" required>@foreach($collaborateurs as $c)<option value="{{ $c->id }}" @selected((int) old('redacteur_id', $cr->redacteur_id) === $c->id)>{{ $c->nomComplet() }}</option>@endforeach</select>@else{{ $cr->redacteur?->nomComplet() ?? '—' }}@endif</td></tr>
                <tr><th>Horaires</th><td><input type="time" name="heure_debut" value="{{ $cr->heure_debut ? substr($cr->heure_debut, 0, 5) : '' }}"> <span class="syno-gris">→</span> <input type="time" name="heure_fin" value="{{ $cr->heure_fin ? substr($cr->heure_fin, 0, 5) : '' }}"></td>
                    <th>Lieu</th><td><input name="lieu" value="{{ $cr->lieu }}" placeholder="Lieu de la réunion"></td></tr>
            </table>
            <div class="syno-pied"><span>Police {!! $choixPolice !!}</span> Tailles :
                <span>titres de section {!! $taille('section', 'Taille des titres de section (PDF et Word)') !!}</span>
                <span>sous-titres {!! $taille('sous_titre', 'Taille des sous-titres (PDF et Word)') !!}</span>
                <span>texte {!! $taille('texte', 'Taille du texte (PDF et Word)') !!}</span>
            </div>
            {!! $entetePied !!}
        </section>
        @else
        {{-- EN-TÊTE --}}
        <div class="section" data-sect="entete">
            <div class="section-head" onclick="toggleSection(this)">
                <h3>En-tête</h3>
                <div class="sect-status"><span class="sect-toggle">&#9660;</span></div>
            </div>
            <div class="section-body">
                <div class="fg"><label>Titre</label><input name="titre" value="{{ $cr->titre }}"></div>
                @if($sprintsRattachables->isNotEmpty())
                <div class="fg"><label>Sprint du rapport <span style="color:#b91c1c">*</span></label>
                    <select name="sprint_id"><option value="">— Choisir le sprint : ses user stories et ses tâches seront reprises —</option>
                        @foreach($sprintsRattachables as $s)<option value="{{ $s->id }}">{{ $s->projet?->libelle }} · Sprint {{ $s->numero }} ({{ $s->date_debut->format('d/m') }} → {{ $s->date_fin->format('d/m/Y') }})</option>@endforeach
                    </select>
                    <div style="font-size:.78rem;color:#b45309;margin-top:4px">Ce rapport n'est rattaché à aucun sprint : choisissez-le puis enregistrez.</div>
                </div>
                @endif
                @if($user->estPilote())
                <div class="fg"><div class="row2">
                    <div><label>Date</label><input type="date" name="date_reunion" value="{{ old('date_reunion', $cr->date_reunion->format('Y-m-d')) }}" required></div>
                    @if($cr->estReunion())
                    <div><label>Type de réunion</label>
                        <select name="type">
                            <option value="reunion_ordinaire" @selected(old('type', $cr->type) === 'reunion_ordinaire')>Réunion ordinaire</option>
                            <option value="reunion_extraordinaire" @selected(old('type', $cr->type) === 'reunion_extraordinaire')>Réunion extraordinaire</option>
                        </select>
                    </div>
                    @endif
                @if($cr->estReunion())
                </div></div>
                <div class="fg"><div class="row2">
                    <div><label>Président de séance</label>
                        <select name="president_id">
                            <option value="">— Aucun —</option>
                            @foreach($collaborateurs as $c)
                            <option value="{{ $c->id }}" @selected((int) old('president_id', $cr->president_id) === $c->id)>{{ $c->nomComplet() }} ({{ $c->roleLibelle() }})</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div><label>Rédacteur</label>
                        <select name="redacteur_id" required>
                            @foreach($collaborateurs as $c)
                            <option value="{{ $c->id }}" @selected((int) old('redacteur_id', $cr->redacteur_id) === $c->id)>{{ $c->nomComplet() }} ({{ $c->roleLibelle() }})</option>
                            @endforeach
                        </select>
                    </div>
                </div></div>
                @endif
                <div class="fg"><div class="row-reu">
                    <div><label>Heure début</label><input type="time" name="heure_debut" value="{{ $cr->heure_debut }}"></div>
                    <div><label>Heure fin</label><input type="time" name="heure_fin" value="{{ $cr->heure_fin }}"></div>
                    <div><label>Lieu</label><input name="lieu" value="{{ $cr->lieu }}"></div>
                </div></div>
            </div>
        </div>

        {{-- MISE EN FORME DU DOCUMENT (PDF / Word) --}}
        @php
            $champsMef = [
                'titre'      => ['Titre du document', 'ex. « COMPTE RENDU DE RÉUNION »'],
                'section'    => ['Titres de section', 'ex. « 1. CONTEXTE… », « 2. DÉROULEMENT… »'],
                'sous_titre' => ['Sous-titres', 'ex. « 2.1. Informations »'],
                'texte'      => ['Texte', 'paragraphes, listes, tableaux'],
            ];
        @endphp
        <div class="section collapsed" data-sect="mise-en-forme">
            <div class="section-head" onclick="toggleSection(this)">
                <h3>Mise en forme du document (tailles en pt)</h3>
                <div class="sect-status"><span class="sect-toggle">&#9660;</span></div>
            </div>
            <div class="section-body">
                <p style="font-size:.82rem;color:#94a3b8;margin-bottom:12px">S'applique à la version PDF et au fichier Word. « Par défaut » conserve la taille standard du document.</p>
                <div class="fg"><div class="row2">
                @foreach($champsMef as $cle => [$libelle, $aide])
                    <div>
                        <label>{{ $libelle }} <span style="font-weight:400;color:#94a3b8;font-size:.78rem">- {{ $aide }}</span></label>
                        <select name="mise_en_forme[{{ $cle }}]">
                            <option value="">Par défaut</option>
                            @foreach($taillesMef as $pt)
                            <option value="{{ $pt }}" @selected((string) ($mef[$cle] ?? '') === (string) $pt)>{{ str_replace('.', ',', $pt) }} pt</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                </div></div>
            </div>
        </div>
        @endif

        @if($cr->estReunion())
            {{-- PRÉSENCES --}}
            <div class="section" data-sect="presences">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3>Liste de présence</h3>
                    <div class="sect-status">
                        <button type="button" class="btn-validate" onclick="event.stopPropagation();validerSection(this)" title="Valider cette section">&#10003; Valider</button>
                        <span class="sect-toggle">&#9660;</span>
                    </div>
                </div>
                <div class="section-body">
                    <table class="tbl">
                        <thead><tr><th class="num-col">N°</th><th>Collaborateur</th><th>Fonction</th><th style="width:80px;text-align:center">Présent</th></tr></thead>
                        <tbody>
                        @foreach($donnees['presences'] ?? [] as $i => $p)
                        <tr>
                            <td class="num-col">{{ $i + 1 }}</td>
                            <td>{{ $p['nom'] }}<input type="hidden" name="donnees[presences][{{ $i }}][nom]" value="{{ $p['nom'] }}"></td>
                            <td><input type="text" name="donnees[presences][{{ $i }}][fonction]" value="{{ $p['fonction'] ?? '' }}" placeholder="Fonction"></td>
                            <td style="text-align:center"><input type="checkbox" class="chk" name="donnees[presences][{{ $i }}][present]" value="1" @checked($p['present'] ?? false)></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 1. CONTEXTE + ODJ --}}
            <div class="section" data-sect="contexte">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3 data-num="1">Contexte et objectifs</h3>
                    <div class="sect-status">
                        <button type="button" class="btn-validate" onclick="event.stopPropagation();validerSection(this)">&#10003; Valider</button>
                        <span class="sect-toggle">&#9660;</span>
                    </div>
                </div>
                <div class="section-body">
                    <div class="fg">
                        <label>Contexte et objectif</label>
                        <div class="rich-toolbar" data-target="ctx-editor">
                            <button type="button" onclick="execRich('bold',this)" title="Gras"><b>G</b></button>
                            <button type="button" onclick="execRich('italic',this)" title="Italique"><i>I</i></button>
                            <button type="button" onclick="execRich('underline',this)" title="Souligné"><u>S</u></button>
                            <button type="button" onclick="execRich('insertUnorderedList',this)" title="Liste à puces">&#8226; Liste</button>
                            <button type="button" onclick="execRich('insertOrderedList',this)" title="Liste numérotée">1. Liste</button>
                            <button type="button" onmousedown="event.preventDefault();listeHier(this)" title="Liste numérotée hiérarchique (ex. 2.2.1.)">1.1 Liste</button>
                            <button type="button" onmousedown="event.preventDefault();indenterRich('indent',this)" title="Décaler à droite (Tab)">&#8677;</button>
                            <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex])<button type="button" class="coul-pastille" style="--c:#{{ $hex }}" title="{{ $lib }}" onmousedown="event.preventDefault();appliquerCouleur({{ $n }},this)"></button>@endforeach<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)" title="Retirer la couleur">Automatique</button></span></span>
                        </div>
                        <div class="rich-editor" contenteditable="true" id="ctx-editor" style="--num-prefix:'1.'" data-name="donnees[contexte]">{!! $donnees['contexte'] ?? '' !!}</div>
                        <input type="hidden" name="donnees[contexte]" id="ctx-editor-hidden">
                    </div>

                    <label style="font-size:.85rem;font-weight:600;color:#475569;margin-bottom:8px;display:block">Ordre du jour</label>
                    <div id="odj-list">
                    @foreach(($donnees['ordre_du_jour'] ?? []) ?: [['point' => '', 'contenu' => '']] as $k => $odj)
                        <div class="odj-item" data-idx="{{ $k }}">
                            <span class="num">{{ $k + 1 }}</span>
                            <div style="display:flex;gap:10px;align-items:center">
                                <div style="flex:1"><textarea name="donnees[ordre_du_jour][{{ $k }}][point]" rows="2" style="width:100%;padding:10px 14px;font-size:.92rem;border-radius:8px;border:1px solid #d1d5db;resize:vertical;font-family:inherit" placeholder="Ex : Revue des livrables de la semaine">{{ $odj['point'] ?? '' }}</textarea></div>
                                <button type="button" class="btn-rm" onclick="this.closest('.odj-item').remove();renum()" title="Supprimer">&#10005;</button>
                            </div>
                        </div>
                    @endforeach
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:6px">
                        <button type="button" class="btn-add" onclick="ajouterOdj()">+ Ajouter un point</button>
                        <button type="button" class="btn-add" onclick="genererDeroulement()" style="background:var(--accent,#2E74B5);color:#fff;border:1px solid var(--accent,#2E74B5)" title="Créer / mettre à jour les titres du déroulement à partir de l'ordre du jour">&#10003; Valider l'ordre du jour</button>
                    </div>
                </div>
            </div>

            {{-- 2. DÉROULEMENT --}}
            <div class="section" data-sect="deroulement">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3 data-num="2">Déroulement de la réunion</h3>
                    <div class="sect-status">
                        <button type="button" class="btn-validate" onclick="event.stopPropagation();validerSection(this)">&#10003; Valider</button>
                        <span class="sect-toggle">&#9660;</span>
                    </div>
                </div>
                <div class="section-body">
                    <p style="font-size:.82rem;color:#94a3b8;margin-bottom:12px">Développez chaque point de l'ordre du jour. Après avoir modifié l'ordre du jour, cliquez sur « Valider l'ordre du jour » pour mettre à jour les titres ci-dessous.</p>
                    <div id="deroulement-list">
                    @foreach($donnees['ordre_du_jour'] ?? [] as $k => $odj)
                        @if(!empty($odj['point']))
                        <div class="fg derou-item" data-idx="{{ $k }}">
                            <label>2.{{ $k + 1 }}. {{ $odj['point'] }}</label>
                            <div class="rich-toolbar" data-target="derou-{{ $k }}">
                                <button type="button" onclick="execRich('bold',this)" title="Gras"><b>G</b></button>
                                <button type="button" onclick="execRich('italic',this)" title="Italique"><i>I</i></button>
                                <button type="button" onclick="execRich('underline',this)" title="Souligné"><u>S</u></button>
                                <button type="button" onclick="execRich('insertUnorderedList',this)" title="Liste à puces">&#8226; Liste</button>
                                <button type="button" onclick="execRich('insertOrderedList',this)" title="Liste numérotée">1. Liste</button>
                            <button type="button" onmousedown="event.preventDefault();listeHier(this)" title="Liste numérotée hiérarchique (ex. 2.2.1.)">1.1 Liste</button>
                            <button type="button" onmousedown="event.preventDefault();indenterRich('indent',this)" title="Décaler à droite (Tab)">&#8677;</button>
                            <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex])<button type="button" class="coul-pastille" style="--c:#{{ $hex }}" title="{{ $lib }}" onmousedown="event.preventDefault();appliquerCouleur({{ $n }},this)"></button>@endforeach<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)" title="Retirer la couleur">Automatique</button></span></span>
                            </div>
                            <div class="rich-editor" contenteditable="true" id="derou-{{ $k }}" style="--num-prefix:'2.{{ $k + 1 }}.'" data-name="donnees[ordre_du_jour][{{ $k }}][contenu]">{!! $odj['contenu'] ?? '' !!}</div>
                            <input type="hidden" name="donnees[ordre_du_jour][{{ $k }}][contenu]" id="derou-{{ $k }}-hidden">
                        </div>
                        @endif
                    @endforeach
                    </div>
                </div>
            </div>

            {{-- 3. DÉCISIONS --}}
            <div class="section" data-sect="decisions">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3 data-num="3">Décisions / Actions à mener</h3>
                    <div class="sect-status">
                        <button type="button" class="btn-validate" onclick="event.stopPropagation();validerSection(this)">&#10003; Valider</button>
                        <span class="sect-toggle">&#9660;</span>
                    </div>
                </div>
                <div class="section-body">
                    <table class="tbl">
                        <thead><tr><th class="num-col">N°</th><th>Décision / Action</th><th style="width:220px">Responsable(s)</th><th style="width:120px">Échéance</th><th style="width:40px"></th></tr></thead>
                        <tbody id="decisions-body">
                        @foreach($donnees['decisions'] ?? [] as $k => $dec)
                        @php $resps = $dec['responsables'] ?? (isset($dec['responsable']) && $dec['responsable'] ? [$dec['responsable']] : []); @endphp
                        <tr>
                            <td class="num-col">{{ $k + 1 }}</td>
                            <td><input type="text" name="donnees[decisions][{{ $k }}][action]" value="{{ $dec['action'] ?? '' }}"></td>
                            <td><div class="ms-wrap">
                                <div class="ms-btn" onclick="toggleMS(this)">
                                    @if(count($resps))
                                        @foreach($resps as $rn)<span class="ms-tag">{{ $rn }}</span>@endforeach
                                    @else
                                        <span class="ms-placeholder">— Choisir —</span>
                                    @endif
                                </div>
                                <div class="ms-drop">
                                    <label><input type="checkbox" name="donnees[decisions][{{ $k }}][responsables][]" value="Tous" onchange="updateMSDisplay(this)" @checked(in_array('Tous', $resps))> <strong>Tous</strong></label>
                                    @foreach($collaborateurs as $c)
                                    <label><input type="checkbox" name="donnees[decisions][{{ $k }}][responsables][]" value="{{ $c->nomComplet() }}" onchange="updateMSDisplay(this)" @checked(in_array($c->nomComplet(), $resps))> {{ $c->nomComplet() }}</label>
                                    @endforeach
                                </div>
                            </div></td>
                            <td><input type="date" name="donnees[decisions][{{ $k }}][echeance]" value="{{ $dec['echeance'] ?? '' }}"></td>
                            <td><button type="button" class="btn-rm" onclick="this.closest('tr').remove();renumDec()">&#10005;</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn-add" onclick="ajouterDecision()" style="margin-top:8px">+ Ajouter une décision</button>
                </div>
            </div>

            {{-- 4. CONCLUSION --}}
            <div class="section" data-sect="conclusion">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3 data-num="4">Conclusion</h3>
                    <div class="sect-status">
                        <button type="button" class="btn-validate" onclick="event.stopPropagation();validerSection(this)">&#10003; Valider</button>
                        <span class="sect-toggle">&#9660;</span>
                    </div>
                </div>
                <div class="section-body">
                    <div class="fg" style="margin-bottom:0">
                        <div class="rich-toolbar" data-target="concl-editor">
                            <button type="button" onclick="execRich('bold',this)" title="Gras"><b>G</b></button>
                            <button type="button" onclick="execRich('italic',this)" title="Italique"><i>I</i></button>
                            <button type="button" onclick="execRich('underline',this)" title="Souligné"><u>S</u></button>
                            <button type="button" onclick="execRich('insertUnorderedList',this)" title="Liste à puces">&#8226; Liste</button>
                            <button type="button" onclick="execRich('insertOrderedList',this)" title="Liste numérotée">1. Liste</button>
                            <button type="button" onmousedown="event.preventDefault();listeHier(this)" title="Liste numérotée hiérarchique (ex. 2.2.1.)">1.1 Liste</button>
                            <button type="button" onmousedown="event.preventDefault();indenterRich('indent',this)" title="Décaler à droite (Tab)">&#8677;</button>
                            <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex])<button type="button" class="coul-pastille" style="--c:#{{ $hex }}" title="{{ $lib }}" onmousedown="event.preventDefault();appliquerCouleur({{ $n }},this)"></button>@endforeach<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)" title="Retirer la couleur">Automatique</button></span></span>
                        </div>
                        <div class="rich-editor" contenteditable="true" id="concl-editor" style="--num-prefix:'4.'" data-name="donnees[conclusion]">{!! $donnees['conclusion'] ?? '' !!}</div>
                        <input type="hidden" name="donnees[conclusion]" id="concl-editor-hidden">
                    </div>
                </div>
            </div>

        @else
            {{-- ===== DEMO SPRINT - ÉDITEUR BLOC WYSIWYG ===== --}}
            <input type="hidden" name="bloc_data" id="bloc-data-input">


            @if($circuit)
            @php
                $relecture = $donnees['relecture'] ?? [];
                // Le pilote qui rédige valide lui-même ; le responsable du projet qui rédige transmet au pilote
                $delegue = $circuit['mainResponsable'] || $cr->estEnRevision();
                $etapes = $delegue ? ['Rédaction', 'Transmis au pilote', 'Validation'] : ['Rédaction', 'Validation'];
                $rangC = $cr->estConfirme() ? count($etapes) + 1 : ($cr->estEnRevision() ? 2 : 1);
            @endphp
            <div class="be-circuit">
                <ol class="etapes" aria-label="Circuit du rapport">
                    @foreach($etapes as $i => $lib)
                    <li class="{{ $i + 1 < $rangC ? 'fait' : ($i + 1 === $rangC ? 'cours' : '') }}"><b>{!! $i + 1 < $rangC ? '&#10003;' : $i + 1 !!}</b>{{ $lib }}</li>
                    @endforeach
                </ol>
                @if(!empty($relecture['transmis_le']) && $cr->estEnRevision())<span class="info">Transmis par {{ $relecture['transmis_par'] }} le {{ $relecture['transmis_le'] }}</span>@endif
                {{-- Le pilote rédige ; il peut passer la main au responsable du projet, et la reprendre --}}
                <span class="main">
                    @if($circuit['mainResponsable'])
                    <span class="delegue" title="{{ !empty($donnees['main']) ? 'Main passée par ' . $donnees['main']['par'] . ' le ' . $donnees['main']['le'] : '' }}">Rédaction confiée à {{ $cr->redacteur?->nomComplet() }}</span>
                    @else
                    <span class="info">Rédaction : <strong style="color:#1f4e79">{{ $cr->redacteur?->nomComplet() ?? 'le pilote' }}</strong></span>
                    @endif
                    @if($user->estPilote() && $cr->estBrouillon())
                        @if($circuit['mainResponsable'])
                        <button type="button" onclick="mainCR('pilote', 'Reprendre la main sur la rédaction ? Le rapport est enregistré avant.')" title="La rédaction revient au pilote"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>Reprendre la main</button>
                        @elseif($circuit['responsable'])
                        <button type="button" onclick="mainCR('responsable', 'Passer la main à {{ $circuit['responsable']->nomComplet() }} pour la rédaction ? Il vous transmettra le rapport pour validation ; vous gardez la main sur toutes les rubriques. Le rapport est enregistré avant.')" title="Le responsable du projet rédige puis vous transmet le rapport ; vous gardez la main"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>Passer la main à {{ $circuit['responsable']->nomComplet() }}</button>
                        @endif
                    @endif
                </span>
            </div>
            @if(!empty($relecture['renvoi']) && $cr->estBrouillon())
            <div class="be-renvoi"><strong>Renvoyé par {{ $relecture['renvoi']['par'] }}</strong> le {{ $relecture['renvoi']['le'] }} : {{ $relecture['renvoi']['motif'] }}</div>
            @endif
            @endif
            @if($cr->estBrouillon())
            <div class="be-contrib">L'observation de chaque tâche reprend ce que le développeur a consigné sur sa tâche (blocages, commentaires, qualification). Vous la reformulez librement ; « Actualiser » reprend les nouveaux commentaires sans toucher aux observations que vous avez modifiées. Rédigez le reste (verdict du client, user stories, décisions), puis {{ $user->estPilote() ? 'validez le rapport' : 'transmettez-le au pilote' }}.</div>
            @elseif($cr->estEnRevision())
            <div class="be-contrib">Rapport transmis par le responsable du projet : corrigez si besoin, puis validez-le ou renvoyez-le au rédacteur. À la validation, les nouvelles user stories rejoignent le Product Backlog et les décisions deviennent des tâches suivies.</div>
            @endif
            <datalist id="be-modules-liste">@foreach(collect($donnees['stories'] ?? [])->pluck('module')->filter()->unique() as $m)<option value="{{ $m }}">@endforeach</datalist>
            <datalist id="be-membres">@foreach($collaborateurs as $c)<option value="{{ $c->nomComplet() }}">@endforeach</datalist>
            @if($user->estPilote())
            <details class="be-libs" id="be-libs"><summary>Libellés des fonctionnalités<span class="aide">noms importés du Product Backlog, à reformuler pour ce rapport</span></summary><div class="be-libs-grille" id="be-libs-grille"></div></details>
            @endif
            <div id="be-modules"></div>
            <button type="button" class="be-add-mod" onclick="beAddModule()">+ Ajouter un module</button>
        @endif

        <div class="cr-bottom">
            <a href="{{ route('comptes-rendus.index') }}" class="bt bt-ghost"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>Retour</a>
            <a href="{{ route('comptes-rendus.imprimer', $cr) }}" target="_blank" class="bt bt-ghost" title="Version PDF du dernier enregistrement, dans un nouvel onglet"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><circle cx="11.5" cy="14.5" r="2.5"/><line x1="13.3" y1="16.3" x2="15" y2="18"/></svg>Aperçu PDF</a>
            <span class="espace"></span>
            <button type="submit" class="bt bt-sec" title="Enregistrer sans changer d'étape"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>Enregistrer</button>
            @if($circuit)
                @if($cr->estEnRevision() && $user->estPilote())
                <button type="button" class="bt bt-warn" onclick="renvoyerCR()" title="Renvoyer le rapport au rédacteur avec un motif"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>Renvoyer</button>
                @endif
                @if(!$cr->estConfirme() && $user->estPilote())
                <button type="button" class="bt bt-ok" onclick="etapeCR('confirm-form', 'Valider le rapport ? Les nouvelles user stories rejoignent le Product Backlog, les décisions deviennent des tâches suivies et le DOCX est généré.')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Valider le rapport</button>
                @elseif($cr->estBrouillon() && (int) $cr->redacteur_id === (int) $user->id)
                <button type="button" class="bt bt-prim" onclick="etapeCR('transmettre-form', 'Transmettre le rapport au pilote pour validation ? Il est enregistré avant l\'envoi.')"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Transmettre au pilote</button>
                @endif
            @elseif($cr->estBrouillon() && $peutConfirmer)
            <button type="button" class="bt bt-ok" onclick="confirmerCR()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>Confirmer le compte rendu</button>
            @endif
        </div>
    </form>

    @if(!$cr->estConfirme())
    <form method="POST" action="{{ route('comptes-rendus.confirmer', $cr) }}" id="confirm-form" style="display:none">@csrf</form>
    @endif
    @if($circuit && $cr->estBrouillon())
    <form method="POST" action="{{ route('comptes-rendus.transmettre', $cr) }}" id="transmettre-form" style="display:none">@csrf</form>
    @if($user->estPilote())<form method="POST" action="{{ route('comptes-rendus.main', $cr) }}" id="main-form" style="display:none">@csrf<input type="hidden" name="a"></form>@endif
    @endif
    @if($circuit && $cr->estEnRevision())
    <form method="POST" action="{{ route('comptes-rendus.relire', $cr) }}" id="renvoi-form" style="display:none">@csrf<input type="hidden" name="decision" value="renvoi"><input type="hidden" name="motif"></form>
    @endif
</div>

<script>
/* Sommaire construit depuis les sections de la page */
(function () {
    const nav = document.getElementById('cr-sommaire');
    if (!nav) return;
    const maj = () => {
        nav.innerHTML = '';
        document.querySelectorAll('#cr-form .section[data-sect]').forEach(sec => {
            const titre = sec.querySelector('.section-head h3')?.textContent.trim();
            if (!titre || sec.dataset.sect === 'mise-en-forme') return;
            if (!sec.id) sec.id = 'sect-' + sec.dataset.sect;
            const a = document.createElement('a');
            a.href = '#' + sec.id;
            a.className = sec.classList.contains('validated') ? 'ok' : '';
            a.innerHTML = '<i></i>' + titre.replace(/^\d+\.\s*/, '');
            a.addEventListener('click', e => { e.preventDefault(); sec.classList.remove('collapsed'); sec.scrollIntoView({behavior: 'smooth', block: 'start'}); });
            nav.appendChild(a);
        });
    };
    document.addEventListener('DOMContentLoaded', maj);
    // Les pastilles suivent la validation des sections
    new MutationObserver(maj).observe(document.getElementById('cr-form'), {subtree: true, attributes: true, attributeFilter: ['class']});
})();

let odjIdx = {{ count($donnees['ordre_du_jour'] ?? []) }};
let decIdx = {{ count($donnees['decisions'] ?? []) }};
const collabs = @json($collaborateurs->map(fn($c) => $c->nomComplet())->values());
const estReunion = {{ $cr->estReunion() ? 'true' : 'false' }};

/* === Rich editor === */
function execRich(cmd, btn){
    const toolbar = btn.closest('.rich-toolbar');
    const editorId = toolbar.dataset.target;
    const editor = document.getElementById(editorId);
    editor.focus();
    document.execCommand(cmd, false, null);
}
function syncEditors(){
    document.querySelectorAll('.rich-editor').forEach(ed => {
        normaliserListes(ed);
        const hiddenId = ed.id + '-hidden';
        const hidden = document.getElementById(hiddenId);
        if(hidden) hidden.value = ed.innerHTML.trim();
    });
}

/* === Listes hiérarchiques (2.2.1.) et indentation === */
function editeurDeBouton(btn){ return document.getElementById(btn.closest('.rich-toolbar').dataset.target); }
function listeSousCurseur(ed){
    const sel = window.getSelection();
    if(!sel.rangeCount) return null;
    let n = sel.getRangeAt(0).startContainer;
    if(n.nodeType === 3) n = n.parentNode;
    const ol = n.closest && n.closest('ol');
    return ol && ed.contains(ol) ? ol : null;
}
/* Le navigateur imbrique parfois une liste comme sœur des <li> : on la range dans le <li> précédent */
function normaliserListes(ed){
    ed.querySelectorAll('ol > ol, ol > ul, ul > ol, ul > ul').forEach(sub => {
        const prev = sub.previousElementSibling;
        if(prev && prev.tagName === 'LI') prev.appendChild(sub);
    });
}
function racineListe(ol, ed){
    let r = ol;
    while(r.parentElement && r.parentElement !== ed && r.parentElement.closest('ol') && ed.contains(r.parentElement.closest('ol'))) r = r.parentElement.closest('ol');
    return r;
}
function listeHier(btn){
    const ed = editeurDeBouton(btn); if(!ed) return;
    ed.focus();
    let ol = listeSousCurseur(ed);
    if(ol){ racineListe(ol, ed).classList.toggle('hier'); return; }
    document.execCommand('insertOrderedList', false, null);
    ol = listeSousCurseur(ed);
    if(ol) racineListe(ol, ed).classList.add('hier');
}
function indenterRich(cmd, btn){
    const ed = editeurDeBouton(btn); if(!ed) return;
    ed.focus(); document.execCommand(cmd, false, null); normaliserListes(ed);
}
/* Taille : <font size> 1→7 = 9, 10, 12 (normal), 14, 16, 20, 24 pt */
const TAILLES_PT = {1:'9', 2:'10', 3:'12', 4:'14', 5:'16', 6:'20', 7:'24'};
const COULEURS_TEXTE = {!! json_encode(collect(\App\Http\Controllers\CompteRenduController::COULEURS)->map(fn($c, $n) => [$n, $c[0], $c[1]])->values()) !!};
/* Couleur du texte sélectionné : classe tc-N (0 = couleur automatique) */
function appliquerCouleur(n, btn){
    btn.closest('.lh-menu').classList.remove('open');
    const ed = editeurDeBouton(btn); if(!ed) return;
    const sel = window.getSelection();
    const plage = sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
    ed.focus();
    if(plage && ed.contains(plage.commonAncestorContainer)){ sel.removeAllRanges(); sel.addRange(plage); }
    if(!sel.rangeCount || sel.isCollapsed || !ed.contains(sel.getRangeAt(0).commonAncestorContainer)){ alert("Sélectionnez d'abord le texte à colorer."); return; }
    document.execCommand('styleWithCSS', false, false);
    document.execCommand('foreColor', false, '#010203');
    ed.querySelectorAll('font[color="#010203"]').forEach(f => {
        f.removeAttribute('color');
        f.querySelectorAll('font[class*="tc-"]').forEach(x => { x.classList.forEach(k => { if(k.startsWith('tc-')) x.classList.remove(k); }); if(!x.attributes.length || (x.attributes.length === 1 && x.getAttribute('class') === '')) x.replaceWith(...x.childNodes); });
        f.classList.forEach(k => { if(k.startsWith('tc-')) f.classList.remove(k); });
        if(n) f.classList.add('tc-' + n);
        if(!f.classList.length) f.removeAttribute('class');
        if(!f.attributes.length) f.replaceWith(...f.childNodes);
    });
}
function appliquerTaille(n, btn){
    btn.closest('.lh-menu').classList.remove('open');
    const ed = editeurDeBouton(btn); if(!ed) return;
    const sel = window.getSelection();
    const plage = sel.rangeCount ? sel.getRangeAt(0).cloneRange() : null;
    ed.focus();
    if(plage && ed.contains(plage.commonAncestorContainer)){ sel.removeAllRanges(); sel.addRange(plage); }
    if(!sel.rangeCount || sel.isCollapsed || !ed.contains(sel.getRangeAt(0).commonAncestorContainer)){ alert("Sélectionnez d'abord le texte à redimensionner."); return; }
    document.execCommand('styleWithCSS', false, false);
    document.execCommand('fontSize', false, String(n));
    if(n === 3) ed.querySelectorAll('font[size="3"]').forEach(f => f.replaceWith(...f.childNodes));
    afficherTailleCourante();
}
/* Affiche dans le bouton « Taille » la taille du texte sous le curseur */
function afficherTailleCourante(){
    const sel = window.getSelection();
    if(!sel.rangeCount) return;
    let n = sel.getRangeAt(0).startContainer;
    if(n.nodeType === 3) n = n.parentNode;
    const ed = n.closest && n.closest('.rich-editor');
    if(!ed) return;
    const f = n.closest('font[size]');
    const val = TAILLES_PT[f && ed.contains(f) ? f.getAttribute('size') : 3] || '12';
    const tb = document.querySelector(`.rich-toolbar[data-target="${ed.id}"] .taille-val`);
    if(tb) tb.textContent = val;
}
document.addEventListener('selectionchange', afficherTailleCourante);
/* Interligne : classe lh-* posée sur les paragraphes (ou listes) touchés par la sélection */
function appliquerInterligne(classe, btn){
    btn.closest('.lh-menu').classList.remove('open');
    const ed = editeurDeBouton(btn); if(!ed) return;
    const sel = window.getSelection();
    const plage = sel.rangeCount ? sel.getRangeAt(0) : null;
    if(!plage || !ed.contains(plage.commonAncestorContainer)){ alert('Placez le curseur ou sélectionnez les paragraphes dans la zone de texte.'); return; }
    const [sc, so, ec, eo] = [plage.startContainer, plage.startOffset, plage.endContainer, plage.endOffset];
    /* Regroupe le texte « nu » de premier niveau dans des <div> pour pouvoir lui appliquer l'interligne */
    let groupe = null;
    [...ed.childNodes].forEach(n => {
        const bloc = n.nodeType === 1 && /^(DIV|P|UL|OL|BLOCKQUOTE)$/.test(n.tagName);
        if(bloc){ groupe = null; return; }
        if(n.nodeType === 1 && n.tagName === 'BR'){ if(groupe){ groupe.appendChild(n); } groupe = null; return; }
        if(!groupe){ groupe = document.createElement('div'); n.before(groupe); }
        groupe.appendChild(n);
    });
    const zone = document.createRange();
    try { zone.setStart(sc === ed ? ed : sc, sc === ed ? Math.min(so, ed.childNodes.length) : so); zone.setEnd(ec === ed ? ed : ec, ec === ed ? Math.min(eo, ed.childNodes.length) : eo); }
    catch(_) { zone.selectNodeContents(ed); }
    const blocs = [...ed.children].filter(b => zone.intersectsNode(b));
    sel.removeAllRanges(); sel.addRange(zone);
    blocs.forEach(b => {
        b.classList.remove('lh-10','lh-115','lh-15','lh-20','lh-25');
        if(classe) b.classList.add(classe);
        if(!b.classList.length) b.removeAttribute('class');
    });
}
document.addEventListener('mousedown', e => { if(!e.target.closest('.lh-wrap')) document.querySelectorAll('.lh-menu.open').forEach(m => m.classList.remove('open')); });
/* Ouvre le menu sous son bouton, au premier plan (position calculée sur la fenêtre) */
function ouvrirMenuOutil(btn){
    const menu = btn.nextElementSibling;
    const ouvert = menu.classList.contains('open');
    document.querySelectorAll('.lh-menu.open').forEach(m => m.classList.remove('open'));
    if(ouvert) return;
    menu.classList.add('open');
    const r = btn.getBoundingClientRect(), h = menu.offsetHeight, w = menu.offsetWidth;
    menu.style.left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8)) + 'px';
    menu.style.top = (r.bottom + 4 + h > window.innerHeight ? Math.max(8, r.top - h - 4) : r.bottom + 4) + 'px';
}
window.addEventListener('scroll', () => document.querySelectorAll('.lh-menu.open').forEach(m => m.classList.remove('open')), true);
document.addEventListener('keydown', e => {
    if(e.key !== 'Tab') return;
    const ed = e.target.closest && e.target.closest('.rich-editor');
    if(!ed) return;
    e.preventDefault();
    document.execCommand(e.shiftKey ? 'outdent' : 'indent', false, null);
    normaliserListes(ed);
});

/* === Collage : on ne garde que gras / italique / souligné / listes / paragraphes === */
function nettoyerHtmlColle(html){
    const doc = new DOMParser().parseFromString(html, 'text/html');
    doc.querySelectorAll('[style*="mso-list:Ignore"],[style*="mso-list: Ignore"],style,script,meta,link,title,xml').forEach(n => n.remove());
    const GARDER = {B:'b',STRONG:'b',I:'i',EM:'i',U:'u',UL:'ul',OL:'ol',LI:'li',P:'p',DIV:'p',BR:'br',H1:'p',H2:'p',H3:'p',H4:'p',H5:'p',H6:'p'};
    const out = document.createElement('div');
    let listeWord = null;
    function copier(src, dest){
        src.childNodes.forEach(n => {
            if(n.nodeType === 3){ dest.appendChild(document.createTextNode(n.textContent.replace(/ /g,' '))); return; }
            if(n.nodeType !== 1) return;
            const cls = n.className || '';
            if(n.tagName === 'P' && /MsoList/.test(cls) && dest === out){
                if(!listeWord){ listeWord = document.createElement('ul'); out.appendChild(listeWord); }
                const li = document.createElement('li'); copier(n, li); listeWord.appendChild(li); return;
            }
            if(dest === out) listeWord = null;
            const tag = GARDER[n.tagName];
            if(tag === 'br'){ dest.appendChild(document.createElement('br')); return; }
            if(tag){ const el = document.createElement(tag); copier(n, el); if(el.textContent.trim() || tag === 'ul' || tag === 'ol') dest.appendChild(el); }
            else copier(n, dest);
        });
    }
    copier(doc.body, out);
    return out.innerHTML;
}
document.addEventListener('paste', e => {
    const ed = e.target.closest && e.target.closest('.rich-editor');
    if(!ed) return;
    e.preventDefault();
    const cd = e.clipboardData;
    const html = cd.getData('text/html');
    if(html){
        document.execCommand('insertHTML', false, nettoyerHtmlColle(html));
    } else {
        const txt = cd.getData('text/plain');
        const esc = txt.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        document.execCommand('insertHTML', false, esc.split(/\r?\n/).join('<br>'));
    }
});

/* === Sections pliables === */
function toggleSection(head){ head.closest('.section').classList.toggle('collapsed'); }

/* === Validation d'une section : la replie et passe le bouton au vert (re-cliquer annule) === */
function validerSection(btn){
    const section = btn.closest('.section');
    const ok = btn.classList.toggle('validated');
    btn.innerHTML = ok ? '&#10003; Validé' : '&#10003; Valider';
    section.classList.toggle('validated', ok);
    section.classList.toggle('collapsed', ok);
}

/* === ODJ === */
function ajouterOdj(){
    const list = document.getElementById('odj-list');
    const div = document.createElement('div');
    div.className = 'odj-item'; div.dataset.idx = odjIdx;
    div.innerHTML = `<span class="num">${odjIdx+1}</span><div style="display:flex;gap:10px;align-items:center"><div style="flex:1"><textarea name="donnees[ordre_du_jour][${odjIdx}][point]" rows="2" style="width:100%;padding:10px 14px;font-size:.92rem;border-radius:8px;border:1px solid #d1d5db;resize:vertical;font-family:inherit" placeholder="Ex : Revue des livrables de la semaine"></textarea></div><button type="button" class="btn-rm" onclick="this.closest('.odj-item').remove();renum()" title="Supprimer">&#10005;</button></div>`;
    list.appendChild(div); odjIdx++; renum();
}
/* Reconstruit les titres du déroulement (2.x) depuis l'ordre du jour, en conservant le texte déjà saisi */
function genererDeroulement(){
    const liste = document.getElementById('deroulement-list');
    if(!liste) return;
    const contenus = {};
    liste.querySelectorAll('.derou-item').forEach(it => {
        const ed = it.querySelector('.rich-editor');
        contenus[it.dataset.idx] = ed ? ed.innerHTML.trim() : '';
    });
    const points = [];
    document.querySelectorAll('#odj-list .odj-item').forEach(it => {
        const txt = (it.querySelector('textarea')?.value || '').trim();
        if(txt) points.push({idx: it.dataset.idx, point: txt});
    });
    const perdus = Object.keys(contenus).filter(idx => contenus[idx] && !points.some(p => p.idx === idx));
    if(perdus.length && !confirm(`${perdus.length} point(s) retiré(s) de l'ordre du jour contiennent du texte dans le déroulement. Ce texte sera supprimé. Continuer ?`)) return;
    if(!points.length){ alert("Renseignez au moins un point de l'ordre du jour."); return; }

    liste.innerHTML = '';
    points.forEach((p, i) => {
        const edId = 'derou-' + p.idx, num = `2.${i+1}.`;
        const div = document.createElement('div');
        div.className = 'fg derou-item'; div.dataset.idx = p.idx;
        div.innerHTML = `<label></label>
            <div class="rich-toolbar" data-target="${edId}">
                <button type="button" onclick="execRich('bold',this)" title="Gras"><b>G</b></button>
                <button type="button" onclick="execRich('italic',this)" title="Italique"><i>I</i></button>
                <button type="button" onclick="execRich('underline',this)" title="Souligné"><u>S</u></button>
                <button type="button" onclick="execRich('insertUnorderedList',this)" title="Liste à puces">&#8226; Liste</button>
                <button type="button" onclick="execRich('insertOrderedList',this)" title="Liste numérotée">1. Liste</button>
                <button type="button" onmousedown="event.preventDefault();listeHier(this)" title="Liste numérotée hiérarchique (ex. 2.2.1.)">1.1 Liste</button>
                <button type="button" onmousedown="event.preventDefault();indenterRich('indent',this)" title="Décaler à droite (Tab)">&#8677;</button>
                <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">${COULEURS_TEXTE.map(([n, lib, hex]) => `<button type="button" class="coul-pastille" style="--c:#${hex}" title="${lib}" onmousedown="event.preventDefault();appliquerCouleur(${n},this)"></button>`).join('')}<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)" title="Retirer la couleur">Automatique</button></span></span>
            </div>
            <div class="rich-editor" contenteditable="true" id="${edId}" data-name="donnees[ordre_du_jour][${p.idx}][contenu]"></div>
            <input type="hidden" name="donnees[ordre_du_jour][${p.idx}][contenu]" id="${edId}-hidden">`;
        div.querySelector('label').textContent = `${num} ${p.point}`;
        const ed = div.querySelector('.rich-editor');
        ed.style.setProperty('--num-prefix', `'${num}'`);
        ed.innerHTML = contenus[p.idx] || '';
        liste.appendChild(div);
    });

    const sect = liste.closest('.section');
    if(sect){ sect.classList.remove('collapsed'); sect.scrollIntoView({behavior:'smooth', block:'start'}); }
}
function renum(){ document.querySelectorAll('#odj-list .odj-item .num').forEach((el,i) => el.textContent = i+1); }

/* === Multi-select responsables === */
function msCheckboxes(namePrefix){
    return `<label><input type="checkbox" name="${namePrefix}[responsables][]" value="Tous" onchange="updateMSDisplay(this)"> <strong>Tous</strong></label>` + collabs.map(c => `<label><input type="checkbox" name="${namePrefix}[responsables][]" value="${c}" onchange="updateMSDisplay(this)"> ${c}</label>`).join('');
}
function toggleMS(btn){
    const drop = btn.nextElementSibling;
    document.querySelectorAll('.ms-drop.open').forEach(d => { if(d !== drop) d.classList.remove('open'); });
    if(!drop.classList.contains('open')){
        const r = btn.getBoundingClientRect();
        drop.style.top = (r.bottom+2)+'px'; drop.style.left = r.left+'px'; drop.style.width = r.width+'px';
    }
    drop.classList.toggle('open');
}
function updateMSDisplay(chk){
    const wrap = chk.closest('.ms-wrap'), btn = wrap.querySelector('.ms-btn');
    const checked = wrap.querySelectorAll('.ms-drop input:checked');
    btn.innerHTML = checked.length === 0 ? '<span class="ms-placeholder">— Choisir —</span>' : Array.from(checked).map(c => `<span class="ms-tag">${c.value}</span>`).join('');
}
document.addEventListener('click', e => { if(!e.target.closest('.ms-wrap')) document.querySelectorAll('.ms-drop.open').forEach(d => d.classList.remove('open')); });

/* === Decisions (reunion) === */
function ajouterDecision(){
    const body = document.getElementById('decisions-body'), n = body.rows.length+1;
    const row = document.createElement('tr');
    row.innerHTML = `<td class="num-col">${n}</td><td><input type="text" name="donnees[decisions][${decIdx}][action]"></td><td><div class="ms-wrap"><div class="ms-btn" onclick="toggleMS(this)"><span class="ms-placeholder">— Choisir —</span></div><div class="ms-drop">${msCheckboxes('donnees[decisions]['+decIdx+']')}</div></div></td><td><input type="date" name="donnees[decisions][${decIdx}][echeance]"></td><td><button type="button" class="btn-rm" onclick="this.closest('tr').remove();renumDec()">&#10005;</button></td>`;
    body.appendChild(row); decIdx++;
}
function renumDec(){ document.querySelectorAll('#decisions-body tr').forEach((tr,i) => { const td = tr.querySelector('.num-col'); if(td) td.textContent = i+1; }); }

/* ======================================================================
 *  BLOC EDITOR - Éditeur WYSIWYG hiérarchique pour CR Demo
 * ====================================================================== */
if(!estReunion){

const BE_DONNEES = @json($donnees);
let beUid = 100;
const uid = () => 'be_' + (++beUid);

/* --- Icones SVG 2D --- */
const IC = {
    rappel:          '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
    fondamental:     '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
    attention:       '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    remarque:        '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    methode:         '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    conseil:         '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/></svg>',
    definition:      '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
    exemple:         '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
    bonne_pratique:  '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    blocage:         '<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>'
};

const REF_STYLES = {
    rappel:         {bg:'#E8F5E9',border:'#00897B',label:'Rappel'},
    fondamental:    {bg:'#FFF8E1',border:'#F9A825',label:'Fondamental'},
    attention:      {bg:'#FFF3E0',border:'#E6A817',label:'Attention'},
    remarque:       {bg:'#E8EEF4',border:'#2E74B5',label:'Remarque'},
    methode:        {bg:'#F3E8F9',border:'#7B2D8E',label:'Méthode'},
    conseil:        {bg:'#FFF8E1',border:'#EF6C00',label:'Conseil'},
    definition:     {bg:'#E3F2FD',border:'#1565C0',label:'Définition'},
    exemple:        {bg:'#F1F8E9',border:'#558B2F',label:'Exemple'},
    bonne_pratique: {bg:'#E0F2FE',border:'#0288D1',label:'Bonne pratique'},
    blocage:        {bg:'#FEECEC',border:'#CC3333',label:'Blocage'}
};

/* --- Conversion ancien format vers blocs --- */
function legacyToModules(d){
    const mods = [];
    const m1Secs = [{id:uid(),titre:'Tâches réalisées',blocs:[{id:uid(),type:'tableau_taches'},{id:uid(),type:'score'}]}];
    mods.push({id:uid(),titre:'Résultats du sprint',objectif:'Présenter les livrables et le score du sprint',sections:m1Secs});
    const m2Secs = [];
    m2Secs.push({id:uid(),titre:'Actions à mener',blocs:[{id:uid(),type:'actions',items:d.actions_a_mener||[]}]});
    m2Secs.push({id:uid(),titre:'Note pour le sprint suivant',blocs:[{id:uid(),type:'texte',contenu:d.note_sprint||''}]});
    mods.push({id:uid(),titre:'Analyse et prochaines étapes',objectif:'Évaluer la performance et planifier la suite',sections:m2Secs});
    /* Convertir anciennes annotations en blocs reference */
    if(d.annotations && d.annotations.length){
        const annSec = {id:uid(),titre:'Annotations',blocs:[]};
        d.annotations.forEach(a => {
            if(a.contenu) annSec.blocs.push({id:uid(),type:'reference',ref_type:a.type||'remarque',contenu:a.contenu});
        });
        if(annSec.blocs.length) mods[mods.length-1].sections.push(annSec);
    }
    return mods;
}

/* --- Initialisation --- */
let beModules;
if(BE_DONNEES.format === 'blocs_v1' && BE_DONNEES.modules){
    beModules = BE_DONNEES.modules;
} else {
    beModules = legacyToModules(BE_DONNEES);
}

/* --- Rich text toolbar HTML --- */
function beRichToolbar(edId){
    return `<div class="rich-toolbar" data-target="${edId}">
        <button type="button" onmousedown="event.preventDefault();beExec('bold','${edId}')" title="Gras"><b>G</b></button>
        <button type="button" onmousedown="event.preventDefault();beExec('italic','${edId}')" title="Italique"><i>I</i></button>
        <button type="button" onmousedown="event.preventDefault();beExec('underline','${edId}')" title="Souligné"><u>S</u></button>
        <button type="button" onmousedown="event.preventDefault();beExec('insertUnorderedList','${edId}')" title="Liste à puces">&#8226;</button>
        <button type="button" onmousedown="event.preventDefault();beExec('insertOrderedList','${edId}')" title="Liste numérotée">1.</button>
        <button type="button" onmousedown="event.preventDefault();listeHier(this)" title="Liste numérotée hiérarchique (ex. 2.2.1.)">1.1</button>
        <button type="button" onmousedown="event.preventDefault();indenterRich('indent',this)" title="Décaler à droite (Tab)">&#8677;</button>
        <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();ouvrirMenuOutil(this)" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">${COULEURS_TEXTE.map(([n, lib, hex]) => `<button type="button" class="coul-pastille" style="--c:#${hex}" title="${lib}" onmousedown="event.preventDefault();appliquerCouleur(${n},this)"></button>`).join('')}<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)" title="Retirer la couleur">Automatique</button></span></span>
    </div>`;
}
function beExec(cmd, edId){
    const ed = document.getElementById(edId); if(!ed) return;
    ed.focus(); document.execCommand(cmd, false, null);
}

/* --- Rendu d'un bloc --- */
function renderBloc(bloc, secEl){
    const div = document.createElement('div');
    div.className = 'be-bloc' + (bloc.largeur === 'demi' ? ' demi' : ''); div.dataset.id = bloc.id; div.dataset.type = bloc.type;
    if(bloc.module) div.dataset.module = bloc.module;
    beAppliquerCouleur(div, bloc.couleur || '');

    const typeLabels = {verdict:'Verdict du client',indicateurs:'Indicateurs',tableau_backlog:'User stories issues de la review',validation:'Validation',texte:'Texte',sous_titre:'Sous-titre',commentaire:'Commentaire',tableau_stories:'User stories',tableau_taches:'Tâches',score:'Score global',actions:'Actions à mener',reference:'Référence',note:'Note'};
    const typeLbl = typeLabels[bloc.type] || bloc.type;

    let headHtml = `<div class="be-bloc-head"><span class="be-handle" title="Glisser pour déplacer le bloc">&#8942;&#8942;</span><span class="be-type-lbl">${typeLbl}</span>`;
    const estTableau = bloc.type === 'tableau_stories' || bloc.type === 'tableau_taches';
    const legende = estTableau ? `<div class="be-legende"><span class="be-legende-lbl">${typeLbl}</span><select class="be-filtre-module" onchange="beFiltrerModule(this)" title="Fonctionnalité affichée dans ce tableau"><option value="">Toutes les fonctionnalités</option>${beModulesSprint().map(m => `<option value="${beEsc(m)}" ${m === (bloc.module||'') ? 'selected' : ''}>${beEsc(beLib(m))}</option>`).join('')}</select><button type="button" class="be-actualiser" onclick="beActualiser(this)" title="Reprendre l'état actuel du sprint (les observations saisies sont conservées)">&#10227; Actualiser</button></div>` : '';
    headHtml += `<div class="be-bloc-bar"><span class="be-coul-wrap"><button type="button" onclick="beMenuCouleur(this)" title="Couleur du bloc"><span class="be-pastille"></span></button><span class="be-coul-menu">${Object.entries(BLOC_COULEURS).map(([k, [lib, bg, bd]]) => `<button type="button" title="${lib}" style="background:#${bg};border-color:#${bd}" onclick="beChoisirCouleur(this,'${k}')"></button>`).join('')}<button type="button" class="aucune" title="Sans couleur" onclick="beChoisirCouleur(this,'')">&#8856;</button></span></span><button type="button" class="be-larg" onclick="beLargeur(this)" title="Demi-largeur : deux blocs côte à côte">&#9707;</button><button type="button" onclick="beMoveBloc(this,-1)" title="Monter">&#9650;</button><button type="button" onclick="beMoveBloc(this,1)" title="Descendre">&#9660;</button><button type="button" onclick="beRemoveBloc(this)" title="Supprimer" style="color:#dc2626">&#10005;</button></div></div>`;

    let bodyHtml = '<div class="be-bloc-body">' + legende;

    if(bloc.type === 'verdict'){
        const edId = 'be-ed-'+bloc.id;
        bodyHtml += `<div class="be-verdict"><div class="be-verdict-choix"><span class="lib">Verdict du client</span>${Object.entries(BE_VERDICTS).map(([k, [lib, c, b]]) =>
            `<label><input type="radio" name="vd-${bloc.id}" value="${k}" ${k === (bloc.verdict || '') ? 'checked' : ''}><span style="--vc:#${c};--vb:#${b}">${lib}</span></label>`).join('')}</div>`;
        bodyHtml += beRichToolbar(edId);
        bodyHtml += `<div class="rich-editor" contenteditable="true" id="${edId}" data-bloc-id="${bloc.id}" data-placeholder="Appréciation et retours du client : ce qui est apprécié, ce qu'il demande, l'objectif métier visé">${bloc.contenu||''}</div></div>`;
    }
    else if(bloc.type === 'indicateurs'){
        bodyHtml += `<div class="be-kpis">${BE_CARTES.map(([v, l, d]) => `<div class="be-kpi"><div class="v">${beEsc(v)}</div><div class="l">${beEsc(l)}</div>${d ? `<div class="d">${beEsc(d)}</div>` : ''}</div>`).join('')}</div>
            <div class="be-kpis-note">Calculés depuis le rapport (tâches, user stories, verdicts) et le sprint précédent ; mis à jour à chaque enregistrement. Le détail par développeur figure dans le PDF et le Word.</div>`;
    }
    else if(bloc.type === 'sous_titre'){
        bodyHtml += `<div class="be-sstitre"><span class="be-num"></span><input class="be-sstitre-txt" value="${beEsc(bloc.texte)}" placeholder="Sous-titre (niveau 3)"></div>`;
    }
    else if(bloc.type === 'commentaire'){
        bodyHtml += `<div class="be-com"><div class="be-com-tete">&#128172; <strong class="be-com-auteur">${beEsc(bloc.auteur || BE_MOI)}</strong> · <span class="be-com-date">${beEsc(bloc.date || BE_AUJOURDHUI)}</span></div><textarea class="be-com-txt" rows="2" placeholder="Votre commentaire">${beEsc(bloc.contenu)}</textarea></div>`;
    }
    else if(bloc.type === 'texte'){
        const edId = 'be-ed-'+bloc.id;
        bodyHtml += beRichToolbar(edId);
        bodyHtml += `<div class="rich-editor" contenteditable="true" id="${edId}" data-bloc-id="${bloc.id}" style="text-align:justify">${bloc.contenu||''}</div>`;
    }
    else if(bloc.type === 'tableau_stories'){
        const sts = BE_DONNEES.stories || [];
        let grp = null;
        bodyHtml += `<table class="tbl be-tab"><thead><tr><th style="width:22%">${bloc.module ? 'Epic' : 'Module › Epic'}</th><th>User story</th><th style="width:96px">Exécution %</th><th style="width:64px">Tâches</th><th style="width:58px">Pts</th><th style="width:120px">Statut</th><th style="width:30px"></th></tr></thead><tbody>`;
        sts.forEach((st, i) => {
            if(bloc.module && (st.module||'') !== bloc.module) return;
            const ctx = bloc.module ? (st.epic || '') : beLib(st.module||'') + (st.epic ? ' › ' + st.epic : '');
            const opts = BE_STATUTS.map(x => `<option ${x === st.statut ? 'selected' : ''}>${x}</option>`).join('') + (st.statut && !BE_STATUTS.includes(st.statut) ? `<option selected>${beEsc(st.statut)}</option>` : '');
            bodyHtml += `<tr><td style="color:#475569;font-size:.78rem">${ctx !== grp ? beEsc(ctx) : ''}</td>
                <td><input class="be-st" data-idx="${i}" data-champ="numero" value="${beEsc(st.numero)}" style="width:76px;font-weight:700"> <textarea class="be-st" data-idx="${i}" data-champ="intitule" rows="2">${beEsc(st.intitule)}</textarea></td>
                <td><input type="number" min="0" max="100" class="be-st" data-idx="${i}" data-champ="niveau" value="${st.niveau ?? 0}"></td>
                <td style="text-align:center;white-space:nowrap">${st.taches_terminees||0}/${st.taches||0}</td>
                <td><input type="number" min="0" class="be-st" data-idx="${i}" data-champ="points" value="${st.points ?? ''}"></td>
                <td><select class="be-st" data-idx="${i}" data-champ="statut">${opts}</select></td>
                <td><button type="button" class="btn-rm" onclick="beRetirerLigne('stories',${i})" title="Retirer">&#10005;</button></td></tr>`;
            grp = ctx;
        });
        bodyHtml += '</tbody></table>';
        if(!sts.some(st => !bloc.module || (st.module||'') === bloc.module)) bodyHtml += `<div class="be-vide">${BE_SANS_SPRINT ? "Aucune user story : rattachez ce rapport à un sprint dans l'en-tête." : 'Aucune user story pour cette fonctionnalité.'}</div>`;
        bodyHtml += `<button type="button" class="be-add-btn" onclick="beAjouterLigne('stories', this)">+ User story</button>`;
    }
    else if(bloc.type === 'tableau_taches'){
        const taches = BE_DONNEES.taches || [];
        bodyHtml += '<table class="tbl be-tab"><thead><tr><th>Tâche</th><th style="width:58px">Pts</th><th style="width:170px">Attribution</th><th style="width:64px">Réalisée</th><th style="width:200px">Observation</th><th style="width:30px"></th></tr></thead><tbody>';
        // Sans filtre et sur plusieurs fonctionnalités : une ligne-titre par fonctionnalité
        const ordre = [];
        taches.forEach((t,i) => { if(!bloc.module || beModuleTache(t) === bloc.module) ordre.push(i); });
        const grpDe = i => beModuleTache(taches[i]);
        const noms = [...new Set(ordre.map(grpDe))];
        const grouper = !bloc.module && noms.length > 1;
        if(grouper){ const hors = noms.indexOf(BE_HORS_US); if(hors >= 0) noms.push(noms.splice(hors, 1)[0]); ordre.sort((a, b) => noms.indexOf(grpDe(a)) - noms.indexOf(grpDe(b)) || a - b); }
        let grpPrec = null;
        ordre.forEach(i => {
            const t = taches[i];
            if(grouper && grpDe(i) !== grpPrec){ grpPrec = grpDe(i); bodyHtml += `<tr class="be-grp"><td colspan="6">${beEsc(beLib(grpPrec))}</td></tr>`; }
            const chk = (t.termine == true || t.termine == 1 || t.termine === '1') ? 'checked' : '';
            bodyHtml += `<tr data-tache-idx="${i}">
                <td><textarea class="be-t" data-idx="${i}" data-champ="tache" rows="1">${beEsc(t.tache)}</textarea>${t.us ? `<small style="display:block;color:#64748b;font-size:.72rem">${beEsc(t.us)}</small>` : ''}</td>
                <td><input type="number" min="0" class="be-t" data-idx="${i}" data-champ="points" value="${t.points ?? 1}"></td>
                <td><input class="be-t" data-idx="${i}" data-champ="attribution" value="${beEsc(t.attribution)}" list="be-membres"></td>
                <td style="text-align:center"><input type="checkbox" class="chk be-tache-chk" data-idx="${i}" ${chk}></td>
                <td><textarea class="be-tache-obs be-t" rows="1" data-idx="${i}" data-champ="observation" title="Repris des blocages et commentaires du développeur sur sa tâche ; modifiable">${beEsc(t.observation)}</textarea></td>
                <td><button type="button" class="btn-rm" onclick="beRetirerLigne('taches',${i})" title="Retirer">&#10005;</button></td></tr>`;
        });
        bodyHtml += '</tbody></table>';
        if(!taches.some(t => !bloc.module || beModuleTache(t) === bloc.module)) bodyHtml += `<div class="be-vide">${BE_SANS_SPRINT ? "Aucune tâche : rattachez ce rapport à un sprint dans l'en-tête." : 'Aucune tâche pour cette fonctionnalité.'}</div>`;
        bodyHtml += `<button type="button" class="be-add-btn" onclick="beAjouterLigne('taches', this)">+ Tâche</button>`;
    }
    else if(bloc.type === 'score'){
        const pts = BE_DONNEES.points_realises||0, tot = BE_DONNEES.total_points||0, pct = BE_DONNEES.score_pourcent||0;
        bodyHtml += `<div class="be-score"><div class="be-score-val">
            <input type="number" min="0" class="be-score-pts" value="${pts}" oninput="beScoreManuel(this)"> /
            <input type="number" min="0" class="be-score-tot" value="${tot}" oninput="beScoreManuel(this)"> points
            (<span class="be-score-pct">${pct}</span>%)</div>
            <div class="be-score-sub">Score global du sprint - calculé selon les tâches cochées, modifiable manuellement</div></div>`;
    }
    else if(bloc.type === 'actions'){
        const items = bloc.items || [];
        bodyHtml += `<div class="be-actions-list" data-bloc-id="${bloc.id}">`;
        items.forEach((a,i) => {
            const resps = a.responsables || (a.responsable ? [a.responsable] : []);
            bodyHtml += beActionRow(i, a.action||'', resps, a.echeance||'');
        });
        bodyHtml += '</div>';
        bodyHtml += `<button type="button" class="btn-add" onclick="beAddActionRow(this)" style="margin-top:6px">+ Action</button>`;
    }
    else if(bloc.type === 'tableau_backlog'){
        bodyHtml += `<table class="tbl be-tab be-items"><thead><tr><th style="width:96px">N°</th><th style="width:22%">Module</th><th>User story</th><th style="width:130px">Priorité</th><th style="width:64px">Pts</th><th style="width:30px"></th></tr></thead><tbody>${(bloc.items || []).map(beLigneBacklog).join('')}</tbody></table>`;
        bodyHtml += `<button type="button" class="be-add-btn" onclick="beAjouterItem(this, beLigneBacklog)">+ User story</button>`;
    }
    else if(bloc.type === 'validation'){
        bodyHtml += `<table class="tbl be-tab be-items"><thead><tr><th style="width:28%">Qualité</th><th>Nom</th><th style="width:150px">Date</th><th style="width:22%">Signature</th><th style="width:30px"></th></tr></thead><tbody>${(bloc.items || []).map(beLigneValidation).join('')}</tbody></table>`;
        bodyHtml += `<button type="button" class="be-add-btn" onclick="beAjouterItem(this, beLigneValidation)">+ Signataire</button>`;
        bodyHtml += `<div class="be-vide" style="margin-top:6px;text-align:left">Le rédacteur et le pilote qui valide sont inscrits automatiquement à la validation du rapport.</div>`;
    }
    else if(bloc.type === 'reference'){
        const rt = bloc.ref_type || 'remarque';
        const st = REF_STYLES[rt] || REF_STYLES.remarque;
        bodyHtml += `<div class="be-ref" style="background:${st.bg};border:1px solid color-mix(in srgb,${st.border} 35%,#fff)" data-ref-type="${rt}">`;
        bodyHtml += `<div class="be-ref-label" style="color:${st.border}"><select class="be-ref-select" onchange="beChangeRefType(this)" style="border:1px solid ${st.border};color:${st.border};background:rgba(255,255,255,.7);border-radius:5px;padding:3px 8px;font-size:.78rem;font-weight:700">`;
        Object.keys(REF_STYLES).forEach(k => {
            bodyHtml += `<option value="${k}" ${k===rt?'selected':''}>${IC[k]} ${REF_STYLES[k].label}</option>`;
        });
        bodyHtml += `</select></div>`;
        const refEdId = 'be-ref-'+bloc.id;
        bodyHtml += beRichToolbar(refEdId);
        bodyHtml += `<div class="rich-editor" contenteditable="true" id="${refEdId}" data-bloc-id="${bloc.id}" style="min-height:48px;background:rgba(255,255,255,.7)">${bloc.contenu||''}</div>`;
        bodyHtml += '</div>';
    }

    bodyHtml += '</div>';
    div.innerHTML = headHtml + bodyHtml;
    secEl.querySelector('.be-blocs').appendChild(div);
}

/* --- Action row HTML --- */
function beActionRow(i, action, resps, echeance){
    const respTags = resps.length ? resps.map(r => `<span class="ms-tag">${r}</span>`).join('') : '<span class="ms-placeholder">— Choisir —</span>';
    let checks = `<label><input type="checkbox" value="Tous" onchange="updateMSDisplay(this)" ${resps.includes('Tous')?'checked':''}> <strong>Tous</strong></label>`;
    collabs.forEach(c => { checks += `<label><input type="checkbox" value="${c}" onchange="updateMSDisplay(this)" ${resps.includes(c)?'checked':''}> ${c}</label>`; });
    return `<div class="be-act-row" data-act-idx="${i}">
        <input type="text" class="be-act-action" value="${(action||'').replace(/"/g,'&quot;')}" placeholder="Action…">
        <div class="ms-wrap" style="min-width:180px"><div class="ms-btn" onclick="toggleMS(this)">${respTags}</div><div class="ms-drop">${checks}</div></div>
        <input type="date" class="be-act-date" value="${echeance||''}">
        <button type="button" class="be-rm" onclick="this.closest('.be-act-row').remove()">&#10005;</button>
    </div>`;
}
function beAddActionRow(btn){
    const list = btn.previousElementSibling;
    const i = list.querySelectorAll('.be-act-row').length;
    list.insertAdjacentHTML('beforeend', beActionRow(i, '', [], ''));
}

/* --- Rendu d'une section --- */
function renderSection(sec, modEl){
    const div = document.createElement('div');
    div.className = 'be-section'; div.dataset.id = sec.id;
    div.innerHTML = `<div class="be-sec-head">
        <span class="be-handle" title="Glisser pour déplacer la section">&#8942;&#8942;</span>
        <span class="be-num-sec"></span>
        <span class="be-reglages">${beCouleurTitre('couleur_sous_titre', 'Couleur de tous les titres de section')}${beTaille('sous_titre', 'Taille de tous les titres de section (1.1, 1.2…) dans le PDF et le Word')}</span>
        <input type="text" class="be-sec-titre" value="${(sec.titre||'').replace(/"/g,'&quot;')}" placeholder="Titre de la section">
        <div class="be-sec-actions">
            <button type="button" onclick="beMoveSection(this,-1)" title="Monter">&#9650;</button>
            <button type="button" onclick="beMoveSection(this,1)" title="Descendre">&#9660;</button>
            <button type="button" onclick="beRemoveSection(this)" title="Supprimer" style="color:#dc2626">&#10005;</button>
        </div>
    </div>
    <div class="be-blocs"></div>
    <div class="be-ajout">
        <button type="button" class="be-ajout-btn" onclick="beMenuAjout(this)">+ Ajouter un bloc</button>
        <div class="be-ajout-menu">
            <button type="button" onclick="beInsertBloc(this,'sous_titre')"><b>Sous-titre</b><small>Titre de niveau 3, numéroté</small></button>
            <button type="button" onclick="beInsertBloc(this,'texte')"><b>Texte</b><small>Paragraphe, listes, couleur du texte</small></button>
            <button type="button" onclick="beInsertBloc(this,'commentaire')"><b>Commentaire</b><small>Signé et daté</small></button>
            <button type="button" onclick="beShowRefMenu(this)"><b>Encadré</b><small>Remarque, attention, à retenir…</small></button>
            <button type="button" onclick="beInsertBloc(this,'tableau_stories')"><b>User stories</b><small>Tableau du sprint, par fonctionnalité</small></button>
            <button type="button" onclick="beInsertBloc(this,'tableau_taches')"><b>Tâches</b><small>Tableau du sprint, par fonctionnalité</small></button>
            <button type="button" onclick="beInsertBloc(this,'verdict')"><b>Verdict du client</b><small>Acceptée, à reprendre, refusée + retours</small></button>
            <button type="button" onclick="beInsertBloc(this,'indicateurs')"><b>Indicateurs</b><small>Points, tâches, user stories, vélocité</small></button>
            <button type="button" onclick="beInsertBloc(this,'score')"><b>Score</b><small>Points réalisés du sprint</small></button>
            <button type="button" onclick="beInsertBloc(this,'actions')"><b>Actions</b><small>Responsables et échéances</small></button>
            <button type="button" onclick="beInsertBloc(this,'tableau_backlog')"><b>Backlog</b><small>User stories issues de la review</small></button>
            <button type="button" onclick="beInsertBloc(this,'validation')"><b>Validation</b><small>Signataires et dates</small></button>
        </div>
    </div>
`;
    const blocsContainer = div.querySelector('.be-blocs');
    (sec.blocs||[]).forEach(b => renderBloc(b, div));
    modEl.querySelector('.be-sections').appendChild(div);
}

/* --- Rendu d'un module --- */
function renderModule(mod){
    const div = document.createElement('div');
    div.className = 'be-module'; div.dataset.id = mod.id;
    div.innerHTML = `<div class="be-mod-head">
        <span class="be-handle" title="Glisser pour déplacer le module">&#8942;&#8942;</span>
        <span class="be-num-mod"></span>
        <div class="be-mod-fields"><span class="be-reglages">${beCouleurTitre('couleur_section', 'Couleur de tous les titres de module')}${beTaille('section', 'Taille de tous les titres de module (1., 2.…) dans le PDF et le Word')}</span>
            <input type="text" class="be-mod-titre" value="${(mod.titre||'').replace(/"/g,'&quot;')}" placeholder="Titre du module">
            <input type="text" class="be-mod-obj" value="${(mod.objectif||'').replace(/"/g,'&quot;')}" placeholder="Objectif ou chapeau du module (facultatif)">
        </div>
        <div class="be-mod-actions">
            <button type="button" onclick="beMoveModule(this,-1)" title="Monter">&#9650;</button>
            <button type="button" onclick="beMoveModule(this,1)" title="Descendre">&#9660;</button>
            <button type="button" onclick="beRemoveModule(this)" title="Supprimer">&#10005;</button>
        </div>
    </div>
    <div class="be-mod-body">
        <div class="be-sections"></div>
        <button type="button" class="be-add-sec" onclick="beAddSection(this)">+ Section</button>
    </div>`;
    (mod.sections||[]).forEach(s => renderSection(s, div));
    document.getElementById('be-modules').appendChild(div);
}

/* --- SVG helpers pour toolbar --- */
function svgPlus(){ return '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'; }
function svgTable(){ return '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>'; }
function svgChart(){ return '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>'; }
function svgList(){ return '<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>'; }

/* --- Ajouts --- */
function beAddModule(){
    const mod = {id:uid(),titre:'',objectif:'',sections:[{id:uid(),titre:'',blocs:[{id:uid(),type:'texte',contenu:''}]}]};
    beModules.push(mod);
    renderModule(mod);
    beRenumeroter();
    document.querySelector('#be-modules > .be-module:last-child .be-mod-titre')?.focus();
}
function beAddSection(btn){
    const modEl = btn.closest('.be-module');
    const sec = {id:uid(),titre:'',blocs:[{id:uid(),type:'texte',contenu:''}]};
    renderSection(sec, modEl);
    beRenumeroter();
    modEl.querySelector('.be-section:last-child .be-sec-titre')?.focus();
}
function beMenuAjout(btn){
    const m = btn.nextElementSibling;
    document.querySelectorAll('.be-ajout-menu.ouvert').forEach(x => { if(x !== m) x.classList.remove('ouvert'); });
    m.classList.toggle('ouvert');
}
document.addEventListener('click', e => { if(!e.target.closest('.be-ajout')) document.querySelectorAll('.be-ajout-menu.ouvert').forEach(x => x.classList.remove('ouvert')); });
function beInsertBloc(btn, type){
    btn.closest('.be-ajout-menu')?.classList.remove('ouvert');
    const secEl = btn.closest('.be-section');
    const bloc = {id:uid(),type:type};
    if(type === 'texte') bloc.contenu = '';
    if(type === 'verdict'){ bloc.verdict = ''; bloc.contenu = ''; }
    if(type === 'actions') bloc.items = [];
    if(type === 'reference'){ bloc.ref_type = 'remarque'; bloc.contenu = ''; }
    if(type === 'sous_titre') bloc.texte = '';
    if(type === 'tableau_backlog') bloc.items = [{}];
    if(type === 'validation') bloc.items = [{role:'Rédigé par'}, {role:'Validé par (pilote)'}, {role:'Représentant du client'}];
    if(type === 'commentaire'){ bloc.contenu = ''; bloc.auteur = BE_MOI; bloc.date = BE_AUJOURDHUI; }
    // Un tableau ajouté dans une section de fonctionnalité en reprend le module
    if(type === 'tableau_stories' || type === 'tableau_taches'){ const t = secEl.querySelector('.be-sec-titre')?.value; if(beModulesSprint().includes(t)) bloc.module = t; }
    renderBloc(bloc, secEl);
    beRenumeroter();
}

/* --- Menu reference --- */
function beShowRefMenu(btn){
    let menu = btn.parentElement.querySelector('.be-ref-menu');
    if(menu){ menu.remove(); return; }
    menu = document.createElement('div');
    menu.className = 'be-ref-menu';
    // Positionné sur la page, au-dessus de tout (les modules masquent ce qui dépasse)
    const r = btn.getBoundingClientRect();
    menu.style.cssText = 'position:fixed;background:#fff;border:1px solid #d1d5db;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.16);z-index:10000;padding:4px;display:flex;flex-wrap:wrap;gap:2px;width:280px';
    menu.style.left = Math.min(r.left, window.innerWidth - 290) + 'px';
    menu.style.top = (r.bottom + 4 + 260 > window.innerHeight ? Math.max(8, r.top - 264) : r.bottom + 4) + 'px';
    Object.keys(REF_STYLES).forEach(k => {
        const s = REF_STYLES[k];
        menu.innerHTML += `<button type="button" onclick="beInsertRef(this,'${k}')" style="display:flex;align-items:center;gap:4px;padding:5px 8px;border:none;background:${s.bg};border-radius:5px;cursor:pointer;font-size:.76rem;font-weight:600;color:${s.border};flex:1 0 45%">${IC[k]} ${s.label}</button>`;
    });
    btn.parentElement.appendChild(menu);
    const fermer = () => { menu.remove(); document.removeEventListener('click', h); window.removeEventListener('scroll', fermer, true); };
    function h(e){ if(!menu.contains(e.target) && !btn.contains(e.target)) fermer(); }
    setTimeout(() => { document.addEventListener('click', h); window.addEventListener('scroll', fermer, true); }, 0);
}
function beInsertRef(btn, refType){
    btn.closest('.be-ajout-menu')?.classList.remove('ouvert');
    const secEl = btn.closest('.be-section');
    const bloc = {id:uid(),type:'reference',ref_type:refType,contenu:''};
    renderBloc(bloc, secEl);
    const menu = btn.closest('.be-ref-menu');
    if(menu) menu.remove();
}

/* --- Change reference type --- */
function beChangeRefType(sel){
    const rt = sel.value;
    const st = REF_STYLES[rt] || REF_STYLES.remarque;
    const refDiv = sel.closest('.be-ref');
    refDiv.style.background = st.bg;
    refDiv.style.borderColor = `color-mix(in srgb,${st.border} 35%,#fff)`;
    refDiv.dataset.refType = rt;
    const label = sel.closest('.be-ref-label');
    if(label) label.style.color = st.border;
    sel.style.borderColor = st.border;
    sel.style.color = st.border;
}

/* --- Déplacer / Supprimer --- */
function beMoveBloc(btn, dir){
    const bloc = btn.closest('.be-bloc');
    const container = bloc.parentElement;
    const blocs = Array.from(container.querySelectorAll(':scope > .be-bloc'));
    const idx = blocs.indexOf(bloc);
    if(dir === -1 && idx > 0) container.insertBefore(bloc, blocs[idx-1]);
    else if(dir === 1 && idx < blocs.length-1) container.insertBefore(blocs[idx+1], bloc);
}
function beRemoveBloc(btn){ if(confirm('Supprimer ce bloc ?')) btn.closest('.be-bloc').remove(); }
function beMoveSection(btn, dir){
    const sec = btn.closest('.be-section');
    const container = sec.parentElement;
    const secs = Array.from(container.querySelectorAll(':scope > .be-section'));
    const idx = secs.indexOf(sec);
    if(dir === -1 && idx > 0) container.insertBefore(sec, secs[idx-1]);
    else if(dir === 1 && idx < secs.length-1) container.insertBefore(secs[idx+1], sec);
}
function beRemoveSection(btn){ if(confirm('Supprimer cette section et tous ses blocs ?')) btn.closest('.be-section').remove(); }
function beMoveModule(btn, dir){
    const mod = btn.closest('.be-module');
    const container = mod.parentElement;
    const mods = Array.from(container.querySelectorAll(':scope > .be-module'));
    const idx = mods.indexOf(mod);
    if(dir === -1 && idx > 0) container.insertBefore(mod, mods[idx-1]);
    else if(dir === 1 && idx < mods.length-1) container.insertBefore(mods[idx+1], mod);
}
function beRemoveModule(btn){ if(confirm('Supprimer ce module et tout son contenu ?')) btn.closest('.be-module').remove(); }

/* --- Serialisation DOM → JSON --- */
function beSerialize(){
    const data = {
        format: 'blocs_v1',
        sprint: BE_DONNEES.sprint || '',
        projet: BE_DONNEES.projet || '',
        date_debut: BE_DONNEES.date_debut || '',
        date_fin: BE_DONNEES.date_fin || '',
        total_points: BE_DONNEES.total_points || 0,
        points_realises: BE_DONNEES.points_realises || 0,
        score_pourcent: BE_DONNEES.score_pourcent || 0,
        score_manuel: !!BE_DONNEES.score_manuel,
        par_dev: BE_DONNEES.par_dev || [],
        libelles_modules: BE_DONNEES.libelles_modules || {},
        stories: BE_DONNEES.stories || [],
        taches: [],
        modules: []
    };
    /* Tableaux : les données éditées (BE_DONNEES, tenu à jour à chaque saisie) */
    data.taches = (BE_DONNEES.taches || []).map(t => ({...t}));
    data.stories = (BE_DONNEES.stories || []).map(x => ({...x}));
    /* Modules */
    document.querySelectorAll('#be-modules > .be-module').forEach(modEl => {
        const mod = {
            id: modEl.dataset.id,
            titre: modEl.querySelector('.be-mod-titre').value,
            objectif: modEl.querySelector('.be-mod-obj').value,
            sections: []
        };
        modEl.querySelectorAll('.be-section').forEach(secEl => {
            const sec = {
                id: secEl.dataset.id,
                titre: secEl.querySelector('.be-sec-titre').value,
                blocs: []
            };
            secEl.querySelectorAll('.be-blocs > .be-bloc').forEach(blcEl => {
                const type = blcEl.dataset.type;
                const bloc = {id: blcEl.dataset.id, type: type};
                if(type === 'texte'){
                    const ed = blcEl.querySelector('.rich-editor');
                    bloc.contenu = ed ? ed.innerHTML.trim() : '';
                }
                else if(type === 'verdict'){
                    bloc.verdict = blcEl.querySelector('.be-verdict-choix input:checked')?.value || '';
                    const ed = blcEl.querySelector('.rich-editor');
                    bloc.contenu = ed ? ed.innerHTML.trim() : '';
                }
                else if(type === 'reference'){
                    const refDiv = blcEl.querySelector('.be-ref');
                    const sel = blcEl.querySelector('.be-ref-select');
                    bloc.ref_type = sel ? sel.value : (refDiv ? refDiv.dataset.refType : 'remarque');
                    const ed = blcEl.querySelector('.rich-editor');
                    bloc.contenu = ed ? ed.innerHTML.trim() : '';
                }
                else if(type === 'actions'){
                    bloc.items = [];
                    blcEl.querySelectorAll('.be-act-row').forEach(row => {
                        const checked = row.querySelectorAll('.ms-drop input:checked');
                        bloc.items.push({
                            action: row.querySelector('.be-act-action').value,
                            responsables: Array.from(checked).map(c => c.value),
                            echeance: row.querySelector('.be-act-date').value
                        });
                    });
                }
                else if(type === 'tableau_backlog' || type === 'validation') bloc.items = beLireItems(blcEl);
                else if(type === 'sous_titre') bloc.texte = blcEl.querySelector('.be-sstitre-txt')?.value || '';
                else if(type === 'commentaire'){
                    bloc.contenu = blcEl.querySelector('.be-com-txt')?.value || '';
                    bloc.auteur = blcEl.querySelector('.be-com-auteur')?.textContent || '';
                    bloc.date = blcEl.querySelector('.be-com-date')?.textContent || '';
                }
                else if(type === 'tableau_stories' || type === 'tableau_taches') bloc.module = blcEl.dataset.module || null;
                if(blcEl.dataset.couleur) bloc.couleur = blcEl.dataset.couleur;
                if(blcEl.classList.contains('demi')) bloc.largeur = 'demi';
                sec.blocs.push(bloc);
            });
            mod.sections.push(sec);
        });
        data.modules.push(mod);
    });
    return data;
}

/* --- Score dynamique : recalcul depuis les cases "OK" du tableau des tâches --- */
function beRecalculerScore(){
    const taches = BE_DONNEES.taches || [];
    let tot = 0, pts = 0;
    const parDev = {};
    taches.forEach((t,i) => {
        const p = parseInt(t.points, 10) || 1;
        const chk = document.querySelector(`.be-tache-chk[data-idx="${i}"]`);
        const fait = chk ? chk.checked : !!t.termine;
        const nom = t.attribution || '—';
        tot += p; if(fait) pts += p;
        parDev[nom] = parDev[nom] || {nom, total:0, realise:0};
        parDev[nom].total += p; if(fait) parDev[nom].realise += p;
        t.termine = fait;
    });
    const pct = tot > 0 ? Math.round(pts / tot * 100) : 0;
    BE_DONNEES.total_points = tot;
    BE_DONNEES.points_realises = pts;
    BE_DONNEES.score_pourcent = pct;
    BE_DONNEES.par_dev = Object.values(parDev).map(d => ({...d, pourcent: d.total > 0 ? Math.round(d.realise / d.total * 100) : 0}));
    BE_DONNEES.score_manuel = false;
    beAfficherScore();
}
function beAfficherScore(source){
    const {points_realises:pts=0, total_points:tot=0, score_pourcent:pct=0} = BE_DONNEES;
    document.querySelectorAll('.be-score').forEach(box => {
        const p = box.querySelector('.be-score-pts'), t = box.querySelector('.be-score-tot');
        if(p && p !== source) p.value = pts;
        if(t && t !== source) t.value = tot;
        const s = box.querySelector('.be-score-pct'); if(s) s.textContent = pct;
    });
    const h = document.getElementById('be-score-header'); if(h) h.textContent = pct + '%';
}
function beScoreManuel(input){
    const box = input.closest('.be-score');
    const pts = Math.max(0, parseInt(box.querySelector('.be-score-pts').value, 10) || 0);
    const tot = Math.max(0, parseInt(box.querySelector('.be-score-tot').value, 10) || 0);
    BE_DONNEES.points_realises = pts;
    BE_DONNEES.total_points = tot;
    BE_DONNEES.score_pourcent = tot > 0 ? Math.round(pts / tot * 100) : 0;
    BE_DONNEES.score_manuel = true;
    beAfficherScore(input);
}
document.getElementById('be-modules').addEventListener('change', e => {
    if(e.target.classList.contains('be-tache-chk')){
        const idx = e.target.dataset.idx;
        document.querySelectorAll(`.be-tache-chk[data-idx="${idx}"]`).forEach(c => c.checked = e.target.checked);
        beRecalculerScore();
    }
});

/* --- Lignes des tableaux « Backlog issu de la review » et « Validation » --- */
const BE_MOSCOW = @json(\App\Models\UserStory::MOSCOW);
function beLigneBacklog(it = {}){
    const prio = `<option value="">—</option>` + Object.entries(BE_MOSCOW).map(([k, l]) => `<option value="${k}" ${k === (it.moscow||'') ? 'selected' : ''}>${l}</option>`).join('');
    return `<tr><td><input type="hidden" data-champ="user_story_id" value="${beEsc(it.user_story_id)}"><input data-champ="numero" value="${beEsc(it.numero)}" placeholder="Nouvelle" ${it.user_story_id ? 'readonly title="User story créée au Product Backlog"' : 'title="Laisser vide : la user story est créée au Product Backlog à la validation"'}></td><td><input data-champ="module" value="${beEsc(it.module)}" list="be-modules-liste"></td>
        <td><textarea data-champ="intitule" rows="1" placeholder="En tant que…, je veux… afin de…">${beEsc(it.intitule)}</textarea></td>
        <td><select data-champ="moscow">${prio}</select></td><td><input type="number" min="0" data-champ="points" value="${it.points ?? ''}"></td>
        <td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()" title="Retirer">&#10005;</button></td></tr>`;
}
function beLigneValidation(it = {}){
    return `<tr><td><input data-champ="role" value="${beEsc(it.role)}" placeholder="Qualité"></td><td><input data-champ="nom" value="${beEsc(it.nom)}" list="be-membres" placeholder="Nom"></td>
        <td><input type="date" data-champ="date" value="${beEsc(it.date)}"></td><td class="be-sign"></td>
        <td><button type="button" class="btn-rm" onclick="this.closest('tr').remove()" title="Retirer">&#10005;</button></td></tr>`;
}
function beAjouterItem(btn, ligne){
    const tbody = btn.closest('.be-bloc').querySelector('.be-items tbody');
    tbody.insertAdjacentHTML('beforeend', ligne());
    tbody.lastElementChild.querySelector('input, textarea')?.focus();
    beAjusterHauteurs();
}
function beLireItems(blcEl){
    return [...blcEl.querySelectorAll('.be-items tbody tr')].map(tr => {
        const o = {};
        tr.querySelectorAll('[data-champ]').forEach(c => o[c.dataset.champ] = c.value);
        return o;
    });
}

/* --- Tailles des titres (mise en forme du PDF et du Word), réglées sur les titres eux-mêmes --- */
const BE_TAILLES = [9, 10, 10.5, 11, 12, 13, 14, 16, 18, 20, 22, 24, 28];
function beTaille(cle, titre){
    const v = document.getElementById('mef-' + cle)?.value || '';
    return `<select class="taille" data-cle="${cle}" title="${titre}"><option value="">Aa</option>${BE_TAILLES.map(pt => `<option value="${pt}" ${String(pt) === v ? 'selected' : ''}>${String(pt).replace('.', ',')} pt</option>`).join('')}</select>`;
}
const BE_COULEURS_TITRES = @json(array_values(\App\Http\Controllers\CompteRenduController::COULEURS));
function beCouleurTitre(cle, titre){
    const v = document.getElementById('mef-' + cle)?.value || '';
    return `<span class="be-ctitre"><button type="button" class="be-ctitre-btn" title="${titre}" onclick="this.nextElementSibling.classList.toggle('ouvert')"><span style="background:${v ? '#' + v : 'var(--bleu,#1F4E79)'}"></span></button><span class="be-ctitre-menu">${BE_COULEURS_TITRES.map(([lib, hex]) => `<button type="button" title="${lib}" style="background:#${hex}" onclick="beChoisirCouleurTitre('${cle}','${hex}')"></button>`).join('')}<button type="button" class="defaut" title="Couleur par défaut" onclick="beChoisirCouleurTitre('${cle}','')">&#8634;</button></span></span>`;
}
function beChoisirCouleurTitre(cle, hex){
    const champ = document.getElementById('mef-' + cle); if(champ) champ.value = hex;
    document.querySelectorAll('.be-ctitre-menu.ouvert').forEach(m => m.classList.remove('ouvert'));
    beAppliquerTailles();
}
document.addEventListener('click', e => { if(!e.target.closest('.be-ctitre')) document.querySelectorAll('.be-ctitre-menu.ouvert').forEach(m => m.classList.remove('ouvert')); });
function beAppliquerTailles(){
    const z = document.getElementById('be-modules'); if(!z) return;
    [['section', '--t-mod'], ['sous_titre', '--t-sec'], ['titre', '--t-doc']].forEach(([cle, v]) => {
        const pt = parseFloat(document.getElementById('mef-' + cle)?.value || '');
        document.querySelector('.cr-edit').style[pt ? 'setProperty' : 'removeProperty'](v, pt ? (pt * 1.333) + 'px' : '');
    });
    // Couleurs des titres : écran et pastilles des réglages
    [['couleur_section', '--c-mod'], ['couleur_sous_titre', '--c-sec']].forEach(([cle, v]) => {
        const hex = document.getElementById('mef-' + cle)?.value || '';
        document.querySelector('.cr-edit').style[hex ? 'setProperty' : 'removeProperty'](v, hex ? '#' + hex : '');
        document.querySelectorAll(`.be-ctitre-btn[title^="${cle === 'couleur_section' ? 'Couleur de tous les titres de module' : 'Couleur de tous les titres de section'}"] span`).forEach(s => s.style.background = hex ? '#' + hex : 'var(--bleu,#1F4E79)');
    });
}
document.addEventListener('change', e => {
    if(!e.target.matches('select.taille')) return;
    const cle = e.target.dataset.cle, champ = document.getElementById('mef-' + cle);
    if(champ) champ.value = e.target.value;
    document.querySelectorAll(`select.taille[data-cle="${cle}"]`).forEach(x => x.value = e.target.value);
    beAppliquerTailles();
});

/* --- Couleur, largeur, fonctionnalité d'un bloc --- */
const BLOC_COULEURS = @json(\App\Http\Controllers\CompteRenduController::BLOC_COULEURS);
const BE_MOI = @json($user->nomComplet());
const BE_VERDICTS = @json($circuit['verdicts'] ?? \App\Services\RapportReview::VERDICTS);
const BE_CARTES = @json(\App\Services\RapportReview::cartes($circuit['indicateurs'] ?? \App\Services\RapportReview::indicateurs($donnees, $cr->sprint)));
const BE_AUJOURDHUI = @json(now()->format('d/m/Y'));
const BE_HORS_US = @json(\App\Http\Controllers\CompteRenduController::HORS_US);
const BE_SANS_SPRINT = @json(!$cr->sprint_id);
function beModuleTache(t){ return t.module || (t.us_contexte ? t.us_contexte.split(' › ')[0] : BE_HORS_US); }
/* Libellé d'une fonctionnalité : reformulation du pilote, sinon le nom importé */
function beLib(m){ return (BE_DONNEES.libelles_modules || {})[m] || m; }
function beLibsPanneau(){
    const g = document.getElementById('be-libs-grille'); if(!g) return;
    const mods = beModulesSprint();
    document.getElementById('be-libs').style.display = mods.length ? '' : 'none';
    g.innerHTML = mods.map(m => `<span class="orig" title="${beEsc(m)}">${beEsc(m)}</span><input value="${beEsc((BE_DONNEES.libelles_modules || {})[m] || '')}" placeholder="${beEsc(m)}" maxlength="150" data-orig="${beEsc(m)}" onchange="beRenommerModule(this)">`).join('');
}
/* Reformulation : tableaux et regroupements suivent ; les titres de section encore à l'ancien libellé aussi */
function beRenommerModule(inp){
    const orig = inp.dataset.orig, avant = beLib(orig), lib = inp.value.trim();
    BE_DONNEES.libelles_modules = BE_DONNEES.libelles_modules || {};
    if(lib && lib !== orig) BE_DONNEES.libelles_modules[orig] = lib; else delete BE_DONNEES.libelles_modules[orig];
    const apres = beLib(orig);
    document.querySelectorAll('.be-sec-titre').forEach(t => { if(t.value.trim() === avant) t.value = apres; });
    beRerendre();
}
function beModulesSprint(){
    const m = [...(BE_DONNEES.stories || []).map(s => s.module || ''), ...(BE_DONNEES.taches || []).map(beModuleTache)].filter(Boolean);
    return [...new Set(m)];
}
function beAppliquerCouleur(div, k){
    const c = BLOC_COULEURS[k];
    if(c){ div.dataset.couleur = k; div.style.setProperty('--bc-bg', '#' + c[1]); div.style.setProperty('--bc-bd', '#' + c[2]); }
    else { delete div.dataset.couleur; div.style.removeProperty('--bc-bg'); div.style.removeProperty('--bc-bd'); }
}
function beMenuCouleur(btn){
    const m = btn.nextElementSibling;
    document.querySelectorAll('.be-coul-menu.ouvert').forEach(x => { if(x !== m) x.classList.remove('ouvert'); });
    m.classList.toggle('ouvert');
}
function beChoisirCouleur(btn, k){ beAppliquerCouleur(btn.closest('.be-bloc'), k); btn.closest('.be-coul-menu').classList.remove('ouvert'); }
document.addEventListener('click', e => { if(!e.target.closest('.be-coul-wrap')) document.querySelectorAll('.be-coul-menu.ouvert').forEach(x => x.classList.remove('ouvert')); });
function beLargeur(btn){ btn.closest('.be-bloc').classList.toggle('demi'); }
function beFiltrerModule(sel){
    const b = sel.closest('.be-bloc');
    if(sel.value) b.dataset.module = sel.value; else delete b.dataset.module;
    beRerendre();
}

/* --- Tableaux modifiables : chaque saisie met à jour les données --- */
const BE_STATUTS = @json(array_values(\App\Models\UserStory::STATUTS));
function beEsc(v){ return String(v ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
/* Zones de texte des tableaux : hauteur ajustée au contenu */
function beAjusterHauteurs(){ document.querySelectorAll('.be-tab textarea').forEach(t => { t.style.height = 'auto'; t.style.height = t.scrollHeight + 'px'; }); }
document.getElementById('be-modules').addEventListener('input', e => {
    if(e.target.matches('.be-tab textarea')){ e.target.style.height = 'auto'; e.target.style.height = e.target.scrollHeight + 'px'; }
    const el = e.target, liste = el.classList.contains('be-st') ? 'stories' : (el.classList.contains('be-t') ? 'taches' : null);
    if(!liste) return;
    const ligne = (BE_DONNEES[liste] || [])[+el.dataset.idx]; if(!ligne) return;
    ligne[el.dataset.champ] = el.type === 'number' ? (el.value === '' ? null : +el.value) : el.value;
    // Un même tableau peut figurer deux fois : les copies suivent
    document.querySelectorAll(`.${el.classList.contains('be-st') ? 'be-st' : 'be-t'}[data-idx="${el.dataset.idx}"][data-champ="${el.dataset.champ}"]`).forEach(c => { if(c !== el) c.value = el.value; });
    if(liste === 'taches' && el.dataset.champ === 'points' && !BE_DONNEES.score_manuel) beRecalculerScore();
});
document.getElementById('be-modules').addEventListener('change', e => { if(e.target.tagName === 'SELECT' && e.target.classList.contains('be-st')) e.target.dispatchEvent(new Event('input', {bubbles:true})); });
function beRerendre(){
    const d = beSerialize();
    const c = document.getElementById('be-modules'); c.innerHTML = '';
    d.modules.forEach(m => renderModule(m));
    BE_DONNEES.score_manuel ? beAfficherScore() : beRecalculerScore();
    beRenumeroter();
    beAjusterHauteurs();
    beLibsPanneau();
}
function beAjouterLigne(liste, btn){
    // La ligne ajoutée appartient à la fonctionnalité du tableau
    const module = btn && btn.closest('.be-bloc') ? (btn.closest('.be-bloc').dataset.module || '') : '';
    BE_DONNEES[liste] = BE_DONNEES[liste] || [];
    BE_DONNEES[liste].push(liste === 'taches' ? {tache:'', points:1, attribution:'', termine:false, observation:'', module: module || null} : {numero:'', intitule:'', module:module, epic:'', niveau:0, points:null, statut:BE_STATUTS[0], taches:0, taches_terminees:0});
    beRerendre();
}
function beRetirerLigne(liste, i){
    if(!confirm('Retirer cette ligne ?')) return;
    BE_DONNEES[liste].splice(i, 1);
    beRerendre();
}
function beActualiser(btn){
    if(!confirm("Reprendre l'état actuel du sprint ? Les tableaux sont recalculés ; les observations que vous avez modifiées sont conservées, les autres reprennent les derniers commentaires des développeurs.")) return;
    btn.disabled = true;
    fetch(@json(route('comptes-rendus.sprint-actuel', $cr)), {headers: {'Accept': 'application/json'}, redirect: 'error'})
        .then(r => { if(!r.ok) throw new Error(); return r.json(); })
        .then(j => {
            // Observation retouchée (différente de la version pré-remplie) : conservée ; sinon elle reprend les nouveaux commentaires
            const cle = t => t.tache_id ? 'id' + t.tache_id : 't' + t.tache;
            const obs = {};
            (BE_DONNEES.taches || []).forEach(t => { if(t.tache && (t.observation || '') !== (t.observation_auto || '')) obs[cle(t)] = t.observation; });
            BE_DONNEES.stories = j.stories;
            BE_DONNEES.taches = j.taches.map(t => ({...t, observation: obs[cle(t)] ?? t.observation}));
            BE_DONNEES.score_manuel = false;
            beRerendre();
        })
        .catch(() => { alert("Actualisation impossible : rechargez la page et réessayez."); btn.disabled = false; });
}

/* --- Glisser-déposer : modules, sections et blocs, par leur poignée ☰ --- */
let beGlisse = null;
const BE_GLISSABLES = '.be-module, .be-section, .be-bloc';
document.getElementById('be-modules').addEventListener('mousedown', e => {
    const h = e.target.closest('.be-handle'); if(!h) return;
    const item = h.closest(BE_GLISSABLES); if(item) item.draggable = true;
});
document.getElementById('be-modules').addEventListener('dragstart', e => {
    beGlisse = e.target.closest && e.target.closest(BE_GLISSABLES);
    if(!beGlisse || !beGlisse.draggable){ beGlisse = null; return; }
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', '');
    setTimeout(() => beGlisse && beGlisse.classList.add('be-glisse'), 0);
});
document.getElementById('be-modules').addEventListener('dragover', e => {
    if(!beGlisse) return;
    const genre = beGlisse.classList.contains('be-module') ? 'be-module' : (beGlisse.classList.contains('be-section') ? 'be-section' : 'be-bloc');
    const conteneur = {'be-module': '#be-modules', 'be-section': '.be-sections', 'be-bloc': '.be-blocs'}[genre];
    let cible = e.target.closest('.' + genre);
    if(cible && cible !== beGlisse && !beGlisse.contains(cible)){
        e.preventDefault();
        const r = cible.getBoundingClientRect();
        const avant = cible.classList.contains('demi') ? e.clientX < r.left + r.width / 2 : e.clientY < r.top + r.height / 2;
        cible.parentElement.insertBefore(beGlisse, avant ? cible : cible.nextSibling);
        return;
    }
    // Section ou bloc déposé dans un conteneur vide (autre module, autre section)
    const zone = e.target.closest(conteneur);
    if(zone && genre !== 'be-module' && !beGlisse.contains(zone) && !zone.querySelector(':scope > .' + genre)){ e.preventDefault(); zone.appendChild(beGlisse); }
    else if(zone) e.preventDefault();
});
document.getElementById('be-modules').addEventListener('drop', e => { if(beGlisse) e.preventDefault(); });
document.getElementById('be-modules').addEventListener('dragend', () => {
    if(!beGlisse) return;
    beGlisse.classList.remove('be-glisse'); beGlisse.draggable = false; beGlisse = null;
    beRenumeroter();
});

/* --- Init : rendu initial --- */
beModules.forEach(m => renderModule(m));
if(!BE_DONNEES.score_manuel) beRecalculerScore();
beAjusterHauteurs();
beAppliquerTailles();
beLibsPanneau();
window.addEventListener('resize', beAjusterHauteurs);

/* Préfixe des listes hiérarchiques = numéro module.section, recalculé à chaque déplacement */
function beRenumeroter(){
    document.querySelectorAll('#be-modules > .be-module').forEach((modEl, mi) => {
        const nm = modEl.querySelector('.be-num-mod'); if(nm) nm.textContent = mi + 1;
        modEl.querySelectorAll('.be-section').forEach((secEl, si) => {
            const ns = secEl.querySelector('.be-num-sec'); if(ns) ns.textContent = `${mi+1}.${si+1}`;
            secEl.querySelectorAll('.rich-editor').forEach(ed => ed.style.setProperty('--num-prefix', `'${mi+1}.${si+1}.'`));
            secEl.querySelectorAll(':scope > .be-blocs > .be-bloc[data-type="sous_titre"] .be-num').forEach((n, k) => n.textContent = `${mi+1}.${si+1}.${k+1}`);
        });
    });
}
beRenumeroter();
new MutationObserver(muts => {
    if(muts.some(m => [...m.addedNodes, ...m.removedNodes].some(n => n.classList && (n.classList.contains('be-module') || n.classList.contains('be-section') || n.classList.contains('be-bloc'))))) beRenumeroter();
}).observe(document.getElementById('be-modules'), {childList:true, subtree:true});

} /* fin if(!estReunion) */

/* === Confirmation === */
/* Étapes du rapport de review : enregistrer d'abord, puis transmettre / valider / passer la main */
function etapeCR(formId, question){
    if(!confirm(question)) return;
    syncBeforeSubmit();
    const form = document.getElementById('cr-form');
    fetch(form.action, {method:'POST', body:new FormData(form), headers:{'X-Requested-With':'XMLHttpRequest'}})
        .then(r => { if(!r.ok) throw new Error(); document.getElementById(formId).submit(); })
        .catch(() => alert("L'enregistrement a échoué : corrigez le rapport puis réessayez."));
}
/* Passer la main au responsable du projet (ou la reprendre) : le rapport est enregistré d'abord */
function mainCR(a, question){
    document.querySelector('#main-form [name=a]').value = a;
    etapeCR('main-form', question);
}
function renvoyerCR(){
    const motif = prompt('Que doit reprendre le rédacteur ?');
    if(motif === null) return;
    if(!motif.trim()){ alert('Indiquez ce qu\'il faut reprendre.'); return; }
    const f = document.getElementById('renvoi-form');
    f.querySelector('[name=motif]').value = motif.trim();
    f.submit();
}
function confirmerCR(){
    if(confirm('Confirmer definitivement ce compte rendu ? Un fichier DOCX sera genere.')){
        syncBeforeSubmit();
        const form = document.getElementById('cr-form');
        const data = new FormData(form);
        fetch(form.action, {method:'POST', body:data, headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(() => document.getElementById('confirm-form').submit())
            .catch(() => document.getElementById('confirm-form').submit());
    }
}

/* === Sync avant soumission === */
function syncBeforeSubmit(){
    /* Reunion : sync rich editors */
    syncEditors();
    /* Demo : serialize bloc data */
    if(!estReunion && typeof beSerialize === 'function'){
        document.getElementById('bloc-data-input').value = JSON.stringify(beSerialize());
    }
}
document.getElementById('cr-form').addEventListener('submit', function(e){ syncBeforeSubmit(); });
</script>
@endsection
