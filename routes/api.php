<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidatureController;
use App\Http\Controllers\FighterController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);


Route::post('/candidatures', [CandidatureController::class, 'store']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);


Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestion des candidatures
    Route::get('/candidatures', [CandidatureController::class, 'index']);
    Route::get('/candidatures/{id}', [CandidatureController::class, 'show']);
    Route::put('/candidatures/{id}', [CandidatureController::class, 'update']);
    Route::delete('/candidatures/{id}', [CandidatureController::class, 'destroy']);
    Route::post('/candidatures/{id}/promouvoir', [CandidatureController::class, 'promouvoirEnFighter']);

    // Gestion des fighters
    Route::post('/fighters', [FighterController::class, 'store']);

    // Gestion des articles
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
});
