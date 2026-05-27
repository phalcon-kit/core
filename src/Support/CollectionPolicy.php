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

namespace PhalconKit\Support;

use Phalcon\Support\Collection;

/**
 * Merge/intersection helpers for nullable collection policy sets.
 *
 * Several controller query policies use `null` to mean "unrestricted" and an
 * empty collection to mean "explicitly empty". These helpers preserve that
 * distinction when feature, role, and controller policies are combined.
 */
final class CollectionPolicy
{
    /**
     * Interpret a collection map value as an enabled/disabled flag.
     *
     * Several public REST policies accept "enabled map" syntax where the array
     * key is the field or relation name and the value decides whether that key
     * is active, for example `['totalCount' => true]` or values coming from a
     * query string such as `['totalCount' => '1']`.
     *
     * Use this helper only when the value is meant to be a boolean-like flag.
     * Do not use it for policy maps where string values are aliases or query
     * field names, such as `['ownerEmail' => 'Owner.email']`.
     *
     * @param mixed $value Raw collection value from PHP config, merged config,
     *     request maps, or tests.
     *
     * @return bool True when the map entry should be considered enabled.
     */
    public static function isEnabledValue(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }

        if (is_int($value) || is_float($value)) {
            return $value !== 0 && $value !== 0.0;
        }

        if (!is_string($value)) {
            return true;
        }

        $value = strtolower(trim($value));
        return !in_array($value, ['', '0', 'false', 'no', 'off'], true);
    }

    /**
     * Merge an incoming constrained collection into an optional base policy.
     *
     * `null` means the base policy is unrestricted, so the incoming policy
     * becomes the first real constraint. An empty incoming collection leaves an
     * existing constrained base unchanged.
     */
    public static function mergeNullable(
        ?Collection $base,
        Collection $incoming
    ): Collection {
        if ($base === null) {
            return clone $incoming;
        }

        if ($incoming->count() === 0) {
            return clone $base;
        }

        return new Collection(
            array_merge(
                $base->toArray(),
                $incoming->toArray()
            )
        );
    }

    /**
     * Intersect an incoming collection with an optional base policy.
     *
     * `null` means unrestricted, so the incoming collection is returned as the
     * first real constraint. Non-null bases are intersected with incoming
     * values and returned as a new collection.
     */
    public static function intersectNullable(
        ?Collection $base,
        Collection $incoming
    ): Collection {
        if ($base === null) {
            return clone $incoming;
        }

        return new Collection(
            array_values(
                array_intersect(
                    $base->toArray(),
                    $incoming->toArray()
                )
            )
        );
    }
}
