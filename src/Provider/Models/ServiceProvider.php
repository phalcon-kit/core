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

namespace PhalconKit\Provider\Models;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Models;

/**
 * Registers the framework model class-map service.
 *
 * The `models` service maps core PhalconKit model contracts to application
 * model classes. This lets reusable framework services refer to stable core
 * model names while applications provide their concrete user, role, token, or
 * domain model implementations through configuration.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'models';
    
    /**
     * Register the shared `models` service.
     *
     * Class mappings are read from the `models` config section and passed to
     * `PhalconKit\Support\Models`, which exposes typed lookup helpers for the
     * rest of the framework.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->getConfig();
            $options = $config->pathToArray('models', []);
            
            return new Models($options);
        });
    }
}
