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

namespace PhalconKit\Modules\Oauth2\Controllers;

use League\OAuth2\Client\Grant\RefreshToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Modules\Oauth2\Controller;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Exception\HttpException;

/**
 * @property GenericProvider $oauth2Provider
 */
abstract class AbstractController extends Controller
{
    public const string PROVIDER_CLIENT = 'client';
    public const string PROVIDER_FACEBOOK = 'facebook';
    public const string PROVIDER_GITHUB = 'github';
    public const string PROVIDER_GOOGLE = 'google';
    public const string PROVIDER_INSTAGRAM = 'instagram';
    public const string PROVIDER_LINKEDIN = 'linkedin';
    
    public string $defaultScope = 'email';
    
    public string $providerName = self::PROVIDER_CLIENT;
    
    public string $sessionKey = 'oauth2-generic-state';

    /** @var array{state: string, expiresAt: int, provider: string, pkceCode: string|null}|null Validated context for one code exchange. */
    private ?array $authorizationContext = null;
    
    /**
     * Start authorization and store a bounded, provider-specific state record.
     *
     * `oauth2.stateLifetime` controls its lifetime in seconds (default 600).
     * A new attempt replaces the pending attempt for this provider/session.
     * Configured League PKCE verifiers are retained for the callback request.
     *
     * @throws ConfigurationException When the state lifetime is invalid.
     */
    public function authorizationUrlAction(?string $scope = null): ResponseInterface
    {
        $lifetime = filter_var($this->config->path('oauth2.stateLifetime', 600), FILTER_VALIDATE_INT);
        if ($lifetime === false || $lifetime < 1 || $lifetime > 86400) {
            throw new ConfigurationException('OAuth2 state lifetime must be between 1 and 86400 seconds.');
        }
        $this->authorizationContext = null;
        $redirectUrl = $this->oauth2Provider->getAuthorizationUrl([
            'scope' => explode(',', $scope ?: $this->request->get('scope', 'string', $this->defaultScope)),
        ]);
        $this->session->set($this->sessionKey, [
            'state' => $this->oauth2Provider->getState(),
            'expiresAt' => time() + $lifetime,
            'provider' => $this->providerName,
            'pkceCode' => $this->oauth2Provider->getPkceCode(),
        ]);
        return $this->response->redirect($redirectUrl);
    }

    /**
     * Validate and consume callback state before authorizing one code exchange.
     *
     * Values are compared verbatim; malformed, expired, legacy string, missing,
     * and wrong-provider states fail closed. A successful call consumes stored
     * state and allows getAccessToken() once in this controller instance.
     * Session storage must serialize requests or provide equivalent atomic
     * consumption when implementing a custom concurrent session backend.
     */
    public function validateState(?string $state = null): bool
    {
        $this->authorizationContext = null;
        $state ??= $this->request->get('state');
        $context = $this->session->get($this->sessionKey);
        if (!is_array($context) || !is_string($context['state'] ?? null)
            || !is_int($context['expiresAt'] ?? null) || $context['expiresAt'] <= time()
            || ($context['provider'] ?? null) !== $this->providerName
        ) {
            $this->session->remove($this->sessionKey);
            return false;
        }
        if (!is_string($state) || $state === '' || !hash_equals($context['state'], $state)) {
            return false;
        }
        $this->session->remove($this->sessionKey);
        $this->authorizationContext = [
            'state' => $state,
            'expiresAt' => $context['expiresAt'],
            'provider' => $this->providerName,
            'pkceCode' => is_string($context['pkceCode'] ?? null) ? $context['pkceCode'] : null,
        ];
        return true;
    }

    /**
     * Exchange a callback code only after successful one-time state validation.
     *
     * Existing callbacks may call validateState() first; otherwise this method
     * validates request state itself. The context is consumed before the remote
     * exchange, including failed exchanges. Retry by starting authorization again.
     *
     * @throws HttpException With generic status 401 for invalid callback credentials.
     * @throws IdentityProviderException When the provider rejects the exchange.
     */
    public function getAccessToken(?string $code = null): AccessTokenInterface
    {
        if ($this->authorizationContext === null && !$this->validateState()) {
            throw new HttpException('Invalid OAuth2 authorization.', 401);
        }
        $context = $this->authorizationContext;
        $this->authorizationContext = null;
        $code ??= $this->request->get('code');
        if ($context === null || $context['expiresAt'] <= time() || !is_string($code) || $code === '') {
            throw new HttpException('Invalid OAuth2 authorization.', 401);
        }
        // Clear stale verifier state on reused provider instances as well.
        $this->oauth2Provider->setPkceCode($context['pkceCode'] ?? '');
        return $this->oauth2Provider->getAccessToken('authorization_code', ['code' => $code]);
    }

    /**
     * Refresh Token
     * @throws IdentityProviderException
     */
    public function refreshToken(?string $refreshToken = null): AccessTokenInterface
    {
        $refreshToken ??= $this->request->get('refreshToken', 'string');
        return $this->oauth2Provider->getAccessToken(new RefreshToken(), ['code' => $refreshToken]);
    }
    
    /**
     * Use this to interact with an API on the users behalf
     */
    public function getToken(AccessTokenInterface $token): string
    {
        return $token->getToken();
    }
    
    /**
     * Use this to get a new access token if the old one expires
     */
    public function getRefreshToken(AccessTokenInterface $token): ?string
    {
        return $token->getRefreshToken();
    }
    
    /**
     * Unix timestamp at which the access token expires
     */
    public function getExpires(AccessTokenInterface $token): ?int
    {
        return $token->getExpires();
    }
    
    /**
     * Requests and returns the resource owner of given access token.
     */
    public function getResourceOwner(AccessToken $token): ResourceOwnerInterface
    {
        return $this->oauth2Provider->getResourceOwner($token);
    }
}
