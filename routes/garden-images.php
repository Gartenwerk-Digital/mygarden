<?php

use App\Http\Controllers\Gardens\GardenImageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->scopeBindings()
    ->group(function (): void {
        Route::post('gardens/{garden}/images', [GardenImageController::class, 'store'])->name('gardens.images.store');
        // Cover-Routen zuerst, damit /cover nicht als {image}-Parameter interpretiert wird.
        Route::delete('gardens/{garden}/images/cover', [GardenImageController::class, 'unsetCover'])->name('gardens.images.cover.unset');
        Route::put('gardens/{garden}/images/{image}/cover', [GardenImageController::class, 'setCover'])->name('gardens.images.cover.set');
        Route::get('gardens/{garden}/images/{image}', [GardenImageController::class, 'show'])->name('gardens.images.show');
        Route::delete('gardens/{garden}/images/{image}', [GardenImageController::class, 'destroy'])->name('gardens.images.destroy');
    });
