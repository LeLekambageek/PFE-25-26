<?php

namespace App\Policies;

use App\Models\MemoireVersion;
use App\Models\User;

class MemoireVersionPolicy
{
    public function view(User $user, MemoireVersion $version): bool
    {
        $memoire = $version->memoire;

        return $user->hasRole('administration')
            || $memoire->etudiant_id === $user->id
            || $memoire->encadreur_id === $user->id;
    }

    /**
     * Dépôt d'une nouvelle version : uniquement l'étudiant propriétaire du mémoire.
     */
    public function create(User $user, MemoireVersion $version = null): bool
    {
        return $user->can('memoires.deposer_version');
    }

    /**
     * Ajout d'une correction/annotation : uniquement l'encadreur affecté.
     */
    public function corriger(User $user, MemoireVersion $version): bool
    {
        return $user->can('memoires.corriger') && $version->memoire->encadreur_id === $user->id;
    }

    /**
     * Validation finale de la version (encadreur affecté ou administration).
     */
    public function validerFinale(User $user, MemoireVersion $version): bool
    {
        return $version->memoire->encadreur_id === $user->id
            || $user->hasRole('administration');
    }
}
