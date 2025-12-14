<?php

use App\Http\Controllers\RentalSourceController;
use Illuminate\Support\Facades\Route;

// Rediriger la page d'accueil vers la liste des locations
Route::get('/', [RentalSourceController::class, 'index']);

Route::get('/rentals', [RentalSourceController::class, 'index'])->name('rentals.index');
