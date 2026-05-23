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

use PhalconKit\Exception\ServiceException;
use PhalconKit\Identity\ManagerInterface;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;

/**
 * Provides model-level access to the current PhalconKit identity service.
 *
 * The trait is used by attribution behaviors and application models that need
 * the current user or delegated user while handling model lifecycle events. It
 * resolves the identity manager from the model DI so tests and applications can
 * replace the identity service through normal container configuration.
 */
trait Identity
{
    use AbstractInjectable;
    
    /**
     * Resolve the current identity manager from the model DI.
     *
     * The service must implement `PhalconKit\Identity\ManagerInterface`; this
     * allows applications to provide custom identity managers without extending
     * the concrete core manager class.
     *
     * @return ManagerInterface Current identity manager service.
     * @throws ServiceException When the identity service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function getIdentityService(): ManagerInterface
    {
        return $this->getTypedService('identity', ManagerInterface::class, 'model identity helpers');
    }
    
    /**
     * Check whether the current identity is logged in.
     *
     * @param bool $as When true, checks delegated/impersonated identity state
     *     instead of the primary user.
     * @return bool True when the requested identity state is authenticated.
     * @throws ServiceException When the identity service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function isLoggedIn(bool $as = false): bool
    {
        return $this->getIdentityService()->isLoggedIn($as);
    }
    
    /**
     * Check whether the current identity is acting as another user.
     *
     * @return bool True when a delegated/impersonated user is active.
     * @throws ServiceException When the identity service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function isLoggedInAs(): bool
    {
        return $this->isLoggedIn(true);
    }
    
    /**
     * Return the current user model from the identity service.
     *
     * @param bool $as When true, returns the delegated/impersonated user
     *     instead of the primary user.
     * @return UserInterface|null Current user, delegated user, or null when no
     *     matching identity is available.
     * @throws ServiceException When the identity service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function getCurrentUser(bool $as = false): ?UserInterface
    {
        return $this->getIdentityService()->getUser($as);
    }
    
    /**
     * Return the delegated user model from the identity service.
     *
     * @return UserInterface|null Delegated/impersonated user, or null when no
     *     delegated identity is active.
     * @throws ServiceException When the identity service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function getCurrentUserAs(): ?UserInterface
    {
        return $this->getCurrentUser(true);
    }
    
    /**
     * Return the integer ID of the current or delegated user.
     *
     * @param bool $as When true, returns the delegated/impersonated user ID
     *     instead of the primary user ID.
     * @return int|null User ID cast to int, or null when no user is available
     *     or the user does not expose an ID.
     * @throws ServiceException When the identity service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function getCurrentUserId(bool $as = false): ?int
    {
        $user = $this->getCurrentUser($as);
        $id = $user?->getId();
        return $id === null ? null : (int)$id;
    }
    
    /**
     * Build a callback that returns the current or delegated user ID.
     *
     * Behaviors can store this closure and evaluate it later during lifecycle
     * events, ensuring they use the identity state at execution time rather
     * than initialization time.
     *
     * @param bool $as When true, the callback resolves the delegated user ID.
     * @return \Closure():?int Callback returning the requested user ID or null.
     */
    public function getCurrentUserIdCallback(bool $as = false): \Closure
    {
        return function () use ($as): ?int {
            return $this->getCurrentUserId($as);
        };
    }
}
