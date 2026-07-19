<?php

namespace App\Policies;

use App\Models\Soutenance;
use App\Models\User;

class SoutenancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'admin_general', 'responsable_formation', 'enseignant_encadreur', 'etudiant', 'jury_soutenance',
        ]);
    }

    public function view(User $user, Soutenance $soutenance): bool
    {
        return $user->hasAnyRole(['admin_general', 'responsable_formation'])
            || $soutenance->memoire->etudiant_id === $user->id
            || $soutenance->memoire->encadreur_id === $user->id
            || $soutenance->estMembreDuJury($user);
    }

    public function create(User $user): bool
    {
        return $user->can('soutenances.planifier');
    }

    public function update(User $user, Soutenance $soutenance): bool
    {
        return $user->can('soutenances.planifier');
    }

    public function delete(User $user, Soutenance $soutenance): bool
    {
        return $user->hasRole('admin_general');
    }

    public function gererJury(User $user): bool
    {
        return $user->can('soutenances.planifier');
    }

    public function noter(User $user, Soutenance $soutenance): bool
    {
        return $user->can('soutenances.noter') && $soutenance->estMembreDuJury($user);
    }

    public function publierResultats(User $user): bool
    {
        return $user->can('soutenances.publier_resultats');
    }
}
