<?php

namespace ScalableDB\Services;

use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Event;
use PDOException;
use ScalableDB\Events\ShardFailover;
use ScalableDB\Events\ShardResolved;
use ScalableDB\Strategies\ShardingStrategyInterface;

class ShardManager
{
    private ?string $currentShard = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly ShardingStrategyInterface $strategy,
        private readonly array $config     // содержимое scalable-db.php
    ) {}

    /**
     * Определяем шард по tenant/user‑id
     */
    public function resolve(string|int $key): string
    {
        $shard = $this->strategy->getShard($key);

        Event::dispatch(new ShardResolved($key, $shard, class_basename($this->strategy)));

        return $shard;
    }

    /**
     * Выполняет callback в контексте шарда
     */
    public function runInShard(string $shard, Closure $callback): mixed
    {
        $prev = $this->db->getDefaultConnection();

        // 👇 берём имя «главного» подключения
        $primary = $this->config['shards'][$shard]['connection'] ?? $prev;
        $this->db->setDefaultConnection($primary);
        $this->currentShard = $shard;

        try {
            return $callback();
        } catch (PDOException $e) {
            return $this->attemptFailover($shard, $primary, $callback, $e);
        } finally {
            $this->db->setDefaultConnection($prev);
            $this->currentShard = null;
        }
    }

    public function getCurrentShard(): ?string
    {
        return $this->currentShard;
    }

    /**
     * @throws PDOException
     */
    private function attemptFailover(string $shard, string $primary, Closure $callback, PDOException $e): mixed
    {
        if (! ($this->config['failover']['auto_failover'] ?? false)) {
            throw $e;
        }

        $replicas = $this->config['shards'][$shard]['replicas'] ?? [];
        $maxRetries = (int) ($this->config['failover']['max_retries'] ?? 1);
        $lastException = $e;

        foreach (array_slice($replicas, 0, $maxRetries) as $fallback) {
            try {
                return $this->tryConnection($shard, $primary, $fallback, $callback, $e);
            } catch (PDOException $replicaException) {
                $lastException = $replicaException;
            }
        }

        $globalFallback = $this->config['failover']['fallback_connection'] ?? null;
        if (is_string($globalFallback) && $globalFallback !== '') {
            return $this->tryConnection($shard, $primary, $globalFallback, $callback, $e);
        }

        throw $lastException;
    }

    /**
     * @throws PDOException
     */
    private function tryConnection(
        string $shard,
        string $from,
        string $to,
        Closure $callback,
        PDOException $cause
    ): mixed {
        $this->db->purge($from);

        Event::dispatch(new ShardFailover(
            $shard,
            $from,
            $to,
            $cause
        ));

        $this->db->setDefaultConnection($to);

        return $callback();
    }
}
