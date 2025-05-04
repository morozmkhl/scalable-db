<?php
namespace ScalableDB\Services;

use Closure;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Event;
use ScalableDB\Events\ShardFailover;
use ScalableDB\Events\ShardResolved;
use ScalableDB\Strategies\ShardingStrategyInterface;

class ShardManager
{
    private ?string $currentShard = null;

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
        } catch (\PDOException $e) {

            /** ▸ FAIL‑OVER логика */
            $replicas = $this->config['shards'][$shard]['replicas'] ?? [];
            if ($replicas !== []) {
                // пробуем первую реплику (read‑only) вместо мастера
                $fallback = $replicas[0];
                $this->db->purge($primary);                // сбросить плохое PDO

                Event::dispatch(new ShardFailover(
                    $shard,
                    $primary,
                    $fallback,
                    $e
                ));

                $this->db->setDefaultConnection($fallback);
                // ⚠ возможна запись ‑ бросаем исключение при попытке write

                try {
                    return $callback();
                } finally {
                    $this->db->setDefaultConnection($prev);
                    $this->currentShard = null;
                }
            }

            throw $e;
        } finally {
            // нормальное завершение
            $this->db->setDefaultConnection($prev);
            $this->currentShard = null;
        }

        return null;
    }

    public function getCurrentShard(): ?string
    {
        return $this->currentShard;
    }
}