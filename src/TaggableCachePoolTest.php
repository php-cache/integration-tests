<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\IntegrationTests;

use Cache\Adapter\Common\TaggableCacheItemPoolInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
abstract class TaggableCachePoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type array with functionName => reason.
     */
    protected $skippedTests = [];

    /**
     * @type TaggableCacheItemPoolInterface
     */
    private $cache;

    /**
     * @return TaggableCacheItemPoolInterface that is used in the tests
     */
    abstract public function createCachePool();

    protected function setUp()
    {
        $this->cache = $this->createCachePool();
    }

    protected function tearDown()
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

        $item = $this->cache->getItem('key');
        $item->set('value');
        $item->setTags(['tag1', 'tag2']);
        $this->cache->save($item);

        // The item should be saved
        $this->assertTrue($this->cache->hasItem('key'));

        // I want to clear all post by author
        $this->cache->invalidateTags(['tag1']);

        // The item should be removed
        $this->assertFalse($this->cache->hasItem('key'), 'Tags does not seams to be saved');
    }

    public function testMultipleTags()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $this->cache->save($this->cache->getItem('key1')->set('value')->setTags(['tag1', 'tag2']));
        $this->cache->save($this->cache->getItem('key2')->set('value')->setTags(['tag1', 'tag3']));
        $this->cache->save($this->cache->getItem('key3')->set('value')->setTags(['tag2', 'tag3']));
        $this->cache->save($this->cache->getItem('key4')->set('value')->setTags(['tag4', 'tag3']));

        $this->cache->invalidateTags(['tag1']);
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));
        $this->assertTrue($this->cache->hasItem('key3'));
        $this->assertTrue($this->cache->hasItem('key4'));

        $this->cache->invalidateTags(['tag2']);
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));
        $this->assertFalse($this->cache->hasItem('key3'));
        $this->assertTrue($this->cache->hasItem('key4'));
    }

    public function testTagAccessor()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $this->assertCount(0, $item->getTags());

        $item->addTag('tag0');
        $this->assertCount(1, $item->getTags());

        $item->setTags(['tag1', 'tag2']);
        $this->assertCount(2, $item->getTags());
        $tags = $item->getTags();
        $this->assertTrue(in_array('tag1', $tags));
        $this->assertTrue(in_array('tag2', $tags));

        $item->addTags(['tag3', 'tag4']);
        $this->assertCount(4, $item->getTags());
        $tags = $item->getTags();
        $this->assertTrue(in_array('tag4', $tags));
        $this->assertTrue(in_array('tag3', $tags));
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testTagAccessorWithNoString()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->addTag(new \stdClass());
        $this->cache->save($item);
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testTagAccessorWithEmptyTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->addTag('');
        $this->cache->save($item);
    }

    /**
     * @expectedException \Psr\Cache\InvalidArgumentException
     */
    public function testTagAccessorWithInvalidTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->addTag('@foo@');
        $this->cache->save($item);
    }

    public function testTagAccessorDuplicateTags()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->addTag('tag');
        $this->cache->save($item);
        $item->addTag('tag');
        $this->cache->save($item);
        $item->addTag('tag');
        $this->cache->save($item);

        $this->assertCount(1, $item->getTags());
    }

    /**
     * The tag must be removed whenever we remove an item. If not, when creating a new item
     * with the same key will get the same tags.
     */
    public function testRemoveTagWhenItemIsRemoved()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1']);

        // Save the item and then delete it
        $this->cache->save($item);
        $this->cache->deleteItem('key');

        // Create a new item (same key) (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);

        // Clear the tag, The new item should not be cleared
        $this->cache->invalidateTags(['tag1']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key should be removed from the tag list when the item is removed');
    }

    public function testClearPool()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        // Clear the pool
        $this->cache->clear();

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Tags should be removed when the pool was cleared.');
    }

    public function testInvalidateTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1', 'tag2']);
        $this->cache->save($item);
        $item = $this->cache->getItem('key2')->set('value');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        $this->cache->invalidateTag('tag2');
        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertTrue($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');
    }

    public function testInvalidateTags()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key')->set('value');
        $item->setTags(['tag1', 'tag2']);
        $this->cache->save($item);
        $item = $this->cache->getItem('key2')->set('value');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        $this->cache->invalidateTags(['tag1', 'tag2']);
        $this->assertFalse($this->cache->hasItem('key'), 'Item should be cleared when tag is invalidated');
        $this->assertFalse($this->cache->hasItem('key2'), 'Item should be cleared when tag is invalidated');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key')->set('value');
        $this->cache->save($item);
        $this->cache->invalidateTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');
    }
}
