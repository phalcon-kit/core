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
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Row;

class IntermediateDeleteModelDouble extends ModelBehaviorDouble
{
    public static ModelInterface|Row|false|null $findFirstResult = null;
    public static ResultsetInterface $findResult;

    #[\Override]
    public static function findFirst(mixed $parameters = null): ModelInterface|Row|false|null
    {
        return self::$findFirstResult;
    }

    #[\Override]
    public static function find(mixed $parameters = null): ResultsetInterface
    {
        return self::$findResult;
    }
}
