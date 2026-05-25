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

/**
 * Contract for role matching and configured role inheritance.
 */
interface RoleInterface
{
    /**
     * Check whether the current identity matches requested roles.
     *
     * The legacy `$or` parameter name is misleading: `false` checks whether any
     * requested role matches, while `true` requires every requested role to
     * match. The parameter is kept for compatibility.
     *
     * @param array<int, string>|null $roles Role names to test.
     * @param bool $or Legacy mode flag; `false` means any-match, `true` means
     *     all-match at the current level.
     * @param bool $inherit Include configured inherited roles.
     */
    public function hasRole(?array $roles = null, bool $or = false, bool $inherit = true): bool;
    
    /**
     * Match one or more values against a haystack.
     *
     * Nested arrays flip the current matching mode, allowing callers to express
     * alternating any/all groups without a separate expression object.
     *
     * @param array<int, mixed>|string|null $needles Values or nested groups to
     *     match.
     * @param array<int, string> $haystack Available values.
     * @param bool $or Legacy mode flag; `false` means any-match, `true` means
     *     all-match at the current level.
     */
    public function has(array|string|null $needles = null, array $haystack = [], bool $or = false): bool;
    
    /**
     * Resolve configured inherited roles for the provided base roles.
     *
     * @param array<int, string> $roleIndexList Base role names.
     *
     * @return array<int, string>
     */
    public function getInheritedRoleList(array $roleIndexList = []): array;
}
