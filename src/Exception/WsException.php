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

namespace PhalconKit\Exception;

use PhalconKit\Exception;

/**
 * Raised for WebSocket bootstrap, routing, or request-handling failures.
 *
 * Use this exception when an error belongs specifically to the WebSocket
 * execution boundary and should be distinguishable from MVC HTTP or CLI
 * failures. Service/configuration problems inside that boundary should still
 * prefer the more specific PhalconKit exception category when possible.
 */
class WsException extends Exception
{
}
