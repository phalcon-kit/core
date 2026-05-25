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

namespace PhalconKit\Provider\Logger;

use PhalconKit\Di\DiInterface;
use PhalconKit\Logger\Loggers;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the default logger service.
 *
 * The `logger` service is a convenience alias for `loggers->get('default')`.
 * Use it for application code that needs the standard logger, and use the
 * `loggers` registry directly when a dedicated named logger is required.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'logger';
    
    /**
     * Register the shared `logger` service.
     *
     * @throws \PhalconKit\Exception\ServiceException When the `loggers` service
     *     is missing or does not implement the PhalconKit logger registry.
     * @throws \PhalconKit\Exception\ConfigurationException When the default
     *     logger's adapter or formatter configuration is invalid.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $loggers = $di->getTyped('loggers', Loggers::class);
            
            return $loggers->get('default');
        });
    }
}
