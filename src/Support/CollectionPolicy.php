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
