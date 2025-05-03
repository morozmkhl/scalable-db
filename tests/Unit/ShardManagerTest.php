<?php
use ScalableDB\Facades\Shard;

it('routes tenant by range', function () {
    $routed = Shard::forTenant(150)->run(fn () => Shard::current());

    expect($routed)->toBe('B');
});