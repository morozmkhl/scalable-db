<?php
namespace ScalableDB\Strategies;

interface ShardingStrategyInterface
{
    /**
     * @param string|int $key   — любой идентификатор (user id, tenant id …)
     * @return string           — имя шарда (напр. "shard_0")
     */
    public function getShard($key): string;
}