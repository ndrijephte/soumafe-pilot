{{-- Actions de la rétrospective du sprint précédent, à appliquer dans ce sprint. Paramètres : $sprint, $enTache (bouton « En faire une tâche ») --}}
@php $reprise = \App\Services\CeremoniesScrum::actionsPrecedentes($sprint); @endphp
@if($reprise)
@once
@push('styles')
<style>
.retro-actions{background:#fff;border:1px solid var(--bord);border-radius:10px;padding:0;margin-bottom:14px}
.retro-actions summary{display:flex;align-items:center;gap:8px;padding:10px 16px;cursor:pointer;font-size:13.5px;font-weight:700;color:var(--bleu)}
.retro-actions summary .nb{background:#eef4fb;color:#2e74b5;border-radius:999px;padding:1px 8px;font-size:11px}
.retro-actions summary small{font-weight:500;color:var(--gris);font-size:11.5px}
.retro-actions ul{list-style:none;margin:0;padding:0 16px 8px}
.retro-actions li{display:grid;grid-template-columns:160px minmax(0,1fr) auto;gap:12px;align-items:start;padding:8px 0;border-top:1px solid #f1f5f9;font-size:13px;color:#334155}
.retro-actions .th{font-weight:700;color:#1f4e79}
@media(max-width:700px){.retro-actions li{grid-template-columns:1fr}}
</style>
@endpush
@endonce
<details class="retro-actions" open>
    <summary>Actions à appliquer <span class="nb">{{ count($reprise['lignes']) }}</span>
        <small>Rétrospective du <a href="{{ route('scrum.retro', $reprise['sprint_id']) }}" style="color:var(--accent);font-weight:600;text-decoration:none">sprint {{ $reprise['numero'] }}</a>{{ $reprise['valide'] ? '' : ' (non validée)' }}</small>
    </summary>
    <ul>
        @foreach($reprise['lignes'] as $l)
        <li>
            <span class="th">{{ $l['theme'] ?: '-' }}</span>
            <x-redaction lecture vide="" :value="$l['actions']" />
            @if($enTache ?? false)
            <button type="button" class="btn sm sec" data-texte="{{ \Illuminate\Support\Str::limit(\App\Services\TexteRiche::texte($l['actions']), 250, '') }}" onclick="const d=document.getElementById('dlg-tache');d.querySelector('[name=intitule]').value=this.dataset.texte;d.showModal()">En faire une t&acirc;che</button>
            @endif
        </li>
        @endforeach
    </ul>
</details>
@endif
