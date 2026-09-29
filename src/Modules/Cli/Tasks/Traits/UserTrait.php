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

namespace PhalconKit\Modules\Cli\Tasks\Traits;

use Phalcon\Db\Column;
use PhalconKit\Models\Interfaces\UserInterface;
use PhalconKit\Support\Utils;

trait UserTrait
{
    public array $tables = [];

    /**
     * Normalize model messages through the base CLI task output contract.
     *
     * @param iterable<mixed> $messages Messages returned by a model or resultset.
     *
     * @return list<array{message: string, field: string|null, type: string|null, code: int|null}>
     */
    abstract protected function normalizeCliMessages(iterable $messages, ?string $fallbackMessage = null): array;
    
    public function initialize(): void
    {
        Utils::setUnlimitedRuntime();
        $this->addModelsPermissions();
    }
    
    /**
     * Retrieves an array of class definitions mapped to their respective configurations.
     *
     * @return array<string, array<string, string|callable>>
     */
    public function getDefinitions(): array
    {
        return [
            $this->models->getUserClass() => [
                'password' => function (UserInterface $user): ?string {
                    return $user->getEmail();
                },
            ],
            $this->models->getUserRoleClass() => [],
        ];
    }
    
    /**
     * @return (array|int|mixed)[]
     *
     * @psalm-return array{errors: array<never, never>|mixed, save: 0|1}
     */
    public function createAction(string $email, ?string $password = null): array
    {
        $password = $this->resolvePasswordInput($password);
        $response = [
            'errors' => [],
            'save' => 0
        ];
        
        $role = explode('@', $email)[0];
        $firstName = ucfirst($role);
        $lastName = ucfirst($role);
        $password ??= $role;
        
        $assign = [
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'password' => $password,
            'passwordConfirm' => $password,
        ];
        
        $roleEntity = $this->models->getRole()::findFirst([
            'key = :role:',
            'bind' => ['role' => $role],
            'bindTypes' => ['role' => Column::BIND_PARAM_STR],
        ]);
        
        $userEntity = $this->newUserEntity();
        if ($roleEntity) {
            $assign[$this->getUserRoleAssignmentAlias($userEntity)] = [['roleId' => $roleEntity->getId()]];
        }

        $userEntity->assign($assign);
        
        if (!$userEntity->save()) {
            $response['errors'] = $this->normalizeCliMessages($userEntity->getMessages(), 'User save failed.');
        } else {
            $response['save']++;
        }
        
        return $response;
    }
    
    /**
     * @return (array|int|mixed)[]
     *
     * @psalm-return array{errors: array<never, never>|mixed, save: 0|1}
     */
    public function roleAction(string $email, string $role): array
    {
        $response = [
            'errors' => [],
            'save' => 0
        ];
        
        $userEntity = $this->models->getUser()::findFirst([
            'email = :email:',
            'bind' => ['email' => $email],
            'bindTypes' => ['email' => Column::BIND_PARAM_STR],
        ]);
        
        $roleEntity = $this->models->getRole()::findFirst([
            'key = :role:',
            'bind' => ['role' => $role],
            'bindTypes' => ['role' => Column::BIND_PARAM_STR],
        ]);
        
        if ($userEntity && $roleEntity) {
            $userEntity->assign([
                $this->getUserRoleAssignmentAlias($userEntity) => [['roleId' => $roleEntity->getId()]],
            ]);
            if (!$userEntity->save()) {
                $response['errors'] = $this->normalizeCliMessages($userEntity->getMessages(), 'User role save failed.');
            } else {
                $response['save']++;
            }
        }
        
        return $response;
    }
    
    public function passwordAction(?string $username = null, ?string $password = null): array
    {
        if ($this->dispatcher->getParameter('passwordStdin') && empty($username)) {
            throw new \PhalconKit\Exception\InvalidArgumentException('A target email is required with --password-stdin.');
        }
        $password = $this->resolvePasswordInput($password);
        $response = [];
        
        $class = $this->models->getUserClass();
        $fields = $this->getDefinitions()[$class] ?? [];
        
        // Using a model (run validations, events, etc.)
        $response[$class] = [
            'errors' => [],
            'matched' => 0,
            'save' => 0,
        ];
        
        $userInstance = $this->models->getUser();
        $list = empty($username) ? $userInstance::find() : $userInstance::find([
            'email = :email:',
            'bind' => ['email' => $username],
            'bindTypes' => ['email' => Column::BIND_PARAM_STR],
        ]);
        
        assert($list instanceof \Iterator);
        foreach ($list as $entity) {
            $response[$class]['matched']++;
            $assign = [];
            foreach ($fields as $field => $value) {
                $assign[$field] = is_callable($value) ? $value($entity) : $value;
            }
            if ($password !== null && $password !== '') {
                $assign['password'] = $password;
                $assign['passwordConfirm'] = $password;
            }
            $entity->assign($assign);
            if (!$entity->save()) {
                $response[$class]['errors'] = array_merge(
                    $response[$class]['errors'],
                    $this->normalizeCliMessages($entity->getMessages(), 'User password save failed.')
                );
            }
            else {
                $response[$class]['save']++;
            }
        }

        if (!empty($username) && $response[$class]['matched'] === 0) {
            $response[$class]['errors'][] = [
                'message' => sprintf('No user found for email "%s".', $username),
                'field' => 'email',
                'type' => 'NotFound',
                'code' => 404,
            ];
        }

        return $response;
    }

    /**
     * Create a fresh configured user model for CLI create operations.
     *
     * Applications may map `User::class` to their own model implementation via
     * the framework model map. This hook keeps create operations on that mapped
     * class while still letting app tasks override the instantiation strategy
     * when they need custom construction.
     */
    protected function newUserEntity(): UserInterface
    {
        $userPrototype = $this->models->getUser();

        /** @var class-string<UserInterface> $userClass */
        $userClass = $userPrototype::class;

        return new $userClass();
    }

    /**
     * Resolve an explicitly requested stdin password without exposing it in argv.
     *
     * Existing positional/custom-model assignment remains supported. Stdin mode
     * rejects empty input and conflicting password arguments before any writes.
     *
     * @throws \PhalconKit\Exception\InvalidArgumentException When input is empty or ambiguous.
     */
    protected function resolvePasswordInput(?string $password): ?string
    {
        if (!$this->dispatcher->getParameter('passwordStdin')) {
            return $password;
        }
        if ($password !== null) {
            throw new \PhalconKit\Exception\InvalidArgumentException('Use either --password-stdin or a password argument.');
        }

        $password = rtrim($this->readPasswordFromStdin(), "\r\n");
        if ($password === '') {
            throw new \PhalconKit\Exception\InvalidArgumentException('Password input must not be empty.');
        }

        return $password;
    }

    /** Read CLI secret input; override only for an application input adapter. */
    protected function readPasswordFromStdin(): string
    {
        $password = stream_get_contents(STDIN);
        if ($password === false) {
            throw new \PhalconKit\Exception\InvalidArgumentException('Unable to read password input.');
        }
        return $password;
    }

    /**
     * Resolve the application's direct user-role membership relationship.
     *
     * Core's generated User defines UserRoleList. Application models may retain
     * RoleNode; prefer that alias when present to preserve their save hooks.
     * A missing relationship is a configuration error, never a successful role
     * assignment. The model manager must have initialized the resolved user.
     *
     * @throws \PhalconKit\Exception\LogicException When neither membership alias exists.
     */
    protected function getUserRoleAssignmentAlias(UserInterface $user): string
    {
        foreach (['RoleNode', 'UserRoleList'] as $alias) {
            if ($this->modelsManager->getRelationByAlias($user::class, $alias)) {
                return $alias;
            }
        }

        throw new \PhalconKit\Exception\LogicException('The user model needs a RoleNode or UserRoleList relationship.');
    }
    
    public function addModelsPermissions(?array $tables = null): void
    {
        $permissions = [];
        $tables ??= $this->getDefinitions();
        foreach ($tables as $model => $entity) {
            $permissions[$model] = ['*'];
        }
        $permissions[$this->models->getRoleClass()] = ['find'];
        $this->config->merge([
            'permissions' => [
                'roles' => [
                    'cli' => [
                        'models' => $permissions,
                    ],
                ],
            ],
        ]);
        
        $this->acl->setOption('permissions', $this->config->pathToArray('permissions') ?? []);
    }
}
