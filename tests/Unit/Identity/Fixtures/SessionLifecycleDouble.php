<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity\Fixtures;

/** Session-manager lifecycle for array storage doubles; native replay is tested separately. */
trait SessionLifecycleDouble
{
    private string $sessionId = 'unit-session-id';

    public function getId(): string
    {
        return $this->sessionId;
    }

    public function exists(): bool
    {
        return true;
    }

    public function regenerateId(bool $deleteOldSession = true): static
    {
        $this->sessionId .= '-renewed';
        return $this;
    }
}
