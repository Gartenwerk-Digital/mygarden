<?php

use App\Http\Controllers\Areas\AreaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->scopeBindings()
    ->group(function (): void {
        Route::get('gardens/{garden}/areas/create', [AreaController::class, 'create'])->name('areas.create');
        Route::post('gardens/{garden}/areas', [AreaController::class, 'store'])->name('areas.store');
        Route::get('gardens/{garden}/areas/{area}', [AreaController::class, 'show'])->name('areas.show');
        Route::get('gardens/{garden}/areas/{area}/edit', [AreaController::class, 'edit'])->name('areas.edit');
        Route::patch('gardens/{garden}/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
        Route::patch('gardens/{garden}/areas/{area}/archive', [AreaController::class, 'archive'])->name('areas.archive');
        Route::patch('gardens/{garden}/areas/{area}/unarchive', [AreaController::class, 'unarchive'])->name('areas.unarchive');
        Route::delete('gardens/{garden}/areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');
    });
