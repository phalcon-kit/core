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

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'models';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            // config
            $config = $di->getConfig();
            
            // models config (mapping)
            $options = $config->pathToArray('models', []);
            
            return new Models($options);
        });
    }
}
