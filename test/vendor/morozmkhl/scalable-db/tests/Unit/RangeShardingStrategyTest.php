<?php

use ScalableDB\Strategies\RangeShardingStrategy;

it('routes key within configured ranges', function () {
    $strategy = new RangeShardingStrategy([
        ['min' => 1, 'max' => 100, 'shard' => 'A'],
        ['min' => 101, 'max' => 200, 'shard' => 'B'],
    ]);

    expect($strategy->getShard(50))->toBe('A')
        ->and($strategy->getShard(150))->toBe('B');
});

it('treats missing max as open ended range', function () {
    $strategy = new RangeShardingStrategy([
        ['min' => 1000, 'max' => null, 'shard' => 'Z'],
    ]);

    expect($strategy->getShard(1000))->toBe('Z')
        ->and($strategy->getShard(PHP_INT_MAX - 1))->toBe('Z');
});

it('throws when key is outside all ranges', function () {
    $strategy = new RangeShardingStrategy([
        ['min' => 1, 'max' => 10, 'shard' => 'A'],
    ]);

    expect(fn () => $strategy->getShard(99))
        ->toThrow(RuntimeException::class, 'Range strategy: key [99] not in any range');
});
