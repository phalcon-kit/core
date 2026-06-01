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

    private function createUserTask(): UserTask
    {
        $models = new Models([
            Role::class => UserTaskRoleDouble::class,
            User::class => UserTaskUserDouble::class,
        ]);
        $models->setDI($this->di);
        $this->di?->set('models', $models);

        $task = new UserTask();
        $task->setDI($this->di);

        return $task;
    }
}
