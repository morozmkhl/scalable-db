<?php
namespace ScalableDB\Facades;

use Illuminate\Support\Facades\Facade;

class Shard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shard.manager';
    }

    /* ---------- fluent helper ---------- */

    public static function forTenant(int|string $tenantId): object
    {
        /** @var \ScalableDB\Services\ShardManager $manager */
        $manager = static::getFacadeRoot();
        $shard   = $manager->resolve($tenantId);

        return new class ($manager, $shard) {
            public function __construct(
                private readonly \ScalableDB\Services\ShardManager $mgr,
                private readonly string $shard
            ) {}

            public function run(\Closure $cb): mixed
            {
                return $this->mgr->runInShard($this->shard, $cb);
            }
        };
    }

    public static function current(): ?string
    {
        /** @var \ScalableDB\Services\ShardManager $manager */
        $manager = static::getFacadeRoot();

        return $manager?->getCurrentShard();
    }
}