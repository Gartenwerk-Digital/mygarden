<?php

use App\Http\Controllers\Plants\PlantSpeciesController;
use App\Http\Controllers\Plants\PlantVarietyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('plants', [PlantSpeciesController::class, 'index'])->name('plants.index');
    Route::get('plants/create', [PlantSpeciesController::class, 'create'])->name('plants.create');
    Route::post('plants', [PlantSpeciesController::class, 'store'])->name('plants.store');
    Route::get('plants/{plantSpecies}', [PlantSpeciesController::class, 'show'])->name('plants.show');
    Route::get('plants/{plantSpecies}/edit', [PlantSpeciesController::class, 'edit'])->name('plants.edit');
    Route::patch('plants/{plantSpecies}', [PlantSpeciesController::class, 'update'])->name('plants.update');
    Route::patch('plants/{plantSpecies}/archive', [PlantSpeciesController::class, 'archive'])->name('plants.archive');
    Route::patch('plants/{plantSpecies}/unarchive', [PlantSpeciesController::class, 'unarchive'])->name('plants.unarchive');
    Route::delete('plants/{plantSpecies}', [PlantSpeciesController::class, 'destroy'])->name('plants.destroy');

    Route::scopeBindings()->group(function (): void {
        Route::get('plants/{plantSpecies}/varieties/create', [PlantVarietyController::class, 'create'])->name('plants.varieties.create');
        Route::post('plants/{plantSpecies}/varieties', [PlantVarietyController::class, 'store'])->name('plants.varieties.store');
        Route::get('plants/{plantSpecies}/varieties/{variety}/edit', [PlantVarietyController::class, 'edit'])->name('plants.varieties.edit');
        Route::patch('plants/{plantSpecies}/varieties/{variety}', [PlantVarietyController::class, 'update'])->name('plants.varieties.update');
        Route::patch('plants/{plantSpecies}/varieties/{variety}/archive', [PlantVarietyController::class, 'archive'])->name('plants.varieties.archive');
        Route::patch('plants/{plantSpecies}/varieties/{variety}/unarchive', [PlantVarietyController::class, 'unarchive'])->name('plants.varieties.unarchive');
        Route::delete('plants/{plantSpecies}/varieties/{variety}', [PlantVarietyController::class, 'destroy'])->name('plants.varieties.destroy');
    });
});
