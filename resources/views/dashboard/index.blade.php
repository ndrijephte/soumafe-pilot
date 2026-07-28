@extends('layouts.app')
@section('titre', 'Tableau de bord')
@push('styles')
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px;transition:transform .15s,box-shadow .15s}
.kpi:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.06)}
.kpi .lbl{font-size:11.5px;color:var(--gris);text-transform:uppercase;letter-spacing:.4px}
.kpi .val{font-size:28px;font-weight:700;color:var(--bleu);margin:4px 0}
.kpi .sub{font-size:11.5px}
.grille{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px}
.panneau{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px}
.panneau h3{font-size:14px;color:var(--bleu);margin-bottom:10px;display:flex;align-items:center;gap:6px}
.tache-line{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--bord);font-size:13px}
.tache-line:last-child{border:none}
.jauge-wrap{background:#e2e8f0;border-radius:6px;height:10px;overflow:hidden;margin-top:6px}
.jauge-bar{height:100%;border-radius:6px;background:var(--accent);transition:width .5s}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:8px}
.alerte.warn{background:#fff7ed;border:1px solid #fed7aa;color:#b45309}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.alerte.info{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord)}
.mini-table tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600}
.b-ok{background:#dcfce7;color:var(--ok)}.b-warn{background:#fef3c7;color:#b45309}.b-ko{background:#fee2e2;color:var(--ko)}
.membre-line{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--bord)}
.membre-line:last-child{border-bottom:none}
.membre-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#1a5276);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex:none;box-shadow:0 2px 6px rgba(46,116,181,.2)}
.sprint-item{margin-bottom:12px;padding-bottom:12px;border-bottom:1px solid var(--bord)}
.sprint-item:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0}
.lien{font-size:12px;color:var(--accent);text-decoration:none;font-weight:600}
.lien:hover{text-decoration:underline}
.section-sep{margin-top:28px;padding-top:24px;border-top:2px solid var(--bord)}
.section-sep h2{font-size:16px;color:var(--bleu);margin-bottom:14px;display:flex;align-items:center;gap:8px}
@media(max-width:900px){.grille{grid-template-columns:1fr}}
</style>
@endpush
@section('contenu')
<h2>Bonjour, {{ auth()->user()->prenom ?? auth()->user()->name }}</h2>
<div class="crumb">{{ now()->translatedFormat('l j F Y') }} — votre tableau de bord</div>

@foreach($alertes as $a)
<div class="alerte {{ $a['type'] }}">
    @php $icone = match($a['type']) { 'ko' => '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>', 'warn' => '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>', default => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>' }; @endphp
    <svg class="ic" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icone !!}</svg>
    {{ $a['msg'] }}
</div>
@endforeach

<div class="kpis">
    <div class="kpi"><div class="lbl">Tâches totales</div><div class="val">{{ $total }}</div></div>
    <div class="kpi"><div class="lbl">Terminées</div><div class="val" style="color:var(--ok)">{{ $terminees }}</div><div class="sub">Cette semaine : {{ $termineesSemaine }}</div></div>
    <div class="kpi"><div class="lbl">En cours</div><div class="val" style="color:var(--accent)">{{ $enCours }}</div></div>
    <div class="kpi" @if($enRetard > 0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">En retard</div><div class="val" style="color:{{ $enRetard > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $enRetard }}</div></div>
    <div class="kpi" @if($bloquees > 0) style="background:#fef2f2;border-color:#fecaca" @endif><div class="lbl">Bloqu&eacute;es</div><div class="val" style="color:{{ $bloquees > 0 ? 'var(--ko)' : 'var(--ok)' }}">{{ $bloquees }}</div></div>
    <div class="kpi">
        <div class="lbl">Complétion</div><div class="val">{{ $tauxCompletion }}%</div>
        <div class="jauge-wrap"><div class="jauge-bar" style="width:{{ $tauxCompletion }}%"></div></div>
    </div>
</div>

<div class="grille">
    <div class="panneau">
        <h3>
            <svg class="ic" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4"/><polyline points="8 12 11 15 16 9"/></svg>
            Mes t&acirc;ches actives ({{ $mesTachesActives->count() }})
        </h3>
        @forelse($mesTachesActives as $t)
        @php
            $statutCls = match($t->statut) { 'en_cours' => 'b-ok', 'bloque' => 'b-ko', default => 'b-warn' };
            $prioCls = $t->priorite === 'haute' ? 'color:var(--ko);font-weight:700' : '';
            $echeanceCls = ($t->echeance && $t->echeance->lt(today()) && $t->statut !== 'termine') ? 'color:var(--ko);font-weight:700' : 'color:var(--gris)';
        @endphp
        <div class="tache-line" style="gap:8px">
            <div style="flex:1">
                <a href="{{ route('taches.show', $t) }}" style="text-decoration:none;color:#1e293b;font-weight:500">{{ $t->intitule }}</a>
                <div style="font-size:10.5px;color:var(--gris);margin-top:2px">{{ $t->projet?->libelle ?? '' }} @if($t->priorite === 'haute')&middot; <span style="color:var(--ko);font-weight:600">Haute</span>@endif</div>
            </div>
            <span style="font-size:11px;{{ $echeanceCls }}">{{ $t->echeance?->format('d/m') ?? '' }}</span>
            <span class="badge {{ $statutCls }}" style="font-size:10px">{{ $t->labelStatut() }}</span>
        </div>
        @empty
        <p style="color:var(--gris);font-size:13px">Aucune t&acirc;che active.</p>
        @endforelse
        <div style="margin-top:12px"><a href="{{ route('taches.index') }}" class="btn">Voir toutes mes activit&eacute;s</a></div>
    </div>
    <div class="panneau">
        <h3>
            <svg class="ic" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="3" y1="20" x2="21" y2="20"/></svg>
            Résumé de la semaine
        </h3>
        <div style="font-size:13px;line-height:2">
            <div>Tâches terminées : <strong>{{ $termineesSemaine }}</strong></div>
            <div>Tâches en cours : <strong>{{ $enCours }}</strong></div>
            <div>Blocages actifs : <strong>{{ $bloquees }}</strong></div>
            <div>Taux de complétion global : <strong>{{ $tauxCompletion }}%</strong></div>
        </div>

        {{-- Jauge d'assiduité (US-15) --}}
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--bord)">
            <h3 style="font-size:13px;color:var(--bleu);margin-bottom:6px">Assiduité (30 jours)</h3>
            @php $couleur = $assiduite['taux'] >= 80 ? 'var(--ok)' : ($assiduite['taux'] >= 50 ? 'var(--warn)' : 'var(--ko)'); @endphp
            <div style="font-size:24px;font-weight:700;color:{{ $couleur }}">{{ $assiduite['taux'] }}%</div>
            <div class="jauge-wrap"><div class="jauge-bar" style="width:{{ $assiduite['taux'] }}%;background:{{ $couleur }}"></div></div>
            <div style="font-size:11px;color:var(--gris);margin-top:4px">{{ $assiduite['jours_saisis'] }} / {{ $assiduite['jours_ouvres'] }} jours ouvr&eacute;s saisis</div>
            <a href="{{ route('saisie.index') }}" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600">Voir mon suivi de saisie &rarr;</a>
        </div>
    </div>
</div>

{{-- ═══ Section responsable projet (dynamique) ═══ --}}
@if($estResponsable && $projetData)
<div class="section-sep">
    <h2>
        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Mes projets (responsable)
    </h2>

    {{-- KPI projets --}}
    <div class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr))">
        @foreach($projetData['mesProjets'] as $p)
        @php $pct = $p->taches_count > 0 ? round(($p->terminees_count / $p->taches_count) * 100) : 0; @endphp
        <div class="kpi">
            <div class="lbl">{{ $p->libelle }}</div>
            <div class="val" style="font-size:22px">{{ $pct }}%</div>
            <div class="jauge-wrap" style="height:8px;margin-top:4px"><div class="jauge-bar" style="width:{{ $pct }}%;background:{{ $pct >= 70 ? 'var(--ok)' : ($pct >= 40 ? 'var(--warn)' : 'var(--accent)') }}"></div></div>
            <div class="sub" style="margin-top:4px">{{ $p->terminees_count }}/{{ $p->taches_count }} t&acirc;ches
                @if($p->en_retard_count > 0) &middot; <span style="color:var(--ko)">{{ $p->en_retard_count }} retard</span>@endif
                @if($p->bloquees_count > 0) &middot; <span style="color:var(--ko)">{{ $p->bloquees_count }} bloq.</span>@endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="grille">
        {{-- Sprints en cours --}}
        <div class="panneau">
            <h3>
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
                Sprints en cours ({{ $projetData['sprintsEnCours']->count() }})
            </h3>
            @forelse($projetData['sprintsEnCours'] as $s)
            <div class="sprint-item">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                    <span style="font-size:13px;font-weight:600;color:var(--bleu)">Sprint {{ $s->numero }} &mdash; {{ $s->projet->libelle ?? '' }}</span>
                    @if($s->jours_restants !== null)
                    <span class="badge {{ $s->jours_restants <= 3 ? 'b-ko' : 'b-ok' }}">{{ $s->jours_restants }}j</span>
                    @endif
                </div>
                <div class="jauge-wrap" style="height:6px"><div class="jauge-bar" style="width:{{ $s->progression ?? 0 }}%"></div></div>
                <div style="display:flex;justify-content:space-between;margin-top:3px">
                    <span style="font-size:10.5px;color:var(--gris)">{{ $s->stats_terminees ?? 0 }}/{{ $s->stats_total ?? 0 }}</span>
                    <a href="{{ route('sprints.show', $s) }}" class="lien">D&eacute;tail &rarr;</a>
                </div>
            </div>
            @empty
            <div style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Aucun sprint en cours.</div>
            @endforelse
        </div>

        {{-- Membres de l'équipe --}}
        <div class="panneau">
            <h3>
                <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                &Eacute;quipe projet
            </h3>
            @foreach($projetData['membres'] as $m)
            @php $barColor = $m->taux >= 70 ? 'var(--ok)' : ($m->taux >= 40 ? 'var(--warn)' : 'var(--ko)'); @endphp
            <div class="membre-line">
                <div class="membre-avatar">{{ mb_strtoupper(mb_substr($m->name,0,1).mb_substr($m->prenom ?? '',0,1)) }}</div>
                <div style="flex:1">
                    <div style="font-size:13px;font-weight:600">{{ $m->nomComplet() }}</div>
                    <div style="font-size:10.5px;color:var(--gris)">
                        {{ $m->taches_en_cours }} en cours
                        @if($m->taches_bloquees > 0)&middot; <span style="color:var(--ko)">{{ $m->taches_bloquees }} bloq.</span>@endif
                    </div>
                    <div class="jauge-wrap" style="height:4px;margin-top:3px"><div class="jauge-bar" style="width:{{ $m->taux }}%;background:{{ $barColor }}"></div></div>
                </div>
                <span style="font-size:12px;font-weight:700;color:var(--bleu)">{{ $m->taux }}%</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Tâches bloquées sur mes projets --}}
    @if($projetData['topBloquees']->count() > 0)
    <div class="panneau" style="margin-bottom:14px">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            T&acirc;ches bloqu&eacute;es sur mes projets
        </h3>
        <table class="mini-table">
            <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>Projet</th></tr></thead>
            <tbody>
            @foreach($projetData['topBloquees'] as $t)
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 35) }}</a></td>
                <td>{{ $t->user->nomComplet() }}</td>
                <td style="font-size:11px;color:var(--gris)">{{ $t->projet->libelle ?? '—' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif
@endsection
