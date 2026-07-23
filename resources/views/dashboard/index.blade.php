@extends('layouts.app')
@section('titre', 'Tableau de bord')
@push('styles')
<style>
.kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:20px}
.kpi{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:16px}
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
    <div class="kpi"><div class="lbl">En retard</div><div class="val" style="color:var(--ko)">{{ $enRetard }}</div></div>
    <div class="kpi">
        <div class="lbl">Complétion</div><div class="val">{{ $tauxCompletion }}%</div>
        <div class="jauge-wrap"><div class="jauge-bar" style="width:{{ $tauxCompletion }}%"></div></div>
    </div>
</div>

<div class="grille">
    <div class="panneau">
        <h3>
            <svg class="ic" viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="4"/><polyline points="8 12 11 15 16 9"/></svg>
            Tâches du jour ({{ $tachesDuJour->count() }})
        </h3>
        @forelse($tachesDuJour as $t)
        <div class="tache-line">
            <div>
                <span class="typ {{ strtolower($t->type) }}">{{ $t->type }}</span>
                <a href="{{ route('taches.show', $t) }}" style="text-decoration:none;color:#1e293b">{{ $t->intitule }}</a>
            </div>
            <span style="font-size:11px;color:var(--gris)">{{ $t->echeance?->format('d/m') ?? '' }}</span>
        </div>
        @empty
        <p style="color:var(--gris);font-size:13px">Aucune tâche active aujourd'hui.</p>
        @endforelse
        <div style="margin-top:12px"><a href="{{ route('taches.index') }}" class="btn">Voir toutes mes activités</a></div>
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
            <div style="font-size:11px;color:var(--gris);margin-top:4px">{{ $assiduite['jours_saisis'] }} / {{ $assiduite['jours_ouvres'] }} jours ouvrés saisis</div>
            <a href="{{ route('saisie.index') }}" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:600">Voir mon suivi de saisie &rarr;</a>
        </div>
    </div>
</div>
@endsection
