# PhalconKit OpenAI Runtime

Use this reference when a PhalconKit application uses the built-in `openAi` DI
service or adds app-owned runtime AI features. For reusable agent skills and
AI documentation assets, use `AI.md` and the skill references instead.

The canonical application recipe is the [OpenAI guide](../../../../guides/openai.md).

## Phalcon Baseline

Native Phalcon references:

- Dependency injection: https://docs.phalcon.io/latest/di/
- Controllers: https://docs.phalcon.io/latest/controllers/
- CLI applications: https://docs.phalcon.io/latest/cli/
- Logger: https://docs.phalcon.io/latest/logger/

Phalcon does not provide OpenAI runtime APIs. PhalconKit registers an OpenAI
client as a DI service, so native DI/controller/task/logger guidance still
applies to where the client is injected, called, and observed.

## Service Provider

`PhalconKit\Provider\OpenAi\ServiceProvider` registers the shared `openAi`
client and supplies missing shared transports:

- `openAiHttpClient`: `Psr\Http\Client\ClientInterface`, defaulting to Guzzle.
- `openAiStreamHandler`: `callable(RequestInterface): ResponseInterface`, using
  PSR-7 messages and defaulting to the same Guzzle client with `stream => true`.

Pre-registered transport services are preserved. Register overrides before
resolving `openAi`; an already resolved client retains its original transports.
Wrap a stream-handler callable in a non-static DI factory that returns it so
Phalcon can bind the factory and does not invoke the handler during resolution.
A PSR-18-only replacement needs its own
handler for streamed requests because PSR-18 has no streaming option.

Runtime usage:

```php
$client = $this->di->get('openAi');
```

Prefer wrapping the client in an app domain service when more than one
controller/task will use the same prompt, tool, retry, logging, or persistence
policy.

## Current Config Contract

The current provider reads these keys from `openai`:

```php
'openai' => [
    'apiKey' => Env::get('OPENAI_API_KEY'),
    'organization' => Env::get('OPENAI_ORGANIZATION'),
    'project' => Env::get('OPENAI_PROJECT'),
    'baseUri' => Env::get('OPENAI_BASE_URI', 'api.openai.com/v1'),
],
```

The provider also accepts older bootstrap aliases as fallbacks:
`secretKey` for `apiKey`, `organizationId` for `organization`, and `projectId`
for `project`. A non-empty canonical value wins after trimming; an empty value
falls back to the alias. Prefer canonical keys in new app config.

## Dependency Boundary

`openai-php/client` and Guzzle are suggested/dev packages in Core. Applications
using the default provider transports need both in their production Composer
graph. The current package constraints are `openai-php/client ^0.21.0` and
`guzzlehttp/guzzle ^8.2.0`. Applications supplying both transports can use a
different PSR-18 client.

Check the app's `composer.json` before adding runtime code:

```bash
composer show openai-php/client
composer show guzzlehttp/guzzle
```

If the app does not install the package, add it through the app's dependency
workflow before enabling the provider.

## Responses Usage Capture

SDK 0.21's typed Responses usage omits cache-write fields. Applications needing
those counters can opt into `PhalconKit\OpenAi\UsageCapture` and
`UsageCaptureHttpClient` without replacing SDK response objects.

Share one `UsageCapture` instance between the HTTP decorator and
`UsageCapture::wrapStreamHandler()`. Register both DI transport overrides; the
PSR-18 decorator is not itself a Guzzle client. The
[guide's capture example](../../../../guides/openai.md#capture-responses-usage)
shows the full wiring.

After the SDK consumes a JSON response or the terminal streamed event, call
`takeForResponseId($responseId)` once. The result contains `usage` and
`usage_valid`, with optional `model` and `service_tier`; absent, consumed, or
evicted records return `[]`. Verify `usage_valid` before using counters and
preserve missing fields as missing. Unknown usage keys are ignored. Captured
counters include input/output/total tokens, cached input, reasoning output, and
cache writes including 30-minute and 12-hour TTL details.

The default store retains 128 response IDs and evicts the oldest. Each JSON
body or SSE data line has a fixed 8 MiB observation limit. Capture reads no bytes
ahead of the caller; malformed, oversized, or partly consumed bodies may have
no usage entry. Re-reading a body does not recreate consumed records. Use
`reset()` between jobs in long-lived workers. The capture store retains only
allowed counters and bounded identifiers, not prompts, outputs, or headers.

Usage capture does not calculate charges. Applications own model approvals,
retirement rules, allowed workflows, pricing, and persistence.

## Where To Call OpenAI

Use these boundaries:

- Controllers validate request data and call an app service.
- CLI tasks can run imports, batch enrichment, or async review jobs.
- Domain services own prompts, model selection, tool schemas, retries, and
  persistence.
- Models should not call OpenAI directly during normal save hooks unless the
  app has deliberately accepted that latency and failure mode.

Do not mix prompt construction, authorization, persistence, and HTTP response
formatting in one controller action.

## Secret And Data Handling

Rules:

- Never commit API keys, organization ids, project ids, raw prompts with
  private customer data, or full OpenAI responses that may contain private
  data.
- Log request ids, model names, record ids, and high-level statuses rather
  than prompt bodies and completions.
- Store generated output only through app-owned persistence rules.
- Gate user-triggered AI features through normal ACL and rate/usage policy.
- Treat external AI calls as network calls that can fail or time out.

## Testing

For app OpenAI features, test without calling the real API by default:

- Provider config maps expected keys.
- HTTP overrides receive ordinary and multipart file requests and preserve
  download bytes; streaming overrides receive streamed requests.
- Responses usage retains supplied cache-write counters and marks malformed
  counters invalid, with explicit handling for missing or consumed entries.
- Domain service receives an injected fake client.
- Controller rejects unauthorized callers before the service is invoked.
- Timeout/error paths return app-approved error messages.
- Saved output schema is validated.

Invalid DI transport types raise `PhalconKit\Exception\ServiceException`.
A PSR-18-only client without a custom stream handler fails only when streaming
is requested. Core preserves SDK transport/API errors and does not add retries.
Exception types can differ between ordinary and streamed requests because
custom handlers and Guzzle's streaming `http_errors` behavior participate in
error handling. Test the actual injected transports.

Use real API smoke tests only when the app has explicit credentials, budget,
network approval, and a non-production test path.

## Documentation Boundary

This reference documents the PhalconKit provider contract. It is not model
selection guidance and should not make claims about the latest OpenAI models.
When a task asks for current OpenAI API behavior, use official OpenAI docs and
update app code separately from this provider reference.
