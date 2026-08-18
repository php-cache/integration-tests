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

namespace Cache\IntegrationTests;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

abstract class SimpleCacheTest extends TestCase
{
    /** @var array<string, string> */
    protected array $skippedTests = [];

    protected CacheInterface $cache;

    /**
     * @return CacheInterface that is used in the tests
     */
    abstract public function createSimpleCache(): CacheInterface;

    /**
     * Advance time perceived by the cache for the purposes of testing TTL.
     *
     * The default implementation sleeps for the specified duration,
     * but subclasses are encouraged to override this,
     * adjusting a mocked time possibly set up in {@link createSimpleCache()},
     * to speed up the tests.
     */
    public function advanceTime(int $seconds)
    {
        sleep($seconds);
    }

    #[Before]
    public function setupService()
    {
        $this->cache = $this->createSimpleCache();
    }

    #[After]
    public function tearDownService()
    {
        if (isset($this->cache)) {
            $this->cache->clear();
        }
    }

    /** @param callable(): mixed $callable */
    private function assertCacheExceptionOrTypeError(callable $callable): void
    {
        try {
            $callable();
        } catch (\TypeError|InvalidArgumentException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('Expected TypeError or PSR InvalidArgumentException.');
    }

    private function callWithInvalidArguments(callable $callable, mixed ...$arguments): mixed
    {
        return $callable(...$arguments);
    }

    /**
     * Data provider for invalid cache keys.
     *
     * @return list<array{string}>
     */
    public static function invalidKeys(): array
    {
        return self::invalidArrayKeys();
    }

    /**
     * Data provider for invalid array keys.
     *
     * @return list<array{string}>
     */
    public static function invalidArrayKeys(): array
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

    /** @return list<array{mixed}> */
    public static function invalidKeyTypes(): array
    {
        return [
            [true],
            [false],
            [null],
            [2],
            [2.5],
            [new \stdClass()],
            [['array']],
        ];
    }

    /** @return list<array{mixed}> */
    public static function nonIterableValues(): array
    {
        return [
            ['key'],
            [true],
            [false],
            [null],
            [2],
            [2.5],
            [new \stdClass()],
        ];
    }

    /** @return list<array{mixed}> */
    public static function invalidTtlValues(): array
    {
        return [
            [''],
            [true],
            [false],
            ['abc'],
            [2.5],
            [' 1'],
            ['12foo'],
            ['025'],
            [new \stdClass()],
            [['array']],
        ];
    }

    /** @return list<array{mixed}> */
    public static function invalidSetMultipleKeyTypes(): array
    {
        return [
            [true],
            [false],
            [null],
            [2.5],
            [new \stdClass()],
            [['array']],
        ];
    }

    /**
     * Data provider for valid keys.
     *
     * @return list<array{string}>
     */
    public static function validKeys(): array
    {
        return [
            ['AbC19_.'],
            ['1234567890123456789012345678901234567890123456789012345678901234'],
        ];
    }

    /**
     * Data provider for valid data to store.
     *
     * @return list<array{mixed}>
     */
    public static function validData(): array
    {
        return [
            ['AbC19_.'],
            [4711],
            [47.11],
            [true],
            [null],
            [['key' => 'value']],
            [new \stdClass()],
        ];
    }

    public function testSet()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->set('key', 'value');
        $this->assertTrue($result, 'set() must return true if success');
        $this->assertEquals('value', $this->cache->get('key'));
    }

    #[Group('slow')]
    public function testSetTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->set('key1', 'value', 2);
        $this->assertTrue($result, 'set() must return true if success');
        $this->assertEquals('value', $this->cache->get('key1'));

        $this->cache->set('key2', 'value', new \DateInterval('PT2S'));
        $this->assertEquals('value', $this->cache->get('key2'));

        $this->advanceTime(3);

        $this->assertNull($this->cache->get('key1'), 'Value must expire after ttl.');
        $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
    }

    public function testSetExpiredTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key0', 'value');
        $this->cache->set('key0', 'value', 0);
        $this->assertNull($this->cache->get('key0'));
        $this->assertFalse($this->cache->has('key0'));

        $this->cache->set('key1', 'value', -1);
        $this->assertNull($this->cache->get('key1'));
        $this->assertFalse($this->cache->has('key1'));
    }

    public function testGet()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertNull($this->cache->get('key'));
        $this->assertEquals('foo', $this->cache->get('key', 'foo'));

        $this->cache->set('key', 'value');
        $this->assertEquals('value', $this->cache->get('key', 'foo'));
    }

    public function testDelete()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue($this->cache->delete('key'), 'Deleting a value that does not exist should return true');
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->delete('key'), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key'), 'Values must be deleted on delete()');
    }

    public function testClear()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue($this->cache->clear(), 'Clearing an empty cache should return true');
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->clear(), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key'), 'Values must be deleted on clear()');
    }

    public function testSetMultiple()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->setMultiple(['key0' => 'value0', 'key1' => 'value1']);
        $this->assertTrue($result, 'setMultiple() must return true if success');
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testSetMultipleWithIntegerArrayKey()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->setMultiple(['0' => 'value0']);
        $this->assertTrue($result, 'setMultiple() must return true if success');
        $this->assertEquals('value0', $this->cache->get('0'));
    }

    #[Group('slow')]
    public function testSetMultipleTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple(['key2' => 'value2', 'key3' => 'value3'], 2);
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));

        $this->cache->setMultiple(['key4' => 'value4'], new \DateInterval('PT2S'));
        $this->assertEquals('value4', $this->cache->get('key4'));

        $this->advanceTime(3);
        $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
        $this->assertNull($this->cache->get('key3'), 'Value must expire after ttl.');
        $this->assertNull($this->cache->get('key4'), 'Value must expire after ttl.');
    }

    public function testSetMultipleExpiredTtl()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple(['key0' => 'value0', 'key1' => 'value1'], 0);
        $this->assertNull($this->cache->get('key0'));
        $this->assertNull($this->cache->get('key1'));
    }

    public function testSetMultipleWithGenerator()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 'key0' => 'value0';
            yield 'key1' => 'value1';
        };

        $this->cache->setMultiple($gen());
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testGetMultiple()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->getMultiple(['key0', 'key1']);
        $keys = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertNull($r);
        }
        sort($keys);
        $this->assertSame(['key0', 'key1'], $keys);

        $this->cache->set('key3', 'value');
        $result = $this->cache->getMultiple(['key2', 'key3', 'key4'], 'foo');
        $keys = [];
        foreach ($result as $key => $r) {
            $keys[] = $key;
            if ('key3' === $key) {
                $this->assertEquals('value', $r);
            } else {
                $this->assertEquals('foo', $r);
            }
        }
        sort($keys);
        $this->assertSame(['key2', 'key3', 'key4'], $keys);
    }

    public function testGetMultiplePreservesNumericStringKeys()
    {
        $count = 0;
        foreach ($this->cache->getMultiple(['123']) as $key => $value) {
            $this->assertSame('123', $key);
            $this->assertNull($value);
            ++$count;
        }

        $this->assertSame(1, $count);
    }

    public function testGetMultipleWithGenerator()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 1 => 'key0';
            yield 1 => 'key1';
        };

        $this->cache->set('key0', 'value0');
        $result = $this->cache->getMultiple($gen());
        $keys = [];
        foreach ($result as $key => $r) {
            $keys[] = $key;
            if ('key0' === $key) {
                $this->assertEquals('value0', $r);
            } elseif ('key1' === $key) {
                $this->assertNull($r);
            } else {
                $this->fail('Unexpected cache key returned.');
            }
        }
        sort($keys);
        $this->assertSame(['key0', 'key1'], $keys);
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertNull($this->cache->get('key1'));
    }

    public function testDeleteMultiple()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue($this->cache->deleteMultiple([]), 'Deleting a empty array should return true');
        $this->assertTrue($this->cache->deleteMultiple(['key']), 'Deleting a value that does not exist should return true');

        $this->cache->set('key0', 'value0');
        $this->cache->set('key1', 'value1');
        $this->assertTrue($this->cache->deleteMultiple(['key0', 'key1']), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key0'), 'Values must be deleted on deleteMultiple()');
        $this->assertNull($this->cache->get('key1'), 'Values must be deleted on deleteMultiple()');
    }

    public function testDeleteMultipleGenerator()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 1 => 'key0';
            yield 1 => 'key1';
        };
        $this->cache->set('key0', 'value0');
        $this->assertTrue($this->cache->deleteMultiple($gen()), 'Deleting a generator should return true');

        $this->assertNull($this->cache->get('key0'), 'Values must be deleted on deleteMultiple()');
        $this->assertNull($this->cache->get('key1'), 'Values must be deleted on deleteMultiple()');
    }

    public function testHas()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertFalse($this->cache->has('key0'));
        $this->cache->set('key0', 'value0');
        $this->assertTrue($this->cache->has('key0'));
    }

    public function testBasicUsageWithLongKey()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $key = str_repeat('a', 300);

        $this->assertFalse($this->cache->has($key));
        $this->assertTrue($this->cache->set($key, 'value'));

        $this->assertTrue($this->cache->has($key));
        $this->assertSame('value', $this->cache->get($key));

        $this->assertTrue($this->cache->delete($key));

        $this->assertFalse($this->cache->has($key));
    }

    #[DataProvider('invalidKeys')]
    public function testGetInvalidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->get($key);
    }

    #[DataProvider('invalidKeyTypes')]
    public function testGetInvalidKeyTypes(mixed $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'get'], $key)
        );
    }

    #[DataProvider('invalidKeys')]
    public function testGetMultipleInvalidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->getMultiple(['key1', $key, 'key2']);
    }

    #[DataProvider('invalidKeyTypes')]
    public function testGetMultipleInvalidKeyTypes(mixed $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'getMultiple'], ['key1', $key, 'key2'])
        );
    }

    #[DataProvider('nonIterableValues')]
    public function testGetMultipleNonIterable(mixed $keys)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'getMultiple'], $keys)
        );
    }

    #[DataProvider('invalidKeys')]
    public function testSetInvalidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->set($key, 'foobar');
    }

    #[DataProvider('invalidKeyTypes')]
    public function testSetInvalidKeyTypes(mixed $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'set'], $key, 'foobar')
        );
    }

    #[DataProvider('invalidArrayKeys')]
    public function testSetMultipleInvalidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $values = function () use ($key) {
            yield 'key1' => 'foo';
            yield $key => 'bar';
            yield 'key2' => 'baz';
        };
        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->setMultiple($values());
    }

    #[DataProvider('invalidSetMultipleKeyTypes')]
    public function testSetMultipleInvalidKeyTypes(mixed $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $values = static function () use ($key): \Generator {
            yield 'key1' => 'foo';
            yield $key => 'bar';
            yield 'key2' => 'baz';
        };

        $this->assertCacheExceptionOrTypeError(fn (): bool => $this->cache->setMultiple($values()));
    }

    #[DataProvider('nonIterableValues')]
    public function testSetMultipleNonIterable(mixed $values)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'setMultiple'], $values)
        );
    }

    #[DataProvider('invalidKeys')]
    public function testHasInvalidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->has($key);
    }

    #[DataProvider('invalidKeyTypes')]
    public function testHasInvalidKeyTypes(mixed $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'has'], $key)
        );
    }

    #[DataProvider('invalidKeys')]
    public function testDeleteInvalidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->delete($key);
    }

    #[DataProvider('invalidKeyTypes')]
    public function testDeleteInvalidKeyTypes(mixed $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'delete'], $key)
        );
    }

    #[DataProvider('invalidKeys')]
    public function testDeleteMultipleInvalidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->deleteMultiple(['key1', $key, 'key2']);
    }

    #[DataProvider('invalidKeyTypes')]
    public function testDeleteMultipleInvalidKeyTypes(mixed $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'deleteMultiple'], ['key1', $key, 'key2'])
        );
    }

    #[DataProvider('nonIterableValues')]
    public function testDeleteMultipleNonIterable(mixed $keys)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'deleteMultiple'], $keys)
        );
    }

    #[DataProvider('invalidTtlValues')]
    public function testSetInvalidTtl(mixed $ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'set'], 'key', 'value', $ttl)
        );
    }

    #[DataProvider('invalidTtlValues')]
    public function testSetMultipleInvalidTtl(mixed $ttl)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertCacheExceptionOrTypeError(
            fn (): mixed => $this->callWithInvalidArguments([$this->cache, 'setMultiple'], ['key' => 'value'], $ttl)
        );
    }

    public function testNullOverwrite()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', 5);
        $this->cache->set('key', null);

        $this->assertNull($this->cache->get('key'), 'Setting null to a key must overwrite previous value');
    }

    public function testDataTypeString()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', '5');
        $result = $this->cache->get('key');
        $this->assertTrue('5' === $result, 'Wrong data type. If we store a string we must get an string back.');
    }

    public function testDataTypeInteger()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', 5);
        $result = $this->cache->get('key');
        $this->assertTrue(5 === $result, 'Wrong data type. If we store an int we must get an int back.');
    }

    public function testDataTypeFloat()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $float = 1.23456789;
        $this->cache->set('key', $float);
        $result = $this->cache->get('key');
        $this->assertTrue(\is_float($result), 'Wrong data type. If we store float we must get an float back.');
        $this->assertEquals($float, $result);
    }

    public function testDataTypeBoolean()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', false);
        $result = $this->cache->get('key');
        $this->assertTrue(\is_bool($result), 'Wrong data type. If we store boolean we must get an boolean back.');
        $this->assertFalse($result);
        $this->assertTrue($this->cache->has('key'), 'has() should return true when true are stored. ');
    }

    public function testDataTypeArray()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $array = ['a' => 'foo', 2 => 'bar'];
        $this->cache->set('key', $array);
        $result = $this->cache->get('key');
        $this->assertTrue(\is_array($result), 'Wrong data type. If we store array we must get an array back.');
        $this->assertEquals($array, $result);
    }

    public function testDataTypeObject()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $object = new \stdClass();
        $object->a = 'foo';
        $this->cache->set('key', $object);
        $result = $this->cache->get('key');
        $this->assertTrue(\is_object($result), 'Wrong data type. If we store object we must get an object back.');
        $this->assertEquals($object, $result);
    }

    public function testBinaryData()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $data = '';
        for ($i = 0; $i < 256; ++$i) {
            $data .= \chr($i);
        }

        $array = ['a' => 'foo', 2 => 'bar'];
        $this->cache->set('key', $data);
        $result = $this->cache->get('key');
        $this->assertTrue($data === $result, 'Binary data must survive a round trip.');
    }

    #[DataProvider('validKeys')]
    public function testSetValidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set($key, 'foobar');
        $this->assertEquals('foobar', $this->cache->get($key));
    }

    #[DataProvider('validKeys')]
    public function testSetMultipleValidKeys(string $key)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple([$key => 'foobar']);
        $result = $this->cache->getMultiple([$key]);
        $keys = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertEquals($key, $i);
            $this->assertEquals('foobar', $r);
        }
        $this->assertSame([$key], $keys);
    }

    #[DataProvider('validData')]
    public function testSetValidData(mixed $data)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', $data);
        $this->assertEquals($data, $this->cache->get('key'));
    }

    #[DataProvider('validData')]
    public function testSetMultipleValidData(mixed $data)
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple(['key' => $data]);
        $result = $this->cache->getMultiple(['key']);
        $keys = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertEquals($data, $r);
        }
        $this->assertSame(['key'], $keys);
    }

    public function testObjectAsDefaultValue()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $obj = new \stdClass();
        $obj->foo = 'value';
        $this->assertEquals($obj, $this->cache->get('key', $obj));
    }

    public function testObjectDoesNotChangeInCache()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $obj = new \stdClass();
        $obj->foo = 'value';
        $this->cache->set('key', $obj);
        $obj->foo = 'changed';

        $cacheObject = $this->cache->get('key');
        $this->assertInstanceOf(\stdClass::class, $cacheObject);
        $this->assertEquals('value', $cacheObject->foo, 'Object in cache should not have their values changed.');
    }
}
