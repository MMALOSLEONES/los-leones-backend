<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CandidatureController;
use App\Http\Controllers\FighterController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\FighterMediaController;

Route::post('/login', [AuthController::class, 'login']);


Route::post('/candidatures', [CandidatureController::class, 'store']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/fighters', [FighterController::class, 'index']);
Route::get('/fighters/{id}', [FighterController::class, 'show']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);
Route::get('/fighters', [FighterController::class, 'publicIndex']);
Route::get('/fighters/{id}', [FighterController::class, 'publicShow']);


Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);

    // Gestion des candidatures
    Route::get('/candidatures', [CandidatureController::class, 'index']);
    Route::get('/candidatures/{id}', [CandidatureController::class, 'show']);
    Route::put('/candidatures/{id}', [CandidatureController::class, 'update']);
    Route::delete('/candidatures/{id}', [CandidatureController::class, 'destroy']);
    Route::post('/candidatures/{id}/promouvoir', [CandidatureController::class, 'promouvoirEnFighter']);
    Route::get('/candidatures/{id}/photo', [CandidatureController::class, 'photo']);

    // Gestion des fighters
    Route::post('/fighters', [FighterController::class, 'store']);
    Route::get('/fighters', [FighterController::class, 'index']);
    Route::delete('/fighters/{id}', [FighterController::class, 'destroy']);
    Route::get('/fighters/{id}', [FighterController::class, 'show']);  // Voit tout le monde
    Route::put('/fighters/{id}', [FighterController::class, 'update']);// Modifie et active/désactive

    // Gestion des articles
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
    Route::get('/articles', [ArticleController::class, 'indexAdmin']);

    Route::get('/me', fn(Request $request) => $request->user());

    Route::post('/fighters/{id}/photo', [FighterController::class, 'updatePhoto']);
    Route::post('/articles/{id}/photos', [ArticleController::class, 'addPhoto']);
    Route::delete('/articles/{id}/photos/{photoId}', [ArticleController::class, 'removePhoto']);

    //gestion photos et video fighter 
    Route::put('/fighters/{id}', [FighterController::class, 'update']);
    Route::post('/fighters/{id}/photos', [FighterMediaController::class, 'addPhoto']);
    Route::post('/fighters/{id}/videos/upload-url', [FighterMediaController::class, 'videoUploadUrl']);
    Route::post('/fighters/{id}/videos', [FighterMediaController::class, 'registerVideo']);
    Route::post('/fighters/{id}/videos/file', [FighterMediaController::class, 'addVideoFile']); 
    Route::put('/fighters/{id}/media/{mediaId}/cover', [FighterMediaController::class, 'makeCover']);
    Route::delete('/fighters/{id}/media/{mediaId}', [FighterMediaController::class, 'destroy']);


    Route::get('/candidatures/media/{mediaId}', [CandidatureController::class, 'afficherMediaPrive']);

    Route::post('/fighters/{id}/videos/upload-url', [FighterMediaController::class, 'videoUploadUrl']);

});
