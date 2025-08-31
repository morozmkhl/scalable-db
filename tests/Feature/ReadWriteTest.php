<?php

use Illuminate\Support\Facades\DB;
use ScalableDB\Facades\Shard;

function setupReadWriteShard(): void
{
    config()->set('database.connections.master', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'sticky' => true,
        'read' => ['database' => ':memory:'],
        'write' => ['database' => ':memory:'],
    ]);

    config()->set('scalable-db.shards.S0', [
        'connection' => 'master',
        'replicas' => [],
    ]);
    config()->set('scalable-db.default_strategy', 'hash');
    config()->set('scalable-db.strategies.hash.map', [0 => 'S0']);
    config()->set('scalable-db.strategies.hash.shard_count', 1);
}

it('reads via forRead helper', function () {
    setupReadWriteShard();

    Shard::forTenant(5)->forWrite()->run(function () {
        DB::statement('CREATE TABLE foo(id INTEGER PRIMARY KEY, bar TEXT)');
        DB::table('foo')->insert(['bar' => 'baz']);
    });

    Shard::forTenant(5)->forRead()->run(function () {
        $val = DB::table('foo')->where('id', 1)->value('bar');
        expect($val)->toBe('baz');
    });
});

it('writes via forWrite helper and sticks to master for subsequent reads', function () {
    setupReadWriteShard();

    Shard::forTenant(5)->forWrite()->run(function () {
        DB::statement('CREATE TABLE foo(id INTEGER PRIMARY KEY, bar TEXT)');
        DB::table('foo')->insert(['bar' => 'baz']);
    });

    Shard::forTenant(5)->forRead()->run(function () {
        $val = DB::table('foo')->where('id', 1)->value('bar');
        expect($val)->toBe('baz');
    });
});
