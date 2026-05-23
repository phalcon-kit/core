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

trait User
{
    /**
     * Current/impersonated user instance
     */
    protected ?UserInterface $user = null;
    
    /**
     * Original user instance when impersonating
     */
    protected ?UserInterface $userAs = null;
    
    /**
     * Return the current user or impersonated user object based on the session
     *
     * @param bool $as Flag to indicate whether to get the user as another user
     * @param bool|null $force Flag to indicate whether to force the retrieval of the user object
     *
     * @return UserInterface|null The user object or null if session is not available
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
     * Set the current user or impersonated user instance.
     *
     * @param UserInterface|null $user The user instance to set or null to unset the current user.
     * @return void
     */
    public function setUser(?UserInterface $user): void
    {
        $this->user = $user;
    }
    
    /**
     * Retrieve the original user when impersonating
     *
     * @return UserInterface|null The user instance or null if not available.
     */
    public function getUserAs(): ?UserInterface
    {
        return $this->getUser(true);
    }
    
    /**
     * Set the original user instance when impersonating
     *
     * @param UserInterface|null $user The user instance to set, or null to unset.
     * @return void
     */
    public function setUserAs(?UserInterface $user): void
    {
        $this->userAs = $user;
    }
    
    /**
     * Retrieves the current/impersonated user or the original user ID.
     *
     * @param bool $as Determines whether to retrieve the original user (true) or the current/impersonated one (false).
     * @return int|null Returns the user ID as an integer if available, or null if the user is not set.
     */
    public function getUserId(bool $as = false): ?int
    {
        $user = $this->getUser($as);
        return isset($user) ? (int)$user->getId() : null;
    }
    
    /**
     * Retrieves the original user ID when impersonating.
     *
     * @return int|null Returns the user ID as an integer if available, or null otherwise.
     */
    public function getUserAsId(): ?int
    {
        return $this->getUserId(true);
    }
    
    /**
     * Retrieves the list of roles associated with the current identity.
     *
     * @return array Returns an array of roles. If no roles are set, returns an empty array.
     */
    public function getRoleList(): array
    {
        return $this->getIdentity()['roleList'] ?? [];
    }
    
    /**
     * Retrieves the list of groups associated with the current identity.
     *
     * @return array Returns an array of group identifiers or an empty array if no groups are found.
     */
    public function getGroupList(): array
    {
        return $this->getIdentity()['groupList'] ?? [];
    }
    
    /**
     * Retrieves the list of types associated with the current identity.
     *
     * @return array Returns an array of types. If no types are found, returns an empty array.
     */
    public function getTypeList(): array
    {
        return $this->getIdentity()['typeList'] ?? [];
    }
    
    /**
     * Checks if the user is currently logged in.
     *
     * @param bool $as Determines whether to check the original user (true) or the current/impersonated one (false).
     * @param bool $force Forces a fresh check ignoring cached user session data when set to true.
     * @return bool Returns true if the user is logged in, false otherwise.
     */
    public function isLoggedIn(bool $as = false, bool $force = false): bool
    {
        return (bool)$this->getUser($as, $force);
    }
    
    /**
     * Checks if the user is logged in and impersonating another user.
     *
     * @param bool $force Determines whether to enforce a specific login check.
     * @return bool Returns true if the user is logged in based on the condition, otherwise false.
     */
    public function isLoggedInAs(bool $force = false): bool
    {
        return $this->isLoggedIn(true, $force);
    }
    
    /**
     * Finds and retrieves a user by their unique identifier.
     *
     * @param int $id The unique identifier of the user to be retrieved.
     * @return UserInterface|null Returns the user instance if found, or null if no user exists with the specified identifier.
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
     * Finds and retrieves a user by their email address.
     *
     * @param string $string The email address of the user to search for.
     * @return UserInterface|null Returns a UserInterface instance if a user with the specified email is found, or null if no user matches the email.
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
