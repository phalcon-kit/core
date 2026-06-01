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

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Row;
use PhalconKit\Models\Role;

final class UserTaskRoleDouble extends Role
{
    /** @var list<mixed> */
    public static array $findFirstParameters = [];

    public static ?self $row = null;

    public static function resetState(): void
    {
        self::$findFirstParameters = [];
        self::$row = null;
    }

    #[\Override]
    public static function findFirst(mixed $parameters = null): ModelInterface|Row|false|null
    {
        self::$findFirstParameters[] = $parameters;
        return self::$row;
    }
}
