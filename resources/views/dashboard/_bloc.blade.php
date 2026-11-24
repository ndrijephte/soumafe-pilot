{{--
    Bloc du tableau de bord avec sa propre période (du … au), rechargé sans recharger la page.
    Paramètres : $bloc (sprints | bloquees | retard), $du, $au (Y-m-d), $defDu, $defAu (période du tableau de bord),
    et selon le bloc : $sprintsEnCours | $topBloquees + $bloquees | $tachesEnRetard + $enRetard ; $plein (pleine largeur), $classe.
--}}
@php
    $titres = ['sprints' => 'Sprints de la période', 'bloquees' => 'Tâches bloquées', 'retard' => 'Tâches en retard'];
    $nb = match($bloc) { 'sprints' => $sprintsEnCours->count(), 'bloquees' => $bloquees ?? $topBloquees->count(), 'retard' => $enRetard ?? $tachesEnRetard->count() };
    $liste = match($bloc) { 'bloquees' => $topBloquees, 'retard' => $tachesEnRetard, default => collect() };
    $propre = $du !== $defDu || $au !== $defAu;
    $detail = match($bloc) {
        'sprints' => route('sprints.index'),
        'bloquees' => route('taches.pilote', ['statut' => 'bloque', 'du' => $du, 'au' => $au]),
        'retard' => route('taches.pilote', ['statut' => 'en_retard', 'du' => $du, 'au' => $au, 'critere' => 'echeance']),
    };
@endphp
<div class="panneau bloc-p {{ $classe ?? '' }}" data-bloc="{{ $bloc }}" data-def-du="{{ $defDu }}" data-def-au="{{ $defAu }}" @if(!empty($plein)) data-plein="1" style="grid-column:1/-1" @endif>
    <h3>
        @if($bloc === 'sprints')
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
        @elseif($bloc === 'bloquees')
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
        @else
        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        @endif
        {{ $titres[$bloc] }} ({{ $nb }})
        <x-detail :href="$detail" :titre="mb_strtolower($titres[$bloc])" />
    </h3>
    <div class="bloc-periode" title="Période de ce bloc (indépendante de la période du tableau de bord)">
        <label>du</label><input type="date" class="bp-du" value="{{ $du }}" aria-label="Début de la période du bloc">
        <label>au</label><input type="date" class="bp-au" value="{{ $au }}" aria-label="Fin de la période du bloc">
        @if($propre)<button type="button" class="bp-reinit" title="Revenir à la période du tableau de bord">&#8634; Période du tableau de bord</button>@endif
        <span class="bp-erreur" hidden></span>
    </div>
    <div class="bloc-contenu">
    @if($bloc === 'sprints')
        @forelse($sprintsEnCours as $s)
        <div class="sprint-item">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
                <span style="font-size:13px;font-weight:600;color:var(--bleu)">Sprint {{ $s->numero }} - {{ $s->projet->libelle ?? '' }}</span>
                @if($s->statut === 'termine')
                <span style="font-size:11px;font-weight:600;color:#15803d;white-space:nowrap">Clôturé le {{ $s->date_fin->format('d/m') }}</span>
                @elseif($s->jours_restants !== null)
                <span style="font-size:11px;font-weight:600;color:#475569;white-space:nowrap">{{ $s->urgence()['court'] }}</span>
                @endif
            </div>
            <div class="jauge-wrap"><div class="jauge-bar" style="width:{{ $s->progression ?? 0 }}%;background:{{ $s->statut === 'termine' ? 'var(--ok)' : 'var(--accent)' }}"></div></div>
            <div style="display:flex;justify-content:space-between;margin-top:3px">
                <span style="font-size:10.5px;color:var(--gris)">{{ $s->stats_terminees ?? 0 }}/{{ $s->stats_total ?? 0 }} t&acirc;ches · {{ $s->date_debut->format('d/m') }} → {{ $s->date_fin->format('d/m/Y') }}</span>
                <a href="{{ route('sprints.show', $s) }}" class="lien">D&eacute;tail &rarr;</a>
            </div>
        </div>
        @empty
        <div style="text-align:center;color:var(--gris);font-size:13px;padding:16px">Aucun sprint sur la p&eacute;riode.</div>
        @endforelse
    @elseif($bloc === 'bloquees')
        <table class="mini-table" data-collapse-table="5">
            <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>Projet</th></tr></thead>
            <tbody>
            @forelse($liste as $t)
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 35) }}</a>@if($t->statut !== 'bloque')<div style="font-size:10.5px;color:var(--gris)">Débloquée · {{ $t->labelStatut() }}</div>@endif</td>
                <td>{{ $t->user?->nomComplet() }}</td>
                <td style="font-size:11px;color:var(--gris)">{{ $t->projet->libelle ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="color:var(--gris);text-align:center;padding:14px">Aucune</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($nb > $liste->count())<div style="font-size:11.5px;color:var(--gris);margin-top:6px;text-align:right"><a href="{{ $detail }}" class="lien">Voir les {{ $nb }} tâches →</a></div>@endif
    @else
        <table class="mini-table" data-collapse-table="5">
            <thead><tr><th>T&acirc;che</th><th>Collaborateur</th><th>&Eacute;ch&eacute;ance</th><th>Retard</th></tr></thead>
            <tbody>
            @forelse($liste as $t)
            <tr>
                <td><a href="{{ route('taches.show', $t) }}" style="color:var(--accent);text-decoration:none;font-weight:500">{{ \Illuminate\Support\Str::limit($t->intitule, 30) }}</a></td>
                <td>{{ $t->user?->nomComplet() }}</td>
                <td style="font-size:11px;color:var(--gris)">{{ $t->echeance->format('d/m') }}</td>
                <td style="white-space:nowrap"><x-voyant :tache="$t" texte /></td>
            </tr>
            @empty
            <tr><td colspan="4" style="color:var(--gris);text-align:center;padding:14px">Aucune</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($nb > $liste->count())<div style="font-size:11.5px;color:var(--gris);margin-top:6px;text-align:right"><a href="{{ $detail }}" class="lien">Voir les {{ $nb }} tâches →</a></div>@endif
    @endif
    </div>
</div>
@once
@push('styles')
<style>
.bloc-periode{display:flex;align-items:center;flex-wrap:wrap;gap:5px 6px;font-size:11.5px;color:var(--gris);margin:-4px 0 10px}
.bloc-periode input{padding:3px 6px;border:1px solid var(--bord);border-radius:6px;background:#fff;color:#334155;font-size:11.5px;width:122px;font-family:inherit}
.bloc-periode input:focus-visible{outline:2px solid #2e74b5;outline-offset:1px}
.bloc-periode .bp-reinit{border:none;background:none;color:#2e74b5;font-size:11.5px;font-weight:600;cursor:pointer;padding:2px 4px}
.bloc-periode .bp-reinit:hover{text-decoration:underline}
.bloc-periode .bp-erreur{color:var(--ko)}
.bloc-p[aria-busy=true] .bloc-contenu{opacity:.45;transition:opacity .15s}
.bloc-p.bloc-dg{margin-bottom:20px}
</style>
@endpush
@push('scripts')
<script>
// Période propre à chaque bloc : le bloc se recharge seul, sans toucher au reste du tableau de bord
(function () {
    const url = @json(route('dashboard.bloc'));
    async function recharger(panneau, du, au) {
        const err = panneau.querySelector('.bp-erreur');
        if (!du || !au) return;
        if (du > au) { err.textContent = 'La date de début doit précéder la date de fin.'; err.hidden = false; return; }
        panneau.setAttribute('aria-busy', 'true');
        const p = new URLSearchParams({bloc: panneau.dataset.bloc, du, au, def_du: panneau.dataset.defDu, def_au: panneau.dataset.defAu});
        if (panneau.dataset.plein) p.set('plein', '1');
        if (panneau.classList.contains('bloc-dg')) p.set('classe', 'bloc-dg');
        try {
            const rep = await fetch(url + '?' + p, {headers: {'X-Requested-With': 'XMLHttpRequest'}, redirect: 'error'});
            if (!rep.ok) throw new Error(rep.status);
            const tmp = document.createElement('div');
            tmp.innerHTML = (await rep.text()).trim();
            const neuf = tmp.firstElementChild;
            panneau.replaceWith(neuf);
            if (window.replierListes) window.replierListes(neuf);
        } catch (e) {
            panneau.removeAttribute('aria-busy');
            err.textContent = 'Impossible de charger cette période.'; err.hidden = false;
        }
    }
    document.addEventListener('change', e => {
        const panneau = e.target.closest('.bloc-p');
        if (!panneau || !e.target.matches('.bp-du, .bp-au')) return;
        recharger(panneau, panneau.querySelector('.bp-du').value, panneau.querySelector('.bp-au').value);
    });
    document.addEventListener('click', e => {
        if (!e.target.matches('.bp-reinit')) return;
        const panneau = e.target.closest('.bloc-p');
        recharger(panneau, panneau.dataset.defDu, panneau.dataset.defAu);
    });
})();
</script>
@endpush
@endonce
