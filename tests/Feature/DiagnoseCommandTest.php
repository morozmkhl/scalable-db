<?php
use Illuminate\Support\Str;

it('returns failure when shard down', function () {

    config()->set('scalable-db.shards', [
        'BAD' => ['connection' => 'not_exists', 'replicas' => []],
    ]);

    [$code, $out] = runCmd('shard:diagnose', ['--json' => true]);

    expect($code)->toBe(1)
    ->and(Str::contains($out, 'BAD'))
    ->toBeTrue();
});