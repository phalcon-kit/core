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
 * Clears REST cache options after cache configuration is initialized.
 *
 * Attach this listener to endpoints that should bypass controller-level query
 * caching without disabling the rest of the find-option preparation pipeline.
 */
class RemoveCacheConfig
{
    /**
     * Remove every cache option from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after cache config initialization.
     * @param Restful $controller REST controller whose cache config should be cleared.
     * @return void
     */
    public function afterInitializeCacheConfig(Event $event, Restful $controller): void
    {
        $controller->getCacheConfig()?->clear();
    }
}
