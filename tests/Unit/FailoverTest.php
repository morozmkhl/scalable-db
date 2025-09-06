<?php

use Illuminate\Database\DatabaseManager;
use Mockery as m;
use Mockery\MockInterface;
use ScalableDB\Services\ShardManager;
use ScalableDB\Strategies\ShardingStrategyInterface;

/**
 * @param  array<string, mixed>  $cfg
 */
function makeFailoverManager(DatabaseManager $db, array $cfg): ShardManager
{
    $strategy = new class implements ShardingStrategyInterface
    {
        public function getShard($key): string
        {
            return 'S';
        }
    };

    return new ShardManager($db, $strategy, $cfg);
}

it('falls back to replica when master down', function () {

    /** @var DatabaseManager&MockInterface $db */
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

    $cfg = [
        'shards' => [
            'S' => [
                'connection' => 'bad_master',
                'replicas' => ['replica_ok'],
            ],
        ],
        'failover' => [
            'auto_failover' => true,
            'max_retries' => 1,
            'fallback_connection' => null,
        ],
    ];

    $mgr = makeFailoverManager($db, $cfg);

    $attempt = 0;
    $result = $mgr->runInShard('S', function () use (&$attempt) {
        $attempt++;
        if ($attempt === 1) {
            throw new PDOException('server gone');
        }

        return 'ok';
    });

    expect($result)->toBe('ok');
});

it('throws immediately when auto_failover is disabled', function () {

    /** @var DatabaseManager&MockInterface $db */
    $db = m::mock(DatabaseManager::class);

    $db->shouldReceive('getDefaultConnection')->andReturn('mysql');
    $db->shouldReceive('setDefaultConnection')->with('bad_master');
    $db->shouldReceive('setDefaultConnection')->with('mysql');
    $db->shouldNotReceive('purge');

    $cfg = [
        'shards' => [
            'S' => [
                'connection' => 'bad_master',
                'replicas' => ['replica_ok'],
            ],
        ],
        'failover' => [
            'auto_failover' => false,
            'max_retries' => 1,
        ],
    ];

    $mgr = makeFailoverManager($db, $cfg);

    $mgr->runInShard('S', function () {
        throw new PDOException('server gone');
    });
})->throws(PDOException::class, 'server gone');

it('tries multiple replicas up to max_retries', function () {

    /** @var DatabaseManager&MockInterface $db */
    $db = m::mock(DatabaseManager::class);

    $db->shouldReceive('getDefaultConnection')->andReturn('mysql');

    $db->shouldReceive('setDefaultConnection')->with('bad_master');
    $db->shouldReceive('purge')->with('bad_master');
    $db->shouldReceive('setDefaultConnection')->with('replica_down');
    $db->shouldReceive('purge')->with('bad_master');
    $db->shouldReceive('setDefaultConnection')->with('replica_ok');
    $db->shouldReceive('setDefaultConnection')->atLeast()->once()->with('mysql');

    $cfg = [
        'shards' => [
            'S' => [
                'connection' => 'bad_master',
                'replicas' => ['replica_down', 'replica_ok'],
            ],
        ],
        'failover' => [
            'auto_failover' => true,
            'max_retries' => 2,
        ],
    ];

    $mgr = makeFailoverManager($db, $cfg);

    $attempt = 0;
    $result = $mgr->runInShard('S', function () use (&$attempt) {
        $attempt++;
        if ($attempt < 3) {
            throw new PDOException("attempt $attempt failed");
        }

        return 'ok';
    });

    expect($result)->toBe('ok');
});

it('uses global fallback_connection when replicas are exhausted', function () {

    /** @var DatabaseManager&MockInterface $db */
    $db = m::mock(DatabaseManager::class);

    $db->shouldReceive('getDefaultConnection')->andReturn('mysql');

    $db->shouldReceive('setDefaultConnection')->with('bad_master');
    $db->shouldReceive('purge')->with('bad_master');
    $db->shouldReceive('setDefaultConnection')->with('replica_down');
    $db->shouldReceive('purge')->with('bad_master');
    $db->shouldReceive('setDefaultConnection')->with('global_fallback');
    $db->shouldReceive('setDefaultConnection')->atLeast()->once()->with('mysql');

    $cfg = [
        'shards' => [
            'S' => [
                'connection' => 'bad_master',
                'replicas' => ['replica_down'],
            ],
        ],
        'failover' => [
            'auto_failover' => true,
            'max_retries' => 1,
            'fallback_connection' => 'global_fallback',
        ],
    ];

    $mgr = makeFailoverManager($db, $cfg);

    $attempt = 0;
    $result = $mgr->runInShard('S', function () use (&$attempt) {
        $attempt++;
        if ($attempt < 3) {
            throw new PDOException("attempt $attempt failed");
        }

        return 'ok';
    });

    expect($result)->toBe('ok');
});
