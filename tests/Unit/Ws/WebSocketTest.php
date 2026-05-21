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

namespace PhalconKit\Tests\Unit\Ws;

use Phalcon\Di\FactoryDefault\Cli;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Ws\WebSocket;

class WebSocketTest extends AbstractUnit
{
    public function testWebSocketExtendsCliConsole(): void
    {
        $webSocket = new WebSocket();

        $this->assertInstanceOf(\Phalcon\Cli\Console::class, $webSocket);
    }

    public function testConstructorAcceptsContainer(): void
    {
        $di = new Cli();
        $webSocket = new WebSocket($di);

        $this->assertSame($di, $webSocket->getDI());
    }
}
