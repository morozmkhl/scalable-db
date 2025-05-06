<?php
namespace ScalableDB\Strategies;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LookupShardingStrategy implements ShardingStrategyInterface
{
    public function __construct(
        private readonly array $cfg,
    ) {}

    public function getShard($key): string
    {
        $ttl   = $this->cfg['cache_ttl'] ?? 0;
        $cacheKey = 'scalabledb_lookup_'.$key;

        return $ttl
            ? Cache::remember($cacheKey, $ttl, fn () => $this->lookup($key))
            : $this->lookup($key);
    }

    private function lookup(string|int $key): string
    {
        $row = DB::connection($this->cfg['connection'])
            ->table($this->cfg['table'])
            ->where($this->cfg['key_column'], $key)
            ->value($this->cfg['shard_column']);

        if (!$row) {
            throw new RuntimeException("Tenant [$key] not found in lookup table");
        }

        return $row;
    }
}
