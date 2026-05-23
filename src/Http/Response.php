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

namespace PhalconKit\Http;

/**
 * HTTP response implementation used by PhalconKit services.
 *
 * This wrapper delegates to Phalcon's response object while providing a
 * framework-scoped type for DI definitions, controller return values, and
 * service contracts.
 */
class Response extends \Phalcon\Http\Response implements ResponseInterface
{
}
