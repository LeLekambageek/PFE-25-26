<?php

namespace App\Policies;

use App\Models\Encadrement;
use App\Models\User;

class EncadrementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('encadrements.consulter') || $user->hasRole('administration');
    }

    public function view(User $user, Encadrement $encadrement): bool
    {
        if ($user->hasRole('etudiant')) {
            return $user->etudiant?->id === $encadrement->etudiant_id;
        }
        if ($user->hasRole('enseignant_encadreur')) {
            return $user->enseignant?->id === $encadrement->enseignant_id;
        }
        return $user->hasRole('administration');
    }

    public function create(User $user): bool
    {
        return $user->can('encadrements.gerer');
    }

    public function update(User $user, Encadrement $encadrement): bool
    {
    return $user->can('encadrements.gerer');
    }

    public function ajouterEntree(User $user, Encadrement $encadrement): bool
    {
        return $this->view($user, $encadrement);
    }

    public function planifierRdv(User $user, Encadrement $encadrement): bool
    {
        return $user->hasRole('enseignant_encadreur') && $user->enseignant?->id === $encadrement->enseignant_id;
    }

    public function cloturer(User $user, Encadrement $encadrement): bool
    {
    return $user->hasRole('enseignant_encadreur') && $user->enseignant?->id === $encadrement->enseignant_id;
    }
}