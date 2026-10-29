@extends('layouts.app')
@section('sousTitre', 'Comptes rendus')
@section('titre', 'Rédiger - ' . $cr->titre)
@section('contenu')
<style>
.cr-edit{max-width:920px;margin:0 auto}
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
.tbl th{background:var(--bleu,#1F4E79);color:#fff;padding:8px 10px;text-align:left;font-weight:600}
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
.lh-menu{display:none;position:absolute;top:100%;left:0;z-index:50;background:#fff;border:1px solid #d1d5db;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.12);padding:3px;flex-direction:column;min-width:80px}
.lh-menu.open{display:flex}
.lh-menu button{text-align:left}
.rich-toolbar button.active{background:var(--bleu,#1F4E79);color:#fff}
.rich-editor{border:1px solid #d1d5db;border-radius:0 0 8px 8px;padding:10px 14px;min-height:80px;font-size:.9rem;color:var(--bleu,#1F4E79);line-height:1.6;outline:none;text-align:justify}
.rich-editor:focus{border-color:var(--bleu,#1F4E79);box-shadow:0 0 0 3px rgba(31,78,121,.1)}
.rich-editor ul,.rich-editor ol{margin:4px 0 4px 20px}
.rich-editor *{font-family:inherit!important;font-size:inherit!important;color:inherit!important;background:none!important;line-height:inherit!important}
.rich-editor a{text-decoration:none!important;pointer-events:none}
/* Couleur du texte (palette de l'éditeur) */
.coul-a{display:inline-block;font-weight:700;border-bottom:3px solid #2E74B5;line-height:1;padding:0 1px}
.lh-menu.coul-menu{width:150px;flex-wrap:wrap;gap:4px;padding:6px}
.lh-menu.coul-menu.open{display:flex}
.lh-menu .coul-pastille{width:26px;height:22px;padding:0;border-radius:5px;border:1px solid rgba(0,0,0,.12);background:var(--c)!important}
.lh-menu .coul-pastille:hover{outline:2px solid #93c5fd;outline-offset:1px}
.lh-menu .coul-auto{flex-basis:100%;font-size:.74rem;text-align:center}
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
.odj-item .num{position:absolute;left:14px;top:20px;background:var(--bleu,#1F4E79);color:#fff;font-size:.78rem;font-weight:700;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center}
.odj-item textarea{padding:10px 14px;font-size:.92rem;border-radius:8px;border:1px solid #d1d5db;font-family:inherit}
/* Bottom bar */
.cr-bottom{display:flex;gap:12px;justify-content:flex-end;align-items:center;flex-wrap:wrap;margin-top:24px;padding-top:16px;border-top:1px solid #e2e8f0}
.cr-bottom .btn-retour{padding:9px 20px;border-radius:8px;font-size:.85rem;font-weight:500;text-decoration:none;color:#64748b;background:#f1f5f9;border:1px solid #e2e8f0;transition:all .15s}
.cr-bottom .btn-retour:hover{background:#e2e8f0;color:#334155}
.cr-bottom .btn-save{padding:9px 24px;border-radius:8px;font-size:.85rem;font-weight:600;color:#fff;background:var(--accent,#2E74B5);border:none;cursor:pointer;transition:all .15s;box-shadow:0 1px 3px rgba(46,116,181,.2)}
.cr-bottom .btn-save:hover{background:var(--bleu,#1F4E79)}
.cr-bottom .btn-confirm{padding:9px 24px;border-radius:8px;font-size:.85rem;font-weight:600;color:#fff;background:var(--ok,#16a34a);border:none;cursor:pointer;transition:all .15s;box-shadow:0 1px 3px rgba(22,163,106,.2)}
.cr-bottom .btn-confirm:hover{background:#15803d}
@media(max-width:900px){.tbl{display:block;overflow-x:auto}.cr-bottom{justify-content:stretch}.cr-bottom .btn-retour,.cr-bottom .btn-save,.cr-bottom .btn-confirm{flex:1;text-align:center;justify-content:center}}
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
.be-blocs{padding:10px 14px}
.be-bloc{background:#fff;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;position:relative;transition:box-shadow .15s}
.be-bloc:hover{box-shadow:0 2px 8px rgba(31,78,121,.08)}
.be-bloc-head{display:flex;align-items:center;gap:6px;padding:5px 10px;background:#fafbfc;border-bottom:1px solid #f1f5f9;border-radius:8px 8px 0 0;font-size:.76rem;color:#64748b}
.be-bloc-head .be-handle{cursor:grab;color:#b0b8c4;font-size:.9rem;padding:0 3px}
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
/* Actions bloc */
.be-act-row{display:flex;gap:6px;align-items:center;margin-bottom:6px}
.be-act-row input[type=text]{flex:1;padding:5px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:.84rem}
.be-act-row input[type=date]{width:130px;padding:5px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.82rem}
</style>

<div class="cr-edit">
    <h2>{{ $cr->titre }}</h2>
    <div class="sub">{{ $cr->typeCourt() }} - {{ $cr->date_reunion->format('d/m/Y') }} - Rédacteur : {{ $cr->redacteur?->nomComplet() }}</div>

    @if(session('ok'))
    <div style="background:#d1fae5;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.85rem">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
    <div style="background:#fee2e2;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.85rem">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('comptes-rendus.update', $cr) }}" id="cr-form">
        @csrf @method('PUT')

        {{-- EN-TÊTE --}}
        <div class="section" data-sect="entete">
            <div class="section-head" onclick="toggleSection(this)">
                <h3>En-tête</h3>
                <div class="sect-status"><span class="sect-toggle">&#9660;</span></div>
            </div>
            <div class="section-body">
                <div class="fg"><label>Titre</label><input name="titre" value="{{ $cr->titre }}"></div>
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
                </div></div>
                <div class="fg"><div class="row2">
                    @if($cr->estReunion())
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
                <div class="fg"><div class="row2">
                    <div><label>Heure début</label><input type="time" name="heure_debut" value="{{ $cr->heure_debut }}"></div>
                    <div><label>Heure fin</label><input type="time" name="heure_fin" value="{{ $cr->heure_fin }}"></div>
                </div></div>
                <div class="fg"><label>Lieu</label><input name="lieu" value="{{ $cr->lieu }}"></div>
            </div>
        </div>

        {{-- MISE EN FORME DU DOCUMENT (PDF / Word) --}}
        @php
            $mef = $donnees['mise_en_forme'] ?? [];
            $champsMef = [
                'titre'      => ['Titre du document', 'ex. « COMPTE RENDU DE RÉUNION »'],
                'section'    => ['Titres de section', 'ex. « 1. CONTEXTE… », « 2. DÉROULEMENT… »'],
                'sous_titre' => ['Sous-titres', 'ex. « 2.1. Informations »'],
                'texte'      => ['Texte', 'paragraphes, listes, tableaux'],
            ];
            $taillesMef = [9, 10, 10.5, 11, 12, 13, 14, 16, 18, 20, 22, 24, 28];
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
                    <h3>1. Contexte et objectifs</h3>
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
                            <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex])<button type="button" class="coul-pastille" style="--c:#{{ $hex }}" title="{{ $lib }}" onmousedown="event.preventDefault();appliquerCouleur({{ $n }},this)"></button>@endforeach<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)">Couleur automatique</button></span></span>
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
                        <button type="button" class="btn-add" onclick="genererDeroulement()" style="background:var(--bleu,#1F4E79);color:#fff;border-color:var(--bleu,#1F4E79)" title="Créer / mettre à jour les titres du déroulement à partir de l'ordre du jour">&#10003; Valider l'ordre du jour</button>
                    </div>
                </div>
            </div>

            {{-- 2. DÉROULEMENT --}}
            <div class="section" data-sect="deroulement">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3>2. Déroulement de la réunion</h3>
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
                            <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex])<button type="button" class="coul-pastille" style="--c:#{{ $hex }}" title="{{ $lib }}" onmousedown="event.preventDefault();appliquerCouleur({{ $n }},this)"></button>@endforeach<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)">Couleur automatique</button></span></span>
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
                    <h3>3. Décisions / Actions à mener</h3>
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
                    <h3>4. Conclusion</h3>
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
                            <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">@foreach(\App\Http\Controllers\CompteRenduController::COULEURS as $n => [$lib, $hex])<button type="button" class="coul-pastille" style="--c:#{{ $hex }}" title="{{ $lib }}" onmousedown="event.preventDefault();appliquerCouleur({{ $n }},this)"></button>@endforeach<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)">Couleur automatique</button></span></span>
                        </div>
                        <div class="rich-editor" contenteditable="true" id="concl-editor" style="--num-prefix:'4.'" data-name="donnees[conclusion]">{!! $donnees['conclusion'] ?? '' !!}</div>
                        <input type="hidden" name="donnees[conclusion]" id="concl-editor-hidden">
                    </div>
                </div>
            </div>

        @else
            {{-- ===== DEMO SPRINT - ÉDITEUR BLOC WYSIWYG ===== --}}
            <input type="hidden" name="bloc_data" id="bloc-data-input">

            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;padding:10px 16px;background:#EFF4FB;border-radius:10px">
                <div style="font-size:.85rem;color:#64748b">
                    Sprint : <strong style="color:var(--bleu)">{{ $donnees['sprint'] ?? '—' }}</strong>
                    - Projet : <strong style="color:var(--bleu)">{{ $donnees['projet'] ?? '—' }}</strong>
                    - Score : <strong style="color:var(--bleu)" id="be-score-header">{{ $donnees['score_pourcent'] ?? 0 }}%</strong>
                </div>
            </div>

            <div id="be-modules"></div>
            <button type="button" class="be-add-mod" onclick="beAddModule()">+ Ajouter un module</button>
        @endif

        <div class="cr-bottom">
            <a href="{{ route('comptes-rendus.index') }}" class="btn-retour">&larr; Retour</a>
            <button type="submit" class="btn-save">{{ $cr->estConfirme() ? 'Enregistrer les modifications' : 'Enregistrer brouillon' }}</button>
            @if($cr->estBrouillon())
            <button type="button" class="btn-confirm" onclick="confirmerCR()">Confirmer le compte rendu</button>
            @endif
        </div>
    </form>

    @if($cr->estBrouillon())
    <form method="POST" action="{{ route('comptes-rendus.confirmer', $cr) }}" id="confirm-form" style="display:none">@csrf</form>
    @endif
</div>

<script>
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
                <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">${COULEURS_TEXTE.map(([n, lib, hex]) => `<button type="button" class="coul-pastille" style="--c:#${hex}" title="${lib}" onmousedown="event.preventDefault();appliquerCouleur(${n},this)"></button>`).join('')}<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)">Couleur automatique</button></span></span>
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
        <button type="button" onmousedown="event.preventDefault();indenterRich('outdent',this)" title="Décaler à gauche (Maj+Tab)">&#8676;</button><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Taille du texte sélectionné (pt)">Taille : <span class="taille-val">12</span> &#9662;</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerTaille(1,this)">9</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(2,this)">10</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(3,this)">12 (normal)</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(4,this)">14</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(5,this)">16</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(6,this)">20</button><button type="button" onmousedown="event.preventDefault();appliquerTaille(7,this)">24</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Interligne des paragraphes sélectionnés">&#8597; Interligne</button><span class="lh-menu"><button type="button" onmousedown="event.preventDefault();appliquerInterligne('',this)">Normal</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-10',this)">1,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-115',this)">1,15</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-15',this)">1,5</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-20',this)">2,0</button><button type="button" onmousedown="event.preventDefault();appliquerInterligne('lh-25',this)">2,5</button></span></span><span class="lh-wrap"><button type="button" onmousedown="event.preventDefault();this.nextElementSibling.classList.toggle('open')" title="Couleur du texte sélectionné"><span class="coul-a">A</span> &#9662;</button><span class="lh-menu coul-menu">${COULEURS_TEXTE.map(([n, lib, hex]) => `<button type="button" class="coul-pastille" style="--c:#${hex}" title="${lib}" onmousedown="event.preventDefault();appliquerCouleur(${n},this)"></button>`).join('')}<button type="button" class="coul-auto" onmousedown="event.preventDefault();appliquerCouleur(0,this)">Couleur automatique</button></span></span>
    </div>`;
}
function beExec(cmd, edId){
    const ed = document.getElementById(edId); if(!ed) return;
    ed.focus(); document.execCommand(cmd, false, null);
}

/* --- Rendu d'un bloc --- */
function renderBloc(bloc, secEl){
    const div = document.createElement('div');
    div.className = 'be-bloc'; div.dataset.id = bloc.id; div.dataset.type = bloc.type;

    const typeLabels = {texte:'Texte',tableau_taches:'Tableau des tâches',score:'Score global',actions:'Actions à mener',reference:'Référence',note:'Note'};
    const typeLbl = typeLabels[bloc.type] || bloc.type;

    let headHtml = `<div class="be-bloc-head"><span class="be-handle" title="Déplacer">&#9776;</span><span class="be-type-lbl">${typeLbl}</span>`;
    headHtml += `<div class="be-bloc-bar"><button type="button" onclick="beMoveBloc(this,-1)" title="Monter">&#9650;</button><button type="button" onclick="beMoveBloc(this,1)" title="Descendre">&#9660;</button><button type="button" onclick="beRemoveBloc(this)" title="Supprimer" style="color:#dc2626">&#10005;</button></div></div>`;

    let bodyHtml = '<div class="be-bloc-body">';

    if(bloc.type === 'texte'){
        const edId = 'be-ed-'+bloc.id;
        bodyHtml += beRichToolbar(edId);
        bodyHtml += `<div class="rich-editor" contenteditable="true" id="${edId}" data-bloc-id="${bloc.id}" style="text-align:justify">${bloc.contenu||''}</div>`;
    }
    else if(bloc.type === 'tableau_taches'){
        const taches = BE_DONNEES.taches || [];
        bodyHtml += '<table class="tbl"><thead><tr><th>Tâche</th><th style="width:50px">Pts</th><th>Attribution</th><th style="width:70px">Réalisée</th><th>Observation</th></tr></thead><tbody>';
        taches.forEach((t,i) => {
            const chk = (t.termine == true || t.termine == 1 || t.termine === '1') ? 'checked' : '';
            bodyHtml += `<tr data-tache-idx="${i}"><td>${t.tache||''}</td><td style="text-align:center">${t.points||1}</td><td>${t.attribution||''}</td><td style="text-align:center"><input type="checkbox" class="chk be-tache-chk" data-idx="${i}" ${chk}></td><td><input type="text" class="be-tache-obs" data-idx="${i}" value="${(t.observation||'').replace(/"/g,'&quot;')}"></td></tr>`;
        });
        bodyHtml += '</tbody></table>';
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
    else if(bloc.type === 'reference'){
        const rt = bloc.ref_type || 'remarque';
        const st = REF_STYLES[rt] || REF_STYLES.remarque;
        bodyHtml += `<div class="be-ref" style="background:${st.bg};border-color:${st.border}" data-ref-type="${rt}">`;
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
        <span class="be-handle" title="Déplacer">&#9776;</span>
        <input type="text" class="be-sec-titre" value="${(sec.titre||'').replace(/"/g,'&quot;')}" placeholder="Titre de la section">
        <div class="be-sec-actions">
            <button type="button" onclick="beMoveSection(this,-1)" title="Monter">&#9650;</button>
            <button type="button" onclick="beMoveSection(this,1)" title="Descendre">&#9660;</button>
            <button type="button" onclick="beRemoveSection(this)" title="Supprimer" style="color:#dc2626">&#10005;</button>
        </div>
    </div>
    <div class="be-blocs"></div>
    <div style="padding:6px 14px 10px">
        <div class="be-add-bar">
            <button type="button" class="be-add-btn" onclick="beInsertBloc(this,'texte')">${svgPlus()} Texte</button>
            <button type="button" class="be-add-btn" onclick="beInsertBloc(this,'score')">${svgChart()} Score</button>
            <button type="button" class="be-add-btn" onclick="beInsertBloc(this,'actions')">${svgList()} Actions</button>
            <button type="button" class="be-add-btn" onclick="beShowRefMenu(this)">&#9670; Référence</button>
        </div>
    </div>`;
    const blocsContainer = div.querySelector('.be-blocs');
    (sec.blocs||[]).forEach(b => renderBloc(b, div));
    modEl.querySelector('.be-sections').appendChild(div);
}

/* --- Rendu d'un module --- */
function renderModule(mod){
    const div = document.createElement('div');
    div.className = 'be-module'; div.dataset.id = mod.id;
    div.innerHTML = `<div class="be-mod-head">
        <span class="be-handle" title="Déplacer">&#9776;</span>
        <div class="be-mod-fields">
            <input type="text" class="be-mod-titre" value="${(mod.titre||'').replace(/"/g,'&quot;')}" placeholder="Titre du module">
            <input type="text" class="be-mod-obj" value="${(mod.objectif||'').replace(/"/g,'&quot;')}" placeholder="Objectif du module">
        </div>
        <div class="be-mod-actions">
            <button type="button" onclick="beMoveModule(this,-1)" title="Monter">&#9650;</button>
            <button type="button" onclick="beMoveModule(this,1)" title="Descendre">&#9660;</button>
            <button type="button" onclick="beRemoveModule(this)" title="Supprimer">&#10005;</button>
        </div>
    </div>
    <div class="be-mod-body">
        <div class="be-sections"></div>
        <button type="button" class="be-add-sec" onclick="beAddSection(this)">+ Ajouter une section</button>
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
}
function beAddSection(btn){
    const modEl = btn.closest('.be-module');
    const sec = {id:uid(),titre:'',blocs:[{id:uid(),type:'texte',contenu:''}]};
    renderSection(sec, modEl);
}
function beInsertBloc(btn, type){
    const secEl = btn.closest('.be-section');
    const bloc = {id:uid(),type:type};
    if(type === 'texte') bloc.contenu = '';
    if(type === 'actions') bloc.items = [];
    if(type === 'reference'){ bloc.ref_type = 'remarque'; bloc.contenu = ''; }
    renderBloc(bloc, secEl);
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
    refDiv.style.borderColor = st.border;
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
        taches: [],
        modules: []
    };
    /* Extraire taches depuis le DOM (checkbox termine + observation) */
    const srcTaches = BE_DONNEES.taches || [];
    srcTaches.forEach((t,i) => {
        const chk = document.querySelector(`.be-tache-chk[data-idx="${i}"]`);
        const obs = document.querySelector(`.be-tache-obs[data-idx="${i}"]`);
        data.taches.push({
            tache: t.tache, points: t.points, attribution: t.attribution, statut: t.statut,
            termine: chk ? chk.checked : (t.termine || false),
            observation: obs ? obs.value : (t.observation || '')
        });
    });
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
                /* tableau_taches et score n'ont pas de contenu editable propre */
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

/* --- Init : rendu initial --- */
beModules.forEach(m => renderModule(m));
if(!BE_DONNEES.score_manuel) beRecalculerScore();

/* Préfixe des listes hiérarchiques = numéro module.section, recalculé à chaque déplacement */
function beRenumeroter(){
    document.querySelectorAll('#be-modules > .be-module').forEach((modEl, mi) => {
        modEl.querySelectorAll('.be-section').forEach((secEl, si) => {
            secEl.querySelectorAll('.rich-editor').forEach(ed => ed.style.setProperty('--num-prefix', `'${mi+1}.${si+1}.'`));
        });
    });
}
beRenumeroter();
new MutationObserver(muts => {
    if(muts.some(m => [...m.addedNodes, ...m.removedNodes].some(n => n.classList && (n.classList.contains('be-module') || n.classList.contains('be-section') || n.classList.contains('be-bloc'))))) beRenumeroter();
}).observe(document.getElementById('be-modules'), {childList:true, subtree:true});

} /* fin if(!estReunion) */

/* === Confirmation === */
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
