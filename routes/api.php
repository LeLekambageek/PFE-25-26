<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StageController;
use App\Http\Controllers\Api\EncadrementController;
use App\Http\Controllers\Api\AnnuaireController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemoireController;
use App\Http\Controllers\Api\MemoireVersionController;
use App\Http\Controllers\Api\SoutenanceController;
use App\Http\Controllers\Api\SoutenanceNoteController;
use App\Http\Controllers\Api\ProcesVerbalController;
use App\Http\Controllers\Api\EntrepriseController;
use App\Http\Controllers\Api\CandidatureStageController;
use App\Http\Controllers\Api\RapportStageController;
use App\Http\Controllers\Api\CreneauSoutenanceController;
use App\Http\Controllers\Api\EtudiantController;
use App\Http\Controllers\Api\EncadreurController;
use App\Http\Controllers\Api\AdministrationController;
use App\Http\Controllers\Api\JuryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DocumentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OffreStageController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/changer-mot-de-passe', [AuthController::class, 'changerMotDePasse']);

    // Stages
    Route::get('/stages', [StageController::class, 'index']);
    Route::post('/stages', [StageController::class, 'store']);
    Route::post('/stages/{stage}/valider', [StageController::class, 'validerStage']);
    Route::post('/stages/{stage}/affecter-encadreur', [StageController::class, 'affecterEncadreur']);
    Route::get('/stages/{stage}/journal', [StageController::class, 'journal']);
    Route::post('/stages/{stage}/journal', [StageController::class, 'ajouterEntreeJournal']);
    Route::post('/stages/{stage}/cloturer', [StageController::class, 'cloturer']);

    // Encadrements
    Route::get('/encadrements', [EncadrementController::class, 'index']);
    Route::post('/encadrements', [EncadrementController::class, 'store']);
    Route::put('/encadrements/{encadrement}', [EncadrementController::class, 'modifier']);
    Route::get('/encadrements/{encadrement}/entree', [EncadrementController::class, 'entrees']);
    Route::post('/encadrements/{encadrement}/entree', [EncadrementController::class, 'ajouterEntree']);
    Route::post('/encadrements/{encadrement}/rendez-vous', [EncadrementController::class, 'planifierRdv']);
    Route::post('/encadrements/{encadrement}/cloturer', [EncadrementController::class, 'cloturer']);

    // Annuaire
    Route::get('/annuaire/etudiants', [AnnuaireController::class, 'etudiants']);
    Route::get('/annuaire/enseignants', [AnnuaireController::class, 'enseignants']);

    // Memoires
    Route::apiResource('memoires', MemoireController::class);
    Route::post('memoires/{memoire}/valider', [MemoireController::class, 'valider']);
    Route::post('memoires/{memoire}/rejeter', [MemoireController::class, 'rejeter']);
    Route::post('memoires/{memoire}/demander-modification', [MemoireController::class, 'demanderModification']);
    Route::post('memoires/{memoire}/affecter-encadreur', [MemoireController::class, 'affecterEncadreur']);
    Route::post('memoires/{memoire}/accorder-eligibilite-soutenance', [MemoireController::class, 'accorderEligibiliteSoutenance']);

    // Versions de memoire
    Route::get('memoires/{memoire}/versions', [MemoireVersionController::class, 'index']);
    Route::post('memoires/{memoire}/versions', [MemoireVersionController::class, 'store']);
    Route::post('versions/{version}/corriger', [MemoireVersionController::class, 'corriger']);
    Route::post('versions/{version}/corrections', [MemoireVersionController::class, 'ajouterCommentaire']);
    Route::post('versions/{version}/valider-finale', [MemoireVersionController::class, 'validerFinale']);
    Route::get('versions/{version}/download', [MemoireVersionController::class, 'download']);

    // Soutenances
    Route::apiResource('soutenances', SoutenanceController::class)->except(['update']);
    Route::put('soutenances/{soutenance}', [SoutenanceController::class, 'update']);
    Route::post('soutenances/{soutenance}/jury', [SoutenanceController::class, 'composerJury']);
    Route::post('soutenances/{soutenance}/convocations', [SoutenanceController::class, 'envoyerConvocations']);
    Route::post('soutenances/{soutenance}/publier-resultats', [SoutenanceController::class, 'publierResultats']);

    // Notation
    Route::get('soutenances/{soutenance}/notes', [SoutenanceNoteController::class, 'index']);
    Route::post('soutenances/{soutenance}/notes', [SoutenanceNoteController::class, 'store']);

    // Proces-verbaux
    Route::prefix('soutenances/{soutenance}/proces-verbaux')->group(function () {
        Route::get('/', [ProcesVerbalController::class, 'index']);
        Route::post('/', [ProcesVerbalController::class, 'generer']);
        Route::get('/{procesVerbal}', [ProcesVerbalController::class, 'show']);
        Route::get('/{procesVerbal}/download', [ProcesVerbalController::class, 'download']);
        Route::post('/{procesVerbal}/signer', [ProcesVerbalController::class, 'signer']);
        Route::delete('/{procesVerbal}', [ProcesVerbalController::class, 'destroy']);
    });
    Route::get('etudiants/{etudiantId}/proces-verbaux', [ProcesVerbalController::class, 'getByEtudiant']);

    // Bibliotheque numerique (accessible a tous les roles, sans filtre par role)
    Route::apiResource('documents', DocumentController::class);
    Route::get('documents/{document}/download', [DocumentController::class, 'download']);

    // Entreprises
    Route::apiResource('entreprises', EntrepriseController::class);

    // Candidatures de stage
    Route::apiResource('candidatures-stage', CandidatureStageController::class)->parameters(['candidatures-stage' => 'candidature']);
    Route::post('candidatures-stage/{candidature}/affecter-stage', [CandidatureStageController::class, 'affecterStage']);

    // Rapports de stage
    Route::apiResource('rapports-stage', RapportStageController::class)->except(['update'])->parameters(['rapports-stage' => 'rapport']);
    Route::post('rapports-stage/{rapport}/corriger', [RapportStageController::class, 'corriger']);
    Route::post('rapports-stage/{rapport}/valider', [RapportStageController::class, 'valider']);
    Route::get('rapports-stage/{rapport}/download', [RapportStageController::class, 'download']);

    // Créneaux de soutenance
    Route::apiResource('creneaux-soutenance', CreneauSoutenanceController::class)->parameters(['creneaux-soutenance' => 'creneau']);
    Route::post('creneaux-soutenance/{creneau}/reserver', [CreneauSoutenanceController::class, 'reserver']);
    Route::post('creneaux-soutenance/{creneau}/annuler', [CreneauSoutenanceController::class, 'annuler']);
    Route::post('creneaux-soutenance/{creneau}/valider', [CreneauSoutenanceController::class, 'validerReservation']);
    Route::get('creneaux-soutenance-disponibles', [CreneauSoutenanceController::class, 'disponiblesPourEtudiant']);

    // Étudiant 
    Route::get('etudiants', [EtudiantController::class, 'index']);
    Route::get('etudiants/{etudiant}', [EtudiantController::class, 'show']);
    Route::prefix('mon-espace')->group(function () {
        Route::get('stages', [EtudiantController::class, 'mesStages']);
        Route::get('stage-actif', [EtudiantController::class, 'monStageActif']);
        Route::get('memoires', [EtudiantController::class, 'mesMemoires']);
        Route::get('encadrements', [EtudiantController::class, 'mesEncadrements']);
        Route::get('candidatures', [EtudiantController::class, 'mesCandidatures']);
        Route::get('rapports', [EtudiantController::class, 'mesRapports']);
        Route::get('soutenance', [EtudiantController::class, 'informationsSoutenance']);
        Route::get('resultats-soutenance', [EtudiantController::class, 'resultatsSoutenance']);
    });

    // Encadreur
    Route::get('mes-etudiants-encadres', [EncadreurController::class, 'mesEtudiants']);
    Route::get('etudiants-encadres/{etudiantId}/stage', [EncadreurController::class, 'informationsStageEtudiant']);

    // Administration
    Route::prefix('administration')->group(function () {
        Route::post('etudiants', [AdministrationController::class, 'creerCompteEtudiant']);
        Route::put('etudiants/{id}', [AdministrationController::class, 'modifierCompteEtudiant']);
        Route::delete('etudiants/{id}', [AdministrationController::class, 'supprimerCompteEtudiant']);
        Route::get('enseignants', [AdministrationController::class, 'gererComptesEnseignants']);
        Route::post('enseignants', [AdministrationController::class, 'creerCompteEnseignant']);
        Route::put('enseignants/{id}', [AdministrationController::class, 'modifierCompteEnseignant']);
        Route::delete('enseignants/{id}', [AdministrationController::class, 'supprimerCompteEnseignant']);
        Route::post('enseignants/{enseignantId}/role-encadreur', [AdministrationController::class, 'attribuerRoleEncadreur']);
        Route::put('stages/{etudiantId}/entreprise', [AdministrationController::class, 'associerEntrepriseEtudiant']);
        Route::get('memoires-eligibles-soutenance', [AdministrationController::class, 'memoiresValidesFinale']);
        Route::get('jury', [AdministrationController::class, 'gererComptesJury']);
        Route::post('jury', [AdministrationController::class, 'creerCompteJury']);
        Route::put('jury/{id}', [AdministrationController::class, 'modifierCompteJury']);
        Route::delete('jury/{id}', [AdministrationController::class, 'supprimerCompteJury']);
        Route::post('jury/{jury}/reactiver', [AdministrationController::class, 'reactiverJury']);
        Route::post('users/{user}/reinitialiser-mot-de-passe', [AdministrationController::class, 'reinitialiserMotDePasse']);
    });

    // Jury 
    Route::prefix('jury')->group(function () {
        Route::get('mes-soutenances', [JuryController::class, 'mesSoutenances']);
        Route::get('soutenances/{soutenance}', [JuryController::class, 'informationsSoutenance']);
        Route::get('soutenances/{soutenance}/memoire', [JuryController::class, 'consulterMemoire']);
        Route::get('soutenances/{soutenance}/memoire/download', [JuryController::class, 'telechargerMemoire']);
        Route::get('soutenances/{soutenance}/acces', [JuryController::class, 'verifierAcces']);
        Route::post('soutenances/{soutenance}/valider-notes', [JuryController::class, 'validerMesNotes']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('non-lues/count', [NotificationController::class, 'countUnread']);
        Route::post('tout-marquer-lu', [NotificationController::class, 'markAllAsRead']);
        Route::get('{notification}', [NotificationController::class, 'show']);
        Route::post('{notification}/lu', [NotificationController::class, 'markAsRead']);
        Route::delete('{notification}', [NotificationController::class, 'destroy']);
    });

    // Tableaux de bord
    Route::prefix('dashboard')->group(function () {
        Route::get('apercu', [DashboardController::class, 'apercu']);
        Route::get('memoires-par-statut', [DashboardController::class, 'memoiresParStatut']);
        Route::get('taux-encadrement', [DashboardController::class, 'tauxEncadrement']);
        Route::get('planning-soutenances', [DashboardController::class, 'planningSoutenances']);
        Route::get('resultats-soutenances', [DashboardController::class, 'resultatsSoutenances']);
        Route::get('graphiques', [DashboardController::class, 'graphiques']);
        Route::get('delais-moyens', [DashboardController::class, 'delaisMoyens']);
        Route::get('export/pdf', [DashboardController::class, 'exporterPdf']);
        Route::get('export/excel', [DashboardController::class, 'exporterExcel']);
        Route::get('export/csv', [DashboardController::class, 'exporterCsv']);
    });

    Route::apiResource('offres-stage', OffreStageController::class);
});