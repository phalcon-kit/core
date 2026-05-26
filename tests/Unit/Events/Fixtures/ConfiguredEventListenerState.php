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

namespace PhalconKit\Tests\Unit\Events\Fixtures;

final class ConfiguredEventListenerState
{
    /** @var array<int, array{listener: string, hasDi: bool, data: mixed}> */
    public static array $calls = [];

    public static function reset(): void
    {
        self::$calls = [];
    }
}
