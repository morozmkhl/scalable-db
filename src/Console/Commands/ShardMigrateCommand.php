<?php

namespace ScalableDB\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ShardMigrateCommand extends Command
{
    protected $signature = 'shard:migrate
                            {--shard= : Run migrations only on the selected shard}
                            {--path=database/migrations : Path to migration files}
                            {--force : Force the operation to run in production}';

    protected $description = 'Run migrations on all shards or on selected shard';

    public function handle(): int
    {

        $cfg = config('scalable-db.shards');
        $path = (string) $this->option('path');

        $target = $this->option('shard')
            ? [$this->option('shard') => $cfg[$this->option('shard')] ?? null]
            : $cfg;

        foreach ($target as $name => $def) {
            if (! $def) {
                $this->error("Shard [$name] not found");

                continue;
            }

            $this->line("➜  Migrating shard <info>$name</info> (path: $path) …");
            $code = Artisan::call('migrate', [
                '--database' => $def['connection'],
                '--path' => $path,
                '--force' => $this->option('force'),
            ]);

            if ($code !== 0) {
                return $code;
            }
        }

        $this->info('Shard migrations complete');

        return Command::SUCCESS;
    }
}
