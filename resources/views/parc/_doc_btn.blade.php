{{-- Trombone d'une ligne : ouvre le dossier documentaire ; pastille = nombre de documents, couleur = échéance la plus urgente. --}}
@php $r = ($docsResume[$t] ?? [])[$id] ?? ['n' => 0, 'niveau' => null]; @endphp
<button type="button" class="act-btn act-docs{{ $r['n'] ? ' a-docs' : '' }}{{ $r['niveau'] && $r['niveau'] !== 'ok' ? ' v-' . $r['niveau'] : '' }}" data-docs="{{ $t }}-{{ $id }}"
    title="Documents{{ $r['n'] ? ' (' . $r['n'] . ')' : '' }}{{ in_array($r['niveau'], ['retard', 'proche', 'semaine'], true) ? ' — document à renouveler' : '' }}"
    aria-label="Documents" onclick="ouvrirDocuments('{{ $t }}', {{ $id }})"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg><span class="docs-n">{{ $r['n'] ?: '' }}</span></button>
