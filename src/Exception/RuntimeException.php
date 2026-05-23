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
 * Raised when a PhalconKit operation fails at runtime outside DI resolution.
 *
 * Prefer `ServiceException` for missing or invalid DI services and
 * `ConfigurationException` for bad config. Use this class for operational
 * runtime failures such as query-building failures, unsupported relation
 * shapes encountered during execution, or wrapped lower-level exceptions that
 * should remain in the native runtime-exception family.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
