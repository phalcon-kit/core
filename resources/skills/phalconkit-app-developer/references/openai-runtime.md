# PhalconKit OpenAI Runtime

Use this reference when a PhalconKit application uses the built-in `openAi` DI
service or adds app-owned runtime AI features. For reusable agent skills and
AI documentation assets, use `AI.md` and the skill references instead.

## Phalcon Baseline

Native Phalcon references:

- Dependency injection: https://docs.phalcon.io/5.16/di/
- Controllers: https://docs.phalcon.io/5.16/controllers/
- CLI applications: https://docs.phalcon.io/5.16/cli/
- Logger: https://docs.phalcon.io/5.16/logger/

Phalcon does not provide OpenAI runtime APIs. PhalconKit registers an OpenAI
client as a DI service, so native DI/controller/task/logger guidance still
applies to where the client is injected, called, and observed.

## Service Provider

`PhalconKit\Provider\OpenAi\ServiceProvider` registers the `openAi` service.
It builds an `openai-php/client` client with a Guzzle HTTP client and a stream
handler.

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
for `project`. Prefer the canonical keys in new app config and keep the aliases
only while migrating existing applications.

## Dependency Boundary

`openai-php/client` is listed as a suggested/dev package in core. Applications
that use the runtime provider should ensure the dependency is installed in
their production Composer graph.

Check the app's `composer.json` before adding runtime code:

```bash
composer show openai-php/client
```

If the app does not install the package, add it through the app's dependency
workflow before enabling the provider.

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
- Domain service receives an injected fake client.
- Controller rejects unauthorized callers before the service is invoked.
- Timeout/error paths return app-approved error messages.
- Saved output schema is validated.

Use real API smoke tests only when the app has explicit credentials, budget,
network approval, and a non-production test path.

## Documentation Boundary

This reference documents the PhalconKit provider contract. It is not model
selection guidance and should not make claims about the latest OpenAI models.
When a task asks for current OpenAI API behavior, use official OpenAI docs and
update app code separately from this provider reference.
