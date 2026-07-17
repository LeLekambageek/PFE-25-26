<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manageUsers(User $user): bool
    {
        return $user->can('utilisateurs.consulter');
    }

    public function createUser(User $user): bool
    {
        return $user->can('utilisateurs.creer');
    }

    public function assignRole(User $user): bool
    {
        return $user->can('utilisateurs.attribuer_role');
    }

    public function manageJury(User $user): bool
    {
        return $user->can('utilisateurs.consulter');
    }
}
