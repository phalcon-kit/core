<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Provider\Redis;

use Phalcon\Di\DiInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use Redis;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'redis';

    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {

            $config = $di->get('config');
            assert($config instanceof ConfigInterface);

            $redisConfig = $config->pathToArray('redis') ?? [];
            $redisOptions = $redisConfig['options'] ?? [];

            $redisConfig['host'] ??= '127.0.0.1';
            $redisConfig['port'] ??= 6379;
            $redisConfig['timeout'] ??= 0.0;
            $redisConfig['persistentId'] ??= null;
            $redisConfig['retryInterval'] ??= 0;
            $redisConfig['readTimeout'] ??= 0.0;
            $redisConfig['context'] ??= null;

            $redis = new Redis($redisOptions);

            if (!$redis->connect(
                $redisConfig['host'],
                $redisConfig['port'],
                $redisConfig['timeout'],
                $redisConfig['persistentId'],
                $redisConfig['retryInterval'],
                $redisConfig['readTimeout'],
                $redisConfig['context']
            )) {
                throw new \RedisException('Redis connection failed.');
            }

            if (!empty($redisConfig['auth']) && !$redis->auth($redisConfig['auth'])) {
                throw new \RedisException('Redis authentication failed.');
            }

            if (isset($redisConfig['database']) && !$redis->select((int)$redisConfig['database'])) {
                throw new \RedisException('Redis database selection failed.');
            }

            return $redis;
        });
    }
}
