<?php
namespace ScalableDB\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use ScalableDB\Facades\Shard;

class TenantShardMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Попытка достать tenant‑id из пользователя или заголовка
        $tenantId = $this->resolveTenantId($request);

        // Если tenant не найден — пропускаем без изменения контекста
        if ($tenantId === null) {
            return $next($request);
        }

        // Оборачиваем выполнение запроса в контекст шарда
        return Shard::forTenant($tenantId)->run(fn () => $next($request));
    }

    private function resolveTenantId(Request $request): ?int
    {
        // 1️⃣. Аутентифицированный пользователь (user->tenant_id)
        if ($user = $request->user()) {
            return $user->tenant_id ?? $user->id;   // подстройте под свою модель
        }

        // 2️⃣. Заголовок X-Tenant-ID
        if ($tid = $request->header('X-Tenant-ID')) {
            return (int) $tid;
        }

        // 3️⃣. Query‑параметр (?tenant_id=…)
        return $request->query('tenant_id');
    }
}