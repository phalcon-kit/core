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

namespace PhalconKit\Fractal\Serializer;

use League\Fractal\Serializer\ArraySerializer;

/**
 * Fractal serializer that returns payload arrays without a resource envelope.
 *
 * League Fractal's default array serializers may wrap data under resource keys.
 * This serializer is used when PhalconKit endpoints need the transformed data
 * itself as the response body. The resource key is accepted for Fractal
 * compatibility, but it is intentionally ignored.
 *
 * This is the default serializer used by PhalconKit REST helpers, so controller
 * responses remain shaped like the transformed model/item arrays instead of
 * being wrapped under a `data` or resource-name envelope.
 *
 * @see https://fractal.thephpleague.com/serializers/
 */
class RawArraySerializer extends ArraySerializer
{
    /**
     * Return collection data exactly as transformed by Fractal.
     *
     * The collection resource key is ignored on purpose. Use a different
     * serializer when an API contract requires collection envelopes,
     * pagination wrappers, or top-level metadata.
     *
     * @param string|null $resourceKey Fractal resource key, ignored by this raw
     *     serializer.
     * @param array<int|string, mixed> $data Transformed collection payload.
     *
     * @return array<int|string, mixed> Unwrapped collection payload.
     */
    #[\Override]
    public function collection(?string $resourceKey, array $data): array
    {
        return $data;
    }
    
    /**
     * Return item data exactly as transformed by Fractal.
     *
     * The item resource key is ignored on purpose so single-record responses
     * keep the same top-level fields emitted by their transformer.
     *
     * @param string|null $resourceKey Fractal resource key, ignored by this raw
     *     serializer.
     * @param array<array-key, mixed> $data Transformed item payload.
     *
     * @return array<array-key, mixed> Unwrapped item payload.
     */
    #[\Override]
    public function item(?string $resourceKey, array $data): array
    {
        return $data;
    }
    
    /**
     * Serialize null resources as an empty array.
     *
     * This keeps API responses shape-stable for callers that expect JSON
     * objects or arrays instead of literal null bodies.
     *
     * Fractal allows serializers to choose the representation of null
     * resources. PhalconKit chooses `[]` here to match the raw-array response
     * style used by collection and item resources.
     *
     * @return array<never, never>
     */
    #[\Override]
    public function null(): ?array
    {
        return [];
    }
}
