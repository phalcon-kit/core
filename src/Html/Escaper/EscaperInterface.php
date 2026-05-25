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

namespace PhalconKit\Html\Escaper;

/**
 * Escaper contract with PhalconKit's JSON attribute escaping helper.
 *
 * Services typed against this interface can use every native Phalcon escaper
 * method plus `json()`, which is required by PhalconKit tag helpers when
 * embedding structured payloads in HTML attributes.
 */
interface EscaperInterface extends \Phalcon\Html\Escaper\EscaperInterface
{
    /**
     * Escape a JSON payload for safe embedding in an HTML attribute.
     *
     * @param mixed|null $json JSON string or scalar value to encode. Null is
     *     represented as the literal string `null`.
     *
     * @return string Raw-url-encoded JSON payload.
     */
    public function json(mixed $json = null): string;
}
