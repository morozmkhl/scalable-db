### Middleware

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // …
        \ScalableDB\Http\Middleware\TenantShardMiddleware::class, // auto‑shard
    ],
];