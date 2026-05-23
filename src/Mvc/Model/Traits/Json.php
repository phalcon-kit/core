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

namespace PhalconKit\Mvc\Model\Traits;

use PhalconKit\Exception\InvalidArgumentException;

/**
 * Trait Json
 *
 * This trait provides methods for encoding and decoding JSON data.
 */
trait Json
{
    /**
     * Encodes a value to JSON.
     *
     * @param mixed $value The value to be encoded.
     * @param int $flags [Optional] Bitmask of JSON encode options.
     *                              Defaults to JSON_UNESCAPED_SLASHES.
     * @param int $depth [Optional] The maximum depth of recursion when encoding nested objects.
     *                              Defaults to 512.
     *
     * @return string|false The JSON encoded string on success, or `false` on failure.
     */
    public function jsonEncode(mixed $value, int $flags = JSON_UNESCAPED_SLASHES, int $depth = 512): string|false
    {
        $depth = $this->validateJsonDepth($depth);
        return json_encode($value, $flags, $depth);
    }
    
    /**
     * Decodes a JSON string.
     *
     * @param string $json The JSON string to be decoded.
     * @param bool|null $associative [Optional] When `true`, returned objects will be converted into associative arrays.
     *                                         When `false`, objects will be returned as generic objects. If `null`, objects
     *                                         will be returned based on the JSON_NUMERIC_CHECK flag.
     * @param int $depth [Optional] The maximum depth of recursion when decoding nested objects.
     *                              Defaults to 512.
     * @param int $flags [Optional] Bitmask of JSON decode options.
     *                              Defaults to 0.
     *
     * @return mixed The decoded value on success, or the original JSON string on failure.
     */
    public function jsonDecode(string $json, ?bool $associative = null, int $depth = 512, int $flags = 0): mixed
    {
        $depth = $this->validateJsonDepth($depth);
        return json_decode($json, $associative, $depth, $flags);
    }
    
    /**
     * Validates that the provided depth is within the supported JSON recursion range.
     *
     * @param int $depth The recursion depth to validate.
     * @return int<1,2147483647> The validated depth.
     *
     * @throws InvalidArgumentException If depth is outside the valid range.
     */
    private function validateJsonDepth(int $depth): int
    {
        if ($depth < 1 || $depth > 2147483647) {
            throw new InvalidArgumentException(sprintf(
                'Invalid JSON depth: %d (must be between 1 and 2,147,483,647)',
                $depth
            ));
        }
        
        return $depth;
    }
}
