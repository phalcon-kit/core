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

use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Mvc\Model\Traits\EagerLoad;

class EagerLoadForwardDouble extends EagerLoadForwardParent
{
    use EagerLoad;

    public static function exposeFindWithByCustom(): ?array
    {
        return static::findWithBy('findByCustom', []);
    }

    public static function find(mixed $parameters = null): ResultsetInterface
    {
        throw new \BadMethodCallException('The direct finder is not used by this test double.');
    }

    public static function findFirst(mixed $parameters = null): mixed
    {
        throw new \BadMethodCallException('The direct first finder is not used by this test double.');
    }
}
