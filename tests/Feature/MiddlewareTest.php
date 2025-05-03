<?php
use Illuminate\Support\Facades\Route;
use ScalableDB\Tests\TestCase;
use ScalableDB\Facades\Shard;


beforeEach(function () {
    // Регистрируем маршрут "echo‑шард"
    Route::middleware('shard.tenant')
        ->get('/ping', fn () => response()->json(['shard' => Shard::current()]));
});

it('assigns shard via header', function () {
    // Tenant‑ID = 150 (из Range‑стратегии → shard "B")
    $resp = $this->get('/ping', ['X-Tenant-ID' => '150']);

    $resp->assertJson(['shard' => 'B']);
});