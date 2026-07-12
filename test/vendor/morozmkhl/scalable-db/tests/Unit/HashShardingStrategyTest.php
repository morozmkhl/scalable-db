<?php

use ScalableDB\Strategies\HashShardingStrategy;

it('routes key by crc32 modulo shard count', function () {
    $map = [0 => 'shard_a', 1 => 'shard_b'];
    $strategy = new HashShardingStrategy($map, 2);

    $key = 42;
    $slot = crc32((string) $key) % 2;

    expect($strategy->getShard($key))->toBe($map[$slot]);
});

it('accepts string keys', function () {
    $map = [0 => 'shard_a', 1 => 'shard_b'];
    $strategy = new HashShardingStrategy($map, 2);

    $slot = crc32('tenant-99') % 2;

    expect($strategy->getShard('tenant-99'))->toBe($map[$slot]);
});

it('throws when slot is missing from map', function () {
    $strategy = new HashShardingStrategy([0 => 'shard_a'], 2);

    $key = 1;
    while (crc32((string) $key) % 2 !== 1) {
        $key++;
    }

    expect(fn () => $strategy->getShard($key))
        ->toThrow(RuntimeException::class, 'No shard mapped for slot [1]');
});
