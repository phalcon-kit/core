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

namespace PhalconKit\Encryption;

use Phalcon\Encryption\Security as PhalconSecurity;
use PhalconKit\Encryption\Security\Random;
use PhalconKit\Config\ConfigInterface;

/**
 * Security service with PhalconKit random generation and Argon2 defaults.
 *
 * The class preserves Phalcon's security API while replacing the random helper
 * with PhalconKit's implementation. When Argon2 hashing is selected, hash
 * options are completed from `security.argon2` config before falling back to
 * PHP's password defaults.
 */
class Security extends PhalconSecurity
{
    /**
     * Create the security service and install the PhalconKit random helper.
     */
    public function __construct(?\Phalcon\Session\ManagerInterface $session = null, ?\Phalcon\Http\RequestInterface $request = null)
    {
        parent::__construct($session, $request);
        $this->random = new Random();
    }

    /**
     * Return the application config service used for security defaults.
     *
     * @return ConfigInterface Configuration service registered in the DI.
     */
    public function getConfig(): ConfigInterface
    {
        return $this->getDI()->get('config');
    }
    
    /**
     * Hash a password, merging configured Argon2 options when applicable.
     *
     * Explicit options passed by the caller always win. Missing Argon2
     * `memory_cost`, `time_cost`, and `threads` values are filled from the
     * framework config before PHP defaults are used.
     *
     * @param string $password Password or secret to hash.
     * @param array<string, mixed> $options Algorithm-specific hash options.
     *
     * @return string Password hash returned by Phalcon/PHP.
     */
    #[\Override]
    public function hash(string $password, array $options = []): string
    {
        if (in_array($this->getDefaultHash(), [
            PhalconSecurity::CRYPT_ARGON2I,
            PhalconSecurity::CRYPT_ARGON2ID
        ])) {
            $defaultOptions = $this->getConfig()->pathToArray('security.argon2') ?? [];
            $options['memory_cost'] ??= $defaultOptions['memoryCost'] ?? PASSWORD_ARGON2_DEFAULT_MEMORY_COST;
            $options['time_cost'] ??= $defaultOptions['timeCost'] ?? PASSWORD_ARGON2_DEFAULT_TIME_COST;
            $options['threads'] ??= $defaultOptions['threads'] ?? PASSWORD_ARGON2_DEFAULT_THREADS;
        }
        
        return parent::hash($password, $options);
    }
    
    /**
     * Return the PhalconKit random helper used by this security service.
     */
    #[\Override]
    public function getRandom(): Random
    {
        return $this->random;
    }
}
