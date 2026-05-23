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
 * Raised for HTTP/request-level failures handled by PhalconKit controllers.
 *
 * Use this exception for invalid REST query parameters, unsupported content
 * types, authorization failures represented as HTTP status codes, and other
 * request validation errors. The exception code should carry the HTTP status
 * when one is available.
 */
class HttpException extends Exception
{
}
