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
use Cache\Taggable\TaggablePoolInterface;
use Psr\Cache\CacheItemPoolInterface;

abstract class TaggableCachePoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type CacheItemPoolInterface|TaggablePoolInterface
     */
    private $cache;

    /**
     * @return TaggablePoolInterface that is used in the tests
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
        $item = $this->cache->getItem('tobias', ['developer', 'speaker']);
        $item->set('foobar');
        $this->cache->save($item);

        $item = $this->cache->getItem('aaron', ['developer', 'nice guy']);
        $item->set('foobar');
        $this->cache->save($item);

        $item = $this->cache->getItem('the king of Sweden', ['nice guy', 'king']);
        $item->set('foobar');
        $this->cache->save($item);

        $this->assertTrue($this->cache->getItem('tobias', ['developer', 'speaker'])->isHit());
        $this->assertTrue($this->cache->getItem('tobias', ['speaker', 'developer'])->isHit());
        $this->assertFalse($this->cache->getItem('tobias', ['developer'])->isHit());
        $this->assertFalse($this->cache->getItem('tobias', ['king'])->isHit());
        $this->assertFalse($this->cache->getItem('tobias')->isHit());

        // Remove everything tagged with 'nice guy'
        $this->cache->clear(['nice guy']);
        $this->assertTrue($this->cache->getItem('tobias', ['developer', 'speaker'])->isHit());
        $this->assertFalse($this->cache->getItem('aaron', ['developer', 'nice guy'])->isHit());
        $this->assertFalse($this->cache->getItem('the king of Sweden', ['nice guy', 'king'])->isHit());

        // To clear everything you do as you usually do
        $this->cache->clear();
        $this->assertFalse($this->cache->getItem('tobias', ['developer', 'speaker'])->isHit());
    }

    public function testGetItem()
    {
        $item = $this->cache->getItem('tobias', ['developer', 'speaker']);
        $item->set('value');
        $this->cache->save($item);

        $item = $this->cache->getItem('tobias', ['developer']);
        $this->assertFalse($item->isHit(), 'There should be no item with key "tobias" and tag "developer"');
    }

    public function testGetItems()
    {
        $item = $this->cache->getItem('tobias', ['developer']);
        $item->set('value');
        $this->cache->save($item);

        $items = $this->cache->getItems(['tobias', 'aaron'], ['developer']);
        $this->assertCount(2, $items);
        $this->assertTrue($items['tobias']->isHit());
        $this->assertFalse($items['aaron']->isHit());
    }

    public function testHasItem()
    {
        $item = $this->cache->getItem('tobias', ['developer']);
        $item->set('value');
        $this->cache->save($item);

        $this->assertTrue($this->cache->hasItem('tobias', ['developer']));
        $this->assertFalse($this->cache->hasItem('aaron', ['developer']));
    }

    public function testDeleteItem()
    {
        $item = $this->cache->getItem('tobias', ['developer', 'speaker']);
        $item->set('foobar');
        $this->cache->save($item);

        $this->cache->deleteItem('tobias', ['developer']);
        $this->assertTrue($this->cache->getItem('tobias', ['developer', 'speaker'])->isHit());

        $this->cache->deleteItem('tobias', ['developer', 'speaker']);
        $this->assertFalse($this->cache->getItem('tobias', ['developer', 'speaker'])->isHit());
    }

    public function testNoKeyModificationWhenNoTags()
    {
        $item = $this->cache->getItem('key');
        $item->set('foobar');
        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertEquals('key', $item->getKey(), 'Key can not change when using no tags');
    }

    public function testKeysShouldAppearUnchanged()
    {
        $item = $this->cache->getItem('key', ['tag']);
        $item->set('foobar');
        $this->cache->save($item);

        $item = $this->cache->getItem('key', ['tag']);
        $this->assertEquals('key', $item->getKey(), 'Key should appear intact even when using tags');
    }
}
