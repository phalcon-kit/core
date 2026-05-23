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

use PhalconKit\Dispatcher\DispatcherTrait;

/**
 * WebSocket task dispatcher.
 *
 * WebSocket requests use CLI-style task dispatching, with the shared
 * PhalconKit dispatcher helpers layered on top for namespace/action handling.
 */
class Dispatcher extends \PhalconKit\Cli\Dispatcher implements DispatcherInterface
{
    use DispatcherTrait;
}
