<?php

declare(strict_types=1);

namespace PhalconKit\Mvc\Controller\Traits\Query;

use LogicException;
use Phalcon\Support\Collection;

/**
 * Find-definition compiler.
 *
 * Purpose
 * - Accepts heterogeneous "find" inputs (Phalcon-style arrays + custom/nested condition blocks).
 * - Normalizes them into a single, merge-safe structure.
 * - Preserves existing behaviors:
 *   - Supports numeric Phalcon signature: [0 => '...', 1 => bind[], 2 => bindTypes[]]
 *   - Supports 'conditions' as string or list (merged as list, stringified later)
 *   - Supports nested condition blocks under 'conditions' (recursive compilation)
 *   - Enforces bind/bindTypes collision rules (only identical collisions are allowed)
 *   - Rejects merging unknown keys when they collide (explicit failure, not silent override)
 *   - Final stringification of group/order/distinct lists to CSV strings
 */
trait Compiler
{
    /**
     * Convert a Collection into a recursively compiled array suitable for find compilation.
     *
     * Behavior:
     * - Recursively converts nested Collections to arrays.
     * - Drops null values (but keeps false/0/"").
     * - At the first nesting level (level === 1), returns a list (array_values) to preserve JSON/list semantics.
     *
     * @return array<array-key, mixed>
     */
    public function prepareCollectionToCompile(Collection $collection, int $level = 0): array
    {
        $result = [];
        
        foreach ($collection->getIterator() as $key => $value) {
            if ($value instanceof Collection) {
                $value = $this->prepareCollectionToCompile($value, $level + 1);
            }
            
            // Keep false/0/"", drop only null
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        
        return $level === 1 ? array_values($result) : $result;
    }
    
    /**
     * Compile and merge multiple find definitions.
     *
     * @param array<array-key, mixed> ...$finds
     * @return array<array-key, mixed>
     */
    public function compileFinds(array ...$finds): array
    {
        $compiled = [];
        
        foreach ($finds as $find) {
            $compiled[] = $this->compileFind($find);
        }
        
        return $this->mergeCompiledFind(...$compiled);
    }
    
    /**
     * Merge multiple *already compiled* find definitions.
     *
     * Merge rules (explicit and strict):
     * - Scalar keys that must match if provided by multiple inputs: limit, offset, column
     * - conditions: list-merge (stringification happens in afterMergeCompileFind)
     * - joins: list-merge
     * - columns/distinct/group/order/models: list-merge (later stringified by implodeUniqueToString)
     * - bind/bindTypes: collision allowed only when values are identical
     * - boolean flags: true wins
     * - other keys: disallow collisions (fail fast)
     *
     * @param array<array-key, mixed> ...$compiledFinds
     * @return array<array-key, mixed>
     */
    public function mergeCompiledFind(array ...$compiledFinds): array
    {
        /** @var array<array-key, mixed> $out */
        $out = [];
        
        foreach ($compiledFinds as $f) {
            // Scalars that must match if present in multiple inputs
            foreach (['limit', 'offset', 'column'] as $k) {
                if (!array_key_exists($k, $f)) {
                    continue;
                }
                
                if (array_key_exists($k, $out) && $out[$k] !== $f[$k]) {
                    throw new LogicException(sprintf('Cannot merge find definitions with different %s.', $k));
                }
                
                $out[$k] = $f[$k];
            }
            
            // conditions: always list-merge here (stringification later)
            if (array_key_exists('conditions', $f)) {
                $conds = $this->normalizeListValue($f['conditions'], 'conditions');
                $out['conditions'] = array_merge($out['conditions'] ?? [], $conds);
            }
            
            // joins: list merge (kept as list; any dedupe/normalization is an upstream concern)
            if (array_key_exists('joins', $f)) {
                $joins = $this->normalizeListValue($f['joins'], 'joins');
                $out['joins'] = array_merge($out['joins'] ?? [], $joins);
            }
            
            // columns/distinct/group/order/models: list merge (may be stringified later)
            foreach (['columns', 'distinct', 'group', 'order', 'models'] as $k) {
                if (!array_key_exists($k, $f)) {
                    continue;
                }
                
                $list = $this->normalizeListValue($f[$k], $k);
                $out[$k] = array_merge($out[$k] ?? [], $list);
            }
            
            // bind / bindTypes: forbid key collisions (or allow if identical)
            foreach (['bind', 'bindTypes'] as $k) {
                if (!array_key_exists($k, $f)) {
                    continue;
                }
                
                $value = $f[$k];
                
                if ($value === null) {
                    continue;
                }
                
                if (!is_array($value)) {
                    throw new LogicException(sprintf('Invalid %s value: expected array.', $k));
                }
                
                if (!isset($out[$k])) {
                    $out[$k] = [];
                }
                
                if (!is_array($out[$k])) {
                    // Defensive: if upstream code injected a non-array, fail loudly.
                    throw new LogicException(sprintf('Invalid merged %s value: expected array.', $k));
                }
                
                /** @var array<array-key, mixed> $outMap */
                $outMap = $out[$k];
                /** @var array<array-key, mixed> $inMap */
                $inMap = $value;
                
                $collisions = array_intersect_key($outMap, $inMap);
                if ($collisions !== []) {
                    foreach (array_keys($collisions) as $ck) {
                        if ($outMap[$ck] !== $inMap[$ck]) {
                            throw new LogicException(
                                sprintf('Cannot merge find definitions: %s key collision on "%s".', $k, (string) $ck)
                            );
                        }
                    }
                }
                
                // Safe merge
                foreach ($inMap as $bk => $bv) {
                    $outMap[$bk] = $bv;
                }
                
                $out[$k] = $outMap;
            }
            
            // Boolean flags (true wins)
            foreach (['for_update', 'shared_lock'] as $k) {
                if (!array_key_exists($k, $f)) {
                    continue;
                }
                
                $out[$k] = (bool) ($out[$k] ?? false) || (bool) $f[$k];
            }
            
            /**
             * All remaining keys:
             * - If key is already handled above, skip
             * - If key collides, reject (explicitly unsupported merge)
             */
            foreach ($f as $k => $v) {
                if (in_array($k, [
                    'limit',
                    'offset',
                    'column',
                    'columns',
                    'distinct',
                    'conditions',
                    'bind',
                    'bindTypes',
                    'joins',
                    'group',
                    'order',
                    'for_update',
                    'shared_lock',
                    'models',
                ], true)) {
                    continue;
                }
                
                if (array_key_exists($k, $out)) {
                    throw new LogicException(sprintf('Cannot merge find definitions: key collision on "%s".', (string) $k));
                }
                
                $out[$k] = $v;
            }
        }
        
        $this->afterMergeCompileFind($out);
        
        return $out;
    }
    
    /**
     * Post-merge normalization hook.
     *
     * Responsibilities:
     * - Convert merged conditions list into a single AND-string (preserves the previous "stringify after merge" behavior)
     * - Disallow integer-like keys in merged output (guards against accidental list merges at root)
     * - Convert group/order/distinct arrays into CSV strings (unique values only)
     *
     * @param array<array-key, mixed> $merged
     */
    public function afterMergeCompileFind(array &$merged): void
    {
        if (!empty($merged['conditions'])) {
            if (!is_array($merged['conditions'])) {
                throw new LogicException('Invalid merged conditions: expected array.');
            }
            
            /** @var array<int, mixed> $conditions */
            $conditions = $merged['conditions'];
            
            $conditions = array_map(
                static fn(mixed $v): string => is_string($v) ? trim($v) : trim((string) $v),
                $conditions
            );
            $conditions = array_filter($conditions, static fn(string $v): bool => $v !== '');
            $conditions = array_values(array_unique($conditions));
            
            if ($conditions !== []) {
                $merged['conditions'] = '(' . implode(') AND (', $conditions) . ')';
            } else {
                unset($merged['conditions']);
            }
        }
        
        foreach (array_keys($merged) as $k) {
            if (is_int($k)) {
                throw new LogicException('Cannot merge find definitions: integer keys are not allowed.');
            }
            
            if (is_string($k) && ctype_digit($k)) {
                throw new LogicException('Cannot merge find definitions: integer-like keys are not allowed.');
            }
        }
        
        $this->implodeUniqueToString($merged);
    }
    
    /**
     * Compile a single find definition into a normalized/merge-ready structure.
     *
     * @param array<array-key, mixed> $find
     * @return array<array-key, mixed>
     */
    public function compileFind(array $find): array
    {
        /** @var list<string> $conditions */
        $conditions = [];
        /** @var list<array<array-key, mixed>> $subFinds */
        $subFinds = [];
        
        // empty find definition, nothing to do
        if ($find === []) {
            return [];
        }
        
        $this->beforeCompileFind($find);
        
        /**
         * Phalcon positional signature:
         *  [0 => 'cond', 1 => bind[], 2 => bindTypes[]]
         */
        if (isset($find[0]) && is_string($find[0])) {
            $conditions[] = $find[0];
            unset($find[0]);
            
            if (isset($find[1])) {
                if (!is_array($find[1])) {
                    throw new LogicException('Invalid bind value.');
                }
                
                if (isset($find['bind']) && !is_array($find['bind'])) {
                    throw new LogicException('Invalid existing bind value: expected array.');
                }
                
                $find['bind'] = array_merge($find['bind'] ?? [], $find[1]);
                unset($find[1]);
            }
            
            if (isset($find[2])) {
                if (!is_array($find[2])) {
                    throw new LogicException('Invalid bind type.');
                }
                
                if (isset($find['bindTypes']) && !is_array($find['bindTypes'])) {
                    throw new LogicException('Invalid existing bindTypes value: expected array.');
                }
                
                $find['bindTypes'] = array_merge($find['bindTypes'] ?? [], $find[2]);
                unset($find[2]);
            }
        }
        
        // Promote scalar conditions string into the local conditions list
        if (isset($find['conditions']) && is_string($find['conditions'])) {
            $conditions[] = $find['conditions'];
            unset($find['conditions']);
        }
        
        /**
         * conditions list path:
         * - merge literal strings into $conditions
         * - compile nested blocks (recursive) into $subFinds
         * - keep semantics: empty/null blocks are removed
         */
        if (isset($find['conditions']) && is_array($find['conditions'])) {
            foreach ($find['conditions'] as $key => &$condition) {
                // Drop null/empty-array blocks
                if ($condition === null || $condition === []) {
                    unset($find['conditions'][$key]);
                    continue;
                }
                
                // Drop empty strings (but keep non-empty strings)
                if (is_string($condition)) {
                    if (trim($condition) === '') {
                        unset($find['conditions'][$key]);
                        continue;
                    }
                    
                    $conditions[] = $condition;
                    unset($find['conditions'][$key]);
                    continue;
                }
                
                /**
                 * Sub-find encoded as positional array:
                 *   [0 => 'cond', 1 => bind[], 2 => bindTypes[]]
                 */
                if (is_array($condition) && isset($condition[0]) && is_string($condition[0])) {
                    $conditions[] = $condition[0];
                    unset($condition[0]);
                }
                
                /**
                 * Sub-find encoded as associative:
                 *   ['conditions' => '...'] or ['conditions' => [...]]
                 */
                if (is_array($condition) && array_key_exists('conditions', $condition)) {
                    if (is_string($condition['conditions'])) {
                        if (trim($condition['conditions']) !== '') {
                            $conditions[] = $condition['conditions'];
                        }
                        unset($condition['conditions']);
                    }
                    
                    if (is_array($condition['conditions'] ?? null)) {
                        $subFinds[] = $this->compileFind($condition);
                    }
                    
                    continue;
                }
                
                /**
                 * Generic nested object:
                 * - For native Phalcon keys: compile together as a find
                 * - For custom keys: compile each nested array independently
                 */
                if (is_array($condition) && $condition !== []) {
                    foreach (array_keys($condition) as $subKey) {
                        $subValue = $condition[$subKey];
                        $toCompile = [];
                        
                        // Native keys from Phalcon, compiled together
                        if (
                            is_int($subKey)
                            || in_array($subKey, [
                                'conditions',
                                'bind',
                                'bindTypes',
                                'model',
                                'columns',
                                'joins',
                                'group',
                                'order',
                                'distinct',
                                'column',
                            ], true)
                        ) {
                            $toCompile[$subKey] = $subValue;
                        }
                        // Custom keys: one compilation per custom key (only if value is a non-empty array)
                        elseif (is_array($subValue) && $subValue !== []) {
                            $subFinds[] = $this->compileFind($subValue);
                        }
                        
                        if ($toCompile !== []) {
                            $subFinds[] = $this->compileFind($toCompile);
                        }
                        
                        unset($condition[$subKey]);
                    }
                }
            }
        }
        
        // Final form for this layer (conditions preserved as list; stringification happens after merge)
        $find['conditions'] = $conditions;
        
        $this->afterCompileFind($find);
        
        return $this->mergeCompiledFind($find, ...$subFinds);
    }
    
    /**
     * Hook: called before the find is compiled (in-place normalization).
     *
     * Current behavior (preserved):
     * - Promote group/order/columns string into a single-item list (non-empty only).
     *
     * @param array<array-key, mixed> $find
     */
    public function beforeCompileFind(array &$find): void
    {
        foreach (['group', 'order', 'columns'] as $key) {
            if (isset($find[$key]) && is_string($find[$key]) && trim($find[$key]) !== '') {
                $find[$key] = [$find[$key]];
            }
        }
    }
    
    /**
     * Hook: called after the find is compiled (in-place cleanup and coercions).
     *
     * Current behavior (preserved):
     * - Remove null values, empty arrays, and empty strings
     * - Enforce integer coercion for limit/offset when present
     * - Stringify group/order/distinct arrays into unique CSV strings (via implodeUniqueToString)
     *
     * @param array<array-key, mixed> $find
     */
    public function afterCompileFind(array &$find): void
    {
        // Remove empty values (null, empty array, empty string)
        foreach ($find as $key => $value) {
            if ($value === null) {
                unset($find[$key]);
                continue;
            }
            
            if (is_array($value) && $value === []) {
                unset($find[$key]);
                continue;
            }
            
            if ($value === '') {
                unset($find[$key]);
                continue;
            }
        }
        
        // Enforce integer keys
        foreach (['limit', 'offset'] as $key) {
            if (isset($find[$key]) && !is_int($find[$key])) {
                $find[$key] = (int) $find[$key];
            }
        }
        
        // Enforce string keys
        $this->implodeUniqueToString($find);
    }
    
    /**
     * Convert a list-like key into a string (unique CSV), in-place.
     *
     * Keys are expected to be:
     * - unset OR scalar string OR list of scalar strings
     *
     * @param array<array-key, mixed> $array
     * @param list<string> $keys
     */
    private function implodeUniqueToString(array &$array, array $keys = ['group', 'order', 'distinct']): void
    {
        foreach ($keys as $key) {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                continue;
            }
            
            /** @var array<int, mixed> $values */
            $values = $array[$key];
            
            $values = array_map(
                static fn(mixed $v): string => is_string($v) ? trim($v) : trim((string) $v),
                $values
            );
            $values = array_filter($values, static fn(string $v): bool => $v !== '');
            $values = array_values(array_unique($values));
            
            $array[$key] = implode(', ', $values);
        }
    }
    
    /**
     * Normalize a value that is allowed to be either:
     * - string (treated as single-item list)
     * - array  (treated as list as-is)
     * - null   (treated as empty list)
     *
     * Anything else is a hard error because merge semantics depend on predictable list types.
     *
     * @return list<mixed>
     */
    private function normalizeListValue(mixed $value, string $keyName): array
    {
        if ($value === null) {
            return [];
        }
        
        if (is_string($value)) {
            return [$value];
        }
        
        if (is_array($value)) {
            /**
             * Keep as-is:
             * - We intentionally do not force list normalization here because callers may rely on
             *   associative arrays for some keys (joins sometimes carry structured arrays).
             * - Merge semantics are still correct because we only append (array_merge) at the top level.
             */
            return array_values($value) === $value ? $value : [$value];
        }
        
        throw new LogicException(sprintf('Invalid %s value: expected array|string|null.', $keyName));
    }
}
