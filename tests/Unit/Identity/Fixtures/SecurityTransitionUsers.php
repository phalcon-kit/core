<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity\Fixtures;

use RuntimeException;
use PhalconKit\Models\Interfaces\UserInterface;

final class SecurityTransitionUsers
{
    public static array $users = [];
    public static bool $throw = false;
    public static function findFirstWith(array $with, array $params): ?UserInterface
    {
        if (self::$throw) {
            throw new RuntimeException('Synthetic user lookup failure');
        }
        return self::$users[$params['bind']['id']] ?? null;
    }
    public static function findFirst(array $params): ?UserInterface
    {
        if (isset($params['bind']['email'])) {
            foreach (self::$users as $user) {
                if ($user->getEmail() === $params['bind']['email']) {
                    return $user;
                }
            }
            return null;
        }
        return self::$users[$params['bind']['id']] ?? null;
    }
}
