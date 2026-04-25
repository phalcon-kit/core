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
use Phalcon\Filter\Filter;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields\AbstractSearchFields;
use PhalconKit\Support\Helper\Arr\FlattenKeys;

/**
 * Search-based query condition provider.
 *
 * PURPOSE
 * -------
 * This trait is responsible for producing SQL search conditions
 * based on a free-text `search` parameter and a declarative list
 * of searchable fields.
 *
 * It does NOT:
 *  - Rank results
 *  - Perform relevance scoring
 *  - Apply database-specific full-text features
 *
 * It ONLY:
 *  - Expands search terms into LIKE expressions
 *  - Applies strict AND / OR grouping semantics
 *  - Produces a compiler-safe condition payload
 *
 * CONDITION CONTRACT
 * ------------------
 * All conditions produced by this trait MUST follow this shape:
 *
 *  [
 *      0 => string  SQL condition fragment (parenthesized),
 *      1 => array   bind values,
 *      2 => array   bind types,
 *  ]
 *
 * Returning `null` ALWAYS means:
 *  → "No search restriction should be applied"
 *
 * This invariant is relied upon by the query compiler.
 */
trait SearchConditions
{
    use AbstractParams;
    use AbstractModel;
    use AbstractQuery;
    use AbstractSearchFields;

    /**
     * Registered search condition sets.
     *
     * This collection allows multiple named search strategies
     * to coexist (e.g. default, advanced, scoped, etc.).
     *
     * Keys:
     *  - symbolic identifiers
     * Values:
     *  - condition payloads OR lazy builders
     */
    protected ?Collection $searchConditions = null;

    /**
     * Initialize search conditions.
     *
     * Called during controller / query bootstrap.
     *
     * The default search condition is eagerly built to ensure:
     *  - deterministic behavior
     *  - no hidden runtime branching
     */
    public function initializeSearchConditions(): void
    {
        $this->searchConditions = new Collection(
            [
                'default' => $this->buildDefaultSearchCondition(),
            ],
            false
        );
    }

    /**
     * Replace the entire search condition collection.
     *
     * Used by consumers that want full control over
     * how search conditions are produced.
     */
    public function setSearchConditions(?Collection $searchConditions): void
    {
        $this->searchConditions = $searchConditions;
    }

    /**
     * Retrieve the registered search conditions.
     */
    public function getSearchConditions(): ?Collection
    {
        return $this->searchConditions;
    }

    /**
     * Build the default search condition.
     *
     * SEMANTICS
     * ---------
     * Given:
     *   search = "foo bar"
     *   fields = [title, description]
     *
     * Resulting logic:
     *
     *   (
     *     (title LIKE '%foo%' OR description LIKE '%foo%')
     *     AND
     *     (title LIKE '%bar%' OR description LIKE '%bar%')
     *   )
     *
     * This ensures:
     *  - Every term MUST match at least one field
     *  - Multiple terms narrow results (AND)
     *
     * @return array|null
     */
    public function buildDefaultSearchCondition(): ?array
    {
        // Normalize and extract search tokens from request
        $searchTerms = $this->extractSearchTerms();
        if ($searchTerms === []) {
            // No search input → no restriction
            return null;
        }

        // Retrieve declared searchable fields
        $searchFields = $this->getSearchFields()?->toArray() ?? [];
        if ($searchFields === []) {
            // Search requested but no searchable fields exist
            return null;
        }

        $bind = [];
        $bindTypes = [];
        $groups = [];

        // Each search term becomes an OR-group across fields
        foreach ($searchTerms as $term) {
            $group = $this->buildSearchTermGroup(
                $term,
                $searchFields,
                $bind,
                $bindTypes
            );

            // Only include non-empty groups
            if ($group !== []) {
                $groups[] = '(' . implode(' OR ', $group) . ')';
            }
        }

        // If nothing survived normalization, abort cleanly
        if ($groups === []) {
            return null;
        }

        // AND-coalesce all term groups
        return [
            '(' . implode(' AND ', $groups) . ')',
            $bind,
            $bindTypes,
        ];
    }

    /**
     * Build an OR-group for a single search term.
     *
     * Each enabled searchable field produces:
     *   field LIKE '%term%'
     *
     * Flattening is required because search fields may be
     * declared in nested / relational form.
     *
     * @param string $term
     * @param array  $searchFields
     * @param array  $bind       Accumulator for bind values
     * @param array  $bindTypes  Accumulator for bind types
     *
     * @return string[] List of SQL expressions
     */
    public function buildSearchTermGroup(
        string $term,
        array $searchFields,
        array &$bind,
        array &$bindTypes
    ): array {
        $expressions = [];

        // Flatten nested field definitions into dot-notation keys
        $flattened = FlattenKeys::process($searchFields) ?? [];
        foreach ($flattened as $fieldName => $enabled) {
            // Skip disabled or malformed entries
            if (!$enabled || !is_string($fieldName) || $fieldName === '') {
                continue;
            }

            // Fully-qualified model field
            $field = $this->appendModelName($fieldName);

            // Generate unique, collision-safe bind key
            $bindKey = $this->generateBindKey('search');

            $expressions[] = "{$field} LIKE :{$bindKey}:";
            $bind[$bindKey] = '%' . $term . '%';
            $bindTypes[$bindKey] = Column::BIND_PARAM_STR;
        }

        return $expressions;
    }

    /**
     * Extract normalized search terms from request parameters.
     *
     * NORMALIZATION RULES
     * -------------------
     *  - Input is treated as free text
     *  - Whitespace is collapsed
     *  - Empty tokens are discarded
     *  - Duplicate terms are removed
     *  - Original order is preserved
     *
     * This method is intentionally isolated so that
     * search tokenization can evolve independently
     * of SQL generation.
     */
    public function extractSearchTerms(): array
    {
        $raw = $this->getParam(
            'search',
            [
                Filter::FILTER_STRING,
                Filter::FILTER_TRIM,
            ]
        );

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        // Split on any whitespace sequence
        $parts = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [];
        }

        // Deduplicate while preserving order
        return array_values(array_unique($parts));
    }
}
