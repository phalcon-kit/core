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

namespace PhalconKit\Mvc\Controller\Traits\Query\Conditions;

trait ExistentialConditions
{
    /* ==========================================================
     * Existential accumulation (AND-coalescing)
     * ======================================================== */

    /**
     * Small immutable key describing an existential “universe” we can safely coalesce.
     *
     * We coalesce ONLY when:
     *  - Same group level
     *  - Same relationship path (same $originalField relationship chain)
     *  - Same polarity (EXISTS vs NOT EXISTS)
     *  - AND-connected siblings only
     *
     * We do NOT attempt to coalesce across OR boundaries or across nested groups.
     */
    protected function getExistentialBucketKey(
        string $originalField,
        bool $negated,
        string $scope
    ): string {
        // Strip alias tokens: RecordUserStatus[a] → RecordUserStatus
        $field = preg_replace('/\[[^\]]+\]/', '', $originalField);

        // Strip leaf column: RecordUserStatus.userId → RecordUserStatus
        if (str_contains($field, '.')) {
            $field = substr($field, 0, strrpos($field, '.'));
        }

        // The bucket key represents the existential universe
        return $scope . '|' . $field . '|' . ($negated ? 'not' : 'pos');
    }

    /**
     * Accumulate a single existential predicate for AND-coalescing.
     *
     * IMPORTANT INVARIANTS (guaranteed by caller):
     *  - This method is ONLY called for AND-connected siblings
     *  - OR / XOR predicates are emitted immediately elsewhere
     *  - All predicates in a bucket share:
     *      - the same relationship universe
     *      - the same polarity (EXISTS vs NOT EXISTS)
     *
     * This method MUST:
     *  - only accumulate
     *  - never emit SQL
     *  - never merge binds globally
     *
     * @param array  $pending
     * @param string $bucketKey
     * @param string $originalField
     * @param bool   $negated
     * @param string $compiledConditionSql  SQL fragment inside EXISTS
     * @param array  $bind
     * @param array  $bindTypes
     */
    protected function pushExistentialCondition(
        array &$pending,
        string $bucketKey,
        string $originalField,
        bool $negated,
        string $compiledConditionSql,
        array $bind,
        array $bindTypes
    ): void {
        if (!isset($pending[$bucketKey])) {
            $pending[$bucketKey] = [
                // Relationship universe anchor
                'field'      => $originalField,

                // EXISTS vs NOT EXISTS
                'negated'    => $negated,

                // Only AND buckets are allowed
                'logic'      => 'and',

                // Accumulated inner predicates
                'conditions' => [],

                // Accumulated binds for this EXISTS
                'bind'       => [],
                'bindTypes'  => [],
            ];
        }

        // Append predicate SQL
        $pending[$bucketKey]['conditions'][] = '(' . $compiledConditionSql . ')';

        // Merge predicate-local binds into bucket
        if ($bind !== []) {
            if (array_intersect_key($pending[$bucketKey]['bind'], $bind) !== []) {
                throw new \LogicException(
                    'Bind collision detected inside existential bucket.'
                );
            }
            $pending[$bucketKey]['bind'] += $bind;
        }

        if ($bindTypes !== []) {
            if (array_intersect_key($pending[$bucketKey]['bindTypes'], $bindTypes) !== []) {
                throw new \LogicException(
                    'BindType collision detected inside existential bucket.'
                );
            }
            $pending[$bucketKey]['bindTypes'] += $bindTypes;
        }
    }

    /**
     * Flush all accumulated existential buckets into SQL fragments.
     *
     * GUARANTEED PRECONDITIONS:
     *  - Each bucket represents exactly ONE existential universe
     *  - Buckets contain ONLY AND-connected predicates
     *  - OR / XOR predicates have already been emitted
     *
     * Therefore:
     *  - Each bucket emits EXACTLY ONE EXISTS / NOT EXISTS
     *  - All bucket binds are merged exactly once
     *
     * @param array $pending
     * @param array $fragments
     * @param array $bind
     * @param array $bindTypes
     */
    protected function flushExistentialBuckets(
        array &$pending,
        array &$fragments,
        array &$bind,
        array &$bindTypes
    ): void {
        if ($pending === []) {
            return;
        }

        foreach ($pending as $bucket) {
            if (
                empty($bucket['field']) ||
                empty($bucket['conditions']) ||
                !is_array($bucket['conditions'])
            ) {
                continue;
            }

            if (($bucket['logic'] ?? 'and') !== 'and') {
                throw new \LogicException(
                    'Existential bucket invariant violated: non-AND bucket encountered.'
                );
            }

            // Combine inner predicates with AND
            $innerSql = implode(' AND ', $bucket['conditions']);

            // Build EXISTS / NOT EXISTS
            $exists = $this->buildExistsConditionFromField(
                $bucket['field'],
                $innerSql,
                (bool)$bucket['negated']
            );

            if (empty($exists['conditions'])) {
                continue;
            }

            // Emit SQL fragment
            $fragments[] = 'and ' . $exists['conditions'];

            // Merge bucket binds
            if (!empty($bucket['bind'])) {
                if (array_intersect_key($bind, $bucket['bind']) !== []) {
                    throw new \LogicException(
                        'Bind collision detected while flushing existential bucket.'
                    );
                }
                $bind += $bucket['bind'];
            }

            if (!empty($bucket['bindTypes'])) {
                if (array_intersect_key($bindTypes, $bucket['bindTypes']) !== []) {
                    throw new \LogicException(
                        'BindType collision detected while flushing existential bucket.'
                    );
                }
                $bindTypes += $bucket['bindTypes'];
            }

            // Merge EXISTS join binds
            if (!empty($exists['bind'])) {
                if (array_intersect_key($bind, $exists['bind']) !== []) {
                    throw new \LogicException(
                        'Bind collision detected while merging EXISTS join binds.'
                    );
                }
                $bind += $exists['bind'];
            }

            if (!empty($exists['bindTypes'])) {
                if (array_intersect_key($bindTypes, $exists['bindTypes']) !== []) {
                    throw new \LogicException(
                        'BindType collision detected while merging EXISTS join bindTypes.'
                    );
                }
                $bindTypes += $exists['bindTypes'];
            }
        }

        // Hard boundary: buckets must never leak
        $pending = [];
    }
}
