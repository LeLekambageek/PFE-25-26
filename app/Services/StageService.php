<?php

namespace App\Services;

use App\Models\Stage;
use Illuminate\Validation\ValidationException;
use App\Models\StageJournalEntry;

class StageService
{
    public function creerDemande(array $data, int $etudiantId): Stage
    {
        return Stage::create([
            ...$data,
            'etudiant_id' => $etudiantId,
            'statut' => 'en_attente',
        ]);
    }

    /**
     * Affectation directe et définitive d'un stage par l'administration
     * (l'étudiant ne peut pas créer/refuser une affectation).
     */
    public function affecterDirectement(array $data): Stage
    {
        return Stage::create([
            'etudiant_id' => $data['etudiant_id'],
            'entreprise_id' => $data['entreprise_id'],
            'encadreur_id' => $data['encadreur_id'] ?? null,
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'statut' => 'valide',
        ]);
    }

    public function valider(Stage $stage): Stage
    {
        if (! $stage->peutEtreValide()) {
            throw ValidationException::withMessages([
                'statut' => ["Le stage est deja au statut « {$stage->statut} », impossible de le valider."],
            ]);
        }

        $stage->update(['statut' => 'valide']);

        return $stage->fresh();
    }

    public function affecterEncadreur(Stage $stage, int $encadreurId): Stage
    {
        $stage->update(['encadreur_id' => $encadreurId]);

        return $stage->fresh();
    }

    public function cloturerEtGenererAttestation(Stage $stage): Stage
    {
        $stage->update(['statut' => 'termine']);

        return $stage->fresh();
    }

    public function ajouterEntreeJournal(Stage $stage, int $auteurId, string $contenu): StageJournalEntry
{
    return $stage->journalEntries()->create([
        'auteur_id' => $auteurId,
        'contenu' => $contenu,
    ]);
}
}