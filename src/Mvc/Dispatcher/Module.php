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

class Module extends Injectable
{
    public function beforeForward(Event $event, DispatcherInterface $dispatcher, array $forward): void
    {
        if (!empty($forward['module'])) {
            $dispatcher->setModuleName($forward['module']);
            // Namespace rewriting on module forwards remains application
            // configuration until module namespace conventions are standardized.
        }
    }
}
