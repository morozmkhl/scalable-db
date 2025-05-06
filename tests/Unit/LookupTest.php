<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;
use ScalableDB\Facades\Shard;

beforeEach(function () {
    // in‑memory sqlite как lookup‑БД
    config()->set('database.connections.lookup', [
        'driver' => 'sqlite', 'database' => ':memory:',
    ]);

    // создаём таблицу tenants и данные
    Schema::connection('lookup')->create('tenants', function ($t) {
        $t->integer('id')->primary();
        $t->string('shard');
    });
    DB::connection('lookup')->table('tenants')->insert([
        ['id' => 99, 'shard' => 'S_LK'],
    ]);

    // подменяем конфиг scalable‑db
    config()->set('scalable-db', [
        'default_strategy' => 'lookup',
        'strategies' => [
            'lookup' => [
                'connection'   => 'lookup',
                'table'        => 'tenants',
                'key_column'   => 'id',
                'shard_column' => 'shard',
                'cache_ttl'    => 0,
            ],
        ],
        'shards' => [
            'S_LK' => ['connection' => 'sqlite', 'replicas' => []],
        ],
    ]);

    // пересоздаём ShardManager
    Facade::clearResolvedInstances();
    app()->forgetInstance('shard.manager');
});

it('resolves shard via lookup table', function () {
    $resolved = Shard::forTenant(99)->run(fn () => Shard::current());
    expect($resolved)->toBe('S_LK');
});

it('throws when tenant missing', function () {
    expect(fn () => Shard::forTenant(404)->run(fn () => true))
        ->toThrow(RuntimeException::class);
});
