<?php
namespace ScalableDB\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ShardMigrateCommand extends Command
{
    protected $signature   = 'shard:migrate {--shard=} {--force}';
    protected $description = 'Run migrations on all shards or on selected shard';

    public function handle(): int
    {

        $cfg = config('scalable-db.shards');

        $target = $this->option('shard')
            ? [$this->option('shard') => $cfg[$this->option('shard')] ?? null]
            : $cfg;

        foreach ($target as $name => $def) {
            if (!$def) {
                $this->error("Shard [$name] not found"); continue;
            }

            $this->line("➜  Migrating shard <info>$name</info> …");

            $code = Artisan::call('migrate', [
                '--database' => $def['connection'],
                '--force'    => $this->option('force'),
            ]);

            if ($code !== 0) return $code;
        }

        $this->info('Shard migrations complete');
        return Command::SUCCESS;
    }
}