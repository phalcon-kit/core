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

namespace PhalconKit\Mvc\Controller\Traits\Actions\Rest;

use Phalcon\Filter\Filter;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Support\CollectionPolicy;

trait DistinctAction
{
    use AbstractInjectable;
    use AbstractModel;
    use AbstractParams;
    use AbstractQuery;
    use AbstractRestResponse;

    /**
     * Request parameter used by {@see distinctAction()} to choose a field.
     */
    public const string DISTINCT_ACTION_FIELD_PARAMETER = 'field';

    /**
     * Controller-owned fields that may be enumerated by {@see distinctAction()}.
     */
    protected ?Collection $distinctActionFields = null;

    /**
     * Return distinct values for one explicitly allowed field.
     *
     * The action is intended for facets, autocomplete controls, and dashboard
     * filters that need the list of possible values under the same filters,
     * joins, permissions, identity conditions, binds, pagination, and cache
     * options as the normal REST query. It deliberately does not expose
     * arbitrary client-selected columns: concrete controllers must opt into
     * fields through {@see initializeDistinctActionFields()}.
     *
     * Successful responses expose:
     * - `data`: scalar distinct values returned by the database.
     * - `field`: the public field requested by the client.
     * - `count`: the number of returned values.
     *
     * @throws \Phalcon\Filter\Exception When request parameter filtering fails.
     */
    public function distinctAction(): ResponseInterface
    {
        $field = $this->getDistinctActionRequestedField();
        if ($field === null) {
            return $this->setDistinctActionErrorResponse('Distinct field is required.');
        }

        $resolvedField = $this->resolveDistinctActionField($field);
        if ($resolvedField === null) {
            return $this->setDistinctActionErrorResponse('Distinct field is not allowed.');
        }

        $values = $this->normalizeDistinctActionResult(
            $this->find($this->getDistinctActionFind($resolvedField))
        );

        $this->setRestViewVars([
            self::REST_VIEW_DATA => $values,
            self::REST_VIEW_FIELD => $field,
            self::REST_VIEW_COUNT => count($values),
        ]);

        return $this->setRestResponse(true);
    }

    /**
     * Initialize the action-level distinct field allow-list.
     *
     * The default is intentionally closed. Concrete controllers can override
     * this method and call {@see setDistinctActionFields()} with either a value
     * list (`['status']`) or a public-to-query map
     * (`['ownerEmail' => 'Owner.email']`). The map form lets applications keep
     * public API names stable while querying joined aliases internally.
     */
    public function initializeDistinctActionFields(): void
    {
        $this->setDistinctActionFields(null);
    }

    /**
     * Replace the fields that {@see distinctAction()} may enumerate.
     *
     * Passing null disables the action for every field. This is the safest
     * default for reusable framework controllers because exposing all
     * filterable columns could leak high-cardinality or sensitive values.
     */
    public function setDistinctActionFields(?Collection $distinctActionFields): void
    {
        $this->distinctActionFields = $distinctActionFields;
    }

    /**
     * Return the configured distinct action field policy.
     */
    public function getDistinctActionFields(): ?Collection
    {
        return $this->distinctActionFields;
    }

    /**
     * Check whether any distinct action fields are currently enabled.
     */
    public function hasDistinctActionFields(): bool
    {
        return $this->getDistinctActionFieldMap() !== [];
    }

    /**
     * Merge additional distinct action fields with the current policy.
     *
     * @param Collection $distinctActionFields Collection containing public field
     *     names, enabled maps, or public-to-query aliases accepted by
     *     {@see getDistinctActionFieldMap()}.
     */
    public function mergeDistinctActionFields(Collection $distinctActionFields): void
    {
        $this->distinctActionFields = CollectionPolicy::mergeNullable(
            $this->distinctActionFields,
            $distinctActionFields
        );
    }

    /**
     * Read the requested distinct field from request parameters.
     *
     * @return string|null A non-empty public field name, or null when the client
     *     did not provide one.
     *
     * @throws \Phalcon\Filter\Exception When request parameter filtering fails.
     */
    protected function getDistinctActionRequestedField(): ?string
    {
        $field = $this->getParam(
            self::DISTINCT_ACTION_FIELD_PARAMETER,
            [Filter::FILTER_STRING, Filter::FILTER_TRIM]
        );

        return is_string($field) && $field !== '' ? $field : null;
    }

    /**
     * Resolve a public distinct field to the actual query field.
     *
     * The returned value is intentionally still normalized by
     * {@see appendModelName()} when the query is built. This method only applies
     * the controller allow-list; it does not format PHQL identifiers.
     */
    protected function resolveDistinctActionField(string $field): ?string
    {
        return $this->getDistinctActionFieldMap()[$field] ?? null;
    }

    /**
     * Normalize the configured distinct field policy to public => query fields.
     *
     * Supported collection shapes:
     * - `['status', 'type']` exposes the same public and query fields.
     * - `['status' => true]` enables a field map entry.
     * - `['ownerEmail' => 'Owner.email']` exposes a public alias that queries a
     *   joined model alias.
     * - false, null, and empty-string values are ignored so controllers can
     *   disable inherited entries through merge policy.
     *
     * @return array<string, string>
     */
    protected function getDistinctActionFieldMap(): array
    {
        $fields = [];

        foreach ($this->getDistinctActionFields()?->toArray() ?? [] as $key => $value) {
            if ($value === false || $value === null || $value === '') {
                continue;
            }

            if (is_string($key) && $key !== '' && $value === true) {
                $fields[$key] = $key;
                continue;
            }

            if (is_string($key) && $key !== '' && is_string($value)) {
                $fields[$key] = $value;
                continue;
            }

            if (is_string($value)) {
                $fields[$value] = $value;
            }
        }

        return $fields;
    }

    /**
     * Build the find options used to select distinct values.
     *
     * The prepared query contributes conditions, joins, permissions, bind data,
     * pagination, and cache options. Selection-oriented keys are removed because
     * this action owns the selected column and should not inherit request
     * `column`, `columns`, `distinct`, `group`, or `having` state from list or
     * aggregate endpoints.
     *
     * @return array<string|int, mixed>
     */
    protected function getDistinctActionFind(string $field): array
    {
        $find = $this->prepareFind();
        $select = $this->appendModelName($field);

        unset(
            $find['column'],
            $find['columns'],
            $find['distinct'],
            $find['group'],
            $find['having']
        );

        $find['columns'] = 'DISTINCT ' . $select . ' AS value';
        $find['order'] = $select . ' ASC';

        return $find;
    }

    /**
     * Convert a distinct resultset into a scalar value list.
     *
     * Phalcon usually hydrates `DISTINCT ... AS value` rows as arrays with a
     * `value` key. The fallback accepts one-column rows so custom hydration
     * modes and drivers can still produce the same public response shape.
     *
     * @return list<mixed>
     */
    protected function normalizeDistinctActionResult(ResultsetInterface $resultset): array
    {
        $values = [];

        foreach ($resultset->toArray() as $row) {
            $values[] = $this->getDistinctActionRowValue($row);
        }

        return $values;
    }

    /**
     * Extract the selected value from one hydrated distinct row.
     */
    protected function getDistinctActionRowValue(mixed $row): mixed
    {
        if (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        }

        if (is_array($row)) {
            if (array_key_exists('value', $row)) {
                return $row['value'];
            }

            if (count($row) === 1) {
                return reset($row);
            }
        }

        return $row;
    }

    /**
     * Return a standard REST error response for distinct action validation.
     */
    protected function setDistinctActionErrorResponse(string $message): ResponseInterface
    {
        $this->setRestViewVar(self::REST_VIEW_MESSAGES, [$message]);

        return $this->setRestErrorResponse();
    }
}
