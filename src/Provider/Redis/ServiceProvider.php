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

use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Provider\AbstractServiceProvider;
use Redis;

/**
 * Registers the native Redis client service.
 *
 * Connection settings come from the `redis` config section. The provider
 * handles connection, optional authentication, and optional database selection
 * before returning the client, wrapping extension failures in
 * `ServiceException` so framework consumers can catch a stable exception type.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'redis';

    /**
     * Register the shared Redis client service.
     *
     * The provider reads the `redis` configuration path, applies conservative
     * connection defaults, and returns a native `Redis` instance from the DI
     * container. Native Redis extension failures are wrapped in
     * `ServiceException` so callers can catch a stable PhalconKit service
     * boundary instead of depending on extension-specific exception behavior.
     *
     * @param DiInterface $di The PhalconKit container that supplies the config
     *     service and receives the shared Redis service definition.
     *
     * @return void
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di): Redis {

            $config = $di->getConfig();

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

            try {
                $connected = $redis->connect(
                    $redisConfig['host'],
                    $redisConfig['port'],
                    $redisConfig['timeout'],
                    $redisConfig['persistentId'],
                    $redisConfig['retryInterval'],
                    $redisConfig['readTimeout'],
                    $redisConfig['context']
                );
            }
            catch (\RedisException $e) {
                throw new ServiceException('Redis connection failed.', previous: $e);
            }

            if (!$connected) {
                throw new ServiceException('Redis connection failed.');
            }

            if (!empty($redisConfig['auth'])) {
                try {
                    $authenticated = $redis->auth($redisConfig['auth']);
                }
                catch (\RedisException $e) {
                    throw new ServiceException('Redis authentication failed.', previous: $e);
                }

                if (!$authenticated) {
                    throw new ServiceException('Redis authentication failed.');
                }
            }

            if (isset($redisConfig['database'])) {
                try {
                    $selected = $redis->select((int)$redisConfig['database']);
                }
                catch (\RedisException $e) {
                    throw new ServiceException('Redis database selection failed.', previous: $e);
                }

                if (!$selected) {
                    throw new ServiceException('Redis database selection failed.');
                }
            }

            return $redis;
        });
    }
}
