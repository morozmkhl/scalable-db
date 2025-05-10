<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ScalableDB\Facades\Shard;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        collect([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
            ['id' => 3, 'name' => 'Carol'],
        ])->each(function ($row) {
            Shard::forTenant($row['id'])->run(
                fn () => DB::table('users')->insert($row)
            );
        });
    }
}