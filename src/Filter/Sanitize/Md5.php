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
 * Sanitizer for md5-style lowercase hexadecimal strings.
 *
 * The sanitizer strips every character outside `0-9` and `a-f`. It does not
 * hash the input and it does not validate that the remaining value is exactly
 * 32 characters long, so callers that need a strict md5 digest should combine
 * this sanitizer with a length or pattern validator.
 */
class Md5
{
    /**
     * Keep only lowercase hexadecimal characters from the input.
     *
     * @param string $input Candidate token or digest.
     *
     * @return string|null Sanitized lowercase hex characters, or null if the
     *     underlying regular-expression engine reports an error.
     */
    public function __invoke(string $input): ?string
    {
        return preg_replace('/[^0-9a-f]/', '', $input);
    }
}
