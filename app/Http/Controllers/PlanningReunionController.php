<?php
namespace App\Http\Controllers;

use App\Models\{AffectationReunion, AuditLog, NotificationApp, ParticipantReunion, Projet, RoleReunion, TypeReunion, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema};

class PlanningReunionController extends Controller
{
    public function index(Request $r)
    {
        abort_unless($r->user()->estPilote() || $r->user()->role === 'collaborateur', 403);

        $types = TypeReunion::with('roles')->where('actif', true)->get();
        $typeActif = $r->get('type', $types->first()?->id);
        $type = $types->firstWhere('id', $typeActif) ?? $types->first();

        $estPonctuel = $type && $type->recurrence !== 'quotidien';
        $semaine = $r->get('semaine', $estPonctuel ? now()->startOfMonth()->format('Y-m-d') : now()->startOfWeek()->format('Y-m-d'));

        if ($estPonctuel) {
            $debut = \Carbon\Carbon::parse($semaine)->startOfMonth();
            $fin = $debut->copy()->endOfMonth();
        } else {
            $debut = \Carbon\Carbon::parse($semaine)->startOfWeek();
            $fin = $debut->copy()->endOfWeek();
        }

        $affectations = $type
            ? AffectationReunion::with(['user', 'role', 'remplacant', 'projet'])
                ->where('type_reunion_id', $type->id)
                ->whereBetween('date_reunion', [$debut, $fin])
                ->get()
            : collect();

        if ($estPonctuel) {
            // Jours = uniquement les dates ayant des affectations
            $jours = $affectations->pluck('date_reunion')
                ->map(fn($d) => $d instanceof \Carbon\Carbon ? $d : \Carbon\Carbon::parse($d))
                ->unique(fn($d) => $d->format('Y-m-d'))
                ->sortBy(fn($d) => $d->timestamp)
                ->values()->all();

            // Grouper en instances (réunions distinctes) par date + created_at
            $instances = $affectations->sortBy('id')
                ->groupBy(fn($a) => $a->date_reunion->format('Y-m-d'))
                ->map(fn($dateAff) => $dateAff->groupBy(fn($a) => $a->created_at?->format('Y-m-d H:i') ?? $a->id)->values());
        } else {
            // Jours travaillés (horaires paramétrables)
            $jours = [];
            for ($d = $debut->copy(); $d->lte($fin); $d->addDay()) {
                if (!\App\Services\CalendrierTravail::estJourTravaille($d)) continue;
                $jours[] = $d->copy();
            }
        }

        $participants = $type
            ? ParticipantReunion::with('user')
                ->where('type_reunion_id', $type->id)
                ->where('actif', true)
                ->get()
            : collect();

        $collaborateurs = User::where('actif', true)
            ->whereNotIn('role', ['invite'])
            ->orderBy('name')->get();

        $estPilote = $r->user()->estPilote();
        $estGestionnaire = $r->user()->estAdmin();

        $instances = $instances ?? null;

        $projets = Projet::whereNull('deleted_at')->orderBy('libelle')->get();

        return view('planning.index', compact(
            'types', 'type', 'jours', 'affectations', 'participants',
            'collaborateurs', 'semaine', 'debut', 'fin', 'estPilote', 'estGestionnaire', 'estPonctuel', 'instances', 'projets'
        ));
    }

    /* ===== GÉNÉRER LE PLANNING (rotation automatique) ===== */
    public function generer(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'type_reunion_id' => 'required|exists:types_reunion,id',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'affectations' => 'nullable|array',
            'affectations.*' => 'nullable|exists:users,id',
            'heure' => 'nullable|date_format:H:i',
            'projet_id' => 'nullable|exists:projets,id',
        ]);

        $type = TypeReunion::with('roles')->findOrFail($r->type_reunion_id);

        // Sauvegarder l'heure sur le type
        if ($r->filled('heure')) {
            $type->update(['heure' => $r->heure]);
        }
        $participants = ParticipantReunion::with('user')
            ->where('type_reunion_id', $type->id)
            ->where('actif', true)
            ->get();

        if ($participants->isEmpty()) {
            return back()->withErrors(['Aucun participant actif pour ce type de réunion.']);
        }

        $roles = $type->roles;
        if ($roles->isEmpty()) {
            return back()->withErrors(['Aucun rôle défini pour ce type de réunion.']);
        }

        $debut = \Carbon\Carbon::parse($r->date_debut);
        $fin = \Carbon\Carbon::parse($r->date_fin);
        $ponctuel = $type->recurrence !== 'quotidien' && $debut->equalTo($fin);

        // Supprimer les affectations existantes (sauf ponctuel - permet plusieurs le même jour)
        if (!$ponctuel) {
            AffectationReunion::where('type_reunion_id', $type->id)
                ->whereBetween('date_reunion', [$debut, $fin])
                ->where('statut', 'prevu')
                ->delete();
        }

        $userIds = $participants->pluck('user_id')->toArray();
        $nbUsers = count($userIds);
        $nbRoles = $roles->count();
        $jourIdx = 0;

        if ($ponctuel) {
            // Réunion ponctuelle : affectation manuelle ou rotation par défaut
            $manualAff = $r->input('affectations', []);
            $projetId = $r->projet_id ?: null;
            foreach ($roles as $rIdx => $role) {
                $userId = !empty($manualAff[$role->id]) ? (int)$manualAff[$role->id] : $userIds[$rIdx % $nbUsers];
                AffectationReunion::create([
                    'type_reunion_id' => $type->id,
                    'role_reunion_id' => $role->id,
                    'user_id' => $userId,
                    'date_reunion' => $debut->format('Y-m-d'),
                    'statut' => 'prevu',
                    'projet_id' => $projetId,
                ]);
            }
            $jourIdx = 1;
        } else {
            for ($d = $debut->copy(); $d->lte($fin); $d->addDay()) {
                if (!\App\Services\CalendrierTravail::estJourTravaille($d)) continue;

                // Vérifier récurrence (quotidien = tous les jours ouvrés)
                if ($type->recurrence === 'hebdomadaire' && $d->dayOfWeek !== 1) continue;
                if ($type->recurrence === 'bimensuel' && ($d->dayOfWeek !== 1 || $d->weekOfMonth > 2)) continue;
                if ($type->recurrence === 'mensuel' && ($d->dayOfWeek !== 1 || $d->weekOfMonth !== 1)) continue;
                if ($type->recurrence === 'fin_sprint' && $jourIdx > 0) continue;

                foreach ($roles as $rIdx => $role) {
                    $userIndex = ($jourIdx * $nbRoles + $rIdx) % $nbUsers;
                    AffectationReunion::create([
                        'type_reunion_id' => $type->id,
                        'role_reunion_id' => $role->id,
                        'user_id' => $userIds[$userIndex],
                        'date_reunion' => $d->format('Y-m-d'),
                        'statut' => 'prevu',
                    ]);
                }
                $jourIdx++;
            }
        }

        // Notifier les participants
        $msgNotif = $ponctuel
            ? 'Réunion "' . $type->libelle . '" programmée le ' . $debut->format('d/m/Y')
            : 'Planning "' . $type->libelle . '" généré du ' . $debut->format('d/m') . ' au ' . $fin->format('d/m');
        foreach ($userIds as $uid) {
            if ($uid === auth()->id()) continue;
            NotificationApp::create([
                'user_id' => $uid,
                'type' => 'planning',
                'contenu' => $msgNotif,
                'lu' => false,
                'canal' => 'in_app',
            ]);
        }

        $projetNom = ($ponctuel && $r->projet_id) ? Projet::find($r->projet_id)?->libelle : null;
        $auditDetail = $ponctuel
            ? "Ponctuel: {$debut->format('d/m/Y')}, {$nbUsers} participants" . ($projetNom ? ", Projet: {$projetNom}" : '')
            : "Période: {$debut->format('d/m/Y')} - {$fin->format('d/m/Y')}, {$nbUsers} participants";
        AuditLog::log('creation', 'planning', $type->libelle, $auditDetail);
        return redirect()->route('planning.index', ['type' => $type->id, 'semaine' => $debut->format('Y-m-d')])
            ->with('succes', $ponctuel ? 'Réunion programmée.' : 'Planning généré avec succès.');
    }

    /* ===== GESTION PARTICIPANTS ===== */
    public function ajouterParticipant(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'type_reunion_id' => 'required|exists:types_reunion,id',
            'user_id' => 'required|exists:users,id',
        ]);

        ParticipantReunion::firstOrCreate(
            ['type_reunion_id' => $r->type_reunion_id, 'user_id' => $r->user_id],
            ['actif' => true]
        );

        return redirect()->route('planning.index', ['type' => $r->type_reunion_id])->with('succes', 'Participant ajouté.');
    }

    public function retirerParticipant(Request $r, ParticipantReunion $participant)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);
        $typeId = $participant->type_reunion_id;
        $participant->delete();
        return redirect()->route('planning.index', ['type' => $typeId])->with('succes', 'Participant retiré.');
    }

    /* ===== GESTION TYPES & RÔLES ===== */
    public function storeType(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'code' => 'required|string|max:30|unique:types_reunion,code',
            'libelle' => 'required|string|max:100',
            'recurrence' => 'required|in:quotidien,hebdomadaire,bimensuel,mensuel,fin_sprint',
        ]);

        TypeReunion::create($r->only('code', 'libelle', 'recurrence'));
        return redirect()->route('planning.index')->with('succes', 'Type de réunion créé.');
    }

    public function storeRole(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'type_reunion_id' => 'required|exists:types_reunion,id',
            'libelle' => 'required|string|max:80',
        ]);

        $code = \Str::slug($r->libelle, '_');
        $ordre = RoleReunion::where('type_reunion_id', $r->type_reunion_id)->max('ordre') + 1;

        RoleReunion::create([
            'type_reunion_id' => $r->type_reunion_id,
            'code' => $code,
            'libelle' => $r->libelle,
            'ordre' => $ordre,
        ]);

        return redirect()->route('planning.index', ['type' => $r->type_reunion_id])->with('succes', 'Rôle ajouté.');
    }

    /* ===== SUPPRIMER UN RÔLE ===== */
    public function destroyRole(Request $r, RoleReunion $role)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['motif_suppression' => 'required|string|min:5']);

        $typeId = $role->type_reunion_id;
        $libelle = $role->libelle;

        // Supprimer les affectations futures liées à ce rôle
        AffectationReunion::where('role_reunion_id', $role->id)
            ->where('date_reunion', '>=', now()->format('Y-m-d'))
            ->where('statut', 'prevu')
            ->delete();

        $role->delete();

        AuditLog::log('suppression', 'planning', $libelle, "Rôle supprimé. Motif: {$r->motif_suppression}");
        return redirect()->route('planning.index', ['type' => $typeId])->with('succes', 'Rôle supprimé.');
    }

    /* ===== MODIFIER UN TYPE (heure, libellé) ===== */
    public function updateType(Request $r, TypeReunion $type)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'libelle' => 'required|string|max:100',
            'heure' => 'nullable|date_format:H:i',
        ]);

        $type->update($r->only('libelle', 'heure'));
        AuditLog::log('modification', 'planning', $type->libelle, 'Type modifié' . ($r->heure ? " - Heure: {$r->heure}" : ''));
        return redirect()->route('planning.index', ['type' => $type->id])->with('succes', 'Type de réunion modifié.');
    }

    /* ===== MODIFIER UNE AFFECTATION (changer le collaborateur) ===== */
    public function modifierAffectation(Request $r, AffectationReunion $affectation)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate(['user_id' => 'required|exists:users,id']);

        $ancien = $affectation->user?->nomComplet() ?? '—';
        $affectation->update(['user_id' => $r->user_id]);
        $nouveau = User::find($r->user_id)->nomComplet();

        AuditLog::log('modification', 'planning', $affectation->role->libelle ?? '—',
            "Affectation modifiée: {$ancien} → {$nouveau} le " . $affectation->date_reunion->format('d/m/Y'));

        // Notifier le nouveau
        if ((int) $r->user_id !== auth()->id()) {
            NotificationApp::create([
                'user_id' => $r->user_id,
                'type' => 'planning',
                'contenu' => 'Vous êtes désormais affecté comme ' . ($affectation->role->libelle ?? '') . ' le ' . $affectation->date_reunion->format('d/m/Y'),
                'lu' => false,
                'canal' => 'in_app',
            ]);
        }

        return redirect()->route('planning.index', [
            'type' => $affectation->type_reunion_id,
            'semaine' => $affectation->date_reunion->startOfMonth()->format('Y-m-d'),
        ])->with('succes', "Affectation modifiée: {$nouveau}.");
    }

    /* ===== SUPPRIMER UNE INSTANCE DE RÉUNION ===== */
    public function supprimerInstance(Request $r)
    {
        abort_unless($r->user()->estAdmin(), 403);
        $r->validate([
            'motif_suppression' => 'required|string|min:5',
            'affectation_ids' => 'required|string',
        ]);

        $ids = array_filter(explode(',', $r->affectation_ids));
        if (empty($ids)) {
            return back()->withErrors(['Aucune affectation à supprimer.']);
        }

        $first = AffectationReunion::with('typeReunion')->find($ids[0]);
        if (!$first) return back()->withErrors(['Réunion introuvable.']);

        $typeId = $first->type_reunion_id;
        $date = $first->date_reunion->format('d/m/Y');
        $typeName = $first->typeReunion->libelle ?? '—';

        AffectationReunion::whereIn('id', $ids)->delete();

        AuditLog::log('suppression', 'planning', $typeName,
            "Réunion du {$date} supprimée. Motif: {$r->motif_suppression}");

        return redirect()->route('planning.index', [
            'type' => $typeId,
            'semaine' => $first->date_reunion->startOfMonth()->format('Y-m-d'),
        ])->with('succes', 'Réunion supprimée.');
    }

    /* ===== SIGNALER ABSENCE / REMPLACEMENT ===== */
    public function signalerAbsence(Request $r, AffectationReunion $affectation)
    {
        abort_unless($r->user()->estAdmin() || $r->user()->id === $affectation->user_id, 403);
        $r->validate(['remplacant_id' => 'nullable|exists:users,id']);

        if ($r->remplacant_id) {
            $affectation->update(['statut' => 'remplace', 'remplacant_id' => $r->remplacant_id]);
            // Notifier le remplaçant
            NotificationApp::create([
                'user_id' => $r->remplacant_id,
                'type' => 'planning',
                'contenu' => 'Vous remplacez ' . $affectation->user->name . ' comme ' . $affectation->role->libelle . ' le ' . $affectation->date_reunion->format('d/m/Y'),
                'lu' => false,
                'canal' => 'in_app',
            ]);
        } else {
            $affectation->update(['statut' => 'absent']);
        }

        return redirect()->route('planning.index', [
            'type' => $affectation->type_reunion_id,
            'semaine' => $affectation->date_reunion->startOfWeek()->format('Y-m-d'),
        ])->with('succes', 'Absence enregistrée.');
    }
}
