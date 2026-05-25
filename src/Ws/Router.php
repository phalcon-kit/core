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

use PhalconKit\Router\RouterInterface;

/**
 * WebSocket router built on Phalcon's CLI routing model.
 *
 * WebSocket commands are dispatched more like CLI tasks than MVC HTTP routes,
 * so the WebSocket module reuses the PhalconKit CLI router while exposing the
 * shared framework router contract.
 *
 * @see \PhalconKit\Cli\Router for the native CLI router-interface compatibility
 *     note.
 */
class Router extends \PhalconKit\Cli\Router implements RouterInterface
{
}
