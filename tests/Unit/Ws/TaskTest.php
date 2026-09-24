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

namespace PhalconKit\Tests\Unit\Ws;

use PhalconKit\Di\Di;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Ws\Task;

class TaskTest extends AbstractUnit
{
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function testWorkerErrorCallbackPreservesNativeFieldsAndExistingOverride(): void
    {
        if (!class_exists(\Swoole\WebSocket\Server::class)) {
            eval('namespace Swoole\WebSocket; class Server { public function __construct(string $host, int $port) {} }');
        }

        // Construct the callback argument without starting a server event loop.
        $server = new \Swoole\WebSocket\Server('127.0.0.1', 0);
        $task = new class extends \PhalconKit\Modules\Ws\Tasks\AbstractTask {
            public array $workerErrors = [];
            public array $messages = [];

            #[\Override]
            public function onWorkerError(\Swoole\WebSocket\Server $server, int $fd, int $code, string $reason): void
            {
                $this->workerErrors[] = [$server, $fd, $code, $reason];
                parent::onWorkerError($server, $fd, $code, $reason);
            }

            #[\Override]
            public function log(string $message, ?\Swoole\WebSocket\Server $server = null): void
            {
                $this->messages[] = $message;
            }
        };
        $task->initializeWorkerError();

        ($task->onWorkerError)($server, 7, 4242, 255, 9);

        $this->assertSame([[$server, 7, 255, 'pid=4242, signal=9']], $task->workerErrors);
        $this->assertSame(['Worker error: workerId=7, exitCode=255, pid=4242, signal=9'], $task->messages);
    }

    public function testResetConnectionStateDelegatesToSharedModelsManager(): void
    {
        $di = new Di();
        $manager = new class extends Manager {
            public int $resetCalls = 0;

            #[\Override]
            public function resetConnectionState(): void
            {
                $this->resetCalls++;
                parent::resetConnectionState();
            }
        };
        $di->set('modelsManager', $manager);
        $task = new Task();
        $task->setDI($di);

        $task->resetConnectionState();

        $this->assertSame(1, $manager->resetCalls);
    }

    public function testResetConnectionStateAllowsMissingModelsManager(): void
    {
        $task = new Task();
        $task->setDI(new Di());

        $task->resetConnectionState();

        $this->addToAssertionCount(1);
    }
}
