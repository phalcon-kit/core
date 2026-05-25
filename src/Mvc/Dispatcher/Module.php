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

use Phalcon\Dispatcher\DispatcherInterface;
use Phalcon\Events\Event;
use PhalconKit\Di\Injectable;

/**
 * Dispatcher listener that keeps the module name synchronized during forwards.
 *
 * Phalcon forwards can carry a `module` route part without automatically
 * updating the dispatcher module name. This listener applies that route part so
 * downstream listeners and diagnostics see the forwarded module consistently.
 */
class Module extends Injectable
{
    /**
     * Apply a forwarded module name before Phalcon continues dispatching.
     *
     * @param Event $event Dispatch event emitted by Phalcon.
     * @param DispatcherInterface $dispatcher Active dispatcher instance.
     * @param array<string, mixed> $forward Forward route parts.
     */
    public function beforeForward(Event $event, DispatcherInterface $dispatcher, array $forward): void
    {
        if (!empty($forward['module'])) {
            $dispatcher->setModuleName($forward['module']);
            // Namespace rewriting on module forwards remains application
            // configuration until module namespace conventions are standardized.
        }
    }
}
