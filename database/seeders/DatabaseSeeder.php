<?php

namespace ScalableDB\database\Seeders;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        (new TenantSeeder)->run();
    }
}