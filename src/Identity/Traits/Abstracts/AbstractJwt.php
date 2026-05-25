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
 * Declares JWT claim methods required by session and identity helpers.
 *
 * The identity session layer uses the active claim key to read and write the
 * session payload, so any class using this abstract trait must provide the JWT
 * claim lifecycle from {@see \PhalconKit\Identity\Traits\Interfaces\JwtInterface}.
 */
trait AbstractJwt
{
    /**
     * @return array{jwt: string, refreshToken: string, refreshed: bool}
     */
    abstract public function getJwt(bool $refresh = false): array;
    
    /**
     * @return array<string, mixed>
     */
    abstract public function getClaim(bool $refresh = false, bool $force = false): array;
    
    /**
     * @param array<string, mixed> $claim
     */
    abstract public function setClaim(array $claim): void;
    
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $options
     */
    abstract public function getJwtToken(string $id, array $data = [], array $options = []): string;
    
    /**
     * @return array<string, mixed>
     */
    abstract public function getClaimFromToken(string $token, ?string $claim = null): array;
    
    /**
     * @param array<int, string> $authorization
     *
     * @return array<string, mixed>
     */
    abstract public function getClaimFromAuthorization(array $authorization): array;
}
