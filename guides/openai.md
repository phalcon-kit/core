# OpenAI

Use the shared `openAi` service to call OpenAI from an application service or
CLI task. PhalconKit configures the PHP SDK and lets your application replace
its HTTP and streaming transports independently.

## Install And Configure

Install the optional dependencies in your application's production dependency
graph:

```bash
composer require 'openai-php/client:^0.21.0' 'guzzlehttp/guzzle:^8.2.0'
```

Core lists these packages as suggestions and development dependencies. Installing
Core alone does not install them for your application. Guzzle supplies the
default HTTP and streaming transports; applications supplying both transports
can choose a different PSR-18 client.

The standard bootstrap registers
`PhalconKit\Provider\OpenAi\ServiceProvider`. Set `OPENAI_API_KEY` in your
application environment. Keep credentials out of source control. Optional
environment values are `OPENAI_ORGANIZATION`, `OPENAI_PROJECT`, and
`OPENAI_BASE_URI`.

These are the provider's config keys under `openai`:

| Key | Default | Legacy fallback |
| --- | --- | --- |
| `apiKey` | Empty string | `secretKey` |
| `organization` | `null` | `organizationId` |
| `project` | `null` | `projectId` |
| `baseUri` | `api.openai.com/v1` | None |

Canonical keys take precedence when non-empty after trimming. Empty canonical
values fall back to the legacy aliases. The standard config also accepts
`OPENAI_SECRET_KEY`, `OPENAI_ORGANIZATION_ID`, and `OPENAI_PROJECT_ID` as legacy
environment names. Use canonical names in new configuration.

Resolve the SDK client from an injectable application service:

```php
$client = $this->di->get('openAi');
```

Registration and client resolution do not make an API request. The SDK sends
requests when resource methods are called, such as `responses()->create()` or
`files()->upload()`.

## Inject Transports

Register overrides in your application provider's `register(DiInterface $di)`
method before anything resolves `openAi`. See [Configuration](configuration.md)
for registering an application provider.

| Shared DI service | Contract | Default |
| --- | --- | --- |
| `openAiHttpClient` | `Psr\Http\Client\ClientInterface` | A Guzzle client |
| `openAiStreamHandler` | `callable(RequestInterface): ResponseInterface` using PSR-7 messages | The same Guzzle client, with `stream => true` |
| `openAi` | `OpenAI\Contracts\ClientContract` | The configured SDK client |

The Core provider preserves transport services already registered in the
container. The shared SDK client retains the transports it received when first
resolved; replacing a DI service afterward does not change that client.

For example, configure a Guzzle client with application timeouts:

```php
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;

$di->setShared('openAiHttpClient', fn (): ClientInterface => new Client([
    'connect_timeout' => 10.0,
    'timeout' => 60.0,
]));
```

The default stream handler uses this same client. Ordinary requests, multipart
file uploads, and file downloads go through its PSR-18 `sendRequest()` method.
Streamed requests go through `openAiStreamHandler`.

PSR-18 does not define a streaming option. When injecting a client that does not
implement `GuzzleHttp\ClientInterface`, provide a stream handler if the
application streams responses. Register a handler through a DI factory that
returns the callable; otherwise Phalcon treats the callable as a service
factory and invokes it during resolution. The factory must be non-static so
Phalcon can bind it to the container. This example explicitly wires a
Guzzle handler:

```php
use Closure;
use GuzzleHttp\Client;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$httpClient = new Client(['timeout' => 60.0]);
$di->setShared('openAiHttpClient', $httpClient);
$di->setShared('openAiStreamHandler', fn (): Closure =>
    static fn (RequestInterface $request): ResponseInterface =>
        $httpClient->send($request, ['stream' => true])
);
```

## Capture Responses Usage

SDK response objects expose the fields supported by the installed SDK. In
`openai-php/client` 0.21, serializing Responses usage omits cache-write counters.
Applications that need these counters can opt into
`PhalconKit\OpenAi\UsageCapture` without changing SDK response types.

Wire a shared capture store to both transports. The `openAiUsageCapture` service
name in this example is application-owned; Core does not register it:

```php
use Closure;
use GuzzleHttp\Client;
use PhalconKit\OpenAi\UsageCapture;
use PhalconKit\OpenAi\UsageCaptureHttpClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

$httpClient = new Client(['timeout' => 60.0]);
$capture = new UsageCapture();

$di->setShared('openAiUsageCapture', $capture);
$di->setShared('openAiHttpClient', new UsageCaptureHttpClient($httpClient, $capture));
$di->setShared('openAiStreamHandler', fn (): Closure =>
    $capture->wrapStreamHandler(
        static fn (RequestInterface $request): ResponseInterface =>
            $httpClient->send($request, ['stream' => true])
    )
);
```

Register both overrides: the HTTP decorator implements PSR-18, so the default
Guzzle stream handler cannot use it directly.

After a Responses request, consume the captured entry using the SDK response's
ID. Here `$approvedModel` comes from your application policy:

```php
$response = $di->get('openAi')->responses()->create([
    'model' => $approvedModel,
    'input' => 'Summarize this sentence: The library now supports file uploads.',
]);

/** @var UsageCapture $capture */
$capture = $di->getShared('openAiUsageCapture');
$billingUsage = $capture->takeForResponseId($response->id);
```

`takeForResponseId()` removes the entry. It returns an empty array when no entry
is available, including when it was already consumed or evicted. A captured
entry has `usage` and `usage_valid` keys, and may include `model` and
`service_tier`. Persist the entry in your application if it must survive beyond
the current process. An observed response with missing or `null` usage clears
any older unconsumed entry for the same response ID, so stale counters are not
returned for that observation.

Capture observes successful JSON responses to `POST /responses` and
`GET /responses/{id}`, and terminal SSE events for completed, incomplete, and
failed responses. Retrieved response IDs must match the requested ID. Capture
observes bytes as the SDK or caller consumes the body; it does not eagerly
read a stream. For streamed requests, consume the terminal event before taking
usage. A cancelled or partly consumed stream may have no entry.

The store retains these fields inside `usage` when supplied as nonnegative
integers:

- `input_tokens`, `output_tokens`, and `total_tokens`;
- `input_tokens_details.cached_tokens` and `input_tokens_details.cache_write_tokens`;
- `input_tokens_details.cache_write_tokens_details.ttl_30m_tokens` and
  `input_tokens_details.cache_write_tokens_details.ttl_12h_tokens`;
- `output_tokens_details.reasoning_tokens`.

See OpenAI's [prompt-caching usage fields](https://developers.openai.com/api/docs/guides/prompt-caching#monitor-cache-performance)
for the cache counter definitions.

Unknown usage fields are ignored. The store does not retain request bodies,
response text, API keys, or headers. Model and service-tier identifiers are
limited to 128 ASCII letters, digits, periods, underscores, colons, or hyphens.
Malformed known counters are omitted and set `usage_valid` to `false`; check
this flag before using captured counters. Missing fields remain absent; do not
interpret an absent counter as a measured zero.

The default capacity is 128 response IDs. Pass another positive capacity to
`new UsageCapture($capacity)` when appropriate; the oldest entry is evicted
when capacity is exceeded. Zero or negative capacity throws
`InvalidArgumentException`. Each observed JSON body or SSE data line has a fixed
8 MiB transient observation limit. Malformed or oversized payloads produce no
capture, and capture cannot recover bytes the caller never reads. Rewinding an
already observed body does not recreate a consumed entry. Use `reset()` at
application job boundaries when reusing a container in a long-lived worker.

## Handle Failures And Test Offline

The provider throws `PhalconKit\Exception\ServiceException` when
`openAiHttpClient` does not implement PSR-18 or `openAiStreamHandler` is not
callable. A non-Guzzle HTTP client without a custom handler can make ordinary
requests, but streaming throws a `ServiceException` explaining the missing
handler.

Network and API failures retain the SDK's error behavior. Depending on the
failure and transport, callers can receive SDK `ErrorException`,
`RateLimitException`, `ServerException`, `TransporterException`, or
`UnserializableResponse` exceptions. Catch errors at your application service
boundary and apply your own retry, logging, and user-message policy. Do not
assume every transport reports a given HTTP status with the same exception.

Inject fixture transports to test without credentials or network access.
Exercise both ordinary and streamed requests, multipart file uploads, download
bytes, config aliases, malformed responses, and error propagation. Test your
consumer's behavior when usage is absent, invalid, already consumed, or evicted.
Offline fixtures verify integration behavior; they do not establish live model
availability, account access, pricing, or service performance.

Your application owns model approvals and retirement, allowed workflows,
authorization, prompts, retries, persistence, and pricing. Core supplies
transport configuration and usage observation; it does not turn counters into
charges or approve generated output.
