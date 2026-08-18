<?php

declare(strict_types=1);

namespace Cache\IntegrationTests\Tests;

use Cache\IntegrationTests\CachePoolTest as CachePoolContract;
use Cache\IntegrationTests\Tests\Fixtures\ValidatingCachePool;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class CachePoolTest extends CachePoolContract
{
    private ?string $namespace = null;

    public function createCachePool(): CacheItemPoolInterface
    {
        $this->namespace ??= bin2hex(random_bytes(16));

        return new ValidatingCachePool(new FilesystemAdapter($this->namespace));
    }

    public function testDataProvidersExposeCases()
    {
        self::assertNotEmpty(self::invalidKeys());
        self::assertNotEmpty(self::invalidKeyTypes());
    }
}
