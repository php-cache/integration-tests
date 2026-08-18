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
use Psr\Cache\CacheItemInterface;

final class TaggableCacheItem implements TaggableCacheItemInterface
{
    /** @var array<string, string> */
    private array $tags;

    /**
     * @param array<string, string> $previousTags
     */
    public function __construct(
        private readonly CacheItemInterface $item,
        private readonly array $previousTags,
    ) {
        $this->tags = $previousTags;
    }

    public function getKey(): string
    {
        return $this->item->getKey();
    }

    public function get(): mixed
    {
        return $this->item->get();
    }

    public function isHit(): bool
    {
        return $this->item->isHit();
    }

    public function set(mixed $value): static
    {
        $this->item->set($value);

        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->item->expiresAt($expiration);

        return $this;
    }

    public function expiresAfter(\DateInterval|int|null $time): static
    {
        $this->item->expiresAfter($time);

        return $this;
    }

    public function getPreviousTags(): array
    {
        return $this->previousTags;
    }

    public function setTags(array $tags): static
    {
        $validatedTags = [];
        foreach ($tags as $tag) {
            $tag = ValidatingCachePool::validateKey($tag);
            $validatedTags[$tag] = $tag;
        }
        $this->tags = $validatedTags;

        return $this;
    }

    public function getInnerItem(): CacheItemInterface
    {
        return $this->item;
    }

    /** @return array<string, string> */
    public function getTags(): array
    {
        return $this->tags;
    }
}
