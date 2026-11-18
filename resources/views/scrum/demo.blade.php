@extends('layouts.app')
@section('sousTitre', 'Projets & Sprints')
@section('titre', 'Rapport de review - Sprint ' . $sprint->numero . ' - ' . $sprint->projet->libelle)

@push('styles')
<style>
.demo-grille{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:14px;align-items:start}
.demo-cr{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
.demo-cr h3{margin:0;font-size:15px;color:var(--bleu)}
.demo-cr .meta{font-size:12.5px;color:var(--gris);margin-top:3px}
.demo-cr .actions{margin-left:auto;display:flex;gap:8px}
.demo-form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.demo-form .large{grid-column:span 2}
.demo-liste{list-style:none;margin:0;padding:0}
.demo-liste li{display:flex;gap:10px;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:13px;color:#334155}
.demo-liste li:last-child{border-bottom:0}
.demo-liste b{color:var(--accent);white-space:nowrap}
.demo-liste small{display:block;color:var(--gris);font-size:11.5px}
.circuit{display:flex;gap:0;margin:0 0 14px;flex-wrap:wrap}
.circuit .et{flex:1 1 140px;display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:12.5px;color:var(--gris);border-bottom:3px solid #e2e8f0}
.circuit .et b{flex:none;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11.5px;background:#e2e8f0;color:#64748b}
.circuit .et.fait{color:#15803d;border-color:#86efac}.circuit .et.fait b{background:#15803d;color:#fff}
.circuit .et.cours{color:var(--bleu);font-weight:600;border-color:var(--accent)}.circuit .et.cours b{background:var(--accent);color:#fff}
.demo-note{margin-top:12px;padding:9px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12.5px;color:#475569;line-height:1.5}
.renvoi{margin-top:10px;padding:8px 12px;border:1px solid #fcd34d;background:#fffbeb;border-radius:8px;font-size:12.5px;color:#92400e}
@media(max-width:900px){.demo-grille,.demo-form{grid-template-columns:1fr}.demo-form .large{grid-column:auto}}
</style>
@endpush

@section('contenu')
@include('scrum._entete', ['sprint' => $sprint, 'actif' => 'demo', 'page' => 'Rapport de review'])

@php
    // Circuit : séance de review → rédaction (le pilote, ou le responsable du projet qui lui transmet) → validation par le pilote → Review des développeurs
    $rang = $verrou ? 0 : (!$cr ? 1 : ($cr->estBrouillon() ? 2 : ($cr->estEnRevision() ? 3 : 5)));
    $etapes = ['Séance de review', 'Rédaction', 'Validation', 'Review des développeurs'];
@endphp
<div class="circuit">
    @foreach($etapes as $i => $lib)
    <div class="et {{ $i + 1 < $rang ? 'fait' : ($i + 1 === $rang ? 'cours' : '') }}"><b>{!! $i + 1 < $rang ? '&#10003;' : $i + 1 !!}</b>{{ $lib }}</div>
    @endforeach
</div>
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
    @if($verrou)<span class="pill p-attente">Rapport à venir</span><span style="font-size:12px;color:var(--gris)">{{ $verrou }}</span>
    @elseif(!$cr)<span class="pill p-attente">Rapport non préparé</span><span style="font-size:12px;color:var(--gris)">Après la séance de review avec le client, le pilote prépare le rapport.</span>
    @elseif($cr->estConfirme())<span class="pill p-ok">Rapport validé</span><span style="font-size:12px;color:var(--gris)">Ses user stories ont rejoint le Product Backlog et ses décisions sont suivies en tâches. La Review des développeurs est ouverte.</span>
    @elseif($cr->estEnRevision())<span class="pill p-warn">Transmis au pilote</span><span style="font-size:12px;color:var(--gris)">Rédigé par {{ $cr->redacteur?->nomComplet() }} : le pilote le valide ou le renvoie.</span>
    @else<span class="pill p-warn">En rédaction</span><span style="font-size:12px;color:var(--gris)">{{ $mainResponsable ? 'Rédaction confiée à ' . $cr->redacteur?->nomComplet() . ', qui le transmettra au pilote pour validation.' : 'Le pilote rédige et valide le rapport.' }}</span>@endif
</div>

<div class="demo-grille">
<div class="carte">
    @if($cr)
    <div class="demo-cr">
        <div>
            <h3>{{ $cr->titre }}</h3>
            <div class="meta">{{ $cr->date_reunion->format('d/m/Y') }}@if($cr->heure_debut) · {{ substr($cr->heure_debut, 0, 5) }}@endif @if($cr->lieu) · {{ $cr->lieu }}@endif · Rédacteur : <strong style="color:#1e293b">{{ $cr->redacteur?->nomComplet() ?? '—' }}</strong></div>
        </div>
        <div class="actions">
            @if($peutRediger)<a class="btn" href="{{ route('comptes-rendus.edit', $cr) }}">{{ $cr->estEnRevision() ? 'Valider le rapport' : 'Rédiger le rapport' }}</a>@endif
            <a class="btn sec" href="{{ route('comptes-rendus.show', $cr) }}">Consulter</a>
            @if($cr->estConfirme() && $cr->fichier_path)<a class="btn sec" href="{{ route('comptes-rendus.telecharger', $cr) }}">DOCX</a>@endif
        </div>
    </div>
    @if(!empty(($cr->getDonnees()['relecture'] ?? [])['renvoi']))
    @php $rv = $cr->getDonnees()['relecture']['renvoi']; @endphp
    <div class="renvoi"><strong>Renvoyé par {{ $rv['par'] }}</strong> le {{ $rv['le'] }} : {{ $rv['motif'] }}</div>
    @endif
    @if($cr->estBrouillon())
    <div class="demo-note">L'observation de chaque tâche reprend ce que son développeur a consigné sur la tâche : blocages déclarés, commentaires de suivi, qualification. Développeurs : commentez vos tâches, c'est ce qui alimente le rapport.</div>
    @endif
    @elseif($verrou)
    <p style="margin:0;font-size:13px;color:var(--gris)">Le rapport se prépare une fois le sprint clôturé.</p>
    @elseif($peutPreparer)
    <h3 class="bloc-titre" style="font-size:14px;font-weight:700;color:var(--bleu);margin:0 0 10px">Préparer le rapport de review</h3>
    <form method="POST" action="{{ route('comptes-rendus.designer') }}">@csrf
        <input type="hidden" name="type" value="demo_sprint">
        <input type="hidden" name="sprint_id" value="{{ $sprint->id }}">
        <input type="hidden" name="titre" value="Rapport de Review - Sprint {{ $sprint->numero }} - {{ $sprint->projet->libelle }}">
        <div class="demo-form">
            <div class="champ"><label>Date de la review</label><input type="date" name="date_reunion" value="{{ today()->toDateString() }}" required></div>
            <div class="champ"><label>Heure</label><input type="time" name="heure_debut"></div>
            <div class="champ large"><label>Lieu</label><input name="lieu" maxlength="255" placeholder="Locaux du client, visioconférence…"></div>
        </div>
        <div style="margin-top:12px;display:flex;align-items:center;gap:12px;flex-wrap:wrap"><button class="btn">Créer le rapport</button><span style="font-size:12px;color:var(--gris)">Vous en serez le rédacteur ; vous pourrez passer la main au responsable du projet depuis l'éditeur.</span></div>
    </form>
    @else
    <p style="margin:0;font-size:13px;color:var(--gris)">Le rapport sera préparé par le pilote. Commentez vos tâches (blocages, remarques) : ces commentaires alimentent l'observation de chaque tâche.</p>
    @endif
</div>

<div class="carte">
    <h3 style="font-size:14px;font-weight:700;color:var(--bleu);margin:0 0 6px">À présenter : {{ $aPresenter->count() }} user stor{{ $aPresenter->count() > 1 ? 'ies' : 'y' }} terminée{{ $aPresenter->count() > 1 ? 's' : '' }}</h3>
    @if($aPresenter->isEmpty())
    <p style="margin:0;font-size:13px;color:var(--gris)">Aucune user story terminée dans ce sprint.</p>
    @else
    <ul class="demo-liste">
        @foreach($aPresenter as $us)
        <li><b>{{ $us->numero }}</b><span>{{ $us->intitule }}@if($us->module)<small>{{ $us->module }}{{ $us->epic ? ' › ' . $us->epic : '' }}</small>@endif</span></li>
        @endforeach
    </ul>
    @endif
</div>
</div>
@endsection
