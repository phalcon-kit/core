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

namespace PhalconKit\Provider\Gravatar;

//use Phalcon\Avatar\Gravatar;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'gravatar';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        // Intentionally empty until the package defines a maintained Gravatar
        // client, dependency, configuration shape, and privacy stance.
//        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
//    
//            $options ??= $di->getConfig()->pathToArray('gravatar', []);
//            
//            return new Gravatar($options);
//        });
    }
}
