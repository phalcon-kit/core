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

use Phalcon\Encryption\Security\Exception as SecurityException;
use Phalcon\Encryption\Security\JWT\Exceptions\ValidatorException;
use PhalconKit\Di\AbstractInjectable;
use PhalconKit\Exception\HttpException;
use stdClass;

/**
 * Resolves identity claims from JWTs, bearer authorization, or session fallback.
 *
 * Access and refresh tokens both carry the same claim payload, but use
 * different token ids so the validator can distinguish normal and refresh
 * flows. The claim `key` is also used by the session identity trait as the
 * server-side lookup key for the small `userId`/`asUserId` payload, unless
 * `identity.stateless` stores that payload directly in the token subject.
 */
trait Jwt
{
    use AbstractInjectable;
    
    /**
     * Cached claim payload for the current manager instance.
     *
     * @var array<string, mixed>
     */
    public array $claim = [];
    
    /**
     * Generate access and refresh tokens for the current claim.
     *
     * When no claim key exists, a new UUID key is created. During refresh with
     * session-backed identity storage, the existing identity payload is copied
     * from the old key to the new key after the old storage entry is removed,
     * which invalidates tokens tied to the old key while keeping the user
     * logged in. In stateless identity mode, the payload is preserved directly
     * in the claim so clients can carry it without PHP session storage; old
     * signed JWTs remain valid until expiration or an application-level
     * revocation strategy rejects them.
     *
     * @param bool $refresh Rotate the claim key and invalidate previous tokens.
     *
     * @return array{jwt: string, refreshToken: string, refreshed: bool}
     *
     * @throws SecurityException When token key generation fails.
     * @throws ValidatorException When JWT creation fails.
     * @throws HttpException With status 401 when a supplied token is invalid,
     *     before session identity is read or rotated and before tokens are issued.
     */
    public function getJwt(bool $refresh = false): array
    {
        $claim = $this->getClaim($refresh, $refresh);
        
        // Session fallback is intentionally ignored by stateless identity mode.
        $sessionFallback = !$this->config->path('identity.stateless', false)
            && $this->config->path('identity.sessionFallback', false);
        
        // undefined key, create a new one using uuid
        if (empty($claim['key'])) {
            $this->setClaim(array_merge($claim, ['key' => $this->security->getRandom()->uuid()]));
            
            // save new key into session when using session fallback
            if ($sessionFallback) {
                $this->session->set($this->getSessionKey(), $this->getClaim());
            }
        }
        
        else if ($refresh) {
            // get session identity before
            $sessionIdentity = $this->getSessionIdentity();
            $this->removeSessionIdentity();
            
            // change the store key for a new one
            // this will invalidate the previous jwt tokens
            $this->setClaim(['key' => $this->security->getRandom()->uuid()]);
            
            // save the current session identity to the new key
            if (!empty($sessionIdentity)) {
                $this->setSessionIdentity($sessionIdentity);
            }
            
            // save new key into session when using session fallback
            if ($sessionFallback) {
                $this->session->set($this->getSessionKey(), $this->getClaim());
            }
        }
        
        // generate a new jwt using the store and jwt token options
        $tokenOptions = $this->config->pathToArray('identity.token') ?? [];
        $token = $this->getJwtToken($this->getSessionKey(), $this->claim, $tokenOptions);
        
        // generate a new refresh token using the store and refresh token options
        $refreshTokenOptions = $this->config->pathToArray('identity.refreshToken') ?? [];
        $refreshToken = $this->getJwtToken($this->getSessionKey(true), $this->claim, $refreshTokenOptions);
        
        return [
            'jwt' => $token,
            'refreshToken' => $refreshToken,
            'refreshed' => $refresh,
        ];
    }
    
    /**
     * Resolve the current claim from request and session sources.
     *
     * Resolution order is refresh token, JWT request value, authorization
     * header, then optional session fallback. The fallback is intentionally
     * disabled by default because it couples token authentication to server-side
     * session state, and is always skipped when `identity.stateless` is enabled.
     *
     * @param bool $refresh Prefer the refresh-token source.
     * @param bool $force Ignore the cached claim for this manager instance.
     *
     * @return array<string, mixed> Claim payload or an empty array when no
     *     supported credential is present.
     * @throws HttpException With status 401 when a supplied token fails parsing
     *     or validation. Invalid tokens never fall through to session fallback.
     */
    public function getClaim(bool $refresh = false, bool $force = false): array
    {
        // Using cached store
        if (!$force && !empty($this->claim)) {
            return $this->claim;
        }
        
        $json = $this->getJsonRawBody();
        
        if ($refresh) {
            $refreshToken = $this->request->get('refreshToken', null, $json->refreshToken ?? null);
            if ($refreshToken !== null && $refreshToken !== '') {
                if (!is_string($refreshToken)) {
                    throw new HttpException('Invalid authentication token.', 401);
                }
                $this->setClaim($this->getClaimFromToken($refreshToken, $this->getSessionKey(true)));
                return $this->claim;
            }
        }
        
        // Using JWT
        $jwt = $this->request->get('jwt', null, $json->jwt ?? null);
        if ($jwt !== null && $jwt !== '') {
            if (!is_string($jwt)) {
                throw new HttpException('Invalid authentication token.', 401);
            }
            $this->setClaim($this->getClaimFromToken($jwt, $this->getSessionKey()));
            return $this->claim;
        }
        
        // Using X-Authorization Header (recommended)
        $authorizationHeaderKey = $this->config->path('identity.authorizationHeader', 'X-Authorization');
        $authorization = preg_split('/\s+/', trim($this->request->getHeader($authorizationHeaderKey)), flags: PREG_SPLIT_NO_EMPTY) ?: [];
        if (!empty($authorization)) {
            $this->setClaim($this->getClaimFromAuthorization($authorization));
            return $this->claim;
        }
        
        // Using Session Fallback (less secure)
        if (
            !$this->config->path('identity.stateless', false)
            && $this->config->path('identity.sessionFallback', false)
            && $this->session->has($this->getSessionKey())
        ) {
            $this->setClaim($this->session->get($this->getSessionKey()));
            return $this->claim;
        }
        
        // Unsupported authorization method
        return [];
    }
    
    /**
     * Replace the cached claim for this manager instance.
     *
     * @param array<string, mixed> $claim Claim payload.
     */
    public function setClaim(array $claim): void
    {
        $this->claim = $claim;
    }
    
    /**
     * Build a signed JWT with Phalcon's JWT service.
     *
     * Missing issuer and audience values default to the current request URI.
     * Missing token id defaults to `$id`, and the subject defaults to the JSON
     * encoded claim data.
     *
     * @param string $id Expected token id.
     * @param array<string, mixed> $data Claim payload encoded into `sub`.
     * @param array<string, mixed> $options Additional JWT builder options.
     *
     * @return string Encoded JWT.
     *
     * @throws ValidatorException When the JWT builder rejects the options.
     */
    public function getJwtToken(string $id, array $data = [], array $options = []): string
    {
        $uri = $this->request->getScheme() . '://' . $this->request->getHttpHost();
        
        $options['issuer'] ??= $uri;
        $options['audience'] ??= $uri;
        $options['id'] ??= $id;
        $options['subject'] ??= json_encode($data);
        
        $builder = $this->jwt->builder($options);
        return $builder->getToken()->getToken();
    }
    
    /**
     * Validate a JWT and return its decoded subject payload.
     *
     * The token must match the current request URI as issuer and audience. When
     * `$claim` is provided, it is used as the expected token id so access and
     * refresh tokens cannot be exchanged.
     *
     * @param string $token Encoded JWT.
     * @param string|null $claim Expected token id.
     *
     * @return array<string, mixed> Decoded `sub` payload or an empty array when
     *     the subject is missing/non-array.
     * @throws HttpException With status 401 when parsing or validation fails.
     *     Validation errors are enforced before decoding the subject; neither
     *     the token nor the validator's diagnostics are exposed in the exception.
     */
    public function getClaimFromToken(string $token, ?string $claim = null): array
    {
        $uri = $this->request->getScheme() . '://' . $this->request->getHttpHost();
        
        try {
            $token = $this->jwt->parseToken($token);
            $claims = $token->getClaims();
            // Native validation can coerce malformed dates; require NumericDate values.
            foreach (['exp', 'nbf', 'iat'] as $dateClaim) {
                if ($claims->has($dateClaim)) {
                    $value = $claims->get($dateClaim);
                    if (!is_int($value) && !is_float($value)) {
                        throw new HttpException('Invalid authentication token.', 401);
                    }
                }
            }
            $errors = $this->jwt->validateToken($token, 0, [
                'issuer' => $uri,
                'audience' => $uri,
                'id' => $claim,
            ]);
        }
        catch (\InvalidArgumentException | ValidatorException | \DateMalformedStringException | \TypeError $exception) {
            throw new HttpException('Invalid authentication token.', 401);
        }

        if ($errors !== []) {
            throw new HttpException('Invalid authentication token.', 401);
        }

        try {
            $ret = $claims->has('sub') ? json_decode($claims->get('sub'), true, flags: JSON_THROW_ON_ERROR) : [];
        }
        catch (\JsonException | \TypeError $exception) {
            throw new HttpException('Invalid authentication token.', 401);
        }
        return is_array($ret) ? $ret : [];
    }
    
    /**
     * Resolve a claim from a bearer authorization header.
     *
     * @param array<int, string> $authorization Header parts, usually
     *     `[Bearer, token]`.
     *
     * @return array<string, mixed> Claim payload or an empty array when the
     *     header is not a bearer token.
     * @throws HttpException With status 401 when the bearer credential is
     *     malformed or its token is invalid. Unsupported schemes return [].
     */
    public function getClaimFromAuthorization(array $authorization): array
    {
        $authorizationType = $authorization[0] ?? null;
        $authorizationToken = $authorization[1] ?? null;
        
        if ($authorizationType && strtolower($authorizationType) === 'bearer') {
            if (!$authorizationToken || count($authorization) !== 2) {
                throw new HttpException('Invalid authentication token.', 401);
            }
            return $this->getClaimFromToken($authorizationToken, $this->getSessionKey());
        }
        
        return [];
    }
    
    /**
     * Return the request JSON body as an object.
     *
     * Phalcon throws for invalid JSON; identity credential lookup treats that
     * as an empty body so malformed optional JSON does not prevent header/query
     * credentials from being evaluated.
     *
     * @return stdClass Parsed body or an empty object.
     */
    private function getJsonRawBody()
    {
        try {
            return $this->request->getJsonRawBody();
        }
        catch (\InvalidArgumentException $e) {
            return new stdClass();
        }
    }
}
