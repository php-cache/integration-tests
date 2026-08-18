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

namespace Cache\IntegrationTests\Tests\Fixtures;

use Cache\TagInterop\TaggableCacheItemInterface;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;

final class TaggableCachePool implements TaggableCacheItemPoolInterface
{
    private readonly ArrayAdapter $pool;

    /** @var array<string, array<string, string>> */
    private array $tagsByKey = [];

    public function __construct()
    {
        $this->pool = new ArrayAdapter();
    }

    public function getItem(string $key): TaggableCacheItemInterface
    {
        $item = $this->pool->getItem($key);
        $previousTags = $item->isHit() ? ($this->tagsByKey[$key] ?? []) : [];

        return new TaggableCacheItem($item, $previousTags);
    }

    /** @return iterable<string, TaggableCacheItemInterface> */
    public function getItems(array $keys = []): iterable
    {
        foreach ($keys as $key) {
            $item = $this->getItem($key);

            yield $item->getKey() => $item;
        }
    }

    public function hasItem(string $key): bool
    {
        return $this->pool->hasItem($key);
    }

    public function clear(): bool
    {
        $this->tagsByKey = [];

        return $this->pool->clear();
    }

    public function deleteItem(string $key): bool
    {
        unset($this->tagsByKey[$key]);

        return $this->pool->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->tagsByKey[$key]);
        }

        return $this->pool->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        $item = $this->requireTaggableItem($item);
        $saved = $this->pool->save($item->getInnerItem());
        $this->syncTags($item);

        return $saved;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $item = $this->requireTaggableItem($item);
        $saved = $this->pool->saveDeferred($item->getInnerItem());
        if ($saved) {
            $this->tagsByKey[$item->getKey()] = $item->getTags();
        }

        return $saved;
    }

    public function commit(): bool
    {
        return $this->pool->commit();
    }

    public function invalidateTag(string $tag): bool
    {
        return $this->invalidateTags([$tag]);
    }

    public function invalidateTags(array $tags): bool
    {
        $validatedTags = [];
        foreach ($tags as $tag) {
            $tag = ValidatingCachePool::validateKey($tag);
            $validatedTags[$tag] = true;
        }

        $keys = [];
        foreach ($this->tagsByKey as $key => $itemTags) {
            if ([] !== array_intersect_key($itemTags, $validatedTags)) {
                $keys[] = $key;
            }
        }

        return $this->deleteItems($keys);
    }

    private function requireTaggableItem(CacheItemInterface $item): TaggableCacheItem
    {
        if (!$item instanceof TaggableCacheItem) {
            throw new InvalidArgumentException('Items must come from this cache pool.');
        }

        return $item;
    }

    private function syncTags(TaggableCacheItem $item): void
    {
        if ($this->pool->hasItem($item->getKey())) {
            $this->tagsByKey[$item->getKey()] = $item->getTags();

            return;
        }

        unset($this->tagsByKey[$item->getKey()]);
    }
}
