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

namespace PhalconKit\Mvc\Model\Traits;

use PhalconKit\Config\ConfigInterface;
use PhalconKit\Encryption\Security as SecurityService;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;

trait Hash
{
    use AbstractInjectable;
    
    /**
     * Hash a string
     *
     * @param string $string The string to be hashed
     * @param string|null $salt (optional) The salt value to be appended to the string before hashing
     * @param string|null $workFactor (optional) The work factor to determine the hashing cost
     *
     * @return string The salted hash value of the input string
     * @throws ServiceException When the config or security service cannot be
     *     resolved through the PhalconKit DI contract.
     */
    public function hash(string $string, ?string $salt = null, ?string $workFactor = null): string
    {
        $config = $this->getTypedService('config', ConfigInterface::class, 'model hash helpers');
        $security = $this->getTypedService('security', SecurityService::class, 'model hash helpers');
        
        // Get salt & workFactor
        $salt ??= $config->path('security.salt') ?? '';
        $workFactor ??= $config->path('security.workFactor') ?? 10;
        
        // return salted hash
        return $security->hash($salt . $string, ['cost' => $workFactor]);
    }
    
    /**
     * Checks whether a given hash is valid for a given string.
     *
     * @param string|null $hash The hash value to be checked.
     * @param string|null $string The string to be hashed and checked against the given hash value.
     * @param int $maxPassLength The maximum length of the password.
     *
     * @return bool Returns true if the hash is valid for the string, false otherwise.
     * @throws ServiceException When the config or security service cannot be
     *     resolved through the PhalconKit DI contract.
     */
    public function checkHash(?string $hash = null, ?string $string = null, int $maxPassLength = 0): bool
    {
        // hash empty, not valid
        if (empty($hash)) {
            return false;
        }
        
        // string empty not valid
        if (empty($string)) {
            return false;
        }
        
        $config = $this->getTypedService('config', ConfigInterface::class, 'model hash helpers');
        $security = $this->getTypedService('security', SecurityService::class, 'model hash helpers');
        
        // Get salt
        $salt = $config->path('security.salt') ?? '';
        
        // check hash
        return $security->checkHash($salt . $string, $hash, $maxPassLength);
    }
}
