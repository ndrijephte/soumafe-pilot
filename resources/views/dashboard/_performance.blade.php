{{-- Performance équipe : période libre (du ... au), avec les exercices en raccourci ; le bloc se recharge sans recharger la page --}}
@push('styles')
<style>
.perf-tete{margin-bottom:12px}
.perf-tete h3{margin:0 0 10px}
.perf-periode{display:flex;align-items:center;flex-wrap:wrap;gap:6px 8px;font-size:12px;color:var(--gris)}
.perf-periode select,.perf-periode input{padding:5px 8px;border:1px solid var(--bord);border-radius:8px;background:#fff;color:#334155;font-size:12px}
.perf-periode select{flex:1 1 150px;min-width:0}
.perf-periode input{width:128px}
.perf-periode select:focus-visible,.perf-periode input:focus-visible{outline:2px solid #2e74b5;outline-offset:1px}
#perf-contenu{transition:opacity .15s}
#perf-contenu[aria-busy=true]{opacity:.45}
.perf-vide{font-size:12.5px;color:var(--gris);padding:18px 0;text-align:center}
.perf-erreur{font-size:11.5px;color:var(--ko);margin-bottom:8px}
</style>
@endpush
<div class="panneau">
    <div class="perf-tete">
        <h3>
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Performance &eacute;quipe
        </h3>
        <div class="perf-periode" id="perf-periode" data-url="{{ route('dashboard.performance') }}">
            <select id="perf-exercice" aria-label="Exercice">
                <option value="">Période libre</option>
                @foreach($performance['exercices'] as $cle => $ex)
                <option value="{{ $cle }}" data-du="{{ $ex['debut'] }}" data-au="{{ $ex['fin'] }}" @selected($cle === $performance['cle'])>{{ \App\Services\ExerciceParc::nom($cle) }}</option>
                @endforeach
            </select>
            <label for="perf-du">du</label>
            <input type="date" id="perf-du" value="{{ $performance['du'] }}">
            <label for="perf-au">au</label>
            <input type="date" id="perf-au" value="{{ $performance['au'] }}">
        </div>
    </div>
    <p class="perf-erreur" id="perf-erreur" role="alert" hidden></p>
    <div id="perf-contenu" aria-live="polite">
        @include('dashboard._performance_lignes')
    </div>
</div>
@push('scripts')
<script>
(function () {
    const zone = document.getElementById('perf-periode'), exercice = document.getElementById('perf-exercice'),
          du = document.getElementById('perf-du'), au = document.getElementById('perf-au'),
          bloc = document.getElementById('perf-contenu'), erreur = document.getElementById('perf-erreur');
    let affichee = { du: du.value, au: au.value }, requete = null;

    // Le menu indique l'exercice quand les dates lui correspondent exactement, sinon « Période libre »
    function synchroniser() {
        const o = [...exercice.options].find(x => x.dataset.du === du.value && x.dataset.au === au.value);
        exercice.value = o ? o.value : '';
    }
    function signaler(message) { erreur.textContent = message; erreur.hidden = false; }

    async function charger() {
        if (requete) requete.abort();
        requete = null;
        bloc.removeAttribute('aria-busy');
        erreur.hidden = true;
        if (!du.value || !au.value) return;
        if (du.value > au.value) return signaler('La date de début doit précéder la date de fin.');
        if (du.value === affichee.du && au.value === affichee.au) return;

        const enCours = requete = new AbortController(), periode = { du: du.value, au: au.value };
        bloc.setAttribute('aria-busy', 'true');
        try {
            const rep = await fetch(zone.dataset.url + '?' + new URLSearchParams(periode), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, redirect: 'error', signal: enCours.signal }); // session expirée : pas de page de connexion dans le bloc
            if (!rep.ok) throw new Error(rep.status);
            bloc.innerHTML = await rep.text();
            window.replierListes(bloc);
            affichee = periode;
        } catch (e) {
            if (e.name === 'AbortError') return;
            du.value = affichee.du; au.value = affichee.au;
            synchroniser();
            signaler('Impossible de charger cette période. Réessayez.');
        }
        if (enCours === requete) bloc.removeAttribute('aria-busy');
    }

    exercice.addEventListener('change', function () {
        const o = exercice.selectedOptions[0];
        if (!o.value) return du.focus();
        du.value = o.dataset.du; au.value = o.dataset.au;
        charger();
    });
    [du, au].forEach(champ => champ.addEventListener('change', function () { synchroniser(); charger(); }));
})();
</script>
@endpush
