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

use Phalcon\Filter\Exception as FilterException;
use Phalcon\Http\ResponseInterface;
use Phalcon\Support\Collection;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExpose;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Support\CollectionPolicy;

trait FindAction
{
    use AbstractExpose;
    use AbstractParams;
    use AbstractQuery;
    use AbstractInjectable;
    use AbstractRestResponse;

    /**
     * Request parameter used by list actions to opt into count metadata.
     */
    public const string FIND_ACTION_COUNT_PARAMETER = 'count';

    /**
     * Controller-owned policy for count fields embeddable in list actions.
     *
     * A null policy mirrors the other REST field policies and means clients may
     * request any supported framework count field. A non-null collection turns
     * on explicit allow-list mode, where an empty collection blocks every
     * embedded list-count field.
     */
    protected ?Collection $findActionCountFields = null;
    
    /**
     * Legacy alias for `findAction()`.
     *
     * @deprecated since PhalconKit 1.0, use findAction() instead.
     */
    public function getAllAction(): ResponseInterface
    {
        return $this->findAction();
    }
    
    /**
     * Legacy alias for `findWithAction()`.
     *
     * @deprecated since PhalconKit 1.0, use findWithAction() instead.
     */
    public function getAllWithAction(): ResponseInterface
    {
        return $this->findWithAction();
    }
    
    /**
     * Find and expose records matching the prepared REST query.
     *
     * The `data` response variable receives the exposed result list. Query
     * preparation is delegated to the shared query trait, so filters, fields,
     * permissions, identity constraints, ordering, limits, and joins stay
     * consistent across REST list endpoints.
     */
    public function findAction(): ResponseInterface
    {
        $this->setRestViewVar(self::REST_VIEW_DATA, $this->listExpose($this->find()));
        $this->setFindActionCountFieldValues();

        return $this->setRestResponse(true);
    }
    
    /**
     * Find records with eager-loaded relationships and expose the result list.
     *
     * When the client does not send the `with` parameter, relationships are
     * resolved from the controller's configured eager-load graph. When the
     * client sends `with`, only the requested, controller-approved subset is
     * loaded. The exposed response shape remains the same as `findAction()`,
     * with related data included where the eager-load graph permits it.
     *
     * @throws FilterException When request parameter filtering fails.
     * @throws HttpException When a requested relationship is not allowed.
     */
    public function findWithAction(): ResponseInterface
    {
        $this->setRestViewVar(self::REST_VIEW_DATA, $this->listExpose($this->findWith($this->getRequestedWith())));
        $this->setFindActionCountFieldValues();

        return $this->setRestResponse(true);
    }

    /**
     * Initialize list-action count metadata policy.
     *
     * The default is null, which preserves normal list payloads until a client
     * asks for count metadata through {@see FIND_ACTION_COUNT_PARAMETER}. When
     * requested, null allows any supported framework count field, matching the
     * unrestricted behavior used by other REST field policies.
     */
    public function initializeFindActionCountFields(): void
    {
        $this->setFindActionCountFields(null);
    }

    /**
     * Replace the count fields that list actions may embed.
     *
     * Supported field names are:
     * - `count`: the native count query result, matching {@see countAction()}.
     * - `groupedCount`: the raw grouped count result.
     * - `bucketTotal`: the sum of recognized grouped count buckets.
     * - `totalCount`: a separate ungrouped count query.
     *
     * Passing null leaves the policy unrestricted for supported framework count
     * fields. Passing an empty collection enables allow-list mode but allows no
     * embedded list counts, which is useful for controllers that must reject
     * every client-requested list count.
     */
    public function setFindActionCountFields(?Collection $findActionCountFields): void
    {
        $this->findActionCountFields = $findActionCountFields;
    }

    /**
     * Return the configured list-action count field policy.
     *
     * A null return value means unrestricted supported count fields. A non-null
     * collection is normalized by {@see getFindActionCountFieldNames()} before
     * request fields are accepted.
     */
    public function getFindActionCountFields(): ?Collection
    {
        return $this->findActionCountFields;
    }

    /**
     * Check whether list-action count allow-list mode is configured.
     *
     * This reports policy presence, not whether counts are available. A false
     * result means the policy is unrestricted for supported count fields, while
     * an empty non-null collection means every requested count field is denied.
     */
    public function hasFindActionCountFields(): bool
    {
        return $this->findActionCountFields !== null;
    }

    /**
     * Merge additional list-action count fields with the current policy.
     *
     * The collection accepts the same value-list and enabled-map shapes as
     * count action response fields, for example `[self::REST_VIEW_COUNT]` or
     * `[self::COUNT_RESPONSE_TOTAL_COUNT => true]`. Merging into a null policy
     * creates the first explicit allow-list; it does not need a separate setter
     * call in controller initialization.
     */
    public function mergeFindActionCountFields(Collection $findActionCountFields): void
    {
        $this->findActionCountFields = CollectionPolicy::mergeNullable(
            $this->findActionCountFields,
            $findActionCountFields
        );
    }

    /**
     * Add requested, allowed count metadata to the list response view.
     *
     * Normal `count`, `groupedCount`, and `bucketTotal` use the standard count
     * query, which honors filters/search/joins/permissions and removes
     * pagination through the shared query helper. `totalCount` runs the
     * ungrouped count query used by {@see countAction()}.
     *
     * @throws FilterException When request parameter filtering fails.
     * @throws HttpException When the client requests an unsupported or
     *     disallowed count field.
     */
    protected function setFindActionCountFieldValues(): void
    {
        $fields = array_fill_keys($this->getFindActionRequestedCountFieldNames(), true);
        if ($fields === []) {
            return;
        }

        $count = false;
        if (
            isset($fields[self::REST_VIEW_COUNT])
            || isset($fields[self::COUNT_RESPONSE_GROUPED_COUNT])
            || isset($fields[self::COUNT_RESPONSE_BUCKET_TOTAL])
        ) {
            $count = $this->count();
        }

        if (isset($fields[self::REST_VIEW_COUNT])) {
            $this->setRestViewVar(self::REST_VIEW_COUNT, $count);
        }

        if (isset($fields[self::COUNT_RESPONSE_GROUPED_COUNT])) {
            $this->setRestViewVar(self::COUNT_RESPONSE_GROUPED_COUNT, $count);
        }

        if (isset($fields[self::COUNT_RESPONSE_BUCKET_TOTAL])) {
            $this->setRestViewVar(
                self::COUNT_RESPONSE_BUCKET_TOTAL,
                $this->getCountActionBucketTotal($count)
            );
        }

        if (isset($fields[self::COUNT_RESPONSE_TOTAL_COUNT])) {
            $this->setRestViewVar(self::COUNT_RESPONSE_TOTAL_COUNT, $this->count($this->getCountActionTotalFind()));
        }
    }

    /**
     * Return the requested count fields accepted by the current policy.
     *
     * A null policy is intentionally unrestricted across supported framework
     * count fields, so clients can opt in to list counts without every
     * controller declaring boilerplate. Non-null policies restrict the accepted
     * names, and unsupported or disallowed requests fail instead of silently
     * doing surprising work.
     *
     * @return list<string>
     *
     * @throws FilterException When request parameter filtering fails.
     * @throws HttpException When the client requests a disallowed count field.
     */
    protected function getFindActionRequestedCountFieldNames(): array
    {
        $requestedFields = $this->normalizeFindActionRequestedCountFields(
            $this->getParam(self::FIND_ACTION_COUNT_PARAMETER)
        );

        if ($requestedFields === []) {
            return [];
        }

        $allowedFields = array_fill_keys($this->getFindActionAllowedCountFieldNames(), true);
        foreach ($requestedFields as $field) {
            if (!isset($allowedFields[$field])) {
                throw new HttpException(sprintf('Unauthorized list count field "%s".', $field), 403);
            }
        }

        return $requestedFields;
    }

    /**
     * Return the count fields accepted by the current list-action policy.
     *
     * Null means unrestricted across the finite set of framework-supported
     * count fields. Non-null policies are normalized through the controller
     * collection so empty collections and disabled entries can intentionally
     * deny every requested field.
     *
     * @return list<string>
     */
    protected function getFindActionAllowedCountFieldNames(): array
    {
        if ($this->getFindActionCountFields() === null) {
            return $this->getFindActionSupportedCountFieldNames();
        }

        return $this->getFindActionCountFieldNames();
    }

    /**
     * Return the built-in list-count field names that PhalconKit can emit.
     *
     * This finite set is the boundary for unrestricted mode. It allows
     * consumers to skip boilerplate policy declarations without turning
     * arbitrary request strings into dynamic response variables.
     *
     * @return list<string>
     */
    protected function getFindActionSupportedCountFieldNames(): array
    {
        return [
            self::REST_VIEW_COUNT,
            self::COUNT_RESPONSE_GROUPED_COUNT,
            self::COUNT_RESPONSE_BUCKET_TOTAL,
            self::COUNT_RESPONSE_TOTAL_COUNT,
        ];
    }

    /**
     * Normalize the controller allow-list to count response field names.
     *
     * Count-field policies do not support public-to-query aliases, so string
     * keys are treated as enabled-map entries. This keeps PHP config,
     * environment-derived config, and request-map semantics aligned for count
     * fields without changing alias-capable policies such as distinct/order
     * fields.
     *
     * @return list<string>
     */
    protected function getFindActionCountFieldNames(): array
    {
        $fields = [];

        foreach ($this->getFindActionCountFields()?->toArray() ?? [] as $key => $value) {
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
     * Normalize the client `count` request parameter to field names.
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
    protected function normalizeFindActionRequestedCountFields(mixed $requested): array
    {
        if ($requested === null || $requested === false || $requested === 0) {
            return [];
        }

        if ($requested === true || is_int($requested)) {
            return [self::REST_VIEW_COUNT];
        }

        if (is_string($requested)) {
            return $this->normalizeFindActionRequestedCountString($requested);
        }

        if (!is_array($requested)) {
            throw new HttpException(sprintf('Invalid type for "count" parameter: expected null, bool, string, or array, got %s.', gettype($requested)), 400);
        }

        $fields = [];
        foreach ($requested as $key => $value) {
            if (is_string($key)) {
                if ($this->isFindActionCountEnabledValue($value)) {
                    $fields[] = trim($key);
                }
                continue;
            }

            if ($value === true || (is_int($value) && $value !== 0)) {
                $fields[] = self::REST_VIEW_COUNT;
                continue;
            }

            if (is_string($value)) {
                $fields = array_merge($fields, $this->normalizeFindActionRequestedCountString($value));
            }
        }

        return $this->normalizeFindActionCountFieldList($fields);
    }

    /**
     * Normalize a scalar `count` request value.
     *
     * @return list<string>
     */
    protected function normalizeFindActionRequestedCountString(string $requested): array
    {
        $requested = trim($requested);
        if ($requested === '' || !$this->isFindActionCountEnabledValue($requested)) {
            return [];
        }

        if ($this->isFindActionCountTruthyString($requested)) {
            return [self::REST_VIEW_COUNT];
        }

        return $this->normalizeFindActionCountFieldList(explode(',', $requested));
    }

    /**
     * Check whether an enabled-map `count[field]` value should request a field.
     */
    protected function isFindActionCountEnabledValue(mixed $value): bool
    {
        return CollectionPolicy::isEnabledValue($value);
    }

    /**
     * Check whether a scalar string requests the default native count field.
     */
    protected function isFindActionCountTruthyString(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Trim, de-duplicate, and drop empty count field names.
     *
     * @param array<int, mixed> $fields Raw field fragments.
     * @return list<string>
     */
    protected function normalizeFindActionCountFieldList(array $fields): array
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
}
