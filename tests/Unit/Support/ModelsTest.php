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

namespace PhalconKit\Tests\Unit\Support;

use PhalconKit\Exception\ServiceException;
use PhalconKit\Models\Backup;
use PhalconKit\Models\User;
use PhalconKit\Support\Models;
use PhalconKit\Tests\Unit\AbstractUnit;

class ModelsTest extends AbstractUnit
{
    public Models $models;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->models = new Models();
    }
    
    public function testInstances(): void
    {
        // no instance should be loaded
        $this->assertEquals([], $this->models->getInstances());
        
        // load all instances
        $instances = [
            \PhalconKit\Models\Backup::class => $this->models->getBackup(),
            \PhalconKit\Models\Audit::class => $this->models->getAudit(),
            \PhalconKit\Models\AuditDetail::class => $this->models->getAuditDetail(),
            \PhalconKit\Models\Log::class => $this->models->getLog(),
            \PhalconKit\Models\Email::class => $this->models->getEmail(),
            \PhalconKit\Models\Job::class => $this->models->getJob(),
            \PhalconKit\Models\File::class => $this->models->getFile(),
            \PhalconKit\Models\Session::class => $this->models->getSession(),
            \PhalconKit\Models\Flag::class => $this->models->getFlag(),
            \PhalconKit\Models\Setting::class => $this->models->getSetting(),
            \PhalconKit\Models\Lang::class => $this->models->getLang(),
            \PhalconKit\Models\Translate::class => $this->models->getTranslate(),
            \PhalconKit\Models\Workspace::class => $this->models->getWorkspace(),
            \PhalconKit\Models\WorkspaceLang::class => $this->models->getWorkspaceLang(),
            \PhalconKit\Models\Page::class => $this->models->getPage(),
            \PhalconKit\Models\Post::class => $this->models->getPost(),
            \PhalconKit\Models\Template::class => $this->models->getTemplate(),
            \PhalconKit\Models\Table::class => $this->models->getTable(),
            \PhalconKit\Models\Profile::class => $this->models->getProfile(),
            \PhalconKit\Models\Oauth2::class => $this->models->getOauth2(),
            \PhalconKit\Models\User::class => $this->models->getUser(),
            \PhalconKit\Models\UserType::class => $this->models->getUserType(),
            \PhalconKit\Models\UserGroup::class => $this->models->getUserGroup(),
            \PhalconKit\Models\UserRole::class => $this->models->getUserRole(),
            \PhalconKit\Models\UserFeature::class => $this->models->getUserFeature(),
            \PhalconKit\Models\Role::class => $this->models->getRole(),
            \PhalconKit\Models\RoleRole::class => $this->models->getRoleRole(),
            \PhalconKit\Models\RoleFeature::class => $this->models->getRoleFeature(),
            \PhalconKit\Models\Group::class => $this->models->getGroup(),
            \PhalconKit\Models\GroupRole::class => $this->models->getGroupRole(),
            \PhalconKit\Models\GroupType::class => $this->models->getGroupType(),
            \PhalconKit\Models\GroupFeature::class => $this->models->getGroupFeature(),
            \PhalconKit\Models\Type::class => $this->models->getType(),
            \PhalconKit\Models\Feature::class => $this->models->getFeature(),
        ];
        
        // check instance types
        foreach ($instances as $expected => $actual) {
            $this->assertInstanceOf($expected, $actual);
            $this->assertInstanceOf($expected, $this->models->getInstance($expected));
        }
        
        // all instances should be loaded
        $this->assertSame($instances, $this->models->getInstances());
        
        // test unset instance
        $this->models->unsetInstance(\PhalconKit\Models\Backup::class);
        unset($instances[\PhalconKit\Models\Backup::class]);
        $this->assertSame($instances, $this->models->getInstances());
    }
    
    public function testGetInstanceRejectsMissingMappedClass(): void
    {
        $models = new Models([
            Backup::class => 'App\\Missing\\Backup',
        ]);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Mapped model class "App\\Missing\\Backup" for "PhalconKit\Models\Backup" '
            . 'does not resolve to a loadable class.'
        );
        
        $models->getBackup();
    }
    
    public function testGetInstanceRejectsMappedClassThatIsNotAModel(): void
    {
        $models = new Models([
            Backup::class => \stdClass::class,
        ]);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected mapped model class "stdClass" for "PhalconKit\Models\Backup" '
            . 'to create an instance of "PhalconKit\Mvc\Model"; got "stdClass".'
        );
        
        $models->getBackup();
    }
    
    public function testTypedGetterRejectsMappedModelWithWrongContract(): void
    {
        $models = new Models([
            User::class => Backup::class,
        ]);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected mapped model instance for "PhalconKit\Models\User" '
            . 'to implement "PhalconKit\Models\Interfaces\UserInterface"; '
            . 'got "PhalconKit\Models\Backup".'
        );
        
        $models->getUser();
    }
}
