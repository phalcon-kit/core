<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

if (!function_exists('array_unset_recursive')) {
    /**
     * Remove selected keys from an array at every nesting level.
     *
     * The function mutates the provided array in place and returns the number of
     * entries removed. Numeric indexes are not reindexed after removal, matching
     * PHP's normal `unset()` behavior. Use `$strict = false` only when string and
     * integer key equivalence is explicitly desired.
     *
     * @param array<array-key, mixed> $array Array to mutate.
     * @param array<array-key, mixed> $keyList Keys that should be removed.
     * @param bool $strict Whether key comparisons should use strict type
     *     comparison.
     *
     * @return int Number of entries removed across all nesting levels.
     */
    function array_unset_recursive(array &$array, array $keyList, bool $strict = true): int
    {
        $removeCount = 0;
        foreach ($array as $key => $element) {
            if (in_array($key, $keyList, $strict)) {
                unset($array[$key]);
                $removeCount++;
            } elseif (is_array($element)) {
                $removeCount += array_unset_recursive($array[$key], $keyList, $strict);
            }
        }
        return $removeCount;
    }
}
