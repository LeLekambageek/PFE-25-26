<?php

namespace App\Policies;

use App\Models\OffreStage;
use App\Models\User;

class OffreStagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('offres.consulter') || $user->hasRole('administration');
    }

    public function view(User $user, OffreStage $offre): bool
    {
        return $user->can('offres.consulter') || $user->hasRole('administration');
    }

    public function create(User $user): bool
    {
        return $user->can('offres.gerer');
    }

    public function update(User $user, OffreStage $offre): bool
    {
        return $user->can('offres.gerer');
    }

    public function delete(User $user, OffreStage $offre): bool
    {
        return $user->can('offres.gerer');
    }
}