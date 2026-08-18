<?php

declare(strict_types=1);

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\IntegrationTests\Tests;

use Cache\IntegrationTests\SimpleCacheTest as SimpleCacheContract;
use Cache\IntegrationTests\Tests\Fixtures\ValidatingCachePool;
use Cache\IntegrationTests\Tests\Fixtures\ValidatingSimpleCache;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class SimpleCacheTest extends SimpleCacheContract
{
    private ?string $namespace = null;

    public function createSimpleCache(): CacheInterface
    {
        $this->namespace ??= bin2hex(random_bytes(16));

        $pool = new ValidatingCachePool(new FilesystemAdapter($this->namespace));

        return new ValidatingSimpleCache(new Psr16Cache($pool));
    }

    public function testDataProvidersExposeCases()
    {
        self::assertNotEmpty(self::invalidKeys());
        self::assertNotEmpty(self::invalidArrayKeys());
        self::assertNotEmpty(self::invalidKeyTypes());
        self::assertNotEmpty(self::nonIterableValues());
        self::assertNotEmpty(self::invalidTtlValues());
        self::assertNotEmpty(self::invalidSetMultipleKeyTypes());
        self::assertNotEmpty(self::validKeys());
        self::assertNotEmpty(self::validData());
    }
}
