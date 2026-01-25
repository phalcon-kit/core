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

use Phalcon\Db\Column;
use Phalcon\Filter\Exception;
use Phalcon\Filter\Filter;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractFilterFields;
use PhalconKit\Support\Helper\Arr\FlattenKeys;

trait FilterConditions
{
    use FilterSemantics;
    use AbstractInjectable;
    use AbstractModel;
    use AbstractParams;
    use AbstractFilterFields;

    protected ?Collection $filterConditions = null;

    /**
     * Initializes the filter conditions for the current instance.
     * This method sets up the default filter conditions using a predefined collection
     * and ensures they are properly configured for subsequent operations.
     *
     * @return void This method does not return any value.
     * @throws \Exception
     */
    public function initializeFilterConditions(): void
    {
        $this->setFilterConditions(new Collection([
            'default' => $this->defaultFilterCondition(),
        ], false));
    }

    /**
     * Sets the filter conditions used for configuring specific query or data processing criteria.
     *
     * @param Collection|null $filterConditions A collection of filter conditions to be applied.
     *                                           Can be null to clear existing conditions.
     * @return void
     */
    public function setFilterConditions(?Collection $filterConditions): void
    {
        $this->filterConditions = $filterConditions;
    }

    /**
     * Retrieves the collection of filter conditions applied to the current context.
     * If no filter conditions are set, this method returns null.
     *
     * @return Collection|null The collection of filter conditions, or null if no conditions are set.
     */
    public function getFilterConditions(): ?Collection
    {
        return $this->filterConditions;
    }

    /**
     * Constructs a SQL filter condition based on the provided filters and allowed fields.
     * Supports nested group filters, validation of fields and operators, and handles
     * both normal and foreign filters, including subqueries and negative operators.
     *
     *  Responsibilities (and ONLY these):
     *   - Retrieve and validate raw filters
     *   - Prepare allowed filter fields
     *   - Delegate boolean semantics entirely to compileGroup()
     *   - Return legacy-shaped output
     *
     *  Explicitly NOT responsible for:
     *   - Boolean glue decisions
     *   - Prefix vs infix logic
     *   - Group normalization
     *   - Operator semantics
     *
     * @param array|null $filters An optional array of filter conditions to apply.
     *                             Each filter should include keys like 'field', 'operator',
     *                             and optionally 'value' or 'subquery'. Nested groups can
     *                             also be specified.
     * @param array|null $allowedFilters An optional array of allowed filter fields
     *                                    for validation. If not provided, defaults to
     *                                    the fields obtained from the model's configuration.
     * @param bool $or A flag indicating whether the filters should be combined using OR
     *                 (true) or AND (false) logic. Defaults to false.
     * @return array|string|null The constructed SQL filter condition. Returns:
     *                           - An array containing the SQL string, binding values, and binding types.
     *                           - A string representation of the SQL condition if no bindings are necessary.
     *                           - Null if no valid filters are provided.
     * @throws \Exception If a required property like 'field' or 'operator' is missing,
     *                    or if an unauthorized filter field or unsupported operator is used.
     */
    public function defaultFilterCondition(
        ?array $filters = null,
        ?array $allowedFilters = null,
        bool   $or = false,
        int    $level = 0
    ): array|string|null
    {
        // Retrieve filters from request if not provided explicitly
        $filters ??= $this->getParam('filters');

        if (empty($filters)) {
            return null;
        }

        /*
         * Prepare allowed filter fields once.
         * Flattening preserves legacy support for:
         *   - nested definitions
         *   - join-based filters
         */
        $allowedFilters ??= $this->getFilterFields()?->toArray();
        $allowedFilters = FlattenKeys::process($allowedFilters ?? []);

        /*
         * Compile entire filter tree in one pass.
         * All boolean semantics are handled recursively inside compileGroup().
         */
        $compiled = $this->compileGroup($filters, $or, $level, $allowedFilters);

        if ($compiled === null || $compiled['sql'] === '') {
            return null;
        }

        /*
         * Legacy return shape:
         *  [
         *    0 => SQL string,
         *    1 => bind array,
         *    2 => bindTypes array
         *  ]
         *
         * IMPORTANT:
         *  - SQL is already fully normalized
         *  - No additional wrapping or logic stripping must occur here
         */
        return [
            $compiled['sql'],
            $compiled['bind'],
            $compiled['bindTypes'],
        ];
    }

    /**
     * Compile a group of filters into legacy-compatible SQL.
     *
     * Core invariants restored:
     *  - Prefix boolean logic ("and <expr>")
     *  - First-element logic is computed and preserved
     *  - Group nesting toggles default logic
     *  - Explicit "logic" may appear on fields OR groups
     *  - Regex-based normalization at group exit
     *
     * @param array $filters Group payload
     * @param bool $or Current alternation mode (flipped per nesting)
     * @param int $level Recursion depth (0 = root)
     * @param array $allowedFilters Allowed filter fields
     *
     * @return array|null ['sql'=>string,'bind'=>array,'bindTypes'=>array]
     *
     * @throws \Exception
     */
    protected function compileGroup(array $filters, bool $or, int $level, array $allowedFilters): ?array
    {
        $fragments = [];   // each fragment is a string, usually starting with "and|or|xor"
        $bind = [];
        $bindTypes = [];

        foreach ($filters as $index => $filter) {

            /* ==========================================================
             * GROUP NODE (nested array)
             * ======================================================== */
            if (is_array($filter) && isset($filter[0]) && is_array($filter[0])) {

                // Compile nested group with flipped alternation
                $nested = $this->compileGroup($filter, !$or, $level + 1, $allowedFilters);

                if ($nested !== null && $nested['sql'] !== '') {
                    $fragments[] = $nested['sql'];
                    $bind += $nested['bind'];
                    $bindTypes += $nested['bindTypes'];
                }
                continue;
            }

            /* ==========================================================
             * FIELD NODE (validation)
             * ======================================================== */
            if (empty($filter['field'])) {
                throw new \Exception('A valid filter field property is required.', 400);
            }
            if (empty($filter['operator'])) {
                throw new \Exception('A valid filter operator property is required.', 400);
            }

            /* ==========================================================
             * Resolve boolean prefix (LEGACY)
             * ======================================================== */
            $logic = $this->resolveFilterLogicToken($filter, $index, $or);

            /* ==========================================================
             * Field / operator resolution (unchanged modern logic)
             * ======================================================== */
            $rawField = $this->filter->sanitize(
                $filter['field'],
                [Filter::FILTER_STRING, Filter::FILTER_TRIM]
            );

            if (!$this->isFilterAllowed($rawField, $allowedFilters)) {
                throw new \Exception(sprintf('Unauthorized filter field "%s".', $rawField), 403);
            }

            $operator = $this->normalizeFilterOperator((string)$filter['operator']);
            if ($operator === '') {
                throw new \Exception(sprintf('Unsupported filter operator "%s".', $filter['operator']), 403);
            }

            /* ==========================================================
             * "IS *" contract enforcement (legacy-compatible)
             *
             * Important nuance:
             *  - Legacy frontends sometimes send "" or null; these are tolerated.
             *  - Any non-empty value is rejected (strict).
             * ======================================================== */
            if (str_starts_with($operator, 'is')) {
                if (array_key_exists('value', $filter) && $filter['value'] !== '' && $filter['value'] !== null) {
                    throw new \Exception(sprintf('Operator "%s" does not accept a value.', $operator), 403);
                }
                unset($filter['value']);
            }

            /* ==========================================================
             * Operator/value optimization (safe)
             *
             * Safe semantic rewrites (e.g. contains + int → =)
             * Must happen BEFORE:
             *  - isNegative detection
             *  - isNoValueOperator checks
             *  - subquery / join strategy decisions
             * ======================================================== */
            if (array_key_exists('value', $filter)) {
                [$operator, $filter['value']] =
                    $this->optimizeOperatorAndValue($operator, $filter['value']);
            }

            /* ==========================================================
             * Field binder resolution
             * ======================================================== */
            [$originalField, , $fieldName, $joinAlias] = $this->splitField($rawField);
            $fieldBinder = $this->appendModelName($fieldName, $joinAlias);

            /* ==========================================================
             * Bind factory
             * ======================================================== */
            $filterId = $this->security->getRandom()->hex(8);
            $makeBind = static function (string $suffix) use ($filterId): string {
                return '_' . uniqid($filterId . '_' . $suffix . '_') . '_';
            };

            /* ==========================================================
             * NO-VALUE operators
             *
             * Do NOT route these through compileSingleFilterCondition unless that
             * method explicitly supports missing "value".
             * ======================================================== */
            if ($this->isNoValueOperator($operator)) {
                $semanticMap = [
                    // Empty semantics
                    'is empty' => "(TRIM({$fieldBinder}) = '' or {$fieldBinder} is null)",
                    'is not empty' => "not (TRIM({$fieldBinder}) = '' or {$fieldBinder} is null)",

                    // Null semantics
                    'is null' => "{$fieldBinder} is null",
                    'is not null' => "{$fieldBinder} is not null",

                    // Boolean semantics (PHQL-safe)
                    'is true' => "{$fieldBinder} = 1",
                    'is false' => "{$fieldBinder} = 0",
                    'is not true' => "{$fieldBinder} != 1",
                    'is not false' => "{$fieldBinder} != 0",
                ];

                if (!isset($semanticMap[$operator])) {
                    throw new \LogicException("Unhandled no-value operator: {$operator}");
                }

                $fragments[] = $logic . ' ' . $semanticMap[$operator];
                continue;
            }

            /* ==========================================================
             * Semantic classification (used ONLY for rewrite decisions)
             *
             * Only used to decide rewrite strategy (inline vs NOT EXISTS).
             * It must NOT influence boolean glue.
             * ======================================================== */
            $isForeign = str_contains($rawField, '.');
            $scope = $this->getFilterScope($filter);
            $isTextual = $this->isTextOperator($operator);
            $isNegativeText = $this->isNegativeTextOperator($operator);

            /* ==========================================================
             * FOREIGN text predicate + subquery
             * => rewrite into correlated EXISTS / NOT EXISTS
             *
             * Rationale:
             *  - Text predicates on 1-N relations are NOT row-local
             *  - Inline evaluation on LEFT JOINs is semantically incorrect
             *    for BOTH positive and negative cases
             *  - EXISTS / NOT EXISTS restores correct set semantics
             *
             * Control model:
             *  - Semantics decide WHEN (foreign + text + subquery)
             *  - Polarity decides EXISTS vs NOT EXISTS
             *  - This block ONLY emits SQL
             * ======================================================== */
            if ($isForeign && $isTextual && $scope === 'root') {

                /*
                 * Compile the predicate using the POSITIVE form.
                 * Polarity is handled exclusively by EXISTS vs NOT EXISTS.
                 */
                $effectiveOp = $isNegativeText
                    ? $this->toPositiveOperator($operator)
                    : $operator;

                [$sql, $b, $bt] = $this->compileSingleFilterCondition(
                    $fieldBinder,
                    $effectiveOp,
                    $filter,
                    $makeBind,
                    'existential'
                );

                if ($sql !== '') {

                    $exists = $this->buildExistsConditionFromField(
                        $originalField,
                        $sql,
                        $isNegativeText
                    );

                    if (!empty($exists['conditions'])) {
                        // Apply legacy boolean prefix HERE (caller responsibility)
                        $fragments[] = $logic . ' ' . $exists['conditions'];

                        // Merge bind data from:
                        //  - compiled predicate
                        //  - existential join normalization
                        $bind += $b;
                        $bind += $exists['bind'];

                        $bindTypes += $bt;
                        $bindTypes += $exists['bindTypes'];
                    }
                }

                continue;
            }

            /* ==========================================================
             * Normal inline compilation
             * ======================================================== */
            if (!array_key_exists('value', $filter)) {
                throw new \Exception(sprintf('Operator "%s" requires a value.', $operator), 400);
            }

            [$sql, $b, $bt] = $this->compileSingleFilterCondition(
                $fieldBinder,
                $operator,
                $filter,
                $makeBind
            );

            if ($sql !== '') {
                $fragments[] = $logic . ' ' . $sql;
                $bind += $b;
                $bindTypes += $bt;
            }
        }

        if ($fragments === []) {
            return null;
        }

        // Assemble exactly like legacy: concatenate fragments, then normalize once.
        $sql = $this->assembleLegacyGroupSql($fragments, $level);

        return [
            'sql' => $sql,
            'bind' => $bind,
            'bindTypes' => $bindTypes,
        ];
    }

    /**
     * Compiles a single filter condition into a SQL expression, including bind parameters and bind types.
     * - SQL string (no surrounding AND/OR, caller controls)
     * - bind array
     * - bindTypes array
     *
     * For negative-subquery use, caller passes the *positive* operator.
     *
     * Responsibilities:
     *  - Translate **canonical operators** (output of normalizeFilterOperator) into SQL
     *  - Preserve legacy semantics (especially negative text operators on LEFT JOINs)
     *  - Remain deterministic and side-effect free
     *
     * IMPORTANT INVARIANTS:
     *  - Operator is already normalized and validated
     *  - Field binder already contains the correct model / join alias
     *  - This method MUST NOT guess intent
     *
     * @param string $fieldBinder The field or column placeholder used in the SQL condition.
     * @param string $operator The operator to be used in the condition (e.g., '=', 'between', 'in', 'like').
     * @param array $filter Contains the filtering criteria, including the value(s) to be used for the condition.
     * @param \Closure $getValue A closure that generates unique parameter names for binding values in the query.
     *
     * @return array An array containing three elements:
     *               - string The compiled SQL condition as a string.
     *               - array Associative array of bind parameters for the query, with parameter names as keys.
     *               - array Associative array of bind types for each parameter.
     */
    protected function compileSingleFilterCondition(
        string   $fieldBinder,
        string   $operator,
        array    $filter,
        \Closure $getValue,
        string   $mode = 'inline',
    ): array
    {
        $isExistential = ($mode === 'existential');

        /*
         * EXISTENTIAL MODE INVARIANTS:
         * - operator MUST be positive
         * - NULL / empty compensation is FORBIDDEN
         * - predicate MUST be row-local
         */
        if ($isExistential) {
            if ($this->isNegativeOperator($operator)) {
                throw new \LogicException(
                    "Negative operator '{$operator}' is not allowed in existential compilation."
                );
            }
        }

        $bind = [];
        $bindTypes = [];

        /* --------------------------------------------------------------
         * BETWEEN / NOT BETWEEN
         * ----------------------------------------------------------- */
        if ($operator === 'between' || $operator === 'not between') {
            $v0 = $getValue('value');
            $v1 = $getValue('value');

            $minFirst = $filter['value'][0] <= $filter['value'][1];
            $bind[$v0] = $filter['value'][$minFirst ? 0 : 1];
            $bind[$v1] = $filter['value'][$minFirst ? 1 : 0];

            $bindTypes[$v0] = Column::BIND_PARAM_STR;
            $bindTypes[$v1] = Column::BIND_PARAM_STR;

            $not = str_starts_with($operator, 'not ') ? 'not ' : '';
            return ["{$not}{$fieldBinder} between :{$v0}: and :{$v1}:", $bind, $bindTypes];
        }

        /* --------------------------------------------------------------
         * DISTANCE SPHERE
         * ----------------------------------------------------------- */
        if (in_array($operator, [
            'distance sphere equals',
            'distance sphere greater than',
            'distance sphere greater than or equal',
            'distance sphere less than',
            'distance sphere less than or equal',
        ], true)) {
            $values = [$getValue('value'), $getValue('value'), $getValue('value'), $getValue('value')];
            $bind[$values[0]] = $filter['value'][0];
            $bind[$values[1]] = $filter['value'][1];
            $bind[$values[2]] = $filter['value'][2];
            $bind[$values[3]] = $filter['value'][3];

            $bindTypes[$values[0]] = Column::BIND_PARAM_DECIMAL;
            $bindTypes[$values[1]] = Column::BIND_PARAM_DECIMAL;
            $bindTypes[$values[2]] = Column::BIND_PARAM_DECIMAL;
            $bindTypes[$values[3]] = Column::BIND_PARAM_DECIMAL;

            $bitwise =
                (str_contains($operator, 'greater') ? '>' : '') .
                (str_contains($operator, 'less') ? '<' : '') .
                (str_contains($operator, 'equal') ? '=' : '');

            $v = $getValue('value');
            $bind[$v] = $filter['value'];
            $bindTypes[$v] = Column::BIND_PARAM_STR;

            $sql = "ST_Distance_Sphere(point(:{$values[0]}:, :{$values[1]}:), point(:{$values[2]}:, :{$values[3]}:)) {$bitwise} :{$v}:";
            return [$sql, $bind, $bindTypes];
        }

        /* --------------------------------------------------------------
         * IN / NOT IN
         * ----------------------------------------------------------- */
        if ($operator === 'in' || $operator === 'not in') {
            $v = $getValue('value');
            $bind[$v] = $filter['value'];
            $bindTypes[$v] = Column::BIND_PARAM_STR;
            return ["{$fieldBinder} {$operator} ({{$v}:array})", $bind, $bindTypes];
        }

        /* --------------------------------------------------------------
         * MULTI-VALUE SEMANTIC OPERATORS
         * ----------------------------------------------------------- */
        $sqlParts = [];
        $values = is_array($filter['value']) ? $filter['value'] : [$filter['value']];

        foreach ($values as $rawValue) {
            $v = $getValue('value');
            $isNegative = str_starts_with($operator, 'does not ') || str_starts_with($operator, 'not ');
            $orIsNullOrEmpty = (!$isExistential && $isNegative) ? " or {$fieldBinder} is null or TRIM({$fieldBinder}) = ''" : "";

            /* ---------- CONTAINS / DOES NOT CONTAIN ---------- */
            if (in_array($operator, ['contains', 'does not contain'], true)) {
                $bind[$v] = '%' . $rawValue . '%';
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlOp = $isNegative ? 'not like' : 'like';
                $sqlParts[] = "{$fieldBinder} {$sqlOp} :{$v}:" . $orIsNullOrEmpty;
                continue;
            }

            /* ---------- STARTS WITH / DOES NOT START WITH ---------- */
            if (in_array($operator, ['starts with', 'does not start with'], true)) {
                $bind[$v] = $rawValue . '%';
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlOp = $isNegative ? 'not like' : 'like';
                $sqlParts[] = "{$fieldBinder} {$sqlOp} :{$v}:" . $orIsNullOrEmpty;
                continue;
            }

            /* ---------- ENDS WITH / DOES NOT END WITH ---------- */
            if (in_array($operator, ['ends with', 'does not end with'], true)) {
                $bind[$v] = '%' . $rawValue;
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlOp = $isNegative ? 'not like' : 'like';
                $sqlParts[] = "{$fieldBinder} {$sqlOp} :{$v}:" . $orIsNullOrEmpty;
                continue;
            }

            /* ---------- CONTAINS WORD / DOES NOT CONTAIN WORD ---------- */
            if (in_array($operator, ['contains word', 'does not contain word'], true)) {
                $bind[$v] = '\\b' . $rawValue . '\\b';
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlOp = $isNegative ? 'not regexp' : 'regexp';
                $sqlParts[] = "{$sqlOp}({$fieldBinder}, :{$v}:)";
                continue;
            }

            /* ---------- REGEXP / NOT REGEXP ---------- */
            if ($operator === 'regexp' || $operator === 'not regexp') {
                $bind[$v] = $rawValue;
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlParts[] = "{$operator}({$fieldBinder}, :{$v}:)";
                continue;
            }

            /* ---------- IS EMPTY / IS NOT EMPTY ---------- */
            if ($operator === 'is empty') {
                $sqlParts[] = "(TRIM({$fieldBinder}) = '' or {$fieldBinder} is null)";
                continue;
            }

            if ($operator === 'is not empty') {
                $sqlParts[] = "not (TRIM({$fieldBinder}) = '' or {$fieldBinder} is null)";
                continue;
            }

            /* ---------- FALLBACK: SIMPLE COMPARISON ---------- */
            $bind[$v] = $rawValue;
            $bindTypes[$v] = $this->getBindTypeFromRawValue($rawValue);

            $rhs = is_array($rawValue) ? "({{$v}:array})" : ":{$v}:";
            $sqlParts[] = "{$fieldBinder} {$operator} {$rhs}";
        }

        if ($sqlParts === []) {
            return ['', [], []];
        }

        /* --------------------------------------------------------------
         * GROUPING LOGIC
         *
         * Positive semantics: OR
         * Negative semantics: AND
         * ----------------------------------------------------------- */
//        $glue = $this->isNegativeOperator($operator) ? ' and ' : ' or ';
//        $sql = '((' . implode(')' . $glue . '(', $sqlParts) . '))';
        $innerGlue = $this->isNegativeOperator($operator) ? ' and ' : ' or ';
        $innerGlue = $isExistential ? ' or ' : $innerGlue;
        $sql = '(' . implode($innerGlue, array_map(fn($p) => "($p)", $sqlParts)) . ')';

        return [$sql, $bind, $bindTypes];
    }

    /**
     * Assemble and normalize a group using the legacy prefix-token model.
     *
     * @param string[] $fragments Each fragment is already prefixed with "and|or|xor"
     *                            OR is a nested group's already-normalized SQL.
     * @param int $level Recursion depth (0 = root).
     */
    protected function assembleLegacyGroupSql(array $fragments, int $level): string
    {
        $sql = trim(implode(' ', array_filter($fragments, static fn($s) => is_string($s) && trim($s) !== '')));

        if ($sql === '') {
            return '';
        }

        // Legacy normalization: strip token at root, preserve it for nested groups.
        return preg_replace(
            '/^(xor |and |or )(.*)$/i',
            $level > 0 ? '$1($2)' : '($2)',
            $sql
        );
    }

    /**
     * Optimizes operator / value pairs based on value semantics.
     *
     * This method performs **safe semantic rewrites** where intent
     * is unambiguous and SQL correctness would otherwise be violated.
     *
     * Examples:
     *  - contains + int      → =
     *  - contains + int[]    → in
     *  - does not contain + int   → !=
     *  - does not contain + int[] → not in
     *
     * IMPORTANT:
     *  - This method MUST be side-effect free
     *  - It MUST NOT guess intent
     *  - It MUST preserve meaning for strings
     *
     * @param string $operator Canonical operator (already normalized)
     * @param mixed $value Raw filter value
     *
     * @return array{string, mixed} Optimized operator and value
     */
    protected function optimizeOperatorAndValue(string $operator, mixed $value): array
    {
        // Normalize array wrapper
        $values = is_array($value) ? $value : [$value];

        // Detect numeric-only values
        $allInts = !empty($values)
            && array_reduce(
                $values,
                static fn($carry, $v) => $carry && is_int($v),
                true
            );

        if (!$allInts) {
            // Nothing to optimize safely
            return [$operator, $value];
        }

        /* --------------------------------------------------------------
         * Numeric containment promotion
         * ----------------------------------------------------------- */
        if ($operator === 'contains') {
            return count($values) === 1
                ? ['=', $values[0]]
                : ['in', $values];
        }

        if ($operator === 'does not contain') {
            return count($values) === 1
                ? ['!=', $values[0]]
                : ['not in', $values];
        }

        return [$operator, $value];
    }

    /**
     * Build a correlated EXISTS / NOT EXISTS subquery condition from a relationship field.
     *
     * This method is a PURE SQL emitter:
     *  - it does NOT decide semantics
     *  - it does NOT infer polarity
     *  - it only materializes an existential path
     *
     * Polarity (EXISTS vs NOT EXISTS) is controlled explicitly by the caller.
     *
     * Assumptions:
     *  - getJoinsDefinitionFromField() returns joins ordered deepest → shallowest
     *  - joins may contain payload blocks (new join format)
     *  - $condition already references join aliases, not the root model
     *
     * @param string $field Relationship field used to resolve joins
     * @param string $condition Predicate applied inside the subquery
     * @param bool $negated Whether to emit NOT EXISTS instead of EXISTS
     *
     * @return array{conditions: string, bind: array, bindTypes: array}
     * @throws \Exception
     */
    protected function buildExistsConditionFromField(
        string $field,
        string $condition,
        bool   $negated = false
    ): array
    {
        $joins = $this->getJoinsDefinitionFromField($field);

        if (empty($joins)) {
            throw new \Exception(sprintf(
                'Unable to prepare existential subquery for the foreign field "%s".',
                $field
            ), 400);
        }

        /**
         * Normalize joins once.
         * This:
         *  - merges payload SQL into ON clauses
         *  - extracts bind + bindTypes
         *  - returns pure Phalcon joins
         */
        $normalized = $this->normalizeJoins($joins);

        $joins = $normalized['joins'];
        $bind = $normalized['bind'];
        $bindTypes = $normalized['bindTypes'];

        /**
         * First join is the anchor that correlates the subquery
         * back to the root model.
         */
        $firstJoin = array_shift($joins);
        [$rootModel, $rootOn, $rootAlias] = $firstJoin;

        /**
         * Remaining joins are emitted verbatim inside the subquery.
         */
        $joinsSql = [];

        foreach ($joins as $join) {
            [$model, $on, $alias] = $join;
            $type = $join[3] ?? 'INNER';

            $joinsSql[] =
                strtoupper($type) .
                ' JOIN [' . $model . '] AS [' . $alias . '] ON ' . $on;
        }

        /**
         * Build correlated EXISTS / NOT EXISTS subquery.
         * Correlation happens via the first join ON clause.
         */
        $existsKeyword = $negated ? 'NOT EXISTS' : 'EXISTS';

        $sql =
            $existsKeyword . ' (' .
            'SELECT 1 FROM [' . $rootModel . '] AS [' . $rootAlias . '] ' .
            implode(' ', $joinsSql) .
            ' WHERE ' . $rootOn .
            ' AND (' . $condition . ')' .
            ')';

        return [
            'conditions' => $sql,
            'bind' => $bind,
            'bindTypes' => $bindTypes,
        ];
    }

    /**
     * Resolves and returns the semantic scope of a given filter.
     *
     * This method determines the evaluation context for the filter's predicate
     * based on the specified scope. It uses predefined conventions to limit
     * the valid values and to ensure consistent behavior.
     *
     * CONTRACT:
     *  - Input MUST explicitly define 'scope' or resolve to the default value
     *  - Input MUST define a valid scope from the allowed set
     *  - Output MUST represent an allowed scope ('root', 'self', 'through')
     *
     * This method MUST NOT:
     *  - infer scope from other filter elements (operators, fields, etc.)
     *  - guess the intent of the filter
     *
     * IMPORTANT (scope - semantics - SQL):
     *  - root - set-level - EXISTS / NOT EXISTS
     *  - through - edge-level - EXISTS anchored on pivot
     *  - self - row-level - inline join predicate
     *
     * @param array $filter The filter definition, which may include a 'scope' key.
     * @return string The resolved scope ('root' by default, or one of the allowed scopes).
     * @throws \LogicException If the provided scope is invalid or unrecognized.
     */
    protected function getFilterScope(array $filter): string
    {
        /*
         * Scope defines the semantic universe over which the predicate is evaluated.
         *
         * Allowed values (by convention):
         *  - 'root'    : set-level semantics (EXISTS / NOT EXISTS) — DEFAULT
         *  - 'self'    : row-level semantics (inline join predicate)
         *  - 'through' : relationship-edge semantics (reserved, not implemented yet)
         *
         * IMPORTANT:
         *  - This method is the ONLY place where scope is resolved
         *  - Do NOT infer scope from operator, field, or join depth
         *  - Do NOT guess intent
         */

        if (isset($filter['scope'])) {
            $scope = strtolower(trim((string) $filter['scope']));
            $allowedScopes = ['root', 'self', 'through'];

            if (!in_array($scope, $allowedScopes, true)) {
                throw new \LogicException("Invalid filter scope: {$scope}");
            }

            return $scope;
        }

        // Default semantic: record-level truth
        return 'root';
    }

    /**
     * Resolve the logical token ("and" | "or" | "xor") that prefixes the current fragment.
     *
     * Legacy-compatible semantics:
     *  - "logic" in payload always wins if present (validated).
     *  - Otherwise, fall back to the historical alternation rule that depends on:
     *      - $or   : current group default mode (toggled at each nesting level)
     *      - $index: first element is treated specially (legacy quirk)
     *
     * Why the first-element rule exists:
     *  - The legacy generator always prefixed tokens (even for the first fragment).
     *  - A normalization regex later strips the leading token at root level but
     *    preserves it for nested groups.
     *  - Many legacy payloads rely on this behavior, especially when forcing
     *    logic *before* a group.
     *
     * IMPORTANT:
     *  - This method returns a lowercase token suitable for prefix emission
     *    ("and ", "or ", "xor ").
     *  - It does NOT decide parentheses or string formatting; the caller/assembler does.
     *
     * @param array $node Current filter node (field filter or group metadata carrier).
     * @param int $index Index within the current group (0-based).
     * @param bool $or Current group alternation flag (flipped per nesting level).
     *
     * @return string One of: "and", "or", "xor"
     *
     * @throws \Exception If an unsupported logical operator is provided.
     */
    protected function resolveFilterLogicToken(array $node, int $index, bool $or): string
    {
        // 1) Explicit override (payload "logic") always wins.
        //    Keep this tolerant only at this boundary.
        $logic = $this->filter->sanitize($node['logic'] ?? null, [Filter::FILTER_STRING, Filter::FILTER_TRIM, 'lower']);

        $logic = is_string($logic) ? trim($logic) : '';

        if ($logic !== '') {
            if (!in_array($logic, ['and', 'or', 'xor'], true)) {
                throw new \Exception(sprintf('Unsupported logical operator: `%s`', $logic), 400);
            }

            return $logic;
        }

        // 2) Legacy fallback (level-driven alternation + first-element quirk)
        //
        // Legacy rule:
        //   - When $or is false (root mode), index 0 => "or", others => "and"
        //   - When $or is true  (toggled mode), index 0 => "and", others => "or"
        //
        // This is the exact behavior from:
        //   $logic ?: ($or ? ($i===0?'and':'or') : ($i===0?'or':'and'))
        return $or
            ? ($index === 0 ? 'and' : 'or')
            : ($index === 0 ? 'or' : 'and');
    }

    /**
     * Retrieves the bind type based on the raw value.
     *
     * @param mixed|null $rawValue The raw value to determine the bind type for.
     *
     * @return int The bind type based on the raw value. Possible values are:
     *             - Column::BIND_PARAM_STR: If the raw value is a string or an array.
     *             - Column::BIND_PARAM_INT: If the raw value is an integer.
     *             - Column::BIND_PARAM_BOOL: If the raw value is a boolean.
     *             - Column::BIND_PARAM_DECIMAL: If the raw value is a float or a double.
     *             - Column::BIND_PARAM_NULL: If the raw value is null or its type is not recognized.
     */
    public function getBindTypeFromRawValue(mixed $rawValue = null): int
    {
        if (is_string($rawValue)) {
            return Column::BIND_PARAM_STR;
        }

        if (is_int($rawValue)) {
            return Column::BIND_PARAM_INT;
        }

        if (is_bool($rawValue)) {
            return Column::BIND_PARAM_BOOL;
        }

        if (is_float($rawValue)) {
            return Column::BIND_PARAM_DECIMAL;
        }

        if (is_array($rawValue)) {
            return Column::BIND_PARAM_STR;
        }

        return Column::BIND_PARAM_NULL;
    }

    /**
     * Convert a canonical NEGATIVE operator into its POSITIVE equivalent.
     *
     * This method is used EXCLUSIVELY for existential compilation.
     *
     * CONTRACT:
     *  - Input MUST be a canonical operator (normalizeFilterOperator output)
     *  - Input MUST represent a negative semantic operator
     *  - Output MUST be the positive equivalent
     *
     * This method MUST NOT:
     *  - guess intent
     *  - rewrite non-negative operators
     *  - accept unknown operators
     *
     * @throws \LogicException if operator cannot be safely converted
     */
    protected function toPositiveOperator(string $operator): string
    {
        $operator = strtolower(trim($operator));

        /* ==========================================================
         * Explicit semantic negations (textual)
         * ======================================================== */
        $semanticMap = [
            'does not contain' => 'contains',
            'does not start with' => 'starts with',
            'does not end with' => 'ends with',
            'does not contain word' => 'contains word',
            'not regexp' => 'regexp',
        ];

        if (isset($semanticMap[$operator])) {
            return $semanticMap[$operator];
        }

        /* ==========================================================
         * Symbolic negations
         * ======================================================== */
        $symbolicMap = [
            '!=' => '=',
            '<>' => '=',
            'not like' => 'like',
            'not in' => 'in',
            'not between' => 'between',
        ];

        if (isset($symbolicMap[$operator])) {
            return $symbolicMap[$operator];
        }

        /* ==========================================================
         * Structural safety net (last resort)
         * ======================================================== */
        if (str_starts_with($operator, 'not ')) {
            return substr($operator, 4);
        }

        if (str_starts_with($operator, 'does not ')) {
            return substr($operator, 9);
        }

        /* ==========================================================
         * Rejection
         * ======================================================== */
        if ($this->isNegativeOperator($operator)) {
            throw new \LogicException(
                "Unable to convert negative operator '{$operator}' to a positive equivalent."
            );
        }

        // Operator is already positive
        return $operator;
    }

    /**
     *  Returns:
     *   - originalField: user-provided field (trimmed)
     *   - joinName:      portion before last dot, or null
     *   - fieldName:     portion after last dot (or whole)
     *   - joinAlias:     mapped alias (dynamicJoinsMapping), or joinName
     * @param string $field
     * @return array
     */
    protected function splitField(string $field): array
    {
        $originalField = $field;

        if (!str_contains($field, '.')) {
            return [$originalField, null, $field, null];
        }

        $joinName = substr($field, 0, strrpos($field, '.'));
        $fieldName = substr($field, strrpos($field, '.') + 1);

        $joinAlias = $this->dynamicJoinsMapping[$joinName] ?? $joinName;

        return [$originalField, $joinName, $fieldName, $joinAlias];
    }

    /**
     * Check whether the current request filters contain one or more of the specified fields.
     *
     * Supports nested AND/OR logic - each nested array inverts the operator.
     *
     * Examples:
     *   hasFiltersParams('status')                      // checks if "status" filter exists
     *   hasFiltersParams(['status', 'type'], true)      // "status" OR "type"
     *   hasFiltersParams(['status', 'type'])            // "status" AND "type"
     *   hasFiltersParams([['status', 'type']])          // "status" OR "type"
     *   hasFiltersParams([[['status', 'type']]])        // "status" AND "type"
     *
     * @param array|string|null $fields List of fields to check against. If null, checks if "filters" param exists.
     * @param bool $or If true, matches at least one (OR). If false, matches all (AND).
     *
     * @return bool True if the filters satisfy the condition, false otherwise.
     *
     * @throws Exception
     */
    public function hasFiltersFieldsParams(array|string|null $fields = null, bool $or = false): bool
    {
        // no filters param
        if (!$this->hasParam('filters')) {
            return false;
        }

        $filters = $this->getParam('filters');
        if (empty($filters)) {
            return false;
        }

        // if no fields specified, just check presence of filters param
        if (is_null($fields)) {
            return true;
        }

        // normalize to array
        $fields = (array)$fields;

        // collect all filter fields recursively
        $flattenFilters = static function (array $filters) use (&$flattenFilters): array {
            $found = [];
            foreach ($filters as $filter) {
                // handle nested groups
                if (isset($filter[0]) && is_array($filter[0])) {
                    $found = array_merge($found, $flattenFilters($filter));
                    continue;
                }

                if (!empty($filter['field'])) {
                    $found[] = $filter['field'];
                }
            }
            return $found;
        };

        // helper for nested evaluation (similar to has())
        $nestedCheck = function (array|string|null $needles, array $filters, bool $or) use (&$nestedCheck, &$flattenFilters): bool {
            if (!is_array($needles)) {
                $needles = isset($needles) ? [$needles] : [];
            }
            if (empty($needles)) {
                return false;
            }

            $filterFields = $flattenFilters($filters);
            $result = [];

            foreach ($needles as $needle) {
                if (is_array($needle)) {
                    $result[] = $nestedCheck($needle, $filters, !$or);
                } else {
                    $result[] = in_array($needle, $filterFields, true);
                }
            }

            // logical inversion rule
            return $or
                ? in_array(true, $result, true) // OR
                : !in_array(false, $result, true); // AND
        };

        return $nestedCheck($fields, $filters, $or);
    }
}
