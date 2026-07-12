<?php

use Illuminate\Support\Facades\Facade;
use ScalableDB\Facades\Shard;

beforeEach(function () {
    config()->set('scalable-db.default_strategy', 'hash');
    config()->set('scalable-db.strategies.hash.shard_count', 2);
    config()->set('scalable-db.strategies.hash.map', [
        0 => 'shard_0',
        1 => 'shard_1',
    ]);
    config()->set('scalable-db.shards', [
        'shard_0' => ['connection' => 'sqlite', 'replicas' => []],
        'shard_1' => ['connection' => 'sqlite', 'replicas' => []],
    ]);

    Facade::clearResolvedInstances();
    app()->forgetInstance('shard.manager');
});

it('routes tenant 42 to correct hash shard', function () {
    $map = [0 => 'shard_0', 1 => 'shard_1'];
    $expected = $map[crc32('42') % 2];

    /** @var string $shard */
    $shard = Shard::forTenant(42)->run(fn () => Shard::current());

    expect($shard)->toBe($expected);
});

it('routes different tenants to different shards when slots differ', function () {
    $map = [0 => 'shard_0', 1 => 'shard_1'];

    $tenantA = 1;
    while (crc32((string) $tenantA) % 2 !== 0) {
        $tenantA++;
    }

    $tenantB = 1;
    while (crc32((string) $tenantB) % 2 !== 1) {
        $tenantB++;
    }

    $shardA = Shard::forTenant($tenantA)->run(fn () => Shard::current());
    $shardB = Shard::forTenant($tenantB)->run(fn () => Shard::current());

    expect($shardA)->toBe($map[0])
        ->and($shardB)->toBe($map[1])
        ->and($shardA)->not->toBe($shardB);
});
