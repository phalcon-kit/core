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

namespace PhalconKit\Support\Helper\Arr;

/**
 * Replace string fragments throughout a nested array.
 *
 * This helper is intentionally conservative: nested arrays are processed
 * recursively, string leaves are passed through `str_replace()`, and all other
 * values are returned unchanged. It is useful for config/template arrays that
 * contain placeholders alongside booleans, numbers, or nulls.
 */
class RecursiveStrReplace
{
    /**
     * Invoke the helper and return an array result.
     *
     * @param array<string|int, mixed> $collection Input array.
     * @param array<string, string> $replaces Search/replace map.
     *
     * @return array<string|int, mixed>
     */
    public function __invoke(array $collection, array $replaces): array
    {
        return self::process($collection, $replaces) ?? [];
    }
    
    /**
     * Recursively replace string values while preserving non-string values.
     *
     * @param array<string|int, mixed> $collection Input array.
     * @param array<string, string> $replaces Search strings as keys and
     *     replacement strings as values.
     *
     * @return array<string|int, mixed>|null
     */
    public static function process(array $collection, array $replaces): ?array
    {
        return array_map(function ($value) use ($replaces) {
            if (is_array($value)) {
                return self::process($value, $replaces);
            }
            if (is_string($value)) {
                return str_replace(array_keys($replaces), array_values($replaces), $value);
            }
            return $value;
        }, $collection);
    }
}
