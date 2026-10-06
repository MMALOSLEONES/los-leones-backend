<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\CandidatureController;
use App\Http\Controllers\FighterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/candidatures', [CandidatureController::class, 'store']);

Route::prefix('admin')->group(function () {
    
    // CRUD des Candidatures (Demandes)
    Route::get('/candidatures', [CandidatureController::class, 'index']);          // Afficher tout
    Route::get('/candidatures/{id}', [CandidatureController::class, 'show']);     // Voir une seule
    Route::put('/candidatures/{id}', [CandidatureController::class, 'update']);   // Modifier (ex: changer le statut)
    Route::delete('/candidatures/{id}', [CandidatureController::class, 'destroy']); // Supprimer
    
    // Action spéciale : Transformer une candidature  validée en Fighter
    Route::post('/candidatures/{id}/promouvoir', [CandidatureController::class, 'promouvoirEnFighter']);

    // Création directe d'un Fighter par l'admin (sans passer par une candidature)
    Route::post('/fighters', [FighterController::class, 'store']);
    
});


// Les visiteurs peuvent lire les articles, mais seul l'admin peut les modifier
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);



Route::prefix('admin')->group(function () {
    Route::post('/articles', [ArticleController::class, 'store']);       // Créer
    Route::put('/articles/{id}', [ArticleController::class, 'update']);   // Modifier
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']); // Supprimer
});
