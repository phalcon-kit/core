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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use PhalconKit\Mvc\Controller\Attributes\AllowRoles;
use PhalconKit\Mvc\Controller\Attributes\AttachBehavior;
use PhalconKit\Mvc\Controller\Attributes\PermissionFeature;
use PhalconKit\Mvc\Controller\Rest;

#[PermissionFeature('project.view', actions: ['findWith', 'find-with'])]
#[AllowRoles('admin', actions: ['findWith'])]
#[AttachBehavior(BehaviorTestListener::class, actions: ['findWith'])]
final class AttributePolicyController extends Rest
{
    #[AllowRoles(['manager', 'admin'])]
    #[PermissionFeature('project.write')]
    #[AttachBehavior(InjectableBehaviorTestListener::class, roles: 'manager')]
    public function saveUserNodeAction(): void
    {
    }

    #[AttachBehavior(BehaviorTestListener::class, features: 'project.write', actions: 'findWith')]
    public function findWithAction(): void
    {
    }
}
