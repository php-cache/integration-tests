<?php

/*
 * This file is part of php-cache\integration-tests package.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\IntegrationTests;

use Cache\Doctrine\CachePool;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

abstract class CachePoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type CacheItemPoolInterface
     */
    private $cache;

    /**
     * @return CacheItemPoolInterface that is used in the tests
     */
    abstract public function createCachePool();

    /**
     * @return CachePool
     */
    public function setUp()
    {
        $this->cache = $this->createCachePool();
    }

    protected function tearDown()
    {
        $this->cache->clear();
    }

    public function testBasicUsage()
    {
        $item = $this->cache->getItem('foo');
        $item->set('4711');
        $this->cache->save($item);

        $item = $this->cache->getItem('bar');
        $item->set('4712');
        $this->cache->save($item);

        $fooItem = $this->cache->getItem('foo');
        $this->assertTrue($fooItem->isHit());
        $this->assertEquals('4711', $fooItem->get());

        $barItem = $this->cache->getItem('bar');
        $this->assertTrue($barItem->isHit());
        $this->assertEquals('4712', $barItem->get());

        // Remove 'foo' and make sure 'bar' is still there
        $this->cache->deleteItem('foo');
        $this->assertFalse($this->cache->getItem('foo')->isHit());
        $this->assertTrue($this->cache->getItem('bar')->isHit());

        // Remove everything
        $this->cache->clear();
        $this->assertFalse($this->cache->getItem('foo')->isHit());
        $this->assertFalse($this->cache->getItem('bar')->isHit());
    }

    public function testDeferredSave()
    {
        $item = $this->cache->getItem('foo');
        $item->set('4711');
        $this->cache->saveDeferred($item);

        $item = $this->cache->getItem('bar');
        $item->set('4712');
        $this->cache->saveDeferred($item);

        // They are not saved yet, should be false
        $this->assertFalse($this->cache->getItem('foo')->isHit());
        $this->assertFalse($this->cache->getItem('bar')->isHit());

        $this->cache->commit();

        // They should be a hit now
        $this->assertTrue($this->cache->getItem('foo')->isHit());
        $this->assertTrue($this->cache->getItem('bar')->isHit());
    }

    public function testHasItem()
    {
        $this->assertFalse($this->cache->hasItem('foo'));

        $item = $this->cache->getItem('foo');
        $item->set('4711');

        // The item is not saved
        $this->assertFalse($this->cache->hasItem('foo'));

        // Save
        $this->cache->save($item);

        $this->assertTrue($this->cache->hasItem('foo'));
    }

    public function testGetItems()
    {
        $keys  = ['foo', 'bar', 'baz'];
        $items = $this->cache->getItems($keys);
        $this->assertCount(3, $items);

        /** @type CacheItemInterface $item */
        foreach ($items as $i => $item) {
            $item->set($i);
            $this->cache->save($item);
        }

        $sameItems = $this->cache->getItems($keys);
        foreach ($sameItems as $item) {
            $this->assertTrue($item->isHit());
        }
    }

    public function testDeleteItems()
    {
        $items = $this->cache->getItems(['foo', 'bar', 'baz']);

        /** @type CacheItemInterface $item */
        foreach ($items as $idx => $item) {
            $item->set($idx);
            $this->cache->save($item);
        }

        // All should be a hit
        $this->assertTrue($this->cache->getItem('foo')->isHit());
        $this->assertTrue($this->cache->getItem('bar')->isHit());
        $this->assertTrue($this->cache->getItem('baz')->isHit());

        $this->cache->deleteItems(['foo', 'bar']);

        $this->assertFalse($this->cache->getItem('foo')->isHit());
        $this->assertFalse($this->cache->getItem('bar')->isHit());
        $this->assertTrue($this->cache->getItem('baz')->isHit());
    }
}
