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
 * Raised when a framework service cannot be resolved or used safely.
 *
 * Use this exception for missing DI services, wrong service types, invalid
 * service state, or runtime service contract violations. It extends
 * RuntimeException because these failures usually depend on the container or
 * current runtime state rather than a single method argument.
 */
class ServiceException extends \RuntimeException implements ExceptionInterface
{
}
