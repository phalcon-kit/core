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

use Phalcon\Acl\Role;

trait Acl
{
    /**
     * Check whether the current identity has at least one (or all) of the given ACL roles.
     *
     * This method evaluates the provided role names against the **effective ACL role set**
     * returned by {@see getAclRoles()}, not just the raw identity roles. As a result:
     *
     * - Contextual roles such as `ws`, `cli`, and `everyone` are implicitly considered.
     * - The `guest` role may be present when no explicit identity roles exist.
     * - Inherited roles are already resolved and included.
     *
     * Internally, this delegates to {@see has()}, comparing:
     * - the requested roles (`$roles`)
     * - against the keys of the computed ACL role map
     *
     * @param array|null $roles List of role identifiers to test.
     *                          - `null` typically implies a truthy check against defaults,
     *                            depending on {@see has()} semantics.
     * @param bool $or Logical mode:
     *                          - `false` (default): all roles must be present (AND).
     *                          - `true`: at least one role must be present (OR).
     *
     * @return bool True if the role condition is satisfied, false otherwise.
     */
    public function hasAclRole(?array $roles = null, bool $or = false): bool
    {
        return $this->has($roles, array_keys($this->getAclRoles()), $or);
    }
    
    /**
     * Build and return the effective ACL role set for the current identity.
     *
     * This method computes the **final, authoritative list of ACL roles** used by
     * permission checks. The resulting role set is not a direct reflection of the
     * identity’s stored roles; it is a **context-aware, normalized, and expanded**
     * role map that accounts for execution context and role inheritance.
     *
     * Resolution rules, applied in order:
     *
     * 1. **Execution-context roles**
     *    - `ws` is added when running under a WebSocket bootstrap.
     *    - `cli` is added when running under a console/CLI bootstrap.
     *
     * 2. **Global role**
     *    - `everyone` is always added, regardless of identity state.
     *
     * 3. **Identity roles**
     *    - If `$roleList` is provided, it is treated as the authoritative base role list.
     *    - Otherwise, roles are derived from the current identity via `getRoleList()`.
     *
     * 4. **Guest fallback**
     *    - If no base roles are resolved, `guest` is added as the sole identity role.
     *
     * 5. **Inherited roles**
     *    - All roles implied by inheritance rules are automatically added.
     *
     * The returned array is keyed by role name and contains instantiated ACL `Role`
     * objects, ensuring uniqueness and preventing duplicate role registration.
     *
     * @param array|null $roleList Optional explicit list of base role identifiers.
     *                             When provided, it overrides identity-derived roles
     *                             but still participates in inheritance resolution.
     *
     * @return array<string, Role> Map of role name to ACL Role instance representing
     *                             the complete effective ACL role set.
     */
    public function getAclRoles(?array $roleList = null): array
    {
        $aclRoles = [];
        
        // Add websocket role when running in WS context
        if ($this->bootstrap->isWs()) {
            $aclRoles['ws'] = new Role('ws');
        }
        
        // Add console role when running in CLI context
        if ($this->bootstrap->isCli()) {
            $aclRoles['cli'] = new Role('cli');
        }
        
        // Add global role always present for every identity
        $aclRoles['everyone'] = new Role('everyone');
        
        // Resolve base role list (explicit override or identity-derived)
        $roleList ??= array_keys($this->getRoleList());
        
        // If no identity roles exist, fallback to guest
        if (empty($roleList)) {
            $aclRoles['guest'] = new Role('guest');
        }
        else {
            // Register all base identity roles
            foreach ($roleList as $role) {
                $aclRoles[$role] ??= new Role($role);
            }
            
            // Register all inherited roles derived from base roles
            foreach ($this->getInheritedRoleList($roleList) as $role) {
                $aclRoles[$role] ??= new Role($role);
            }
        }
        
        return $aclRoles;
    }
}
