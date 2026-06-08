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

use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\ConfigurationException;
use Phalcon\Session\Manager;
use Phalcon\Session\Adapter\Redis;
use Phalcon\Session\Adapter\Noop;
use Phalcon\Session\Adapter\Stream;
use Phalcon\Storage\AdapterFactory;
use Phalcon\Storage\SerializerFactory;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the session manager service.
 *
 * Session configuration is resolved from `session.driver`,
 * `session.default`, `session.drivers`, and optional `session.ini` values. The
 * default driver is Phalcon's stream adapter with a temporary-directory save
 * path, which keeps legacy MVC applications session-capable without extra
 * configuration.
 *
 * This provider intentionally starts the session before returning it. Identity
 * flows that need stateless JWT-only behavior should use `identity.stateless`
 * so flash messages, OAuth2 state, locale persistence, and other PHP-session
 * consumers can keep working normally.
 *
 * @see https://docs.phalcon.io/5.14/session/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'session';
    
    /**
     * Register the shared `session` service.
     *
     * Adapter classes using Phalcon's stream/noop constructor are instantiated
     * directly. Other adapters are created with a storage adapter factory and
     * must implement `SessionHandlerInterface` before being attached to the
     * manager.
     *
     * @throws ConfigurationException When a factory-backed adapter does not
     *     implement PHP's session handler interface.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            
            $sessionConfig = $config->pathToArray('session') ?? [];
            
            $driverName = $sessionConfig['driver'] ?? 'stream';
            
            $defaultOptions = $sessionConfig['default'] ?? [];
            $driverOptions = $sessionConfig['drivers'][$driverName] ?? [];
            $options = array_merge($defaultOptions, $driverOptions);
            
            // Create a fresh manager for the configured adapter.
            $session = new Manager();
            
            // Avoid leaking an already-active PHP session into the configured
            // manager. This mainly protects tests and long-running processes
            // that resolve the service multiple times with different config.
            if ($session->exists()) {
                $session->destroy();
            }
            
            // Apply session INI settings before the manager starts.
            $sessionIniConfig = $sessionConfig['ini'] ?? [];
            foreach ($sessionIniConfig as $sessionIniKey => $sessionIniValue) {
                ini_set($sessionIniKey, $sessionIniValue);
            }
            
            // Resolve and instantiate the configured storage adapter.
            $adapter = $options['adapter'] ?? Stream::class;
            if (!is_string($adapter) || $adapter === '') {
                $adapter = Stream::class;
            }

            if (is_a($adapter, Stream::class, true)) {
                $options['savePath'] ??= sys_get_temp_dir();
            }

            if ($adapter === Noop::class) {
                $adapterInstance = new Noop();
                $session->setAdapter($adapterInstance);
            }
            else if ($adapter === Stream::class) {
                $adapterInstance = new Stream($options);
                $session->setAdapter($adapterInstance);
            }
            else {
                $serializerFactory = new SerializerFactory();
                $adapterFactory = new AdapterFactory($serializerFactory);
                $adapterInstance = new $adapter($adapterFactory, $options);
                if (!$adapterInstance instanceof \SessionHandlerInterface) {
                    throw new ConfigurationException(
                        'Session adapter must implement ' . \SessionHandlerInterface::class
                    );
                }
                $session->setAdapter($adapterInstance);
                
                // Redis-backed sessions also need PHP's native session INI
                // values so native session handling points at the same backend.
                if (is_a($adapter, Redis::class, true)) {
                    $options['host'] ??= '127.0.0.1';
                    $options['port'] ??= 6379;
                    ini_set('session.save_handler', 'redis');
                    ini_set('session.save_path', $options['host'] . ':' . $options['port'] . '?' . http_build_query($options));
                }
            }
            
            // Keep existing package behavior: resolving the service starts it.
            $session->start();
            return $session;
        });
    }
}
