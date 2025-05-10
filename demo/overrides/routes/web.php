<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use ScalableDB\Facades\Shard;


Route::get('/debug-middleware', function () {
    $rc = new \ReflectionClass(App\Http\Middleware\VerifyCsrfToken::class);
    return $rc->getFileName(); // покажет путь до реально загруженного класса
});

Route::get('/ping', fn () => Shard::forTenant(1)->run(
    fn () => DB::select('select 1')
));

Route::post('/users', function (Request $r) {
    return Shard::forTenant($r->id)->run(function () use ($r) {
        DB::table('users')->insert(['id' => $r->id, 'name' => $r->name]);
        return ['status' => 'ok'];
    });
});

Route::get('/users/{id}', function ($id) {
    return Shard::forTenant($id)->run(
        fn () => DB::table('users')->find($id)
    );
});

/** показать, на каком шарде хранится tenant */
Route::get('/users/shard/{tenantId}', fn ($tenantId) =>
['shard' => Shard::forTenant($tenantId)->run(fn () => Shard::current())]
);

/** статус шардов */
Route::get('/status', function () {
    $resp = [];
    foreach (config('scalable-db.shards') as $name => $info) {
        try {
            DB::connection($info['connection'])->select('select 1');
            $resp[$name]['master'] = 'OK';
        } catch (\Throwable) {
            $resp[$name]['master'] = 'DOWN';
        }
    }
    return $resp;
});