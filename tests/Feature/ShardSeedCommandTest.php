<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ScalableDB\Database\Seeders\DatabaseSeeder;

it('runs configured seeder across lookup database', function () {
    config()->set('database.connections.lookup', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    Schema::connection('lookup')->create('tenants', function ($table) {
        $table->integer('id')->primary();
        $table->string('shard');
    });

    config()->set('scalable-db.seeder', DatabaseSeeder::class);

    [$code] = runCmd('shard:seed');

    expect($code)->toBe(0)
        ->and(DB::connection('lookup')->table('tenants')->count())->toBe(3)
        ->and(DB::connection('lookup')->table('tenants')->pluck('shard')->all())
        ->toContain('shard_0', 'shard_1');
});
