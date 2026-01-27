<?php
namespace PhalconKit\Support;

use Phalcon\Support\Collection;

final class CollectionPolicy
{
    /**
     * Null = unrestricted universe.
     * Non-null = constrained universe.
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
     * Intersection with null-as-unrestricted semantics.
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
