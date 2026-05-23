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
 * Raised when PhalconKit detects an impossible or inconsistent framework state.
 *
 * Use this exception for violated internal invariants, invalid extension
 * contracts, or developer-facing integration mistakes that are not caused by a
 * single bad service lookup. It preserves PHP's native `LogicException`
 * inheritance so existing catch blocks remain compatible while adding the
 * PhalconKit exception marker contract.
 */
class LogicException extends \LogicException implements ExceptionInterface
{
}
