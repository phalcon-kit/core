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

use Phalcon\Db\Column;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Mvc\Model\Behavior\Security as SecurityBehavior;

/**
 * Resolves identity users from the configured user model.
 *
 * The trait keeps separate cached instances for the effective user and the
 * original impersonating user. It reads only lightweight ids from the identity
 * session payload, then loads full user records with role, group, and type
 * relations for downstream ACL and API payloads.
 */
trait User
{
    /**
     * Effective user for the current request.
     */
    protected ?UserInterface $user = null;
    
    /**
     * Original user when the current request is impersonating another user.
     */
    protected ?UserInterface $userAs = null;
    
    /**
     * Return the effective user or original impersonating user.
     *
     * Unless `$force` is set, the method returns the cached instance for the
     * requested slot. A fresh lookup reads `userId` or `asUserId` from the
     * session identity payload and eager-loads role, group, and type relations
     * through the configured user model.
     *
     * @param bool $as Return the original impersonating user instead of the
     *     effective user.
     * @param bool|null $force Force a fresh lookup instead of using the cached
     *     model instance.
     *
     * @return UserInterface|null User model or null when no identity is stored.
     */
    public function getUser(bool $as = false, ?bool $force = null): ?UserInterface
    {
        if (!$force) {
            if ($as && !empty($this->userAs)) {
                return $this->userAs;
            }
            else if (!$as && !empty($this->user)) {
                return $this->user;
            }
        }
        
        $sessionIdentity = $this->getSessionIdentity();
        
        $userId = $as
            ? $sessionIdentity['asUserId'] ?? null
            : $sessionIdentity['userId'] ?? null;
        
        $user = null;
        if (!empty($userId)) {
            SecurityBehavior::staticStart();
            
            $user = $this->models->getUser()::findFirstWith([
                'RoleList',
                'GroupList',
                'TypeList',
            ], [
                'id = :id:',
                'bind' => ['id' => (int)$userId],
                'bindTypes' => ['id' => Column::BIND_PARAM_INT],
            ]);
            
            if ($user) {
                $user = $this->requireIdentityUser($user);
            }
            
            SecurityBehavior::staticStop();
        }
        
        $as
            ? $this->setUserAs($user)
            : $this->setUser($user);
        
        return $user ?: null;
    }

    /**
     * Require the configured user model query to return the identity contract.
     *
     * The identity manager can resolve the user model from application
     * configuration, so the query result is a framework integration boundary.
     * This helper keeps `getUser()` focused on session/user selection while
     * failing clearly if the configured model does not implement the expected
     * PhalconKit user interface.
     *
     * @param mixed $user User record returned by the configured model.
     *
     * @return UserInterface
     *
     * @throws ServiceException When the configured user model does not return
     *     the PhalconKit identity user contract.
     */
    protected function requireIdentityUser(mixed $user): UserInterface
    {
        if ($user instanceof UserInterface) {
            return $user;
        }

        throw new ServiceException(sprintf(
            'Configured identity user model must return an instance of "%s"; got "%s".',
            UserInterface::class,
            get_debug_type($user)
        ));
    }
    
    /**
     * Cache the effective user for this manager instance.
     *
     * @param UserInterface|null $user User model or null to clear the cache.
     */
    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }
    
    /**
     * Return the original user during impersonation.
     *
     * @return UserInterface|null Original user or null when not impersonating.
     */
    public function getUserAs(): ?UserInterface
    {
        return $this->getUser(true);
    }
    
    /**
     * Cache the original user for this manager instance.
     *
     * @param UserInterface|null $user User model or null to clear the cache.
     */
    public function setUserAs(?UserInterface $user): void
    {
        $this->userAs = $user;
    }
    
    /**
     * Return the effective or original user's id.
     *
     * @param bool $as Return the original impersonating user id.
     *
     * @return int|null User id or null when no matching user is logged in.
     */
    public function getUserId(bool $as = false): ?int
    {
        $user = $this->getUser($as);
        return isset($user) ? (int)$user->getId() : null;
    }
    
    /**
     * Return the original user's id during impersonation.
     *
     * @return int|null Original user id or null when not impersonating.
     */
    public function getUserAsId(): ?int
    {
        return $this->getUserId(true);
    }
    
    /**
     * Return roles associated with the current effective identity.
     *
     * @return array<string, object> Role entities keyed by their stable key.
     */
    public function getRoleList(): array
    {
        return $this->getIdentity()['roleList'] ?? [];
    }
    
    /**
     * Return groups associated with the current effective identity.
     *
     * @return array<string, object> Group entities keyed by their stable key.
     */
    public function getGroupList(): array
    {
        return $this->getIdentity()['groupList'] ?? [];
    }
    
    /**
     * Return types associated with the current effective identity.
     *
     * @return array<string, object> Type entities keyed by their stable key.
     */
    public function getTypeList(): array
    {
        return $this->getIdentity()['typeList'] ?? [];
    }
    
    /**
     * Check whether the effective or original user is logged in.
     *
     * @param bool $as Check the original impersonating user.
     * @param bool $force Force a fresh lookup instead of using cached users.
     *
     * @return bool True when a matching user model can be resolved.
     */
    public function isLoggedIn(bool $as = false, bool $force = false): bool
    {
        return (bool)$this->getUser($as, $force);
    }
    
    /**
     * Check whether the current session is impersonating another user.
     *
     * @param bool $force Force a fresh lookup of the original user.
     *
     * @return bool True when `asUserId` resolves to a user.
     */
    public function isLoggedInAs(bool $force = false): bool
    {
        return $this->isLoggedIn(true, $force);
    }
    
    /**
     * Find a user by primary key through the configured user model.
     *
     * @param int $id User id.
     *
     * @return UserInterface|null Matching user or null.
     */
    public function findUserById(int $id): ?UserInterface
    {
        return $this->models->getUser()::findFirst([
            'id = :id:',
            'bind' => ['id' => $id],
            'bindTypes' => ['id' => Column::BIND_PARAM_INT],
        ]);
    }
    
    /**
     * Find a user by email through the configured user model.
     *
     * @param string $string Email address.
     *
     * @return UserInterface|null Matching user or null.
     */
    public function findUserByEmail(string $string): ?UserInterface
    {
        return $this->models->getUser()::findFirst([
            'email = :email:',
            'bind' => [
                'email' => $string,
            ],
            'bindTypes' => [
                'email' => Column::BIND_PARAM_STR,
            ],
        ]);
    }
}
