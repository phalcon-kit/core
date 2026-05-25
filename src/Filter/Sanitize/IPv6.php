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
 * Sanitizer that accepts only valid IPv6 address strings.
 *
 * Valid IPv6 values are returned unchanged. Invalid input, `null`, and valid
 * addresses from the wrong family return an empty string, matching Phalcon's
 * common sanitizer pattern where failed scalar sanitization collapses to an
 * empty form value.
 */
class IPv6
{
    /**
     * Validate and return an IPv6 address.
     *
     * @param string|null $input Candidate address from request/config input.
     *
     * @return string The original IPv6 address, or an empty string when invalid.
     */
    public function __invoke(?string $input = null): string
    {
        return filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ?: '';
    }
}
