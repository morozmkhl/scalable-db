<?php
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Facade;
use ScalableDB\Facades\Shard;
use ScalableDB\Events\ShardResolved;
use ScalableDB\Events\ShardFailover;

it('fires ShardResolved', function () {
    Event::fake();

    Shard::forTenant(5)->run(fn () => true);

    Event::assertDispatched(ShardResolved::class, function (ShardResolved $e) {
        return $e->tenantKey === 5 && $e->shard === 'A';
    });
});

it('fires ShardFailover', function () {

    config()->set('database.connections.bad_master', [
        'driver' => 'sqlite', 'database' => ':memory:'
    ]);
    config()->set('database.connections.replica_ok', [
        'driver' => 'sqlite', 'database' => ':memory:'
    ]);

    config()->set('scalable-db', [
        'default_strategy' => 'hash',

        'strategies' => [
            'hash' => [
                'shard_count' => 1,
                'map' => [0 => 'S0'],
            ],
        ],

        'shards' => [
            'S0' => [
                'connection' => 'bad_master',
                'replicas'   => ['replica_ok'],
            ],
        ],
    ]);

    Facade::clearResolvedInstances();
    app()->forgetInstance('shard.manager');

    Event::fake([ShardFailover::class]);

    $attempt = 0;
    Shard::forTenant(1)->run(function () use (&$attempt) {
        $attempt++;
        if ($attempt === 1) {
            throw new PDOException('simulate master down');
        }
        return true;
    });

    Event::assertDispatched(ShardFailover::class, function ($e) {
        return $e->fromConnection === 'bad_master'
            && $e->toConnection === 'replica_ok';
    });
});