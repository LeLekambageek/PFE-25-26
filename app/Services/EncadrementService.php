<?php

namespace App\Services;

use App\Models\Encadrement;
use App\Models\EncadrementEntry;
use App\Models\EncadrementRendezVous;
use Illuminate\Database\Eloquent\Model;

class EncadrementService
{
    public function creer(int $etudiantId, int $enseignantId, string $type = 'stage', ?Model $encadrable = null): Encadrement
    {
        return Encadrement::create([
            'etudiant_id' => $etudiantId,
            'enseignant_id' => $enseignantId,
            'type' => $type,
            'statut' => 'actif',
            'encadrable_id' => $encadrable?->id,
            'encadrable_type' => $encadrable ? get_class($encadrable) : null,
        ]);
    }

    public function ajouterEntree(Encadrement $encadrement, int $auteurId, string $contenu): EncadrementEntry
    {
        return $encadrement->entries()->create([
            'auteur_id' => $auteurId,
            'contenu' => $contenu,
        ]);
    }

    public function planifierRdv(Encadrement $encadrement, string $datePrevue, ?string $sujet = null): EncadrementRendezVous
    {
        return $encadrement->rendezVous()->create([
            'date_prevue' => $datePrevue,
            'sujet' => $sujet,
            'statut' => 'planifie',
        ]);
    }

    public function cloturer(Encadrement $encadrement): Encadrement
    {
        $encadrement->update(['statut' => 'termine']);

        return $encadrement->fresh();
    }

    public function modifier(Encadrement $encadrement, int $enseignantId): Encadrement
    {
        $encadrement->update(['enseignant_id' => $enseignantId]);

        return $encadrement->fresh();
    }
}