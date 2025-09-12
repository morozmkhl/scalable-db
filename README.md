# ScalableDB

Shard routing layer for Laravel 11 applications. The package resolves a tenant or entity key to a database shard, switches the default connection for the duration of a callback or HTTP request, and provides optional fail-over and operational CLI commands.

[![Tests](https://github.com/morozmkhl/scalable-db/actions/workflows/ci.yml/badge.svg)](https://github.com/morozmkhl/scalable-db/actions/workflows/ci.yml)

## Requirements

| Component | Version |
|-----------|---------|
| PHP       | ^8.2    |
| Laravel   | ^11.5   |

## Installation

```bash
composer require morozmkhl/scalable-db
php artisan vendor:publish --tag=scalable-db-config
```

Optional publish targets:

```bash
php artisan vendor:publish --tag=scalable-db-migrations
php artisan vendor:publish --tag=scalable-db-database
```

The `scalable-db-database` tag provides a sample `database-shards.php` stub with read/write host configuration.

## Configuration

Publish `config/scalable-db.php` and define shard connections in `config/database.php`. Each shard entry maps a logical name to a Laravel connection name and an optional list of replica connections used during fail-over.

```php
return [
    'default_strategy' => 'hash',

    'strategies' => [
        'hash' => [
            'shard_count' => 2,
            'map' => [0 => 'shard_0', 1 => 'shard_1'],
        ],
    ],

    'shards' => [
        'shard_0' => [
            'connection' => 'shard0_master',
            'replicas' => ['shard0_replica1'],
        ],
        'shard_1' => [
            'connection' => 'shard1_master',
            'replicas' => [],
        ],
    ],

    'failover' => [
        'auto_failover' => false,
        'max_retries' => 1,
        'fallback_connection' => null,
    ],
];
```

Fail-over is disabled by default. Set `auto_failover` to `true` to enable replica retry on `PDOException`.

Further reference: [Configuration](docs/configuration.md).

## Sharding strategies

### Hash

Assigns keys using `crc32((string) $key) % shard_count` and a slot-to-shard map.

```php
'strategies' => [
    'hash' => [
        'shard_count' => 2,
        'map' => [0 => 'shard_0', 1 => 'shard_1'],
    ],
],
'default_strategy' => 'hash',
```

```php
use ScalableDB\Facades\Shard;

Shard::forTenant($userId)->run(function () use ($userId) {
    return User::find($userId);
});
```

### Range

Assigns keys by inclusive numeric ranges.

```php
'strategies' => [
    'range' => [
        'ranges' => [
            ['min' => 1,     'max' => 10000,  'shard' => 'shard_0'],
            ['min' => 10001, 'max' => 20000,  'shard' => 'shard_1'],
        ],
    ],
],
'default_strategy' => 'range',
```

```php
Shard::forTenant($orderId)->run(function () use ($orderId) {
    return Order::find($orderId);
});
```

### Lookup

Resolves keys from a lookup table (for example, `tenants`). Supports optional query result caching.

```php
'strategies' => [
    'lookup' => [
        'connection'   => 'lookup',
        'table'        => 'tenants',
        'key_column'   => 'id',
        'shard_column' => 'shard',
        'cache_ttl'    => 300,
    ],
],
'default_strategy' => 'lookup',
```

```php
Shard::forTenant($tenantId)->run(function () use ($tenantId) {
    return Post::where('tenant_id', $tenantId)->get();
});
```

Strategy details: [Sharding strategies](docs/strategies.md).

## Read/write splitting

ScalableDB does not implement read/write routing. Configure `read`, `write`, and `sticky` on each shard connection in `config/database.php` using Laravel's built-in behaviour. The package switches the default connection to the shard master; Laravel routes individual statements to read or write hosts within that connection.

```php
'shard0_master' => [
    'driver' => 'mysql',
    'read'   => ['host' => ['replica1.example.com']],
    'write'  => ['host' => ['master.example.com']],
    'sticky' => true,
],
```

Optional helpers select the read or write PDO for the current connection:

```php
Shard::forTenant($id)->forRead()->run(fn () => User::find($id));
Shard::forTenant($id)->forWrite()->run(fn () => User::create([...]));
```

## HTTP middleware

Register routes behind the `shard.tenant` middleware alias. The middleware resolves a tenant identifier in the following order:

1. `$request->user()->tenant_id`, or `$request->user()->id` when `tenant_id` is absent
2. HTTP header `X-Tenant-ID`
3. Query parameter `tenant_id`

If no tenant identifier is present, the request proceeds without changing the active shard.

```php
Route::middleware('shard.tenant')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

## Artisan commands

| Command | Description |
|---------|-------------|
| `php artisan shard:migrate [--shard=NAME] [--path=PATH]` | Run migrations on all shards or one shard |
| `php artisan shard:seed` | Run the configured seeder class |
| `php artisan shard:status` | Report connectivity for masters and replicas |
| `php artisan shard:diagnose [--json]` | Full diagnostics; exit code `1` on failure |

Command reference: [CLI](docs/cli.md).

## Events

| Event | Dispatched when |
|-------|-----------------|
| `ShardResolved` | A shard name is resolved from a tenant key |
| `ShardFailover` | Fail-over switches from master to a replica or fallback connection |

```php
use ScalableDB\Events\ShardFailover;
use Illuminate\Support\Facades\Event;

Event::listen(ShardFailover::class, function (ShardFailover $event) {
    logger()->warning("Fail-over {$event->shard}: {$event->fromConnection} -> {$event->toConnection}", [
        'error' => $event->exception->getMessage(),
    ]);
});
```

## Telescope

[Laravel Telescope](https://laravel.com/docs/telescope) is an optional dependency. When present, the package registers a watcher that tags entries with `shard:<name>` based on the active shard context.

## Testing

```bash
composer test
composer lint -- --test
composer analyse
```

The test suite uses [Pest](https://pestphp.com/) and [Orchestra Testbench](https://packages.tools/testbench/). CI runs lint, static analysis, a PHP 8.2/8.4 × SQLite/MySQL matrix, and a Docker demo smoke test.

## Demo environment

A Docker-based demo application is provided under `demo/`.

```bash
cd demo
docker compose up -d --build
curl http://localhost:8000/ping
curl -X POST -d "id=5&name=Eve" http://localhost:8000/users
curl http://localhost:8000/users/shard/5
```

See [Demo](docs/demo.md).

## Limitations

- **Single-shard scope.** Queries run against one shard per callback or request. Cross-shard joins, aggregates, and transactions are not supported.
- **Fail-over trigger.** Fail-over reacts to `PDOException` only. Application-level errors and connection timeouts outside PDO are not handled automatically.
- **Fail-over default.** `auto_failover` is `false` by default; replica retry must be enabled explicitly.
- **Replica writes.** Fail-over may route traffic to a read-only replica. Write operations against a replica can fail at the database level.
- **No rebalancing.** The package does not migrate data between shards or rebalance keys.
- **No distributed transactions.** Two-phase commit and saga patterns are out of scope.
- **Replication setup.** MySQL (or other) replication must be configured manually in `config/database.php`.
- **Strategy binding.** `ShardManager` is registered as a singleton. Changing `default_strategy` at runtime requires `app()->forgetInstance('shard.manager')`.

## Documentation

Full documentation is in [docs/](docs/README.md): installation, configuration, architecture, API, CLI, and development notes.

## Contributing

Run the following before opening a pull request:

```bash
composer lint -- --test
composer analyse
composer test
```

Code style is enforced with [Laravel Pint](https://laravel.com/docs/pint). Static analysis uses [PHPStan](https://phpstan.org/) with [Larastan](https://github.com/larastan/larastan).

## License

The MIT License. See [LICENSE](LICENSE).
