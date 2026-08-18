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

use Psr\SimpleCache\CacheInterface;

final class ValidatingSimpleCache implements CacheInterface
{
    public function __construct(private readonly CacheInterface $cache)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        ValidatingCachePool::validateKey($key);

        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        ValidatingCachePool::validateKey($key);

        return $this->cache->set($key, $value, $ttl);
    }

    public function delete(string $key): bool
    {
        ValidatingCachePool::validateKey($key);

        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    /**
     * @param iterable<mixed, mixed> $keys
     *
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $validatedKeys = iterator_to_array($this->validateKeys($keys), false);

        return $this->restoreKeys($this->cache->getMultiple($validatedKeys, $default), $validatedKeys, $default);
    }

    /** @param iterable<array-key, mixed> $values */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        return $this->cache->setMultiple($this->validateValues($values), $ttl);
    }

    /** @param iterable<mixed, mixed> $keys */
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->cache->deleteMultiple($this->validateKeys($keys));
    }

    public function has(string $key): bool
    {
        ValidatingCachePool::validateKey($key);

        return $this->cache->has($key);
    }

    /**
     * @param iterable<mixed, mixed> $keys
     *
     * @return iterable<int, string>
     */
    private function validateKeys(iterable $keys): iterable
    {
        foreach ($keys as $key) {
            yield ValidatingCachePool::validateKey($key);
        }
    }

    /**
     * @param iterable<array-key, mixed> $values
     *
     * @return iterable<string|int, mixed>
     */
    private function validateValues(iterable $values): iterable
    {
        foreach ($values as $key => $value) {
            $validatedKey = \is_int($key) ? (string) $key : ValidatingCachePool::validateKey($key);

            yield $validatedKey => $value;
        }
    }

    /**
     * @param iterable<array-key, mixed> $values
     * @param list<string>               $keys
     *
     * @return iterable<string, mixed>
     */
    private function restoreKeys(iterable $values, array $keys, mixed $default): iterable
    {
        $valuesByKey = [];
        foreach ($values as $key => $value) {
            $valuesByKey["\0".(string) $key] = $value;
        }

        foreach ($keys as $key) {
            $lookupKey = "\0".$key;

            yield $key => \array_key_exists($lookupKey, $valuesByKey) ? $valuesByKey[$lookupKey] : $default;
        }
    }
}
