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
        
        if ($roleEntity) {
            $assign['rolenode'] = [['roleId' => $roleEntity->getId()]];
        }
        
        $userEntity = $this->newUserEntity();
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
                'rolenode' => [['roleId' => $roleEntity->getId()]],
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
            if (!empty($password)) {
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
    
    public function addModelsPermissions(?array $tables = null): void
    {
        $permissions = [];
        $tables ??= $this->getDefinitions();
        foreach ($tables as $model => $entity) {
            $permissions[$model] = ['*'];
        }
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
