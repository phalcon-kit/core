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

use PhalconKit\Di\InjectableProperties;

/**
 * Base class for PhalconKit CLI tasks.
 *
 * Extend this class for framework/application CLI tasks that need Phalcon's
 * native task lifecycle plus PhalconKit injectable service annotations. The
 * class does not add task behavior itself; action methods remain normal
 * Phalcon CLI task methods.
 *
 * @property \PhalconKit\Cli\Console $console
 * @property \PhalconKit\Cli\Router $router
 * @property \PhalconKit\Cli\Dispatcher $dispatcher
 *
 * @see https://docs.phalcon.io/5.17/application-cli/
 */
class Task extends \Phalcon\Cli\Task implements TaskInterface
{
    use InjectableProperties;
}
