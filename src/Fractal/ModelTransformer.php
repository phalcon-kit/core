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
 */
class ModelTransformer extends Transformer
{
    /**
     * Convert a model instance to the array consumed by Fractal serializers.
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
