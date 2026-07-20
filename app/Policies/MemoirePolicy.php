<?php

namespace App\Policies;

use App\Models\Memoire;
use App\Models\User;

class MemoirePolicy
{
    /**
     * Tout utilisateur authentifié lié au projet peut lister (le controller filtre par rôle).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            'administration', 'enseignant_encadreur', 'etudiant',
        ]);
    }

    public function view(User $user, Memoire $memoire): bool
    {
        if ($user->hasRole('administration')) {
            return in_array($memoire->statut, ['valide', 'valide_final']);
        }

        return $memoire->etudiant_id === $user->id
            || $memoire->encadreur_id === $user->id;
    }

    /**
     * Proposition d'un sujet de mémoire : étudiant ou enseignant.
     */
    public function create(User $user): bool
    {
        return $user->can('memoires.proposer') || $user->hasRole('enseignant_encadreur');
    }

    /**
     * Validation du sujet, rejet, demande de modification : admin/responsable de
     * formation, OU l'encadreur déjà assigné à CE mémoire (cas du sujet proposé
     * par l'étudiant, soumis à son encadreur).
     */
    public function valider(User $user, Memoire $memoire): bool
    {
        return $user->can('memoires.valider') || $memoire->encadreur_id === $user->id;
    }

    public function affecterEncadreur(User $user): bool
    {
        return $user->hasRole('administration');
    }

    /**
     * Autoriser la demande de soutenance : geste explicite et distinct de
     * l'encadreur affecté à CE mémoire (indépendant du pourcentage d'avancement).
     */
    public function accorderEligibiliteSoutenance(User $user, Memoire $memoire): bool
    {
        return $user->hasRole('enseignant_encadreur') && $memoire->encadreur_id === $user->id;
    }

    public function update(User $user, Memoire $memoire): bool
    {
        return $memoire->encadreur_id === $user->id
            || $memoire->etudiant_id === $user->id;
    }

    public function delete(User $user, Memoire $memoire): bool
    {
        return $memoire->encadreur_id === $user->id;
    }
}
