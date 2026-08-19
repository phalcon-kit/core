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
