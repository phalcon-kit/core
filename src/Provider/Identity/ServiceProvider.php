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

namespace PhalconKit\Provider\Identity;

use PhalconKit\Di\DiInterface;
use PhalconKit\Identity\Manager as Identity;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'identity';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
            
            $config = $di->getConfig();
            
            $options ??= $config->pathToArray('identity');
            
            $identity = new Identity($options);
            $identity->setDI($di);
            
            return $identity;
        });
    }
}
