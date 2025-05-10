<?php
namespace ScalableDB;

use Illuminate\Support\ServiceProvider;
use ScalableDB\Console;
use ScalableDB\Services\ShardManager;
use ScalableDB\Strategies\HashShardingStrategy;
use ScalableDB\Strategies\LookupShardingStrategy;
use ScalableDB\Strategies\RangeShardingStrategy;

class ScalableDBServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/scalable-db.php', 'scalable-db');

        $this->app->singleton('shard.manager', function ($app) {
            $cfg      = $app['config']->get('scalable-db');
            $strategy = $cfg['default_strategy'];

            $strategyInstance = match ($strategy) {
                'hash'  => new HashShardingStrategy(
                    $cfg['strategies']['hash']['map'],
                    $cfg['strategies']['hash']['shard_count'],
                ),
                'range' => new RangeShardingStrategy(
                    $cfg['strategies']['range']['ranges'],
                ),
                'lookup' => new LookupShardingStrategy($cfg['strategies']['lookup']),
                default => throw new \RuntimeException("Unknown strategy [$strategy]")
            };

            return new ShardManager(
                $app['db'],
                $strategyInstance,
                $cfg
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations/lookup' => database_path('migrations/lookup'),
            ], 'scalable-db-migrations');
            $this->publishes([
                __DIR__.'/../config/scalable-db.php' => config_path('scalable-db.php'),
            ], 'scalable-db-config');


        }

        $this->commands([
            Console\Commands\ShardMigrateCommand::class,
            Console\Commands\ShardStatusCommand::class,
            Console\Commands\ShardDiagnoseCommand::class,
            Console\Commands\ShardSeedCommand::class,
        ]);

        $router = $this->app['router'];
        $router->aliasMiddleware('shard.tenant', \ScalableDB\Http\Middleware\TenantShardMiddleware::class);
        if (class_exists(\Laravel\Telescope\Telescope::class)) {
            \ScalableDB\Telescope\ShardTagWatcher::register();
        }

    }
}