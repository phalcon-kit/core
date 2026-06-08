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

use PhalconKit\Dispatcher\DispatcherTrait;

/**
 * CLI dispatcher with PhalconKit diagnostic state export.
 *
 * The dispatcher keeps Phalcon's native CLI dispatch behavior and adds the
 * shared PhalconKit dispatcher helper surface through {@see DispatcherTrait},
 * including state export used by diagnostics and tests.
 *
 * @see https://docs.phalcon.io/5.14/application-cli/
 */
class Dispatcher extends \Phalcon\Cli\Dispatcher implements DispatcherInterface
{
    use DispatcherTrait;
}
