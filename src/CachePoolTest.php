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

    public function tearDown()
    {
        if ($this->cache !== null) {
            $this->cache->clear();
        }
    }

    /**
     * Data provider for invalid keys.
     *
     * @return array
     */
    public function invalidKeys()
    {
        return [['{'], ['}'], ['('], [')'], ['/'], ['\\'], ['@'], [':']];
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

    public function testGetItem()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->cache->save($item);

        // get existing item
        $item = $this->cache->getItem('key');
        $this->assertEquals('value', $item->get());

        // get non-existent item
        $item = $this->cache->getItem('key2');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit is false.");
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

        $keys[] = 'biz';
        $items  = $this->cache->getItems($keys);
        $this->assertCount(4, $items);
        $this->assertTrue($items['foo']->isHit());
        $this->assertTrue($items['bar']->isHit());
        $this->assertTrue($items['baz']->isHit());
        $this->assertFalse($items['biz']->isHit());
    }

    public function testGetItemsEmpty()
    {
        $items = $this->cache->getItems([]);
        $this->assertCount(0, $items);
        $this->assertTrue(is_array($items));
    }

    public function testHasItem()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->cache->save($item);

        // has existing item
        $this->assertTrue($this->cache->hasItem('key'));

        // has non-existent item
        $this->assertFalse($this->cache->hasItem('key2'));
    }

    public function testClear()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->cache->save($item);

        $return = $this->cache->clear();

        $this->assertTrue($return);
        $this->assertFalse($this->cache->getItem('key')->isHit());
    }

    public function testClearWithDeferredItems()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->cache->saveDeferred($item);

        $this->cache->clear();
        $this->cache->commit();

        $this->assertFalse($this->cache->getItem('key')->isHit());
    }

    public function testDeleteItem()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->cache->save($item);

        $this->assertTrue($this->cache->deleteItem('key'));
        $this->assertFalse($this->cache->getItem('key')->isHit());

        $this->assertTrue($this->cache->deleteItem('key2'), 'Deleting an item that does not exist should be return true.');
    }

    public function testDeleteItems()
    {
        $items = $this->cache->getItems(['foo', 'bar', 'baz']);

        /** @type CacheItemInterface $item */
        foreach ($items as $idx => $item) {
            $item->set($idx);
            $this->cache->save($item);
        }

        // All should be a hit but 'biz'
        $this->assertTrue($this->cache->getItem('foo')->isHit());
        $this->assertTrue($this->cache->getItem('bar')->isHit());
        $this->assertTrue($this->cache->getItem('baz')->isHit());
        $this->assertFalse($this->cache->getItem('biz')->isHit());

        $return = $this->cache->deleteItems(['foo', 'bar', 'biz']);
        $this->assertTrue($return);

        $this->assertFalse($this->cache->getItem('foo')->isHit());
        $this->assertFalse($this->cache->getItem('bar')->isHit());
        $this->assertTrue($this->cache->getItem('baz')->isHit());
        $this->assertFalse($this->cache->getItem('biz')->isHit());
    }

    public function testSave()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $return = $this->cache->save($item);

        $this->assertTrue($return);
        $this->assertEquals('value', $this->cache->getItem('key')->get());
    }

    public function testDeferredSave()
    {
        $item = $this->cache->getItem('foo');
        $item->set('4711');
        $this->cache->saveDeferred($item);

        $item = $this->cache->getItem('bar');
        $item->set('4712');
        $this->cache->saveDeferred($item);

        // They are not saved yet but should be a hit
        $this->assertTrue($this->cache->getItem('foo')->isHit());
        $this->assertTrue($this->cache->getItem('bar')->isHit());

        $this->cache->commit();

        // They should be a hit after the commit as well
        $this->assertTrue($this->cache->getItem('foo')->isHit());
        $this->assertTrue($this->cache->getItem('bar')->isHit());
    }

    public function testDeleteDeferredItem()
    {
        $item = $this->cache->getItem('foo');
        $item->set('4711');
        $this->cache->saveDeferred($item);
        $this->assertTrue($this->cache->getItem('foo')->isHit());

        $this->cache->deleteItem('foo');
        $this->assertFalse($this->cache->getItem('foo')->isHit());

        $this->cache->commit();
        $this->assertFalse($this->cache->getItem('foo')->isHit());
    }

    public function testDeferredSaveWithoutCommit()
    {
        $item = $this->cache->getItem('foo');
        $item->set('4711');
        $this->cache->saveDeferred($item);

        $this->cache = null;

        $cache = $this->createCachePool();
        $this->assertTrue($cache->getItem('foo')->isHit());
    }

    public function testCommit()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->cache->saveDeferred($item);
        $return = $this->cache->commit();

        $this->assertTrue($return);
        $this->assertEquals('value', $this->cache->getItem('key')->get());
    }

    public function testExpiration()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        // Expire after 2 seconds
        $item->expiresAfter(2);
        $this->cache->save($item);

        sleep(4);
        $item = $this->cache->getItem('key');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit is false.");
    }

    public function testKeyLength()
    {
        $key  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.';
        $item = $this->cache->getItem($key);
        $item->set('value');
        $this->cache->save($item);

        $this->assertTrue($this->cache->hasItem($key));
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testGetItemInvalidKeys($key)
    {
        $this->cache->getItem(sprintf('random%stext', $key));
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testGetItemsInvalidKeys($key)
    {
        $this->cache->getItems(['key1', sprintf('random%stext', $key), 'key2']);
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testHasItemInvalidKeys($key)
    {
        $this->cache->hasItem(sprintf('random%stext', $key));
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testDeleteItemInvalidKeys($key)
    {
        $this->cache->deleteItem(sprintf('random%stext', $key));
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     * @dataProvider invalidKeys
     */
    public function testDeleteItemsInvalidKeys($key)
    {
        $this->cache->deleteItems(['key1', sprintf('random%stext', $key), 'key2']);
    }

    public function testDataTypeString()
    {
        $item = $this->cache->getItem('key');
        $item->set('5');
        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue('5' === $item->get());
        $this->assertTrue(is_string($item->get()));
    }

    public function testDataTypeInteger()
    {
        $item = $this->cache->getItem('key');
        $item->set(5);
        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue(5 === $item->get());
        $this->assertTrue(is_int($item->get()));
    }

    public function testDataTypeNull()
    {
        $item = $this->cache->getItem('key');
        $item->set(null);
        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue(null === $item->get());
        $this->assertTrue(is_null($item->get()));
        $this->assertTrue($item->isHit(), 'It should be a hit even though the stored value is null.');
    }


    public function testIsHit()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->assertFalse($item->isHit(), 'It can not be a hit before it is saved');
        $this->cache->save($item);
        $this->assertTrue($item->isHit());

        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());
    }

    public function testIsHitDeferred()
    {
        $item = $this->cache->getItem('key');
        $item->set('value');
        $this->cache->saveDeferred($item);
        $this->assertTrue($item->isHit());

        // Test accessing the value before it is committed
        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());

        $this->cache->commit();
        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());
    }
}
