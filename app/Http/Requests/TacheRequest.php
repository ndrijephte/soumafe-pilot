<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TacheRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'intitule'   => ['required', 'string', 'max:255'],
            'type'       => ['required', 'in:T1,T2,T3'],
            // Règle d'or du cadrage : une T1 n'existe pas hors sprint
            'sprint_id'  => ['required_if:type,T1', 'nullable', 'exists:sprints,id'],
            'projet_id'  => ['required_if:type,T1', 'nullable', 'exists:projets,id'],
            'activite'   => ['nullable', 'string', 'max:255'],
            'priorite'   => ['required', 'in:haute,moyenne,basse'],
            'date_debut' => ['required', 'date'],
            // T1 et T2 : échéance obligatoire ; T3 (urgente non planifiée) : optionnelle
            'echeance'   => [$this->input('type') !== 'T3' ? 'required' : 'nullable', 'date', 'after_or_equal:date_debut'],
            'points'     => ['required_if:type,T1', 'nullable', 'integer', 'between:1,21'],
        ];
    }

    public function messages(): array
    {
        return [
            'sprint_id.required_if' => 'Une tâche de sprint (T1) doit être rattachée à un sprint - aucune tâche projet hors sprint.',
            'projet_id.required_if' => 'Une tâche de sprint (T1) doit être rattachée à un projet.',
            'echeance.required' => "L'échéance est obligatoire pour les tâches T1 et T2.",
            'echeance.after_or_equal' => "L'échéance ne peut pas précéder la date de début.",
            'points.required_if' => 'Les points sont obligatoires pour une tâche de sprint (T1).',
        ];
    }
}
