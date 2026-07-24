<?php

use App\Http\Controllers\GardenPlantings\PlantingEntryController;
use App\Http\Controllers\GardenPlantings\PlantingEntryImageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('gardens/{garden}/plantings/{planting}/entries/create', [PlantingEntryController::class, 'create'])->name('planting-entries.create');
        Route::post('gardens/{garden}/plantings/{planting}/entries', [PlantingEntryController::class, 'store'])->name('planting-entries.store');
        Route::get('gardens/{garden}/plantings/{planting}/entries/{entry}/edit', [PlantingEntryController::class, 'edit'])->name('planting-entries.edit');
        Route::patch('gardens/{garden}/plantings/{planting}/entries/{entry}', [PlantingEntryController::class, 'update'])->name('planting-entries.update');
        Route::delete('gardens/{garden}/plantings/{planting}/entries/{entry}', [PlantingEntryController::class, 'destroy'])->name('planting-entries.destroy');

        Route::get('gardens/{garden}/plantings/{planting}/entries/{entry}/images/{image}', [PlantingEntryImageController::class, 'show'])->name('planting-entry-images.show');
    });
