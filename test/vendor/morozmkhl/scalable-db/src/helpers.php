<?php

use ScalableDB\Facades\Shard;

if (! function_exists('shard_for')) {
    function shard_for(int|string $tenant, Closure $cb): mixed
    {
        return Shard::forTenant($tenant)->run($cb);
    }
}
