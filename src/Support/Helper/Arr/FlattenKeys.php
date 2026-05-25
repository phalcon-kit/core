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
 * Flatten nested arrays into dot-path keyed rule maps.
 *
 * Integer keys with string values are treated as shorthand enabled fields. This
 * shape is used by exposure and controller field policies where nested config
 * needs to become a flat lookup table. Nested arrays also create an explicit
 * entry for their parent key with `false` when no direct parent rule exists,
 * allowing callers to distinguish a container node from an enabled leaf.
 */
class FlattenKeys
{
    /**
     * Invoke the helper and always return an array.
     *
     * @param array<string|int, mixed> $collection Nested policy/config values.
     * @param string $delimiter Delimiter used between nested path segments.
     * @param bool $lowerKey Normalize string keys to lowercase.
     *
     * @return array<array-key, mixed>
     */
    public function __invoke(array $collection = [], string $delimiter = '.', bool $lowerKey = true): array
    {
        return self::process($collection, $delimiter, $lowerKey) ?? [];
    }
    
    /**
     * Flatten a nested rule map into delimiter-separated keys.
     *
     * Example input `['user' => ['email' => true]]` becomes
     * `['user.email' => true, 'user' => false]`. A list item like `['email']`
     * is treated as `['email' => true]` so concise allow-lists can be written
     * without repeating `true`.
     *
     * @param array<string|int, mixed> $collection Nested policy/config values.
     * @param string $delimiter Key delimiter, normally `.`.
     * @param bool $lowerKey Whether string keys should be normalized to lower
     *     case.
     * @param string|null $context Current recursion path.
     *
     * @return array<array-key, mixed>|null
     */
    public static function process(array $collection = [], string $delimiter = '.', bool $lowerKey = false, ?string $context = null): ?array
    {
        $ret = [];
        
        foreach ($collection as $key => $value) {
            // Numeric keys are shorthand for enabled string values.
            if (is_int($key)) {
                if (is_string($value)) {
                    $key = $value;
                    $value = true;
                }
                else {
                    $key = null;
                }
            }
            
            // Policy lookups are usually case-insensitive when requested.
            if (is_string($key)) {
                $key = trim($lowerKey ? mb_strtolower($key) : $key);
            }
            
            // Empty strings represent enabled leaves in compact config arrays.
            if (is_string($value) && empty($value)) {
                $value = true;
            }
            
            $currentKey = (!empty($context) ? $context . (!empty($key) ? $delimiter : null) : null) . $key;
            if (is_callable($value)) {
                $value = $value();
            }
            
            if (is_array($value)) {
                $subRet = self::process($value, $delimiter, $lowerKey, $currentKey);
                if (is_array($subRet)) {
                    $ret = array_merge_recursive($ret, $subRet);
                }
                
                if (!isset($ret[$currentKey])) {
                    $ret[$currentKey] = false;
                }
            }
            else {
                $ret[$currentKey] = $value;
            }
        }
        
        return $ret;
    }
}
