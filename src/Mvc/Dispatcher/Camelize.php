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

namespace PhalconKit\Mvc\Dispatcher;

use Phalcon\Dispatcher\AbstractDispatcher;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use PhalconKit\Di\Injectable;

/**
 * Normalizes dispatched controller and action names to framework method names.
 *
 * The listener converts dashed, underscored, or otherwise uncamelized route
 * parts into the controller/action casing expected by Phalcon dispatching. It
 * is intentionally not registered by the default dispatcher provider because
 * automatic route-name rewriting can be a compatibility-sensitive behavior for
 * applications that rely on exact route values.
 */
class Camelize extends Injectable
{
    /**
     * Normalize controller and action names before the dispatch loop runs.
     *
     * MVC dispatchers also receive a ucfirst-normalized controller name so
     * class lookup matches conventional controller class casing. All dispatcher
     * types receive an lcfirst-normalized action name so `my-action` and
     * `my_action` resolve to `myAction`.
     */
    public function beforeDispatchLoop(Event $event, AbstractDispatcher $dispatcher): void
    {
        if ($event->getType() === 'beforeDispatchLoop') {
            if ($dispatcher instanceof MvcDispatcher) {
                $dispatcher->setControllerName(
                    ucfirst(
                        $this->helper->camelize(
                            $this->helper->uncamelize(
                                $dispatcher->getControllerName()
                            )
                        )
                    )
                );
            }
            
            $dispatcher->setActionName(
                lcfirst(
                    $this->helper->camelize(
                        $this->helper->uncamelize(
                            $dispatcher->getActionName()
                        )
                    )
                )
            );
        }
    }
}
