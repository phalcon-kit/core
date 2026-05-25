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
 * HTTP request contract extended with PhalconKit request helpers.
 *
 * The extra helpers expose CORS, preflight, same-origin checks, and a diagnostic
 * array snapshot used by framework controllers and debugging tools. The helpers
 * classify request shape only; response policy such as allowed origins and
 * headers remains the responsibility of the application layer.
 */
interface RequestInterface extends \Phalcon\Http\RequestInterface
{
    /**
     * Return true when an Origin header targets a different origin.
     *
     * @return bool True when the request has a cross-origin `Origin` header.
     */
    public function isCors(): bool;
    
    /**
     * Return true when the request is a browser CORS preflight request.
     *
     * A preflight request must be cross-origin, use `OPTIONS`, and include a
     * non-empty `Access-Control-Request-Method` header.
     *
     * @return bool True when the request is shaped like a browser CORS
     *     preflight.
     */
    public function isPreflight(): bool;
    
    /**
     * Check whether the Origin header matches the current scheme and host.
     *
     * @return bool True when `Origin` equals the request scheme and host.
     */
    public function isSameOrigin(): bool;
    
    /**
     * Export a diagnostic snapshot of request input and derived request flags.
     *
     * The result may contain headers and authentication metadata. Treat it as a
     * debug/testing surface and redact sensitive values before production logs.
     *
     * @return array<string, mixed> Request bodies, parameters, headers,
     *     negotiated values, origin flags, HTTP method flags, and server
     *     metadata.
     */
    public function toArray(): array;
}
