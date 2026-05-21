<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use PhalconKit\Models\User;

class ProtectedRelationshipUser extends User
{
    protected mixed $rolelist = null;

    #[\Override]
    public function initialize(): void
    {
        parent::initialize();
        $this->setSource('user');
    }

    public function getProtectedRoleList(): mixed
    {
        return $this->rolelist;
    }

    public function setProtectedRoleList(mixed $roleList): void
    {
        $this->rolelist = $roleList;
    }
}
