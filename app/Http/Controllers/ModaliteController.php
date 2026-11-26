<?php
namespace App\Http\Controllers;

use App\Models\{AuditLog, TypeAbsence, TypeBlocage, TypeEvenement};
use Illuminate\Http\Request;

/**
 * Saisie manuelle d'une valeur absente d'une liste déroulante (types d'événement, de blocage…).
 * La valeur rejoint aussitôt le paramétrage correspondant. Le parc a son propre point d'entrée (ParcController::ajouterModalite).
 * Réponse JSON : {code, libelle}.
 */
class ModaliteController extends Controller
{
    /** Listes ouvertes à la saisie manuelle : modèle, libellé de la liste. */
    public const LISTES = [
        'type_evenement' => [TypeEvenement::class, "Types d'événement"],
        'type_blocage'   => [TypeBlocage::class, 'Types de blocage'],
        'type_absence'   => [TypeAbsence::class, "Natures d'absence"],
        'titre_poste'    => [\App\Models\TitrePoste::class, 'Titres de poste'],
    ];

    public function store(Request $r)
    {
        abort_if($r->user()->aRole('direction'), 403, 'La direction consulte sans modifier les listes.');
        $v = $r->validate([
            'categorie' => 'required|in:' . implode(',', array_keys(self::LISTES)),
            'libelle' => 'required|string|min:2|max:80',
        ], [], ['libelle' => 'libellé']);
        // même règle de création que l'écran Administration > Listes de choix (libellé existant réutilisé, code tiré du libellé)
        $t = \App\Http\Controllers\Admin\ListeChoixController::creer($v['categorie'], $v['libelle']);
        // les titres de poste sont enregistrés par leur libellé (pas de code)
        return response()->json(['code' => $t->code ?? $t->libelle, 'libelle' => $t->libelle]);
    }
}
