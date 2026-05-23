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

namespace PhalconKit\Provider\ModelsManager;

use PhalconKit\Di\DiInterface;
use Phalcon\Events\ManagerInterface;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'modelsManager';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $manager = new Manager();
    
            $eventsManager = $di->get('eventsManager');
            if ($eventsManager instanceof ManagerInterface) {
                $manager->setEventsManager($eventsManager);
            }
            
            return $manager;
        });
    }
}
