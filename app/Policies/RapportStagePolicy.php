<?php

namespace App\Policies;

use App\Models\RapportStage;
use App\Models\Stage;
use App\Models\User;

class RapportStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administration', 'enseignant_encadreur', 'etudiant']);
    }

    public function view(User $user, RapportStage $rapport): bool
    {
        return $user->hasRole('administration')
            || $rapport->etudiant->user_id === $user->id
            || $rapport->stage->encadreur_id === $user->enseignant?->id;
    }

    public function create(User $user, Stage $stage): bool
    {
        return $user->hasRole('etudiant') && $stage->etudiant_id === $user->etudiant?->id;
    }

    public function corriger(User $user, RapportStage $rapport): bool
    {
        return $user->can('stages.evaluer') && $rapport->stage->encadreur_id === $user->enseignant?->id;
    }

    public function valider(User $user, RapportStage $rapport): bool
    {
        return ($user->can('stages.evaluer') && $rapport->stage->encadreur_id === $user->enseignant?->id)
            || $user->hasRole('administration');
    }

    public function delete(User $user, RapportStage $rapport): bool
    {
        return $user->hasRole('administration') || $rapport->etudiant->user_id === $user->id;
    }
}
