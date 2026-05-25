<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Identity\Traits\Interfaces;

/**
 * Contract for switching an authenticated session into and out of impersonation.
 */
interface ImpersonationInterface
{
    /**
     * Impersonate another user while preserving the original user id.
     *
     * @param array<string, mixed> $params Parameters containing the target
     *     `userId`.
     *
     * @return array<string, mixed> Login state, validation messages, and
     *     optional JWT values when stateless identity mode changes the token
     *     payload.
     */
    public function loginAs(array $params = []): array;

    /**
     * Restore the original user stored in the impersonation session payload.
     *
     * @return array{loggedIn: bool, loggedInAs: bool, jwt?: string, refreshToken?: string, refreshed?: bool}
     */
    public function logoutAs(): array;
}
