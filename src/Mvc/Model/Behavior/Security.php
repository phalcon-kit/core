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

namespace PhalconKit\Mvc\Model\Behavior;

use Phalcon\Contracts\Acl\Adapter\Adapter as AclAdapter;
use Phalcon\Messages\Message;
use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Acl\AclInterface;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Identity\ManagerInterface as IdentityManagerInterface;
use PhalconKit\Mvc\Model\Behavior\Traits\ProgressTrait;
use PhalconKit\Mvc\Model\Behavior\Traits\SkippableTrait;

/**
 * Enforces ACL permissions for model lifecycle operations.
 *
 * The behavior checks configured model ACL components before write, restore,
 * reorder, and finder/count operations. It resolves the shared ACL and
 * identity services lazily from the default PhalconKit DI because native
 * Phalcon model behaviors are instantiated and notified by Phalcon internals,
 * not by constructor injection.
 *
 * Consumers can override the cached ACL adapter or role list with `setAcl()`
 * and `setRoles()` for tests, CLI workflows, or specialized authorization
 * flows. Passing null clears the cache and makes the next lookup resolve from
 * the default DI again.
 */
class Security extends Behavior
{
    use SkippableTrait;
    use ProgressTrait;
    
    /**
     * Cached ACL role names used by permission checks.
     *
     * The cache avoids resolving the identity service for every model event.
     * Set it to null through `setRoles()` when impersonation or login state
     * changes during the same process.
     *
     * @var array<int|string, string|\Stringable>|null
     */
    public static ?array $roles = null;

    /**
     * Cached ACL adapter containing model and component permissions.
     *
     * This is intentionally the native ACL adapter returned by the PhalconKit
     * ACL service, not the service wrapper itself.
     */
    public static ?AclAdapter $acl = null;
    
    /**
     * Replace or clear the cached ACL adapter used by model permission checks.
     *
     * Use this in tests or long-running processes when the permission matrix
     * changes after the behavior has already resolved it. Passing null clears
     * the cache so `getAcl()` will resolve a fresh adapter from the default DI.
     *
     * @param AclAdapter|null $acl Native ACL adapter to cache, or null to
     *     force lazy resolution on the next permission check.
     */
    public static function setAcl(?AclAdapter $acl = null): void
    {
        self::$acl = $acl;
    }
    
    /**
     * Resolve the ACL adapter containing model and component permissions.
     *
     * The default `acl` service must implement `PhalconKit\Acl\AclInterface`.
     * Its `get()` method is called with the `models` and `components` sections
     * so model-level checks share the same permission graph as dispatcher
     * checks.
     *
     * @return AclAdapter Native ACL adapter used for permission checks.
     * @throws ServiceException When the default DI or ACL service cannot be
     *     resolved through the PhalconKit DI contract.
     */
    public static function getAcl(): AclAdapter
    {
        if (is_null(self::$acl)) {
            $acl = ServiceResolver::fromDefault(
                'acl',
                AclInterface::class,
                context: 'model security behavior'
            );
            self::setAcl($acl->get(['models', 'components']));
        }

        if (!self::$acl instanceof AclAdapter) {
            throw new ServiceException('Could not resolve ACL adapter for model security behavior.');
        }

        return self::$acl;
    }
    
    /**
     * Replace or clear the cached role list used by model permission checks.
     *
     * Passing null clears the cache so `getRoles()` will resolve the current
     * identity service and rebuild the role list. This matters for tests,
     * impersonation, and long-running worker processes where identity state can
     * change without restarting PHP.
     *
     * @param array<int|string, string|\Stringable>|null $roles Role names or
     *     ACL role objects to check, or null to force lazy identity resolution
     *     next time.
     */
    public static function setRoles(?array $roles = null): void
    {
        self::$roles = $roles;
    }
    
    /**
     * Resolve ACL role names for the current identity.
     *
     * Role names are cached after the first lookup. Clear them with
     * `setRoles(null)` when identity state changes inside the same request or
     * worker process.
     *
     * @return array<int|string, string|\Stringable> Roles used against the ACL
     *     adapter.
     * @throws ServiceException When the default DI or identity service cannot
     *     be resolved through the PhalconKit DI contract.
     */
    public static function getRoles(): array
    {
        if (!isset(self::$roles)) {
            $identity = ServiceResolver::fromDefault(
                'identity',
                IdentityManagerInterface::class,
                context: 'model security behavior'
            );
            self::setRoles($identity->getAclRoles());
        }
        return self::$roles ?? [];
    }
    
    /**
     * Handle Phalcon model events and stop unauthorized operations.
     *
     * Only `before*` finder, aggregate, write, restore, and reorder events are
     * checked. The behavior returns null when disabled or when it is already
     * resolving permissions, which prevents recursive checks while the identity
     * service loads role data from models.
     *
     * @param string $type Phalcon event name such as `beforeCreate`,
     *     `beforeFind`, or `beforeReorder`.
     * @param ModelInterface $model Model instance being checked.
     * @return bool|null True when the event is allowed, false when the model
     *     receives a permission error message, or null when the event is not
     *     handled by this behavior.
     * @throws ServiceException When ACL or identity services cannot be resolved
     *     for a handled event.
     */
    #[\Override]
    public function notify(string $type, ModelInterface $model): ?bool
    {
        if (!$this->isEnabled()) {
            return null;
        }
        
        // skip check while still in progress
        // needed to retrieve roles for itself
        if ($this->inProgress()) {
            return null;
        }
        
        $beforeEvents = [
            'beforeFind' => true,
            'beforeFindFirst' => true,
            'beforeCount' => true,
            'beforeSum' => true,
            'beforeAverage' => true,
            'beforeCreate' => true,
            'beforeUpdate' => true,
            'beforeDelete' => true,
            'beforeRestore' => true,
            'beforeReorder' => true,
        ];
        
        if ($beforeEvents[$type] ?? false) {
            self::staticStart();
            
            $type = (str_starts_with($type, 'before')) ? lcfirst(substr($type, 6)) : $type;
            $isAllowed = $this->isAllowed($type, $model);
            
            self::staticStop();
            return $isAllowed;
        }
        
        return true;
    }
    
    /**
     * Check whether roles may execute an operation on a model class.
     *
     * If no ACL adapter or role list is provided, the method falls back to the
     * cached/default ACL and identity services. Denials are reported on the
     * model as Phalcon messages so callers following normal model validation
     * flows can inspect the failure reason.
     *
     * @param string $type Normalized operation name, such as `create`,
     *     `update`, `delete`, `restore`, `find`, or `count`.
     * @param ModelInterface $model Model instance being authorized.
     * @param AclAdapter|null $acl Optional adapter override, useful for
     *     tests or callers that already resolved a scoped ACL.
     * @param array<int|string, string|\Stringable>|null $roles Optional role
     *     names or ACL role objects to check instead of resolving the current
     *     identity roles.
     * @return bool True when any role is allowed; false when the model class is
     *     not registered in the ACL or all roles are denied.
     * @throws ServiceException When ACL or identity services must be resolved
     *     but are unavailable or incompatible.
     */
    public function isAllowed(string $type, ModelInterface $model, ?AclAdapter $acl = null, ?array $roles = null): bool
    {
        $acl ??= self::getAcl();
        $modelClass = get_class($model);
        
        // component not found
        if (!$acl->isComponent($modelClass)) {
            $model->appendMessage(new Message(
                'Model permission not found for `' . $modelClass . '`',
                'id',
                'NotFound',
                404
            ));
            return false;
        }
        
        // allowed for roles
        $roles ??= self::getRoles();
        foreach ($roles as $role) {
            if ($acl->isAllowed($role, $modelClass, $type)) {
                return true;
            }
        }
        
        $model->appendMessage(new Message(
            'Current identity forbidden to execute `' . $type . '` on `' . $modelClass . '`',
            'id',
            'Forbidden',
            403
        ));
        return false;
    }
}
