<?php

use Illuminate\Support\Facades\Route;
use ScalableDB\Facades\Shard;

use function Pest\Laravel\get;

beforeEach(function () {
    Route::middleware('shard.tenant')
        ->get('/ping', fn () => response()->json(['shard' => Shard::current()]));
});

it('assigns shard via header', function () {
    get('/ping', ['X-Tenant-ID' => '150'])
        ->assertJson(['shard' => 'B']);
});
