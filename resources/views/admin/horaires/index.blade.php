@extends('layouts.app')
@section('sousTitre', 'Administration')
@section('titre', 'Administration - Horaires de travail')
@push('styles')
<style>
.hr-vigueur{background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:14px 18px;margin-bottom:16px}
.hr-vigueur-tete{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px}
.hr-vigueur-tete h3{font-size:14px;color:var(--bleu);margin:0}
.hr-vigueur-tete h3 span{font-weight:400;color:var(--gris);font-size:12.5px;margin-left:6px}
.hr-faits{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));border:1px solid var(--bord);border-radius:10px}
.hr-faits>div{padding:10px 14px;border-left:1px solid var(--bord)}
.hr-faits>div:first-child{border-left:none}
.hr-faits .lab{font-size:10.5px;color:var(--gris);text-transform:uppercase;letter-spacing:.3px;font-weight:600}
.hr-faits .val{font-size:14.5px;color:var(--bleu);font-weight:600;margin-top:3px}
.hr-faits .det{font-size:11.5px;color:var(--gris);margin-top:2px}
.hr-titre{font-size:13px;font-weight:700;color:var(--bleu);margin:4px 0 8px}
.hr-note{font-size:11.5px;color:var(--gris);margin:10px 2px 0;line-height:1.5}
/* Fenêtre : semaine type */
.fm-dlg.large{width:760px}
.hr-semaine{width:100%;border-collapse:collapse;font-size:12.5px;margin-top:6px}
.hr-semaine td{padding:5px 6px;border-bottom:1px solid #f1f5f9}
.hr-semaine tr:last-child td{border-bottom:none}
.hr-semaine .jour{width:130px}
.hr-semaine input[type=time]{width:118px;padding:6px 8px;border:1px solid var(--bord);border-radius:8px;font-size:12.5px;font-family:inherit}
.hr-semaine tr.off input[type=time]{opacity:.35;pointer-events:none}
.hr-semaine .eff{font-size:11.5px;color:var(--gris)}
.hr-semaine .eff b{color:var(--accent);font-weight:600}
.hr-jour{display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-weight:600;color:#334155}
.hr-jour input{width:15px;height:15px;accent-color:var(--accent)}
.hr-semaine tr.off .hr-jour{color:var(--gris);font-weight:400}
.fm-dlg input[type=time].fm-champ,.fm-dlg input[type=number].fm-champ{max-width:170px}
</style>
@endpush
@section('contenu')
@include('admin._ui')
@php
    $icEdit = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
    $icAnnul = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>';
    $aVenir = $regles->first(fn($h) => $h->date_effet->isFuture());
    $ouvrir = $base || $edition || $errors->any();
@endphp
<h2>Administration</h2>
<div class="crumb">Administration &rsaquo; Temps de travail &rsaquo; Horaires</div>
@include('admin._nav')

@if(session('succes'))<div class="ad-alerte ok">{{ session('succes') }}</div>@endif
@if(session('erreur'))<div class="ad-alerte ko">{{ session('erreur') }}</div>@endif
@if($errors->any())<div class="ad-alerte ko">{{ $errors->first() }}</div>@endif

{{-- Règle en vigueur --}}
<div class="hr-vigueur">
    <div class="hr-vigueur-tete">
        <h3>Horaires en vigueur <span>{{ $enVigueur->date_effet->year <= 2000 ? "depuis l'origine" : 'depuis le ' . $enVigueur->date_effet->translatedFormat('d F Y') }}@if($enVigueur->motif && $enVigueur->date_effet->year > 2000) · {{ $enVigueur->motif }}@endif</span></h3>
        <div style="display:flex;gap:8px;align-items:center">
            @if($aVenir)<span class="etat bleu">Changement programmé le {{ $aVenir->date_effet->translatedFormat('d M Y') }}</span>@endif
            <a class="btn-p" href="{{ route('admin.horaires.index', ['modifier' => $enVigueur->id]) }}" data-sans-icone>
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Programmer un changement</a>
        </div>
    </div>
    <div class="hr-faits">
        <div><div class="lab">Jours travaillés</div><div class="val">{{ $enVigueur->joursLibelle() }}</div></div>
        <div><div class="lab">Horaires</div><div class="val">{{ $enVigueur->arrivee() }} → {{ $enVigueur->depart() }}</div>
            @foreach($enVigueur->exceptionsLibelle() as $exc)<div class="det">{{ $exc }}</div>@endforeach</div>
        <div><div class="lab">Pause</div><div class="val">{{ ($p = $enVigueur->pause()) ? $p[0] . ' → ' . $p[1] : 'Aucune' }}</div></div>
        <div><div class="lab">Durée</div><div class="val">{{ str_replace('.', ',', $enVigueur->dureeSemaine()) }} h / semaine</div><div class="det">{{ str_replace('.', ',', $enVigueur->dureeJour()) }} h par jour</div></div>
        <div><div class="lab">Tolérances</div><div class="val">{{ $enVigueur->tolerance_arrivee }} · {{ $enVigueur->tolerance_depart }} min</div><div class="det">retard · départ anticipé</div></div>
        <div><div class="lab">Rappels</div><div class="val">{{ $enVigueur->heureControleSaisie() }} · {{ $enVigueur->heureDailyAuto() }}</div><div class="det">contrôle de saisie · Daily</div></div>
    </div>
</div>

{{-- Règles programmées et historique --}}
<div class="hr-titre">Règles programmées et historique</div>
<div class="ad-panneau">
<table class="ad-table">
<thead><tr><th>Période d'application</th><th>Jours</th><th>Horaires</th><th>Pause · tolérances</th><th>Semaine</th><th>Motif</th><th>Statut</th><th></th></tr></thead>
<tbody>
@foreach($regles as $i => $h)
@php
    $suivante = $regles[$i - 1] ?? null;
    $statut = $h->date_effet->isFuture() ? ['À venir', 'bleu'] : ($h->is($enVigueur) ? ['En vigueur', 'ok'] : ['Historique', 'mut']);
@endphp
<tr>
    <td style="white-space:nowrap">{{ $h->date_effet->year <= 2000 ? 'Origine' : 'Du ' . $h->date_effet->format('d/m/Y') }}{{ $suivante ? ' au ' . $suivante->date_effet->copy()->subDay()->format('d/m/Y') : '' }}
        @if($h->createur)<span class="sec">{{ $h->createur->nomComplet() }}, {{ $h->updated_at?->format('d/m/Y') }}</span>@endif</td>
    <td>{{ $h->joursLibelle() }}</td>
    <td style="white-space:nowrap">{{ $h->arrivee() }} → {{ $h->depart() }}@foreach($h->exceptionsLibelle() as $exc)<span class="sec">{{ $exc }}</span>@endforeach</td>
    <td style="white-space:nowrap">{{ ($p = $h->pause()) ? $p[0] . ' → ' . $p[1] : 'Sans pause' }}<span class="sec">{{ $h->tolerance_arrivee }} / {{ $h->tolerance_depart }} min</span></td>
    <td style="white-space:nowrap">{{ str_replace('.', ',', $h->dureeSemaine()) }} h</td>
    <td>{{ $h->motif ?? '—' }}</td>
    <td><span class="etat {{ $statut[1] }}">{{ $statut[0] }}</span></td>
    <td class="act">
        @if($h->date_effet->isFuture())
        <a class="ib" href="{{ route('admin.horaires.index', ['modifier' => $h->id]) }}" title="Modifier cette programmation" aria-label="Modifier">{!! $icEdit !!}</a>
        <form method="POST" action="{{ route('admin.horaires.destroy', $h) }}" onsubmit="return confirm('Annuler cette règle à venir ?')" style="display:inline">@csrf @method('DELETE')
            <button class="ib ko" data-sans-icone title="Annuler cette règle à venir" aria-label="Annuler">{!! $icAnnul !!}</button>
        </form>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
<p class="hr-note">Une règle s'applique à partir de sa date d'effet, sans effet rétroactif : pointages, jours ouvrés, retards et statistiques des périodes passées
    restent calculés avec les horaires de l'époque. Les jours fériés et les jours ouvrables spéciaux s'ajoutent à ces règles.</p>

{{-- Fenêtre : nouvelle règle --}}
<dialog id="dlg-horaire" class="fm-dlg large">
    <div class="fm-tete">
        <span class="fm-ic"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
        <div><h3>@if($edition)Modifier la programmation du {{ $edition->date_effet->format('d/m/Y') }}@else Programmer de nouveaux horaires @endif</h3>
            <p>@if($base && !$edition)Les valeurs de la règle {{ $base->date_effet->year <= 2000 ? "d'origine" : 'du ' . $base->date_effet->format('d/m/Y') }} sont reprises : modifiez ce qui change. @endif
               La nouvelle règle s'appliquera à partir de la date choisie ; l'historique reste inchangé.</p></div>
    </div>
    <form method="POST" action="{{ route('admin.horaires.store') }}" id="hr-form">
        @csrf
        @if($edition)<input type="hidden" name="remplace_id" value="{{ $edition->id }}">@endif
        <div class="fm-ligne2" style="grid-template-columns:180px 1fr">
            <div><label class="fm-lab" for="hr-date">Applicable à partir du <span class="req">*</span></label>
                <input type="date" name="date_effet" id="hr-date" class="fm-champ" required min="{{ today()->toDateString() }}" value="{{ old('date_effet', $edition ? $edition->date_effet->toDateString() : today()->addDay()->toDateString()) }}"></div>
            <div><label class="fm-lab" for="hr-motif">Motif du changement <span class="req">*</span></label>
                <input type="text" name="motif" id="hr-motif" class="fm-champ" required maxlength="255" value="{{ old('motif', $edition?->motif) }}" placeholder="Ex. : horaires du Ramadan, note de service n°…"></div>
        </div>

        <div class="fm-section"><h4>Semaine type</h4>
            <p class="fm-aide">Cochez les jours travaillés. L'horaire général s'applique à tous ; renseignez un jour seulement s'il diffère (ex. : samedi 08:00 → 12:00).</p>
            <div class="fm-ligne2" style="grid-template-columns:auto auto 1fr;align-items:end">
                <div><label class="fm-lab" for="hr-arr">Début de journée <span class="req">*</span></label><input type="time" name="heure_arrivee" id="hr-arr" class="fm-champ" required value="{{ old('heure_arrivee', $modele->arrivee()) }}"></div>
                <div><label class="fm-lab" for="hr-dep">Fin de journée <span class="req">*</span></label><input type="time" name="heure_depart" id="hr-dep" class="fm-champ" required value="{{ old('heure_depart', $modele->depart()) }}"></div>
                <p class="fm-aide" style="margin-bottom:8px">Au-delà du début : retard. Avant la fin : départ anticipé.</p>
            </div>
            @php
                $coches = array_map('intval', old('jours', $modele->joursTravailles()));
                $pj = old('par_jour', collect(range(1, 7))->mapWithKeys(fn($j) => [$j => $modele->horaireDuJour($j) ?? []])->all());
            @endphp
            <table class="hr-semaine" id="hr-semaine">
                @foreach(\App\Models\HoraireTravail::JOURS as $n => $nom)
                <tr data-jour="{{ $n }}">
                    <td class="jour"><label class="hr-jour"><input type="checkbox" name="jours[]" value="{{ $n }}" @checked(in_array($n, $coches, true))>{{ $nom }}</label></td>
                    <td><input type="time" name="par_jour[{{ $n }}][arrivee]" value="{{ $pj[$n]['arrivee'] ?? '' }}" aria-label="Début ({{ $nom }})" data-sans-controle></td>
                    <td style="color:var(--gris)">→</td>
                    <td><input type="time" name="par_jour[{{ $n }}][depart]" value="{{ $pj[$n]['depart'] ?? '' }}" aria-label="Fin ({{ $nom }})" data-sans-controle></td>
                    <td class="eff"></td>
                </tr>
                @endforeach
            </table>
        </div>

        <div class="fm-section"><h4>Pause et tolérances</h4>
            <div class="fm-ligne2" style="grid-template-columns:repeat(4,1fr)">
                <div><label class="fm-lab">Début de pause</label><input type="time" name="pause_debut" class="fm-champ" value="{{ old('pause_debut', $modele->pause()[0] ?? '') }}"></div>
                <div><label class="fm-lab">Fin de pause</label><input type="time" name="pause_fin" class="fm-champ" value="{{ old('pause_fin', $modele->pause()[1] ?? '') }}"></div>
                <div><label class="fm-lab">Tolérance retard</label><input type="number" name="tolerance_arrivee" class="fm-champ" min="0" max="120" value="{{ old('tolerance_arrivee', $modele->tolerance_arrivee ?? 0) }}"></div>
                <div><label class="fm-lab">Tolérance départ</label><input type="number" name="tolerance_depart" class="fm-champ" min="0" max="120" value="{{ old('tolerance_depart', $modele->tolerance_depart ?? 0) }}"></div>
            </div>
            <p class="fm-aide">La pause est déduite de la durée de travail. Les tolérances (en minutes) évitent de compter un retard ou un départ anticipé de quelques minutes.</p>
        </div>

        <div class="fm-ligne2" style="gap:24px">
            <div class="fm-section"><h4>Jours exceptionnels travaillés</h4>
                <div class="fm-ligne2">
                    <div><label class="fm-lab">Début</label><input type="time" name="exc_arrivee" class="fm-champ" value="{{ old('exc_arrivee', $modele->exceptionnel()[0]) }}"></div>
                    <div><label class="fm-lab">Fin</label><input type="time" name="exc_depart" class="fm-champ" value="{{ old('exc_depart', $modele->exceptionnel()[1]) }}"></div>
                </div>
                <p class="fm-aide">Samedi, dimanche ou jour férié déclaré ouvrable sans horaire propre.</p>
            </div>
            <div class="fm-section"><h4>Rappels automatiques</h4>
                <div class="fm-ligne2">
                    <div><label class="fm-lab">Contrôle de saisie <span class="req">*</span></label><input type="time" name="heure_controle_saisie" class="fm-champ" required value="{{ old('heure_controle_saisie', $modele->heureControleSaisie()) }}"></div>
                    <div><label class="fm-lab">Daily Scrum <span class="req">*</span></label><input type="time" name="heure_daily_auto" class="fm-champ" required value="{{ old('heure_daily_auto', $modele->heureDailyAuto()) }}"></div>
                </div>
                <p class="fm-aide">Heures d'exécution quotidienne : relance des saisies de la veille, création des Daily programmés.</p>
            </div>
        </div>

        <p class="fm-resume" id="hr-resume"></p>
        <div class="fm-pied">
            <a class="btn-s" href="{{ route('admin.horaires.index') }}" onclick="if(!{{ $ouvrir ? 'true' : 'false' }}){event.preventDefault();this.closest('dialog').close();}">Annuler</a>
            <button class="btn-p">{{ $edition ? 'Enregistrer la modification' : 'Programmer' }}</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
/* Semaine type : jours non cochés grisés, horaire effectif de chaque jour, durée hebdomadaire calculée en direct */
(function(){
    var f = document.getElementById('hr-form');
    var min = function(h){ if (!h) return null; var p = h.split(':'); return +p[0] * 60 + +p[1]; };
    var maj = function(){
        var ga = f.heure_arrivee.value, gd = f.heure_depart.value, pa = min(f.pause_debut.value), pf = min(f.pause_fin.value), total = 0, jours = [];
        f.querySelectorAll('#hr-semaine tr').forEach(function(tr){
            var coche = tr.querySelector('input[type=checkbox]').checked, a = tr.querySelector('[name$="[arrivee]"]'), d = tr.querySelector('[name$="[depart]"]');
            tr.classList.toggle('off', !coche); a.placeholder = ga; d.placeholder = gd;
            var da = a.value || ga, dd = d.value || gd, propre = !!(a.value || d.value);
            var duree = Math.max(0, min(dd) - min(da));
            if (pa !== null && pf !== null && pf > pa) duree -= Math.max(0, Math.min(pf, min(dd)) - Math.max(pa, min(da)));
            if (coche) { total += duree; jours.push(tr.querySelector('.hr-jour').textContent.trim().slice(0, 3)); }
            tr.querySelector('.eff').innerHTML = coche ? (propre ? '<b>horaire propre</b> · ' : 'horaire général · ') + (duree / 60).toFixed(1).replace('.', ',') + ' h' : 'non travaillé';
        });
        document.getElementById('hr-resume').innerHTML = jours.length
            ? '<b>' + (total / 60).toFixed(1).replace('.', ',') + ' h par semaine</b> sur ' + jours.length + ' jour' + (jours.length > 1 ? 's' : '') + ' (' + jours.join(', ') + ')' + (pa !== null && pf > pa ? ', pause déduite' : '')
            : '<span style="color:var(--ko)">Cochez au moins un jour travaillé.</span>';
    };
    f.addEventListener('input', maj); f.addEventListener('change', maj); maj();
    @if($ouvrir) document.getElementById('dlg-horaire').showModal(); @endif
})();
</script>
@endpush
