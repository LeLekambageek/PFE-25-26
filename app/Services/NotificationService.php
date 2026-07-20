<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Memoire;
use App\Models\Soutenance;
use App\Models\Stage;
use App\Models\Encadrement;
use App\Models\CreneauSoutenance;

class NotificationService
{
    public function envoyerNotification(User $user, string $type, string $titre, string $message, $notifiable = null, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'data' => $data,
            'statut' => 'non_lue',
            'date_creation' => now(),
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable ? $notifiable->id : null,
        ]);
    }

    // Notifications Étudiant
    public function affectationStage(Stage $stage): void
    {
        $this->envoyerNotification(
            $stage->etudiant->user,
            'affectation_stage',
            'Affectation de stage',
            "Vous avez été affecté au stage '{$stage->titre}' chez {$stage->entreprise->raison_sociale}.",
            $stage,
            ['stage_id' => $stage->id]
        );
    }

    public function affectationEncadreur(Encadrement $encadrement): void
    {
        $this->envoyerNotification(
            $encadrement->etudiant->user,
            'affectation_encadreur',
            'Affectation d\'encadreur',
            "L'enseignant {$encadrement->enseignant->user->name} a été affecté comme votre encadreur pour {$encadrement->type}.",
            $encadrement,
            ['encadrement_id' => $encadrement->id]
        );
    }

    public function nouveauCommentaireEncadreur(Encadrement $encadrement, string $contenu): void
    {
        $this->envoyerNotification(
            $encadrement->etudiant->user,
            'nouveau_commentaire',
            'Nouveau commentaire de l\'encadreur',
            "Votre encadreur a ajouté un nouveau commentaire.",
            $encadrement,
            ['encadrement_id' => $encadrement->id]
        );
    }

    public function nouveauRendezVous(Encadrement $encadrement): void
    {
        $this->envoyerNotification(
            $encadrement->etudiant->user,
            'nouveau_rendez_vous',
            'Nouveau rendez-vous programmé',
            "Un nouveau rendez-vous a été programmé avec votre encadreur.",
            $encadrement,
            ['encadrement_id' => $encadrement->id]
        );
    }

    public function memoireValideFinal(Memoire $memoire): void
    {
        $this->envoyerNotification(
            $memoire->etudiant,
            'memoire_valide_final',
            'Mémoire validé comme version finale',
            'Votre mémoire a été validé comme version finale. Vous pouvez maintenant demander un créneau de soutenance.',
            $memoire,
            ['memoire_id' => $memoire->id]
        );
    }

    public function sujetAModifier(Memoire $memoire): void
    {
        $this->envoyerNotification(
            $memoire->etudiant,
            'sujet_modifier',
            'Sujet à modifier',
            'Votre encadreur demande des modifications sur votre sujet de mémoire.',
            $memoire,
            ['memoire_id' => $memoire->id]
        );
    }

    public function nouveauDocumentAnnote(Memoire $memoire): void
    {
        $this->envoyerNotification(
            $memoire->etudiant,
            'document_annoté',
            'Nouveau document annoté',
            'Votre encadreur a annoté votre mémoire.',
            $memoire,
            ['memoire_id' => $memoire->id]
        );
    }

    public function soutenancePlanifiee(Soutenance $soutenance): void
    {
        $this->envoyerNotification(
            $soutenance->memoire->etudiant,
            'soutenance_planifiee',
            'Soutenance planifiée',
            "Votre soutenance a été planifiée le {$soutenance->date_soutenance->format('d/m/Y')} à {$soutenance->heure_debut} en salle {$soutenance->salle}.",
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }

    public function convocationDisponible(Soutenance $soutenance): void
    {
        $this->envoyerNotification(
            $soutenance->memoire->etudiant,
            'convocation_disponible',
            'Convocation disponible',
            'Votre convocation pour la soutenance est maintenant disponible.',
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }

    public function soutenanceValidee(Soutenance $soutenance): void
    {
        $this->envoyerNotification(
            $soutenance->memoire->etudiant,
            'soutenance_validee',
            'Soutenance validée',
            'Votre demande de créneau de soutenance a été validée par l\'administration.',
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }

    public function resultatsPublies(Soutenance $soutenance): void
    {
        $this->envoyerNotification(
            $soutenance->memoire->etudiant,
            'resultats_publies',
            'Résultats publiés',
            'Les résultats de votre soutenance ont été publiés.',
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }

    // Notifications Enseignant Encadreur
    public function nouveauMemoireDepose(Memoire $memoire): void
    {
        if ($memoire->encadreur) {
            $this->envoyerNotification(
                $memoire->encadreur,
                'nouveau_memoire',
                'Nouveau mémoire déposé',
                "L'étudiant {$memoire->etudiant->name} a déposé une nouvelle version de son mémoire.",
                $memoire,
                ['memoire_id' => $memoire->id]
            );
        }
    }

    public function rendezVousAccepte(Encadrement $encadrement): void
    {
        $this->envoyerNotification(
            $encadrement->enseignant->user,
            'rendez_vous_accepte',
            'Rendez-vous accepté',
            'L\'étudiant a accepté le rendez-vous.',
            $encadrement,
            ['encadrement_id' => $encadrement->id]
        );
    }

    public function reponseEtudiant(Encadrement $encadrement): void
    {
        $this->envoyerNotification(
            $encadrement->enseignant->user,
            'reponse_etudiant',
            'Réponse d\'un étudiant',
            'Vous avez reçu une nouvelle réponse de votre étudiant.',
            $encadrement,
            ['encadrement_id' => $encadrement->id]
        );
    }

    public function nouvellePropositionSujet(Memoire $memoire): void
    {
        if ($memoire->encadreur) {
            $this->envoyerNotification(
                $memoire->encadreur,
                'nouvelle_proposition_sujet',
                'Nouvelle proposition de sujet',
                "L'étudiant {$memoire->etudiant->name} a proposé un nouveau sujet de mémoire.",
                $memoire,
                ['memoire_id' => $memoire->id]
            );
        }
    }

    public function demandeCreneauSoutenance(CreneauSoutenance $creneau): void
    {
        if ($creneau->memoire && $creneau->memoire->encadreur) {
            $this->envoyerNotification(
                $creneau->memoire->encadreur,
                'demande_creneau_soutenance',
                'Demande de créneau de soutenance',
                "L'étudiant {$creneau->memoire->etudiant->name} a demandé un créneau de soutenance.",
                $creneau,
                ['creneau_id' => $creneau->id]
            );
        }
    }

    public function convocationGeneree(Soutenance $soutenance): void
    {
        if ($soutenance->memoire->encadreur) {
            $this->envoyerNotification(
                $soutenance->memoire->encadreur,
                'convocation_generee',
                'Convocation générée',
                'Une convocation a été générée pour la soutenance de votre étudiant.',
                $soutenance,
                ['soutenance_id' => $soutenance->id]
            );
        }
    }

    // Notifications Administration
    public function nouvelleCandidatureStage(\App\Models\CandidatureStage $candidature): void
    {
        $admins = User::role('administration')->get();
        
        foreach ($admins as $admin) {
            $this->envoyerNotification(
                $admin,
                'nouvelle_candidature',
                'Nouvelle candidature de stage',
                "L'étudiant {$candidature->etudiant->user->name} a postulé à une offre de stage.",
                $candidature,
                ['candidature_id' => $candidature->id]
            );
        }
    }

    public function validationFinaleMemoire(Memoire $memoire): void
    {
        $admins = User::role('administration')->get();
        
        foreach ($admins as $admin) {
            $this->envoyerNotification(
                $admin,
                'validation_finale_memoire',
                'Validation finale d\'un mémoire',
                "Le mémoire de {$memoire->etudiant->name} a été validé comme version finale.",
                $memoire,
                ['memoire_id' => $memoire->id]
            );
        }
    }

    public function soutenanceProgrammee(Soutenance $soutenance): void
    {
        $admins = User::role('administration')->get();
        
        foreach ($admins as $admin) {
            $this->envoyerNotification(
                $admin,
                'soutenance_programmee',
                'Soutenance programmée',
                "Une soutenance a été programmée pour {$soutenance->memoire->etudiant->name}.",
                $soutenance,
                ['soutenance_id' => $soutenance->id]
            );
        }
    }

    public function juryIndisponible(Soutenance $soutenance): void
    {
        $admins = User::role('administration')->get();
        
        foreach ($admins as $admin) {
            $this->envoyerNotification(
                $admin,
                'jury_indisponible',
                'Jury indisponible',
                'Un membre du jury est indisponible pour une soutenance.',
                $soutenance,
                ['soutenance_id' => $soutenance->id]
            );
        }
    }

    public function tousJuryOntNote(Soutenance $soutenance): void
    {
        $admins = User::role('administration')->get();
        
        foreach ($admins as $admin) {
            $this->envoyerNotification(
                $admin,
                'tous_jury_ont_note',
                'Tous les membres du jury ont noté',
                "Tous les membres du jury ont validé leurs notes pour la soutenance de {$soutenance->memoire->etudiant->name}.",
                $soutenance,
                ['soutenance_id' => $soutenance->id]
            );
        }
    }

    // Notifications Jury
    public function nouvelleSoutenanceJury(Soutenance $soutenance, User $jury): void
    {
        $this->envoyerNotification(
            $jury,
            'nouvelle_soutenance',
            'Nouvelle soutenance programmée',
            "Vous avez été assigné à une nouvelle soutenance.",
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }

    public function convocationJury(Soutenance $soutenance, User $jury): void
    {
        $this->envoyerNotification(
            $jury,
            'convocation_jury',
            'Convocation disponible',
            'Votre convocation pour la soutenance est disponible.',
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }

    public function rappelAvantSoutenance(Soutenance $soutenance, User $jury): void
    {
        $this->envoyerNotification(
            $jury,
            'rappel_soutenance',
            'Rappel avant soutenance',
            "Rappel : Votre soutenance aura lieu le {$soutenance->date_soutenance->format('d/m/Y')} à {$soutenance->heure_debut}.",
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }

    public function procesVerbalGenere(Soutenance $soutenance, User $jury): void
    {
        $this->envoyerNotification(
            $jury,
            'proces_verbal_genere',
            'Procès-verbal généré',
            'Le procès-verbal de la soutenance a été généré.',
            $soutenance,
            ['soutenance_id' => $soutenance->id]
        );
    }
}
