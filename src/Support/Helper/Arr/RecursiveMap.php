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
 * Apply a callback to every scalar value in a nested array.
 *
 * Array keys and nesting are preserved. Only non-array leaf values are passed
 * to the callback, which makes the helper useful for normalizing config values
 * without changing the shape expected by providers or policy builders.
 */
class RecursiveMap
{
    /**
     * Invoke the helper for a nested array.
     *
     * @param array<string|int, mixed> $collection Input array.
     * @param callable $callback Callback applied to each non-array value.
     *
     * @return array<string|int, mixed>
     */
    public function __invoke(array $collection, callable $callback): array
    {
        return self::process($collection, $callback);
    }
    
    /**
     * Apply a callback to each non-array value and preserve array structure.
     *
     * @param array<string|int, mixed> $collection The array to process.
     * @param callable $callback Callback receiving each non-array value.
     *
     * @return array<string|int, mixed>
     */
    public static function process(array $collection, callable $callback): array
    {
        $func = function (mixed $item) use (&$func, &$callback): mixed {
            return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
        };
        
        return array_map($func, $collection);
    }
}
