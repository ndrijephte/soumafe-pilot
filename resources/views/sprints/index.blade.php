@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Projets & Sprints')
@push('styles')
<style>
.projet-section{margin-bottom:28px}
.projet-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.projet-code{background:var(--accent);color:#fff;font-weight:700;font-size:12px;padding:4px 10px;border-radius:6px;letter-spacing:.5px}
.projet-nom{font-size:16px;font-weight:600;color:var(--bleu)}
.projet-resp{font-size:12px;color:var(--gris)}
.sprint-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px}
.sprint-card{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;position:relative;transition:box-shadow .2s}
.sprint-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
.sprint-num{font-size:18px;font-weight:700;color:var(--bleu)}
.sprint-obj{font-size:13px;color:#1e293b;margin:6px 0 10px;line-height:1.4}
.sprint-dates{font-size:11.5px;color:var(--gris);display:flex;align-items:center;gap:6px}
.sprint-bar{height:6px;background:#e2e8f0;border-radius:3px;margin:10px 0 8px;overflow:hidden}
.sprint-bar div{height:100%;border-radius:3px;background:var(--accent);transition:width .4s}
.sprint-stats{display:flex;gap:16px;font-size:12px;color:var(--gris)}
.sprint-stats strong{color:#1e293b}
/* Sprint en cours : se distingue nettement des sprints planifiés ou clôturés */
.sprint-card.en-cours{grid-column:1/-1;background:#F2F7FD;border:1.5px solid #2e74b5;box-shadow:0 4px 18px -6px rgba(46,116,181,.35);padding:16px 20px}
.sprint-card.en-cours:hover{box-shadow:0 8px 24px -6px rgba(46,116,181,.45)}
.sprint-card.en-cours .sprint-num{font-size:20px;color:#1f4e79}
.sprint-card.en-cours .sprint-obj{font-size:13.5px;color:#1f4e79;font-weight:600}
.sprint-card.en-cours .sprint-bar{height:9px;background:#dbe7f5;border-radius:6px}
.sprint-card.en-cours .sprint-bar div{border-radius:6px;background:#2e74b5}
.sprint-card.en-cours .sprint-stats{color:#475569;align-items:center}
.encours-tete{display:flex;align-items:center;gap:10px;margin:0 0 8px;font-size:10.5px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#2e74b5}
.encours-tete .pouls{position:relative;width:8px;height:8px;border-radius:50%;background:#16a34a}
.encours-tete .pouls::after{content:"";position:absolute;inset:0;border-radius:50%;background:#16a34a;animation:pouls-sp 1.8s ease-out infinite}
@keyframes pouls-sp{0%{transform:scale(1);opacity:.6}100%{transform:scale(2.6);opacity:0}}
@media(prefers-reduced-motion:reduce){.encours-tete .pouls::after{animation:none}}
.encours-tete .reste{margin-left:auto;padding:3px 10px;border-radius:999px;background:#2e74b5;color:#fff;letter-spacing:.3px}
.encours-tete .reste.retard{background:#dc2626}
.encours-pct{font-size:12px;font-weight:700;color:#1f4e79;margin-left:auto}
.sprint-taches{margin-top:10px;border-top:1px solid var(--bord);padding-top:8px}
.sprint-tache-line{display:flex;justify-content:space-between;align-items:center;padding:4px 0;font-size:12px;border-bottom:1px solid #f1f5f9}
.sprint-tache-line:last-child{border:none}
.pill{padding:2px 9px;border-radius:10px;font-size:10.5px;font-weight:600}
.s-planifie{background:#e2e8f0;color:#475569}.s-en_cours{background:#dbeafe;color:#1d4ed8}.s-termine{background:#dcfce7;color:var(--ok)}.s-arrete{background:#fee2e2;color:#b91c1c}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sec{background:#fff;color:var(--accent);border:1px solid var(--accent)}
.btn.sm{padding:5px 12px;font-size:11.5px}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.btn-toggle-termines{display:inline-flex;align-items:center;gap:6px;background:var(--fond);border:1px solid var(--bord);border-radius:8px;padding:6px 14px;font-size:12px;color:var(--gris);cursor:pointer;margin-top:8px;transition:background .2s}
.btn-toggle-termines:hover{background:#e2e8f0;color:var(--bleu)}
.btn-toggle-termines svg{transition:transform .2s}
.btn-toggle-termines.ouvert svg{transform:rotate(180deg)}
.sprints-termines{display:none;margin-top:12px}
.sprints-termines.visible{display:grid}
dialog{border:none;border-radius:14px;padding:24px;width:460px;max-width:95vw;box-shadow:0 8px 30px rgba(0,0,0,.18);max-height:85vh;overflow-y:auto}
dialog::backdrop{background:rgba(22,56,92,.35)}
dialog label{font-size:12px;color:var(--gris);display:block;margin:10px 0 3px}
dialog input,dialog select{width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px}
dialog input:focus,dialog select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(46,116,181,.12)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.req{color:var(--ko)}
.filtre-bar{display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.filtre-bar input,.filtre-bar select{padding:6px 10px;border:1px solid var(--bord);border-radius:8px;font-size:12px}
.filtre-bar input{min-width:180px}
@media(max-width:900px){.sprint-grid{grid-template-columns:1fr}.form-row{grid-template-columns:1fr}.filtre-bar{flex-direction:column}.projet-header{flex-wrap:wrap}}
.btn-visibilite{background:none;border:none;padding:2px 4px;cursor:pointer;color:var(--gris);border-radius:4px;transition:color .2s}
.btn-visibilite:hover{color:var(--accent)}
.sprint-card.masque{opacity:.55;border-style:dashed;border-color:#94a3b8}
.panneau-excl{position:absolute;top:36px;right:0;background:#fff;border:1px solid var(--bord);border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,.12);padding:10px 12px;z-index:50;min-width:220px;max-height:260px;overflow-y:auto;display:none}
.panneau-excl.visible{display:block}
.panneau-excl h4{font-size:12px;color:var(--bleu);margin:0 0 8px;font-weight:700}
.panneau-excl label{display:flex;align-items:center;gap:6px;font-size:12px;color:#1e293b;padding:4px 0;cursor:pointer;margin:0}
.panneau-excl label:hover{color:var(--accent)}
.panneau-excl input[type=checkbox]{accent-color:var(--accent);width:14px;height:14px}
.panneau-excl .btn-excl-ok{display:block;width:100%;margin-top:8px;padding:5px 0;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:11.5px;cursor:pointer;font-weight:600}
.badge-excl{background:#fef2f2;color:var(--ko);font-size:10px;font-weight:600;padding:1px 6px;border-radius:8px;margin-left:2px}
@media(max-width:600px){dialog{padding:16px}.sprint-card{padding:12px}.sprint-num{font-size:16px}.btn{padding:6px 12px;font-size:12px}}
</style>
@endpush
@section('contenu')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;flex-wrap:wrap;gap:8px">
    <h2>Projets & Sprints</h2>
    @if(auth()->user()->peutGererProjet())
    <div style="display:flex;gap:8px">
        <button class="btn" onclick="document.getElementById('dlg-sprint').showModal()">+ Nouveau sprint</button>
        <button class="btn sec" onclick="document.getElementById('dlg-import').showModal()">Importer (.xlsx)</button>
    </div>
    @endif
</div>
<div class="onglets-page">
    <a href="{{ route('sprints.index') }}" class="actif">Projets / Sprints</a>
    @if(auth()->user()->estPilote())
    <a href="{{ route('taches.pilote') }}">Toutes les tâches</a>
    @endif
</div>

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div> @endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div> @endif
@if(session('erreurs_import') && count(session('erreurs_import')))<div class="alerte" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e"><strong>Remarques de l'import :</strong><ul style="margin:4px 0 0 18px">@foreach(session('erreurs_import') as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div> @endif

@if($espaces->isNotEmpty())
<div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-bottom:12px;font-size:12.5px">
    <span style="color:var(--gris);font-weight:600">Espaces projets (équipe, backlog) :</span>
    @foreach($espaces as $ep)
    <a href="{{ route('projets.espace', $ep) }}" style="padding:3px 10px;border:1px solid var(--bord);border-radius:14px;background:#fff;color:var(--accent);text-decoration:none;font-weight:600">{{ $ep->code }}</a>
    @endforeach
</div>
@endif

{{-- Barre de filtres --}}
<div class="filtre-bar">
    <input type="text" id="filtreProjet" placeholder="Rechercher un projet..." oninput="filtrer()">
    <select id="filtreStatut" onchange="filtrer()">
        <option value="">Tous statuts sprint</option>
        <option value="planifie">Planifi&eacute;</option>
        <option value="en_cours">En cours</option>
        <option value="termine">Termin&eacute;</option>
    </select>
</div>

@foreach($projets as $p)
@if($p->sprints->count() > 0)
<div class="projet-section" data-projet>
    <div class="projet-header">
        <span class="projet-code">{{ $p->code }}</span>
        <div>
            <div class="projet-nom">{{ $p->libelle }}@if($espaces->contains('id', $p->id)) <a href="{{ route('projets.espace', $p) }}" style="font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;margin-left:6px">Espace projet ›</a>@endif</div>
            @if($p->responsable)
            <div class="projet-resp" style="display:inline-flex;align-items:center;gap:5px;background:linear-gradient(135deg,#dbeafe,#eff6ff);border:1px solid #93c5fd;color:#1d4ed8;padding:3px 10px;border-radius:6px;font-weight:600;margin-top:4px">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                {{ $p->responsable->nomComplet() }}
            </div>
            @endif
        </div>
    </div>
    @php
        $sprintsActifs = $p->sprints->whereIn('statut', ['en_cours', 'planifie']);
        $sprintsTermines = $p->sprints->where('statut', 'termine');
    @endphp
    <div class="sprint-grid">
        @foreach($sprintsActifs as $s)
        @php
            $totalTaches = $s->taches->count();
            $terminees = $s->taches->where('statut', 'termine')->count();
            $pct = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;
            $nbExcl = count($s->exclus);
        @endphp
        <a href="{{ route('sprints.show', $s) }}" class="sprint-card{{ $s->statut === 'en_cours' ? ' en-cours' : '' }}{{ !$s->visible_collaborateurs ? ' masque' : '' }}" data-sprint data-statut="{{ $s->statut }}" style="text-decoration:none;color:inherit;display:block;position:relative">
            @if($s->statut === 'en_cours')
            @php $urg = $s->urgence(); @endphp
            <div class="encours-tete"><span class="pouls" aria-hidden="true"></span>Sprint en cours<span class="reste {{ $urg['niveau'] === 'retard' ? 'retard' : '' }}" title="{{ $urg['libelle'] }}">{{ $urg['court'] }}</span></div>
            @endif
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span class="sprint-num">Sprint {{ $s->numero }}</span>
                <span style="display:flex;align-items:center;gap:6px">
                    @if (auth()->user()->estAdmin())
                    <button onclick="toggleVisibilite('{{ route('sprints.toggle-visibilite', $s) }}', event)" class="btn-visibilite" title="{{ $s->visible_collaborateurs ? 'Masquer pour tous' : 'Rendre visible' }}">
                        @if ($s->visible_collaborateurs)
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        @else
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="var(--ko)" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        @endif
                    </button>
                    <button onclick="ouvrirExclusions({{ $s->id }}, event)" class="btn-visibilite" title="G&eacute;rer visibilit&eacute; par collaborateur">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @if ($nbExcl > 0) <span class="badge-excl">{{ $nbExcl }}</span> @endif
                    </button>
                    <div class="panneau-excl" id="panneau-excl-{{ $s->id }}">
                        <h4>Masquer pour :</h4>
                        <form method="POST" action="{{ route('sprints.update-exclusions', $s) }}">@csrf
                            @foreach ($collaborateurs as $c)
                            <label><input type="checkbox" name="exclus[]" value="{{ $c->id }}" {{ in_array($c->id, $s->exclus) ? 'checked' : '' }}> {{ $c->nomComplet() }}</label>
                            @endforeach
                            <button type="submit" class="btn-excl-ok" onclick="event.stopPropagation()">Valider</button>
                        </form>
                    </div>
                    @endif
                    @if($s->statut === 'termine' && $s->arrete_le)<span class="pill s-arrete" title="Arrêté le {{ $s->arrete_le->format('d/m/Y') }}{{ $s->justification_arret ? ' - Motif : ' . $s->justification_arret : '' }}">Arrêté</span>@else<span class="pill s-{{ $s->statut }}">{{ ['planifie' => 'Planifié', 'en_cours' => 'En cours', 'termine' => 'Clôturé'][$s->statut] ?? $s->statut }}</span>@endif
                </span>
            </div>
            <div class="sprint-obj">{{ $s->objectif }}</div>
            <div class="sprint-dates">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ $s->date_debut->format('d/m') }} &rarr; {{ $s->date_fin->format('d/m/Y') }}
            </div>
            <div class="sprint-bar"><div style="width:{{ $pct }}%"></div></div>
            <div class="sprint-stats">
                <div>T&acirc;ches : <strong>{{ $totalTaches }}</strong></div>
                <div>Termin&eacute;es : <strong>{{ $terminees }}</strong></div>
                <div title="{{ $s->resumePoints() }}">Points : <strong>{{ $s->pointsLivres() }}</strong> @if ($s->pointsEngages()) / {{ $s->pointsEngages() }} @endif @if($s->statut !== 'termine' && $s->storiesNonEstimees())<span style="color:#c2410c;font-weight:600">· {{ $s->storiesNonEstimees() }} non estimée(s)</span>@endif</div>
                @if($s->statut === 'en_cours')<span class="encours-pct" title="Tâches terminées">{{ $pct }} % réalisé</span>@endif
            </div>
            <div style="margin-top:8px;font-size:11px;color:var(--accent);font-weight:600">Voir le d&eacute;tail &rarr;</div>
        </a>
        @endforeach
    </div>
    @if($sprintsTermines->count() > 0)
    <button class="btn-toggle-termines" onclick="toggleTermines(this)">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        Voir les {{ $sprintsTermines->count() }} sprint{{ $sprintsTermines->count() > 1 ? 's' : '' }} termin&eacute;{{ $sprintsTermines->count() > 1 ? 's' : '' }}
    </button>
    <div class="sprints-termines sprint-grid">
        @foreach($sprintsTermines as $s)
        @php
            $totalTaches = $s->taches->count();
            $terminees = $s->taches->where('statut', 'termine')->count();
            $pct = $totalTaches > 0 ? round(($terminees / $totalTaches) * 100) : 0;
            $nbExcl = count($s->exclus);
        @endphp
        <a href="{{ route('sprints.show', $s) }}" class="sprint-card{{ $s->statut === 'en_cours' ? ' en-cours' : '' }}{{ !$s->visible_collaborateurs ? ' masque' : '' }}" data-sprint data-statut="{{ $s->statut }}" style="text-decoration:none;color:inherit;display:block;position:relative">
            @if($s->statut === 'en_cours')
            @php $urg = $s->urgence(); @endphp
            <div class="encours-tete"><span class="pouls" aria-hidden="true"></span>Sprint en cours<span class="reste {{ $urg['niveau'] === 'retard' ? 'retard' : '' }}" title="{{ $urg['libelle'] }}">{{ $urg['court'] }}</span></div>
            @endif
            <div style="display:flex;justify-content:space-between;align-items:center">
                <span class="sprint-num">Sprint {{ $s->numero }}</span>
                <span style="display:flex;align-items:center;gap:6px">
                    @if (auth()->user()->estAdmin())
                    <button onclick="toggleVisibilite('{{ route('sprints.toggle-visibilite', $s) }}', event)" class="btn-visibilite" title="{{ $s->visible_collaborateurs ? 'Masquer pour tous' : 'Rendre visible' }}">
                        @if ($s->visible_collaborateurs)
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        @else
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="var(--ko)" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        @endif
                    </button>
                    <button onclick="ouvrirExclusions({{ $s->id }}, event)" class="btn-visibilite" title="G&eacute;rer visibilit&eacute; par collaborateur">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        @if ($nbExcl > 0) <span class="badge-excl">{{ $nbExcl }}</span> @endif
                    </button>
                    <div class="panneau-excl" id="panneau-excl-{{ $s->id }}">
                        <h4>Masquer pour :</h4>
                        <form method="POST" action="{{ route('sprints.update-exclusions', $s) }}">@csrf
                            @foreach ($collaborateurs as $c)
                            <label><input type="checkbox" name="exclus[]" value="{{ $c->id }}" {{ in_array($c->id, $s->exclus) ? 'checked' : '' }}> {{ $c->nomComplet() }}</label>
                            @endforeach
                            <button type="submit" class="btn-excl-ok" onclick="event.stopPropagation()">Valider</button>
                        </form>
                    </div>
                    @endif
                    @if($s->statut === 'termine' && $s->arrete_le)<span class="pill s-arrete" title="Arrêté le {{ $s->arrete_le->format('d/m/Y') }}{{ $s->justification_arret ? ' - Motif : ' . $s->justification_arret : '' }}">Arrêté</span>@else<span class="pill s-{{ $s->statut }}">{{ ['planifie' => 'Planifié', 'en_cours' => 'En cours', 'termine' => 'Clôturé'][$s->statut] ?? $s->statut }}</span>@endif
                </span>
            </div>
            <div class="sprint-obj">{{ $s->objectif }}</div>
            <div class="sprint-dates">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ $s->date_debut->format('d/m') }} &rarr; {{ $s->date_fin->format('d/m/Y') }}
            </div>
            <div class="sprint-bar"><div style="width:{{ $pct }}%"></div></div>
            <div class="sprint-stats">
                <div>T&acirc;ches : <strong>{{ $totalTaches }}</strong></div>
                <div>Termin&eacute;es : <strong>{{ $terminees }}</strong></div>
                <div title="{{ $s->resumePoints() }}">Points : <strong>{{ $s->pointsLivres() }}</strong> @if ($s->pointsEngages()) / {{ $s->pointsEngages() }} @endif @if($s->statut !== 'termine' && $s->storiesNonEstimees())<span style="color:#c2410c;font-weight:600">· {{ $s->storiesNonEstimees() }} non estimée(s)</span>@endif</div>
            </div>
            <div style="margin-top:8px;font-size:11px;color:var(--accent);font-weight:600">Voir le d&eacute;tail &rarr;</div>
        </a>
        @endforeach
    </div>
    @endif
</div>
@endif
@endforeach

@if($projets->every(fn($p) => $p->sprints->isEmpty()))
<div class="carte" style="text-align:center;padding:40px">
    <p style="color:var(--gris);font-size:14px;margin-bottom:12px">Aucun sprint cr&eacute;&eacute; pour le moment.</p>
    @if(auth()->user()->peutGererProjet())
    <button class="btn" onclick="document.getElementById('dlg-sprint').showModal()">Cr&eacute;er le premier sprint</button>
    @else
    <p style="color:var(--gris);font-size:13px">Le responsable projet ou l'administrateur peut cr&eacute;er des sprints.</p>
    @endif
</div>
@endif

{{-- Dialog création sprint --}}
@if(auth()->user()->peutGererProjet())
<dialog id="dlg-sprint">
    <h3 style="color:var(--bleu);margin-bottom:4px">Nouveau sprint</h3>
    <form method="POST" action="{{ route('sprints.store') }}">@csrf
        <label>Projet <span class="req">*</span></label>
        <select name="projet_id" required>
            <option value="">— Choisir —</option>
            @foreach(auth()->user()->projetsGerables() as $pg)<option value="{{ $pg->id }}">{{ $pg->code }} - {{ $pg->libelle }}</option> @endforeach
        </select>
        <div class="form-row">
            <div><label>N° du sprint <span class="req">*</span></label><input type="number" name="numero" min="1" required></div>
            <div><label>Points engag&eacute;s</label><input type="number" name="engagement_points" min="1"></div>
        </div>
        <label>Objectif <span class="req">*</span></label><textarea name="objectif" required placeholder="Ex : kanban + admin comptes" rows="3" style="width:100%;padding:7px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;resize:vertical;font-family:inherit"></textarea>
        <div class="form-row">
            <div><label>D&eacute;but <span class="req">*</span></label><input type="date" name="date_debut" required></div>
            <div><label>Fin <span class="req">*</span></label><input type="date" name="date_fin" required></div>
        </div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Cr&eacute;er le sprint</button>
        </div>
    </form>
</dialog>

{{-- Dialog import xlsx : classeur Scrum (sprints créés d'un coup) ou liste de tâches --}}
<dialog id="dlg-import" style="width:540px">
    <h3 style="color:var(--bleu);margin-bottom:4px">Importer un classeur (.xlsx)</h3>
    <p style="font-size:12.5px;color:#475569;margin:0 0 12px;line-height:1.55">Le type de classeur est reconnu automatiquement.</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px">
        <div style="background:#f2f7fd;border:1px solid #d6e5f5;border-radius:10px;padding:10px 12px;font-size:11.5px;color:#334155;line-height:1.5">
            <strong style="display:block;color:#1f4e79;font-size:12px;margin-bottom:3px">Classeur Scrum</strong>
            Consolidé ou archive d'un sprint : Product Backlog, Backlog / Planning des sprints, Dailys, Reviews, Rétrospectives. <b>Les sprints sont créés à partir du classeur</b>, avec leur objectif et leur période.
        </div>
        <div style="background:var(--fond);border:1px solid var(--bord);border-radius:10px;padding:10px 12px;font-size:11.5px;color:#334155;line-height:1.5">
            <strong style="display:block;color:#1e293b;font-size:12px;margin-bottom:3px">Liste de tâches</strong>
            Colonnes reconnues par leur nom : <b>Intitulé</b>, <b>Assigné à</b>, <b>Échéance</b> (obligatoires), Date début, Priorité, Points, Projet, User story.
        </div>
    </div>
    <form method="POST" action="{{ route('sprints.importer') }}" enctype="multipart/form-data" onsubmit="const b=this.querySelector('button.btn:not(.sec)');b.disabled=true;b.textContent='Import en cours…'">@csrf
        <label>Projet <span style="font-weight:400;color:var(--gris)">(obligatoire pour un classeur Scrum)</span></label>
        <select name="projet_id" id="imp-projet" onchange="filtrerSprintsImport()">
            <option value="">— Choisir —</option>
            @foreach(auth()->user()->projetsGerables() as $pg)<option value="{{ $pg->id }}">{{ $pg->code }} - {{ $pg->libelle }}</option>@endforeach
        </select>
        <label>Sprint <span style="font-weight:400;color:var(--gris)">(facultatif : tâches à verser dans un sprint existant)</span></label>
        <select name="sprint_id" id="imp-sprint">
            <option value="">— Aucun : sprints du classeur, ou tâches hors sprint —</option>
            @foreach($projets as $p)
                @foreach($p->sprints as $s)
                <option value="{{ $s->id }}" data-projet="{{ $p->id }}">{{ $p->code }} - Sprint {{ $s->numero }}</option>
                @endforeach
            @endforeach
        </select>
        <label>Fichier Excel (.xlsx) <span class="req">*</span></label>
        <input type="file" name="fichier" accept=".xlsx" required style="padding:8px;border:1px dashed var(--bord);border-radius:8px;background:var(--fond);margin-top:4px">
        <p style="font-size:11.5px;color:var(--gris);margin:8px 0 0;line-height:1.5">Réimporter le même classeur ne crée pas de doublon ; un sprint déjà clôturé n'est pas modifié.</p>
        <label style="display:flex;align-items:flex-start;gap:8px;margin-top:12px;padding:9px 12px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:12px;color:#92400e;cursor:pointer;font-weight:400"><input type="checkbox" name="simulation" value="1" style="width:auto;margin-top:2px"><span><b>Simulation</b> : voir le résultat (sprints, tâches, doublons évités, remarques) sans rien enregistrer. Recommandé avant un import en production.</span></label>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end">
            <button type="button" class="btn sec" onclick="this.closest('dialog').close()">Annuler</button>
            <button class="btn">Importer</button>
        </div>
    </form>
</dialog>
@endif
@endsection

<form id="form-toggle-visibilite" method="POST" style="display:none">@csrf</form>

@push('scripts')
<script>
@if($errors->any())document.addEventListener('DOMContentLoaded',function(){var d=document.getElementById('dlg-sprint');if(d)d.showModal();});@endif
// Import : les sprints proposés sont ceux du projet choisi
function filtrerSprintsImport(){
    const p = document.getElementById('imp-projet').value, sel = document.getElementById('imp-sprint');
    [...sel.options].forEach(o => { if (o.value) o.hidden = !!p && o.dataset.projet !== p; });
    if (sel.selectedOptions[0]?.hidden) sel.value = '';
}
function toggleVisibilite(url,e){e.preventDefault();e.stopPropagation();var f=document.getElementById('form-toggle-visibilite');f.action=url;f.submit();}
function ouvrirExclusions(id,e){e.preventDefault();e.stopPropagation();document.querySelectorAll('.panneau-excl.visible').forEach(function(p){if(p.id!=='panneau-excl-'+id)p.classList.remove('visible');});document.getElementById('panneau-excl-'+id).classList.toggle('visible');}
document.addEventListener('click',function(e){if(!e.target.closest('.panneau-excl')&&!e.target.closest('.btn-visibilite')){document.querySelectorAll('.panneau-excl.visible').forEach(function(p){p.classList.remove('visible');});}});
function toggleTermines(btn){
    const section=btn.closest('.projet-section');
    const grid=section.querySelector('.sprints-termines');
    const ouvert=grid.classList.toggle('visible');
    btn.classList.toggle('ouvert',ouvert);
    const n=grid.querySelectorAll('[data-sprint]').length;
    btn.querySelector('span')&&btn.querySelector('span').remove();
    const txt=ouvert?'Masquer les sprints terminés':'Voir les '+n+' sprint'+(n>1?'s':'')+' terminé'+(n>1?'s':'');
    btn.childNodes[btn.childNodes.length-1].textContent=txt;
}
function filtrer(){
    const txt=document.getElementById('filtreProjet').value.toLowerCase();
    const statut=document.getElementById('filtreStatut').value;
    document.querySelectorAll('[data-projet]').forEach(section=>{
        const nom=section.querySelector('.projet-nom').textContent.toLowerCase();
        const code=section.querySelector('.projet-code').textContent.toLowerCase();
        const matchProjet=!txt||nom.includes(txt)||code.includes(txt);
        if(!matchProjet){section.style.display='none';return;}
        // Si filtre sur terminé, ouvrir automatiquement la section terminés
        const gridT=section.querySelector('.sprints-termines');
        const btnT=section.querySelector('.btn-toggle-termines');
        if(statut==='termine'&&gridT&&!gridT.classList.contains('visible')){
            gridT.classList.add('visible');
            if(btnT)btnT.classList.add('ouvert');
        }
        let anyVisible=false;
        section.querySelectorAll('[data-sprint]').forEach(card=>{
            const ok=!statut||card.dataset.statut===statut;
            card.style.display=ok?'':'none';
            if(ok) anyVisible=true;
        });
        // Masquer le bouton toggle si filtre actif sur un statut non-terminé
        if(btnT){
            btnT.style.display=(statut&&statut!=='termine')?'none':'';
        }
        section.style.display=anyVisible?'':'none';
    });
}
</script>
@endpush
