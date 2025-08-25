<?php

namespace ScalableDB\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\DatabaseSeeder;

class ShardSeedCommand extends Command
{
    protected $signature   = 'shard:seed {--count=3}';
    protected $description = 'Seed demo tenants & users across shards';

    public function handle(): int
    {
        $this->call(DatabaseSeeder::class);

        $this->components->info('Tenants & demo‑users seeded!');
        return self::SUCCESS;
    }
}