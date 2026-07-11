<?php

namespace App\Services;

use App\Models\Encadrement;
use App\Models\EncadrementEntry;
use App\Models\EncadrementRendezVous;

class EncadrementService
{
    public function creer(int $etudiantId, int $enseignantId, string $type = 'stage'): Encadrement
    {
        return Encadrement::create([
            'etudiant_id' => $etudiantId,
            'enseignant_id' => $enseignantId,
            'type' => $type,
            'statut' => 'actif',
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
}