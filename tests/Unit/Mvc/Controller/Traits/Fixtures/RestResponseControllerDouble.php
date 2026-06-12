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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use PhalconKit\Mvc\Controller\Rest;

final class RestResponseControllerDouble extends Rest
{
    public bool $unitDebugEnabled = false;

    /**
     * Return the fixture-controlled debug state for deterministic envelopes.
     */
    public function isDebugEnabled(): bool
    {
        return $this->unitDebugEnabled;
    }

    /**
     * Expose the protected helper used by REST actions to set one view field.
     */
    public function exposeSetRestViewVar(string $key, mixed $value): void
    {
        $this->setRestViewVar($key, $value);
    }

    /**
     * Expose the protected helper used by REST actions to set many view fields.
     *
     * @param array<string, mixed> $vars
     */
    public function exposeSetRestViewVars(array $vars, bool $merge = true): void
    {
        $this->setRestViewVars($vars, $merge);
    }

    /**
     * Expose the sanitized view fields used by the REST response envelope.
     *
     * @return array<string, mixed>
     */
    public function exposeRestViewVars(): array
    {
        return $this->getRestViewVars();
    }

    /**
     * Expose failure status resolution for message payload regression tests.
     */
    public function exposeRestActionFailureStatusCode(
        mixed $messages,
        int $emptyStatusCode = 400,
        int $defaultStatusCode = 422
    ): int {
        return $this->getRestActionFailureStatusCode($messages, $emptyStatusCode, $defaultStatusCode);
    }
}
