<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemoireController;
use App\Http\Controllers\Api\MemoireVersionController;
use App\Http\Controllers\Api\SoutenanceController;
use App\Http\Controllers\Api\SoutenanceNoteController;
use App\Http\Controllers\Api\ProcesVerbalController;
use App\Http\Controllers\Api\StageController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);
    Route::apiResource('memoires', MemoireController::class);
    Route::post('memoires/{memoire}/valider', [MemoireController::class, 'valider']);
    Route::post('memoires/{memoire}/rejeter', [MemoireController::class, 'rejeter']);
    Route::post('memoires/{memoire}/affecter-encadreur', [MemoireController::class, 'affecterEncadreur']);
    Route::apiResource('stages', StageController::class)->only(['index', 'store']);
    Route::post('stages/{stage}/valider', [StageController::class, 'validerStage']);
    Route::post('stages/{stage}/affecter-encadreur', [StageController::class, 'affecterEncadreur']);
    Route::get('memoires/{memoire}/versions', [MemoireVersionController::class, 'index']);
    Route::post('memoires/{memoire}/versions', [MemoireVersionController::class, 'store']);
    Route::post('versions/{version}/corriger', [MemoireVersionController::class, 'corriger']);
    Route::post('versions/{version}/valider-finale', [MemoireVersionController::class, 'validerFinale']);
    Route::get('versions/{version}/download', [MemoireVersionController::class, 'download']);
    Route::apiResource('soutenances', SoutenanceController::class)->except(['update']);
    Route::put('soutenances/{soutenance}', [SoutenanceController::class, 'update']);
    Route::post('soutenances/{soutenance}/jury', [SoutenanceController::class, 'composerJury']);
    Route::post('soutenances/{soutenance}/convocations', [SoutenanceController::class, 'envoyerConvocations']);
    Route::post('soutenances/{soutenance}/publier-resultats', [SoutenanceController::class, 'publierResultats']);
    Route::get('soutenances/{soutenance}/notes', [SoutenanceNoteController::class, 'index']);
    Route::post('soutenances/{soutenance}/notes', [SoutenanceNoteController::class, 'store']);
    Route::prefix('soutenances/{soutenance}/proces-verbaux')->group(function () {
        Route::get('/', [ProcesVerbalController::class, 'index']);
        Route::post('/', [ProcesVerbalController::class, 'generer']);
        Route::get('/{procesVerbal}', [ProcesVerbalController::class, 'show']);
        Route::get('/{procesVerbal}/download', [ProcesVerbalController::class, 'download']);
        Route::post('/{procesVerbal}/signer', [ProcesVerbalController::class, 'signer']);
        Route::delete('/{procesVerbal}', [ProcesVerbalController::class, 'destroy']);
    });
    Route::get('etudiants/{etudiantId}/proces-verbaux', [ProcesVerbalController::class, 'getByEtudiant']);
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
