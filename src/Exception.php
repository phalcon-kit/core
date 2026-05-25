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

namespace PhalconKit;

use PhalconKit\Exception\ExceptionInterface;

/**
 * Base checked category for general PhalconKit framework exceptions.
 *
 * New framework code should prefer the more specific exception classes under
 * `PhalconKit\Exception` when a native PHP category is useful, such as
 * `ConfigurationException`, `ServiceException`, `LogicException`, or
 * `RuntimeException`. This base class remains available for older extension
 * points and general framework failures that should still implement the common
 * PhalconKit exception marker.
 */
class Exception extends \Exception implements ExceptionInterface
{
}
