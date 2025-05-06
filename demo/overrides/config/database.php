<?php

return [

    'default' => env('DB_CONNECTION', 'lookup'),

    'connections' => [

        'lookup' => [
            'driver'   => 'sqlite',
            'database' => database_path('lookup.sqlite'),
            'prefix'   => '',
        ],

        'shard0_master' => [
            'driver'   => 'mysql',
            'host'     => env('SHARD0_HOST', 'shard0'),
            'database' => env('SHARD0_DB', 'shard0'),
            'username' => env('SHARD0_USER', 'root'),
            'password' => env('SHARD0_PASS', 'root'),
            'sticky'   => true,
        ],

        'shard1_master' => [
            'driver'   => 'mysql',
            'host'     => env('SHARD1_HOST', 'shard1'),
            'database' => env('SHARD1_DB', 'shard1'),
            'username' => env('SHARD1_USER', 'root'),
            'password' => env('SHARD1_PASS', 'root'),
            'sticky'   => true,
        ],

        'replica1' => [
            'driver'   => 'mysql',
            'host'     => env('REPLICA1_HOST', 'replica1'),
            'database' => env('REPLICA1_DB', 'replica1'),
            'username' => env('REPLICA1_USER', 'root'),
            'password' => env('REPLICA1_PASS', 'root'),
        ],
    ],

    'migrations' => 'migrations',
];