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

use PhalconKit\Models\Interfaces\UserInterface as UserModelInterface;

/**
 * Contract for resolving the effective and impersonating identity users.
 */
interface UserInterface
{
    /**
     * Return the effective user or original impersonating user.
     *
     * @param bool $as Return the original user during impersonation.
     * @param bool|null $force Force a fresh model lookup instead of using the
     *     cached instance.
     */
    public function getUser(bool $as = false, ?bool $force = null): ?UserModelInterface;
    
    /**
     * Cache the effective user for the current manager instance.
     */
    public function setUser(?UserModelInterface $user): void;
    
    /**
     * Return the original user when the session is impersonating another user.
     */
    public function getUserAs(): ?UserModelInterface;
    
    /**
     * Cache the original impersonating user for the current manager instance.
     */
    public function setUserAs(?UserModelInterface $user): void;
    
    /**
     * Return the effective or original user's id.
     *
     * @param bool $as Return the original impersonating user id.
     */
    public function getUserId(bool $as = false): ?int;
    
    /**
     * Return the original user's id during impersonation.
     */
    public function getUserAsId(): ?int;
    
    /**
     * Return identity roles keyed by their stable role key.
     *
     * @return array<string, object>
     */
    public function getRoleList(): array;
    
    /**
     * Return identity groups keyed by their stable group key.
     *
     * @return array<string, object>
     */
    public function getGroupList(): array;
    
    /**
     * Return identity types keyed by their stable type key.
     *
     * @return array<string, object>
     */
    public function getTypeList(): array;
    
    /**
     * Check whether the effective or original user is logged in.
     *
     * @param bool $as Check the original impersonating user.
     * @param bool $force Force a fresh model lookup.
     */
    public function isLoggedIn(bool $as = false, bool $force = false): bool;
    
    /**
     * Check whether the session is currently impersonating another user.
     */
    public function isLoggedInAs(bool $force = false): bool;
    
    /**
     * Find a user by primary key using the configured user model.
     */
    public function findUserById(int $id): ?UserModelInterface;
    
    /**
     * Find a user by email using the configured user model.
     */
    public function findUserByEmail(string $string): ?UserModelInterface;
}
