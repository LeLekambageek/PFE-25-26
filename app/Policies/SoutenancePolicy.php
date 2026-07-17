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

    /**
     * Planification d'une soutenance : responsable de formation / admin.
     */
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

    /**
     * Composition du jury.
     */
    public function gererJury(User $user): bool
    {
        return $user->can('soutenances.planifier');
    }

    /**
     * Notation : uniquement un membre du jury affecté à CETTE soutenance, avec un
     * accès actif et des notes pas encore verrouillées.
     */
    public function noter(User $user, Soutenance $soutenance): bool
    {
        if (! $user->can('soutenances.noter')) {
            return false;
        }

        $juryRow = $soutenance->jury()->where('user_id', $user->id)->first();

        return $juryRow && $juryRow->peutNoter();
    }

    /**
     * Verrouillage définitif des notes du juré courant pour cette soutenance.
     */
    public function validerNotesJury(User $user, Soutenance $soutenance): bool
    {
        return $this->noter($user, $soutenance);
    }

    /**
     * Publication des résultats : responsable de formation / admin.
     */
    public function publierResultats(User $user): bool
    {
        return $user->can('soutenances.publier_resultats');
    }
}
