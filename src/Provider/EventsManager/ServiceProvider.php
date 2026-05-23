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

namespace PhalconKit\Provider\EventsManager;

use PhalconKit\Di\DiInterface;
use Phalcon\Events\Manager;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'eventsManager';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () {
            
            $eventsManager = new Manager();
            $eventsManager->enablePriorities(true);
            
            return $eventsManager;
        });
    }
}
