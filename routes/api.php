<?php

use App\Http\Controllers\Api\MemoireController;
use App\Http\Controllers\Api\MemoireVersionController;
use App\Http\Controllers\Api\SoutenanceController;
use App\Http\Controllers\Api\SoutenanceNoteController;
use App\Http\Controllers\Api\ProcesVerbalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {

    // Mémoires
    Route::apiResource('memoires', MemoireController::class);
    Route::post('memoires/{memoire}/valider', [MemoireController::class, 'valider']);
    Route::post('memoires/{memoire}/rejeter', [MemoireController::class, 'rejeter']);
    Route::post('memoires/{memoire}/affecter-encadreur', [MemoireController::class, 'affecterEncadreur']);

    // Versions de mémoire
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

    // Procès-verbaux
    Route::prefix('soutenances/{soutenance}/proces-verbaux')->group(function () {
        Route::get('/', [ProcesVerbalController::class, 'index']);
        Route::post('/', [ProcesVerbalController::class, 'generer']);
        Route::get('/{procesVerbal}', [ProcesVerbalController::class, 'show']);
        Route::get('/{procesVerbal}/download', [ProcesVerbalController::class, 'download']);
        Route::post('/{procesVerbal}/signer', [ProcesVerbalController::class, 'signer']);
        Route::delete('/{procesVerbal}', [ProcesVerbalController::class, 'destroy']);
    });
    Route::get('etudiants/{etudiantId}/proces-verbaux', [ProcesVerbalController::class, 'getByEtudiant']);
});