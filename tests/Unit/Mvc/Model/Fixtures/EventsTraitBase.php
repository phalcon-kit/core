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

class EventsTraitBase
{
    public static function find(mixed $parameters = null): ResultsetInterface
    {
        return new EventsTraitResultsetDouble();
    }

    public static function findFirst(mixed $parameters = null): mixed
    {
        return null;
    }

    public static function count(mixed $parameters = null): mixed
    {
        return '7';
    }

    public static function sum(mixed $parameters = null): mixed
    {
        return '1.5';
    }

    public static function average(array $parameters = []): mixed
    {
        return '2.5';
    }

    public static function minimum(mixed $parameters = null): mixed
    {
        return '3.5';
    }

    public static function maximum(mixed $parameters = null): mixed
    {
        return '4.5';
    }
}
