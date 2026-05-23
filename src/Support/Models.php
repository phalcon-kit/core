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

use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model;
use PhalconKit\Di\Injectable;
use PhalconKit\Models\Backup;
use PhalconKit\Models\Interfaces\BackupInterface;
use PhalconKit\Models\Audit;
use PhalconKit\Models\Interfaces\AuditInterface;
use PhalconKit\Models\AuditDetail;
use PhalconKit\Models\Interfaces\AuditDetailInterface;
use PhalconKit\Models\Feature;
use PhalconKit\Models\Interfaces\FeatureInterface;
use PhalconKit\Models\Log;
use PhalconKit\Models\Interfaces\LogInterface;
use PhalconKit\Models\Email;
use PhalconKit\Models\Interfaces\EmailInterface;
use PhalconKit\Models\Job;
use PhalconKit\Models\Interfaces\JobInterface;
use PhalconKit\Models\File;
use PhalconKit\Models\Interfaces\FileInterface;
use PhalconKit\Models\Session;
use PhalconKit\Models\Interfaces\SessionInterface;
use PhalconKit\Models\Flag;
use PhalconKit\Models\Interfaces\FlagInterface;
use PhalconKit\Models\Setting;
use PhalconKit\Models\Interfaces\SettingInterface;
use PhalconKit\Models\Lang;
use PhalconKit\Models\Interfaces\LangInterface;
use PhalconKit\Models\Translate;
use PhalconKit\Models\Interfaces\TranslateInterface;
use PhalconKit\Models\Workspace;
use PhalconKit\Models\Interfaces\WorkspaceInterface;
use PhalconKit\Models\WorkspaceLang;
use PhalconKit\Models\Interfaces\WorkspaceLangInterface;
use PhalconKit\Models\Page;
use PhalconKit\Models\Interfaces\PageInterface;
use PhalconKit\Models\Post;
use PhalconKit\Models\Interfaces\PostInterface;
use PhalconKit\Models\Template;
use PhalconKit\Models\Interfaces\TemplateInterface;
use PhalconKit\Models\Table;
use PhalconKit\Models\Interfaces\TableInterface;
use PhalconKit\Models\Field;
use PhalconKit\Models\Interfaces\FieldInterface;
use PhalconKit\Models\Profile;
use PhalconKit\Models\Interfaces\ProfileInterface;
use PhalconKit\Models\Oauth2;
use PhalconKit\Models\Interfaces\Oauth2Interface;
use PhalconKit\Models\User;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Models\UserType;
use PhalconKit\Models\Interfaces\UserTypeInterface;
use PhalconKit\Models\UserGroup;
use PhalconKit\Models\Interfaces\UserGroupInterface;
use PhalconKit\Models\UserRole;
use PhalconKit\Models\Interfaces\UserRoleInterface;
use PhalconKit\Models\UserFeature;
use PhalconKit\Models\Interfaces\UserFeatureInterface;
use PhalconKit\Models\Role;
use PhalconKit\Models\Interfaces\RoleInterface;
use PhalconKit\Models\RoleRole;
use PhalconKit\Models\Interfaces\RoleRoleInterface;
use PhalconKit\Models\RoleFeature;
use PhalconKit\Models\Interfaces\RoleFeatureInterface;
use PhalconKit\Models\Group;
use PhalconKit\Models\Interfaces\GroupInterface;
use PhalconKit\Models\GroupRole;
use PhalconKit\Models\Interfaces\GroupRoleInterface;
use PhalconKit\Models\GroupType;
use PhalconKit\Models\Interfaces\GroupTypeInterface;
use PhalconKit\Models\GroupFeature;
use PhalconKit\Models\Interfaces\GroupFeatureInterface;
use PhalconKit\Models\Type;
use PhalconKit\Models\Interfaces\TypeInterface;

/**
 * Resolves configured PhalconKit model instances without magic methods.
 *
 * Applications can replace core model classes through the `models` config map.
 * This service turns those configured class names into cached model instances
 * while validating the contracts expected by each typed getter. Invalid app
 * mappings are reported as `ServiceException` failures so production runtimes
 * behave consistently even when PHP assertions are disabled.
 *
 * The service intentionally constructs models directly instead of resolving
 * them from the DI container because Phalcon models normally receive their
 * runtime services through the model manager and default DI integration.
 */
class Models extends Injectable
{
    use ModelsMap;
    
    /**
     * Cached model instances keyed by the original core model class name.
     *
     * The cache key remains the core class requested by the framework, not the
     * mapped replacement class. This lets callers swap implementations through
     * config while preserving stable lookup and `unsetInstance()` semantics.
     *
     * @var array<string, Model>
     */
    public array $instances = [];
    
    /**
     * Build the model resolver with an optional explicit model class map.
     *
     * Passing null loads the `models` map from the bootstrap config service via
     * `ModelsMap::setModelsMap()`. Tests and service providers can pass an
     * explicit array to bypass the config lookup and validate local mappings.
     *
     * @param array<string, string>|null $mapping Core model class names mapped
     *     to replacement model class names, or null to load from config.
     * @throws ServiceException When the config-backed model map cannot be
     *     loaded from the DI container.
     */
    public function __construct(?array $mapping = null)
    {
        $this->setModelsMap($mapping);
    }
    
    /**
     * Return every model instance that has already been resolved.
     *
     * @return array<string, Model> Cached instances keyed by the original core
     *     model class name requested by the framework.
     */
    public function getInstances(): array
    {
        return $this->instances;
    }
    
    /**
     * Store a resolved model instance for a core model class.
     *
     * This method is public so tests or advanced applications can pre-seed the
     * cache with custom instances. The supplied instance is not revalidated
     * against a typed getter contract until that getter is called.
     *
     * @param string $class Original core model class used as the cache key.
     * @param Model $instance Model instance to reuse for future lookups.
     */
    public function setInstance(string $class, Model $instance): void
    {
        $this->instances[$class] = $instance;
    }
    
    /**
     * Remove a cached model instance for a core model class.
     *
     * The configured class map is left intact. The next lookup for the same
     * class will instantiate the currently configured mapped model again.
     *
     * @param string $map Original core model class used as the cache key.
     */
    public function unsetInstance(string $map): void
    {
        unset($this->instances[$map]);
    }
    
    /**
     * Return a cached instance of the configured model class for a core class.
     *
     * The requested class is first translated through the model map. The mapped
     * class must exist, be instantiable with its default constructor, and extend
     * `PhalconKit\Mvc\Model`; otherwise a stable framework exception is thrown
     * before a native PHP error or disabled assertion can leak to callers.
     *
     * @param string $class Original core model class name.
     * @return Model Instance of the configured mapped model class.
     * @throws ServiceException When the mapped class cannot be loaded,
     *     instantiated, or does not extend the PhalconKit base model.
     */
    public function getInstance(string $class): Model
    {
        if (!isset($this->instances[$class])) {
            $map = $this->getClassMap($class);
            
            if (!class_exists($map)) {
                throw new ServiceException(sprintf(
                    'Mapped model class "%s" for "%s" does not resolve to a loadable class.',
                    $map,
                    $class
                ));
            }
            
            try {
                $instance = new $map();
            }
            catch (\Throwable $e) {
                throw new ServiceException(sprintf(
                    'Could not instantiate mapped model class "%s" for "%s".',
                    $map,
                    $class
                ), previous: $e);
            }
            
            if (!$instance instanceof Model) {
                throw new ServiceException(sprintf(
                    'Expected mapped model class "%s" for "%s" to create an instance of "%s"; got "%s".',
                    $map,
                    $class,
                    Model::class,
                    get_debug_type($instance)
                ));
            }
            
            $this->setInstance($class, $instance);
        }
        
        return $this->instances[$class];
    }
    
    /**
     * Return a mapped model instance that implements a specific framework contract.
     *
     * Typed getters call this helper after resolving the mapped model. This
     * keeps every public getter concise while ensuring a wrong-but-valid model
     * class, such as mapping `User::class` to `Backup::class`, fails with a
     * precise `ServiceException` instead of a late PHP return-type error.
     *
     * @template T of object
     * @param string $class Original core model class name.
     * @param class-string<T> $expectedType Required model interface or class.
     * @return T Model instance implementing the requested contract.
     * @throws ServiceException When the configured model does not implement
     *     the contract required by the typed getter.
     */
    private function getTypedInstance(string $class, string $expectedType): object
    {
        $instance = $this->getInstance($class);
        
        if (!$instance instanceof $expectedType) {
            throw new ServiceException(sprintf(
                'Expected mapped model instance for "%s" to implement "%s"; got "%s".',
                $class,
                $expectedType,
                get_debug_type($instance)
            ));
        }
        
        return $instance;
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\BackupInterface
     */
    public function getBackup(): BackupInterface
    {
        return $this->getTypedInstance(Backup::class, BackupInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\AuditInterface
     */
    public function getAudit(): AuditInterface
    {
        return $this->getTypedInstance(Audit::class, AuditInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\AuditDetailInterface
     */
    public function getAuditDetail(): AuditDetailInterface
    {
        return $this->getTypedInstance(AuditDetail::class, AuditDetailInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\LogInterface
     */
    public function getLog(): LogInterface
    {
        return $this->getTypedInstance(Log::class, LogInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\EmailInterface
     */
    public function getEmail(): EmailInterface
    {
        return $this->getTypedInstance(Email::class, EmailInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\JobInterface
     */
    public function getJob(): JobInterface
    {
        return $this->getTypedInstance(Job::class, JobInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\FileInterface
     */
    public function getFile(): FileInterface
    {
        return $this->getTypedInstance(File::class, FileInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\SessionInterface
     */
    public function getSession(): SessionInterface
    {
        return $this->getTypedInstance(Session::class, SessionInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\FlagInterface
     */
    public function getFlag(): FlagInterface
    {
        return $this->getTypedInstance(Flag::class, FlagInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\SettingInterface
     */
    public function getSetting(): SettingInterface
    {
        return $this->getTypedInstance(Setting::class, SettingInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\LangInterface
     */
    public function getLang(): LangInterface
    {
        return $this->getTypedInstance(Lang::class, LangInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\TranslateInterface
     */
    public function getTranslate(): TranslateInterface
    {
        return $this->getTypedInstance(Translate::class, TranslateInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\WorkspaceInterface
     */
    public function getWorkspace(): WorkspaceInterface
    {
        return $this->getTypedInstance(Workspace::class, WorkspaceInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\WorkspaceInterface
     */
    public function getWorkspaceLang(): WorkspaceLangInterface
    {
        return $this->getTypedInstance(WorkspaceLang::class, WorkspaceLangInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\PageInterface
     */
    public function getPage(): PageInterface
    {
        return $this->getTypedInstance(Page::class, PageInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\PostInterface
     */
    public function getPost(): PostInterface
    {
        return $this->getTypedInstance(Post::class, PostInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\TemplateInterface
     */
    public function getTemplate(): TemplateInterface
    {
        return $this->getTypedInstance(Template::class, TemplateInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\TableInterface
     */
    public function getTable(): TableInterface
    {
        return $this->getTypedInstance(Table::class, TableInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\ProfileInterface
     */
    public function getProfile(): ProfileInterface
    {
        return $this->getTypedInstance(Profile::class, ProfileInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\UserInterface
     */
    public function getOauth2(): Oauth2Interface
    {
        return $this->getTypedInstance(Oauth2::class, Oauth2Interface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->getTypedInstance(User::class, UserInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\UserTypeInterface
     */
    public function getUserType(): UserTypeInterface
    {
        return $this->getTypedInstance(UserType::class, UserTypeInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\UserGroupInterface
     */
    public function getUserGroup(): UserGroupInterface
    {
        return $this->getTypedInstance(UserGroup::class, UserGroupInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\UserRoleInterface
     */
    public function getUserRole(): UserRoleInterface
    {
        return $this->getTypedInstance(UserRole::class, UserRoleInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\UserFeatureInterface
     */
    public function getUserFeature(): UserFeatureInterface
    {
        return $this->getTypedInstance(UserFeature::class, UserFeatureInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\RoleInterface
     */
    public function getRole(): RoleInterface
    {
        return $this->getTypedInstance(Role::class, RoleInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\RoleRoleInterface
     */
    public function getRoleRole(): RoleRoleInterface
    {
        return $this->getTypedInstance(RoleRole::class, RoleRoleInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\RoleFeatureInterface
     */
    public function getRoleFeature(): RoleFeatureInterface
    {
        return $this->getTypedInstance(RoleFeature::class, RoleFeatureInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\GroupInterface
     */
    public function getGroup(): GroupInterface
    {
        return $this->getTypedInstance(Group::class, GroupInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\GroupRoleInterface
     */
    public function getGroupRole(): GroupRoleInterface
    {
        return $this->getTypedInstance(GroupRole::class, GroupRoleInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\GroupTypeInterface
     */
    public function getGroupType(): GroupTypeInterface
    {
        return $this->getTypedInstance(GroupType::class, GroupTypeInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\GroupFeatureInterface
     */
    public function getGroupFeature(): GroupFeatureInterface
    {
        return $this->getTypedInstance(GroupFeature::class, GroupFeatureInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\TypeInterface
     */
    public function getType(): TypeInterface
    {
        return $this->getTypedInstance(Type::class, TypeInterface::class);
    }
    
    /**
     * Return an instance of \PhalconKit\Models\Interfaces\FeatureInterface
     */
    public function getFeature(): FeatureInterface
    {
        return $this->getTypedInstance(Feature::class, FeatureInterface::class);
    }
}
