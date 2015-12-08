<?php

namespace Cache\IntegrationTests\Tests;

use Cache\Doctrine\CachePool;
use Cache\Taggable\TaggablePoolInterface;
use Doctrine\Common\Cache\MemcachedCache;
use Psr\Cache\CacheItemPoolInterface;

abstract class TaggableCachePoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CacheItemPoolInterface|TaggablePoolInterface
     */
    private $cache;

    /**
     * @return TaggablePoolInterface that is used in the tests
     */
    abstract function createCachePool();

    /**
     *
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


        // Remove everything tagged with 'nice guy'
        $this->cache->clear(['nice guy']);
        $this->assertTrue($this->cache->getItem('tobias', ['developer', 'speaker'])->isHit());
        $this->assertFalse($this->cache->getItem('aaron', ['developer', 'nice guy'])->isHit());
        $this->assertFalse($this->cache->getItem('the king of Sweden', ['nice guy', 'king'])->isHit());

        // To clear everything you do as you usually do
        $this->cache->clear();
        $this->assertFalse($this->cache->getItem('tobias', ['developer', 'speaker'])->isHit());
    }

    /**
     * Make sure we dont get conflicts with the tag key generation
     */
    public function testKeyGeneration()
    {
        $item1 = $this->cache->getItem('tobias', ['developer', 'speaker']);
        $item1->set('foobar');
        $this->cache->save($item1);

        $item2 = $this->cache->getItem('tag:speaker:key', []);
        $this->assertFalse($item2->isHit());
    }

}