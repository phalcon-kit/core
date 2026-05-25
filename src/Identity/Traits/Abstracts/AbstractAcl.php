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
 * Declares ACL role methods required by traits composed into the identity manager.
 *
 * This abstract trait lets small behavior traits call ACL methods without
 * depending on a concrete manager class. It is a compile-time contract only;
 * implementations should follow the public {@see \PhalconKit\Identity\Traits\Interfaces\AclInterface}.
 *
 * @phpstan-ignore trait.unused
 */
trait AbstractAcl
{
    abstract public function getAclRoles(?array $roleList = null): array;
}
