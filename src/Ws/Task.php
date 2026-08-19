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

namespace PhalconKit\Ws;

use Phalcon\Mvc\Model\ManagerInterface as ModelsManagerInterface;
use PhalconKit\Di\InjectableProperties;

/**
 * Base class for WebSocket tasks.
 *
 * Tasks get typed access to the WebSocket console, router, and dispatcher
 * through PhalconKit injectable properties. Concrete tasks should implement
 * action methods such as `listenAction()`.
 *
 * @property WebSocket $webSocket
 * @property Router $router
 * @property Dispatcher $dispatcher
 */
class Task extends \Phalcon\Cli\Task implements TaskInterface
{
    use InjectableProperties;

    /**
     * Clear request-scoped model connection state in a long-running worker.
     *
     * Call this before custom WebSocket callbacks that perform model reads or
     * writes. The built-in abstract task invokes it for open, message, close,
     * HTTP request, and pipe-message callbacks.
     */
    public function resetConnectionState(): void
    {
        $di = $this->getDI();
        if (!$di->has('modelsManager')) {
            return;
        }

        $modelsManager = $di->get('modelsManager');
        if ($modelsManager instanceof ModelsManagerInterface) {
            $modelsManager->resetConnectionState();
        }
    }
}
