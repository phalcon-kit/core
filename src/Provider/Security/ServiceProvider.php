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

namespace PhalconKit\Provider\Security;

use PhalconKit\Di\DiInterface;
use Phalcon\Encryption\Security as PhalconSecurity;
use PhalconKit\Encryption\Security;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    public int $defaultWorkFactor = 12;
    public int $defaultHash = PhalconSecurity::CRYPT_ARGON2ID;
    
    protected string $serviceName = 'security';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            $securityConfig = $config->pathToArray('security') ?? [];
            
            $security = new Security();
            $security->setDI($di);
            $security->setWorkFactor($securityConfig['workFactor'] ?? $this->defaultWorkFactor);
            $security->setDefaultHash($securityConfig['hash'] ?? $this->defaultHash);
            
            return $security;
        });
    }
}
