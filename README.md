# Scalable DB · Sharding & Replication toolkit for Laravel 11

<!-- CI badge (добавится после настройки GitHub Actions) -->

<!-- ![Tests](https://github.com/<vendor>/scalable-db/actions/workflows/ci.yml/badge.svg) -->

**Scalable DB** — лёгкий Laravel‑пакет, который привносит в приложение:

* продвинутый **шардинг** (Hash / Range / Lookup),
* **read/write‑splitting** с авто‑sticky,
* простой **fail‑over** на реплики,
* CLI‑DevTools для диагностики и миграций,
* DX‑фишки: фасад `Shard`, fluent‑API, middleware для HTTP‑контекста.

---

## ✨ Возможности

| Блок                                                   | Готово |
| ------------------------------------------------------ | :----: |
| Hash‑шардинг (`crc32(id) % N`)                         |    ✔   |
| Range‑шардинг (диапазоны id)                           |    ✔   |
| Read / Write splitting + sticky                        |    ✔   |
| Fail‑over master → replica                             |    ✔   |
| Middleware `shard.tenant`                              |    ✔   |
| CLI: `shard:status`, `shard:diagnose`, `shard:migrate` |    ✔   |
| Lookup‑table стратегия                                 |    ⏳   |
| События (`ShardResolved`, `ShardFailover`)             |    ⏳   |
| CI (matrix PHP 8.2/8.4 × SQLite/MySQL)                 |    ⏳   |

---

## ⚡ Установка

```bash
composer require <vendor>/scalable-db --dev
php artisan vendor:publish --tag=scalable-db-config   # создаст config/scalable-db.php
```

> При использовании в пакетах/модулях Testbench подключает сервис‑провайдер автоматически.

---

## 🛠️ Быстрый старт

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

## 🏃‍♀️ Middleware

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

## 👩‍💻 DevTools CLI

| Команда                                    | Назначение                                        |
| ------------------------------------------ | ------------------------------------------------- |
| `php artisan shard:migrate [--shard=NAME]` | Запуск миграций на всех или выбранном шарде       |
| `php artisan shard:status`                 | Таблица шардов, ролей, online/offline, ping‑time  |
| `php artisan shard:diagnose [--json]`      | Полная диагностика; exit‑code `1`, если есть сбои |

---

## 🧪 Тесты

```bash
# локальный запуск
composer test        # Pest + Orchestra Testbench
```

Текущий набор покрывает:

* резолвинг Hash/Range‑стратегий,
* sticky‑поведение после записи,
* fail‑over с моками DB Manager’а,
* работу middleware,
* DevTools CLI.

---

## 🤝 Contributing

Bug‑репорт, pull‑request или идея — welcome!
Перед PR запустите `composer lint && composer test`.
Стиль кода проверяется **Laravel Pint**, статический анализ — **PHPStan**.

---

## 🗺️ Roadmap

* Lookup‑table стратегия (центральная БД tenant → shard).
* Расширенный fail‑over (многократные ретраи, очередь реплик).
* Метрики (prometheus middleware, логирование lag).
* CI‑матрица и релиз в Packagist.

---

## 📄 License

The MIT License (MIT). See [`LICENSE`](LICENSE) for details.
