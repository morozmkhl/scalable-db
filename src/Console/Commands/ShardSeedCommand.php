<?php

namespace ScalableDB\Console\Commands;

use Illuminate\Console\Command;

class ShardSeedCommand extends Command
{
    protected $signature = 'shard:seed {--count=3}';

    protected $description = 'Seed demo tenants & users across shards';

    public function handle(): int
    {
        $seeder = config('scalable-db.seeder');

        $this->call($seeder);

        $this->components->info('Tenants & demo‑users seeded!');

        return self::SUCCESS;
    }
}
