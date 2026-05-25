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

namespace PhalconKit\Provider\Console;

use PhalconKit\Di\DiInterface;
use PhalconKit\Cli\Console;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the CLI console service.
 *
 * The console service is the Phalcon CLI entrypoint used by task bootstraps.
 * It is registered separately from the MVC application so command execution can
 * use CLI router/dispatcher defaults while still sharing the same PhalconKit DI
 * contracts as the rest of the framework.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'console';
    
    /**
     * Register the shared `console` service.
     *
     * The console receives the DI container at construction time, matching
     * Phalcon's native console lifecycle and allowing task modules to resolve
     * framework services from the configured container.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            return new Console($di);
        });
    }
}
