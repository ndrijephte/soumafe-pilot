{{--
    Niveau d'exécution d'une user story : barre, pourcentage, origine (déclaré / calculé) et déclaration par les développeurs.
    @include('scrum._niveau_us', ['us' => $us, 'taches' => $tachesOptionnelles])
--}}
@php
    $pctN = \App\Services\AvancementScrum::pctStory($us, $taches ?? null);
    $origN = \App\Services\AvancementScrum::origine($us);
    $peutN = auth()->user() && \App\Services\AvancementScrum::peutDeclarer(auth()->user(), $us);
    $titreN = $origN === 'declare'
        ? 'Déclaré' . ($us->avancementPar ? ' par ' . $us->avancementPar->nomComplet() : '') . ($us->avancement_le ? ' le ' . $us->avancement_le->format('d/m/Y') : '') . ($us->avancement_note ? ' : ' . $us->avancement_note : '')
        : ($us->estFinie() ? 'Story terminée' : 'Calculé à partir des tâches terminées');
@endphp
@once
@push('styles')
<style>
.niv{display:inline-flex;align-items:center;gap:7px;position:relative;font-size:11.5px;color:#475569;white-space:nowrap}
.niv .nb{width:64px;height:5px;background:#e5eaf1;border-radius:5px;overflow:hidden}.niv .nb i{display:block;height:100%;background:#2e74b5;border-radius:5px}
.niv.fini .nb i{background:#16a34a}
.niv b{color:#1f2937;font-weight:700;min-width:30px}
.niv .org{font-size:10px;padding:1px 6px;border-radius:999px;background:#f1f5f9;color:#64748b}
.niv .org.declare{background:#eef4fb;color:#2e74b5}
.niv details{position:relative}
.niv summary{list-style:none;cursor:pointer;font-size:11px;font-weight:600;color:#2e74b5;padding:1px 6px;border:1px solid #cbd5e1;border-radius:6px;background:#fff}
.niv summary::-webkit-details-marker{display:none}
.niv .pop{position:absolute;right:0;top:calc(100% + 6px);z-index:40;width:250px;background:#fff;border:1px solid var(--bord);border-radius:10px;box-shadow:0 10px 24px rgba(15,23,42,.14);padding:10px 12px;white-space:normal}
.niv .pop label{display:block;font-size:10.5px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.3px;margin:0 0 4px}
.niv .pop .pas{display:grid;grid-template-columns:repeat(6,1fr);gap:4px;margin-bottom:8px}
.niv .pop .pas label{margin:0;text-transform:none;letter-spacing:0}
.niv .pop .pas input{display:none}
.niv .pop .pas span{display:block;text-align:center;padding:4px 0;border:1px solid #e2e8f0;border-radius:6px;font-size:11px;font-weight:600;color:#334155;cursor:pointer}
.niv .pop .pas input:checked+span{background:#2e74b5;border-color:#2e74b5;color:#fff}
.niv .pop input[type=text]{width:100%;box-sizing:border-box;padding:5px 8px;border:1px solid var(--bord);border-radius:6px;font-size:12px}
.niv .pop .act{display:flex;justify-content:space-between;align-items:center;margin-top:8px}
.niv .pop button{font-size:11.5px;padding:4px 10px;border-radius:6px;cursor:pointer;font-family:inherit}
.niv .pop .ok{background:#2e74b5;color:#fff;border:none}
.niv .pop .raz{background:none;border:none;color:#64748b;text-decoration:underline;padding:0}
</style>
@endpush
@endonce
<span class="niv {{ $pctN >= 100 ? 'fini' : '' }}" title="{{ $titreN }}">
    <span class="nb"><i style="width:{{ $pctN }}%"></i></span><b>{{ $pctN }} %</b>
    <span class="org {{ $origN }}">{{ $origN === 'declare' ? 'déclaré' : 'calculé' }}</span>
    @if($peutN && !$us->estFinie())
    <details>
        <summary title="Indiquer le niveau d'exécution de la story">Déclarer</summary>
        <form method="POST" action="{{ route('user-stories.avancement', $us) }}" class="pop">@csrf @method('PATCH')
            <label>Niveau d'exécution - {{ $us->numero }}</label>
            <div class="pas">
                @foreach(range(0, 100, 10) as $p)
                <label><input type="radio" name="avancement" value="{{ $p }}" @checked($us->avancement === $p || ($us->avancement === null && $p === (int) (round($pctN / 10) * 10)))><span>{{ $p }}</span></label>
                @endforeach
            </div>
            <input type="text" name="avancement_note" maxlength="500" value="{{ $us->avancement_note }}" placeholder="Note (facultative) : reste à faire, difficulté…">
            <div class="act">
                @if($us->avancement !== null)<button type="submit" name="avancement" value="" class="raz" title="Revenir au calcul à partir des tâches">Revenir au calcul</button>@else<span></span>@endif
                <button class="ok">Enregistrer</button>
            </div>
        </form>
    </details>
    @endif
</span>
