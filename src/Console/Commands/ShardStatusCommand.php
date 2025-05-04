<?php
namespace ScalableDB\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShardStatusCommand extends Command
{
    protected $signature = 'shard:status';
    protected $description = 'Show health of all shards';

    public function handle()
    {
        $rows = [];

        foreach (config('scalable-db.shards') as $name => $def) {
            $rows[] = $this->probe($name, $def['connection'], 'master');

            foreach ($def['replicas'] ?? [] as $rep) {
                $rows[] = $this->probe($name, $rep, 'replica');
            }
        }

        $this->table(['Shard', 'Connection', 'Role', 'Status', 'ms'], $rows);
    }

    private function probe(string $shard, string $conn, string $role): array
    {
        $t0 = microtime(true);
        try {
            DB::connection($conn)->select('select 1');
            $ok = 'OK';
        } catch (\Throwable $e) {
            $ok = 'ERROR';
        }
        $ms = (int) ((microtime(true) - $t0) * 1_000);

        return [$shard, $conn, $role, $ok, $ms];
    }
}