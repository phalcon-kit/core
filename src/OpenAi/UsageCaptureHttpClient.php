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

namespace PhalconKit\OpenAi;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/** PSR-18 decorator capturing Responses usage without changing SDK response objects. */
final class UsageCaptureHttpClient implements ClientInterface
{
    /**
     * @param ClientInterface $client Application-owned HTTP transport.
     * @param UsageCapture $capture Shared billing store; also use it for streaming.
     */
    public function __construct(private readonly ClientInterface $client, private readonly UsageCapture $capture)
    {
    }

    /**
     * Send through the original client and observe eligible response bodies lazily.
     *
     * Requests, including uploads, are not read or modified. Client exceptions
     * propagate unchanged; the caller's reads determine when usage is available.
     */
    #[\Override]
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->capture->wrapResponse($request, $this->client->sendRequest($request));
    }
}
