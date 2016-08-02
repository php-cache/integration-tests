<?php

/*
 * This file is part of php-cache organization.
 *
 * (c) 2015-2015 Aaron Scherer <aequasi@gmail.com>, Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Cache\IntegrationTests\CachePoolTest as BaseTest;

class StashTest extends BaseTest
{
    private $client = null;

    public function createCachePool()
    {
        return new \Stash\Pool($this->getClient());
    }

    private function getClient()
    {
        if ($this->client === null) {
            $this->client = new \Stash\Driver\Redis(['servers' => [['server' => '127.0.0.1', 'port' => '6379']]]);
        }

        return $this->client;
    }
}
