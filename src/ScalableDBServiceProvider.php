<?php
namespace ScalableDB;

use Illuminate\Support\ServiceProvider;
use ScalableDB\Console;
use ScalableDB\Services\ShardManager;
use ScalableDB\Strategies\HashShardingStrategy;

class ScalableDBServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('shard.manager', function ($app) {
            $cfg      = $app['config']->get('scalable-db');
            $strategy = $cfg['default_strategy'];

            $strategyInstance = match ($strategy) {
                'hash'  => new \ScalableDB\Strategies\HashShardingStrategy(
                    $cfg['strategies']['hash']['map'],
                    $cfg['strategies']['hash']['shard_count'],
                ),
                'range' => new \ScalableDB\Strategies\RangeShardingStrategy(
                    $cfg['strategies']['range']['ranges'],
                ),
                default => throw new \RuntimeException("Unknown strategy [$strategy]")
            };

            return new \ScalableDB\Services\ShardManager(
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
                __DIR__.'/../config/scalable-db.php' => config_path('scalable-db.php'),
            ], 'scalable-db-config');

            $this->commands([
                Console\Commands\ShardMigrateCommand::class,
            ]);
        }

        $router = $this->app['router'];
        $router->aliasMiddleware('shard.tenant', \ScalableDB\Http\Middleware\TenantShardMiddleware::class);
    }
}