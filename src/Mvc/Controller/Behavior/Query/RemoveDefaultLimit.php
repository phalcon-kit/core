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

namespace PhalconKit\Mvc\Controller\Behavior\Query;

use Phalcon\Events\Event;
use PhalconKit\Mvc\Controller\Restful;

/**
 * Disables default pagination before REST query initialization.
 *
 * This listener sets both `maxLimit` and `limit` to `-1`, the framework
 * sentinel used for unrestricted queries, before request/configured pagination
 * state is assembled.
 */
class RemoveDefaultLimit
{
    /**
     * Disable the maximum and current limits before query state is built.
     *
     * @param Event $event Controller lifecycle event emitted before query initialization.
     * @param Restful $controller REST controller whose pagination defaults should be disabled.
     * @return void
     */
    public function beforeInitializeQuery(Event $event, Restful $controller): void
    {
        $controller->setMaxLimit(-1);
        $controller->setLimit(-1);
    }
}
