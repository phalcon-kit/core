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

namespace PhalconKit\Tests\Unit\Modules\Cli;

use Phalcon\Messages\Message;
use PhalconKit\Modules\Cli\Task;
use PhalconKit\Tests\Unit\AbstractUnit;

class TaskTest extends AbstractUnit
{
    public function testCliPayloadNormalizationRecursivelySerializesMessages(): void
    {
        $message = new Message('Invalid email.', 'email', 'PresenceOf', 422);
        $task = new class extends Task {
            public function exposeNormalizeCliPayload(mixed $payload): mixed
            {
                return $this->normalizeCliPayload($payload);
            }
        };

        $this->assertSame([
            'errors' => [
                [
                    'message' => 'Invalid email.',
                    'field' => 'email',
                    'type' => 'PresenceOf',
                    'code' => 422,
                ],
            ],
        ], $task->exposeNormalizeCliPayload([
            'errors' => [$message],
        ]));
    }
}
