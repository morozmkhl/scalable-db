<?php
use Illuminate\Database\DatabaseManager;
use Mockery as m;
use ScalableDB\Services\ShardManager;
use ScalableDB\Strategies\ShardingStrategyInterface;

it('falls back to replica when master down', function () {

    $db = m::mock(DatabaseManager::class);

    $db->shouldReceive('getDefaultConnection')->andReturn('mysql');

    $db->shouldReceive('setDefaultConnection')
        ->once()->with('bad_master');

    $db->shouldReceive('purge')
        ->once()->with('bad_master');

    $db->shouldReceive('setDefaultConnection')
        ->once()->with('replica_ok');

    $db->shouldReceive('setDefaultConnection')
        ->atLeast()->once()->with('mysql');

    $strategy = new class implements ShardingStrategyInterface {
        public function getShard($key): string { return 'S'; }
    };

    $cfg = [
        'shards' => [
            'S' => [
                'connection' => 'bad_master',
                'replicas'   => ['replica_ok'],
            ],
        ],
    ];

    $mgr = new ShardManager($db, $strategy, $cfg);

    $attempt = 0;
    $result  = $mgr->runInShard('S', function () use (&$attempt) {
        $attempt++;
        if ($attempt === 1) {
            throw new PDOException('server gone');
        }
        return 'ok';
    });

    expect($result)->toBe('ok');
});