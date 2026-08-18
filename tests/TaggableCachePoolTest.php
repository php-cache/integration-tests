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

namespace Cache\IntegrationTests\Tests;

use Cache\IntegrationTests\TaggableCachePoolTest as TaggableCachePoolContract;
use Cache\IntegrationTests\Tests\Fixtures\TaggableCachePool;
use Cache\TagInterop\TaggableCacheItemPoolInterface;
use PHPUnit\Framework\SkippedTest;

final class TaggableCachePoolTest extends TaggableCachePoolContract
{
    public function createCachePool(): TaggableCacheItemPoolInterface
    {
        return new TaggableCachePool();
    }

    public function testDataProviderExposesCases()
    {
        self::assertNotEmpty(self::invalidTags());
    }

    public function testSkippedTestsMapStopsConfiguredTests()
    {
        $tested = 0;
        foreach (get_class_methods(TaggableCachePoolContract::class) as $method) {
            if (!str_starts_with($method, 'test') || (new \ReflectionMethod(TaggableCachePoolContract::class, $method))->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $this->skippedTests[$method] = 'expected skip';
            try {
                $this->{$method}();
                self::fail(\sprintf('%s did not honor the skipped tests map.', $method));
            } catch (SkippedTest $exception) {
                self::assertSame('expected skip', $exception->getMessage());
            }
            unset($this->skippedTests[$method]);
            ++$tested;
        }

        self::assertGreaterThan(0, $tested);
    }
}
