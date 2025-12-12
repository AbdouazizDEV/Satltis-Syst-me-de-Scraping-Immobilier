<?php

use App\Http\Controllers\RentalSourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/rentals', [RentalSourceController::class, 'index'])->name('rentals.index');
