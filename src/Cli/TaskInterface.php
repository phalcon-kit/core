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
 * Marker contract for PhalconKit CLI task handlers.
 *
 * Tasks implement Phalcon's native task contract and can type against this
 * interface when they want to stay within the PhalconKit namespace. The
 * default {@see Task} base class adds typed injectable property annotations for
 * common CLI services.
 */
interface TaskInterface extends \Phalcon\Cli\TaskInterface
{
}
