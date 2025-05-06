<?php
use Laravel\Telescope\IncomingEntry;
use ScalableDB\Telescope\ShardTagWatcher;
use ScalableDB\Facades\Shard;

it('returns shard tag when context set', function () {

    $entry = new IncomingEntry( ['sql' => 'select 1']);

    Shard::forTenant(5)->run(function () use ($entry) {
        $tags = ShardTagWatcher::tags($entry);
        expect($tags)->toContain('shard:A');
    });
});

it('returns empty array when no shard', function () {
    $entry = new IncomingEntry([]);
    expect(ShardTagWatcher::tags($entry))->toBe([]);
});
