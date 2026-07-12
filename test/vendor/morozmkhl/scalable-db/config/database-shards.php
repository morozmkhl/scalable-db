<?php

/**
 * Publishable stub: merge into config/database.php or require from there.
 *
 * Read/write splitting is a Laravel feature — ScalableDB only switches
 * the default connection to the shard master; Laravel routes queries
 * to read/write hosts inside that connection.
 */
return [

    'connections' => [

        'shard0_master' => [
            'driver' => 'mysql',
            'database' => env('SHARD0_DB', 'shard0'),
            'username' => env('SHARD0_USER', 'root'),
            'password' => env('SHARD0_PASS', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'sticky' => true,
            'read' => [
                'host' => [
                    env('SHARD0_REPLICA_HOST', 'replica1.example.com'),
                ],
            ],
            'write' => [
                'host' => [
                    env('SHARD0_HOST', 'master0.example.com'),
                ],
            ],
        ],

        'shard0_replica1' => [
            'driver' => 'mysql',
            'host' => env('SHARD0_REPLICA_HOST', 'replica1.example.com'),
            'database' => env('SHARD0_DB', 'shard0'),
            'username' => env('SHARD0_USER', 'root'),
            'password' => env('SHARD0_PASS', ''),
            'read_only' => true,
        ],

        'shard1_master' => [
            'driver' => 'mysql',
            'database' => env('SHARD1_DB', 'shard1'),
            'username' => env('SHARD1_USER', 'root'),
            'password' => env('SHARD1_PASS', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'sticky' => true,
            'read' => [
                'host' => [
                    env('SHARD1_REPLICA_HOST', 'replica2.example.com'),
                ],
            ],
            'write' => [
                'host' => [
                    env('SHARD1_HOST', 'master1.example.com'),
                ],
            ],
        ],

    ],

];
