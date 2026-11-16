@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Horaires de travail')
@push('styles')
<style>
.mini-table{width:100%;border-collapse:collapse;font-size:12.5px}
.mini-table th{text-align:left;padding:8px 10px;background:var(--fond);color:var(--gris);font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.3px}
.mini-table td{padding:7px 10px;border-bottom:1px solid var(--bord);vertical-align:top}
.mini-table tr:hover{background:#f8fafc}
.badge{padding:2px 8px;border-radius:8px;font-size:10px;font-weight:600;display:inline-block;white-space:nowrap}
.b-ok{background:#dcfce7;color:var(--ok)}
.b-info{background:#dbeafe;color:#1d4ed8}
.b-gris{background:#e2e8f0;color:#475569}
.btn{background:var(--accent);color:#fff;border:none;padding:8px 16px;border-radius:8px;font-size:13px;cursor:pointer;text-decoration:none}
.btn.sm{padding:4px 10px;font-size:11.5px;border-radius:6px}
.btn.danger{background:var(--ko)}
.alerte{display:flex;gap:10px;align-items:center;border-radius:10px;padding:10px 14px;font-size:12.5px;margin-bottom:12px}
.alerte.ok{background:#f0fdf4;border:1px solid #bbf7d0;color:var(--ok)}
.alerte.ko{background:#fef2f2;border:1px solid #fecaca;color:var(--ko)}
.hor-actuel{display:flex;gap:22px;flex-wrap:wrap;align-items:center;border:1.5px solid #93c5fd;background:#f8fbff;border-radius:12px;padding:14px 18px;margin-bottom:16px}
.hor-actuel .lab{font-size:10.5px;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;font-weight:600}
.hor-actuel .val{font-size:15px;color:var(--bleu);font-weight:600;margin-top:2px}
.hor-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px 16px}
.hor-form label{display:block;font-size:11.5px;color:var(--gris);font-weight:600;margin-bottom:4px}
.hor-form input[type=date],.hor-form input[type=time],.hor-form input[type=text]{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;box-sizing:border-box}
.hor-jours{display:flex;gap:6px;flex-wrap:wrap}
.hor-jours label{display:flex;align-items:center;gap:5px;border:1px solid var(--bord);border-radius:8px;padding:6px 10px;font-size:12.5px;color:var(--texte);font-weight:500;margin:0;cursor:pointer}
.hor-jours input:checked + span{color:var(--accent);font-weight:600}
.aide{font-size:11.5px;color:var(--gris);margin-top:10px}
.hor-bloc{grid-column:1/-1;border-top:1px dashed var(--bord);padding-top:12px;margin-top:2px}
.hor-bloc h4{margin:0 0 4px;font-size:12.5px;color:var(--bleu)}
.hor-bloc .sous{font-size:11.5px;color:var(--gris);margin:0 0 10px}
.hor-ligne{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end}
.hor-ligne>div{min-width:150px}
.hor-ligne input[type=number]{width:100%;padding:8px 10px;border:1px solid var(--bord);border-radius:8px;font-size:13px;box-sizing:border-box}
.hor-pj{width:auto;border-collapse:collapse;font-size:12.5px}
.hor-pj td:nth-child(3){padding:5px 10px;color:var(--gris)}
.hor-pj td{padding:5px 8px 5px 0}
.hor-pj td:first-child{width:110px;font-weight:600;color:var(--texte)}
.hor-pj input[type=time]{width:130px;padding:6px 8px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px}
.hor-pj tr.off{opacity:.4}
.hor-pj .eff{font-size:11.5px;color:var(--gris)}
.hor-actuel .det{font-size:11.5px;color:var(--gris);margin-top:2px}
@media(max-width:900px){.hor-form{grid-template-columns:1fr}.mini-table{display:block;overflow-x:auto}}
</style>
@endpush
@section('contenu')
<h2>Horaires et jours de travail</h2>
<div class="crumb">Administration &rsaquo; Référentiels &rsaquo; Horaires de travail</div>
@include('admin._nav')

@if(session('succes'))<div class="alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="alerte ko">{{ $errors->first() }}</div>@endif

<div class="hor-actuel">
    <div><div class="lab">En vigueur depuis</div><div class="val">{{ $enVigueur->date_effet->year <= 2000 ? 'Origine' : $enVigueur->date_effet->format('d/m/Y') }}</div></div>
    <div><div class="lab">Jours travaillés</div><div class="val">{{ $enVigueur->joursLibelle() }}</div></div>
    <div><div class="lab">Horaires</div><div class="val">{{ $enVigueur->arrivee() }} → {{ $enVigueur->depart() }}</div>
        @foreach($enVigueur->exceptionsLibelle() as $exc)<div class="det">{{ $exc }}</div>@endforeach</div>
    <div><div class="lab">Pause</div><div class="val">{{ ($p = $enVigueur->pause()) ? $p[0] . ' → ' . $p[1] : 'Aucune' }}</div></div>
    <div><div class="lab">Durée / jour · semaine</div><div class="val">{{ str_replace('.', ',', $enVigueur->dureeJour()) }} h · {{ str_replace('.', ',', $enVigueur->dureeSemaine()) }} h</div></div>
    <div><div class="lab">Tolérance retard · départ</div><div class="val">{{ $enVigueur->tolerance_arrivee }} · {{ $enVigueur->tolerance_depart }} min</div></div>
    <div><div class="lab">Rappels automatiques</div><div class="val">{{ $enVigueur->heureControleSaisie() }} · {{ $enVigueur->heureDailyAuto() }}</div><div class="det">contrôle de saisie · Daily Scrum</div></div>
</div>

<div class="carte" style="margin-bottom:16px">
    <div id="form-horaires" style="display:flex;align-items:center;gap:10px;margin:0 0 12px">
        <h3 style="margin:0;font-size:14px;color:var(--bleu)">
            @if($edition)Modifier la programmation du {{ $edition->date_effet->format('d/m/Y') }}
            @elseif($base)Nouvelle règle à partir de celle {{ $base->date_effet->year <= 2000 ? "d'origine" : 'du ' . $base->date_effet->format('d/m/Y') }}
            @else Nouvelle règle @endif
        </h3>
        @if($base)<a href="{{ route('admin.horaires.index') }}" style="margin-left:auto;font-size:12px;color:var(--gris)">Annuler la modification</a>@endif
    </div>
    @if($base && !$edition)<div class="aide" style="margin:-4px 0 12px">Cette règle est déjà entrée en vigueur : ses valeurs sont reprises dans une nouvelle règle, applicable à partir de la date choisie. L'historique reste inchangé.</div>@endif
    <form method="POST" action="{{ route('admin.horaires.store') }}">
        @csrf
        @if($edition)<input type="hidden" name="remplace_id" value="{{ $edition->id }}">@endif
        <div class="hor-form">
            <div>
                <label>Applicable à partir du</label>
                <input type="date" name="date_effet" required min="{{ today()->toDateString() }}" value="{{ old('date_effet', $edition ? $edition->date_effet->toDateString() : today()->addDay()->toDateString()) }}">
            </div>
            <div>
                <label>Heure de début (retard au-delà)</label>
                <input type="time" name="heure_arrivee" required value="{{ old('heure_arrivee', $modele->arrivee()) }}">
            </div>
            <div>
                <label>Heure de fin (départ anticipé avant)</label>
                <input type="time" name="heure_depart" required value="{{ old('heure_depart', $modele->depart()) }}">
            </div>
            <div style="grid-column:1/-1">
                <label>Jours travaillés</label>
                @php $coches = array_map('intval', old('jours', $modele->joursTravailles())); @endphp
                <div class="hor-jours">
                    @foreach(\App\Models\HoraireTravail::JOURS as $n => $nom)
                    <label><input type="checkbox" name="jours[]" value="{{ $n }}" @checked(in_array($n, $coches, true))><span>{{ $nom }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="hor-bloc">
                <h4>Horaires propres à certains jours</h4>
                <p class="sous">Laissez vide pour appliquer l'horaire général ci-dessus (ex. : vendredi 08:00 → 16:00, samedi 08:00 → 13:00).</p>
                @php $pj = old('par_jour', collect(range(1, 7))->mapWithKeys(fn($j) => [$j => $modele->horaireDuJour($j) ?? []])->all()); @endphp
                <table class="hor-pj" id="hor-pj">
                    @foreach(\App\Models\HoraireTravail::JOURS as $n => $nom)
                    <tr data-jour="{{ $n }}" class="{{ in_array($n, $coches, true) ? '' : 'off' }}">
                        <td>{{ $nom }}</td>
                        <td><input type="time" name="par_jour[{{ $n }}][arrivee]" value="{{ $pj[$n]['arrivee'] ?? '' }}" title="Début ({{ $nom }})"></td>
                        <td>→</td>
                        <td><input type="time" name="par_jour[{{ $n }}][depart]" value="{{ $pj[$n]['depart'] ?? '' }}" title="Fin ({{ $nom }})"></td>
                        <td class="eff"></td>
                    </tr>
                    @endforeach
                </table>
            </div>
            <div class="hor-bloc">
                <h4>Pause et tolérances</h4>
                <p class="sous">La pause est déduite de la durée de travail et de la durée de présence. La tolérance évite de compter un retard ou un départ anticipé de quelques minutes.</p>
                <div class="hor-ligne">
                    <div><label>Début de pause</label><input type="time" name="pause_debut" value="{{ old('pause_debut', $modele->pause()[0] ?? '') }}"></div>
                    <div><label>Fin de pause</label><input type="time" name="pause_fin" value="{{ old('pause_fin', $modele->pause()[1] ?? '') }}"></div>
                    <div><label>Tolérance de retard (min)</label><input type="number" name="tolerance_arrivee" min="0" max="120" value="{{ old('tolerance_arrivee', $modele->tolerance_arrivee ?? 0) }}"></div>
                    <div><label>Tolérance de départ (min)</label><input type="number" name="tolerance_depart" min="0" max="120" value="{{ old('tolerance_depart', $modele->tolerance_depart ?? 0) }}"></div>
                </div>
            </div>
            <div class="hor-bloc">
                <h4>Rappels automatiques</h4>
                <p class="sous">Heures d'exécution quotidienne : contrôle des saisies de la veille (notifications) et création automatique des Daily Scrum programmés.</p>
                <div class="hor-ligne">
                    <div><label>Contrôle de saisie</label><input type="time" name="heure_controle_saisie" required value="{{ old('heure_controle_saisie', $modele->heureControleSaisie()) }}"></div>
                    <div><label>Création des Daily Scrum</label><input type="time" name="heure_daily_auto" required value="{{ old('heure_daily_auto', $modele->heureDailyAuto()) }}"></div>
                </div>
            </div>
            <div style="grid-column:1/-1">
                <label>Motif du changement</label>
                <input type="text" name="motif" required maxlength="255" value="{{ old('motif', $edition?->motif) }}" placeholder="Ex. : horaires du Ramadan, note de service n°…">
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px"><button class="btn">{{ $edition ? 'Enregistrer la modification' : 'Enregistrer' }}</button></div>
    </form>
    <script>
    // Horaires par jour : grisés pour les jours non travaillés ; horaire effectif affiché
    (function () {
        const f = document.querySelector('form[action="{{ route('admin.horaires.store') }}"]');
        const maj = () => {
            const ga = f.heure_arrivee.value, gd = f.heure_depart.value;
            f.querySelectorAll('#hor-pj tr').forEach(tr => {
                const j = tr.dataset.jour, coche = f.querySelector(`input[name="jours[]"][value="${j}"]`).checked;
                const a = tr.querySelector('[name$="[arrivee]"]'), d = tr.querySelector('[name$="[depart]"]');
                tr.classList.toggle('off', !coche);
                a.placeholder = ga; d.placeholder = gd;
                tr.querySelector('.eff').textContent = coche ? ((a.value || d.value) ? 'horaire propre' : 'horaire général ' + ga + ' → ' + gd) : 'non travaillé';
            });
        };
        f.addEventListener('input', maj); f.addEventListener('change', maj); maj();
    })();
    </script>
    <div class="aide">
        La règle s'applique à partir de sa date d'effet, sans effet rétroactif : pointages, jours ouvrés, retards et statistiques
        des périodes passées restent calculés avec les horaires de l'époque. Les jours fériés et les jours ouvrables spéciaux s'ajoutent à ces règles.
    </div>
</div>

<div class="carte" style="padding:0;overflow-x:auto">
<table class="mini-table">
<thead><tr><th>Période d'application</th><th>Jours travaillés</th><th>Horaires</th><th>Pause · tolérances</th><th>Durée / semaine</th><th>Rappels</th><th>Motif</th><th>Enregistré par</th><th>Statut</th><th></th></tr></thead>
<tbody>
@foreach($regles as $i => $h)
@php
    $suivante = $regles[$i - 1] ?? null;
    $statut = $h->date_effet->isFuture() ? ['À venir', 'b-info'] : ($h->is($enVigueur) ? ['En vigueur', 'b-ok'] : ['Historique', 'b-gris']);
@endphp
<tr>
    <td style="white-space:nowrap">{{ $h->date_effet->year <= 2000 ? 'Origine' : 'Du ' . $h->date_effet->format('d/m/Y') }}{{ $suivante ? ' au ' . $suivante->date_effet->copy()->subDay()->format('d/m/Y') : '' }}</td>
    <td>{{ $h->joursLibelle() }}</td>
    <td style="white-space:nowrap">{{ $h->arrivee() }} → {{ $h->depart() }}@foreach($h->exceptionsLibelle() as $exc)<br><span style="font-size:11px;color:var(--gris)">{{ $exc }}</span>@endforeach</td>
    <td style="white-space:nowrap">{{ ($p = $h->pause()) ? $p[0] . ' → ' . $p[1] : 'Sans pause' }}<br><span style="font-size:11px;color:var(--gris)">{{ $h->tolerance_arrivee }} / {{ $h->tolerance_depart }} min</span></td>
    <td>{{ str_replace('.', ',', $h->dureeSemaine()) }} h</td>
    <td style="white-space:nowrap">{{ $h->heureControleSaisie() }} · {{ $h->heureDailyAuto() }}</td>
    <td>{{ $h->motif ?? '-' }}</td>
    <td style="white-space:nowrap">{{ $h->createur?->nomComplet() ?? '-' }}@if($h->createur)<br><span style="font-size:11px;color:var(--gris)">{{ $h->updated_at?->format('d/m/Y H:i') }}</span>@endif</td>
    <td><span class="badge {{ $statut[1] }}">{{ $statut[0] }}</span></td>
    <td style="white-space:nowrap">
        @if($h->date_effet->isFuture())
        <a class="btn sm" href="{{ route('admin.horaires.index', ['modifier' => $h->id]) }}#form-horaires" title="Modifier cette programmation à venir">Modifier</a>
        <form method="POST" action="{{ route('admin.horaires.destroy', $h) }}" onsubmit="return confirm('Annuler cette règle à venir ?')" style="display:inline">@csrf @method('DELETE')
            <button class="btn sm danger">Annuler</button>
        </form>
        @elseif($h->is($enVigueur))
        <a class="btn sm" href="{{ route('admin.horaires.index', ['modifier' => $h->id]) }}#form-horaires" title="Reprendre ces horaires dans une nouvelle règle à venir">Modifier</a>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
