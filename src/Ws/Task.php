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
use PhalconKit\Di\RequestState;

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
     * Clear all request-scoped state in a long-running worker.
     *
     * One Swoole worker serves many clients with the same container, so state
     * from one callback must not reach the next: write-connection pinning, the
     * identity manager's cached claim and users, the model ACL role cache, and
     * the Blameable parent link. The built-in abstract task invokes this for
     * open, message, close, HTTP request, and pipe-message callbacks; call it
     * before custom callbacks that authenticate or perform model operations.
     */
    public function resetRequestState(): void
    {
        $this->resetConnectionState();
        RequestState::reset($this->getDI());
    }

    /**
     * Clear request-scoped model connection state in a long-running worker.
     *
     * This is part of `resetRequestState()`, which callbacks should call
     * instead so identity and model ACL caches are reset too.
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
