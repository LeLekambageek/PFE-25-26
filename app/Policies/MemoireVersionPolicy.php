<?php

namespace App\Policies;

use App\Models\MemoireVersion;
use App\Models\User;

class MemoireVersionPolicy
{
    public function view(User $user, MemoireVersion $version): bool
    {
        $memoire = $version->memoire;

        return $user->hasAnyRole(['admin_general', 'responsable_formation'])
            || $memoire->etudiant_id === $user->id
            || $memoire->encadreur_id === $user->id;
    }

    public function create(User $user, MemoireVersion $version = null): bool
    {
        return $user->can('memoires.deposer_version');
    }

    public function corriger(User $user, MemoireVersion $version): bool
    {
        return $user->can('memoires.corriger') && $version->memoire->encadreur_id === $user->id;
    }

    public function validerFinale(User $user, MemoireVersion $version): bool
    {
        return $version->memoire->encadreur_id === $user->id
            || $user->hasRole('responsable_formation');
    }
}
