<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;

/** Uses Core/native assignment with isolated, programmable record lookups. */
final class RelationshipAssignmentModel extends NativeRelationshipModelDouble
{
    public static ?\Closure $lookup = null;
    public static array $lookups = [];

    public mixed $tenantId = null;
    public mixed $parentTenantId = null;
    public mixed $secret = null;

    #[\Override]
    public static function findFirst(mixed $parameters = null): ModelInterface|Row|false|null
    {
        self::$lookups[] = $parameters;
        return self::$lookup !== null ? (self::$lookup)($parameters) : null;
    }
}
