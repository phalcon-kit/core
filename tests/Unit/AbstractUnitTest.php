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

namespace PhalconKit\Tests\Unit;

final class AbstractUnitTest extends AbstractUnit
{
    public function testUnavailableServiceSkipMessageUsesConsistentText(): void
    {
        $this->assertSame(
            'Database service is not available.',
            $this->unavailableServiceSkipMessage('Database')
        );

        $this->assertSame(
            'Database service is not available: expected Phalcon MySQL adapter.',
            $this->unavailableServiceSkipMessage('Database', detail: ' expected Phalcon MySQL adapter. ')
        );

        $this->assertSame(
            'ClamAV service is not available: socket connection failed.',
            $this->unavailableServiceSkipMessage('ClamAV', new \RuntimeException('socket connection failed.'))
        );

        $this->assertSame(
            'Redis service is not available: connection attempt returned false.',
            $this->unavailableServiceSkipMessage(
                'Redis',
                new \RuntimeException('ignored exception detail.'),
                'connection attempt returned false.'
            )
        );
    }
}
