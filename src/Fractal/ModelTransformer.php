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

namespace PhalconKit\Fractal;

use Phalcon\Mvc\Model;

/**
 * Default Fractal transformer for Phalcon models.
 *
 * The transformer delegates to `Model::toArray()` so it mirrors the fields that
 * the model exposes through Phalcon's normal serialization path. Applications
 * can extend this class when they need a starting point that preserves the
 * model's own visibility and virtual-field behavior.
 *
 * Use a dedicated application transformer when API output must hide fields,
 * rename attributes, add computed values, or control included relationships.
 * This default transformer is intentionally transparent rather than policy
 * heavy.
 *
 * @see https://docs.phalcon.io/5.15/db-models/
 * @see https://fractal.thephpleague.com/transformers/
 */
class ModelTransformer extends Transformer
{
    /**
     * Convert a model instance to the array consumed by Fractal serializers.
     *
     * No filtering is applied here beyond whatever the model's `toArray()`
     * implementation already does. That keeps the default behavior predictable
     * for generated/admin resources while leaving public API shaping to custom
     * transformers.
     *
     * @param Model $model Model instance being serialized.
     *
     * @return array<string, mixed> Model attributes and virtual fields exposed
     *     by the model's `toArray()` implementation.
     */
    public function transform(Model $model): array
    {
        return $model->toArray();
    }
}
