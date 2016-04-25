<?php

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
            $this->client = new \Stash\Driver\Redis(array('servers' => array('127.0.0.1', '6379')));
        }

        return $this->client;
    }
}