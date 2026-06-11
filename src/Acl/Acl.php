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

namespace PhalconKit\Acl;

use Phalcon\Di\AbstractInjectionAware;
use Phalcon\Acl\Adapter\Memory;
use Phalcon\Acl\Component;
use Phalcon\Acl\Role;
use PhalconKit\Support\Options\Options;

/**
 * Builds native Phalcon ACL instances from PhalconKit permission config.
 *
 * The input permission structure is the same shape used by REST controllers:
 * roles can reference reusable feature blocks, components map to allowed
 * accesses, and role inheritance can be applied through a configurable key.
 * The builder returns a native in-memory ACL so downstream code can use normal
 * Phalcon ACL checks after PhalconKit has expanded the app config.
 */
class Acl extends AbstractInjectionAware implements AclInterface
{
    use Options;
    
    /**
     * Build an in-memory ACL for one or more configured component sections.
     *
     * `componentsName` lets callers build ACLs from alternate permission
     * sections, for example `components`, `models`, or app-specific groups.
     * When `permissions` is null, the method reads the `permissions` option
     * stored on this ACL service. Feature entries referenced by roles are merged
     * before components are registered.
     *
     * Integer component keys are treated as shorthand component names with `*`
     * access. A component named `*` is ignored because Phalcon's native ACL
     * still needs concrete component registrations.
     *
     * @param array<int, string> $componentsName Permission sections to inspect.
     * @param array<string, mixed>|null $permissions Permission tree to compile.
     * @param string $inherit Role-inheritance key inside the permission tree.
     *
     * @return Memory Native Phalcon in-memory ACL populated with roles,
     *     components, accesses, and inheritance.
     */
    #[\Override]
    public function get(array $componentsName = ['components'], ?array $permissions = null, string $inherit = 'inherit'): Memory
    {
        $acl = new Memory();
        $aclRoleList = [];
        
        $permissions ??= $this->getOption('permissions', []);
        $featureList = $permissions['features'] ?? [];
        $roleList = $permissions['roles'] ?? [];
        
        foreach ($roleList as $role => $rolePermission) {
            $role = $role === '*' ? 'everyone' : $role;
            $aclRole = new Role($role);
            $aclRoleList[$role] = $aclRole;
            $acl->addRole($aclRole);
            
            if (isset($rolePermission['features'])) {
                foreach ($rolePermission['features'] as $feature) {
                    if (!isset($featureList[$feature])) {
                        continue;
                    }
                    $rolePermission = array_merge_recursive($rolePermission, $featureList[$feature]);
                }
            }
            
            foreach ($componentsName as $componentName) {
                $components = $rolePermission[$componentName] ?? [];
                $components = is_array($components) ? $components : [$components];
                
                foreach ($components as $component => $accessList) {
                    // Support shorthand ['SomeController'] => '*'
                    if (is_int($component)) {
                        $component = $accessList;
                        $accessList = '*';
                    }
                    
                    if ($component === '*') {
                        continue;
                    }
                    
                    $aclAccess = PermissionName::accessList($accessList);
                    $aclComponent = new Component($component);
                    $acl->addComponent($aclComponent, $aclAccess);
                    $acl->allow((string)$aclRole, (string)$aclComponent, $aclAccess);
                }
            }
        }
        
        /**
         * Add inheritance (role extends)
         */
        foreach ($aclRoleList as $role => $aclRole) {
            $inheritList = $permissions[$role][$inherit] ?? [];
            $inheritList = is_array($inheritList) ? $inheritList : [$inheritList];
            foreach ($inheritList as $inheritRole) {
                $acl->addInherit((string)$aclRole, $aclRoleList[$inheritRole]);
            }
        }
        
        return $acl;
    }
}
