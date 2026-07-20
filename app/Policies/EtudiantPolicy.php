<?php

namespace App\Policies;

use App\Models\Etudiant;
use App\Models\User;

class EtudiantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['administration', 'enseignant_encadreur']);
    }

    public function view(User $user, Etudiant $etudiant): bool
    {
        return $user->hasAnyRole(['administration', 'enseignant_encadreur'])
            || $etudiant->user_id === $user->id;
    }
}
