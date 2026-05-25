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

namespace PhalconKit\Provider\Utils;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Utils;

/**
 * Registers the utility helper service.
 *
 * `PhalconKit\Support\Utils` groups framework utility helpers that do not fit a
 * narrower service. The provider keeps those helpers injectable for consumers
 * that avoid static access.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'utils';
    
    /**
     * Register the shared `utils` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () {
            
            return new Utils();
        });
    }
}
