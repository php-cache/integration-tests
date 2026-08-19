# PSR-6 and PSR-16 integration tests

[![CI](https://github.com/php-cache/integration-tests/actions/workflows/unit-tests.yaml/badge.svg)](https://github.com/php-cache/integration-tests/actions/workflows/unit-tests.yaml)
[![Latest Stable Version](https://poser.pugx.org/cache/integration-tests/v/stable)](https://packagist.org/packages/cache/integration-tests)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

This package tests whether cache implementations follow PSR-6 or PSR-16. It also includes suites for PHP Cache tags and hierarchical keys.

Version 1 requires PHP 8.2, PHPUnit 11.5 or 12, `psr/cache` 3, `psr/simple-cache` 2 or 3, and `cache/tag-interop` 2.

## Installation

```bash
composer require --dev cache/integration-tests:^1.0
```

## Testing a PSR-6 pool

Extend `CachePoolTest` and return a fresh pool from `createCachePool()`:

```php
use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemPoolInterface;

final class PoolIntegrationTest extends CachePoolTest
{
    public function createCachePool(): CacheItemPoolInterface
    {
        return new MyCachePool();
    }
}
```

Use `HierarchicalCachePoolTest` for pools that support hierarchical keys.

## Testing tags

```php
use Cache\IntegrationTests\TaggableCachePoolTest;
use Cache\TagInterop\TaggableCacheItemPoolInterface;

final class TagIntegrationTest extends TaggableCachePoolTest
{
    public function createCachePool(): TaggableCacheItemPoolInterface
    {
        return new MyTaggableCachePool();
    }
}
```

## Testing a PSR-16 cache

```php
use Cache\IntegrationTests\SimpleCacheTest;
use Psr\SimpleCache\CacheInterface;

final class SimpleCacheIntegrationTest extends SimpleCacheTest
{
    public function createSimpleCache(): CacheInterface
    {
        return new MySimpleCache();
    }
}
```

## Skipping unsupported behavior

The test bases expose a typed `$skippedTests` map. Skip a test only when the storage backend cannot provide that behavior.

```php
use Cache\IntegrationTests\CachePoolTest;
use Psr\Cache\CacheItemPoolInterface;

final class PoolWithSkippedExpirationTest extends CachePoolTest
{
    /** @var array<string, string> */
    protected array $skippedTests = [
        'testExpiration' => 'This backend does not support expiration.',
    ];

    public function createCachePool(): CacheItemPoolInterface
    {
        return new MyCachePool();
    }
}
```

## Contributing

Run the complete local checks before opening a pull request:

```bash
composer test
composer coverage
composer analyse
composer cs-check
```

Report problems on the [GitHub issue tracker](https://github.com/php-cache/integration-tests/issues).
