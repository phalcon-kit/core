<?php

declare(strict_types=1);

namespace PhalconKit\Mvc\Controller\Traits\Query;

use LogicException;
use Phalcon\Support\Collection;

trait Compiler
{
    public function prepareCollectionToCompile(Collection $collection, int $level = 0): array
    {
        $result = [];
        foreach ($collection->getIterator() as $key => $value) {
            if ($value instanceof Collection) {
                $value = $this->prepareCollectionToCompile($value, $level + 1);
            }
            if (isset($value)) {
                $result[$key] = $value;
            }
        }
        return $level === 1? array_values($result) : $result;
    }

//    private function normalizeArrayShape(array $a): array
//    {
//        return array_keys($a) === range(0, count($a) - 1)
//            ? array_values($a)
//            : $a;
//    }

    public function compileFinds(array ...$finds): array
    {
        $compiled = [];
        foreach ($finds as $find) {
            $compiled [] = $this->compileFind($find);
        }
        return $this->mergeCompiledFind(...$compiled);
    }

    public function mergeCompiledFind(array ...$compiledFinds): array
    {
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
            if (isset($f['conditions'])) {
                $out['conditions'] = array_merge($out['conditions'] ?? [], (array) $f['conditions']);
            }

            // joins: append (dedupe optional, depends on your join normalizer)
            if (isset($f['joins']) && is_array($f['joins'])) {
                $out['joins'] = array_merge($out['joins'] ?? [], $f['joins']);
            }

            // columns/distinct/group/order: list merge
            foreach (['columns', 'distinct', 'group', 'order', 'models'] as $k) {
                if (isset($f[$k])) {
                    $out[$k] = array_merge($out[$k] ?? [], (array) $f[$k]);
                }
            }

            // bind / bindTypes: forbid key collisions (or allow if identical)
            foreach (['bind', 'bindTypes'] as $k) {
                if (!isset($f[$k]) || !is_array($f[$k])) {
                    continue;
                }

                $out[$k] ??= [];

                $collisions = array_intersect_key($out[$k], $f[$k]);
                if (!empty($collisions)) {
                    // allow collision only if values identical
                    foreach (array_keys($collisions) as $ck) {
                        if ($out[$k][$ck] !== $f[$k][$ck]) {
                            throw new LogicException(sprintf('Cannot merge find definitions: %s key collision on "%s".', $k, (string) $ck));
                        }
                    }
                }

                // safe merge
                foreach ($f[$k] as $bk => $bv) {
                    $out[$k][$bk] = $bv;
                }
            }

            // Boolean flags (true wins)
            foreach (['for_update', 'shared_lock'] as $k) {
                if (isset($f[$k])) {
                    $out[$k] = ($out[$k] ?? false) || (bool) $f[$k];
                }
            }

            // cache and other keys: last-wins (or expand as needed)
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
                    'models'
                ], true)) {
                    continue;
                }
                // unsupported keys are not allowed to be merged
                if (isset($out[$k])) {
                    throw new LogicException(sprintf('Cannot merge find definitions: key collision on "%s".', $k));
                }
                $out[$k] = $v;
            }
        }

        $this->afterMergeCompileFind($out);

        return $out;
    }

    public function afterMergeCompileFind(array &$merged): void
    {
        if (!empty($merged['conditions']) && is_array($merged['conditions'])) {
            $merged['conditions'] = array_unique(array_filter(array_map('trim', $merged['conditions'])));
            $merged['conditions'] = '(' . implode(') AND (', $merged['conditions']) . ')';
        }

        foreach (array_keys($merged) as $k) {
            if (is_int($k) || ctype_digit((string)$k)) {
                throw new LogicException('Cannot merge find definitions: integer keys are not allowed.');
            }
        }

        $this->implodeUniqueToString($merged);
    }

    public function compileFind(array $find): array
    {
        $conditions = [];
        $subFinds = [];

        // empty find definition, nothing to do
        if ($find === []) {
            return [];
        }

        $this->beforeCompileFind($find);

        // path for [0 => '', 1 => [], 2 => []] (conditions, bind, bindTypes)
        if (isset($find[0]) && is_string($find[0])) {
            $conditions [] = $find[0];
            unset($find[0]);

            if (isset($find[1])) {
                if (is_array($find[1])) {
                    $find['bind'] = array_merge($find['bind'] ?? [], $find[1]);
                    unset($find[1]);
                } else {
                    throw new LogicException('Invalid bind value.');
                }
            }

            if (isset($find[2])) {
                if (is_array($find[2])) {
                    $find['bindTypes'] = array_merge($find['bindTypes'] ?? [], $find[2]);
                    unset($find[2]);
                } else {
                    throw new LogicException('Invalid bind type.');
                }
            }
        }

        if (isset($find['conditions']) && is_string($find['conditions'])) {
            $conditions[] = $find['conditions'];
            unset($find['conditions']);
        }

        // path for [conditions => []] (merge into root find definition)
        if (isset($find['conditions']) && is_array($find['conditions'])) {
            foreach ($find['conditions'] as $key => &$condition) {

                // skip empty array or null conditions
                if ($condition === [] || $condition === null) {
                    unset($find['conditions'][$key]);
                    continue;
                }

                // skip empty string conditions
                else if (is_string($condition) && trim($condition) === '') {
                    continue;
                }

                // path for [conditions => '']
                else if (is_string($condition)) {
                    $conditions []= $condition;
                    unset($find['conditions'][$key]);
                    continue;
                }

                // move conditions to root find layer (merge)
                else if (isset($condition[0]) && is_string($condition[0])) {
                    $conditions []= $condition[0];
                    unset($condition[0]);
                }

                // move conditions to root find layer (merge)
                else if (isset($condition['conditions'])) {
                    // path for [conditions => '']
                    if (is_string($condition['conditions'])) {
                        $conditions []= $condition['conditions'];
                        unset($condition['conditions']);
                    }
                    // path for [conditions => [0, 1, 2]] and for [conditions => ['conditions' => [...]]] (nested)
                    if (is_array($condition['conditions'])) {
                        $subFinds []= $this->compileFind($condition);
                    }
                }

                // sub-compilation & merge with current
                else if (is_array($condition) && count($condition)) {

                    // move properties to root find layer (merge)
                    foreach (array_keys($condition) as $subKey) {
                        $subValue = $condition[$subKey];
                        $toCompile = [];

                        // native keys from phalcon, can be compiled all together
                        if (is_int($subKey) || in_array($subKey, [
                            'conditions',
                            'bind',
                            'bindTypes',
                            'model',
                            'columns',
                            'joins',
                            'group',
                            'order',
                            'distinct',
                            'column'
                        ], true)) {
                            $toCompile[$subKey] = $subValue;
                        }

                        // custom keys, one compilation per custom key
                        else if (is_string($subKey) && is_array($subValue) && count($subValue)) {
                            $subFinds []= $this->compileFind($subValue);
                        }

                        // compile native phalcon keys
                        if (count($toCompile)) {
                            $subFinds []= $this->compileFind($toCompile);
                        }

                        // always unset after compilation
                        unset($condition[$subKey]);
                    }
                }
            }
        }

        // final form
        $find['conditions'] = $conditions;
        $this->afterCompileFind($find);

        // return final find definition
        return $this->mergeCompiledFind($find, ...$subFinds);
    }

    public function beforeCompileFind(array &$find)
    {
        $promoteToArrayKeys = ['group', 'order', 'columns'];
        foreach ($promoteToArrayKeys as $key) {
            if (isset($find[$key]) && is_string($find[$key])) {
                if (trim($find[$key]) !== '') {
                    $find[$key] = [$find[$key]];
                }
            }
        }
    }

    public function afterCompileFind(array &$find)
    {
        // remove empty values
        foreach ($find as $key => $value) {
            // unset null values
            if (!isset($value)) {
                unset($find[$key]);
            }
            // unset empty arrays
            if (is_array($value) && empty($value)) {
                unset($find[$key]);
            }
            // unset empty strings
            if ($value === '') {
                unset($find[$key]);
            }
        }

        // enforce conditions as a string (this is done after merge)
//        if (!empty($find['conditions']) && is_array($find['conditions'])) {
//            $find['conditions'] = array_unique(array_filter(array_map('trim', $find['conditions'])));
//            if (!empty($find['conditions'])) {
//                $find['conditions'] = '(' . implode(') AND (', $find['conditions']) . ')';
//            }
//        }

        // enforce integer keys
        $enforceIntegerKeys = ['limit', 'offset'];
        foreach ($enforceIntegerKeys as $key) {
            if (isset($find[$key]) && !is_int($find[$key])) {
                $find[$key] = (int) $find[$key];
            }
        }

        // enforce string keys
        $this->implodeUniqueToString($find);
    }

    private function implodeUniqueToString(array &$array, array $keys = ['group', 'order', 'distinct']): void
    {
        foreach ($keys as $key) {
            if (isset($array[$key]) && is_array($array[$key])) {
                $array[$key] = implode(', ', array_unique($array[$key]));
            }
        }
    }
}
