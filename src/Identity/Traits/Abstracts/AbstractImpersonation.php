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

namespace PhalconKit\Identity\Traits\Abstracts;

/**
 * Declares impersonation methods required by composed identity traits.
 *
 * The concrete manager stores `userId` as the effective user and `asUserId` as
 * the original user during impersonation. Implementations should preserve that
 * payload shape unless they also replace the session helpers that consume it.
 *
 * @phpstan-ignore trait.unused
 */
trait AbstractImpersonation
{
    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    abstract public function loginAs(array $params = []): array;
    
    /**
     * @return array{loggedIn: bool, loggedInAs: bool}
     */
    abstract public function logoutAs(): array;
}
