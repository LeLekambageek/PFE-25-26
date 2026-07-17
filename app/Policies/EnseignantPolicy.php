<?php

namespace App\Policies;

use App\Models\User;

class EnseignantPolicy
{
    /**
     * Consultation des informations de stage d'un étudiant encadré. L'appartenance
     * (est-ce bien SON étudiant) est vérifiée dans le contrôleur via l'Encadrement.
     */
    public function viewEtudiantStage(User $user): bool
    {
        return $user->hasRole('enseignant_encadreur');
    }
}
