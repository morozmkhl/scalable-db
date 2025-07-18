<?php
use Illuminate\Support\Facades\DB;
use ScalableDB\Facades\Shard;

it('sticks to master after write', function () {

    config()->set('database.connections.master', [
        'driver' => 'sqlite', 'database' => ':memory:', 'sticky' => true,
        'read'  => ['host' => ':memory:'],
        'write' => ['host' => ':memory:'],
    ]);

    config()->set('scalable-db.shards.S0', [
        'connection' => 'master', 'replicas' => [],
    ]);
    config()->set('scalable-db.default_strategy', 'hash');
    config()->set('scalable-db.strategies.hash.map', [0 => 'S0']);
    config()->set('scalable-db.strategies.hash.shard_count', 1);

    Shard::forTenant(5)->run(function () {
        DB::statement('CREATE TABLE foo(id INTEGER PRIMARY KEY, bar TEXT)');
        DB::table('foo')->insert(['bar' => 'baz']);
        $val = \DB::table('foo')->where('id', 1)->value('bar');
        expect($val)->toBe('baz');
    });
});