# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-07-11

### Added

- Fail-over configuration in `ShardManager`: `auto_failover`, `max_retries`, `fallback_connection`.
- `shard:migrate --path` option (default: `database/migrations`).
- Publishable `config/database-shards.php` stub (`scalable-db-database` tag).
- `Shard::forTenant()->forRead()` and `forWrite()` helpers for Laravel read/write routing.
- Configurable seeder class via `scalable-db.seeder`.
- Demo smoke test job in CI.
- PHPStan configuration (`phpstan.neon`, level 6).
- Test coverage for hash, range, lookup strategies, fail-over, migrate, seed, and read/write helpers.

### Changed

- `laravel/telescope` moved from `require` to `require-dev` and `suggest`.
- Package seeders namespace aligned to `ScalableDB\Database\Seeders` (PSR-4).
- Demo replica host uses `SHARD0_REPLICA_HOST` (defaults to `replica1`).
- CI workflow: lint and PHPStan gate, PHP 8.2/8.4 × SQLite/MySQL matrix, Composer cache.
- README rewritten in English; documentation expanded under `docs/`.
- `minimum-stability` set to `stable`.

### Fixed

- `ShardSeedCommand` uses configurable package seeder instead of application `DatabaseSeeder`.
- Removed unreachable code in `ShardManager::runInShard()`.
- Invalid `<ini>` element in `phpunit.xml` replaced with `<php>` block (PHPUnit 10).
- Demo Docker build: use `.env.example`, disable Composer audit block for Laravel skeleton install.
- Duplicate Telescope migration removed from demo.

## [1.0.0] - 2025-05-08

### Added

- Initial release: hash, range, and lookup sharding strategies.
- `Shard` facade, `shard_for()` helper, and `shard.tenant` middleware.
- Artisan commands: `shard:status`, `shard:diagnose`, `shard:migrate`, `shard:seed`.
- Events: `ShardResolved`, `ShardFailover`.
- Optional Telescope shard tagging via `ShardTagWatcher`.
- Docker demo environment.

[1.1.0]: https://github.com/morozmkhl/scalable-db/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/morozmkhl/scalable-db/releases/tag/v1.0.0
