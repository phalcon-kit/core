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

namespace PhalconKit\Identity\Traits\Abstracts;

/**
 * Declares role matching methods required by ACL and impersonation helpers.
 *
 * The role API keeps its legacy `$or` parameter for compatibility even though
 * the current behavior treats `false` as any-match and `true` as all-match at
 * the current nesting level.
 */
trait AbstractRole
{
    /**
     * @param array<int, string>|null $roles
     */
    abstract public function hasRole(?array $roles = null, bool $or = false, bool $inherit = true): bool;
    
    /**
     * @param array<int, mixed>|string|null $needles
     * @param array<int, string> $haystack
     */
    abstract public function has(array|string|null $needles = null, array $haystack = [], bool $or = false): bool;
    
    /**
     * @param array<int, string> $roleIndexList
     *
     * @return array<int, string>
     */
    abstract public function getInheritedRoleList(array $roleIndexList = []): array;
}
