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
 * Contract for linking OAuth2 identities to local PhalconKit users.
 */
interface Oauth2Interface
{
    /**
     * Create or update an OAuth2 identity and log in the linked local user.
     *
     * @param string $provider Provider key such as `google` or `github`.
     * @param string $providerUuid Stable provider-side user identifier.
     * @param string $accessToken Current provider access token.
     * @param string|null $refreshToken Optional provider refresh token.
     * @param array<string, mixed>|null $meta Optional provider profile data.
     *
     * @return array<string, mixed> Save state, login state, validation
     *     messages, and optional JWT values when stateless identity mode
     *     changes the token payload.
     */
    public function oauth2(string $provider, string $providerUuid, string $accessToken, ?string $refreshToken = null, ?array $meta = []): array;
}
