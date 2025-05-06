# ScalableDB · Sharding& Replication toolkit for Laravel11

![Tests](https://github.com/morozmkhl/scalable-db/actions/workflows/ci.yml/badge.svg)

<!-- CI badge (добавится после настройки GitHubActions) -->

<!-- ![Tests](https://github.com/<vendor>/scalable-db/actions/workflows/ci.yml/badge.svg) -->

**ScalableDB**— лёгкий Laravel‑пакет, который привносит в приложение:

* продвинутый **шардинг** (Hash/Range/ Lookup),
* **read/write‑splitting** с авто‑sticky,
* простой **fail‑over** на реплики,
* CLI‑DevTools для диагностики и миграций,
* DX‑фишки: фасад `Shard`, fluent‑API, middleware для HTTP‑контекста.

---

## ✨Возможности

| Блок                                                   | Готово |
| ------------------------------------------------------ | :----: |
| Hash‑шардинг (`crc32(id)%N`)                         |    ✔   |
| Range‑шардинг (диапазоны id)                           |    ✔   |
| Read/ Write splitting + sticky                        |    ✔   |
| Fail‑over master→ replica                             |    ✔   |
| Middleware`shard.tenant`                              |    ✔   |
| CLI: `shard:status`, `shard:diagnose`, `shard:migrate` |    ✔   |
| Lookup‑table стратегия                                 |    ✔    |
| События (`ShardResolved`, `ShardFailover`)             |    ✔   |
| CI(matrix PHP8.2/8.4×SQLite/MySQL)                 |    ⏳   |

---

## ⚡Установка

```bash
composer require morozmkhl/scalable-db --dev
php artisan vendor:publish --tag=scalable-db-config   # создаст config/scalable-db.php
```

> При использовании в пакетах/модулях Testbench подключает сервис‑провайдер автоматически.

---

## 🛠️Быстрый старт

```php
// config/scalable-db.php (минимальный пример)
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
            'replicas'   => ['shard0_replica1'],
        ],
        'shard_1' => [
            'connection' => 'shard1_master',
            'replicas'   => [],
        ],
    ],
];
```

### Lookup‑strategy

```php
'strategies' => [
  'lookup' => [
      'connection'   => 'lookup',    // БД с таблицей tenants
      'table'        => 'tenants',
      'key_column'   => 'id',
      'shard_column' => 'shard',
      'cache_ttl'    => 300,         // кэшировать 5 минут
  ],
],
'default_strategy' => env('SCALABLE_DB_STRATEGY', 'lookup'),
```

```php
// пример использования
$user = User::find(42);

Shard::forTenant($user->id)->run(function () use ($user) {
    Post::create([
        'user_id' => $user->id,
        'body'    => 'Shard‑aware insert 🚀',
    ]);
});
```

---

## 🏃‍♀️Middleware

```php
Route::middleware('shard.tenant')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

`TenantShardMiddleware` пытается определить tenant‑ID в такой последовательности:

1. `$request->user()->tenant_id` (или `id`, если `tenant_id` отсутствует);
2. заголовок **`X-Tenant-ID`**;
3. query‑параметр **`?tenant_id=…`**.

---

## 👩‍💻DevToolsCLI

| Команда                                    | Назначение                                        |
| ------------------------------------------ | ------------------------------------------------- |
| `php artisan shard:migrate [--shard=NAME]` | Запуск миграций на всех или выбранном шарде       |
| `php artisan shard:status`                 | Таблица шардов, ролей, online/offline, ping‑time  |
| `php artisan shard:diagnose [--json]`      | Полная диагностика; exit‑code `1`, если есть сбои |

---

### События

| Событие | Когда происходит | Поля |
|---------|------------------|------|
| `ShardResolved` | определён шард по tenant‑ключу | `tenantKey`, `shard`, `strategy` |
| `ShardFailover` | мастер упал, переключение на реплику | `shard`, `fromConnection`, `toConnection`, `exception` |

Пример логирования:

```php
Event::listen(ShardFailover::class, function ($e) {
    logger()->warning("Fail‑over {$e->shard}: {$e->fromConnection} → {$e->toConnection}", [
        'error' => $e->exception->getMessage(),
    ]);
});
```

---

## Telescope

Если в проекте установлен LaravelTelescope, Scalable DB автоматически
добавляет тег `shard:<name>` к каждому запросу/команде.  
Это облегчает отладку маршрутизации на шард.

---


## 🧪Тесты

```bash
# локальный запуск
composer test        # Pest + OrchestraTestbench
```

Текущий набор покрывает:

* резолвинг Hash/Range‑стратегий,
* sticky‑поведение после записи,
* fail‑over с моками DB Manager’а,
* работу middleware,
* DevToolsCLI.

---

## 🤝Contributing

Bug‑репорт, pull‑request или идея— welcome!
Перед PR запустите `composer lint && composer test`.
Стиль кода проверяется **LaravelPint**, статический анализ—**PHPStan**.

---

## 🗺️Roadmap

* Расширенный fail‑over (многократные ретраи, очередь реплик).
* Метрики (prometheus middleware, логирование lag).
* CI‑матрица и релиз вPackagist.

---

## 📄License

The MIT License (MIT). See [`LICENSE`](LICENSE) for details.
