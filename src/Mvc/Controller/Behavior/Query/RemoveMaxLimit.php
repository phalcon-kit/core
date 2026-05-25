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
 * Disables the maximum pagination limit before REST query initialization.
 *
 * The listener sets `maxLimit` to `-1`, the framework sentinel for unrestricted
 * result sizes, before configured/request pagination state is initialized.
 */
class RemoveMaxLimit
{
    /**
     * Disable the maximum limit before query state is built.
     *
     * @param Event $event Controller lifecycle event emitted before query initialization.
     * @param Restful $controller REST controller whose maximum limit should be disabled.
     * @return void
     */
    public function beforeInitializeQuery(Event $event, Restful $controller): void
    {
        $controller->setMaxLimit(-1);
    }
}
