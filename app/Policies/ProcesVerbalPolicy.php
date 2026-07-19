<?php

namespace App\Policies;

use App\Models\ProcesVerbal;
use App\Models\Soutenance;
use App\Models\User;

class ProcesVerbalPolicy
{
    public function view(User $user, ProcesVerbal $pv): bool
    {
        return $user->hasAnyRole(['admin_general', 'responsable_formation'])
            || $pv->soutenance->memoire->etudiant_id === $user->id
            || $pv->soutenance->estMembreDuJury($user);
    }

    public function generer(User $user, Soutenance $soutenance): bool
    {
        return $user->hasAnyRole(['admin_general', 'responsable_formation'])
            || $soutenance->estMembreDuJury($user);
    }

    public function delete(User $user, ProcesVerbal $pv): bool
    {
        return $user->hasRole('admin_general') || $pv->genere_par_id === $user->id;
    }

    public function signer(User $user, ProcesVerbal $pv): bool
    {
        return $user->hasAnyRole(['admin_general', 'responsable_formation'])
            || $pv->soutenance->estMembreDuJury($user);
    }
}
