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

namespace PhalconKit\Cli;

/**
 * Combined dispatcher contract for PhalconKit CLI dispatchers.
 *
 * Native CLI modules need Phalcon's dispatcher interface, while shared
 * PhalconKit diagnostics expect the framework dispatcher interface. This
 * combined contract lets DI providers enforce both without depending on the
 * concrete dispatcher class.
 */
interface DispatcherInterface extends \Phalcon\Cli\DispatcherInterface, \PhalconKit\Dispatcher\DispatcherInterface
{
}
