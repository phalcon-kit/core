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

/**
 * Provides role matching and configured role inheritance.
 *
 * The public methods keep the historical `$or` parameter name for compatibility.
 * In practice the flag controls the current matching level: `false` performs an
 * any-match check and `true` performs an all-match check. Nested arrays flip
 * the mode, which lets callers express simple alternating role expressions
 * without introducing a separate parser.
 */
trait Role
{
    use AbstractInjectable;
    
    /**
     * Check whether the current identity has the requested roles.
     *
     * When inheritance is enabled, configured parent roles are added to the
     * current role list before matching. With the legacy `$or` flag left at its
     * default, the method returns true when any requested role matches. Passing
     * `true` requires every requested role to match at the current level.
     *
     * @param array<int, string>|null $roles Role names to check.
     * @param bool $or Legacy mode flag; `false` means any-match and `true`
     *     means all-match.
     * @param bool $inherit Include roles inherited through configuration.
     *
     * @return bool True if the user satisfies the role conditions, false otherwise.
     */
    public function hasRole(?array $roles = null, bool $or = false, bool $inherit = true): bool
    {
        $roleList = array_keys($this->getRoleList());
        return $this->has($roles, $inherit ? array_merge($roleList, $this->getInheritedRoleList($roleList)) : $roleList, $or);
    }
    
    /**
     * Match one or more values against a haystack.
     *
     * At the current level, the legacy `$or` flag behaves as follows:
     * `false` returns true when any needle matches, and `true` returns true only
     * when every needle matches. Each nested array flips the mode for that
     * nested group, enabling expressions such as "all of these groups, where
     * each group may contain any of these roles".
     *
     * Examples:
     *
     * $this->has(['dev', 'admin'], $roles); // 'dev' OR 'admin'
     * $this->has(['dev', 'admin'], $roles, true); // 'dev' AND 'admin'
     * $this->has([['dev', 'admin']], $roles, true); // ('dev' OR 'admin')
     *
     * @param array<int, mixed>|string|null $needles Values or nested groups to
     *     match.
     * @param array<int, string> $haystack Values available to match against.
     * @param bool $or Legacy mode flag; `false` means any-match and `true`
     *     means all-match at the current level.
     *
     * @return bool True when the expression matches the haystack.
     */
    public function has(array|string|null $needles = null, array $haystack = [], bool $or = false): bool
    {
        if (!is_array($needles)) {
            $needles = isset($needles) ? [$needles] : [];
        }
        
        if (empty($needles)) {
            return false;
        }
        
        $result = [];
        foreach ($needles as $needle) {
            if (is_array($needle)) {
                $result [] = $this->has($needle, $haystack, !$or);
            }
            else {
                $result [] = in_array($needle, $haystack, true);
            }
        }
        
        return $or ?
            !in_array(false, $result, true) :
            in_array(true, $result, true);
    }
    
    /**
     * Resolve inherited roles from the permissions configuration.
     *
     * The method walks `permissions.roles.<role>.inherit` recursively, avoids
     * re-processing roles it has already inspected, and returns a de-duplicated
     * list. When no base or inherited role is present, `guest` is added. The
     * universal `everyone` role is always included.
     *
     * @param array<int, string> $roleIndexList Base role names to resolve.
     *
     * @return array<int, string> Unique inherited role names.
     */
    public function getInheritedRoleList(array $roleIndexList = []): array
    {
        $inheritedRoleList = [];
        $processedRoleIndexList = [];
        
        // While we still have role index list to process
        while (!empty($roleIndexList)) {
            // Process role index list
            foreach ($roleIndexList as $roleIndex) {
                // Get inherited roles from config service
                
                $configRoleList = $this->config->path('permissions.roles.' . $roleIndex . '.inherit', false);
                
                if ($configRoleList) {
                    // Append inherited role to process list
                    $roleList = $configRoleList->toArray();
                    $roleIndexList = array_merge($roleIndexList, $roleList);
                    $inheritedRoleList = array_merge($inheritedRoleList, $roleList);
                }
                
                // Add role index to processed list
                $processedRoleIndexList [] = $roleIndex;
            }
            
            // Keep the unprocessed role index list
            $roleIndexList = array_filter(array_unique(array_diff($roleIndexList, $processedRoleIndexList)));
        }
        
        // append a guest role if no roles were detected
        if (empty($roleList) && empty($inheritedRoleList)) {
            $inheritedRoleList [] = 'guest';
        }
        
        // always append everyone a role
        if (!in_array('everyone', $roleIndexList) && !in_array('everyone', $inheritedRoleList)) {
            $inheritedRoleList [] = 'everyone';
        }
        
        // Return the list of an inherited role list (recursively)
        return array_values(array_filter(array_unique($inheritedRoleList)));
    }
}
