<?php
namespace ScalableDB\Telescope;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;

class ShardTagWatcher
{
    public static function register(): void
    {
        Telescope::tag(fn (IncomingEntry $entry) => self::tags($entry));

        // Для live‑tag на QueryExecuted
        Event::listen(QueryExecuted::class, function () {
            // no-op, теги добавит Telescope::tag
        });
    }

    public static function tags(IncomingEntry $entry): array
    {
        $current = \ScalableDB\Facades\Shard::current();
        return $current ? ["shard:$current"] : [];
    }
}
