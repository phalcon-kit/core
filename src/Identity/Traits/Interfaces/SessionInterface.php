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

namespace PhalconKit\Identity\Traits\Interfaces;

/**
 * Contract for storing identity payloads under the current claim key.
 */
interface SessionInterface
{
    /**
     * Return the configured identity session key.
     *
     * @param bool $refresh Append the refresh-token suffix when true.
     */
    public function getSessionKey(bool $refresh = false): string;
    
    /**
     * Remove the identity payload stored under the current claim key.
     */
    public function removeSessionIdentity(): void;
    
    /**
     * Store the identity payload under the current claim key.
     *
     * @param array<string, mixed> $identity Identity payload, usually including
     *     `userId` and optionally `asUserId`.
     */
    public function setSessionIdentity(array $identity): void;
    
    /**
     * Return the identity payload stored under the current claim key.
     *
     * @return array<string, mixed>
     */
    public function getSessionIdentity(): array;
    
    /**
     * Check whether an identity payload exists under the current claim key.
     */
    public function hasSessionIdentity(): bool;
    
    /**
     * Return the active claim key used to address identity session storage.
     */
    public function getKey(): ?string;
}
