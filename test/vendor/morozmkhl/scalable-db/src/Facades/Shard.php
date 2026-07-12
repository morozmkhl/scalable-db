<?php

namespace ScalableDB\Facades;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use ScalableDB\Services\ShardManager;

class Shard extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'shard.manager';
    }

    /* ---------- fluent helper ---------- */

    public static function forTenant(int|string $tenantId): ShardContext
    {
        /** @var ShardManager $manager */
        $manager = static::getFacadeRoot();
        $shard = $manager->resolve($tenantId);

        return new ShardContext($manager, $shard);
    }

    public static function current(): ?string
    {
        /** @var ShardManager $manager */
        $manager = static::getFacadeRoot();

        return $manager->getCurrentShard();
    }
}

class ShardContext
{
    public function __construct(
        private readonly ShardManager $mgr,
        private readonly string $shard,
        private readonly string $pdoMode = 'default',
    ) {}

    public function forRead(): self
    {
        return new self($this->mgr, $this->shard, 'read');
    }

    public function forWrite(): self
    {
        return new self($this->mgr, $this->shard, 'write');
    }

    public function run(\Closure $cb): mixed
    {
        return $this->mgr->runInShard($this->shard, function () use ($cb) {
            $connection = DB::connection();

            if ($this->pdoMode === 'read') {
                $connection->setPdo($connection->getReadPdo());
                $connection->setReadWriteType('read');
            } elseif ($this->pdoMode === 'write') {
                $connection->setReadPdo($connection->getPdo());
                $connection->setReadWriteType('write');
            }

            return $cb();
        });
    }
}
