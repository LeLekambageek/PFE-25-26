<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StageController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/stages', [StageController::class, 'index']);
    Route::post('/stages', [StageController::class, 'store']);
    Route::post('/stages/{stage}/valider', [StageController::class, 'validerStage']);
    Route::post('/stages/{stage}/affecter-encadreur', [StageController::class, 'affecterEncadreur']);
});