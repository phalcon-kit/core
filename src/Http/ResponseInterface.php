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
 * HTTP response contract used by PhalconKit services.
 *
 * The interface keeps Phalcon's response contract and makes the reason phrase
 * accessor explicit for code that needs to inspect final HTTP status metadata
 * after a controller or service has chosen a status code.
 */
interface ResponseInterface extends \Phalcon\Http\ResponseInterface
{
    /**
     * Return the reason phrase associated with the current status code.
     *
     * Phalcon responses can be created before any explicit status code/reason
     * phrase has been set, so callers should handle null.
     */
    public function getReasonPhrase(): string|null;
}
