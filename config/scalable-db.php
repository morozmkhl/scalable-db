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
                // 1‑10 000 → shard_0, 10 001‑20 000 → shard_1
                ['min' => 1,     'max' => 10000,  'shard' => 'shard_0'],
                ['min' => 10001, 'max' => 20000,  'shard' => 'shard_1'],
            ],
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
            'replicas'   => [],
        ],

        'shard_1' => [
            'connection' => 'shard1_master',
            'replicas'   => [],
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