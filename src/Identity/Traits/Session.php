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
use PhalconKit\Exception\ServiceException;
use PhalconKit\Identity\Traits\Abstracts\AbstractJwt;

/**
 * Stores the lightweight identity payload for the active manager.
 *
 * By default the payload is written under the active JWT claim key in the
 * configured session service. This allows JWT refreshes to rotate the storage
 * key and invalidate older tokens while preserving the small `userId` and
 * `asUserId` payload.
 *
 * When `identity.stateless` is enabled, the same payload is stored directly in
 * the JWT claim instead. That mode is intended for API clients that want the
 * identity layer to avoid PHP session persistence while the rest of the
 * application can still use sessions for unrelated features such as flash
 * messages, OAuth2 state, or locale persistence.
 */
trait Session
{
    use AbstractInjectable;
    use AbstractJwt;
    
    public const string SESSION_KEY = 'phalcon-kit-identity';
    public const string REFRESH_SUFFIX = '-refresh';

    /**
     * Claim fields that belong to token bookkeeping instead of the identity
     * payload returned by {@see getSessionIdentity()} in stateless mode.
     *
     * @var array<string, true>
     */
    private const array TOKEN_CLAIM_KEYS = [
        'key' => true,
    ];
    
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
     * Remove the identity payload and clear cached users and model ACL roles.
     * Overrides using custom storage must also call clearIdentityCache().
     *
     * If no claim key is available, there is no addressable identity payload
     * and the method intentionally becomes a no-op.
     */
    public function removeSessionIdentity(): void
    {
        $this->clearIdentityCache();
        if ($this->isStatelessIdentity()) {
            $this->setClaim(array_intersect_key($this->claim, self::TOKEN_CLAIM_KEYS));
            return;
        }

        $key = $this->getKey();
        if ($key) {
            $this->session->remove($key);
        }
    }
    
    /**
     * Replace the identity payload under the active claim key and clear user/ACL caches.
     *
     * Stateless storage preserves only token bookkeeping from the previous claim.
     * Include any custom identity fields in the replacement payload explicitly.
     * Overrides using custom storage must also call clearIdentityCache().
     * PHP-session storage renews the session ID before writing a non-empty
     * userId, including login, OAuth2, impersonation, and authenticated refresh.
     * The old session keeps unrelated data but loses this identity. Custom
     * persistence overrides own equivalent credential-fixation protection;
     * stateless identity does not resolve or renew the PHP session service.
     *
     * @param array<string, mixed> $identity Identity payload, usually including
     *     `userId` and optionally `asUserId`.
     * @throws ServiceException When the PHP session is inactive or cannot renew
     *     its ID. The replacement identity is not written on failure.
     */
    public function setSessionIdentity(array $identity): void
    {
        $this->clearIdentityCache();
        if ($this->isStatelessIdentity()) {
            $this->setClaim(array_merge(array_intersect_key($this->claim, self::TOKEN_CLAIM_KEYS), $identity));
            return;
        }

        $key = $this->getKey();
        if ($key) {
            if (!empty($identity['userId'])) {
                $this->renewIdentitySession($key);
            }
            $this->session->set($key, $identity);
        }
    }

    /**
     * Renew the active PHP session before assigning authenticated identity.
     *
     * Remove this identity before native regeneration persists the old session.
     * Unrelated session values survive in both sessions; only the new session
     * receives the replacement identity. The old anonymous session can expire
     * normally, avoiding immediate deletion during concurrent requests.
     *
     * @param string $key Validated claim key identifying the payload to replace.
     * @throws ServiceException When renewal fails; an unchanged session retains
     *     its previous payload and never receives the replacement identity.
     */
    protected function renewIdentitySession(string $key): void
    {
        $session = $this->session;
        $oldId = $session->getId();
        if (!$session->exists() || $oldId === '') {
            throw new ServiceException('An active session is required to establish identity.');
        }

        $previous = $session->get($key);
        $session->remove($key);
        try {
            $session->regenerateId(false);
            if ($session->getId() === $oldId || !$session->exists()) {
                throw new ServiceException('Unable to renew authentication session.');
            }
        }
        catch (\Throwable $exception) {
            if ($session->getId() === $oldId && $previous !== null) {
                $session->set($key, $previous);
            }
            throw new ServiceException('Unable to renew authentication session.', 0, $exception);
        }
    }
    
    /**
     * Return the identity payload stored under the active claim key.
     *
     * @return array<string, mixed> Empty when no key or payload exists.
     */
    public function getSessionIdentity(): array
    {
        if ($this->isStatelessIdentity()) {
            return array_diff_key($this->getClaim(), self::TOKEN_CLAIM_KEYS);
        }

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
        if ($this->isStatelessIdentity()) {
            return !empty($this->getSessionIdentity());
        }

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

    /**
     * Check whether identity state should be carried only in JWT claims.
     *
     * This setting does not disable the framework session service globally. It
     * only changes where the identity payload is persisted, which keeps
     * unrelated session consumers available for applications that still need
     * them.
     */
    protected function isStatelessIdentity(): bool
    {
        return (bool)$this->config->path('identity.stateless', false);
    }

    /**
     * Return fresh JWT values after an identity state change when needed.
     *
     * Stateless clients must replace their token after login, logout, OAuth2
     * login, and impersonation changes because the identity payload lives in
     * the token subject. Stateful clients keep receiving the legacy response
     * shape because the session-backed payload has already changed server-side.
     *
     * @return array{jwt?: string, refreshToken?: string, refreshed?: bool}
     */
    protected function getJwtForStatelessIdentity(): array
    {
        return $this->isStatelessIdentity() ? $this->getJwt() : [];
    }
}
