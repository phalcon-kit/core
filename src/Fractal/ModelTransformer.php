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
 * This class is responsible for transforming a Model object into an array representation.
 */
class ModelTransformer extends Transformer
{
    public function transform(Model $model): array
    {
        return $model->toArray();
    }
}
