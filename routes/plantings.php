<?php

use App\Http\Controllers\GardenPlantings\GardenPlantingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('gardens/{garden}/plantings/create', [GardenPlantingController::class, 'create'])->name('plantings.create');
        Route::post('gardens/{garden}/plantings', [GardenPlantingController::class, 'store'])->name('plantings.store');
        Route::get('gardens/{garden}/plantings/{planting}', [GardenPlantingController::class, 'show'])->name('plantings.show');
        Route::get('gardens/{garden}/plantings/{planting}/edit', [GardenPlantingController::class, 'edit'])->name('plantings.edit');
        Route::patch('gardens/{garden}/plantings/{planting}', [GardenPlantingController::class, 'update'])->name('plantings.update');
        Route::delete('gardens/{garden}/plantings/{planting}', [GardenPlantingController::class, 'destroy'])->name('plantings.destroy');
    });
