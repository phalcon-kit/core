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
use Phalcon\Filter\Filter;
use stdClass;

trait Jwt
{
    use AbstractInjectable;
    
    public array $claim = [];
    
    /**
     * Generates a new JWT and refresh token based on the specified claim and configuration.
     * If the claim does not have a key or is refreshed, it creates a new key and updates the session if enabled.
     *
     * @param bool $refresh Indicates whether to refresh the claim by generating a new key and invalidating previous tokens.
     * @return array Contains the generated JWT, refresh token, and a flag indicating if the claim was refreshed.
     * @throws SecurityException When token key generation fails.
     * @throws ValidatorException When JWT validation fails.
     */
    public function getJwt(bool $refresh = false): array
    {
        $claim = $this->getClaim($refresh, $refresh);
        
        // this enables or disable the session fallback (not recommended to enable)
        $sessionFallback = $this->config->path('identity.sessionFallback', false);
        
        // undefined key, create a new one using uuid
        if (empty($claim['key'])) {
            $this->setClaim(['key' => $this->security->getRandom()->uuid()]);
            
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
     * Retrieves the claim using different authentication methods such as JWT, Authorization Header, or Session.
     * If the claim is cached and not forced to refresh, it returns the cached claim.
     *
     * @param bool $refresh Determines whether to attempt refreshing the claim if available.
     * @param bool $force Forces bypassing the cached claim and retrieving a new one.
     * @return array The claim data or an empty array if no claim is found.
     */
    public function getClaim(bool $refresh = false, bool $force = false): array
    {
        // Using cached store
        if (!$force && !empty($this->claim)) {
            return $this->claim;
        }
        
        $json = $this->getJsonRawBody();
        
        if ($refresh) {
            $refreshToken = $this->request->get('refreshToken', [Filter::FILTER_STRING], $json->refreshToken ?? null);
            if (!empty($refreshToken)) {
                $this->setClaim($this->getClaimFromToken($refreshToken, $this->getSessionKey(true)));
                return $this->claim;
            }
        }
        
        // Using JWT
        $jwt = $this->request->get('jwt', [Filter::FILTER_STRING], $json->jwt ?? null);
        if (!empty($jwt)) {
            $this->setClaim($this->getClaimFromToken($jwt, $this->getSessionKey()));
            return $this->claim;
        }
        
        // Using X-Authorization Header (recommended)
        $authorizationHeaderKey = $this->config->path('identity.authorizationHeader', 'X-Authorization');
        $authorization = array_filter(explode(' ', $this->request->getHeader($authorizationHeaderKey)));
        if (!empty($authorization)) {
            $this->setClaim($this->getClaimFromAuthorization($authorization));
            return $this->claim;
        }
        
        // Using Session Fallback (less secure)
        if ($this->config->path('identity.sessionFallback', false) && $this->session->has($this->getSessionKey())) {
            $this->setClaim($this->session->get($this->getSessionKey()));
            return $this->claim;
        }
        
        // Unsupported authorization method
        return [];
    }
    
    /**
     * Sets the claim information for the current instance.
     *
     * @param array $claim The claim data to set.
     * @return void
     */
    public function setClaim(array $claim): void
    {
        $this->claim = $claim;
    }
    
    /**
     * Generates a JWT (JSON Web Token) using the provided identifier, payload data, and additional options.
     *
     * @param string $id The unique identifier for the JWT, typically representing a specific user or session.
     * @param array $data An associative array containing the payload data to be encoded in the JWT.
     * @param array $options An associative array of options for the token such as issuer, audience, subject, etc. Defaults will be applied if not provided.
     * @return string The generated JWT token as a string.
     * @throws ValidatorException
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
     * Extracts claims from a provided JWT token after validating it.
     *
     * @param string $token The JWT token to parse and validate.
     * @param string|null $claim An optional identifier to validate the token against.
     * @return array The claims extracted from the token, or an empty array if no valid claims are found.
     */
    public function getClaimFromToken(string $token, ?string $claim = null): array
    {
        $uri = $this->request->getScheme() . '://' . $this->request->getHttpHost();
        
        $token = $this->jwt->parseToken($token);
        
        $this->jwt->validateToken($token, 0, [
            'issuer' => $uri,
            'audience' => $uri,
            'id' => $claim,
        ]);
        $claims = $token->getClaims();
        
        $ret = $claims->has('sub') ? json_decode($claims->get('sub'), true) : [];
        return is_array($ret) ? $ret : [];
    }
    
    /**
     * Extracts claim information from the authorization header if it follows the Bearer token format.
     *
     * @param array $authorization The authorization header split into an array with the type and token.
     * @return array The claim information extracted from the token or an empty array if extraction fails.
     */
    public function getClaimFromAuthorization(array $authorization): array
    {
        $authorizationType = $authorization[0] ?? null;
        $authorizationToken = $authorization[1] ?? null;
        
        if ($authorizationType && $authorizationToken && strtolower($authorizationType) === 'bearer') {
            return $this->getClaimFromToken($authorizationToken, $this->getSessionKey());
        }
        
        return [];
    }
    
    /**
     * Retrieves the raw JSON body from the request.
     * If the body is not valid JSON, an empty stdClass object is returned.
     *
     * @return stdClass The raw JSON body as an object or an empty stdClass object if the input is invalid.
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
