<?php

namespace App\Policies;

use App\Models\ProcesVerbal;
use App\Models\Soutenance;
use App\Models\User;

class ProcesVerbalPolicy
{
    public function view(User $user, ProcesVerbal $pv): bool
    {
        return $user->hasRole('administration')
            || $pv->soutenance->memoire->etudiant_id === $user->id
            || $pv->soutenance->estMembreDuJury($user);
    }

    /**
     * Génération du PV : responsable de formation, admin, ou membre du jury de CETTE soutenance.
     */
    public function generer(User $user, Soutenance $soutenance): bool
    {
        return $user->hasRole('administration')
            || $soutenance->estMembreDuJury($user);
    }

    public function delete(User $user, ProcesVerbal $pv): bool
    {
        return $user->hasRole('administration') || $pv->genere_par_id === $user->id;
    }

    /**
     * Signature : admin, responsable de formation, ou membre du jury de la soutenance concernée.
     */
    public function signer(User $user, ProcesVerbal $pv): bool
    {
        return $user->hasRole('administration')
            || $pv->soutenance->estMembreDuJury($user);
    }
}
