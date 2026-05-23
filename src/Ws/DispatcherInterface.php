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
 * Contract for WebSocket task dispatchers.
 *
 * This combines Phalcon's native CLI dispatcher surface with PhalconKit's
 * shared dispatcher helpers so WebSocket tasks can be resolved and inspected
 * through the same contract as CLI tasks.
 */
interface DispatcherInterface extends \Phalcon\Cli\DispatcherInterface, \PhalconKit\Dispatcher\DispatcherInterface
{
}
