<?php

use App\Http\Controllers\Api\EncadrementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AnnuaireController;

Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/stages', [StageController::class, 'index']);
    Route::post('/stages', [StageController::class, 'store']);
    Route::post('/stages/{stage}/valider', [StageController::class, 'validerStage']);
    Route::post('/stages/{stage}/affecter-encadreur', [StageController::class, 'affecterEncadreur']);
    Route::get('/stages/{stage}/journal', [StageController::class, 'journal']);
    Route::post('/stages/{stage}/journal', [StageController::class, 'ajouterEntreeJournal']);
    Route::post('/stages/{stage}/cloturer', [StageController::class, 'cloturer']);

    Route::get('/encadrements', [EncadrementController::class, 'index']);
    Route::post('/encadrements', [EncadrementController::class, 'store']);
    Route::post('/encadrements/{encadrement}/entree', [EncadrementController::class, 'ajouterEntree']);
    Route::post('/encadrements/{encadrement}/rendez-vous', [EncadrementController::class, 'planifierRdv']);
    Route::post('/encadrements/{encadrement}/cloturer', [EncadrementController::class, 'cloturer']);

    Route::get('/annuaire/etudiants', [AnnuaireController::class, 'etudiants']);
    Route::get('/annuaire/enseignants', [AnnuaireController::class, 'enseignants']);

    Route::put('/encadrements/{encadrement}', [EncadrementController::class, 'modifier']);
});