<?php
namespace ScalableDB\Strategies;

class RangeShardingStrategy implements ShardingStrategyInterface
{
    /**
     * @param array<int, array{min:int, max:int|null, shard:string}> $ranges
     */
    public function __construct(private readonly array $ranges) {}

    public function getShard($key): string
    {
        $key = (int) $key;

        foreach ($this->ranges as $r) {
            $min = $r['min'];
            $max = $r['max'] ?? PHP_INT_MAX;

            if ($key >= $min && $key <= $max) {
                return $r['shard'];
            }
        }

        throw new \RuntimeException("Range strategy: key [$key] not in any range");
    }
}