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
 * Contract for JWT-backed identity claims and token generation.
 */
interface JwtInterface
{
    /**
     * Build access and refresh tokens for the current claim.
     *
     * @param bool $refresh Rotate the claim key and preserve session identity
     *     under the new key when possible.
     *
     * @return array{jwt: string, refreshToken: string, refreshed: bool}
     */
    public function getJwt(bool $refresh = false): array;
    
    /**
     * Resolve the current claim from request tokens, authorization headers, or
     * the optional session fallback.
     *
     * @param bool $refresh Prefer the refresh token source.
     * @param bool $force Ignore any cached claim and inspect request/session
     *     sources again.
     *
     * @return array<string, mixed>
     */
    public function getClaim(bool $refresh = false, bool $force = false): array;
    
    /**
     * Replace the in-memory claim for this manager instance.
     *
     * @param array<string, mixed> $claim Claim payload.
     */
    public function setClaim(array $claim): void;
    
    /**
     * Create a signed JWT for the given token id and payload.
     *
     * @param string $id Token id used by the JWT validator.
     * @param array<string, mixed> $data Subject payload to encode.
     * @param array<string, mixed> $options JWT builder options.
     */
    public function getJwtToken(string $id, array $data = [], array $options = []): string;
    
    /**
     * Validate a JWT and extract its subject claim payload.
     *
     * @param string $token Encoded JWT.
     * @param string|null $claim Expected JWT id.
     *
     * @return array<string, mixed>
     */
    public function getClaimFromToken(string $token, ?string $claim = null): array;
    
    /**
     * Extract a bearer token from an authorization header split into parts.
     *
     * @param array<int, string> $authorization Header parts, usually
     *     `[Bearer, token]`.
     *
     * @return array<string, mixed>
     */
    public function getClaimFromAuthorization(array $authorization): array;
}
