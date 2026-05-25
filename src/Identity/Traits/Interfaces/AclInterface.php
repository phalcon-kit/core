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
 * Contract for building the effective ACL roles of an identity.
 */
interface AclInterface
{
    /**
     * Return ACL role objects keyed by role name.
     *
     * Implementations should include contextual roles such as `everyone`,
     * execution-context roles, identity roles, guest fallback, and inherited
     * roles according to the identity manager policy.
     *
     * @param array<int, string>|null $roleList Optional base role names to use
     *     instead of deriving roles from the current identity.
     *
     * @return array<string, \Phalcon\Acl\Role>
     */
    public function getAclRoles(?array $roleList = null): array;
}
