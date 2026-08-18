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

use Cache\IntegrationTests\CachePoolTest as CachePoolContract;
use Cache\IntegrationTests\HierarchicalCachePoolTest as HierarchicalCachePoolContract;
use Cache\IntegrationTests\SimpleCacheTest as SimpleCacheContract;
use Cache\IntegrationTests\TaggableCachePoolTest as TaggableCachePoolContract;
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

    public function testExtensionPointsRemainCompatibleWithUntypedOverrides()
    {
        $hooks = ['advanceTime', 'setupService', 'tearDownService'];
        $contracts = [
            CachePoolContract::class,
            HierarchicalCachePoolContract::class,
            SimpleCacheContract::class,
            TaggableCachePoolContract::class,
        ];

        foreach ($contracts as $contract) {
            $reflection = new \ReflectionClass($contract);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== $contract) {
                    continue;
                }

                if (!str_starts_with($method->getName(), 'test') && !\in_array($method->getName(), $hooks, true)) {
                    continue;
                }

                self::assertNull(
                    $method->getReturnType(),
                    \sprintf('%s::%s() must remain compatible with untyped consumer overrides.', $contract, $method->getName())
                );
            }
        }
    }
}
