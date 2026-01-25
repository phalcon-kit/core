<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed joins this source code.
 */

namespace PhalconKit\Mvc\Controller\Traits\Query;

use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractJoins;

trait Joins
{
    use AbstractJoins;
    
    protected ?Collection $joins = null;
    
    /**
     * Initializes the joins.
     *
     * This method is responsible for initializing the joins.
     *
     * @return void
     */
    public function initializeJoins(): void
    {
        $this->setJoins(null);
    }
    
    /**
     * Sets the joins for the find criteria.
     *
     * @param Collection|null $joins The collection of joins.
     *                               Pass null to disable joins.
     */
    public function setJoins(?Collection $joins): void
    {
        $this->joins = $joins;
    }
    
    /**
     * Returns the joins collection.
     *
     * This method retrieves the joins for the find criteria.
     * If joins fields have been set, it returns the collection of joins.
     * If no joins have been set, it returns null.
     *
     * Note: The joins are used to add conditions during the find query and are not added to the result.
     *
     * @return Collection|null The collection of joins or null everything is allowed.
     */
    public function getJoins(): ?Collection
    {
        return $this->joins;
    }

    /**
     * Normalize join definitions into pure Phalcon joins and extract join-scoped bind data.
     *
     * Supported join shapes (new format only):
     *
     *  - [class, on, alias]
     *  - [class, on, alias, type]
     *  - [class, on, alias, payload]                      // type omitted
     *  - [class, on, alias, type, payload]
     *
     * Where payload is either a single block:
     *  - ['conditions' => 'x = :x:', 'bind' => [...], 'bindTypes' => [...]]
     *
     * Or a list of blocks:
     *  - [
     *      ['conditions' => 'a = :a:', 'bind' => [...], 'bindTypes' => [...]],
     *      ['conditions' => 'b = :b:', 'bind' => [...], 'bindTypes' => [...]],
     *    ]
     *
     * Return:
     *  - joins:     list of Phalcon joins [class, onSql, alias, type?] (payload removed, ON merged)
     *  - bind:      merged bind map from payload blocks
     *  - bindTypes: merged bindTypes map from payload blocks
     *
     * No mutation. No references.
     *
     * @param array $joins
     * @return array{joins: array<int, array>, bind: array, bindTypes: array}
     */
    protected function normalizeJoins(array $joins): array
    {
        $outJoins = [];
        $outBind = [];
        $outBindTypes = [];

        foreach ($joins as $idx => $join) {
            if (!is_array($join) || !isset($join[0], $join[1], $join[2])) {
                throw new \LogicException(sprintf('Invalid join definition at index %d.', (int)$idx));
            }

            $model = $join[0];
            $on    = $join[1];
            $alias = $join[2];

            if (!is_string($on)) {
                throw new \LogicException(sprintf('Join ON must be a SQL string at index %d.', (int)$idx));
            }

            // Detect whether [3] is type or payload.
            // Rule:
            // - If [4] exists => [3]=type (string-ish), [4]=payload
            // - Else if [3] is array => payload (type omitted)
            // - Else if [3] is string => type (no payload)
            $type = null;
            $payload = null;

            if (array_key_exists(4, $join)) {
                $type = $join[3] ?? null;
                $payload = $join[4];
            } elseif (array_key_exists(3, $join)) {
                if (is_array($join[3])) {
                    $payload = $join[3];
                } else {
                    $type = $join[3];
                }
            }

            // Default join type normalization (keep your convention: "left" etc.)
            if ($type !== null && !is_string($type)) {
                throw new \LogicException(sprintf('Join type must be a string at index %d.', (int)$idx));
            }

            // Merge payload into ON and collect bind data
            if ($payload !== null) {
                [$payloadSql, $payloadBind, $payloadBindTypes] = $this->normalizeJoinPayload($payload, $idx);

                if ($payloadSql !== '') {
                    $on = $this->mergeSqlConditions($on, $payloadSql);
                }

                if ($payloadBind) {
                    $outBind = array_merge($outBind, $payloadBind);
                }

                if ($payloadBindTypes) {
                    $outBindTypes = array_merge($outBindTypes, $payloadBindTypes);
                }
            }

            // Emit pure Phalcon join (payload removed)
            $phalconJoin = [$model, $on, $alias];
            if ($type !== null && $type !== '') {
                $phalconJoin[] = $type;
            }

            $outJoins[] = $phalconJoin;
        }

        return [
            'joins' => $outJoins,
            'bind' => $outBind,
            'bindTypes' => $outBindTypes,
        ];
    }

    /**
     * Normalize a join payload into:
     *  - merged SQL condition (AND-combined, parenthesized)
     *  - merged bind
     *  - merged bindTypes
     *
     * Supported block variants (all equivalent):
     *
     *  [
     *    'conditions' => 'a = :a:',
     *    'bind' => [...],
     *    'bindTypes' => [...],
     *  ]
     *
     *  [
     *    0 => 'a = :a:',
     *    1 => [...],
     *    2 => [...],
     *  ]
     *
     *  [
     *    0 => 'a = :a:',
     *    'bind' => [...],
     *    'bindTypes' => [...],
     *  ]
     *
     * Multiple blocks:
     *  [
     *    [...],
     *    [...],
     *  ]
     *
     * Rule:
     *  - Payload is a LIST OF BLOCKS iff payload[0] is an array.
     *  - Otherwise payload is a SINGLE BLOCK.
     */
    protected function normalizeJoinPayload(array $payload, int $joinIndex): array
    {
        // Single block vs multi-block detection (structural, unambiguous)
        $blocks = (isset($payload[0]) && is_array($payload[0]))
            ? $payload
            : [$payload];

        $sqlParts  = [];
        $bind      = [];
        $bindTypes = [];

        foreach ($blocks as $blockIndex => $block) {
            if (!is_array($block)) {
                throw new \LogicException(sprintf(
                    'Invalid join payload block at join index %d, block %d.',
                    $joinIndex,
                    $blockIndex
                ));
            }

            /* ============================
             * SQL condition
             * ========================== */
            $condition = $block['conditions'] ?? ($block[0] ?? '');

            if ($condition !== '' && !is_string($condition)) {
                throw new \LogicException(sprintf(
                    'Join payload condition must be a string at join index %d, block %d.',
                    $joinIndex,
                    $blockIndex
                ));
            }

            $condition = trim((string)$condition);
            if ($condition !== '') {
                $sqlParts[] = $condition;
            }

            /* ============================
             * bind (named wins over positional)
             * ========================== */
            $b = $block['bind'] ?? ($block[1] ?? null);
            if ($b !== null) {
                if (!is_array($b)) {
                    throw new \LogicException(sprintf(
                        'Join payload bind must be an array at join index %d, block %d.',
                        $joinIndex,
                        $blockIndex
                    ));
                }
                $bind = array_merge($bind, $b);
            }

            /* ============================
             * bindTypes (named wins over positional)
             * ========================== */
            $bt = $block['bindTypes'] ?? ($block[2] ?? null);
            if ($bt !== null) {
                if (!is_array($bt)) {
                    throw new \LogicException(sprintf(
                        'Join payload bindTypes must be an array at join index %d, block %d.',
                        $joinIndex,
                        $blockIndex
                    ));
                }
                $bindTypes = array_merge($bindTypes, $bt);
            }
        }

        $sql = $sqlParts
            ? '(' . implode(') AND (', $sqlParts) . ')'
            : '';

        return [$sql, $bind, $bindTypes];
    }

    /**
     * Merge two SQL condition fragments using AND with safe parentheses.
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    protected function mergeSqlConditions(string $a, string $b): string
    {
        $a = trim($a);
        $b = trim($b);

        if ($a === '') {
            return $b;
        }
        if ($b === '') {
            return $a;
        }

        return '(' . $a . ') AND (' . $b . ')';
    }

}
