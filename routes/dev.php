<?php

use Illuminate\Support\Facades\Route;

Route::get('/dev/design-system', fn () => inertia('dev/design-system'))
    ->name('dev.design-system');
