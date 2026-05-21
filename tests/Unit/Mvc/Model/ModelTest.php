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

namespace PhalconKit\Tests\Unit\Mvc\Model;

use Phalcon\Db\Column;
use Phalcon\Mvc\Model\Resultset\Simple;
use PhalconKit\Models\Audit;
use PhalconKit\Models\AuditDetail;
use PhalconKit\Models\Role;
use PhalconKit\Models\User;
use PhalconKit\Models\UserRole;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\ProtectedRelationshipUser;

class ModelTest extends AbstractUnit
{
    public function prepareTests(): void
    {
        $db = $this->getDb();
        $db->execute('SET FOREIGN_KEY_CHECKS=0;');
        $db->execute('TRUNCATE TABLE ' . $db->escapeIdentifier(new User()->getSource()));
        $db->execute('TRUNCATE TABLE ' . $db->escapeIdentifier(new UserRole()->getSource()));
        $db->execute('TRUNCATE TABLE ' . $db->escapeIdentifier(new Role()->getSource()));
        $db->execute('SET FOREIGN_KEY_CHECKS=1;');
        $this->addModelsPermissions([Audit::class => ['*']]);
        $this->addModelsPermissions([AuditDetail::class => ['*']]);
        $this->addModelsPermissions([User::class => ['*']]);
        $this->addModelsPermissions([UserRole::class => ['*']]);
        $this->addModelsPermissions([Role::class => ['*']]);
        $this->assertEquals('user', new User()->getSource());
        $this->assertEquals('user_role', new UserRole()->getSource());
        $this->assertEquals('role', new Role()->getSource());
    }
    
    public function testModelSave(): void
    {
        $this->prepareTests();
        
        $user = new User();
//        $user->setUsername('test');
//        $user->setFirstName('test');
//        $user->setLastName('test');
        $user->setEmail('test@test.tld');
        
        // Create
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        // Update
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        // Fetch
        $user = User::findFirst([
            'email = :email:',
            'bind' => ['email' => 'test@test.tld'],
            'bindTypes' => ['email' => Column::BIND_PARAM_STR],
        ]);
        $this->assertInstanceOf(User::class, $user);
        
        // Update fetched
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
    }
    
    public function testManyRelationship(): void
    {
        $this->prepareTests();
        
        // Seed the required parent roles first
        for ($i = 1; $i <= 5; $i++) {
            $role = new Role();
            $role->setId($i);
            $role->setKey('role_' . $i);
            $role->setLabel('Role #' . $i);
            ;
            $save = $role->save();
            $messages = $role->getMessages();
            $this->assertTrue($save);
            $this->assertEmpty($messages, json_encode($messages));
        }
        
        $user = new User();
        $user->assign([
            'email' => 'test@test.tld',
            'userrolelist' => [
                false,
                [
                    'roleId' => 1,
                ],
                [
                    'roleId' => 2,
                ]
            ],
        ]);
        
        // Create
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $this->userRoleFindAssert(1, 1);
        $this->userRoleFindAssert(1, 2);
        
        // change an existing relationship and soft-delete other relationships
        $user->assign([
            'userrolelist' => [
                false,
                [
                    'id' => 1,
                    'roleId' => 3,
                ],
            ],
        ]);
        
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $this->userRoleFindAssert(1, 3);
        $this->userRoleFindAssert(1, 2, 1);
        
        // reactivate a previously deleted relationship using id without altering other relationships
        $user->assign([
            'userrolelist' => [
                true,
                [
                    'id' => 2,
                    'deleted' => 0, // it's normal to pass 0 here because it's 1-n relationship
                ],
                [
                    'roleId' => 5,
                    'deleted' => 1,
                ],
            ],
        ]);
        
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $this->userRoleFindAssert(1, 3);
        $this->userRoleFindAssert(1, 2);
        $this->userRoleFindAssert(1, 5, 1);
        
        // reactivate a previously deleted relationship using roleId only without altering other relationships
        // this should not be possible @todo maybe delete?
//        $user->assign([
//            'userrolelist' => [
//                true,
////                [
////                    'roleId' => 5, // this will create a new one and not update the existing one because its single to many
////                ],
//            ],
//        ]);
//
//        $save = $user->save();
//        $messages = $user->getMessages();
//
//        $this->assertEmpty($messages, json_encode($messages));
//        $this->assertTrue($save);
//
//        $this->userRoleFindAssert($user->getId(), 3);
//        $this->userRoleFindAssert($user->getId(), 2);
//        $this->userRoleFindAssert($user->getId(), 5, 1); // the first one should still be deleted
//
//        $roles = UserRole::find([
//            'userId = :userId: and roleId = :roleId:',
//            'bind' => ['userId' => $user->getId(), 'roleId' => 5],
//        ]);
//        $this->assertEquals(2, $roles->count());
//        $this->assertCount(2, $roles);
    }
    
    public function testManyToManyRelationship(): void
    {
        $this->prepareTests();
        
        $user = new User();
        $user->assign([
//            'username' => 'test',
//            'firstName' => 'test',
//            'lastName' => 'test',
            'email' => 'test@test.tld',
            'rolelist' => [
                false,
                [
                    'key' => 'test',
                    'label' => 'test',
                ],
                [
                    'key' => 'test2',
                    'label' => 'test2',
                ],
                [
                    'key' => 'test3',
                    'label' => 'test3',
                ],
            ],
        ]);
        
        // Create
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $this->roleFindAssert('test');
        $this->roleFindAssert('test2');
        $this->roleFindAssert('test3');
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 1']);
        $this->assertCount(3, $roleList);
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 0']);
        $this->assertCount(0, $roleList);
        
        // append more
        $user->assign(['rolelist' => [
            true,
            [
                'key' => 'test4',
                'label' => 'test4',
            ],
        ]]);
        
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $this->roleFindAssert('test');
        $this->roleFindAssert('test2');
        $this->roleFindAssert('test3');
        $this->roleFindAssert('test4');
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 1']);
        $this->assertCount(4, $roleList);
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 0']);
        $this->assertCount(0, $roleList);
        
        // remove and append
        $user->assign(['rolelist' => [
            false,
            [
                'key' => 'test5',
                'label' => 'test5',
            ],
            [
                'key' => 'test6',
                'label' => 'test6',
            ],
        ]]);
        
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $this->roleFindAssert('test');
        $this->roleFindAssert('test2');
        $this->roleFindAssert('test3');
        $this->roleFindAssert('test4');
        $this->roleFindAssert('test5');
        $this->roleFindAssert('test6');
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 1']);
        $this->assertCount(2, $roleList);
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 0']);
        $this->assertCount(4, $roleList);
        
        // add by id
        $user->assign(['rolelist' => [
            true,
            1,
            2,
            3,
            4,
        ]]);
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 1']);
        $this->assertCount(6, $roleList);
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 0']);
        $this->assertCount(0, $roleList);
        
        // remove by id
        $user->assign(['rolelist' => [
            false,
            1,
            2,
            3,
            4,
        ]]);
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 1']);
        $this->assertCount(4, $roleList);
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 0']);
        $this->assertCount(2, $roleList);
        
        // mixed
        $user->assign(['rolelist' => [
            false, // delete everything else
            1, // using int
            ['id' => 2], // using id only
            ['id' => 3, 'key' => 'changed3'], // edit
            ['id' => 3, 'label' => 'changed3'], // edit twice
            '4', // using string
            5, // restore
            6, // restore
            [
                'key' => 'test7',
                'label' => 'test7',
            ], // new entity
            new Role(['key' => 'test8'])->assign(['label' => 'test8']),
        ]]);
        $save = $user->save();
        $messages = $user->getMessages();
        
        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);
        
        $this->roleFindAssert('test');
        $this->roleFindAssert('test2');
        $this->roleFindAssert('changed3');
        $this->roleFindAssert('test4');
        $this->roleFindAssert('test5');
        $this->roleFindAssert('test6');
        $this->roleFindAssert('test7');
        $this->roleFindAssert('test8');
        
        $roleList = $user->getRoleList(['[' . UserRole::class . '].deleted <> 1']);
        $this->assertCount(8, $roleList);
    }
    
    public function testLoadedRelationshipCacheCanReadProtectedRelationProperty(): void
    {
        $user = new ProtectedRelationshipUser();
        $role = new Role();
        $role->setKey('cached-role');

        $user->setLoadedRelatedAlias('RoleList', [$role]);

        $this->assertSame([$role], $user->rolelist);
        $this->assertSame([$role], $user->RoleList);
        $this->assertSame([$role], $user->getProtectedRoleList());
    }

    public function testDirectProtectedRelationPropertyWriteUsesDirtyCache(): void
    {
        $user = new ProtectedRelationshipUser();
        $role = new Role();
        $role->setKey('dirty-role');

        $user->rolelist = [$role];

        $this->assertSame([$role], $user->getDirtyRelatedAlias('RoleList'));
        $this->assertSame([$role], $user->rolelist);
        $this->assertSame([$role], $user->getProtectedRoleList());
    }

    public function testDeclaredProtectedRelationPropertyCanBeReadDirectly(): void
    {
        $user = new ProtectedRelationshipUser();
        $role = new Role();
        $role->setKey('declared-role');

        $user->setProtectedRoleList([$role]);

        $this->assertSame([$role], $user->rolelist);
        $this->assertFalse($user->hasDirtyRelatedAlias('rolelist'));
        $this->assertFalse($user->hasLoadedRelatedAlias('rolelist'));
    }

    public function testNullDirtyRelationshipAliasIsStillTracked(): void
    {
        $user = new ProtectedRelationshipUser();

        $user->CreatedByEntity = null;

        $this->assertTrue($user->hasDirtyRelatedAlias('CreatedByEntity'));
        $this->assertNull($user->getDirtyRelatedAlias('CreatedByEntity'));
        $this->assertNull($user->CreatedByEntity);
    }

    public function testBulkRelationshipAliasSettersNormalizeAliases(): void
    {
        $user = new ProtectedRelationshipUser();
        $role = new Role();
        $role->setKey('bulk-role');

        $user->setLoadedRelated(['RoleList' => [$role]]);
        $user->setDirtyRelated(['CreatedByEntity' => null]);
        $user->setKeepMissingRelated(['RoleList' => false]);

        $this->assertTrue($user->hasLoadedRelatedAlias('rolelist'));
        $this->assertSame([$role], $user->getLoadedRelatedAlias('ROLELIST'));
        $this->assertTrue($user->hasDirtyRelatedAlias('createdbyentity'));
        $this->assertNull($user->getDirtyRelatedAlias('CreatedByEntity'));
        $this->assertFalse($user->getKeepMissingRelatedAlias('rolelist'));
    }

    public function testProtectedRelationshipPropertiesWorkWithAssignAndEagerLoading(): void
    {
        $this->prepareTests();

        $user = new ProtectedRelationshipUser();
        $user->assign([
            'email' => 'protected@test.tld',
            'rolelist' => [
                false,
                [
                    'key' => 'protected-role',
                    'label' => 'Protected Role',
                ],
            ],
        ]);

        $save = $user->save();
        $messages = $user->getMessages();

        $this->assertEmpty($messages, json_encode($messages));
        $this->assertTrue($save);

        $loaded = ProtectedRelationshipUser::findFirstWith(['RoleList'], [
            'email = :email:',
            'bind' => ['email' => 'protected@test.tld'],
            'bindTypes' => ['email' => Column::BIND_PARAM_STR],
        ]);

        $this->assertInstanceOf(ProtectedRelationshipUser::class, $loaded);
        $this->assertTrue($loaded->hasLoadedRelatedAlias('rolelist'));
        $this->assertCount(1, $loaded->rolelist);
        $this->assertSame('protected-role', $loaded->rolelist[0]->getKey());

        $array = $loaded->toArray();
        $this->assertArrayHasKey('rolelist', $array);
        $this->assertSame('protected-role', $array['rolelist'][0]['key']);
    }

    public function roleFindAssert(string $string)
    {
        $role = Role::findFirst(['key = :key:', 'bind' => ['key' => $string]]);
        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals($string, $role->readAttribute('label'));
        $this->assertEquals($string, $role->readAttribute('key'));
        $this->assertEquals(0, $role->readAttribute('deleted'));
        $this->assertNotEmpty($role->readAttribute('createdAt'));
        return $role;
    }
    
    public function userRoleFindAssert(int $userId, int $roleId, int $deleted = 0)
    {
        $userRole = UserRole::findFirst(['userId = :userId: and roleId = :roleId:', 'bind' => ['userId' => $userId, 'roleId' => $roleId]]);
        $this->assertInstanceOf(UserRole::class, $userRole);
        $this->assertEquals($userId, $userRole->readAttribute('userId'));
        $this->assertEquals($roleId, $userRole->readAttribute('roleId'));
        $this->assertEquals($deleted, $userRole->readAttribute('deleted'));
        $this->assertNotEmpty($userRole->readAttribute('createdAt'));
        return $userRole;
    }
    
    public function addModelsPermissions(array $models = []): void
    {
        $permissions = [];
        foreach ($models as $class => $permission) {
            $permissions[$class] = $permission;
        }
        $this->getConfig()->merge([
            'permissions' => [
                'roles' => [
                    'everyone' => [
                        'models' => $permissions,
                    ],
                ],
            ],
        ]);
    }
    
    public function testFindResultset(): void
    {
        $this->prepareTests();
        
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->save();
        
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->save();
        
        $find = User::find();
        
        $this->assertInstanceOf(Simple::class, $find);
        $this->assertEquals(2, $find->count());
        
        $first = $find->getFirst();
        $this->assertInstanceOf(User::class, $first);
        ;
        $this->assertEquals('user1@test.com', $first->getEmail());
        ;
        
        $last = $find->getLast();
        $this->assertInstanceOf(User::class, $last);
        ;
        $this->assertEquals('user2@test.com', $last->getEmail());
    }
    
    public function testFindFirst(): void
    {
        $this->prepareTests();
        
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->save();
        
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->save();
        
        $findFirst = User::findFirst();
        $this->assertInstanceOf(User::class, $findFirst);
        $this->assertEquals('user1@test.com', $findFirst->getEmail());
        
        $findLast = User::findFirst(['order' => 'id DESC']);
        $this->assertInstanceOf(User::class, $findLast);
        $this->assertEquals('user2@test.com', $findLast->getEmail());
    }
    
    public function testCount(): void
    {
        $this->prepareTests();
        
        // Create test users with different values
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->save();
        
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->save();
        
        // Test count
        $sum = User::count();
        
        $this->assertEquals(2, $sum);
    }
    
    public function testSumAggregation(): void
    {
        $this->prepareTests();
        
        // Create test users with different values
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->save();
        
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->save();
        
        // Test sum aggregation
        $sum = User::sum([
            'column' => 'id',
        ]);
        
        $this->assertEquals(3, $sum);
    }
    
    public function testAverageAggregation(): void
    {
        $this->prepareTests();
        
        // Create test users with different values
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->save();
        
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->save();
        
        // Test average aggregation
        $average = User::average([
            'column' => 'id',
        ]);
        
        $this->assertEquals(1.5, $average);
    }
    
    public function testMinAggregation(): void
    {
        $this->prepareTests();
        
        // Create test users with different values
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->save();
        
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->save();
        
        // Test min aggregation
        $min = User::minimum([
            'column' => 'id',
        ]);
        
        $this->assertEquals(1, $min);
    }
    
    public function testMaxAggregation(): void
    {
        $this->prepareTests();
        
        // Create test users with different values
        $user1 = new User();
        $user1->setEmail('user1@test.com');
        $user1->save();
        
        $user2 = new User();
        $user2->setEmail('user2@test.com');
        $user2->save();
        
        // Test max aggregation
        $max = User::maximum([
            'column' => 'id',
        ]);
        
        $this->assertEquals(2, $max);
    }
}
