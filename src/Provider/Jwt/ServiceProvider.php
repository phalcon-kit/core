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

namespace PhalconKit\Provider\Jwt;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the JWT helper service.
 *
 * Configuration is read from `security.jwt` and passed to
 * `PhalconKit\Provider\Jwt\Jwt`. The identity manager and API controllers use
 * this service to keep token signing/parsing options centralized.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'jwt';
    
    /**
     * Register the shared `jwt` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            
            $jwtConfig = $config->pathToArray('security.jwt') ?? [];
            return new Jwt($jwtConfig);
        });
    }
}
