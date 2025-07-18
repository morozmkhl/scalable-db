<?php
use Illuminate\Support\Facades\Route;
use ScalableDB\Facades\Shard;


beforeEach(function () {
    Route::middleware('shard.tenant')
        ->get('/ping', fn () => response()->json(['shard' => Shard::current()]));
});

it('assigns shard via header', function () {
    $resp = $this->get('/ping', ['X-Tenant-ID' => '150']);

    $resp->assertJson(['shard' => 'B']);
});