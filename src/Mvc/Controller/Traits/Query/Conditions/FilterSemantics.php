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

trait FilterSemantics
{
    /* =============================================================
     * Operator normalization
     * ========================================================== */

    /**
     * Normalize, validate, and canonicalize a filter operator.
     *
     * This method is the **single normalization boundary** between:
     *  - untrusted / legacy / frontend-provided operators
     *  - strict, deterministic, internal query compilation
     *
     * Design principles:
     *  - Be tolerant ONLY here
     *  - Be strict everywhere else
     *  - Never guess intent
     *  - Never allow ambiguous mappings
     *
     * Operator lifecycle:
     *  1. Frontend sends arbitrary human-readable operator
     *  2. This method normalizes it into a canonical form
     *  3. Compiler (compileSingleFilterCondition) assumes correctness
     *
     * Categories:
     *  - Native SQL operators        → passed through
     *  - Extended semantic operators → rewritten later by compiler
     *  - Unknown / ambiguous         → rejected
     *
     * @param string $operator Raw operator provided by client / frontend
     * @return string Canonical operator, or empty string if unsupported
     */
    public function normalizeFilterOperator(string $operator): string
    {
        /* ==============================================================
         * 1. STRUCTURAL NORMALIZATION
         * ==============================================================
         * Normalize casing and whitespace early so all comparisons
         * are deterministic and cheap.
         */
        $operator = strtolower(trim($operator));
        $operator = preg_replace('/\s+/', ' ', $operator);

        /* ==============================================================
         * 2. ALIAS & TOLERANCE MAP
         * ==============================================================
         * This map exists to absorb:
         *  - legacy frontend wording
         *  - grammatical mistakes
         *  - UX-friendly phrasing
         *
         * Rules:
         *  - Every alias MUST map to exactly one canonical operator
         *  - No lossy mappings
         *  - No semantic guessing
         *
         * If intent is unclear → DO NOT ADD IT HERE
         */
        $aliasMap = [

            /* ---------- Equality / comparison ---------- */
            'equals' => '=',
            'equal to' => '=',

            'not equal' => '!=',
            'not equal to' => '!=',
            'does not equal' => '!=',

            'different than' => '<>',

            'greater than' => '>',
            'greater then' => '>',   // common typo
            'higher than' => '>',
            'bigger than' => '>',

            'less than' => '<',
            'lower than' => '<',
            'smaller than' => '<',

            'greater than or equal' => '>=',
            'at least' => '>=',

            'less than or equal' => '<=',
            'at most' => '<=',

            'null-safe equal' => '<=>',

            /* ---------- String containment semantics ---------- */
            'contain' => 'contains',
            'contains' => 'contains',

            'does not contain' => 'does not contain',

            'contain word' => 'contains word',
            'contains word' => 'contains word',
            'does not contain word' => 'does not contain word',

            /* ---------- Prefix / suffix semantics ---------- */
            'start with' => 'starts with',
            'starts with' => 'starts with',

            'does not start with' => 'does not start with',
            'does not starts with' => 'does not start with', // grammar error, tolerated

            'end with' => 'ends with',
            'ends with' => 'ends with',

            'does not end with' => 'does not end with',
            'does not ends with' => 'does not end with',    // grammar error, tolerated

            /* ---------- Regex ---------- */
            'regex' => 'regexp',
            'match' => 'regexp',
            'not regex' => 'not regexp',
            'not match' => 'not regexp',
            'does not match' => 'not regexp',

            /* ---------- Emptiness ---------- */
            'empty' => 'is empty',
            'not empty' => 'is not empty',
        ];

        $operator = $aliasMap[$operator] ?? $operator;

        /* ==============================================================
         * 3. NATIVE SQL OPERATORS
         * ==============================================================
         * These operators are directly understood by MySQL / PHQL.
         * They are passed through verbatim and never rewritten.
         */
        $nativeOperators = [
            '=', '!=', '<>', '>', '>=', '<', '<=', '<=>',
            'in', 'not in',
            'like', 'not like',
            'between', 'not between',
            'is', 'is not',
            'is null', 'is not null',
            'is true', 'is not true',
            'is false', 'is not false',
        ];

        if (in_array($operator, $nativeOperators, true)) {
            return $operator;
        }

        /* ==============================================================
         * 4. EXTENDED / SEMANTIC OPERATORS
         * ==============================================================
         * These operators are NOT SQL-native.
         * They express intent and are rewritten later by the compiler.
         *
         * IMPORTANT:
         *  - compileSingleFilterCondition MUST handle ALL of these
         *  - No other operator should reach that method
         */
        $extendedOperators = [
            'starts with',
            'does not start with',
            'ends with',
            'does not end with',

            'contains',
            'does not contain',

            'contains word',
            'does not contain word',

            'regexp',
            'not regexp',

            'distance sphere greater than',
            'distance sphere greater than or equal',
            'distance sphere less than',
            'distance sphere less than or equal',

            'is empty',
            'is not empty',
        ];

        if (in_array($operator, $extendedOperators, true)) {
            return $operator;
        }

        /* ==============================================================
         * 5. REJECTION
         * ==============================================================
         * Anything reaching here is:
         *  - unknown
         *  - ambiguous
         *  - unsupported
         *
         * Caller must treat empty string as invalid operator.
         */
        return '';
    }

    /* =============================================================
     * Operator classification
     * ========================================================== */

    /**
     * Determines whether an operator represents a syntactic negation.
     *
     * IMPORTANT:
     *  - This is NOT a semantic classifier
     *  - This MUST NOT be used for EXISTS / NOT EXISTS decisions
     *  - Intended usage: local grouping glue (AND vs OR)
     *
     * @param string $operator The operator to evaluate.
     * @return bool Returns true if the operator is a negative operator, false otherwise.
     */
    protected function isNegativeOperator(string $operator): bool
    {
        $operator = strtolower(trim($operator));
        return str_contains($operator, 'not') || in_array($operator, ['!=', '<>'], true);
    }

    /**
     * Determines whether the given operator represents a **textual pattern predicate**.
     *
     * A "text operator" is defined as an operator that:
     *  - operates on string patterns (LIKE / REGEXP semantics)
     *  - is NOT atomic over 1-N relations (i.e. cannot be reasoned per joined row)
     *  - therefore may require existential semantics (EXISTS / NOT EXISTS)
     *    to preserve correctness when filtering parent entities
     *
     * This classification is **domain-based**, NOT polarity-based.
     * It answers WHAT kind of predicate this is — not whether it is negated.
     *
     * Why this distinction matters:
     *  - Text predicates can produce false positives / false negatives
     *    when evaluated inline on LEFT JOINs
     *  - Scalar predicates (=, !=, IN, etc.) do not suffer from this problem
     *  - Unary predicates (IS NULL, IS EMPTY, etc.) are row-local and must stay inline
     *
     * Examples (text operators):
     *  - contains
     *  - does not contain
     *  - starts with
     *  - does not start with
     *  - ends with
     *  - does not end with
     *  - contains word
     *  - does not contain word
     *  - regexp
     *  - not regexp
     *
     * Non-examples (NOT text operators):
     *  - =, !=, <>, IN, NOT IN        (scalar comparisons)
     *  - is null, is empty, is true  (unary / semantic operators)
     *  - between                     (range predicate)
     *
     * Design rules:
     *  - Caller MUST pass a canonical operator (output of getFilterOperator)
     *  - This method MUST be deterministic
     *  - This method MUST NOT infer semantics via substrings
     *  - This method MUST NOT consider polarity
     *
     * @param string $operator Canonical operator (already normalized)
     * @return bool True if operator is a textual pattern predicate
     */
    protected function isTextOperator(string $operator): bool
    {
        $operator = strtolower(trim($operator));

        /*
         * Explicit whitelist of textual semantics.
         *
         * IMPORTANT:
         *  - This list defines the *semantic domain* of the operator
         *  - Do NOT include scalar negations (!=, not in)
         *  - Do NOT include unary semantic operators (is empty, is null)
         *  - Every operator here represents a pattern match on strings
         */
        return in_array($operator, [
            'contains',
            'does not contain',

            'starts with',
            'does not start with',

            'ends with',
            'does not end with',

            'contains word',
            'does not contain word',

            'regexp',
            'not regexp',
        ], true);
    }

    /**
     * Determines whether the given operator represents a **negative textual predicate**.
     *
     * A "negative text operator" is defined as:
     *  - a textual pattern predicate (see isTextOperator)
     *  - that expresses the *absence* of a pattern rather than its presence
     *
     * This distinction is CRITICAL for correctness:
     *  - SQL semantics: `NULL NOT LIKE '%x%'` evaluates to NULL (filtered out)
     *  - Expected semantics: parent rows with NO related child rows
     *    must still be INCLUDED
     *
     * Therefore:
     *  - Inline evaluation of negative text predicates on LEFT JOINs
     *    is NOT logically equivalent to the intended filter
     *  - Such predicates often require NOT EXISTS rewrites
     *
     * Examples (negative text operators):
     *  - does not contain
     *  - does not start with
     *  - does not end with
     *  - does not contain word
     *  - not regexp
     *
     * Non-examples (MUST return false):
     *  - !=, <>                  (scalar negation, not textual)
     *  - not in                  (set negation, handled differently)
     *  - is not null             (explicit NULL semantics already encoded)
     *  - is not empty            (unary semantic operator)
     *
     * Design rules:
     *  - Caller MUST pass a canonical operator (getFilterOperator output)
     *  - This method MUST be deterministic
     *  - This method MUST NOT guess negativity via substring checks alone
     *  - This method MUST NOT classify non-text operators
     *
     * @param string $operator Canonical operator (already normalized)
     * @return bool True if operator is a negative textual predicate
     */
    protected function isNegativeTextOperator(string $operator): bool
    {
        $operator = strtolower(trim($operator));

        /*
         * Polarity only applies within the textual domain.
         * If this is not a text operator, it CANNOT be a negative text operator.
         */
        if (!$this->isTextOperator($operator)) {
            return false;
        }

        /*
         * Explicit negative polarity detection.
         *
         * We rely on canonical forms produced by getFilterOperator(),
         * so prefix checks are safe and deterministic here.
         */
        return str_starts_with($operator, 'does not ')
            || str_starts_with($operator, 'not ');
    }

    /**
     * Determines if the given operator is a "no-value" operator.
     * A "no-value" operator does not require an accompanying value for evaluation.
     * This method supports both raw and extended operator sets based on the provided flags.
     *
     * @param string $operator The operator to be checked, which can include phrases
     *                         like 'is null', 'is true', 'is empty', etc.
     * @param bool $raw Indicates whether to consider raw operators (e.g., 'is null', 'is true').
     *                  Defaults to true.
     * @param bool $extended Indicates whether to include extended operators (e.g., 'is empty', 'is not empty').
     *                       Defaults to true.
     * @return bool Returns true if the operator is a recognized "no-value" operator based on
     *              the provided flags; otherwise, returns false.
     */
    protected function isNoValueOperator(string $operator, bool $raw = true, bool $extended = true): bool
    {
        if ($raw && in_array($operator, [
                'is null',
                'is not null',
                'is true',
                'is not true',
                'is false',
                'is not false',
            ], true)) {
            return true;
        }

        if ($extended && in_array($operator, [
                'is empty',
                'is not empty',
            ])) {
            return true;
        }

        return false;
    }

    /* =============================================================
     * Field / policy validation
     * ========================================================== */

    /**
     * Determines if a given field is allowed to be used as a filter.
     * This method verifies whether the field is explicitly allowed,
     * matches dynamic join criteria, or is configured within the allowed filters.
     *
     * @param string $field The field to be checked for filter permission.
     * @param array|null $allowedFilters An array of explicitly allowed filters or
     *                                    filter configurations. Null or empty array
     *                                    implies no filters are allowed.
     * @return bool Returns true if the field is allowed to be used as a filter,
     *              otherwise false.
     */
    public function isFilterAllowed(string $field, ?array $allowedFilters): bool
    {
        if (empty($allowedFilters)) {
            return false;
        }

        // field is explicitly allowed
        if (in_array($field, $allowedFilters, true)) {
            return true;
        }

        if ($allowedFilters[$field] ?? false) {
            return true;
        }

        // field is allowed through dynamic join
        if ($this->isJoinFilterAllowed($field, $allowedFilters)) {
            return true;
        }

        return false;
    }

    /**
     * Determines if a join filter is allowed based on the given field and a list of allowed filters.
     *
     * This method checks the provided field against the allowed filters, optionally
     * normalizing the field by removing segments wrapped in square brackets.
     *
     * @param string $field The field to be checked, potentially including syntax for joins
     *                      or relationships (e.g., dot notation or square bracket syntax).
     * @param array|null $allowedFilters An array of allowed filters to validate against. Can be null.
     * @return bool True if the field is allowed based on the provided filters, false otherwise.
     */
    public function isJoinFilterAllowed(string $field, ?array $allowedFilters): bool
    {
        if (empty($allowedFilters)) {
            return false;
        }

        // @todo see if we should check if the join exists, not sure if we should
//        $joinName = substr($field, 0, strrpos($field, '.'));
//        if (!$this->getJoins()->has($joinName)) {
//            return false;
//        }

        // might have a defined relationship
        $filteredField = preg_replace('/\[[^\]]*\](?=\.)/', '', $field);
        if (in_array($filteredField, $allowedFilters, true)) {
            return true;
        }

        return $allowedFilters[$filteredField] ?? false;
    }
}
