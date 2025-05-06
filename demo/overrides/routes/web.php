<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use ScalableDB\Facades\Shard;

Route::get('/ping', function () {
    return Shard::forTenant(1)->run(fn () => DB::select('select 1'));
});