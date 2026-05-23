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

/**
 * Marker contract for WebSocket task classes.
 *
 * The interface gives applications a stable PhalconKit type for task
 * discovery, DI checks, and documentation without constraining action method
 * names beyond Phalcon's task dispatcher conventions.
 */
interface TaskInterface
{
}
