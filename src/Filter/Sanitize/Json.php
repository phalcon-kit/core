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

namespace PhalconKit\Filter\Sanitize;

/**
 * Sanitizer that keeps only syntactically valid JSON strings.
 *
 * This sanitizer does not decode, normalize, or re-encode JSON. It returns the
 * original string when `json_validate()` accepts it, returns `null` for invalid
 * JSON, and preserves `null` input as `null`. That makes it suitable for fields
 * that store raw JSON while still rejecting malformed payloads.
 */
class Json
{
    /**
     * Validate and return a raw JSON string.
     *
     * @param string|null $input Candidate JSON string.
     *
     * @return string|null The original JSON string, or null for invalid/null
     *     input.
     */
    public function __invoke(?string $input = null): ?string
    {
        if (is_null($input)) {
            return $input;
        }

        return json_validate($input) ? $input : null;
    }
}
