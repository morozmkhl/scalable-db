<?php

use Illuminate\Support\Facades\Schema;

it('accepts custom migration path', function () {
    $dbFile = sys_get_temp_dir().'/shard_test_'.uniqid().'.sqlite';
    touch($dbFile);

    config()->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => $dbFile,
        'prefix' => '',
    ]);
    config()->set('scalable-db.shards', [
        'S0' => ['connection' => 'sqlite', 'replicas' => []],
    ]);

    $relativePath = 'database/migrations/shard_custom_test';
    $migrationDir = base_path($relativePath);
    if (! is_dir($migrationDir)) {
        mkdir($migrationDir, 0755, true);
    }
    file_put_contents($migrationDir.'/2025_01_01_000000_create_items_table.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
PHP);

    [$code] = runCmd('shard:migrate', [
        '--shard' => 'S0',
        '--path' => $relativePath,
    ]);

    expect($code)->toBe(0)
        ->and(Schema::connection('sqlite')->hasTable('items'))->toBeTrue();
});

it('defaults migration path to database/migrations', function () {
    config()->set('scalable-db.shards', [
        'S0' => ['connection' => 'sqlite', 'replicas' => []],
    ]);

    [$code] = runCmd('shard:migrate', ['--shard' => 'S0']);

    expect($code)->toBe(0);
});
