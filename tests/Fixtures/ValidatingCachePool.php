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
use Symfony\Component\Cache\Exception\InvalidArgumentException;

final class ValidatingCachePool implements CacheItemPoolInterface
{
    public function __construct(private readonly CacheItemPoolInterface $pool)
    {
    }

    public function getItem(string $key): CacheItemInterface
    {
        self::validateKey($key);

        return $this->pool->getItem($key);
    }

    /** @return iterable<string, CacheItemInterface> */
    public function getItems(array $keys = []): iterable
    {
        self::validateKeys($keys);

        return $this->pool->getItems($keys);
    }

    public function hasItem(string $key): bool
    {
        self::validateKey($key);

        return $this->pool->hasItem($key);
    }

    public function clear(): bool
    {
        return $this->pool->clear();
    }

    public function deleteItem(string $key): bool
    {
        self::validateKey($key);

        return $this->pool->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        self::validateKeys($keys);

        return $this->pool->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->pool->save($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->pool->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->pool->commit();
    }

    /** @return non-empty-string */
    public static function validateKey(mixed $key): string
    {
        if (!\is_string($key) || '' === $key || 1 === preg_match('~[{}()/\\\\@:]~', $key)) {
            throw new InvalidArgumentException('Cache keys must be non-empty strings without reserved characters.');
        }

        return $key;
    }

    /** @param array<mixed> $keys */
    private static function validateKeys(array $keys): void
    {
        foreach ($keys as $key) {
            self::validateKey($key);
        }
    }
}
