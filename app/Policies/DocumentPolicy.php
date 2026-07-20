<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('bibliotheque.consulter');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->can('bibliotheque.consulter');
    }

    public function create(User $user): bool
    {
        return $user->can('bibliotheque.archiver');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->can('bibliotheque.modifier_metadonnees');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->can('bibliotheque.supprimer');
    }
}
