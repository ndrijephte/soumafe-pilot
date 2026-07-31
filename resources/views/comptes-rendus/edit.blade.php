@extends('layouts.app')
@section('titre', 'Rédiger — ' . $cr->titre)
@section('contenu')
<style>
.cr-edit{max-width:900px;margin:0 auto}
.cr-edit h2{font-size:1.3rem;font-weight:700;color:#1a1a2e;margin-bottom:6px}
.cr-edit .sub{font-size:.85rem;color:#64748b;margin-bottom:20px}
.section{background:#fff;border-radius:12px;padding:20px 24px;margin-bottom:16px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.section h3{font-size:1rem;font-weight:700;color:#1e3a5f;margin-bottom:12px}
.tbl{width:100%;border-collapse:collapse;font-size:.85rem}
.tbl th{background:#1F4E79;color:#fff;padding:8px 10px;text-align:left;font-weight:600}
.tbl td{padding:8px 10px;border-bottom:1px solid #e5e7eb}
.tbl input[type=text],.tbl textarea,.tbl select{width:100%;padding:4px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:.82rem}
.tbl textarea{resize:vertical;min-height:32px}
.chk{width:18px;height:18px;cursor:pointer}
.fg{margin-bottom:14px}
.fg label{font-size:.85rem;font-weight:600;color:#475569;display:block;margin-bottom:4px}
.fg input,.fg textarea{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:.9rem}
.fg textarea{resize:vertical;min-height:60px}
.bar{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;margin-top:20px}
.btn-add{background:#f1f5f9;color:#475569;border:1px dashed #94a3b8;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:.82rem}
.btn-add:hover{background:#e2e8f0}
</style>

<div class="cr-edit">
    <h2>{{ $cr->titre }}</h2>
    <div class="sub">{{ $cr->typeLibelle() }} — {{ $cr->date_reunion->format('d/m/Y') }} — Rédacteur : {{ $cr->redacteur?->nomComplet() }}</div>

    @if(session('ok'))
    <div style="background:#d1fae5;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:.85rem">{{ session('ok') }}</div>
    @endif

    <form method="POST" action="{{ route('comptes-rendus.update', $cr) }}">
        @csrf @method('PUT')

        <div class="section">
            <div class="fg">
                <label>Titre</label>
                <input name="titre" value="{{ $cr->titre }}">
            </div>
            <div class="fg">
                <label>Lieu</label>
                <input name="lieu" value="{{ $cr->lieu }}">
            </div>
        </div>

        @if($cr->type === 'reunion_hebdo')
            {{-- PRÉSENCES --}}
            <div class="section">
                <h3>Liste de présence</h3>
                <table class="tbl">
                    <thead><tr><th>Collaborateur</th><th style="width:80px">Présent</th></tr></thead>
                    <tbody>
                    @foreach($donnees['presences'] ?? [] as $i => $p)
                    <tr>
                        <td>{{ $p['nom'] }}</td>
                        <td style="text-align:center">
                            <input type="hidden" name="donnees[presences][{{ $i }}][nom]" value="{{ $p['nom'] }}">
                            <input type="checkbox" class="chk" name="donnees[presences][{{ $i }}][present]" value="1" @checked($p['present'] ?? false)>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- DILIGENCES --}}
            <div class="section">
                <h3>Diligences par projet</h3>
                @foreach($donnees['diligences'] ?? [] as $j => $dil)
                <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;margin-bottom:12px">
                    <div style="font-weight:600;color:#1e3a5f;margin-bottom:8px">{{ $dil['projet'] }} <span style="font-weight:400;color:#64748b;font-size:.82rem">({{ $dil['responsable'] }})</span></div>
                    <input type="hidden" name="donnees[diligences][{{ $j }}][projet]" value="{{ $dil['projet'] }}">
                    <input type="hidden" name="donnees[diligences][{{ $j }}][responsable]" value="{{ $dil['responsable'] }}">
                    <input type="hidden" name="donnees[diligences][{{ $j }}][taches_terminees]" value="{{ $dil['taches_terminees'] }}">
                    <input type="hidden" name="donnees[diligences][{{ $j }}][taches_bloquees]" value="{{ $dil['taches_bloquees'] }}">
                    <div style="font-size:.82rem;color:#64748b;margin-bottom:6px">
                        Tâches terminées : <strong>{{ $dil['taches_terminees'] }}</strong> — Blocages : <strong>{{ $dil['taches_bloquees'] }}</strong>
                    </div>
                    @if(!empty($dil['evenements']))
                    <div style="font-size:.82rem;color:#64748b;margin-bottom:6px">Événements : {{ implode(', ', $dil['evenements']) }}</div>
                    @endif
                    <div class="fg" style="margin-bottom:0">
                        <label>Observations (à compléter)</label>
                        <textarea name="donnees[diligences][{{ $j }}][observations]" rows="2">{{ $dil['observations'] ?? '' }}</textarea>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- DÉCISIONS --}}
            <div class="section" id="decisions-section">
                <h3>Décisions et actions</h3>
                <table class="tbl">
                    <thead><tr><th>Décision / Action</th><th>Responsable</th><th style="width:120px">Échéance</th></tr></thead>
                    <tbody id="decisions-body">
                    @foreach($donnees['decisions'] ?? [] as $k => $dec)
                    <tr>
                        <td><input type="text" name="donnees[decisions][{{ $k }}][action]" value="{{ $dec['action'] ?? '' }}"></td>
                        <td><input type="text" name="donnees[decisions][{{ $k }}][responsable]" value="{{ $dec['responsable'] ?? '' }}"></td>
                        <td><input type="date" name="donnees[decisions][{{ $k }}][echeance]" value="{{ $dec['echeance'] ?? '' }}"></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="ajouterDecision()" style="margin-top:8px">+ Ajouter une décision</button>
            </div>

            {{-- OBSERVATIONS GÉNÉRALES --}}
            <div class="section">
                <div class="fg" style="margin-bottom:0">
                    <label>Observations générales</label>
                    <textarea name="donnees[observations]" rows="3">{{ $donnees['observations'] ?? '' }}</textarea>
                </div>
            </div>

        @else
            {{-- DEMO SPRINT --}}
            <div class="section">
                <h3>Résultats par tâche</h3>
                <div style="font-size:.85rem;color:#64748b;margin-bottom:10px">
                    Sprint : <strong>{{ $donnees['sprint'] ?? '—' }}</strong> — Projet : <strong>{{ $donnees['projet'] ?? '—' }}</strong>
                    — Période : {{ $donnees['date_debut'] ?? '' }} au {{ $donnees['date_fin'] ?? '' }}
                </div>
                <table class="tbl">
                    <thead><tr><th>Tâche</th><th style="width:50px">Pts</th><th>Attribution</th><th style="width:50px">OK</th><th>Observation</th></tr></thead>
                    <tbody>
                    @foreach($donnees['taches'] ?? [] as $k => $t)
                    <tr>
                        <td>
                            {{ $t['tache'] }}
                            <input type="hidden" name="donnees[taches][{{ $k }}][tache]" value="{{ $t['tache'] }}">
                            <input type="hidden" name="donnees[taches][{{ $k }}][points]" value="{{ $t['points'] }}">
                            <input type="hidden" name="donnees[taches][{{ $k }}][attribution]" value="{{ $t['attribution'] }}">
                            <input type="hidden" name="donnees[taches][{{ $k }}][statut]" value="{{ $t['statut'] }}">
                        </td>
                        <td style="text-align:center">{{ $t['points'] }}</td>
                        <td>{{ $t['attribution'] }}</td>
                        <td style="text-align:center">
                            <input type="checkbox" class="chk" name="donnees[taches][{{ $k }}][termine]" value="1" @checked($t['termine'] ?? false)>
                        </td>
                        <td><input type="text" name="donnees[taches][{{ $k }}][observation]" value="{{ $t['observation'] ?? '' }}"></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h3>Score global</h3>
                <div style="font-size:1.1rem;font-weight:700;color:#1e3a5f">
                    {{ $donnees['points_realises'] ?? 0 }} / {{ $donnees['total_points'] ?? 0 }} points ({{ $donnees['score_pourcent'] ?? 0 }}%)
                </div>
            </div>

            {{-- ACTIONS À MENER --}}
            <div class="section" id="actions-section">
                <h3>Actions à mener</h3>
                <table class="tbl">
                    <thead><tr><th>Action</th><th>Responsable</th><th style="width:120px">Échéance</th></tr></thead>
                    <tbody id="actions-body">
                    @foreach($donnees['actions_a_mener'] ?? [] as $k => $a)
                    <tr>
                        <td><input type="text" name="donnees[actions_a_mener][{{ $k }}][action]" value="{{ $a['action'] ?? '' }}"></td>
                        <td><input type="text" name="donnees[actions_a_mener][{{ $k }}][responsable]" value="{{ $a['responsable'] ?? '' }}"></td>
                        <td><input type="date" name="donnees[actions_a_mener][{{ $k }}][echeance]" value="{{ $a['echeance'] ?? '' }}"></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn-add" onclick="ajouterAction()" style="margin-top:8px">+ Ajouter une action</button>
            </div>

            <div class="section">
                <div class="fg" style="margin-bottom:0">
                    <label>Note pour le sprint suivant</label>
                    <textarea name="donnees[note_sprint]" rows="3">{{ $donnees['note_sprint'] ?? '' }}</textarea>
                </div>
            </div>
        @endif

        <div class="bar">
            <a href="{{ route('comptes-rendus.index') }}" class="btn sec">Retour</a>
            <button type="submit" class="btn" style="background:#f59e0b;color:#fff">Enregistrer brouillon</button>
        </div>
    </form>

    @if($cr->estBrouillon())
    <form method="POST" action="{{ route('comptes-rendus.confirmer', $cr) }}" style="text-align:right;margin-top:10px">
        @csrf
        <button type="submit" class="btn" onclick="return confirm('Confirmer définitivement ce compte rendu ?')">Confirmer le compte rendu</button>
    </form>
    @endif
</div>

<script>
let decIdx = {{ count($donnees['decisions'] ?? []) }};
let actIdx = {{ count($donnees['actions_a_mener'] ?? []) }};

function ajouterDecision(){
    const row = document.createElement('tr');
    row.innerHTML = `<td><input type="text" name="donnees[decisions][${decIdx}][action]"></td>
        <td><input type="text" name="donnees[decisions][${decIdx}][responsable]"></td>
        <td><input type="date" name="donnees[decisions][${decIdx}][echeance]"></td>`;
    document.getElementById('decisions-body').appendChild(row);
    decIdx++;
}

function ajouterAction(){
    const row = document.createElement('tr');
    row.innerHTML = `<td><input type="text" name="donnees[actions_a_mener][${actIdx}][action]"></td>
        <td><input type="text" name="donnees[actions_a_mener][${actIdx}][responsable]"></td>
        <td><input type="date" name="donnees[actions_a_mener][${actIdx}][echeance]"></td>`;
    document.getElementById('actions-body').appendChild(row);
    actIdx++;
}
</script>
@endsection
