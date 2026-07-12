<?php

it('shows OK for healthy shard', function () {

    config()->set('scalable-db.shards', [
        'S0' => ['connection' => 'sqlite', 'replicas' => []],
    ]);

    [$code, $out] = runCmd('shard:status');

    expect($code)->toBe(0)                 // команда успешно выполнилась
        ->and($out)->not->toBe('');
});
