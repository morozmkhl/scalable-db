<?php

namespace ScalableDB\Events;

class ShardResolved
{
    public function __construct(
        public readonly string|int $tenantKey,
        public readonly string     $shard,
        public readonly string     $strategy,   // hash / range / lookup
    ) {}
}
