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

namespace PhalconKit\Provider\Database;

use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Events\Manager as EventsManager;
use PhalconKit\Di\DiInterface;
use PhalconKit\Db\Events\Logger;
use PhalconKit\Db\Events\Profiler;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers a configured PDO database connection.
 *
 * The provider resolves the active driver from `database.default` or from a
 * subclass-specific `$driverName`. Driver definitions can extend one or more
 * other driver definitions through `extends`, allowing applications to keep
 * shared connection options in one place and override only the values that
 * differ per connection.
 *
 * Core database logger and profiler listeners are attached to the shared events
 * manager before the connection is returned.
 */
class ServiceProvider extends AbstractServiceProvider
{
    /**
     * Optional configured driver name forced by a specialized provider.
     *
     * Null means the provider uses `database.default`. Subclasses such as the
     * read-only and dynamic database providers set this value to select a named
     * driver while reusing the base connection-building logic.
     */
    protected ?string $driverName = null;

    protected string $serviceName = 'db';

    /**
     * Tracks whether database listeners were attached during this PHP process.
     */
    protected static bool $attachedEvents = false;
    
    /**
     * Register the shared database service.
     *
     * Supported driver options include `adapter`, `dialectClass`, connection
     * descriptor values accepted by the selected adapter, and control keys such
     * as `extends`/`enable` that are removed before adapter construction.
     *
     * @throws ConfigurationException When driver options are invalid, adapter or
     *     dialect classes do not exist, or the adapter does not extend Phalcon's
     *     PDO adapter base class.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $driverName = $this->driverName;
        $di->setShared($this->getName(), function () use ($di, $driverName) {
            $config = $di->getConfig();
    
            // database config
            $databaseConfig = $config->pathToArray('database') ?? [];
            $driverName ??= $databaseConfig['default'] ?? 'mysql';
            
            // specified driver name
            if (isset($databaseConfig['drivers'][$driverName])) {
                if (!is_array($databaseConfig['drivers'][$driverName])) {
                    throw new ConfigurationException('Database driver option `' . $driverName . '` must be an array');
                }
                
                $driverOptions = array_filter($databaseConfig['drivers'][$driverName], fn(mixed $value) => $value !== null);
                
                if (isset($driverOptions['extends'])) {
                    if (is_string($driverOptions['extends'])) {
                        $driverOptions['extends'] = explode(',', $driverOptions['extends']);
                    }
                    if (is_array($driverOptions['extends'])) {
                        foreach ($driverOptions['extends'] as $extend) {
                            $driverOptions = array_merge($databaseConfig['drivers'][trim($extend)] ?? [], $driverOptions);
                        }
                    }
                }
            }
            
            // default driver name
            else {
                $defaultDriverName = $databaseConfig['default'] ?? 'mysql';
                $driverOptions = $databaseConfig['drivers'][$defaultDriverName] ?? [];
            }
            
            // unset unsupported parameters
            unset($driverOptions['extends']);
            unset($driverOptions['enable']);
            
            // dialect
            if (!empty($driverOptions['dialectClass'])) {
                $dialectClass = $driverOptions['dialectClass'];
                if (!is_string($dialectClass) || !class_exists($dialectClass)) {
                    throw new ConfigurationException(sprintf(
                        'Database dialect class for driver "%s" must be an existing class name.',
                        $driverName
                    ));
                }
                $driverOptions['dialectClass'] = new $dialectClass();
            }
    
            // adapter
            $adapter = $driverOptions['adapter'] ?? Mysql::class;
            if (!is_string($adapter) || !class_exists($adapter)) {
                throw new ConfigurationException(sprintf(
                    'Database adapter class for driver "%s" must be an existing class name.',
                    $driverName
                ));
            }
            unset($driverOptions['adapter']);

            // connection
            $connection = new $adapter($driverOptions);
            if (!$connection instanceof AbstractPdo) {
                throw new ConfigurationException(sprintf(
                    'Database adapter class "%s" must create an instance of "%s".',
                    $adapter,
                    AbstractPdo::class
                ));
            }
            
            // attach events
            $eventsManager = $di->getTyped('eventsManager', EventsManager::class);
            
            if (!self::$attachedEvents) {
                $eventsManager->detach('db', new Logger());
                $eventsManager->detach('db', new Profiler());
            }
            
            $eventsManager->attach('db', new Logger());
            $eventsManager->attach('db', new Profiler());
            self::$attachedEvents = true;
            
            $connection->setEventsManager($eventsManager);
            
            return $connection;
        });
    }
}
