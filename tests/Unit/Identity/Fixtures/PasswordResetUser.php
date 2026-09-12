<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Identity\Fixtures;

/** Minimal schema using Core's real user accessors, hashing, and ORM persistence. */
final class PasswordResetUser extends \PhalconKit\Models\User
{
    public bool $rejectSave = false;

    public function initialize(): void
    {
        $this->setSource('reset_users');
        $this->useDynamicUpdate(true);
        $this->keepSnapshots(true);
        $this->initializeSoftDelete();
    }

    public function columnMap(): array
    {
        return ['id' => 'id', 'email' => 'email', 'password' => 'password', 'reset_token' => 'resetToken', 'deleted' => 'deleted'];
    }

    public function validation(): bool
    {
        return !$this->rejectSave;
    }
}
