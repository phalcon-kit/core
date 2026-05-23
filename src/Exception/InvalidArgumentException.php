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

/**
 * Raised when a caller passes an invalid argument to PhalconKit code.
 *
 * Use this exception for method arguments, payload fragments, or helper input
 * that is structurally invalid but not specifically an application
 * configuration problem. It extends PHP's native `InvalidArgumentException` so
 * existing consumers that catch the native category keep working, while the
 * `ExceptionInterface` marker lets applications handle all PhalconKit-origin
 * failures through one framework contract.
 */
class InvalidArgumentException extends \InvalidArgumentException implements ExceptionInterface
{
}
