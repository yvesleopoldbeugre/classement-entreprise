<?php

use App\Http\Controllers\Api\AvisEntrepriseController;
use App\Http\Controllers\Api\EntrepriseController;
use App\Http\Controllers\Api\MissionController;
use App\Http\Controllers\Api\ProfilController;
use App\Http\Controllers\Api\RetourEntretienController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes publiques : classement et consultation
|--------------------------------------------------------------------------
*/
Route::get('entreprises', [EntrepriseController::class, 'index']);
Route::get('entreprises/{entreprise}', [EntrepriseController::class, 'show']);
Route::get('avis', [AvisEntrepriseController::class, 'index']);
Route::get('retours-entretiens', [RetourEntretienController::class, 'index']);
Route::get('missions', [MissionController::class, 'index']);

/*
|--------------------------------------------------------------------------
| Routes protégées (Sanctum) : contributions et administration
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [ProfilController::class, 'show']);
    Route::patch('profil', [ProfilController::class, 'update']);

    Route::apiResource('entreprises', EntrepriseController::class)
        ->only(['store', 'update', 'destroy']);

    Route::apiResource('avis', AvisEntrepriseController::class)
        ->only(['store', 'update', 'destroy']);

    Route::apiResource('missions', MissionController::class)
        ->only(['store', 'update', 'destroy']);

    Route::apiResource('retours-entretiens', RetourEntretienController::class)
        ->only(['store', 'update', 'destroy'])
        ->parameters(['retours-entretiens' => 'retoursEntretien']);
});
