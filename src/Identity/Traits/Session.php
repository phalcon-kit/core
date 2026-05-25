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

namespace PhalconKit\Identity\Traits;

use PhalconKit\Di\AbstractInjectable;
use PhalconKit\Identity\Traits\Abstracts\AbstractJwt;

/**
 * Stores identity payloads in the configured session service.
 *
 * The payload is written under the active JWT claim key, not directly under the
 * static session key. This allows JWT refreshes to rotate the storage key and
 * invalidate older tokens while preserving the small session identity payload
 * when appropriate.
 */
trait Session
{
    use AbstractInjectable;
    use AbstractJwt;
    
    public const string SESSION_KEY = 'phalcon-kit-identity';
    public const string REFRESH_SUFFIX = '-refresh';
    
    /**
     * Return the configured identity session namespace.
     *
     * @param bool $refresh Append {@see REFRESH_SUFFIX} for refresh-token
     *     operations.
     *
     * @return string Configured session key with the optional refresh suffix.
     */
    public function getSessionKey(bool $refresh = false): string
    {
        return $this->getOption('sessionKey', self::SESSION_KEY) . ($refresh ? self::REFRESH_SUFFIX : '');
    }
    
    /**
     * Remove the identity payload stored under the active claim key.
     *
     * If no claim key is available, there is no addressable identity payload
     * and the method intentionally becomes a no-op.
     */
    public function removeSessionIdentity(): void
    {
        $key = $this->getKey();
        if ($key) {
            $this->session->remove($key);
        }
    }
    
    /**
     * Store the identity payload under the active claim key.
     *
     * @param array<string, mixed> $identity Identity payload, usually including
     *     `userId` and optionally `asUserId`.
     */
    public function setSessionIdentity(array $identity): void
    {
        $key = $this->getKey();
        if ($key) {
            $this->session->set($key, $identity);
        }
    }
    
    /**
     * Return the identity payload stored under the active claim key.
     *
     * @return array<string, mixed> Empty when no key or payload exists.
     */
    public function getSessionIdentity(): array
    {
        $key = $this->getKey();
        return ($key ? $this->session->get($key) : null) ?? [];
    }
    
    /**
     * Check whether an identity payload exists for the active claim key.
     *
     * @return bool True when both a claim key and matching session payload are
     *     present.
     */
    public function hasSessionIdentity(): bool
    {
        $key = $this->getKey();
        return $key && $this->session->has($key);
    }
    
    /**
     * Return the active claim key used to address session identity storage.
     *
     * @return string|null Claim key or null when no usable claim has been
     *     resolved.
     */
    public function getKey(): ?string
    {
        return $this->getClaim()['key'] ?? null;
    }
}
