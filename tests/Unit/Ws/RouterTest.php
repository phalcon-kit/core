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

use PhalconKit\Router\RouterInterface;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Ws\Router;

class RouterTest extends AbstractUnit
{
    public function testWebSocketRouterUsesCliTaskRoutingContract(): void
    {
        $router = new Router();

        $this->assertInstanceOf(\PhalconKit\Cli\Router::class, $router);
        $this->assertInstanceOf(\Phalcon\Cli\RouterInterface::class, $router);
        $this->assertInstanceOf(RouterInterface::class, $router);

        $router->handle([
            'module' => 'ws',
            'task' => 'broadcast',
            'action' => 'send',
            'params' => [
                'channel' => 'alerts',
            ],
        ]);

        $this->assertSame('ws', $router->getModuleName());
        $this->assertSame('broadcast', $router->getTaskName());
        $this->assertSame('send', $router->getActionName());
        $this->assertSame([
            'channel' => 'alerts',
        ], $router->getParams());
        $this->assertSame([
            'module' => 'ws',
            'task' => 'broadcast',
            'action' => 'send',
            'params' => [
                'channel' => 'alerts',
            ],
            'matches' => [],
            'matched' => null,
        ], $router->toArray());
    }
}
