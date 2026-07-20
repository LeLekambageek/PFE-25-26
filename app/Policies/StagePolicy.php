<?php

namespace App\Policies;

use App\Models\Stage;
use App\Models\User;

class StagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stages.suivre') || $user->hasRole('administration');
    }

    public function view(User $user, Stage $stage): bool
    {
        if ($user->hasRole('etudiant')) {
            return $user->etudiant?->id === $stage->etudiant_id;
        }
        if ($user->hasRole('enseignant_encadreur')) {
            return $user->enseignant?->id === $stage->encadreur_id;
        }
        return $user->hasRole('administration');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('administration');
    }

    public function associerEntreprise(User $user): bool
    {
        return $user->hasRole('administration');
    }

    public function validate(User $user, Stage $stage): bool
    {
        return $user->can('stages.valider') && $stage->peutEtreValide();
    }

    public function affecterEncadreur(User $user): bool
    {
        return $user->can('stages.affecter_encadreur');
    }

    public function cloturer(User $user, Stage $stage): bool
    {
        return $user->can('stages.valider') && $stage->statut === 'valide';
    }
}