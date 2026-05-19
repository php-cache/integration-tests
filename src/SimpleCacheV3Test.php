<?php

declare(strict_types=1);

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Cache\IntegrationTests;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PSR-16 integration test suite for implementations using psr/simple-cache ^3.0.
 *
 * PSR-16 v3 introduced strict PHP type hints (string $key, iterable $keys,
 * null|int|DateInterval $ttl). These type hints cause PHP to throw \TypeError
 * before the library code ever runs when clearly-invalid argument types are
 * passed. This makes many of the original SimpleCacheTest data providers
 * incompatible with v3.
 *
 * This subclass:
 * - Filters data providers to only values that survive strict/coercion checks
 * - Accepts both \TypeError (PHP-level rejection) and
 *   \Psr\SimpleCache\InvalidArgumentException (library-level validation) as
 *   valid failure modes
 * - Is fully backward-compatible: existing v1/v2 consumers keep using
 *   SimpleCacheTest; v3 consumers switch to SimpleCacheV3Test
 *
 * @see \Cache\IntegrationTests\SimpleCacheTest
 */
abstract class SimpleCacheV3Test extends SimpleCacheTest
{
    /**
     * Data provider for invalid array keys in setMultiple.
     *
     * Contains only string keys that are invalid per PSR-16 spec.
     * Non-string key types (bool, null, float, int, object, array) are removed
     * because PSR-16 v3's string type hint rejects them at PHP level before the
     * implementation is invoked.
     *
     * @return list<list<string>>
     */
    public static function invalidArrayKeys()
    {
        return [
            [''],
            ['{str'],
            ['rand{'],
            ['rand{str'],
            ['rand}str'],
            ['rand(str'],
            ['rand)str'],
            ['rand/str'],
            ['rand\\str'],
            ['rand@str'],
            ['rand:str'],
        ];
    }

    /**
     * Data provider for invalid cache keys.
     *
     * Keeps the parent's structure (array_merge around invalidArrayKeys) but
     * adds no extra items, since the parent's extra `[2]` is an integer and
     * PSR-16 v3's string type hint rejects it at PHP level.
     *
     * @return list<list<string>>
     */
    public static function invalidKeys()
    {
        return array_merge(
            self::invalidArrayKeys(),
            []
        );
    }

    /**
     * Data provider for invalid TTL values.
     *
     * PSR-16 v3 accepts only null|int|DateInterval for TTL. Values that coerce
     * to valid types in PHP weak mode (e.g. true→1, false→0, ' 1'→1, '025'→25)
     * are removed because they would not exercise invalid-TTL handling.
     *
     * @return list<list<mixed>>
     */
    public static function invalidTtl()
    {
        return [
            [''],
            ['abc'],
            ['12foo'],
            [new \stdClass()],
            [['array']],
        ];
    }

    /**
     * Helper that runs the supplied callable inside a try/catch.
     *
     * With PSR-16 v3 strict type hints a non-string key, non-iterable iterable,
     * or invalid TTL will cause a \TypeError before the library sees the data.
     * The PSR-16 spec says a key MUST be a string, so that is consistent.
     * When the implementation is also typed we accept TypeError as a valid
     * failure indicator for clearly-invalid arguments.
     *
     * @param callable(): void $callable
     */
    private function assertCacheExceptionOrTypeError(callable $callable): void
    {
        try {
            $callable();
            $this->fail('Expected exception to be thrown.');
        } catch (\TypeError $e) {
            // PSR-16 v3 typed interfaces throw TypeError for clearly-invalid
            // argument types (e.g. null key, object key, string for iterable).
            $this->assertTrue(true);
        } catch (\Psr\SimpleCache\InvalidArgumentException $e) {
            // Explicit library-level validation.
            $this->assertTrue(true);
        }
    }

    /**
     * @dataProvider invalidKeys
     */
    #[DataProvider('invalidKeys')]
    public function testGetInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($key) {
            $this->cache->get($key);
        });
    }

    /**
     * @dataProvider invalidKeys
     */
    #[DataProvider('invalidKeys')]
    public function testGetMultipleInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($key) {
            $this->cache->getMultiple(['key1', $key, 'key2']);
        });
    }

    public function testGetMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () {
            $this->cache->getMultiple('key');
        });
    }

    /**
     * @dataProvider invalidKeys
     */
    #[DataProvider('invalidKeys')]
    public function testSetInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($key) {
            $this->cache->set($key, 'foobar');
        });
    }

    /**
     * @dataProvider invalidArrayKeys
     */
    #[DataProvider('invalidArrayKeys')]
    public function testSetMultipleInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($key) {
            $values = function () use ($key) {
                yield 'key1' => 'foo';
                yield $key => 'bar';
                yield 'key2' => 'baz';
            };
            $this->cache->setMultiple($values());
        });
    }

    public function testSetMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () {
            $this->cache->setMultiple('key');
        });
    }

    /**
     * @dataProvider invalidKeys
     */
    #[DataProvider('invalidKeys')]
    public function testHasInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($key) {
            $this->cache->has($key);
        });
    }

    /**
     * @dataProvider invalidKeys
     */
    #[DataProvider('invalidKeys')]
    public function testDeleteInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($key) {
            $this->cache->delete($key);
        });
    }

    /**
     * @dataProvider invalidKeys
     */
    #[DataProvider('invalidKeys')]
    public function testDeleteMultipleInvalidKeys($key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($key) {
            $this->cache->deleteMultiple(['key1', $key, 'key2']);
        });
    }

    public function testDeleteMultipleNoIterable()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () {
            $this->cache->deleteMultiple('key');
        });
    }

    /**
     * @dataProvider invalidTtl
     */
    #[DataProvider('invalidTtl')]
    public function testSetInvalidTtl($ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($ttl) {
            $this->cache->set('key', 'value', $ttl);
        });
    }

    /**
     * @dataProvider invalidTtl
     */
    #[DataProvider('invalidTtl')]
    public function testSetMultipleInvalidTtl($ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(function () use ($ttl) {
            $this->cache->setMultiple(['key' => 'value'], $ttl);
        });
    }

    /**
     * Tests the PSR-16 mandated minimum key length of 64 characters.
     *
     * PSR-16 explicitly states: "The key length MUST be at least 64 characters."
     * This test ensures every compliant implementation supports at least 64 chars.
     *
     * @see https://www.php-fig.org/psr/psr-16/
     */
    public function testBasicUsageWithLongKey64()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $key = str_repeat('a', 64);

        $this->assertFalse($this->cache->has($key));
        $this->assertTrue($this->cache->set($key, 'value'));

        $this->assertTrue($this->cache->has($key));
        $this->assertSame('value', $this->cache->get($key));

        $this->assertTrue($this->cache->delete($key));

        $this->assertFalse($this->cache->has($key));
    }
}
