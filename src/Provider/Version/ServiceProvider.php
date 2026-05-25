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

namespace PhalconKit\Provider\Version;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Version;

/**
 * Registers the framework version helper service.
 *
 * The service exposes package/runtime version helpers through DI for diagnostics
 * and framework metadata responses.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'version';
    
    /**
     * Register the shared `version` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () {
            return new Version();
        });
    }
}
