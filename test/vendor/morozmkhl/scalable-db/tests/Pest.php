<?php

use Illuminate\Support\Facades\Artisan;
use ScalableDB\Tests\TestCase;

/**
 * @param  array<string, mixed>  $opts
 * @return array{0: int, 1: string}
 */
function runCmd(string $command, array $opts = []): array
{
    $exit = Artisan::call($command, $opts);
    $out = Artisan::output();

    return [$exit, $out];
}

uses(TestCase::class)->in(__DIR__.'/Feature', __DIR__.'/Unit');
