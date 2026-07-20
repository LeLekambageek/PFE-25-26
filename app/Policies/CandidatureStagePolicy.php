<?php

namespace App\Policies;

use App\Models\CandidatureStage;
use App\Models\User;

class CandidatureStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('administration') || $user->hasRole('etudiant');
    }

    public function view(User $user, CandidatureStage $candidature): bool
    {
        return $user->hasRole('administration')
            || $candidature->etudiant->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('stages.demander') && $user->hasRole('etudiant');
    }

    /**
     * Triage de la candidature (retenue/rejetée) par l'administration.
     */
    public function update(User $user, CandidatureStage $candidature): bool
    {
        return $user->can('stages.valider');
    }

    public function delete(User $user, CandidatureStage $candidature): bool
    {
        return $user->hasRole('administration')
            || ($candidature->etudiant->user_id === $user->id && $candidature->estEnAttente());
    }

    public function affecterStage(User $user): bool
    {
        return $user->hasRole('administration');
    }
}
