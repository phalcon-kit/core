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

/**
 * Registers the password hashing and token security service.
 *
 * PhalconKit uses its `Encryption\Security` wrapper so hashing helpers can read
 * framework config while preserving Phalcon's security API. Defaults favor
 * Argon2id with a moderate work factor; applications can override both under
 * `security.workFactor` and `security.hash`.
 *
 * @see https://docs.phalcon.io/5.15/encryption-security/
 */
class ServiceProvider extends AbstractServiceProvider
{
    /**
     * Default password hashing work factor used when config does not override.
     */
    public int $defaultWorkFactor = 12;

    /**
     * Default Phalcon password hash algorithm used when config does not
     * override.
     */
    public int $defaultHash = PhalconSecurity::CRYPT_ARGON2ID;
    
    protected string $serviceName = 'security';
    
    /**
     * Register the shared `security` service.
     *
     * The service receives DI before hash defaults are applied so downstream
     * hashing helpers can resolve config-backed Argon options safely.
     */
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
