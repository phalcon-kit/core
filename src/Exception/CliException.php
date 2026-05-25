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
 * Raised for command-line task and console dispatch failures.
 *
 * Use this exception when an error belongs specifically to the CLI execution
 * boundary and cannot be represented more clearly by configuration, service,
 * argument, or runtime exceptions. The class extends the historical base
 * PhalconKit exception for compatibility with existing CLI catch blocks.
 */
class CliException extends Exception
{
}
