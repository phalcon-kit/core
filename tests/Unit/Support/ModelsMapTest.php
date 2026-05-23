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

use Phalcon\Di\Di as NativeDi;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Di\Di;
use PhalconKit\Di\Injectable;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Models\User;
use PhalconKit\Support\ModelsMap;
use PhalconKit\Tests\Unit\AbstractUnit;

class ModelsMapTest extends AbstractUnit
{
    public $modelsMap;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->modelsMap = new class extends Injectable {
            use ModelsMap;
        };
    }
    
    public function testModelsMap(): void
    {
        $classesMap = [
            \PhalconKit\Bootstrap\Config::class => \PhalconKit\Bootstrap\Config::class,
            \PhalconKit\Models\Backup::class => \PhalconKit\Models\Backup::class,
            \PhalconKit\Models\Audit::class => \PhalconKit\Models\Audit::class,
            \PhalconKit\Models\AuditDetail::class => \PhalconKit\Models\AuditDetail::class,
            \PhalconKit\Models\Log::class => \PhalconKit\Models\Log::class,
            \PhalconKit\Models\Email::class => \PhalconKit\Models\Email::class,
            \PhalconKit\Models\Job::class => \PhalconKit\Models\Job::class,
            \PhalconKit\Models\File::class => \PhalconKit\Models\File::class,
            \PhalconKit\Models\Session::class => \PhalconKit\Models\Session::class,
            \PhalconKit\Models\Flag::class => \PhalconKit\Models\Flag::class,
            \PhalconKit\Models\Setting::class => \PhalconKit\Models\Setting::class,
            \PhalconKit\Models\Lang::class => \PhalconKit\Models\Lang::class,
            \PhalconKit\Models\Translate::class => \PhalconKit\Models\Translate::class,
            \PhalconKit\Models\TranslateField::class => \PhalconKit\Models\TranslateField::class,
            \PhalconKit\Models\TranslateTable::class => \PhalconKit\Models\TranslateTable::class,
            \PhalconKit\Models\Workspace::class => \PhalconKit\Models\Workspace::class,
            \PhalconKit\Models\WorkspaceLang::class => \PhalconKit\Models\WorkspaceLang::class,
            \PhalconKit\Models\Page::class => \PhalconKit\Models\Page::class,
            \PhalconKit\Models\Post::class => \PhalconKit\Models\Post::class,
            \PhalconKit\Models\Template::class => \PhalconKit\Models\Template::class,
            \PhalconKit\Models\Table::class => \PhalconKit\Models\Table::class,
            \PhalconKit\Models\Field::class => \PhalconKit\Models\Field::class,
            \PhalconKit\Models\Profile::class => \PhalconKit\Models\Profile::class,
            \PhalconKit\Models\User::class => \PhalconKit\Models\User::class,
            \PhalconKit\Models\UserType::class => \PhalconKit\Models\UserType::class,
            \PhalconKit\Models\UserGroup::class => \PhalconKit\Models\UserGroup::class,
            \PhalconKit\Models\UserRole::class => \PhalconKit\Models\UserRole::class,
            \PhalconKit\Models\UserFeature::class => \PhalconKit\Models\UserFeature::class,
            \PhalconKit\Models\Role::class => \PhalconKit\Models\Role::class,
            \PhalconKit\Models\RoleRole::class => \PhalconKit\Models\RoleRole::class,
            \PhalconKit\Models\RoleFeature::class => \PhalconKit\Models\RoleFeature::class,
            \PhalconKit\Models\Group::class => \PhalconKit\Models\Group::class,
            \PhalconKit\Models\GroupRole::class => \PhalconKit\Models\GroupRole::class,
            \PhalconKit\Models\GroupType::class => \PhalconKit\Models\GroupType::class,
            \PhalconKit\Models\GroupFeature::class => \PhalconKit\Models\GroupFeature::class,
            \PhalconKit\Models\Type::class => \PhalconKit\Models\Type::class,
            \PhalconKit\Models\Feature::class => \PhalconKit\Models\Feature::class,
        ];
        
        $classes = [
            \PhalconKit\Models\Backup::class => $this->modelsMap->getBackupClass(),
            \PhalconKit\Models\Audit::class => $this->modelsMap->getAuditClass(),
            \PhalconKit\Models\AuditDetail::class => $this->modelsMap->getAuditDetailClass(),
            \PhalconKit\Models\Log::class => $this->modelsMap->getLogClass(),
            \PhalconKit\Models\Email::class => $this->modelsMap->getEmailClass(),
            \PhalconKit\Models\Job::class => $this->modelsMap->getJobClass(),
            \PhalconKit\Models\File::class => $this->modelsMap->getFileClass(),
            \PhalconKit\Models\Session::class => $this->modelsMap->getSessionClass(),
            \PhalconKit\Models\Flag::class => $this->modelsMap->getFlagClass(),
            \PhalconKit\Models\Setting::class => $this->modelsMap->getSettingClass(),
            \PhalconKit\Models\Lang::class => $this->modelsMap->getLangClass(),
            \PhalconKit\Models\Translate::class => $this->modelsMap->getTranslateClass(),
            \PhalconKit\Models\Workspace::class => $this->modelsMap->getWorkspaceClass(),
            \PhalconKit\Models\WorkspaceLang::class => $this->modelsMap->getWorkspaceLangClass(),
            \PhalconKit\Models\Page::class => $this->modelsMap->getPageClass(),
            \PhalconKit\Models\Post::class => $this->modelsMap->getPostClass(),
            \PhalconKit\Models\Template::class => $this->modelsMap->getTemplateClass(),
            \PhalconKit\Models\Table::class => $this->modelsMap->getTableClass(),
            \PhalconKit\Models\Profile::class => $this->modelsMap->getProfileClass(),
            \PhalconKit\Models\Oauth2::class => $this->modelsMap->getOauth2Class(),
            \PhalconKit\Models\User::class => $this->modelsMap->getUserClass(),
            \PhalconKit\Models\UserType::class => $this->modelsMap->getUserTypeClass(),
            \PhalconKit\Models\UserGroup::class => $this->modelsMap->getUserGroupClass(),
            \PhalconKit\Models\UserRole::class => $this->modelsMap->getUserRoleClass(),
            \PhalconKit\Models\UserFeature::class => $this->modelsMap->getUserFeatureClass(),
            \PhalconKit\Models\Role::class => $this->modelsMap->getRoleClass(),
            \PhalconKit\Models\RoleRole::class => $this->modelsMap->getRoleRoleClass(),
            \PhalconKit\Models\RoleFeature::class => $this->modelsMap->getRoleFeatureClass(),
            \PhalconKit\Models\Group::class => $this->modelsMap->getGroupClass(),
            \PhalconKit\Models\GroupRole::class => $this->modelsMap->getGroupRoleClass(),
            \PhalconKit\Models\GroupType::class => $this->modelsMap->getGroupTypeClass(),
            \PhalconKit\Models\GroupFeature::class => $this->modelsMap->getGroupFeatureClass(),
            \PhalconKit\Models\Type::class => $this->modelsMap->getTypeClass(),
            \PhalconKit\Models\Feature::class => $this->modelsMap->getFeatureClass(),
        ];
        
        foreach ($classesMap as $class => $expected) {
            $actual = $this->modelsMap->getClassMap($class);
            $this->assertIsString($actual);
            $this->assertEquals($expected, $actual);
        }
        
        foreach ($classes as $expected => $actual) {
            $this->assertIsString($actual);
            $this->assertEquals($expected, $actual);
        }
        
        $this->assertIsArray($this->modelsMap->getModelsMap());
        
        // Map User to UserTest
        $this->modelsMap->setClassMap(\PhalconKit\Models\User::class, 'UserTest');
        $this->assertEquals('UserTest', $this->modelsMap->getUserClass());
        
        // Set a new mapping array
        $this->modelsMap->setModelsMap(['Test' => 'New', 'Test2' => 'New2']);
        $this->assertEquals('New', $this->modelsMap->getClassMap('Test'));
        $this->assertEquals('New2', $this->modelsMap->getClassMap('Test2'));
        
        // Set a new mapping
        $this->modelsMap->setClassMap('Test', 'New3');
        $this->assertEquals('New3', $this->modelsMap->getClassMap('Test'));
        
        // Remove an existing mapping
        $this->modelsMap->removeClassMap('Test');
        $this->assertEquals('Test', $this->modelsMap->getClassMap('Test'));
    }

    public function testSetModelsMapLoadsMappingFromConfig(): void
    {
        $di = new Di();
        $di->set('config', new Config([
            'models' => [
                User::class => 'App\\Models\\User',
            ],
        ]));
        $this->modelsMap->setDI($di);

        $this->modelsMap->setModelsMap();

        $this->assertArrayHasKey(User::class, $this->modelsMap->getModelsMap());
        $this->assertSame('App\\Models\\User', $this->modelsMap->getUserClass());
    }

    public function testModelsMapRejectsNativeOnlyDi(): void
    {
        $di = new NativeDi();
        $di->set('config', new Config());
        $this->modelsMap->setDI($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Could not resolve DI service "config" for models map because the provided DI must implement "PhalconKit\Di\DiInterface"'
        );

        $this->modelsMap->setModelsMap();
    }

    public function testModelsMapRejectsInvalidConfigService(): void
    {
        $di = new Di();
        $di->set('config', new \stdClass());
        $this->modelsMap->setDI($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "config" to be an instance of "PhalconKit\Bootstrap\Config"; got "stdClass".'
        );

        $this->modelsMap->setModelsMap();
    }
}
