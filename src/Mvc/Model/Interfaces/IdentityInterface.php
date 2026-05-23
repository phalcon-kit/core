<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Mvc\Model\Interfaces;

use PhalconKit\Identity\ManagerInterface;
use PhalconKit\Models\Interfaces\UserInterface;

interface IdentityInterface
{
    /**
     * Resolve the identity manager used by model identity helpers.
     *
     * Implementations should resolve the service from the model DI and surface
     * missing or incompatible services as a PhalconKit service exception.
     *
     * @return ManagerInterface Identity manager used by the model helpers.
     * @throws \PhalconKit\Exception\ServiceException When the service cannot be
     *     resolved or does not implement the expected contract.
     */
    public function getIdentityService(): ManagerInterface;

    /**
     * Check whether the primary or delegated identity is authenticated.
     *
     * @param bool $as When true, checks delegated/impersonated identity state
     *     instead of the primary identity.
     * @return bool True when the selected identity is logged in.
     * @throws \PhalconKit\Exception\ServiceException When the identity service
     *     cannot be resolved by the implementation.
     */
    public function isLoggedIn(bool $as = false): bool;

    /**
     * Check whether a delegated/impersonated identity is active.
     *
     * @return bool True when the current identity is acting as another user.
     * @throws \PhalconKit\Exception\ServiceException When the identity service
     *     cannot be resolved by the implementation.
     */
    public function isLoggedInAs(): bool;

    /**
     * Return the current primary or delegated user.
     *
     * @param bool $as When true, returns the delegated/impersonated user.
     * @return UserInterface|null Matching user model, or null when unavailable.
     * @throws \PhalconKit\Exception\ServiceException When the identity service
     *     cannot be resolved by the implementation.
     */
    public function getCurrentUser(bool $as = false): ?UserInterface;

    /**
     * Return the delegated/impersonated user model.
     *
     * @return UserInterface|null Delegated user, or null when no delegated
     *     identity is active.
     * @throws \PhalconKit\Exception\ServiceException When the identity service
     *     cannot be resolved by the implementation.
     */
    public function getCurrentUserAs(): ?UserInterface;

    /**
     * Return the current primary or delegated user ID.
     *
     * @param bool $as When true, returns the delegated/impersonated user ID.
     * @return int|null User ID, or null when no matching user is available.
     * @throws \PhalconKit\Exception\ServiceException When the identity service
     *     cannot be resolved by the implementation.
     */
    public function getCurrentUserId(bool $as = false): ?int;

    /**
     * Build a deferred callback for resolving the current user ID.
     *
     * Behaviors use this to evaluate identity state during model lifecycle
     * events instead of capturing a stale ID at initialization time.
     *
     * @param bool $as When true, the callback resolves the delegated user ID.
     * @return \Closure():?int Callback returning the selected user ID.
     */
    public function getCurrentUserIdCallback(bool $as = false): \Closure;
}
