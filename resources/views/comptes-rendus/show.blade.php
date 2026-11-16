@extends('layouts.app')
@section('sousTitre', 'Comptes rendus')
@section('titre', $cr->titre)
@section('contenu')
{{-- Consultation : le document tel qu'il sera imprimé (version PDF), sous une barre d'actions --}}
<style>
.crv{max-width:900px;margin:0 auto}
.crv-tete{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.crv-tete h2{font-size:1.2rem;font-weight:700;color:var(--bleu,#1F4E79);margin:0}
.crv-tete .sub{font-size:.82rem;color:#64748b;margin-top:3px}
.badge{padding:2px 10px;border-radius:10px;font-size:.72rem;font-weight:600}
.badge-ok{background:#dcfce7;color:#166534}.badge-brouillon{background:#fef3c7;color:#92400e}
.crv-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.crv-actions a,.crv-actions button{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;cursor:pointer;border:1px solid #cbd5e1;background:#fff;color:#1f4e79;font-family:inherit}
.crv-actions a:hover,.crv-actions button:hover{border-color:#93c5fd;background:#f8fbff}
.crv-actions .principal{background:var(--accent,#2E74B5);border-color:var(--accent,#2E74B5);color:#fff}
.crv-actions .principal:hover{background:#245f96}
.crv-actions .suppr{color:#b91c1c;border-color:#fecaca}
.crv-doc{width:100%;border:0;display:block;min-height:70vh;border-radius:10px;background:#eef2f7}
@if(session('ok'))
.crv-alerte{background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;padding:10px 14px;border-radius:10px;margin-bottom:12px;font-size:.86rem}
@endif
</style>

<div class="crv">
    <div class="crv-tete">
        <div>
            <h2>{{ $cr->titre }}</h2>
            <div class="sub">{{ $cr->typeCourt() }} · {{ $cr->date_reunion->format('d/m/Y') }} · <span class="badge {{ $cr->estConfirme() ? 'badge-ok' : 'badge-brouillon' }}">{{ $cr->estConfirme() ? 'Confirmé' : 'Brouillon' }}</span></div>
        </div>
        <div class="crv-actions">
            <a href="{{ route('comptes-rendus.index') }}">&larr; Comptes rendus</a>
            @if(auth()->user()->estPilote() || ($cr->estBrouillon() && auth()->id() === $cr->redacteur_id))
            <a href="{{ route('comptes-rendus.edit', $cr) }}" class="principal">Modifier</a>
            @elseif($cr->type === 'demo_sprint' && $cr->estBrouillon() && $cr->sprint && \App\Services\ScrumService::membres($cr->sprint)->contains('id', auth()->id()))
            <a href="{{ route('comptes-rendus.edit', $cr) }}" class="principal">Compléter mes tâches</a>
            @endif
            <a href="{{ route('comptes-rendus.imprimer', [$cr, 'print' => 1]) }}" target="_blank">Imprimer / PDF</a>
            @if($cr->estConfirme() && $cr->fichier_path)<a href="{{ route('comptes-rendus.telecharger', $cr) }}">&#8681; DOCX</a>@endif
            @if(auth()->user()->estPilote())
            <form method="POST" action="{{ route('comptes-rendus.destroy', $cr) }}" style="margin:0" onsubmit="return demanderMotifSuppression(this,'ce compte rendu')">
                @csrf @method('DELETE')
                <input type="hidden" name="motif_suppression" class="motif-suppr-input">
                <button type="submit" class="suppr">Supprimer</button>
            </form>
            @endif
        </div>
    </div>
    @if(session('ok'))<div class="crv-alerte">{{ session('ok') }}</div>@endif

    <iframe class="crv-doc" id="crv-doc" src="{{ route('comptes-rendus.imprimer', [$cr, 'apercu' => 1]) }}" title="{{ $cr->titre }}"></iframe>
</div>

<script>
// La feuille prend la hauteur du document : une seule barre de défilement, celle de la page
(function () {
    const f = document.getElementById('crv-doc');
    const ajuster = () => { try { f.style.height = f.contentDocument.documentElement.scrollHeight + 'px'; } catch (e) {} };
    f.addEventListener('load', () => { ajuster(); setTimeout(ajuster, 300); });
    window.addEventListener('resize', ajuster);
})();
</script>
@endsection
