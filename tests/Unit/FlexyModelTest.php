<?php

use AuroraWebSoftware\FlexyField\Models\Flexy;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('migrate:fresh');
});

it('can instantiate Flexy model', function () {
    $flexy = new Flexy;

    expect($flexy)->toBeInstanceOf(Flexy::class);
});

it('has guarded empty array', function () {
    $flexy = new Flexy;
    $flexy->fill(['test' => 'value']);

    expect($flexy->test)->toBe('value');
});
