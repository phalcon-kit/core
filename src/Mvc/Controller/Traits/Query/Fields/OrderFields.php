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

namespace PhalconKit\Mvc\Controller\Traits\Query\Fields;

use Phalcon\Support\Collection;
use PhalconKit\Support\CollectionPolicy;

/**
 * Stores the REST order-field allow-list used by the query order parser.
 *
 * A null policy preserves the legacy behavior and allows any client-supplied
 * order field to be normalized by the model query compiler. A non-null
 * collection enables explicit allow-list mode, where only configured public
 * field names can be used by the `order` request parameter.
 */
trait OrderFields
{
    /**
     * Controller-owned order-field policy.
     *
     * Supported collection shapes:
     * - `['status', 'createdAt']` exposes the same public and query fields.
     * - `['status' => true]` enables a field map entry.
     * - `['ownerEmail' => 'Owner.email']` exposes a stable public alias that
     *   orders by a model-qualified or relationship-qualified query field.
     * - false, null, and empty-string values are ignored so inherited entries
     *   can be disabled through merge policy.
     *
     * String values are aliases, not boolean-like flags. For example,
     * `['legacySort' => 'off']` maps `legacySort` to the query field `off`; it
     * does not disable the entry.
     */
    protected ?Collection $orderFields = null;

    /**
     * Initialize the order-field allow-list for REST queries.
     *
     * The default is null for backward compatibility: controllers keep their
     * existing unrestricted ordering behavior until they opt in by overriding
     * this method and calling {@see setOrderFields()}.
     */
    public function initializeOrderFields(): void
    {
        $this->setOrderFields(null);
    }

    /**
     * Replace the fields that clients may use in the `order` parameter.
     *
     * Passing null disables allow-list enforcement and preserves legacy
     * behavior. Passing an empty collection enables allow-list mode but allows
     * no fields, which can be useful for controllers that must reject every
     * client-controlled sort key.
     *
     * @param array|Collection|null $orderFields Field policy collection, null for
     *     unrestricted ordering, or an empty collection for a closed policy.
     */
    public function setOrderFields(array|Collection|null $orderFields): void
    {
        $this->orderFields = CollectionPolicy::normalizeNullable($orderFields);
    }

    /**
     * Return the configured order-field policy.
     *
     * A null return value means unrestricted ordering. A non-null collection is
     * normalized by {@see getOrderFieldMap()} before the request `order`
     * parameter is accepted.
     *
     * @return Collection|null Field policy collection or null for unrestricted
     *     ordering.
     */
    public function getOrderFields(): ?Collection
    {
        return $this->orderFields;
    }

    /**
     * Check whether order-field allow-list mode is configured.
     *
     * This reports policy presence, not whether the policy currently enables at
     * least one field. An empty collection still means allow-list mode is active
     * and every requested order field will be rejected.
     */
    public function hasOrderFields(): bool
    {
        return $this->orderFields !== null;
    }

    /**
     * Merge additional order-field entries into the current policy.
     *
     * Merge semantics match the other REST field-policy collections: null
     * starts from the incoming collection, string keys can override previous
     * entries, and false/null values can disable inherited entries.
     *
     * @param array|Collection $orderFields Additional field policy entries.
     */
    public function mergeOrderFields(array|Collection $orderFields): void
    {
        $this->orderFields = CollectionPolicy::mergeNullable(
            $this->orderFields,
            CollectionPolicy::normalize($orderFields)
        );
    }

    /**
     * Normalize the configured order policy to public => query field names.
     *
     * The public name is what clients send in `order`. The query field is what
     * the framework passes to {@see \PhalconKit\Mvc\Controller\Traits\Model::appendModelName()}
     * when building the PHQL `ORDER BY` expression.
     *
     * @return array<string, string>
     */
    protected function getOrderFieldMap(): array
    {
        $fields = [];

        foreach ($this->getOrderFields()?->toArray() ?? [] as $key => $value) {
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
}
