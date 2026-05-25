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

namespace PhalconKit\Identity\Traits\Abstracts;

/**
 * Declares identity session methods required by JWT, OAuth2, and user helpers.
 *
 * Implementations are expected to store only the small identity payload under
 * the active claim key; the full user model is resolved separately through the
 * configured model service.
 */
trait AbstractSession
{
    abstract public function getSessionKey(bool $refresh = false): string;
    
    abstract public function removeSessionIdentity(): void;
    
    /**
     * @param array<string, mixed> $identity
     */
    abstract public function setSessionIdentity(array $identity): void;
    
    /**
     * @return array<string, mixed>
     */
    abstract public function getSessionIdentity(): array;
    
    abstract public function hasSessionIdentity(): bool;
    
    abstract public function getKey(): ?string;

    /**
     * Return refreshed JWT values when the concrete identity storage is
     * stateless.
     *
     * Stateful identity storage persists the payload server-side, so callers
     * should receive an empty array and preserve their legacy response shape.
     *
     * @return array{jwt?: string, refreshToken?: string, refreshed?: bool}
     */
    abstract protected function getJwtForStatelessIdentity(): array;
}
