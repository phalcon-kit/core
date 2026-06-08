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

use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Support\Collection;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Support\CollectionPolicy;

trait CountAction
{
    use AbstractInjectable;
    use AbstractParams;
    use AbstractQuery;
    use AbstractRestResponse;

    /**
     * Request parameter used by count actions to opt into count metadata.
     */
    public const string COUNT_ACTION_COUNT_PARAMETER = 'count';

    /**
     * Response field containing the raw grouped count result.
     */
    public const string COUNT_RESPONSE_GROUPED_COUNT = 'groupedCount';

    /**
     * Response field containing the sum of grouped count buckets.
     */
    public const string COUNT_RESPONSE_BUCKET_TOTAL = 'bucketTotal';

    /**
     * Response field containing a separate ungrouped count query.
     */
    public const string COUNT_RESPONSE_TOTAL_COUNT = 'totalCount';

    /**
     * Opt-in extra response fields added by {@see countAction()}.
     */
    protected ?Collection $countActionResponseFields = null;

    /**
     * Return the count for the current REST query.
     *
     * The response variable is named `count`. When the underlying query uses a
     * group clause, native Phalcon may return grouped count rows instead of a
     * scalar total; callers should treat this action as a thin REST wrapper
     * around the controller query contract.
     */
    public function countAction(): ResponseInterface
    {
        $count = $this->count();

        $this->setRestViewVar(self::REST_VIEW_COUNT, $count);
        $this->setCountActionResponseFieldValues($count);

        return $this->setRestResponse(true);
    }

    /**
     * Initialize extra count response fields during the REST controller setup.
     *
     * The default keeps the public `count` response unchanged. Concrete
     * controllers can override this initializer and call
     * {@see setCountActionResponseFields()} or
     * {@see mergeCountActionResponseFields()} with the `COUNT_RESPONSE_*`
     * constants when they need dashboard/facet metadata.
     */
    public function initializeCountActionResponseFields(): void
    {
        $this->setCountActionResponseFields(null);
    }

    /**
     * Replace the extra response fields emitted by {@see countAction()}.
     *
     * Passing null means no extra fields, preserving the legacy count response.
     */
    public function setCountActionResponseFields(?Collection $countActionResponseFields): void
    {
        $this->countActionResponseFields = $countActionResponseFields;
    }

    /**
     * Return the configured extra count response fields.
     */
    public function getCountActionResponseFields(): ?Collection
    {
        return $this->countActionResponseFields;
    }

    /**
     * Check whether the controller has opted into extra count response fields.
     */
    public function hasCountActionResponseFields(): bool
    {
        return $this->getCountActionResponseFieldNames() !== [];
    }

    /**
     * Merge extra count response fields with the current field collection.
     *
     * @param Collection $countActionResponseFields Collection containing field
     *     names, usually the `COUNT_RESPONSE_*` constants.
     */
    public function mergeCountActionResponseFields(Collection $countActionResponseFields): void
    {
        $this->countActionResponseFields = CollectionPolicy::mergeNullable(
            $this->countActionResponseFields,
            $countActionResponseFields
        );
    }

    /**
     * Add opt-in grouped-count fields to the response view.
     *
     * `groupedCount` is the raw result returned by the normal count query,
     * `bucketTotal` is only the sum of those returned buckets, and `totalCount`
     * runs a second count query with the group clause removed. Keeping these as
     * separate names prevents bucket totals from being mistaken for unique root
     * record totals on joined/grouped endpoints.
     *
     * @throws HttpException When the client requests an unsupported or
     *     disallowed count field.
     */
    protected function setCountActionResponseFieldValues(ResultsetInterface|int|false $count): void
    {
        $fields = array_fill_keys($this->getCountActionSelectedResponseFieldNames(), true);

        if (isset($fields[self::COUNT_RESPONSE_GROUPED_COUNT])) {
            $this->setRestViewVar(self::COUNT_RESPONSE_GROUPED_COUNT, $count);
        }

        if (isset($fields[self::COUNT_RESPONSE_BUCKET_TOTAL])) {
            $this->setRestViewVar(self::COUNT_RESPONSE_BUCKET_TOTAL, $this->getCountActionBucketTotal($count));
        }

        if (isset($fields[self::COUNT_RESPONSE_TOTAL_COUNT])) {
            $this->setRestViewVar(self::COUNT_RESPONSE_TOTAL_COUNT, $this->count($this->getCountActionTotalFind()));
        }
    }

    /**
     * Normalize the enabled extra count response field names.
     *
     * Collections can be configured either as value lists, for example
     * `[self::COUNT_RESPONSE_TOTAL_COUNT]`, or as enabled maps, for example
     * `[self::COUNT_RESPONSE_TOTAL_COUNT => true]`. Enabled-map values are
     * interpreted with {@see CollectionPolicy::isEnabledValue()}, so config
     * values such as `1`, `'1'`, and `'yes'` enable the key while `0`, `'0'`,
     * `'false'`, `'no'`, and `'off'` disable it.
     *
     * @return list<string>
     */
    protected function getCountActionResponseFieldNames(): array
    {
        $fields = [];

        foreach ($this->getCountActionResponseFields()?->toArray() ?? [] as $key => $value) {
            if (is_string($key)) {
                if ($key !== '' && CollectionPolicy::isEnabledValue($value)) {
                    $fields[] = $key;
                }
                continue;
            }

            if (is_string($value)) {
                $fields[] = $value;
            }
        }

        return array_values(array_unique($fields));
    }

    /**
     * Return the configured and requested extra count response fields.
     *
     * The native `count` field is always emitted by {@see countAction()}, so
     * client requests for `count` are valid but do not add another response
     * field. Requests for optional metadata are checked against the configured
     * policy. A null policy is unrestricted across supported count fields,
     * while an empty collection blocks every optional requested field.
     *
     * @return list<string>
     *
     * @throws HttpException When the client requests a disallowed count field.
     */
    protected function getCountActionSelectedResponseFieldNames(): array
    {
        $fields = $this->getCountActionResponseFieldNames();
        $requestedFields = $this->getCountActionRequestedResponseFieldNames();

        if ($requestedFields === []) {
            return $fields;
        }

        $allowedFields = array_fill_keys($this->getCountActionAllowedResponseFieldNames(), true);
        foreach ($requestedFields as $field) {
            if (!isset($allowedFields[$field])) {
                throw new HttpException(sprintf('Unauthorized count response field "%s".', $field), 403);
            }
        }

        return $this->normalizeCountActionCountFieldList(array_merge(
            $fields,
            array_values(array_filter(
                $requestedFields,
                static fn (string $field): bool => $field !== self::REST_VIEW_COUNT
            ))
        ));
    }

    /**
     * Return count fields accepted by the current count-action policy.
     *
     * @return list<string>
     */
    protected function getCountActionAllowedResponseFieldNames(): array
    {
        if ($this->getCountActionResponseFields() === null) {
            return $this->getCountActionSupportedResponseFieldNames();
        }

        return $this->normalizeCountActionCountFieldList(array_merge(
            [self::REST_VIEW_COUNT],
            $this->getCountActionResponseFieldNames()
        ));
    }

    /**
     * Return the built-in count response field names that can be requested.
     *
     * @return list<string>
     */
    protected function getCountActionSupportedResponseFieldNames(): array
    {
        return [
            self::REST_VIEW_COUNT,
            self::COUNT_RESPONSE_GROUPED_COUNT,
            self::COUNT_RESPONSE_BUCKET_TOTAL,
            self::COUNT_RESPONSE_TOTAL_COUNT,
        ];
    }

    /**
     * Return count response fields requested through the `count` parameter.
     *
     * Supported request shapes:
     * - `?count=1` or `?count=true` requests the native `count` field.
     * - `?count=count,totalCount` requests named fields.
     * - `?count[]=count&count[]=totalCount` requests named fields as a list.
     * - `?count[totalCount]=1` requests named fields as an enabled map.
     *
     * @return list<string>
     *
     * @throws HttpException When the parameter has an unsupported type.
     */
    protected function getCountActionRequestedResponseFieldNames(): array
    {
        return $this->normalizeCountActionRequestedCountFields(
            $this->getParam(self::COUNT_ACTION_COUNT_PARAMETER)
        );
    }

    /**
     * Normalize a client `count` request value to field names.
     *
     * @return list<string>
     *
     * @throws HttpException When the parameter has an unsupported type.
     */
    protected function normalizeCountActionRequestedCountFields(mixed $requested): array
    {
        if ($requested === null || $requested === false || $requested === 0) {
            return [];
        }

        if ($requested === true || is_int($requested)) {
            return [self::REST_VIEW_COUNT];
        }

        if (is_string($requested)) {
            return $this->normalizeCountActionRequestedCountString($requested);
        }

        if (!is_array($requested)) {
            throw new HttpException(sprintf('Invalid type for "count" parameter: expected null, bool, string, or array, got %s.', gettype($requested)), 400);
        }

        $fields = [];
        foreach ($requested as $key => $value) {
            if (is_string($key)) {
                if ($this->isCountActionCountEnabledValue($value)) {
                    $fields[] = trim($key);
                }
                continue;
            }

            if ($value === true || (is_int($value) && $value !== 0)) {
                $fields[] = self::REST_VIEW_COUNT;
                continue;
            }

            if (is_string($value)) {
                $fields = array_merge($fields, $this->normalizeCountActionRequestedCountString($value));
            }
        }

        return $this->normalizeCountActionCountFieldList($fields);
    }

    /**
     * Normalize a scalar `count` request value.
     *
     * @return list<string>
     */
    protected function normalizeCountActionRequestedCountString(string $requested): array
    {
        $requested = trim($requested);
        if ($requested === '' || !$this->isCountActionCountEnabledValue($requested)) {
            return [];
        }

        if ($this->isCountActionCountTruthyString($requested)) {
            return [self::REST_VIEW_COUNT];
        }

        return $this->normalizeCountActionCountFieldList(explode(',', $requested));
    }

    /**
     * Check whether an enabled-map `count[field]` value should request a field.
     */
    protected function isCountActionCountEnabledValue(mixed $value): bool
    {
        return CollectionPolicy::isEnabledValue($value);
    }

    /**
     * Check whether a scalar string requests the default native count field.
     */
    protected function isCountActionCountTruthyString(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Trim, de-duplicate, and drop empty count field names.
     *
     * @param array<int, mixed> $fields Raw field fragments.
     * @return list<string>
     */
    protected function normalizeCountActionCountFieldList(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            if (!is_scalar($field)) {
                continue;
            }

            $field = trim((string)$field);
            if ($field !== '') {
                $normalized[] = $field;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Build the ungrouped count query used by `totalCount`.
     *
     * The query keeps the same filters, joins, permissions, binds, and bind
     * types as the normal count query, but removes the group clause so Phalcon
     * returns an ungrouped total. Controllers with custom aggregate columns can
     * override this method if their total query needs a different column.
     *
     * @return array<string|int, mixed>
     */
    protected function getCountActionTotalFind(): array
    {
        $find = $this->getCalculationFind($this->prepareFind());
        unset($find['group']);

        return $find;
    }

    /**
     * Sum the row-count buckets returned by a grouped count query.
     *
     * This value is deliberately named `bucketTotal`, not `total`, because
     * joined grouped counts can count the same root record in several buckets.
     * If a row does not expose a recognizable numeric count column, the method
     * returns false instead of guessing.
     */
    protected function getCountActionBucketTotal(ResultsetInterface|int|false $count): int|float|false
    {
        if ($count === false || is_int($count)) {
            return $count;
        }

        $total = 0;
        $found = false;

        foreach ($count->toArray() as $row) {
            $value = $this->getCountActionBucketValue($row);
            if ($value === null) {
                return false;
            }

            $found = true;
            $total = is_float($total) || is_float($value)
                ? (float)$total + (float)$value
                : $total + $value;
        }

        return $found ? $total : 0;
    }

    /**
     * Read one numeric count value from a grouped count row.
     *
     * Phalcon commonly exposes grouped `count()` values as `rowcount`, but
     * hydration mode and custom columns can produce arrays with another count
     * key. Known count keys are preferred; a one-column numeric row is accepted
     * as a fallback for custom result rows. Multi-column rows without a known
     * count key are rejected so numeric group keys are not accidentally summed.
     */
    protected function getCountActionBucketValue(mixed $row): int|float|null
    {
        if (is_int($row) || is_float($row)) {
            return $row;
        }

        if (is_object($row) && method_exists($row, 'toArray')) {
            $row = $row->toArray();
        }

        if (!is_array($row)) {
            return null;
        }

        foreach (['rowcount', self::REST_VIEW_COUNT, 'COUNT(*)', 'count(*)', 'total'] as $key) {
            if (isset($row[$key])) {
                return $this->normalizeCountActionBucketNumber($row[$key]);
            }
        }

        if (count($row) === 1) {
            $value = reset($row);
            return $this->normalizeCountActionBucketNumber($value);
        }

        return null;
    }

    /**
     * Normalize one grouped count bucket value.
     *
     * Numeric strings are common when database drivers hydrate aggregate
     * columns. Preserve integer-like values as integers and decimal/scientific
     * values as floats so API payloads stay natural without relying on implicit
     * arithmetic casts.
     */
    protected function normalizeCountActionBucketNumber(mixed $value): int|float|null
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (!is_string($value) || !is_numeric($value)) {
            return null;
        }

        $trimmed = trim($value);
        return preg_match('/^-?\d+$/', $trimmed) === 1 ? (int)$trimmed : (float)$trimmed;
    }
}
