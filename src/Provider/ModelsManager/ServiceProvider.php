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

use Phalcon\Events\Manager as EventsManager;
use PhalconKit\Di\DiInterface;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the model manager service.
 *
 * The provider returns PhalconKit's model manager extension and attaches the
 * shared events manager when one is available. This keeps model events,
 * relationships, and framework model helpers on the same event bus used by the
 * rest of the application.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'modelsManager';
    
    /**
     * Register the shared `modelsManager` service.
     *
     * The provider tolerates a missing or non-Phalcon events manager so tests and
     * specialized bootstraps can create the model manager in isolation.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $manager = new Manager();
    
            if ($di->has('eventsManager')) {
                $eventsManager = $di->get('eventsManager');
                if ($eventsManager instanceof EventsManager) {
                    $manager->setEventsManager($eventsManager);
                }
            }
            
            return $manager;
        });
    }
}
