@extends('layouts.app')
@section('titre', 'Rédiger — ' . $cr->titre)
@section('contenu')
<style>
.cr-edit{max-width:920px;margin:0 auto}
.cr-edit h2{font-size:1.3rem;font-weight:700;color:var(--bleu,#1F4E79);margin-bottom:6px}
.cr-edit .sub{font-size:.85rem;color:#64748b;margin-bottom:20px}
/* Sections pliables */
.section{background:#fff;border-radius:12px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06);overflow:hidden}
.section-head{display:flex;justify-content:space-between;align-items:center;padding:16px 24px;cursor:pointer;user-select:none;border-bottom:2px solid #DDEAF5;transition:background .15s}
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
.rich-toolbar button.active{background:var(--bleu,#1F4E79);color:#fff}
.rich-editor{border:1px solid #d1d5db;border-radius:0 0 8px 8px;padding:10px 14px;min-height:80px;font-size:.9rem;color:var(--bleu,#1F4E79);line-height:1.6;outline:none}
.rich-editor:focus{border-color:var(--bleu,#1F4E79);box-shadow:0 0 0 3px rgba(31,78,121,.1)}
.rich-editor ul,.rich-editor ol{margin:4px 0 4px 20px}
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
.odj-item input[type=text]{padding:10px 14px;font-size:.92rem;border-radius:8px;border:1px solid #d1d5db}
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
.ms-drop{display:none;position:absolute;top:calc(100% + 2px);left:0;right:0;background:#fff;border:1px solid #d1d5db;border-radius:6px;max-height:200px;overflow-y:auto;z-index:50;box-shadow:0 4px 12px rgba(0,0,0,.12)}
.ms-drop.open{display:block}
.ms-drop label{display:flex;align-items:center;gap:6px;padding:5px 10px;font-size:.82rem;cursor:pointer;white-space:nowrap}
.ms-drop label:hover{background:#f1f5f9}
.ms-tag{background:#dbeafe;color:#1d4ed8;padding:1px 7px;border-radius:4px;font-size:.75rem;white-space:nowrap}
.ms-placeholder{color:#94a3b8;font-size:.82rem}
</style>

<div class="cr-edit">
    <h2>{{ $cr->titre }}</h2>
    <div class="sub">{{ $cr->typeCourt() }} — {{ $cr->date_reunion->format('d/m/Y') }} — Rédacteur : {{ $cr->redacteur?->nomComplet() }}</div>

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
                <div class="fg"><div class="row2">
                    <div><label>Heure début</label><input type="time" name="heure_debut" value="{{ $cr->heure_debut }}"></div>
                    <div><label>Heure fin</label><input type="time" name="heure_fin" value="{{ $cr->heure_fin }}"></div>
                </div></div>
                <div class="fg"><label>Lieu</label><input name="lieu" value="{{ $cr->lieu }}"></div>
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
                        <thead><tr><th class="num-col">N°</th><th>Collaborateur</th><th style="width:80px;text-align:center">Présent</th></tr></thead>
                        <tbody>
                        @foreach($donnees['presences'] ?? [] as $i => $p)
                        <tr>
                            <td class="num-col">{{ $i + 1 }}</td>
                            <td>{{ $p['nom'] }}<input type="hidden" name="donnees[presences][{{ $i }}][nom]" value="{{ $p['nom'] }}"></td>
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
                        </div>
                        <div class="rich-editor" contenteditable="true" id="ctx-editor" data-name="donnees[contexte]">{!! $donnees['contexte'] ?? '' !!}</div>
                        <input type="hidden" name="donnees[contexte]" id="ctx-editor-hidden">
                    </div>

                    <label style="font-size:.85rem;font-weight:600;color:#475569;margin-bottom:8px;display:block">Ordre du jour</label>
                    <div id="odj-list">
                    @foreach($donnees['ordre_du_jour'] ?? [['point' => '', 'contenu' => '']] as $k => $odj)
                        <div class="odj-item" data-idx="{{ $k }}">
                            <span class="num">{{ $k + 1 }}</span>
                            <div style="display:flex;gap:10px;align-items:center">
                                <div style="flex:1"><input type="text" name="donnees[ordre_du_jour][{{ $k }}][point]" value="{{ $odj['point'] ?? '' }}" placeholder="Ex : Revue des livrables de la semaine"></div>
                                <button type="button" class="btn-rm" onclick="this.closest('.odj-item').remove();renum()" title="Supprimer">&#10005;</button>
                            </div>
                        </div>
                    @endforeach
                    </div>
                    <button type="button" class="btn-add" onclick="ajouterOdj()" style="margin-top:6px">+ Ajouter un point</button>
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
                    <p style="font-size:.82rem;color:#94a3b8;margin-bottom:12px">Développez chaque point de l'ordre du jour.</p>
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
                            </div>
                            <div class="rich-editor" contenteditable="true" id="derou-{{ $k }}" data-name="donnees[ordre_du_jour][{{ $k }}][contenu]">{!! $odj['contenu'] ?? '' !!}</div>
                            <input type="hidden" name="donnees[ordre_du_jour][{{ $k }}][contenu]" id="derou-{{ $k }}-hidden">
                        </div>
                        @endif
                    @endforeach
                    </div>
                </div>
            </div>

            {{-- Diligences --}}
            @if(!empty($donnees['diligences']))
            <div class="section" data-sect="diligences">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3>Diligences par projet</h3>
                    <div class="sect-status">
                        <button type="button" class="btn-validate" onclick="event.stopPropagation();validerSection(this)">&#10003; Valider</button>
                        <span class="sect-toggle">&#9660;</span>
                    </div>
                </div>
                <div class="section-body">
                @foreach($donnees['diligences'] ?? [] as $j => $dil)
                <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:10px">
                    <div style="font-weight:600;color:#1e3a5f;margin-bottom:6px">{{ $dil['projet'] }} <span style="font-weight:400;color:#64748b;font-size:.82rem">({{ $dil['responsable'] }})</span></div>
                    <input type="hidden" name="donnees[diligences][{{ $j }}][projet]" value="{{ $dil['projet'] }}">
                    <input type="hidden" name="donnees[diligences][{{ $j }}][responsable]" value="{{ $dil['responsable'] }}">
                    <input type="hidden" name="donnees[diligences][{{ $j }}][taches_terminees]" value="{{ $dil['taches_terminees'] }}">
                    <input type="hidden" name="donnees[diligences][{{ $j }}][taches_bloquees]" value="{{ $dil['taches_bloquees'] }}">
                    <div style="font-size:.82rem;color:#64748b;margin-bottom:6px">Terminées : <strong>{{ $dil['taches_terminees'] }}</strong> — Blocages : <strong>{{ $dil['taches_bloquees'] }}</strong></div>
                    <div class="fg" style="margin-bottom:0"><label>Observations</label>
                        <div class="rich-toolbar" data-target="dil-{{ $j }}">
                            <button type="button" onclick="execRich('bold',this)" title="Gras"><b>G</b></button>
                            <button type="button" onclick="execRich('italic',this)" title="Italique"><i>I</i></button>
                            <button type="button" onclick="execRich('underline',this)" title="Souligné"><u>S</u></button>
                            <button type="button" onclick="execRich('insertUnorderedList',this)" title="Liste à puces">&#8226; Liste</button>
                        </div>
                        <div class="rich-editor" contenteditable="true" id="dil-{{ $j }}" data-name="donnees[diligences][{{ $j }}][observations]">{!! $dil['observations'] ?? '' !!}</div>
                        <input type="hidden" name="donnees[diligences][{{ $j }}][observations]" id="dil-{{ $j }}-hidden">
                    </div>
                </div>
                @endforeach
                </div>
            </div>
            @endif

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
                        </div>
                        <div class="rich-editor" contenteditable="true" id="concl-editor" data-name="donnees[conclusion]">{!! $donnees['conclusion'] ?? '' !!}</div>
                        <input type="hidden" name="donnees[conclusion]" id="concl-editor-hidden">
                    </div>
                </div>
            </div>

        @else
            {{-- ===== DEMO SPRINT ===== --}}
            <div class="section">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3>Résultats par tâche</h3>
                    <div class="sect-status"><span class="sect-toggle">&#9660;</span></div>
                </div>
                <div class="section-body">
                    <div style="font-size:.85rem;color:#64748b;margin-bottom:10px">Sprint : <strong>{{ $donnees['sprint'] ?? '—' }}</strong> — Projet : <strong>{{ $donnees['projet'] ?? '—' }}</strong></div>
                    <table class="tbl">
                        <thead><tr><th>Tâche</th><th style="width:50px">Pts</th><th>Attribution</th><th style="width:50px">OK</th><th>Observation</th></tr></thead>
                        <tbody>
                        @foreach($donnees['taches'] ?? [] as $k => $t)
                        <tr>
                            <td>{{ $t['tache'] }}
                                <input type="hidden" name="donnees[taches][{{ $k }}][tache]" value="{{ $t['tache'] }}">
                                <input type="hidden" name="donnees[taches][{{ $k }}][points]" value="{{ $t['points'] }}">
                                <input type="hidden" name="donnees[taches][{{ $k }}][attribution]" value="{{ $t['attribution'] }}">
                                <input type="hidden" name="donnees[taches][{{ $k }}][statut]" value="{{ $t['statut'] }}">
                            </td>
                            <td style="text-align:center">{{ $t['points'] }}</td>
                            <td>{{ $t['attribution'] }}</td>
                            <td style="text-align:center"><input type="checkbox" class="chk" name="donnees[taches][{{ $k }}][termine]" value="1" @checked($t['termine'] ?? false)></td>
                            <td><input type="text" name="donnees[taches][{{ $k }}][observation]" value="{{ $t['observation'] ?? '' }}"></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <div class="section-head"><h3>Score global</h3></div>
                <div class="section-body">
                    <div style="font-size:1.1rem;font-weight:700;color:#1e3a5f">{{ $donnees['points_realises'] ?? 0 }} / {{ $donnees['total_points'] ?? 0 }} points ({{ $donnees['score_pourcent'] ?? 0 }}%)</div>
                </div>
            </div>

            <div class="section">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3>Actions à mener</h3>
                    <div class="sect-status"><span class="sect-toggle">&#9660;</span></div>
                </div>
                <div class="section-body">
                    <table class="tbl">
                        <thead><tr><th class="num-col">N°</th><th>Action</th><th style="width:220px">Responsable(s)</th><th style="width:120px">Échéance</th><th style="width:40px"></th></tr></thead>
                        <tbody id="actions-body">
                        @foreach($donnees['actions_a_mener'] ?? [] as $k => $a)
                        @php $respsA = $a['responsables'] ?? (isset($a['responsable']) && $a['responsable'] ? [$a['responsable']] : []); @endphp
                        <tr>
                            <td class="num-col">{{ $k + 1 }}</td>
                            <td><input type="text" name="donnees[actions_a_mener][{{ $k }}][action]" value="{{ $a['action'] ?? '' }}"></td>
                            <td><div class="ms-wrap">
                                <div class="ms-btn" onclick="toggleMS(this)">
                                    @if(count($respsA))
                                        @foreach($respsA as $rn)<span class="ms-tag">{{ $rn }}</span>@endforeach
                                    @else
                                        <span class="ms-placeholder">— Choisir —</span>
                                    @endif
                                </div>
                                <div class="ms-drop">
                                    <label><input type="checkbox" name="donnees[actions_a_mener][{{ $k }}][responsables][]" value="Tous" onchange="updateMSDisplay(this)" @checked(in_array('Tous', $respsA))> <strong>Tous</strong></label>
                                    @foreach($collaborateurs as $c)
                                    <label><input type="checkbox" name="donnees[actions_a_mener][{{ $k }}][responsables][]" value="{{ $c->nomComplet() }}" onchange="updateMSDisplay(this)" @checked(in_array($c->nomComplet(), $respsA))> {{ $c->nomComplet() }}</label>
                                    @endforeach
                                </div>
                            </div></td>
                            <td><input type="date" name="donnees[actions_a_mener][{{ $k }}][echeance]" value="{{ $a['echeance'] ?? '' }}"></td>
                            <td><button type="button" class="btn-rm" onclick="this.closest('tr').remove();renumAct()">&#10005;</button></td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn-add" onclick="ajouterAction()" style="margin-top:8px">+ Ajouter une action</button>
                </div>
            </div>

            <div class="section">
                <div class="section-head" onclick="toggleSection(this)">
                    <h3>Note pour le sprint suivant</h3>
                    <div class="sect-status"><span class="sect-toggle">&#9660;</span></div>
                </div>
                <div class="section-body">
                    <div class="fg" style="margin-bottom:0">
                        <div class="rich-toolbar" data-target="note-editor">
                            <button type="button" onclick="execRich('bold',this)" title="Gras"><b>G</b></button>
                            <button type="button" onclick="execRich('italic',this)" title="Italique"><i>I</i></button>
                            <button type="button" onclick="execRich('underline',this)" title="Souligné"><u>S</u></button>
                            <button type="button" onclick="execRich('insertUnorderedList',this)" title="Liste à puces">&#8226; Liste</button>
                            <button type="button" onclick="execRich('insertOrderedList',this)" title="Liste numérotée">1. Liste</button>
                        </div>
                        <div class="rich-editor" contenteditable="true" id="note-editor" data-name="donnees[note_sprint]">{!! $donnees['note_sprint'] ?? '' !!}</div>
                        <input type="hidden" name="donnees[note_sprint]" id="note-editor-hidden">
                    </div>
                </div>
            </div>
        @endif

        <div class="cr-bottom">
            <a href="{{ route('comptes-rendus.index') }}" class="btn-retour">&larr; Retour</a>
            <button type="submit" class="btn-save">Enregistrer brouillon</button>
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
let actIdx = {{ count($donnees['actions_a_mener'] ?? []) }};
const collabs = @json($collaborateurs->map(fn($c) => $c->nomComplet())->values());

/* === Rich editor === */
function execRich(cmd, btn){
    const toolbar = btn.closest('.rich-toolbar');
    const editorId = toolbar.dataset.target;
    const editor = document.getElementById(editorId);
    editor.focus();
    document.execCommand(cmd, false, null);
}

/* Sync rich editors to hidden inputs before submit */
function syncEditors(){
    document.querySelectorAll('.rich-editor').forEach(ed => {
        const hiddenId = ed.id + '-hidden';
        const hidden = document.getElementById(hiddenId);
        if(hidden) hidden.value = ed.innerHTML.trim();
    });
}

/* === Sections pliables === */
function toggleSection(head){
    head.closest('.section').classList.toggle('collapsed');
}

function validerSection(btn){
    const section = btn.closest('.section');
    const isValidated = btn.classList.toggle('validated');
    if(isValidated){
        btn.innerHTML = '&#10003; Validé';
        section.classList.add('validated','collapsed');
    } else {
        btn.innerHTML = '&#10003; Valider';
        section.classList.remove('validated','collapsed');
    }
}

/* === ODJ === */
function ajouterOdj(){
    const list = document.getElementById('odj-list');
    const div = document.createElement('div');
    div.className = 'odj-item';
    div.dataset.idx = odjIdx;
    div.innerHTML = `<span class="num">${odjIdx+1}</span>
        <div style="display:flex;gap:10px;align-items:center">
            <div style="flex:1"><input type="text" name="donnees[ordre_du_jour][${odjIdx}][point]" placeholder="Ex : Revue des livrables de la semaine"></div>
            <button type="button" class="btn-rm" onclick="this.closest('.odj-item').remove();renum()" title="Supprimer">&#10005;</button>
        </div>`;
    list.appendChild(div);
    odjIdx++;
    renum();
}
function renum(){
    document.querySelectorAll('#odj-list .odj-item .num').forEach((el,i) => el.textContent = i+1);
}

/* === Multi-select responsables === */
function msCheckboxes(namePrefix){
    return `<label><input type="checkbox" name="${namePrefix}[responsables][]" value="Tous" onchange="updateMSDisplay(this)"> <strong>Tous</strong></label>` + collabs.map(c => `<label><input type="checkbox" name="${namePrefix}[responsables][]" value="${c}" onchange="updateMSDisplay(this)"> ${c}</label>`).join('');
}
function toggleMS(btn){
    const drop = btn.nextElementSibling;
    document.querySelectorAll('.ms-drop.open').forEach(d => { if(d !== drop) d.classList.remove('open'); });
    drop.classList.toggle('open');
}
function updateMSDisplay(chk){
    const wrap = chk.closest('.ms-wrap');
    const btn = wrap.querySelector('.ms-btn');
    const checked = wrap.querySelectorAll('.ms-drop input:checked');
    if(checked.length === 0){
        btn.innerHTML = '<span class="ms-placeholder">— Choisir —</span>';
    } else {
        btn.innerHTML = Array.from(checked).map(c => `<span class="ms-tag">${c.value}</span>`).join('');
    }
}
document.addEventListener('click', function(e){
    if(!e.target.closest('.ms-wrap')) document.querySelectorAll('.ms-drop.open').forEach(d => d.classList.remove('open'));
});

/* === Décisions / Actions === */
function ajouterDecision(){
    const body = document.getElementById('decisions-body');
    const n = body.rows.length + 1;
    const row = document.createElement('tr');
    row.innerHTML = `<td class="num-col">${n}</td>
        <td><input type="text" name="donnees[decisions][${decIdx}][action]"></td>
        <td><div class="ms-wrap">
            <div class="ms-btn" onclick="toggleMS(this)"><span class="ms-placeholder">— Choisir —</span></div>
            <div class="ms-drop">${msCheckboxes('donnees[decisions]['+decIdx+']')}</div>
        </div></td>
        <td><input type="date" name="donnees[decisions][${decIdx}][echeance]"></td>
        <td><button type="button" class="btn-rm" onclick="this.closest('tr').remove();renumDec()">&#10005;</button></td>`;
    body.appendChild(row);
    decIdx++;
}
function renumDec(){
    document.querySelectorAll('#decisions-body tr').forEach((tr,i) => { const td = tr.querySelector('.num-col'); if(td) td.textContent = i+1; });
}
function ajouterAction(){
    const body = document.getElementById('actions-body');
    const n = body.rows.length + 1;
    const row = document.createElement('tr');
    row.innerHTML = `<td class="num-col">${n}</td>
        <td><input type="text" name="donnees[actions_a_mener][${actIdx}][action]"></td>
        <td><div class="ms-wrap">
            <div class="ms-btn" onclick="toggleMS(this)"><span class="ms-placeholder">— Choisir —</span></div>
            <div class="ms-drop">${msCheckboxes('donnees[actions_a_mener]['+actIdx+']')}</div>
        </div></td>
        <td><input type="date" name="donnees[actions_a_mener][${actIdx}][echeance]"></td>
        <td><button type="button" class="btn-rm" onclick="this.closest('tr').remove();renumAct()">&#10005;</button></td>`;
    body.appendChild(row);
    actIdx++;
}
function renumAct(){
    document.querySelectorAll('#actions-body tr').forEach((tr,i) => { const td = tr.querySelector('.num-col'); if(td) td.textContent = i+1; });
}

/* === Confirmation === */
function confirmerCR(){
    if(confirm('Confirmer définitivement ce compte rendu ? Un fichier DOCX sera généré.')){
        syncEditors();
        // D'abord sauvegarder, puis confirmer
        const form = document.getElementById('cr-form');
        const data = new FormData(form);
        fetch(form.action, {method:'POST', body:data, headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(() => document.getElementById('confirm-form').submit())
            .catch(() => document.getElementById('confirm-form').submit());
    }
}

/* Sync avant toute soumission */
document.getElementById('cr-form').addEventListener('submit', function(){ syncEditors(); });
</script>
@endsection
