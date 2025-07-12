<?php

if (! function_exists('shard_for')) {
    function shard_for(int|string $tenant, \Closure $cb): mixed
    {
        return ScalableDB\Facades\Shard::forTenant($tenant)->run($cb);
    }
}