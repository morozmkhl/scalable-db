<?php

use ScalableDB\Facades\Shard;

it('routes tenant by range', function () {
    /** @var string $routed */
    $routed = Shard::forTenant(150)->run(fn () => Shard::current());

    expect($routed)->toBe('B');
});

it('returns null for current shard outside run context', function () {
    expect(Shard::current())->toBeNull();
});

it('resolves shard via manager resolve', function () {
    $manager = app('shard.manager');

    expect($manager->resolve(50))->toBe('A')
        ->and($manager->resolve(150))->toBe('B');
});
