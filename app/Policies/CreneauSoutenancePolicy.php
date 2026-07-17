<?php

namespace App\Policies;

use App\Models\CreneauSoutenance;
use App\Models\User;

class CreneauSoutenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('soutenances.planifier') || $user->hasRole('etudiant');
    }

    public function view(User $user, CreneauSoutenance $creneau): bool
    {
        return $user->can('soutenances.planifier')
            || ($creneau->memoire && $creneau->memoire->etudiant_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('soutenances.planifier');
    }

    public function update(User $user, CreneauSoutenance $creneau): bool
    {
        return $user->can('soutenances.planifier');
    }

    public function delete(User $user, CreneauSoutenance $creneau): bool
    {
        return $user->can('soutenances.planifier');
    }

    public function reserver(User $user): bool
    {
        return $user->hasRole('etudiant');
    }

    public function annuler(User $user, CreneauSoutenance $creneau): bool
    {
        return $user->can('soutenances.planifier');
    }

    public function validerReservation(User $user): bool
    {
        return $user->can('soutenances.planifier');
    }
}
