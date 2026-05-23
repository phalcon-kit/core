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

namespace PhalconKit\Provider\Mailer;

use PhalconKit\Di\DiInterface;
use Phalcon\Events\ManagerInterface;
use Phalcon\Incubator\Mailer\Manager;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'mailer';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            $config = $di->getConfig();
    
            $mailerConfig = $config->pathToArray('mailer', []);
            
            $driver = $mailerConfig['driver'] ?? '';
            $defaultOptions = $mailerConfig['defaults'] ?? [];
            $driverOptions = $mailerConfig['drivers'][$driver] ?? [];
            $options = array_merge($defaultOptions, $driverOptions);
    
            $manager = new Manager($options);
            $manager->setDI($di);
            
            $eventsManager = $di->get('eventsManager');
            if ($eventsManager instanceof ManagerInterface) {
                $manager->setEventsManager($eventsManager);
            }
            
            // temporary fix for smtp auth
            if ($driver === 'smtp') {
                $manager->getMailer()->SMTPAuth = true;
            }
            
            return $manager;
        });
    }
}
