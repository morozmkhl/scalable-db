<?php
namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('lookup')->table('tenants')->insert([
            ['id' => 1, 'shard' => 'shard_0'],
            ['id' => 2, 'shard' => 'shard_1'],
            ['id' => 3, 'shard' => 'shard_0'],
        ]);
    }
}
