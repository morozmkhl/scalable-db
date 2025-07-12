<?php
namespace ScalableDB\Services;

use Illuminate\Database\DatabaseManager;
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
        return $this->strategy->getShard($key);
    }

    /**
     * Выполняет callback в контексте шарда
     */
    public function runInShard(string $shard, \Closure $callback): mixed
    {
        $prev = $this->db->getDefaultConnection();
        $this->db->setDefaultConnection(
            $this->config['shards'][$shard]['connection'] ?? $prev
        );
        $this->currentShard = $shard;

        try {
            return $callback();
        } finally {
            // возвращаемся к предыдущему соединению
            $this->db->setDefaultConnection($prev);
            $this->currentShard = null;
        }
    }

    public function getCurrentShard(): ?string
    {
        return $this->currentShard;
    }
}