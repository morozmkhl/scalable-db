<?php
namespace ScalableDB\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use ScalableDB\ScalableDBServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /** Подключаем наш провайдер */
    protected function getPackageProviders($app)
    {
        return [ ScalableDBServiceProvider::class ];
    }

    /** Регистрируем фасад‑алиас */
    protected function getPackageAliases($app)
    {
        return [ 'Shard' => ScalableDB\Facades\Shard::class ];
    }

    /** Настраиваем окружение ДО инициализации провайдера */
    protected function getEnvironmentSetUp($app)
    {
        // Стратегия range и фиктивные шарды
        $app['config']->set('scalable-db.default_strategy', 'range');
        $app['config']->set('scalable-db.strategies.range.ranges', [
            ['min' => 1,  'max' => 100,  'shard' => 'A'],
            ['min' => 101,'max' => 200,  'shard' => 'B'],
        ]);
        $app['config']->set('scalable-db.shards', [
            'A' => [ 'connection' => 'sqlite', 'replicas' => [] ],
            'B' => [ 'connection' => 'sqlite', 'replicas' => [] ],
        ]);

        // in‑memory SQLite по умолчанию
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}