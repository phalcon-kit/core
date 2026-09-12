<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity\Fixtures;

/** Isolated OAuth account storage for the real identity OAuth consumer. */
final class SessionOauth2Double
{
    public static int $userId = 42;

    public static function findFirst(array $params): self
    {
        return new self();
    }

    public function setAccessToken(string $token): void
    {
    }

    public function setRefreshToken(?string $token): void
    {
    }

    public function setMeta(?string $meta): void
    {
    }

    public function setEmail(?string $email): void
    {
    }

    public function assign(array $data): void
    {
    }

    public function getUserId(): int
    {
        return self::$userId;
    }

    public function save(): bool
    {
        return true;
    }

    public function getMessages(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return ['userId' => self::$userId];
    }
}
