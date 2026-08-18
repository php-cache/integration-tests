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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class HierarchicalCachePool implements CacheItemPoolInterface
{
    private readonly CacheItemPoolInterface $pool;

    /** @var array<non-empty-string, true> */
    private array $keys = [];

    public function __construct()
    {
        $this->pool = new ArrayAdapter();
    }

    public function getItem(string $key): CacheItemInterface
    {
        return $this->pool->getItem($key);
    }

    /** @return iterable<string, CacheItemInterface> */
    public function getItems(array $keys = []): iterable
    {
        return $this->pool->getItems($keys);
    }

    public function hasItem(string $key): bool
    {
        return $this->pool->hasItem($key);
    }

    public function clear(): bool
    {
        $this->keys = [];

        return $this->pool->clear();
    }

    public function deleteItem(string $key): bool
    {
        $key = ValidatingCachePool::validateKey($key);

        if (!str_starts_with($key, '|')) {
            unset($this->keys[$key]);

            return $this->pool->deleteItem($key);
        }

        $keys = array_values(array_filter(
            array_keys($this->keys),
            static fn (string $storedKey): bool => '|' === $key
                ? str_starts_with($storedKey, '|')
                : $storedKey === $key || str_starts_with($storedKey, $key.'|'),
        ));

        foreach ($keys as $storedKey) {
            unset($this->keys[$storedKey]);
        }

        return [] === $keys ? $this->pool->deleteItem($key) : $this->pool->deleteItems($keys);
    }

    public function deleteItems(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            $success = $this->deleteItem($key) && $success;
        }

        return $success;
    }

    public function save(CacheItemInterface $item): bool
    {
        $saved = $this->pool->save($item);
        if ($saved) {
            $key = ValidatingCachePool::validateKey($item->getKey());
            $this->keys[$key] = true;
        }

        return $saved;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $saved = $this->pool->saveDeferred($item);
        if ($saved) {
            $key = ValidatingCachePool::validateKey($item->getKey());
            $this->keys[$key] = true;
        }

        return $saved;
    }

    public function commit(): bool
    {
        return $this->pool->commit();
    }
}
