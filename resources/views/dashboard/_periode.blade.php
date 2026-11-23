{{--
    Période consultée par le tableau de bord : par défaut l'exercice en cours (du 05 du mois au 04 du mois suivant),
    ou toute période choisie. KPI, livraison des sprints, tâches bloquées, retard et performance suivent la période.
    @include('dashboard._periode', ['periode' => $periodeTdb])
--}}
@once
@push('styles')
<style>
.tdb-periode{display:flex;align-items:center;flex-wrap:wrap;gap:8px 12px;background:var(--carte);border:1px solid var(--bord);border-radius:12px;padding:10px 14px;margin-bottom:14px}
.tdb-periode .titre{display:flex;align-items:center;gap:8px;font-size:12.5px;color:#475569}
.tdb-periode .titre svg{width:16px;height:16px;color:#2e74b5;flex:none}
.tdb-periode .titre strong{color:#1f4e79}
.tdb-periode form{display:flex;align-items:center;flex-wrap:wrap;gap:6px;margin:0 0 0 auto;font-size:12px;color:var(--gris)}
.tdb-periode select,.tdb-periode input{padding:6px 9px;border:1px solid var(--bord);border-radius:8px;background:#fff;color:#334155;font-size:12px;font-family:inherit}
.tdb-periode input{width:134px}
.tdb-periode select:focus-visible,.tdb-periode input:focus-visible{outline:2px solid #2e74b5;outline-offset:1px}
.tdb-periode button{padding:6px 12px;border-radius:8px;border:1px solid #2e74b5;background:#2e74b5;color:#fff;font-size:12px;font-weight:600;cursor:pointer;font-family:inherit}
.tdb-periode button:hover{background:#245f96}
.tdb-periode .reinit{font-size:12px;color:#2e74b5;text-decoration:none;font-weight:600}
.tdb-periode .reinit:hover{text-decoration:underline}
@media(max-width:800px){.tdb-periode form{margin-left:0}}
</style>
@endpush
@endonce
<div class="tdb-periode">
    <div class="titre">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        <span>Période : <strong>{{ $periode['libelle'] }}</strong></span>
        @unless($periode['courante'])<a class="reinit" href="{{ route('dashboard') }}">Revenir à l'exercice en cours</a>@endunless
    </div>
    <form method="GET" action="{{ route('dashboard') }}" id="tdb-periode-form">
        <select aria-label="Exercice" onchange="if(this.value){const o=this.selectedOptions[0];this.form.du.value=o.dataset.du;this.form.au.value=o.dataset.au;this.form.submit();}">
            <option value="">Exercice…</option>
            @foreach($periode['exercices'] as $ex)
            <option value="{{ $ex['du'] }}" data-du="{{ $ex['du'] }}" data-au="{{ $ex['au'] }}" @selected($ex['du'] === $periode['du'] && $ex['au'] === $periode['au'])>{{ $ex['nom'] }}</option>
            @endforeach
        </select>
        <label for="tdb-du">du</label><input type="date" id="tdb-du" name="du" value="{{ $periode['du'] }}" required>
        <label for="tdb-au">au</label><input type="date" id="tdb-au" name="au" value="{{ $periode['au'] }}" required>
        <button type="submit">Afficher</button>
    </form>
</div>
