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

namespace PhalconKit\Support;

use Phalcon\Di\DiInterface;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Models\Backup;
use PhalconKit\Models\Audit;
use PhalconKit\Models\AuditDetail;
use PhalconKit\Models\Log;
use PhalconKit\Models\Email;
use PhalconKit\Models\Job;
use PhalconKit\Models\File;
use PhalconKit\Models\Oauth2;
use PhalconKit\Models\Session;
use PhalconKit\Models\Flag;
use PhalconKit\Models\Setting;
use PhalconKit\Models\Lang;
use PhalconKit\Models\Translate;
use PhalconKit\Models\Workspace;
use PhalconKit\Models\WorkspaceLang;
use PhalconKit\Models\Page;
use PhalconKit\Models\Post;
use PhalconKit\Models\Template;
use PhalconKit\Models\Table;
use PhalconKit\Models\Profile;
use PhalconKit\Models\User;
use PhalconKit\Models\UserType;
use PhalconKit\Models\UserGroup;
use PhalconKit\Models\UserRole;
use PhalconKit\Models\UserFeature;
use PhalconKit\Models\Role;
use PhalconKit\Models\RoleRole;
use PhalconKit\Models\RoleFeature;
use PhalconKit\Models\Group;
use PhalconKit\Models\GroupRole;
use PhalconKit\Models\GroupType;
use PhalconKit\Models\GroupFeature;
use PhalconKit\Models\Type;
use PhalconKit\Models\Feature;
use PhalconKit\Mvc\ModelInterface;

/**
 * Provides explicit accessors for configurable framework model classes.
 *
 * Applications can replace core PhalconKit model classes through the `models`
 * config map. This trait lets services resolve those classes without magic
 * methods and keeps all default class names documented in one place. The
 * generic mapping helpers are intentionally loose because downstream projects
 * may map core classes to application-specific subclasses.
 */
trait ModelsMap
{
    /**
     * Return the DI container used to resolve config-backed mappings.
     *
     * Implemented by `PhalconKit\Di\Injectable` consumers.
     */
    abstract public function getDI(): DiInterface;
    
    /**
     * Store mapped model classes keyed by the original core class name.
     *
     * Values are usually class-string values, but the trait does not validate
     * them here so applications can load partial maps before class autoloading
     * is fully available.
     *
     * @var array<string, string>
     */
    public array $modelsMap = [];
    
    /**
     * Resolve the bootstrap config used for model class mappings.
     *
     * The backing DI must be a PhalconKit DI because model mapping is a
     * framework-level integration point and should fail consistently when a
     * native-only container is accidentally used.
     *
     * @return Config Bootstrap config service.
     * @throws ServiceException When the DI container or config service cannot
     *     be resolved through the PhalconKit DI contract.
     */
    public function getConfig(): Config
    {
        return ServiceResolver::fromContainer(
            $this->getDI(),
            'config',
            Config::class,
            context: 'models map'
        );
    }
    
    /**
     * Replace the model mapping or load it from configuration.
     *
     * Passing an explicit array lets tests and service providers avoid a config
     * lookup. Passing null loads `models` from the bootstrap config service and
     * stores an empty array when no mapping is configured.
     *
     * @param array<string, string>|null $modelsMap Mapping of core class names
     *     to replacement class names, or null to load from config.
     * @throws ServiceException When the config-backed mapping cannot be loaded.
     */
    public function setModelsMap(?array $modelsMap = null): void
    {
        $this->modelsMap = $modelsMap ?? $this->getConfig()->pathToArray('models') ?? [];
    }
    
    /**
     * Return the currently configured model class map.
     *
     * @return array<string, string> Mapping of original core class names to the
     *     class names that should be instantiated or referenced.
     */
    public function getModelsMap(): array
    {
        return $this->modelsMap;
    }
    
    /**
     * Add or replace one model class mapping.
     *
     * @param string $map Original core class name or logical map key.
     * @param string $class Replacement class name to return for that key.
     */
    public function setClassMap(string $map, string $class): void
    {
        $this->modelsMap[$map] = $class;
    }
    
    /**
     * Remove one model class mapping.
     *
     * After removal, `getClassMap()` falls back to returning the requested
     * class/key unchanged.
     *
     * @param string $map Original core class name or logical map key to remove.
     */
    public function removeClassMap(string $map): void
    {
        unset($this->modelsMap[$map]);
    }
    
    /**
     * Return the mapped class name for a core class or fallback to itself.
     *
     * @param string $class Original core class name or logical map key.
     * @return string Replacement class name when configured; otherwise the
     *     original value.
     */
    public function getClassMap(string $class): string
    {
        return $this->getModelsMap()[$class] ?? $class;
    }
    
    /**
     * Return the configured backup model class.
     *
     * @return string Replacement for `Backup::class`, or the core class when
     *     no mapping is configured.
     */
    public function getBackupClass(): string
    {
        return $this->getClassMap(Backup::class);
    }
    
    /**
     * Return the configured audit model class.
     *
     * @return string Replacement for `Audit::class`, or the core class when no
     *     mapping is configured.
     */
    public function getAuditClass(): string
    {
        return $this->getClassMap(Audit::class);
    }
    
    /**
     * Return the configured audit-detail model class.
     *
     * @return string Replacement for `AuditDetail::class`, or the core class
     *     when no mapping is configured.
     */
    public function getAuditDetailClass(): string
    {
        return $this->getClassMap(AuditDetail::class);
    }
    
    /**
     * Return the configured log model class.
     *
     * @return string Replacement for `Log::class`, or the core class when no
     *     mapping is configured.
     */
    public function getLogClass(): string
    {
        return $this->getClassMap(Log::class);
    }
    
    /**
     * Return the configured email model class.
     *
     * @return string Replacement for `Email::class`, or the core class when no
     *     mapping is configured.
     */
    public function getEmailClass(): string
    {
        return $this->getClassMap(Email::class);
    }
    
    /**
     * Return the configured job model class.
     *
     * @return string Replacement for `Job::class`, or the core class when no
     *     mapping is configured.
     */
    public function getJobClass(): string
    {
        return $this->getClassMap(Job::class);
    }
    
    /**
     * Return the configured file model class.
     *
     * @return string Replacement for `File::class`, or the core class when no
     *     mapping is configured.
     */
    public function getFileClass(): string
    {
        return $this->getClassMap(File::class);
    }
    
    /**
     * Return the configured session model class.
     *
     * @return string Replacement for `Session::class`, or the core class when
     *     no mapping is configured.
     */
    public function getSessionClass(): string
    {
        return $this->getClassMap(Session::class);
    }
    
    /**
     * Return the configured flag model class.
     *
     * @return string Replacement for `Flag::class`, or the core class when no
     *     mapping is configured.
     */
    public function getFlagClass(): string
    {
        return $this->getClassMap(Flag::class);
    }
    
    /**
     * Return the configured setting model class.
     *
     * @return string Replacement for `Setting::class`, or the core class when
     *     no mapping is configured.
     */
    public function getSettingClass(): string
    {
        return $this->getClassMap(Setting::class);
    }
    
    /**
     * Return the configured language model class.
     *
     * @return string Replacement for `Lang::class`, or the core class when no
     *     mapping is configured.
     */
    public function getLangClass(): string
    {
        return $this->getClassMap(Lang::class);
    }
    
    /**
     * Return the configured translate model class.
     *
     * @return string Replacement for `Translate::class`, or the core class
     *     when no mapping is configured.
     */
    public function getTranslateClass(): string
    {
        return $this->getClassMap(Translate::class);
    }
    
    /**
     * Return the configured workspace model class.
     *
     * @return string Replacement for `Workspace::class`, or the core class
     *     when no mapping is configured.
     */
    public function getWorkspaceClass(): string
    {
        return $this->getClassMap(Workspace::class);
    }
    
    /**
     * Return the configured workspace-language model class.
     *
     * @return string Replacement for `WorkspaceLang::class`, or the core class
     *     when no mapping is configured.
     */
    public function getWorkspaceLangClass(): string
    {
        return $this->getClassMap(WorkspaceLang::class);
    }
    
    /**
     * Return the configured page model class.
     *
     * @return string Replacement for `Page::class`, or the core class when no
     *     mapping is configured.
     */
    public function getPageClass(): string
    {
        return $this->getClassMap(Page::class);
    }
    
    /**
     * Return the configured post model class.
     *
     * @return string Replacement for `Post::class`, or the core class when no
     *     mapping is configured.
     */
    public function getPostClass(): string
    {
        return $this->getClassMap(Post::class);
    }
    
    /**
     * Return the configured template model class.
     *
     * @return string Replacement for `Template::class`, or the core class when
     *     no mapping is configured.
     */
    public function getTemplateClass(): string
    {
        return $this->getClassMap(Template::class);
    }
    
    /**
     * Return the configured table model class.
     *
     * @return string Replacement for `Table::class`, or the core class when no
     *     mapping is configured.
     */
    public function getTableClass(): string
    {
        return $this->getClassMap(Table::class);
    }
    
    /**
     * Return the configured profile model class.
     *
     * @return string Replacement for `Profile::class`, or the core class when
     *     no mapping is configured.
     */
    public function getProfileClass(): string
    {
        return $this->getClassMap(Profile::class);
    }
    
    /**
     * Return the configured OAuth2 model class.
     *
     * @return string Replacement for `Oauth2::class`, or the core class when
     *     no mapping is configured.
     */
    public function getOauth2Class(): string
    {
        return $this->getClassMap(Oauth2::class);
    }
    
    /**
     * Return the configured user model class.
     *
     * @return string Replacement for `User::class`, or the core class when no
     *     mapping is configured.
     */
    public function getUserClass(): string
    {
        return $this->getClassMap(User::class);
    }
    
    /**
     * Return the configured user-type model class.
     *
     * @return string Replacement for `UserType::class`, or the core class when
     *     no mapping is configured.
     */
    public function getUserTypeClass(): string
    {
        return $this->getClassMap(UserType::class);
    }
    
    /**
     * Return the configured user-group model class.
     *
     * @return string Replacement for `UserGroup::class`, or the core class
     *     when no mapping is configured.
     */
    public function getUserGroupClass(): string
    {
        return $this->getClassMap(UserGroup::class);
    }
    
    /**
     * Return the configured user-role model class.
     *
     * @return string Replacement for `UserRole::class`, or the core class when
     *     no mapping is configured.
     */
    public function getUserRoleClass(): string
    {
        return $this->getClassMap(UserRole::class);
    }
    
    /**
     * Return the configured user-feature model class.
     *
     * @return string Replacement for `UserFeature::class`, or the core class
     *     when no mapping is configured.
     */
    public function getUserFeatureClass(): string
    {
        return $this->getClassMap(UserFeature::class);
    }
    
    /**
     * Return the configured role model class.
     *
     * @return string Replacement for `Role::class`, or the core class when no
     *     mapping is configured.
     */
    public function getRoleClass(): string
    {
        return $this->getClassMap(Role::class);
    }
    
    /**
     * Return the configured role-role model class.
     *
     * @return string Replacement for `RoleRole::class`, or the core class when
     *     no mapping is configured.
     */
    public function getRoleRoleClass(): string
    {
        return $this->getClassMap(RoleRole::class);
    }
    
    /**
     * Return the configured role-feature model class.
     *
     * @return string Replacement for `RoleFeature::class`, or the core class
     *     when no mapping is configured.
     */
    public function getRoleFeatureClass(): string
    {
        return $this->getClassMap(RoleFeature::class);
    }
    
    /**
     * Return the configured group model class.
     *
     * @return string Replacement for `Group::class`, or the core class when no
     *     mapping is configured.
     */
    public function getGroupClass(): string
    {
        return $this->getClassMap(Group::class);
    }
    
    /**
     * Return the configured group-role model class.
     *
     * @return string Replacement for `GroupRole::class`, or the core class
     *     when no mapping is configured.
     */
    public function getGroupRoleClass(): string
    {
        return $this->getClassMap(GroupRole::class);
    }
    
    /**
     * Return the configured group-type model class.
     *
     * @return string Replacement for `GroupType::class`, or the core class
     *     when no mapping is configured.
     */
    public function getGroupTypeClass(): string
    {
        return $this->getClassMap(GroupType::class);
    }
    
    /**
     * Return the configured group-feature model class.
     *
     * @return string Replacement for `GroupFeature::class`, or the core class
     *     when no mapping is configured.
     */
    public function getGroupFeatureClass(): string
    {
        return $this->getClassMap(GroupFeature::class);
    }
    
    /**
     * Return the configured type model class.
     *
     * @return string Replacement for `Type::class`, or the core class when no
     *     mapping is configured.
     */
    public function getTypeClass(): string
    {
        return $this->getClassMap(Type::class);
    }
    
    /**
     * Return the configured feature model class.
     *
     * @return string Replacement for `Feature::class`, or the core class when
     *     no mapping is configured.
     */
    public function getFeatureClass(): string
    {
        return $this->getClassMap(Feature::class);
    }
}
