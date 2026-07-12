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
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

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
    Route::post('memoires/{memoire}/affecter-encadreur', [MemoireController::class, 'affecterEncadreur']);

    // Versions de memoire
    Route::get('memoires/{memoire}/versions', [MemoireVersionController::class, 'index']);
    Route::post('memoires/{memoire}/versions', [MemoireVersionController::class, 'store']);
    Route::post('versions/{version}/corriger', [MemoireVersionController::class, 'corriger']);
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
});