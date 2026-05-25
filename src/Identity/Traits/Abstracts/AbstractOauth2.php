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
 * Declares OAuth2 linking methods required by composed identity traits.
 *
 * Implementations should normalize provider data into the core OAuth2 model,
 * link it to a local user, and establish the standard session identity when
 * the linked user can log in.
 *
 * @phpstan-ignore trait.unused
 */
trait AbstractOauth2
{
    /**
     * @param array<string, mixed>|null $meta
     *
     * @return array<string, mixed>
     */
    abstract public function oauth2(string $provider, string $providerUuid, string $accessToken, ?string $refreshToken = null, ?array $meta = []): array;
}
