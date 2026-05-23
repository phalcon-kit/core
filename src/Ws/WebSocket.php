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

use Phalcon\Cli\Console;
use Phalcon\Di\DiInterface;

/**
 * Console runtime used for WebSocket task dispatching.
 *
 * The class extends Phalcon's CLI console because WebSocket entrypoints route
 * to task/action pairs, while the surrounding provider/bootstrap layer owns
 * the actual server lifecycle.
 */
class WebSocket extends Console
{
    /**
     * Create the WebSocket console with an optional DI container.
     */
    public function __construct(?DiInterface $container = null)
    {
        parent::__construct($container);
    }
}
