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

namespace PhalconKit\Provider\Session;

use Phalcon\Di\DiInterface;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Redis;
use Phalcon\Session\Adapter\Noop;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Storage\AdapterFactory;
use Phalcon\Storage\SerializerFactory;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'session';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->get('config');
            assert($config instanceof ConfigInterface);
            
            $sessionConfig = $config->pathToArray('session');
            
            $driverName = $sessionConfig['driver'] ?? '';
            
            $defaultOptions = $sessionConfig['default'] ?? [];
            $driverOptions = $sessionConfig['drivers'][$driverName] ?? [];
            $options = array_merge($defaultOptions, $driverOptions);
            
            // Create the new session manager
            $session = new Manager();
            
            // this is for unit testing purpose and should not have to happen
            if ($session->exists()) {
                $session->destroy();
            }
            
            // ini_set
            $sessionIniConfig = $sessionConfig['ini'] ?? [];
            foreach ($sessionIniConfig as $sessionIniKey => $sessionIniValue) {
                ini_set($sessionIniKey, $sessionIniValue);
            }
            
            // Set the storage adapter
            $adapter = $options['adapter'];
            if (in_array($adapter, [Noop::class, Stream::class])) {
                $adapterInstance = new $adapter($options);
                assert($adapterInstance instanceof \SessionHandlerInterface);
                $session->setAdapter($adapterInstance);
            }
            else {
                $serializerFactory = new SerializerFactory();
                $adapterFactory = new AdapterFactory($serializerFactory);
                $adapterInstance = new $adapter($adapterFactory, $options);
                assert($adapterInstance instanceof \SessionHandlerInterface);
                $session->setAdapter($adapterInstance);
                
                // ini_set save_handler and save_path for redis
                if (is_a($adapter, Redis::class, true)) {
                    ini_set('session.save_handler', 'redis');
                    ini_set('session.save_path', $options['host'] . ':' . $options['port'] . '?' . http_build_query($options));
                }
            }
            
            // Start and return the session
            $session->start();
            return $session;
        });
    }
}
