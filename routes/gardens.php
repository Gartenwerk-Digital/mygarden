<?php

use App\Http\Controllers\Gardens\GardenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('gardens', [GardenController::class, 'index'])->name('gardens.index');
    Route::get('gardens/create', [GardenController::class, 'create'])->name('gardens.create');
    Route::post('gardens', [GardenController::class, 'store'])->name('gardens.store');
    Route::get('gardens/{garden}', [GardenController::class, 'show'])->name('gardens.show');
    Route::get('gardens/{garden}/edit', [GardenController::class, 'edit'])->name('gardens.edit');
    Route::patch('gardens/{garden}', [GardenController::class, 'update'])->name('gardens.update');
    Route::patch('gardens/{garden}/archive', [GardenController::class, 'archive'])->name('gardens.archive');
    Route::patch('gardens/{garden}/unarchive', [GardenController::class, 'unarchive'])->name('gardens.unarchive');
    Route::delete('gardens/{garden}', [GardenController::class, 'destroy'])->name('gardens.destroy');
});
