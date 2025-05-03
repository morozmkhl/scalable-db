<?php
namespace ScalableDB\Strategies;

class HashShardingStrategy implements ShardingStrategyInterface
{
    public function __construct(
        private readonly array $map,
        private readonly int   $count
    ) {}

    public function getShard($key): string
    {
        $hash = crc32((string) $key);
        $slot = $hash % $this->count;

        return $this->map[$slot] ?? throw new \RuntimeException(
            "No shard mapped for slot [$slot]"
        );
    }
}