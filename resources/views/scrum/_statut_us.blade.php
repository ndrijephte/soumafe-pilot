{{-- Statut d'une user story : modifiable par qui alimente le backlog, quand il n'est pas imposé par les tâches. Paramètres : $us, $peutAlimenter --}}
@php
    $optionsStatut = $peutAlimenter ? $us->statutsPermis() : [];
@endphp
@once
@push('styles')
<style>
.st-us{display:inline-block;padding:2px 9px;border:1px solid transparent;border-radius:10px;font-size:11px;font-weight:600;white-space:nowrap}
select.st-us{padding:2px 4px;font-family:inherit;cursor:pointer;max-width:100%}
.st-a_faire{background:#f1f5f9;color:#475569}.st-en_cours{background:#dbeafe;color:#1d4ed8}.st-en_revue{background:#fef3c7;color:#92400e}
.st-terminee{background:#dcfce7;color:#166534}.st-acceptee{background:#166534;color:#fff}.st-bloquee{background:#ede9fe;color:#6d28d9}
</style>
@endpush
@endonce
@if(count($optionsStatut) > 1)
<form method="POST" action="{{ route('user-stories.statut', $us) }}" style="margin:0">@csrf @method('PATCH')
    <select name="statut" class="st-us st-{{ $us->statut }}" aria-label="Statut de {{ $us->numero }}" onchange="this.form.submit()">
        @foreach($optionsStatut as $s)<option value="{{ $s }}" @selected($us->statut === $s)>{{ \App\Models\UserStory::STATUTS[$s] }}</option>@endforeach
    </select>
</form>
@else
<span class="st-us st-{{ $us->statut }}" @if($us->taches()->exists()) title="Statut calculé à partir des tâches" @endif>{{ \App\Services\AvancementScrum::aReprendre($us) ? 'À reprendre' : $us->statutLibelle() }}</span>
@endif
