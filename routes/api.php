<?php

use App\Http\Controllers\Api\ScrapingController;
use App\Http\Controllers\Api\RentalSourceApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes pour le scraping via API
Route::prefix('scraping')->group(function () {
    // Démarrer un scraping
    Route::post('/start', [ScrapingController::class, 'start'])->name('api.scraping.start');
    
    // Obtenir le statut d'un scraping
    Route::get('/status/{jobId}', [ScrapingController::class, 'status'])->name('api.scraping.status');
    
    // Liste des sources disponibles
    Route::get('/sources', [ScrapingController::class, 'sources'])->name('api.scraping.sources');
});

// Routes pour les sources de location (API)
Route::prefix('rentals')->group(function () {
    // Liste des sources avec filtres
    Route::get('/', [RentalSourceApiController::class, 'index'])->name('api.rentals.index');
    
    // Détails d'une source
    Route::get('/{id}', [RentalSourceApiController::class, 'show'])->name('api.rentals.show');
    
    // Statistiques
    Route::get('/stats/summary', [RentalSourceApiController::class, 'stats'])->name('api.rentals.stats');
});

