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

namespace PhalconKit\Provider\Loggers;

use PhalconKit\Di\DiInterface;
use PhalconKit\Logger\Loggers;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the named logger registry service.
 *
 * The `loggers` service owns logger configuration and lazy construction of
 * named logger instances. It combines the global `logger` config with the
 * per-name `loggers` config so callers can request dedicated loggers without
 * duplicating adapter and formatter setup.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'loggers';
    
    /**
     * Register the shared `loggers` service.
     *
     * The service is intentionally separate from `logger`: this provider
     * registers the registry/factory, while the `logger` provider resolves the
     * default logger instance from that registry.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            
            $options = $config->pathToArray('logger') ?? [];
            $options['loggers'] = $config->pathToArray('loggers') ?? [];
            
            return new Loggers($options);
        });
    }
}
