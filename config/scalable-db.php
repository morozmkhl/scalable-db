<?php

// publishes to config/scalable-db.php
return [

    /*
    |--------------------------------------------------------------------------
    | Default sharding strategy
    |--------------------------------------------------------------------------
    |
    | Supported out‑of‑the‑box: "hash"  (range/tenant‑map появятся позже).
    |
    */
    'default_strategy' => env('SCALABLE_DB_STRATEGY', 'hash'),

    /*
    |--------------------------------------------------------------------------
    | Strategy settings
    |--------------------------------------------------------------------------
    */
    'strategies' => [

        'hash' => [
            'shard_count' => 2, // change to real number
            'map' => [
                0 => 'shard_0',
                1 => 'shard_1',
            ],
        ],

        'range' => [
            'ranges' => [
                // 1‑10000 → shard_0, 10001‑20000 → shard_1
                ['min' => 1,     'max' => 10000,  'shard' => 'shard_0'],
                ['min' => 10001, 'max' => 20000,  'shard' => 'shard_1'],
            ],
        ],

        'lookup' => [
            // имя подключения, где хранится таблица tenants
            'connection' => env('SCALABLE_DB_LOOKUP_CONN', 'lookup'),
            // имя таблицы
            'table'      => 'tenants',
            // поле, содержащее tenant‑ID
            'key_column' => 'id',
            // поле, где хранится шард
            'shard_column' => 'shard',
            // (опционально) время кэширования результата
            'cache_ttl'  => 300,   // секунд; 0 = без кэша
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shard definitions
    |--------------------------------------------------------------------------
    */
    'shards' => [

        'shard_0' => [
            'connection' => 'shard0_master',
            'replicas'   => ['shard0_replica1'],
        ],

        'shard_1' => [
            'connection' => 'shard1_master',
            'replicas'   => ['shard1_replica1'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fail‑over behaviour
    |--------------------------------------------------------------------------
    */
    'failover' => [
        'auto_failover'      => false,
        'max_retries'        => 1,
        'fallback_connection'=> null,
    ],
];