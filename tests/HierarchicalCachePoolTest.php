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
