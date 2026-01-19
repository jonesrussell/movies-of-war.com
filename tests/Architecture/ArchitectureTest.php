<?php

declare(strict_types=1);

uses()->group('arch')->in('Architecture');

// Services should not depend on HTTP layer
arch()
    ->expect('App\Services')
    ->not->toUse('App\Http')
    ->not->toUse('Illuminate\Http')
    ->not->toUse('Illuminate\Routing');

// DTOs should be readonly and do not depend on Eloquent
arch()
    ->expect('App\Data')
    ->not->toUse('Illuminate\Database\Eloquent')
    ->not->toUse('App\Models');
