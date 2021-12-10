<?php

declare(strict_types=1);

namespace Mikrofraim\ServiceProvider;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Adapter\PHPArray\ArrayCachePool;
use Cache\Adapter\Redis\RedisCachePool;
use Cache\Adapter\Void\VoidCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Mikrofraim\ServiceProvider;
use Psr\SimpleCache\CacheInterface;

class SimpleCacheProvider extends ServiceProvider
{
    public function createService(): CacheInterface
    {
        $cacheAdapter = $this->config->get(
            'service.cache.adapter',
            'void',
        );

        switch ($cacheAdapter) {
            case 'redis':
                $host = $this->config->get(
                    'service.cache.adapters.redis.hostname',
                    '127.0.0.1',
                );

                $port = $this->config->get(
                    'service.cache.adapters.redis.port',
                    6379,
                );

                $timeout = $this->config->get(
                    'service.cache.adapters.redis.timeout',
                    0,
                );

                $auth = $this->config->get(
                    'service.cache.adapters.redis.auth',
                );

                try {
                    $client = new \Redis();
                } catch (\Exception $e) {
                    throw new \Exception('could not create instance of Redis: ' . $e->getMessage());
                }

                try {
                    $client->connect($host, $port, $timeout);
                } catch (\RedisException $e) {
                    throw new \Exception('could not connect to redis: ' . $e->getMessage());
                }

                if ($auth) {
                    if ($client->auth($auth) === false) {
                        throw new \Exception('redis authentication failed: ' . $client->getLastError());
                    }
                }

                $pool = new RedisCachePool($client);

                break;
            case 'filesystem':
                $cachePath = \sprintf(
                    '%s/%s',
                    $this->config->get('service.cache.adapters.filesystem.root'),
                    $this->config->get('service.cache.adapters.filesystem.directory'),
                );

                if ('/' === $cachePath) {
                    throw new \Exception('filesystem cache path not specified in config');
                }

                $pool = new FilesystemCachePool(
                    new Filesystem(new Local($cachePath)),
                );

                break;
            case 'array':
                $pool = new ArrayCachePool();

                break;
            case 'void':
                $pool = new VoidCachePool();

                break;

            default:
                throw new \Exception('unknown cache adapter: ' . $cacheAdapter);
        }

        return new SimpleCacheBridge($pool);
    }
}
