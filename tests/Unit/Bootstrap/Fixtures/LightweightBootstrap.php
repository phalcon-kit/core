<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Bootstrap\Fixtures;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Events\Manager;
use PhalconKit\Bootstrap;
use PhalconKit\Config\ConfigInterface;

class LightweightBootstrap extends Bootstrap
{
    public function __construct(
        ?string $mode = self::MODE_MVC,
        ?DiInterface $di = null,
        ?ConfigInterface $config = null,
    ) {
        $this->setMode($mode);
        $this->setEventsManager(new Manager());
        $this->setDI($di ?? new Di());

        if ($config) {
            $this->setConfig($config);
        }
    }
}
