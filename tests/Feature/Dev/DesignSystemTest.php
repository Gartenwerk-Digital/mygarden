<?php

use Illuminate\Support\Facades\Route;

it('is accessible in local environment', function () {
    $this->get('/dev/design-system')->assertOk();
})->skip(fn () => ! app()->environment('local'), 'Route only registered in local environment');

it('route is not registered outside local environment', function () {
    expect(Route::has('dev.design-system'))->toBeFalse();
})->skip(fn () => app()->environment('local'), 'Only relevant in non-local environments');
