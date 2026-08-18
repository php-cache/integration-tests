<?php

declare(strict_types=1);

namespace Cache\IntegrationTests\Tests;

use Cache\IntegrationTests\HierarchicalCachePoolTest as HierarchicalCachePoolContract;
use Cache\IntegrationTests\Tests\Fixtures\HierarchicalCachePool;
use Psr\Cache\CacheItemPoolInterface;

final class HierarchicalCachePoolTest extends HierarchicalCachePoolContract
{
    public function createCachePool(): CacheItemPoolInterface
    {
        return new HierarchicalCachePool();
    }
}
