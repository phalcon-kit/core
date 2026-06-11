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

namespace PhalconKit\Tests\Unit\Acl;

use PhalconKit\Acl\PermissionName;
use PhalconKit\Tests\Unit\AbstractUnit;

class PermissionNameTest extends AbstractUnit
{
    public function testActionNormalizesPhpAndRouteNamesToDashCase(): void
    {
        $this->assertSame('find-with', PermissionName::action('findWith'));
        $this->assertSame('find-with', PermissionName::action('find-with'));
        $this->assertSame('find-with', PermissionName::action('find_with'));
        $this->assertSame('find-first-with', PermissionName::action('findFirstWithAction'));
        $this->assertSame('*', PermissionName::action('*'));
    }

    public function testActionCandidatesKeepRawCamelCaseForCompatibility(): void
    {
        $this->assertSame(['find-with', 'findWith'], PermissionName::actionCandidates('findWith'));
        $this->assertSame(['find-with'], PermissionName::actionCandidates('find-with'));
    }

    public function testAccessListAddsDashCaseAliasesWithoutDroppingRawNames(): void
    {
        $this->assertSame(
            ['find-with', 'findWith', 'archive-project'],
            PermissionName::accessList(['findWith', 'archive-project', null])
        );
    }

    public function testHandlerCandidatesIncludeClassAndRouteAliases(): void
    {
        $this->assertSame(
            [
                'App\\Modules\\Api\\Controllers\\ProjectUserController',
                'ProjectUserController',
                'ProjectUser',
                'project-user',
                'project-user-controller',
            ],
            PermissionName::handlerCandidates(
                'App\\Modules\\Api\\Controllers\\ProjectUserController',
                'project-user-controller'
            )
        );
    }
}
