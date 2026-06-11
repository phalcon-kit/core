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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Attributes;

use PhalconKit\Mvc\Controller\Attributes\PermissionAttributeResolver;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\AttributePolicyController;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\BehaviorTestListener;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\InjectableBehaviorTestListener;

class PermissionAttributeResolverTest extends AbstractUnit
{
    public function testForControllerCompilesClassAndActionAttributesIntoPermissionConfig(): void
    {
        $permissions = PermissionAttributeResolver::forController(AttributePolicyController::class);
        $controller = AttributePolicyController::class;

        $this->assertSame(['find-with'], $permissions['features']['project.view']['controllers'][$controller]);
        $this->assertSame(['save-user-node'], $permissions['features']['project.write']['controllers'][$controller]);

        $this->assertSame(
            ['find-with', 'save-user-node'],
            $permissions['roles']['admin']['controllers'][$controller]
        );
        $this->assertSame(['save-user-node'], $permissions['roles']['manager']['controllers'][$controller]);

        $this->assertSame(
            [BehaviorTestListener::class],
            $permissions['roles']['everyone']['behaviorActions'][$controller]['find-with']
        );
        $this->assertSame(
            [InjectableBehaviorTestListener::class],
            $permissions['roles']['manager']['behaviorActions'][$controller]['save-user-node']
        );
        $this->assertSame(
            [BehaviorTestListener::class],
            $permissions['features']['project.write']['behaviorActions'][$controller]['find-with']
        );
    }

    public function testMergePermissionsAppendsNestedListsWithoutDuplicatingValues(): void
    {
        $merged = PermissionAttributeResolver::mergePermissions(
            [
                'roles' => [
                    'admin' => [
                        'features' => ['project.write'],
                        'controllers' => [
                            AttributePolicyController::class => ['find-with'],
                        ],
                    ],
                ],
            ],
            [
                'roles' => [
                    'admin' => [
                        'features' => ['project.write', 'project.delete'],
                        'controllers' => [
                            AttributePolicyController::class => ['find-with', 'delete'],
                        ],
                    ],
                ],
            ]
        );

        $this->assertSame(['project.write', 'project.delete'], $merged['roles']['admin']['features']);
        $this->assertSame(
            ['find-with', 'delete'],
            $merged['roles']['admin']['controllers'][AttributePolicyController::class]
        );
    }

    public function testMergePermissionsKeepsWildcardAccessDominant(): void
    {
        $merged = PermissionAttributeResolver::mergePermissions(
            [
                'roles' => [
                    'admin' => [
                        'controllers' => [
                            AttributePolicyController::class => ['*'],
                        ],
                    ],
                ],
            ],
            [
                'roles' => [
                    'admin' => [
                        'controllers' => [
                            AttributePolicyController::class => ['delete'],
                        ],
                    ],
                ],
            ]
        );

        $this->assertSame(['*'], $merged['roles']['admin']['controllers'][AttributePolicyController::class]);
    }
}
