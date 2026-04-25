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

class WebSocket extends Console
{
    public function __construct(?DiInterface $container = null)
    {
        parent::__construct($container);
    }
}
