<?php

/*
 * This file is part of php-cache\apc-adapter package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\IntegrationTests;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
abstract class HierarchicalCachePoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type array with functionName => reason.
     */
    protected $skippedTests = [];

    /**
     * @type CacheItemPoolInterface
     */
    private $cache;

    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    abstract public function createCachePool();

    public function setUp()
    {
        $this->cache = $this->createCachePool();
    }

    public function tearDown()
    {
        if ($this->cache !== null) {
            $this->cache->clear();
        }
    }

    public function testBasicUsage()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $pool = $this->createCachePool();
        $user = 4711;
        for ($i = 0; $i < 10; $i++) {
            $item = $pool->getItem(sprintf('|users|%d|followers|%d|likes', $user, $i), ['user']);
            $item->set('Justin Bieber');
            $pool->save($item);
        }

        $this->assertTrue($pool->hasItem('|users|4711|followers|4|likes', ['user']));
        $pool->deleteItem('|users|4711|followers', ['user']);
        $this->assertFalse($pool->hasItem('|users|4711|followers|4|likes', ['user']));
    }
}
