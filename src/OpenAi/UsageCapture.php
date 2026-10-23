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

use Closure;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Optional, bounded capture of Responses billing fields omitted by SDK decoders.
 *
 * Share one instance between UsageCaptureHttpClient and wrapStreamHandler().
 * Entries contain only whitelisted usage counters and model/service-tier identifiers;
 * prompts, output, headers, and arbitrary metadata are never stored here. Applications
 * own pricing and persistence and must check usage_valid before using captured counters.
 */
final class UsageCapture
{
    /** @var array<string, array{usage: array<string, mixed>, usage_valid: bool, model?: string, service_tier?: string}> */
    private array $responses = [];

    /**
     * @param int $capacity Maximum unconsumed response IDs; oldest entries are evicted.
     * @throws InvalidArgumentException If capacity is less than one.
     */
    public function __construct(private readonly int $capacity = 128)
    {
        if ($capacity < 1) {
            throw new InvalidArgumentException('Usage capture capacity must be at least one.');
        }
    }

    /** Discard unconsumed counters, for example at the end of a worker job. */
    public function reset(): void
    {
        $this->responses = [];
    }

    /**
     * Consume billing fields for exactly this provider response ID, once.
     *
     * Call after consuming the JSON body or terminal stream event. Missing counters
     * remain missing; invalid supplied counters are omitted and set usage_valid=false.
     * An empty result means no capture is available, not that the request was free.
     *
     * @return array{usage: array<string, mixed>, usage_valid: bool, model?: string, service_tier?: string}|array{}
     */
    public function takeForResponseId(string $responseId): array
    {
        $captured = $this->responses[$responseId] ?? [];
        unset($this->responses[$responseId]);

        return $captured;
    }

    /**
     * Decorate a transport without reading ahead or buffering the entire stream.
     *
     * Register the returned closure as the openAiStreamHandler DI service through
     * a factory returning it. Transport and body-read exceptions remain unchanged.
     *
     * @param Closure(RequestInterface): ResponseInterface $handler Existing transport.
     * @return Closure(RequestInterface): ResponseInterface
     */
    public function wrapStreamHandler(Closure $handler): Closure
    {
        return fn (RequestInterface $request): ResponseInterface => $this->wrapResponse($request, $handler($request));
    }

    /**
     * Observe successful Responses create/retrieve bodies as the caller reads them.
     *
     * Other endpoints, errors, unsupported media types, and unreadable bodies are
     * returned unchanged. JSON bodies and individual SSE data lines have an 8 MiB
     * transient parsing limit; oversized or malformed data yields no capture.
     * Seek, close, and detach retain the underlying stream's behavior. Re-reading
     * an observed body does not recreate consumed records. GET captures must match
     * the response ID in the requested path. No network or body reads occur here.
     */
    public function wrapResponse(RequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $path = rtrim($request->getUri()->getPath(), '/');
        $expectedId = null;
        if ($request->getMethod() === 'GET'
            && preg_match('~/responses/(resp_[a-zA-Z0-9_-]{1,251})$~D', $path, $matches) === 1
        ) {
            $expectedId = $matches[1];
        } elseif ($request->getMethod() !== 'POST' || !str_ends_with($path, '/responses')) {
            return $response;
        }

        $mediaType = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300
            || !in_array($mediaType, ['application/json', 'text/event-stream'], true)
            || !$response->getBody()->isReadable()
        ) {
            return $response;
        }

        /** @var array<string, true> $seen */
        $seen = [];
        $observer = function (string $json) use ($expectedId, &$seen): void {
            $this->captureJson($json, $expectedId, $seen);
        };

        return $response->withBody(new UsageCaptureStream(
            $response->getBody(),
            $observer,
            $mediaType === 'text/event-stream'
        ));
    }

    /** @param array<string, true> $seen */
    private function captureJson(string $json, ?string $expectedId, array &$seen): void
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }
        if (!is_array($payload)) {
            return;
        }
        if (isset($payload['type'])) {
            if (!in_array($payload['type'], ['response.completed', 'response.incomplete', 'response.failed'], true)) {
                return;
            }
            $payload = $payload['response'] ?? null;
        }
        if (!is_array($payload) || ($payload['object'] ?? null) !== 'response') {
            return;
        }
        $id = $payload['id'] ?? null;
        if (!is_string($id) || preg_match('/^resp_[a-zA-Z0-9_-]{1,251}$/D', $id) !== 1
            || ($expectedId !== null && $id !== $expectedId)
            || isset($seen[$id]) || count($seen) >= $this->capacity
        ) {
            return;
        }
        unset($this->responses[$id]);
        if (!isset($payload['usage'])) {
            return;
        }

        $valid = true;
        $usage = $this->filterCounters($payload['usage'], [
            'input_tokens' => null,
            'output_tokens' => null,
            'total_tokens' => null,
            'input_tokens_details' => [
                'cached_tokens' => null,
                'cache_write_tokens' => null,
                'cache_write_tokens_details' => ['ttl_30m_tokens' => null, 'ttl_12h_tokens' => null],
            ],
            'output_tokens_details' => ['reasoning_tokens' => null],
        ], $valid);
        $captured = ['usage' => $usage, 'usage_valid' => $valid];
        foreach (['model', 'service_tier'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && preg_match('/^[a-zA-Z0-9._:-]{1,128}$/D', $value) === 1) {
                $captured[$key] = $value;
            }
        }
        $seen[$id] = true;
        unset($this->responses[$id]);
        $this->responses[$id] = $captured;
        if (count($this->responses) > $this->capacity) {
            array_shift($this->responses);
        }
    }

    /**
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    private function filterCounters(mixed $raw, array $schema, bool &$valid): array
    {
        if (!is_array($raw) || ($raw !== [] && array_is_list($raw))) {
            $valid = false;
            return [];
        }
        $result = [];
        foreach ($schema as $key => $children) {
            if (!array_key_exists($key, $raw)) {
                continue;
            }
            $value = $raw[$key];
            if (is_array($children)) {
                /** @var array<string, mixed> $children */
                $result[$key] = $this->filterCounters($value, $children, $valid);
            } elseif (is_int($value) && $value >= 0) {
                $result[$key] = $value;
            } else {
                $valid = false;
            }
        }
        return $result;
    }
}
