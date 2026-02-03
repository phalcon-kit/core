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
    use ExistentialConditions;
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
     * @param ?string $aliasContext Optional alias context for join-based filters
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
        ?string $aliasContext = null,
        bool   $or = false,
        int    $level = 0,
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
        $compiled = $this->compileGroup($filters, $or, $level, $allowedFilters, $aliasContext);

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
     * This is the compiler’s boolean / structural spine.
     *
     * Responsibilities (and ONLY these):
     *  - Walk the filter AST (arrays + field nodes)
     *  - Resolve legacy prefix tokens ("and|or|xor")
     *  - Enforce boolean boundaries (OR/XOR, group nesting)
     *  - Route each node to either:
     *      - SELF / INLINE compilation (self, row-local)
     *      - EXISTENTIAL compilation (EXISTS / NOT EXISTS)
     *  - Perform AND-sibling existential coalescing (bucket accumulation)
     *  - Emit legacy-shaped SQL with exactly-one normalization at group exit
     *
     * Non-responsibilities:
     *  - Operator normalization (handled by normalizeFilterOperator)
     *  - Join creation policy (handled by caller / DynamicJoins gate)
     *  - Existential join building (handled by buildExistsConditionFromField)
     *
     * Semantic invariants enforced here:
     *  - OR/XOR always terminates existential accumulation (flush boundary)
     *  - Group entry/exit always terminates existential accumulation
     *  - Existential inner predicates are compiled with POSITIVE operators only
     *  - NOT EXISTS is the ONLY allowed negation mechanism for existential text predicates
     *
     * @param array $filters Group payload
     * @param bool $or Current alternation mode (flipped per nesting)
     * @param int $level Recursion depth (0 = root)
     * @param array $allowedFilters Allowed filter fields
     * @param ?string $aliasContext Optional alias context
     *
     * @return array|null ['sql'=>string,'bind'=>array,'bindTypes'=>array]
     *
     * @throws \Exception|\LogicException
     */
    protected function compileGroup(array $filters, bool $or, int $level, array $allowedFilters, ?string $aliasContext = null): ?array
    {
        $fragments = [];  // each fragment is either "and <expr>" / "or <expr>" / "xor <expr>" OR a nested group's normalized SQL
        $bind = [];
        $bindTypes = [];

        /**
         * Pending existential buckets for AND-sibling coalescing.
         *
         * Keyed by getExistentialBucketKey(), which MUST represent the existential “universe”:
         *   - relationship path (no leaf field)
         *   - polarity (EXISTS vs NOT EXISTS)
         *   - scope marker (kept for debugging / future constraints)
         */
        $pendingExists = [];

        foreach ($filters as $index => $node) {

            /* ==========================================================
             * GROUP NODE (nested array)
             * ======================================================== */
            if (is_array($node) && isset($node[0]) && is_array($node[0])) {

                // Group boundary is always a flush boundary.
                $this->flushExistentialBuckets($pendingExists, $fragments, $bind, $bindTypes);

                // Compile nested group with flipped alternation (legacy rule).
                $nested = $this->compileGroup($node, !$or, $level + 1, $allowedFilters, $aliasContext);

                if ($nested !== null && $nested['sql'] !== '') {

                    /*
                     * LEGACY-COMPAT GROUP CONNECTOR (CARRIER LOGIC)
                     *
                     * Business rule to preserve:
                     *  - If the first concrete field inside the group declares `logic`,
                     *    that logic applies to the group itself relative to the parent.
                     *  - Otherwise, fall back to the parent's default token resolution.
                     *
                     * CRITICAL:
                     *  - The nested group SQL at level>0 is *already prefixed*.
                     *  - That prefix MUST NOT leak into the parent.
                     *  - The parent must own the connector at this position.
                     */
                    $groupCarrier = $this->resolveGroupCarrierLogic($node); // 'and'|'or'|'xor'|null
                    $parentLogic = $groupCarrier ?? $this->resolveFilterLogicToken($node[0], $index, $or);

                    // Strip nested prefix token. Parent will re-apply its connector.
                    $strippedSql = preg_replace(
                        '/^(and|or|xor)\s+/i',
                        '',
                        trim($nested['sql'])
                    );

                    $fragments[] = $parentLogic . ' ' . $strippedSql;

                    // Safe bind merge (defensive; should never collide if bind keys are unique).
                    if (array_intersect_key($bind, $nested['bind']) !== []) {
                        throw new \LogicException('Bind collision detected while merging nested group.');
                    }
                    if (array_intersect_key($bindTypes, $nested['bindTypes']) !== []) {
                        throw new \LogicException('BindType collision detected while merging nested group.');
                    }

                    $bind += $nested['bind'];
                    $bindTypes += $nested['bindTypes'];
                }

                continue;
            }

            /* ==========================================================
             * FIELD NODE (validation)
             * ======================================================== */
            if (!is_array($node)) {
                // Defensive: ignore non-array garbage
                continue;
            }

            if (empty($node['field'])) {
                throw new \Exception('A valid filter field property is required.', 400);
            }

            if (empty($node['operator'])) {
                throw new \Exception('A valid filter operator property is required.', 400);
            }

            /* ==========================================================
             * Resolve boolean prefix token (legacy model)
             * ======================================================== */
            $logic = $this->resolveFilterLogicToken($node, $index, $or);

            // OR/XOR is a hard boundary: AND-coalescing cannot cross it.
            if ($logic === 'or' || $logic === 'xor') {
                $this->flushExistentialBuckets($pendingExists, $fragments, $bind, $bindTypes);
            }

            /* ==========================================================
             * Field sanitization + policy validation
             * ======================================================== */
            $rawField = $this->filter->sanitize(
                (string)$node['field'],
                [Filter::FILTER_STRING, Filter::FILTER_TRIM]
            );

            if (!$this->isFilterAllowed($rawField, $allowedFilters)) {
                throw new \Exception(sprintf('Unauthorized filter field "%s".', $rawField), 403);
            }

            /* ==========================================================
             * Operator normalization (single boundary)
             * ======================================================== */
            $operator = $this->normalizeFilterOperator((string)$node['operator']);
            if ($operator === '') {
                throw new \Exception(sprintf('Unsupported filter operator "%s".', (string)$node['operator']), 403);
            }

            /* ==========================================================
             * "IS *" contract enforcement (legacy tolerant boundary)
             * ======================================================== */
            if (str_starts_with($operator, 'is')) {
                if (array_key_exists('value', $node) && $node['value'] !== '' && $node['value'] !== null) {
                    throw new \Exception(sprintf('Operator "%s" does not accept a value.', $operator), 403);
                }
                unset($node['value']);
            }

            /* ==========================================================
             * Canonical operator/value normalization (type-preserving)
             *
             * IMPORTANT:
             *  - This happens BEFORE scope routing because it can change
             *    the operator class (textual -> scalar).
             *  - This is NOT "self / inline-only": it is safe whenever the value
             *    type makes intent unambiguous (int/int[] only).
             * ======================================================== */
            if (array_key_exists('value', $node)) {
                [$operator, $node['value']] = $this->optimizeOperatorAndValue($operator, $node['value']);
            }

            /* ==========================================================
             * Determine semantic scope FIRST (universe routing switch)
             * ======================================================== */
            $scope = $this->getFilterScope($node, $aliasContext);

            /* ==========================================================
             * Field binder resolution (model + alias)
             * ======================================================== */
            [$originalField, , $fieldName, $joinAlias] = $this->splitField($rawField);
            $fieldBinder = $this->appendModelName($fieldName, $joinAlias);

            /* ==========================================================
             * Bind factory (unique, deterministic enough for compiler usage)
             * ======================================================== */
            $filterId = $this->security->getRandom()->hex(8);
            $makeBind = static function (string $suffix) use ($filterId): string {
                return '_' . uniqid($filterId . '_' . $suffix . '_') . '_';
            };

            /* ==========================================================
             * NO-VALUE operators
             *
             * IMPORTANT CORRECTION:
             *  - If scope is existential (e.g. alias [b]), the predicate MUST be inside EXISTS.
             *  - If scope is self/through, emit self / inline (legacy behavior).
             * ======================================================== */
            if ($this->isNoValueOperator($operator)) {

                // Build row-local predicate text (no binds).
                $inlineMap = [
                    'is empty' => "(TRIM({$fieldBinder}) = '' or {$fieldBinder} is null)",
                    'is not empty' => "not (TRIM({$fieldBinder}) = '' or {$fieldBinder} is null)",

                    'is null' => "{$fieldBinder} is null",
                    'is not null' => "{$fieldBinder} is not null",

                    'is true' => "{$fieldBinder} = 1",
                    'is false' => "{$fieldBinder} = 0",
                    'is not true' => "{$fieldBinder} != 1",
                    'is not false' => "{$fieldBinder} != 0",
                ];

                if (!isset($inlineMap[$operator])) {
                    throw new \LogicException("Unhandled no-value operator: {$operator}");
                }

                // Scope must already be resolved before this block.
                // $scope = $this->getFilterScope($node);

                if ($scope === 'existential') {

                    /*
                     * EXISTENTIAL NO-VALUE SEMANTICS
                     *
                     * Key correction:
                     *  - "is not empty" => EXISTS( not empty )
                     *  - "is empty"     => NOT EXISTS( not empty )
                     *
                     * This preserves set logic:
                     *  - is not empty selects rows with at least one qualifying child
                     *  - is empty selects rows with no qualifying child (includes “no children”)
                     */

                    $negated = false;
                    $condSql = $inlineMap[$operator];

                    if ($operator === 'is empty') {
                        // Dualize to NOT EXISTS( is not empty )
                        $negated = true;
                        $condSql = $inlineMap['is not empty'];
                    }

                    // NOTE: do NOT dualize other no-value operators unless you define that contract explicitly.
                    // For now, keep them as EXISTS(<operator predicate>).
                    // If you later want complements for NULL/boolean, add explicit dual rules like above.

                    $bucketKey = $this->getExistentialBucketKey($originalField, $negated, $scope);

                    $this->pushExistentialCondition(
                        $pendingExists,
                        $bucketKey,
                        $originalField,
                        $negated,
                        $condSql,
                        [],
                        []
                    );

                    continue;
                }

                // SELF / INLINE behavior (unchanged)
                $this->flushExistentialBuckets($pendingExists, $fragments, $bind, $bindTypes);
                $fragments[] = $logic . ' ' . $inlineMap[$operator];
                continue;
            }

            /* ==========================================================
             * EXISTENTIAL compilation (EXISTS / NOT EXISTS)
             *
             * Routing rule:
             *  - ONLY $scope decides existentiality (not operator class, not dot presence)
             *
             * Current supported existential predicates (strict correctness):
             *  - Text operators (positive -> EXISTS, negative -> NOT EXISTS)
             *
             * Anything else must be explicitly designed and added; do not guess.
             * ======================================================== */
            if ($scope === 'existential') {

                // Existential coalescing is AND-only. OR/XOR flushed already above.
                if (!in_array($logic, ['and', 'or', 'xor'], true)) {
                    throw new \LogicException("Invalid logic token emitted: {$logic}");
                }

                // This compiler currently supports existential semantics for TEXT predicates only.
                $isTextual = $this->isTextOperator($operator);
                $isNegativeText = $this->isNegativeTextOperator($operator);

                if (!array_key_exists('value', $node)) {
                    throw new \Exception(sprintf('Operator "%s" requires a value.', $operator), 400);
                }

                /*
                 * Existential predicates may be:
                 *  - textual (contains, regexp, etc.)
                 *  - scalar (=, >, IN, BETWEEN, etc.)
                 *
                 * Constraints:
                 *  - no-value operators are forbidden (already enforced earlier)
                 *  - negative semantics must be expressed via NOT EXISTS
                 */
                $negated = $isNegativeText || ($this->isNegativeOperator($operator) && !$isTextual);
                $effectiveOp = $negated ? $this->toPositiveOperator($operator) : $operator;

                // Compile the INNER predicate (must be row-local to the subquery universe).
                [$condSql, $b, $bt] = $this->compileSingleFilterCondition(
                    $fieldBinder,
                    $effectiveOp,
                    $node,
                    $makeBind,
                    'existential'
                );

                if ($condSql === '') {
                    continue;
                }

                /*
                 * EXISTENTIAL EMISSION RULE (critical)
                 *
                 * - AND siblings: may coalesce (bucket)
                 * - OR/XOR siblings: must emit immediately (no bucket), because coalescing is illegal
                 *   and each predicate must merge its own bind namespace deterministically.
                 */
                if ($logic === 'and') {
                    $bucketKey = $this->getExistentialBucketKey($originalField, $negated, $scope);

                    $this->pushExistentialCondition(
                        $pendingExists,
                        $bucketKey,
                        $originalField,
                        $negated,
                        $condSql,
                        $b,
                        $bt
                    );

                    continue;
                }

                // OR / XOR: emit immediately (no bucket)
                $exists = $this->buildExistsConditionFromField(
                    $originalField,
                    '(' . $condSql . ')',
                    $negated
                );

                if (!empty($exists['conditions'])) {
                    $fragments[] = $logic . ' ' . $exists['conditions'];

                    // Merge predicate binds
                    if (!empty($b)) {
                        if (array_intersect_key($bind, $b) !== []) {
                            throw new \LogicException('Bind collision detected while merging OR/XOR existential predicate binds.');
                        }
                        $bind += $b;
                    }
                    if (!empty($bt)) {
                        if (array_intersect_key($bindTypes, $bt) !== []) {
                            throw new \LogicException('BindType collision detected while merging OR/XOR existential predicate bindTypes.');
                        }
                        $bindTypes += $bt;
                    }

                    // Merge EXISTS join binds
                    if (!empty($exists['bind'])) {
                        if (array_intersect_key($bind, $exists['bind']) !== []) {
                            throw new \LogicException('Bind collision detected while merging OR/XOR EXISTS join binds.');
                        }
                        $bind += $exists['bind'];
                    }
                    if (!empty($exists['bindTypes'])) {
                        if (array_intersect_key($bindTypes, $exists['bindTypes']) !== []) {
                            throw new \LogicException('BindType collision detected while merging OR/XOR EXISTS join bindTypes.');
                        }
                        $bindTypes += $exists['bindTypes'];
                    }
                }

                continue;
            }

            /* ==========================================================
             * SELF / INLINE compilation (row-local predicate)
             * ======================================================== */

            // SELF / INLINE is a hard boundary relative to existential accumulation.
            $this->flushExistentialBuckets($pendingExists, $fragments, $bind, $bindTypes);

            if (!array_key_exists('value', $node)) {
                throw new \Exception(sprintf('Operator "%s" requires a value.', $operator), 400);
            }

            [$sql, $b, $bt] = $this->compileSingleFilterCondition(
                $fieldBinder,
                $operator,
                $node,
                $makeBind,
                'self'
            );

            if ($sql !== '') {
                $fragments[] = $logic . ' ' . $sql;

                if (array_intersect_key($bind, $b) !== []) {
                    throw new \LogicException('Bind collision detected while merging self / inline condition.');
                }
                if (array_intersect_key($bindTypes, $bt) !== []) {
                    throw new \LogicException('BindType collision detected while merging self / inline condition.');
                }

                $bind += $b;
                $bindTypes += $bt;
            }
        }

        // Group exit is a hard boundary: flush anything pending.
        $this->flushExistentialBuckets($pendingExists, $fragments, $bind, $bindTypes);

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
     * @param string $mode The semantic scope of the filter condition. Can be 'self' or 'existential'.
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
        string   $mode = 'self',
    ): array
    {
        $isExistential = ($mode === 'existential');

        /*
         * ==========================================================
         * EXISTENTIAL MODE — NON-NEGOTIABLE INVARIANTS
         * ==========================================================
         *
         * Existential predicates describe SET existence.
         * Therefore:
         *  - Inner predicate MUST be positive
         *  - Negation is expressed ONLY via NOT EXISTS
         *  - No scalar negation is allowed
         *  - No unary operators are allowed
         */
        if ($isExistential) {
            if ($this->isNegativeTextOperator($operator)) {
                throw new \LogicException(
                    "Negative text operator '{$operator}' must be normalized to positive before existential compilation."
                );
            }

            if ($this->isNegativeOperator($operator)) {
                throw new \LogicException(
                    "Negative operator '{$operator}' is not allowed in existential compilation."
                );
            }

            if ($this->isNoValueOperator($operator)) {
                throw new \LogicException(
                    "No-value operator '{$operator}' is not allowed in existential compilation."
                );
            }
        }

        $bind = [];
        $bindTypes = [];

        /* ==========================================================
         * BETWEEN / NOT BETWEEN
         * ==========================================================
         *
         * Semantics:
         *  - SELF / INLINE: both allowed
         *  - EXISTENTIAL: only positive BETWEEN allowed
         */
        if ($operator === 'between' || $operator === 'not between') {

            if ($isExistential && str_starts_with($operator, 'not ')) {
                throw new \LogicException(
                    "Negative range operator '{$operator}' is not allowed in existential compilation."
                );
            }

            $v0 = $getValue('value');
            $v1 = $getValue('value');

            // Order values deterministically
            $minFirst = $filter['value'][0] <= $filter['value'][1];
            $bind[$v0] = $filter['value'][$minFirst ? 0 : 1];
            $bind[$v1] = $filter['value'][$minFirst ? 1 : 0];

            $bindTypes[$v0] = Column::BIND_PARAM_STR;
            $bindTypes[$v1] = Column::BIND_PARAM_STR;

            $not = (!$isExistential && str_starts_with($operator, 'not ')) ? 'not ' : '';

            return ["{$not}{$fieldBinder} between :{$v0}: and :{$v1}:", $bind, $bindTypes];
        }

        /* ==========================================================
         * DISTANCE SPHERE (GEO)
         * ==========================================================
         *
         * Semantics:
         *  - Treated as scalar comparison over computed distance
         *  - EXISTENTIAL allowed ONLY in positive form
         */
        if (in_array($operator, [
            'distance sphere equals',
            'distance sphere greater than',
            'distance sphere greater than or equal',
            'distance sphere less than',
            'distance sphere less than or equal',
        ], true)) {

            // Guard against negative existential geo
            if ($isExistential && str_contains($operator, 'not')) {
                throw new \LogicException(
                    "Negative geo operator '{$operator}' is not allowed in existential compilation."
                );
            }

            // Bind geo coordinates
            $coords = [
                $getValue('value'),
                $getValue('value'),
                $getValue('value'),
                $getValue('value'),
            ];

            foreach ($coords as $i => $v) {
                $bind[$v] = $filter['value'][$i];
                $bindTypes[$v] = Column::BIND_PARAM_DECIMAL;
            }

            // Distance threshold
            $v = $getValue('value');
            $bind[$v] = $filter['value'][4] ?? $filter['value'];
            $bindTypes[$v] = Column::BIND_PARAM_STR;

            // Build comparison operator
            $cmp =
                (str_contains($operator, 'greater') ? '>' : '') .
                (str_contains($operator, 'less') ? '<' : '') .
                (str_contains($operator, 'equal') ? '=' : '');

            $sql =
                "ST_Distance_Sphere(" .
                "point(:{$coords[0]}:, :{$coords[1]}:), " .
                "point(:{$coords[2]}:, :{$coords[3]}:)" .
                ") {$cmp} :{$v}:";

            return [$sql, $bind, $bindTypes];
        }

        /* ==========================================================
         * IN / NOT IN
         * ==========================================================
         *
         * Semantics:
         *  - SELF / INLINE: both allowed
         *  - EXISTENTIAL: NOT IN forbidden
         */
        if ($operator === 'in' || $operator === 'not in') {

            if ($isExistential && $operator === 'not in') {
                throw new \LogicException(
                    "Negative set operator '{$operator}' is not allowed in existential compilation."
                );
            }

            $v = $getValue('value');
            $bind[$v] = $filter['value'];
            $bindTypes[$v] = Column::BIND_PARAM_STR;

            return ["{$fieldBinder} {$operator} ({{$v}:array})", $bind, $bindTypes];
        }

        /* ==========================================================
         * MULTI-VALUE / TEXTUAL / SCALAR OPERATORS
         * ==========================================================
         */
        $sqlParts = [];
        $values = is_array($filter['value']) ? $filter['value'] : [$filter['value']];

        foreach ($values as $rawValue) {
            $v = $getValue('value');

            /*
             * SELF / INLINE-ONLY NEGATIVE COMPENSATION
             *
             * Existential predicates MUST NOT compensate NULL / EMPTY.
             */
            $inlineNegative =
                !$isExistential &&
                (str_starts_with($operator, 'does not ') || str_starts_with($operator, 'not '));

            $orNullEmpty = $inlineNegative
                ? " or {$fieldBinder} is null or TRIM({$fieldBinder}) = ''"
                : '';

            /* ---------- CONTAINS / DOES NOT CONTAIN ---------- */
            if (in_array($operator, ['contains', 'does not contain'], true)) {

                if ($isExistential && $operator === 'does not contain') {
                    throw new \LogicException("Negative text operator leaked into existential.");
                }

                $bind[$v] = '%' . $rawValue . '%';
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlOp = ($operator === 'does not contain') ? 'not like' : 'like';
                $sqlParts[] = "{$fieldBinder} {$sqlOp} :{$v}:" . $orNullEmpty;
                continue;
            }

            /* ---------- STARTS / ENDS WITH ---------- */
            if (in_array($operator, [
                'starts with', 'does not start with',
                'ends with', 'does not end with',
            ], true)) {

                if ($isExistential && str_starts_with($operator, 'does not')) {
                    throw new \LogicException("Negative text operator leaked into existential.");
                }

                $bind[$v] = str_starts_with($operator, 'starts')
                    ? $rawValue . '%'
                    : '%' . $rawValue;

                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlOp = str_starts_with($operator, 'does not') ? 'not like' : 'like';
                $sqlParts[] = "{$fieldBinder} {$sqlOp} :{$v}:" . $orNullEmpty;
                continue;
            }

            /* ---------- CONTAINS WORD ---------- */
            if (in_array($operator, ['contains word', 'does not contain word'], true)) {

                if ($isExistential && $operator === 'does not contain word') {
                    throw new \LogicException("Negative word operator leaked into existential.");
                }

                $bind[$v] = '\\b' . $rawValue . '\\b';
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlOp = ($operator === 'does not contain word') ? 'not regexp' : 'regexp';
                $sqlParts[] = "{$sqlOp}({$fieldBinder}, :{$v}:)";
                continue;
            }

            /* ---------- REGEXP ---------- */
            if ($operator === 'regexp' || $operator === 'not regexp') {

                if ($isExistential && $operator === 'not regexp') {
                    throw new \LogicException("Negative regexp leaked into existential.");
                }

                $bind[$v] = $rawValue;
                $bindTypes[$v] = Column::BIND_PARAM_STR;

                $sqlParts[] = "{$operator}({$fieldBinder}, :{$v}:)";
                continue;
            }

            /* ---------- FALLBACK SCALAR ---------- */
            $bind[$v] = $rawValue;
            $bindTypes[$v] = $this->getBindTypeFromRawValue($rawValue);

            $rhs = is_array($rawValue) ? "({{$v}:array})" : ":{$v}:";
            $sqlParts[] = "{$fieldBinder} {$operator} {$rhs}";
        }

        if ($sqlParts === []) {
            return ['', [], []];
        }

        /* ==========================================================
         * GROUPING LOGIC
         *
         * SELF / INLINE:
         *  - positive semantics → OR
         *  - negative semantics → AND
         *
         * EXISTENTIAL:
         *  - ALWAYS OR (set semantics)
         * ==========================================================
         */
        $glue = $isExistential
            ? ' or '
            : ($this->isNegativeOperator($operator) ? ' and ' : ' or ');

        $sql = '(' . implode($glue, array_map(static fn($p) => "($p)", $sqlParts)) . ')';

        return [$sql, $bind, $bindTypes];
    }

    /**
     * Reduce a relationship field to its existential "universe":
     *  - keep relationship chain
     *  - drop the leaf column
     *  - keep explicit alias tokens (e.g. [a]) because they denote distinct instances
     *
     * Examples:
     *  - Comment[a].content            => Comment[a]
     *  - RecordUserStatus[c].userId    => RecordUserStatus[c]
     *  - RecordTag[18_a].Tag.label     => RecordTag[18_a].Tag
     */
    protected function getExistentialUniverseField(string $originalField): string
    {
        $field = trim($originalField);

        // Drop leaf column (everything after the last dot).
        // If there is no dot, universe is the whole field (rare but safe).
        $pos = strrpos($field, '.');
        if ($pos === false) {
            return $field;
        }

        return substr($field, 0, $pos);
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
     * Determines the semantic scope of a filter.
     *
     * Scope is a FIRST-CLASS semantic decision.
     * It MUST be resolved BEFORE any SQL is generated.
     *
     * There are only two valid scopes:
     *
     *  - "self"
     *      Predicate is row-local to the root model.
     *      Safe for inline SQL.
     *
     *  - "existential"
     *      Predicate quantifies over related rows.
     *      MUST be expressed via EXISTS / NOT EXISTS.
     *
     * This method is the SINGLE SOURCE OF TRUTH for that decision.
     *
     * Hard rules (ANY => existential):
     *  ------------------------------------------------------------
     *  1. Field contains an explicit relationship alias
     *       Example: RecordUserStatus[a].userId
     *
     *  2. Field is foreign (contains ".") AND operator is textual
     *       Text predicates on 1-N relations are never row-local.
     *
     *  3. Filter explicitly requests subquery semantics
     *       (legacy / backward compatibility)
     *
     * What this method MUST NOT do:
     *  - Guess intent
     *  - Inspect joins
     *  - Inspect SQL mode
     *  - Inspect grouping context
     *
     * @param array $filter Raw filter payload
     * @param string|null $aliasContext Current alias universe (null = root model)
     *
     * @return string Either "self" or "existential"
     *
     * @throws \LogicException If scope cannot be determined deterministically
     */
    protected function getFilterScope(array $filter, ?string $aliasContext): string
    {
        if (empty($filter['field'])) {
            throw new \LogicException('Cannot determine filter scope without field.');
        }

        $rawField = (string) $filter['field'];

        // Canonical operator (may be empty at this point)
        $operator = isset($filter['operator'])
            ? $this->normalizeFilterOperator((string) $filter['operator'])
            : '';

        /*
         * ==========================================================
         * Resolve field alias (universe)
         * ==========================================================
         *
         * Examples:
         *  - "status"                         → null
         *  - "Comment[a].content"             → "Comment[a]"
         *  - "RecordTag[18_a].Tag.label"      → "RecordTag[18_a].Tag"
         */
        [, $joinName, , $fieldAlias] = $this->splitField($rawField);
        // $fieldAlias is the alias universe of the predicate (may be null)

        /*
         * ==========================================================
         * RULE 0 — Explicit subquery always forces existential
         * ==========================================================
         *
         * Legacy escape hatch. This intentionally overrides all
         * alias-local reasoning.
         */
        if (!empty($filter['subquery'])) {
            return 'existential';
        }

        /*
         * ==========================================================
         * RULE 1 — Root-local field is always row-local
         * ==========================================================
         */
        if ($fieldAlias === null) {
            return 'self';
        }

        /*
         * ==========================================================
         * RULE 2 — Same-alias predicates are row-local
         * ==========================================================
         *
         * Examples:
         *  - aliasContext = "RecordTag[18_a]"
         *  - fieldAlias  = "RecordTag[18_a]"
         */
        if ($aliasContext !== null && $fieldAlias === $aliasContext) {
            return 'self';
        }

        /*
         * ==========================================================
         * RULE 3 — Descendant aliases are row-local inside JOIN context
         * ==========================================================
         *
         * Examples:
         *  - aliasContext = "RecordTag[18_a]"
         *  - fieldAlias  = "RecordTag[18_a].Tag"
         *
         * This preserves legacy JOIN semantics and allows
         * deep join scoping without EXISTS.
         */
        if (
            $aliasContext !== null &&
            str_starts_with($fieldAlias, $aliasContext . '.')
        ) {
            return 'self';
        }

        /*
         * ==========================================================
         * RULE 4 — Foreign TEXT predicates require existential semantics
         * ==========================================================
         *
         * Text predicates over 1-N relations are not row-local
         * when evaluated from outside their alias universe.
         */
        if (
            $operator !== '' &&
            $this->isTextOperator($operator)
        ) {
            return 'existential';
        }

        /*
         * ==========================================================
         * RULE 5 — Everything else is row-local
         * ==========================================================
         *
         * Includes:
         *  - scalar operators (=, in, between, etc.)
         *  - no-value operators (is null, is empty, etc.)
         *  - numeric comparisons on foreign keys
         */
        return 'self';
    }

    /**
     * Determine the effective logic token of a group node.
     *
     * Legacy rule:
     *  - If the first concrete field inside the group has an explicit "logic",
     *    that logic applies to the group as a whole.
     *  - Otherwise, the group has no intrinsic logic.
     */
    protected function resolveGroupCarrierLogic(array $group): ?string
    {
        foreach ($group as $node) {
            // Skip nested groups
            if (is_array($node) && isset($node[0]) && is_array($node[0])) {
                continue;
            }

            if (!empty($node['logic'])) {
                $logic = strtolower(trim((string)$node['logic']));
                if (in_array($logic, ['and', 'or', 'xor'], true)) {
                    return $logic;
                }
            }

            break; // only first concrete node matters
        }

        return null;
    }

    /**
     * Resolve the logical token ("and" | "or" | "xor") that prefixes the current fragment.
     *
     * IMPORTANT CORRECTION:
     *  - At ROOT level (level=0), the fallback logic for index 0 MUST be "and".
     *    This prevents the compiler from generating:
     *        (primary constraints) OR (rest...)
     *    which collapses the filter set to “all rows matching the first constraint”.
     *
     * Legacy behavior preserved:
     *  - Explicit payload "logic" always wins.
     *  - Nested alternation ($or toggling) remains unchanged for index >= 1
     *    and for non-root groups.
     */
    protected function resolveFilterLogicToken(array $node, int $index, bool $or): string
    {
        // 1) Explicit override always wins (strictly validated).
        $logic = $this->filter->sanitize(
            $node['logic'] ?? null,
            [Filter::FILTER_STRING, Filter::FILTER_TRIM, 'lower']
        );

        $logic = is_string($logic) ? trim($logic) : '';

        if ($logic !== '') {
            if (!in_array($logic, ['and', 'or', 'xor'], true)) {
                throw new \Exception(sprintf('Unsupported logical operator: `%s`', $logic), 400);
            }
            return $logic;
        }

        /*
         * 2) Root safety rule:
         *    If this is the first fragment of the ROOT group and logic is not explicit,
         *    force "and".
         *
         * Rationale:
         *  - The root assembler strips the first token anyway.
         *  - But if the first token is "or", the next fragment may become OR-connected
         *    to a broad base constraint (e.g., projectId), yielding “all rows”.
         */
        if ($index === 0) {
            return 'and';
        }

        /*
         * 3) Legacy fallback for non-root groups (unchanged semantics):
         *    - When $or is false: "and"
         *    - When $or is true: "or"
         */
        return $or? 'or' : 'and';
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
