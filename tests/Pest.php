<?php
use ScalableDB\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Testing\PendingCommand;

function runCmd(string $command, array $opts = []): array
{
    $exit  = Artisan::call($command, $opts);   // ← ключевая замена
    $out   = Artisan::output();

    return [$exit, $out];
}

uses(TestCase::class)->in(__DIR__);