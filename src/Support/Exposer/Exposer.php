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

namespace PhalconKit\Support\Exposer;

/**
 * Exposer
 *
 * Production-ready, deterministic exposure engine built on top of a mutable Builder.
 *
 * Design goals (all preserved):
 * - Deny-by-default or allow-by-default behavior controlled explicitly by rules.
 * - Support deep dot-path rules (flattened internally).
 * - Support anonymous functions (closures) as dynamic callbacks.
 * - Support value transformation via string formatters.
 * - Support parent inheritance and child-activation semantics.
 * - Support protected fields (underscore-prefixed) opt-out.
 *
 * Core rule types:
 * - bool
 *   - true  → expose
 *   - false → hide
 *
 * - string
 *   - Expose and transform value using mb_vsprintf()
 *
 * - callable(Builder $builder)
 *   - Return BuilderInterface → caller mutates builder directly
 *   - Return string           → formatter
 *   - Return bool             → expose toggle
 *   - Return iterable         → additional column rules (merged)
 *
 * - iterable
 *   - Nested column definitions (flattened recursively)
 *
 * Root behavior:
 * - A boolean at index 0 (e.g. `[false, 'id', 'email']`)
 *   is treated as a rule on the ROOT path (`''`).
 * - This enables strict deny-by-default semantics with explicit allow-lists.
 */
class Exposer
{
    /**
     * Create and initialize a Builder for an exposure run.
     */
    public static function createBuilder(
        mixed $object,
        ?array $columns = null,
        ?bool $expose = null,
        ?bool $protected = null
    ): Builder {
        $expose ??= true;
        $protected ??= false;
        
        $builder = new Builder();
        $builder->setColumns(self::parseColumnsRecursive($columns));
        $builder->setExpose($expose);
        $builder->setProtected($protected);
        $builder->setValue($object);
        
        return $builder;
    }
    
    /**
     * Apply string formatting to a value.
     */
    private static function formatValue(string $format, mixed $value): string
    {
        return mb_vsprintf($format, [$value]);
    }
    
    /**
     * Determine whether the current builder node should be exposed.
     *
     * Resolution order:
     * 1. Exact rule match (including root: '')
     * 2. Nearest parent rule
     * 3. Child-activation (a deeper rule === true)
     * 4. Protected-field enforcement
     */
    private static function checkExpose(Builder $builder): void
    {
        $columns = $builder->getColumns() ?? [];
        
        /**
         * Canonical full key:
         * - Root is represented as empty string ''
         */
        $fullKey = $builder->getFullKey() ?? '';
        $value   = $builder->getValue();
        
        /* ------------------------------------------------------------
         * 1) Exact path rule (including root)
         * ------------------------------------------------------------ */
        if (array_key_exists($fullKey, $columns)) {
            self::applyRule($builder, $columns[$fullKey], $fullKey, $value);
        }
        
        /* ------------------------------------------------------------
         * 2) Parent path fallback (walk upward, first match wins)
         * ------------------------------------------------------------ */
        else {
            $parentKey = $fullKey;
            
            while ($parentKey !== '') {
                $pos = strrpos($parentKey, '.');
                $parentKey = ($pos === false) ? '' : substr($parentKey, 0, $pos);
                
                if (!array_key_exists($parentKey, $columns)) {
                    continue;
                }
                
                self::applyRule($builder, $columns[$parentKey], $parentKey, $value, true);
                break;
            }
        }
        
        /* ------------------------------------------------------------
         * 3) Child-activation:
         * If a deeper path is explicitly true, expose this container.
         * ------------------------------------------------------------ */
        if ($columns !== []) {
            $currentValue = $builder->getValue();
            
            if (is_iterable($currentValue) || is_callable($currentValue)) {
                foreach ($columns as $columnKey => $columnValue) {
                    if (
                        $columnValue === true
                        && is_string($columnKey)
                        && $columnKey !== ''
                        && ($fullKey === '' || str_starts_with($columnKey, $fullKey . '.'))
                    ) {
                        $builder->setExpose(true);
                        break;
                    }
                }
            }
        }
        
        /* ------------------------------------------------------------
         * 4) Protected-field enforcement
         * ------------------------------------------------------------ */
        $key = $builder->getKey();
        if (
            !$builder->getProtected()
            && is_string($key)
            && str_starts_with($key, '_')
        ) {
            $builder->setExpose(false);
        }
    }
    
    /**
     * Apply a single rule to the builder.
     *
     * @param mixed  $rule
     * @param string $ruleKey
     * @param mixed  $currentValue
     * @param bool   $isParentRule
     */
    private static function applyRule(
        Builder $builder,
        mixed $rule,
        string $ruleKey,
        mixed $currentValue,
        bool $isParentRule = false
    ): void {
        /* ------------------------------------------------------------
         * Boolean rule
         * ------------------------------------------------------------ */
        if (is_bool($rule)) {
            $builder->setExpose($rule);
            return;
        }
        
        /* ------------------------------------------------------------
         * Callable rule (anonymous functions fully supported)
         * ------------------------------------------------------------ */
        if (is_callable($rule)) {
            $builder->setExpose(true);
            
            $callbackReturn = $rule($builder);
            
            if ($callbackReturn instanceof BuilderInterface) {
                // Callback owns builder mutation
                return;
            }
            
            if (is_string($callbackReturn)) {
                $builder->setExpose(true);
                $builder->setValue(self::formatValue($callbackReturn, $currentValue));
                return;
            }
            
            if (is_bool($callbackReturn)) {
                $builder->setExpose($callbackReturn);
                return;
            }
            
            if (is_iterable($callbackReturn)) {
                // Parent rules do not re-merge columns to avoid cascade duplication
                if ($isParentRule) {
                    return;
                }
                
                $parsed = self::parseColumnsRecursive($callbackReturn, $ruleKey) ?? [];
                
                // If callable introduced sub-rules without defining itself,
                // default current ruleKey to false (deny container)
                if (!array_key_exists($ruleKey, $parsed)) {
                    $parsed[$ruleKey] = false;
                }
                
                $builder->setColumns(
                    array_merge($builder->getColumns() ?? [], $parsed)
                );
                return;
            }
            
            return;
        }
        
        /* ------------------------------------------------------------
         * String rule (formatter)
         * ------------------------------------------------------------ */
        if (is_string($rule)) {
            // Historical behavior:
            // Parent string rule hides container but still formats value.
            $builder->setExpose(!$isParentRule);
            $builder->setValue(self::formatValue($rule, $currentValue));
            return;
        }
        
        // Unsupported types are ignored intentionally
    }
    
    /**
     * Expose a value graph according to builder state.
     */
    public static function expose(Builder $builder): mixed
    {
        $columns = $builder->getColumns();
        $value   = $builder->getValue();
        
        if (is_iterable($value) || is_object($value)) {
            $toParse = is_object($value) && method_exists($value, 'toArray')
                ? $value->toArray()
                : (array) $value;
            
            // Explicit deny-by-default at root
            if ($columns === null && !$builder->getExpose()) {
                return [];
            }
            
            $ret = [];
            
            // Preserve context across recursion
            $currentContextKey = $builder->getContextKey();
            $builder->setContextKey($builder->getFullKey());
            
            foreach ($toParse as $fieldKey => $fieldValue) {
                $builder->setParent($value);
                $builder->setKey((string) $fieldKey);
                $builder->setValue($fieldValue);
                
                self::checkExpose($builder);
                
                if ($builder->getExpose()) {
                    $ret[$fieldKey] = self::expose($builder);
                }
            }
            
            $builder->setContextKey($currentContextKey);
            
            return $ret;
        }
        
        return $builder->getExpose() ? $value : null;
    }
    
    /**
     * Parse column definitions into a flattened dot-path rule map.
     *
     * Root semantics:
     * - A boolean without a key becomes a rule on the root path ('').
     * - Root context never produces ".field" keys.
     */
    public static function parseColumnsRecursive(
        ?iterable $columns = null,
        ?string $context = null
    ): ?array {
        if ($columns === null) {
            return null;
        }
        
        /** @var array<string, mixed> $ret */
        $ret = [];
        
        foreach ($columns as $key => $value) {
            /* --------------------------------------------------------
             * Boolean key → root rule
             * -------------------------------------------------------- */
            if (is_bool($key)) {
                $value = $key;
                $key = null;
            }
            
            /* --------------------------------------------------------
             * Numeric key
             * -------------------------------------------------------- */
            if (is_int($key)) {
                if (is_string($value)) {
                    $key = $value;
                    $value = true;
                } else {
                    $key = null;
                }
            }
            
            if (is_string($key)) {
                $key = trim(mb_strtolower($key));
            }
            
            if (is_string($value) && $value === '') {
                $value = true;
            }
            
            /* --------------------------------------------------------
             * Build dot-path safely
             * -------------------------------------------------------- */
            if ($context === null || $context === '') {
                $currentKey = $key ?? '';
            } else {
                $currentKey = ($key !== null) ? ($context . '.' . $key) : $context;
            }
            
            if ($currentKey === null) {
                continue;
            }
            
            /* --------------------------------------------------------
             * Assign rule
             * -------------------------------------------------------- */
            if (is_callable($value)) {
                $ret[$currentKey] = $value;
                continue;
            }
            
            if (is_iterable($value)) {
                $sub = self::parseColumnsRecursive($value, $currentKey) ?? [];
                $ret = array_merge_recursive($ret, $sub);
                
                // If only subkeys exist, default container to false
                if (!array_key_exists($currentKey, $ret)) {
                    $ret[$currentKey] = false;
                }
                
                continue;
            }
            
            $ret[$currentKey] = $value;
        }
        
        return $ret;
    }
}
