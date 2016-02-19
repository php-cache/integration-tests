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

use Cache\Taggable\TaggablePoolInterface;

abstract class TaggableCachePoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @type array with functionName => reason.
     */
    protected $skippedTests = [];

    /**
     * @type TaggablePoolInterface
     */
    private $cache;

    /**
     * @return TaggablePoolInterface that is used in the tests
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
        $this->cache->clearTags(['tag1']);

        // The item should be removed
        $this->assertFalse($this->cache->hasItem('key'));
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

        $this->cache->clearTags(['tag1']);
        $this->assertFalse($this->cache->hasItem('key1'));
        $this->assertFalse($this->cache->hasItem('key2'));
        $this->assertTrue($this->cache->hasItem('key3'));
        $this->assertTrue($this->cache->hasItem('key4'));

        $this->cache->clearTags(['tag2']);
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

        $item = $this->cache->getItem('key');
        $item->setTags(['tag1', 'tag2']);
        $item->addTag('tag3');

        $tags = $item->getTags();
        $this->assertCount(3, $tags);
        $this->assertTrue(in_array('tag1', $tags));
        $this->assertTrue(in_array('tag2', $tags));
        $this->assertTrue(in_array('tag3', $tags));

        $item->setTags(['tag4']);
        $tags = $item->getTags();
        $this->assertCount(1, $tags);
        $this->assertFalse(in_array('tag1', $tags));
        $this->assertTrue(in_array('tag4', $tags));
    }

    public function testRemoveTagWhenItemIsRemoved()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key');
        $item->setTags(['tag1']);

        // Save the item and then delete it
        $this->cache->save($item);
        $this->cache->deleteItem('key');

        // Create a new item (no tags)
        $item = $this->cache->getItem('key');
        $this->cache->save($item);

        // Clear the tag
        $this->cache->clearTags(['tag1']);
        $this->assertTrue($this->cache->hasItem('key'), 'Item key should be removed from the tag list when the item is removed');
    }

    public function testClear()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        $this->cache->clear();

        // Create a new item (no tags)
        $item = $this->cache->getItem('key');
        $this->cache->save($item);
        $this->cache->clearTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Tags should be removed on clear()');
    }

    public function testClearTag()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);

            return;
        }

        $item = $this->cache->getItem('key');
        $item->setTags(['tag1']);
        $this->cache->save($item);

        $this->cache->clearTags(['tag1']);

        // Create a new item (no tags)
        $item = $this->cache->getItem('key');
        $this->cache->save($item);
        $this->cache->clearTags(['tag1']);

        $this->assertTrue($this->cache->hasItem('key'), 'Item key list should be removed when clearing the tags');
    }
}
