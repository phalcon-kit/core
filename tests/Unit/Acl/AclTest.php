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

use Phalcon\Acl\Adapter\Memory;
use PhalconKit\Acl\Acl;
use PhalconKit\Tests\Unit\AbstractUnit;

class AclTest extends AbstractUnit
{
    public function testGetBuildsAclFromOptionsFeaturesComponentsAndInheritance(): void
    {
        $acl = new Acl([
            'permissions' => [
                'features' => [
                    'viewFoos' => [
                        'components' => [
                            'FooController' => ['index', 'index', null],
                            'WildcardController' => '*',
                        ],
                    ],
                    'missingFeature' => [
                        'components' => [
                            'MissingController' => ['index'],
                        ],
                    ],
                ],
                'roles' => [
                    '*' => [
                        'components' => [
                            'PublicController',
                            '*' => ['ignored'],
                        ],
                    ],
                    'user' => [
                        'features' => ['viewFoos', 'unknownFeature'],
                        'extraComponents' => [
                            'ExtraController' => 'show',
                        ],
                    ],
                    'admin' => [
                        'components' => [
                            'AdminController' => ['index'],
                        ],
                    ],
                ],
                'admin' => [
                    'inherit' => ['user'],
                ],
            ],
        ]);

        $memory = $acl->get(['components', 'extraComponents']);

        $this->assertInstanceOf(Memory::class, $memory);
        $this->assertTrue($memory->isAllowed('everyone', 'PublicController', '*'));
        $this->assertTrue($memory->isAllowed('user', 'FooController', 'index'));
        $this->assertFalse($memory->isAllowed('user', 'FooController', 'delete'));
        $this->assertTrue($memory->isAllowed('user', 'ExtraController', 'show'));
        $this->assertTrue($memory->isAllowed('admin', 'FooController', 'index'));
        $this->assertTrue($memory->isAllowed('admin', 'AdminController', 'index'));
        $this->assertFalse($memory->isAllowed('user', 'MissingController', 'index'));
    }

    public function testGetAcceptsExplicitPermissionsAndCustomInheritanceKey(): void
    {
        $memory = (new Acl())->get(permissions: [
            'roles' => [
                'base' => [
                    'components' => [
                        'BaseController' => ['index'],
                    ],
                ],
                'child' => [
                    'components' => [
                        'ChildController' => ['index'],
                    ],
                ],
            ],
            'child' => [
                'extends' => 'base',
            ],
        ], inherit: 'extends');

        $this->assertTrue($memory->isAllowed('child', 'BaseController', 'index'));
        $this->assertTrue($memory->isAllowed('child', 'ChildController', 'index'));
    }
}
