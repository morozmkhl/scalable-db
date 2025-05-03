<?php

return [
    'connections' => [

        'shard0_master' => [
            'driver'    => 'mysql',  'host' => env('DB_S0M_HOST'),
            /* … */ 'sticky' => true,               // ⚑ критично
            'read'  => [ 'host' => [env('DB_S0M_HOST')] ],
            'write' => [ 'host' => [env('DB_S0M_HOST')] ],
        ],

        'shard0_replica1' => [ 'driver' => 'mysql', 'host' => env('DB_S0R1_HOST') ],
        'shard0_replica2' => [ 'driver' => 'mysql', 'host' => env('DB_S0R2_HOST') ],
    ],
];