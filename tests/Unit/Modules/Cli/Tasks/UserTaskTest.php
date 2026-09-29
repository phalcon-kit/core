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

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks;

use Phalcon\Messages\Message;
use PhalconKit\Models\Role;
use PhalconKit\Models\User;
use PhalconKit\Models\UserRole;
use PhalconKit\Modules\Cli\Tasks\UserTask;
use PhalconKit\Support\Models;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures\UserTaskRoleDouble;
use PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures\UserTaskUserDouble;

class UserTaskTest extends AbstractUnit
{
    protected function setUp(): void
    {
        parent::setUp();
        UserTaskRoleDouble::resetState();
        UserTaskUserDouble::resetState();
    }

    protected function tearDown(): void
    {
        UserTaskRoleDouble::resetState();
        UserTaskUserDouble::resetState();
        parent::tearDown();
    }

    public function testCreateActionUsesMappedUserModel(): void
    {
        $result = $this->createUserTask()->createAction('user@example.test', '12Dev34');

        $this->assertSame([
            'errors' => [],
            'save' => 1,
        ], $result);
        $this->assertCount(1, UserTaskUserDouble::$saved);
        $user = UserTaskUserDouble::$saved[0];
        $this->assertSame([
            'email' => 'user@example.test',
            'firstName' => 'User',
            'lastName' => 'User',
            'password' => '12Dev34',
            'passwordConfirm' => '12Dev34',
        ], $user->assigned);
    }

    public function testUserTaskActionsAreOverridableByApplications(): void
    {
        foreach (['createAction', 'roleAction', 'passwordAction'] as $method) {
            $this->assertFalse((new \ReflectionMethod(UserTask::class, $method))->isFinal(), $method);
        }
    }

    public function testPasswordActionReportsNotFoundForTargetedMissingUser(): void
    {
        $result = $this->createUserTask()->passwordAction('missing@example.test', '12Dev34');

        $this->assertSame([
            'errors' => [
                [
                    'message' => 'No user found for email "missing@example.test".',
                    'field' => 'email',
                    'type' => 'NotFound',
                    'code' => 404,
                ],
            ],
            'matched' => 0,
            'save' => 0,
        ], $result[UserTaskUserDouble::class]);
    }

    public function testPasswordActionSavesTargetedMatchedUser(): void
    {
        $task = $this->createUserTask();
        $user = UserTaskUserDouble::make('user@example.test');
        UserTaskUserDouble::$rows = [$user];

        $result = $task->passwordAction('user@example.test', '12Dev34');

        $this->assertSame([
            'errors' => [],
            'matched' => 1,
            'save' => 1,
        ], $result[UserTaskUserDouble::class]);
        $this->assertSame('12Dev34', $user->assigned['password'] ?? null);
        $this->assertSame('12Dev34', $user->assigned['passwordConfirm'] ?? null);
        $this->assertSame([$user], UserTaskUserDouble::$saved);
    }

    public function testPasswordActionExposesSaveFailureMessages(): void
    {
        $task = $this->createUserTask();
        $message = new Message('Password reset is not allowed.', 'password', 'Forbidden', 403);
        UserTaskUserDouble::$rows = [
            UserTaskUserDouble::make('user@example.test', saveResult: false, messages: [$message]),
        ];

        $result = $task->passwordAction('user@example.test', '12Dev34');

        $this->assertSame([
            'errors' => [
                [
                    'message' => 'Password reset is not allowed.',
                    'field' => 'password',
                    'type' => 'Forbidden',
                    'code' => 403,
                ],
            ],
            'matched' => 1,
            'save' => 0,
        ], $result[UserTaskUserDouble::class]);
    }

    public function testCreateAssignsTheGeneratedUserRoleList(): void
    {
        $task = $this->createUserTask();
        $role = new UserTaskRoleDouble();
        $role->setId(7);
        UserTaskRoleDouble::$row = $role;

        $task->createAction('editor@example.test', 'A password!');

        $this->assertSame([['roleId' => 7]], UserTaskUserDouble::$saved[0]->assigned['UserRoleList']);
    }

    public function testCreatePreservesAnApplicationRoleNodeAlias(): void
    {
        $task = $this->createUserTask();
        $user = new UserTaskUserDouble();
        $user->getModelsManager()->addHasMany($user, 'id', UserRole::class, 'userId', ['alias' => 'RoleNode']);
        $role = new UserTaskRoleDouble();
        $role->setId(8);
        UserTaskRoleDouble::$row = $role;

        $task->createAction('editor@example.test', 'A password!');

        $this->assertSame([['roleId' => 8]], UserTaskUserDouble::$saved[0]->assigned['RoleNode']);
    }

    public function testCreateReadsPasswordFromStdinWithoutChangingMappedAssignment(): void
    {
        $task = $this->createUserTask(new class extends UserTask {
            protected function readPasswordFromStdin(): string
            {
                return "A private stdin password!\n";
            }
        });
        $task->dispatcher->setParameter('passwordStdin', true);

        $task->createAction('editor@example.test');

        $this->assertSame('A private stdin password!', UserTaskUserDouble::$saved[0]->assigned['password']);
    }

    public function testStdinPasswordCannotResetEveryAccount(): void
    {
        $task = $this->createUserTask();
        $task->dispatcher->setParameter('passwordStdin', true);

        $this->expectException(\PhalconKit\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('A target email is required');
        $task->passwordAction();
    }

    public function testPasswordActionPreservesExplicitZeroStdinPassword(): void
    {
        $task = $this->createUserTask(new class extends UserTask {
            protected function readPasswordFromStdin(): string
            {
                return "0\n";
            }
        });
        $task->dispatcher->setParameter('passwordStdin', true);
        $user = UserTaskUserDouble::make('user@example.test');
        UserTaskUserDouble::$rows = [$user];

        $task->passwordAction('user@example.test');

        $this->assertSame('0', $user->assigned['password']);
        $this->assertSame('0', $user->assigned['passwordConfirm']);
    }

    public function testEmptyStdinPasswordDoesNotFallBackToGeneratedCredentials(): void
    {
        $task = $this->createUserTask(new class extends UserTask {
            protected function readPasswordFromStdin(): string
            {
                return "\n";
            }
        });
        $task->dispatcher->setParameter('passwordStdin', true);

        $this->expectException(\PhalconKit\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');
        $task->createAction('editor@example.test');
    }

    public function testTaskOptionsAreRemovedFromPositionalActionArguments(): void
    {
        $task = $this->createUserTask();
        $argv = $_SERVER['argv'];
        $_SERVER['argv'] = ['phalcon-kit', 'cli', 'user', 'create', 'editor@example.test', '--password-stdin'];
        $task->dispatcher->setParameters(['editor@example.test', '--password-stdin']);
        try {
            $task->beforeExecuteRoute();
            $params = $task->dispatcher->getParameters();
            $this->assertSame(['editor@example.test'], array_filter($params, 'is_int', ARRAY_FILTER_USE_KEY));
            $this->assertTrue($task->dispatcher->getParameter('passwordStdin'));
        } finally {
            $_SERVER['argv'] = $argv;
        }
    }

    public function testStdinPasswordRejectsConflictingArgumentsBeforeSaving(): void
    {
        $task = $this->createUserTask();
        $task->dispatcher->setParameter('passwordStdin', true);

        $this->expectException(\PhalconKit\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('Use either');
        $task->createAction('editor@example.test', 'A conflicting password!');
    }

    private function createUserTask(?UserTask $task = null): UserTask
    {
        $models = new Models([
            Role::class => UserTaskRoleDouble::class,
            User::class => UserTaskUserDouble::class,
        ]);
        $models->setDI($this->di);
        $this->di?->set('models', $models);

        $task ??= new UserTask();
        $task->setDI($this->di);

        return $task;
    }
}
