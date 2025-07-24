<?php
namespace ScalableDB\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShardDiagnoseCommand extends Command
{
    protected $signature   = 'shard:diagnose {--json}';
    protected $description = 'Check connections and return non‑zero on failure';

    public function handle()
    {
        $failed = [];
        $report = [];

        foreach (config('scalable-db.shards') as $name => $def) {
            foreach ([$def['connection'], ...($def['replicas'] ?? [])] as $conn) {
                try {
                    DB::connection($conn)->select('select 1');
                    $report[] = [$name, $conn, 'OK'];
                } catch (\Throwable $e) {
                    $report[] = [$name, $conn, $e->getMessage()];
                    $failed[] = "$name/$conn";
                }
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode(['failed' => $failed, 'report' => $report], JSON_PRETTY_PRINT));
        } else {
            $this->table(['Shard', 'Conn', 'Result'], $report);
            if ($failed) {
                $this->error('Failures: '.implode(', ', $failed));
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}