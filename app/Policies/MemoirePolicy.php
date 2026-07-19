<?php

namespace App\Policies;

use App\Models\Memoire;
use App\Models\User;

class MemoirePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin_general', 'responsable_formation', 'enseignant_encadreur', 'etudiant',
        ]);
    }

    public function view(User $user, Memoire $memoire): bool
    {
        return $user->hasAnyRole(['admin_general', 'responsable_formation'])
            || $memoire->etudiant_id === $user->id
            || $memoire->encadreur_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('memoires.proposer') || $user->hasRole('enseignant_encadreur');
    }

    public function valider(User $user): bool
    {
        return $user->can('memoires.valider');
    }

    public function affecterEncadreur(User $user): bool
    {
        return $user->hasAnyRole(['admin_general', 'responsable_formation']);
    }

    public function update(User $user, Memoire $memoire): bool
    {
        return $user->hasRole('admin_general')
            || ($user->hasRole('responsable_formation'))
            || $memoire->etudiant_id === $user->id;
    }

    public function delete(User $user, Memoire $memoire): bool
    {
        return $user->hasRole('admin_general');
    }
}
