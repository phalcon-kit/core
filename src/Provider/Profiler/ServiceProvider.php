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

namespace PhalconKit\Provider\Profiler;

use PhalconKit\Di\DiInterface;
use PhalconKit\Db\Profiler;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the database profiler service.
 *
 * The profiler is shared so database listeners, debug output, and tests inspect
 * the same collected query profile data during a request or command.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'profiler';
    
    /**
     * Register the shared `profiler` service.
     *
     * The profiler has no provider-time options, so the DI container can create
     * the PhalconKit profiler class directly.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), Profiler::class);
    }
}
