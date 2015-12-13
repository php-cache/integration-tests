<?php

namespace Cache\IntegrationTests\Helper;

use Cache\Doctrine\CacheItem;
use Cache\Taggable\TaggablePoolTrait;
use Psr\Cache\CacheItemInterface;

/**
 * A cache pool used in tests
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CachePool
{
    use TaggablePoolTrait;

    private $cache;

    public function getItem($key, array $tags = array())
    {
        $taggedKey = $this->generateCacheKey($key, $tags);

        return $this->getTagItem($taggedKey);
    }

    protected function getTagItem($key)
    {
        if (isset($this->cache[$key])) {
            $item = $this->cache[$key];
        } else {
            $item = new CacheItem($key);
        }

        return $item;
    }


    public function save(CacheItemInterface $item)
    {
        $this->cache[$item->getKey()]=$item;
        return true;
    }

    public function exposeGenerateCacheKey($key, array $tags)
    {
        return $this->generateCacheKey($key, $tags);
    }

    public function exposeFlushTag($name)
    {
        return $this->flushTag($name);
    }
}