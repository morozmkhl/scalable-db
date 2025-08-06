<?php
namespace ScalableDB\Events;

class ShardFailover
{
    public function __construct(
        public readonly string $shard,
        public readonly string $fromConnection,
        public readonly string $toConnection,
        public readonly \Throwable $exception,   // оригинальная ошибка мастера
    ) {}
}
