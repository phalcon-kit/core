<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Support\Collection\CollectionInterface;

class ThrowingSaveModelDouble extends ModelBehaviorDouble
{
    #[\Override]
    public function doSave(CollectionInterface $visited): bool
    {
        throw new \RuntimeException('save exploded', 409);
    }
}
