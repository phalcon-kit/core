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

namespace PhalconKit\Provider\Escaper;

use PhalconKit\Di\DiInterface;
use PhalconKit\Html\Escaper;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the HTML escaper service.
 *
 * PhalconKit uses its escaper wrapper so tag helpers, views, and controller
 * code can resolve one framework-scoped escaping implementation from DI while
 * still relying on Phalcon-compatible escaping behavior.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'escaper';
    
    /**
     * Register the `escaper` service.
     *
     * The service is registered by class name instead of a closure because it
     * has no provider-time configuration and can be instantiated directly by
     * the DI container.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->set($this->getName(), Escaper::class);
    }
}
