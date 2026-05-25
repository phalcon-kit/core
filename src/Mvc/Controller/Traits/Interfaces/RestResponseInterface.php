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

namespace PhalconKit\Mvc\Controller\Traits\Interfaces;

use Phalcon\Http\ResponseInterface;

/**
 * Contract for normalizing REST response payloads.
 */
interface RestResponseInterface
{
    /**
     * Set an error response payload and status code.
     *
     * @param int $code HTTP status code.
     * @param string $status HTTP status message.
     * @param mixed $response Error payload.
     */
    public function setRestErrorResponse(int $code = 400, string $status = 'Bad Request', mixed $response = null): ResponseInterface;
    
    /**
     * Set a JSON REST response payload.
     *
     * @param mixed $response Response payload.
     * @param int|null $code Optional HTTP status code.
     * @param string|null $status Optional HTTP status message.
     * @param int $jsonOptions JSON encoding flags.
     * @param int $depth Maximum JSON encoding depth.
     */
    public function setRestResponse(mixed $response = null, ?int $code = null, ?string $status = null, int $jsonOptions = 0, int $depth = 512): ResponseInterface;
}
