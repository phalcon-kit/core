<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity\Fixtures;

use PhalconKit\Identity\Manager;
use PhalconKit\Models\Interfaces\UserInterface;

/** Synthetic user lookup; authentication, JWTs, and session storage remain Core implementations. */
class SessionIdentityManager extends Manager
{
    public array $users = [];

    public function findUserByEmail(string $string): ?UserInterface
    {
        return $this->users[42] ?? null;
    }

    public function findUserById(int $id): ?UserInterface
    {
        return $this->users[$id] ?? null;
    }

    public function getUser(bool $as = false, ?bool $force = null): ?UserInterface
    {
        $id = $this->getSessionIdentity()[$as ? 'asUserId' : 'userId'] ?? null;
        return $id === null ? null : ($this->users[$id] ?? null);
    }

    public function getAclRoles(?array $roleList = null): array
    {
        return [];
    }

    public function getRoleList(): array
    {
        return $this->getUser()?->getId() === 42 ? ['admin' => true] : [];
    }
}
