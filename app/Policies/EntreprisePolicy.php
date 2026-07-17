<?php

namespace App\Policies;

use App\Models\Entreprise;
use App\Models\User;

class EntreprisePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('entreprises.consulter');
    }

    public function view(User $user, Entreprise $entreprise): bool
    {
        return $user->can('entreprises.consulter');
    }

    public function create(User $user): bool
    {
        return $user->can('entreprises.ajouter');
    }

    public function update(User $user, Entreprise $entreprise): bool
    {
        return $user->can('entreprises.modifier');
    }

    public function delete(User $user, Entreprise $entreprise): bool
    {
        return $user->can('entreprises.supprimer');
    }
}
