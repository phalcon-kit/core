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

namespace PhalconKit\Provider\OpenAi;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use OpenAI;
use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Provider\AbstractServiceProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Registers the OpenAI API client service.
 *
 * The provider builds an `openai-php/client` instance from the `openai` config
 * section. Supported values include API key, organization, project, and base
 * URI. The canonical config keys are `apiKey`, `organization`, `project`, and
 * `baseUri`; legacy bootstrap aliases such as `secretKey`, `organizationId`,
 * and `projectId` are accepted as fallbacks so older app config can keep
 * resolving the shared service while it migrates.
 *
 * Applications may register `openAiHttpClient` (PSR-18 ClientInterface) and
 * `openAiStreamHandler` (callable accepting a PSR-7 request and returning a
 * PSR-7 response) before first resolving `openAi`. Existing transport services
 * are preserved. Defaults use a shared Guzzle client and `send()` with
 * `stream => true`; a PSR-18-only replacement needs its own streaming handler
 * when making streamed requests. Register callable handlers through a DI
 * factory returning the callable so Phalcon does not invoke them as factories.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'openAi';
    
    /**
     * Register the shared `openAi` client and missing transport services.
     *
     * Transport services are resolved lazily when the client is first used.
     * Replace them before resolving `openAi`; an already resolved SDK client
     * keeps its original transports. No network requests occur during registration.
     *
     * @param DiInterface $di Container with a PhalconKit `config` service.
     * @throws ServiceException On client resolution when a transport has the
     *         wrong type, or on streaming when a PSR-18-only client has no
     *         explicitly registered stream handler.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        if (!$di->has('openAiHttpClient')) {
            $di->setShared('openAiHttpClient', fn (): ClientInterface => new Client([]));
        }

        if (!$di->has('openAiStreamHandler')) {
            $di->setShared('openAiStreamHandler', function () use ($di): Closure {
                $httpClient = self::resolveHttpClient($di);

                return function (RequestInterface $request) use ($httpClient): ResponseInterface {
                    if (!$httpClient instanceof GuzzleClientInterface) {
                        throw new ServiceException(
                            'Register an openAiStreamHandler callable to stream with a non-Guzzle openAiHttpClient.'
                        );
                    }

                    return $httpClient->send($request, ['stream' => true]);
                };
            });
        }

        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->getConfig();
            $openAiConfig = self::normalizeOpenAiConfig($config->pathToArray('openai') ?? []);
            $httpClient = self::resolveHttpClient($di);
            $streamHandler = $di->getShared('openAiStreamHandler');
            if (!is_callable($streamHandler)) {
                throw new ServiceException('The openAiStreamHandler service must be callable.');
            }

            $openAiFactory = OpenAI::factory()
                ->withApiKey($openAiConfig['apiKey'])
                ->withOrganization($openAiConfig['organization'])
                ->withProject($openAiConfig['project'])
                ->withBaseUri($openAiConfig['baseUri'])
                ->withHttpClient($httpClient)
                ->withStreamHandler(Closure::fromCallable($streamHandler));

            return $openAiFactory->make();
        });
    }

    private static function resolveHttpClient(DiInterface $di): ClientInterface
    {
        $client = $di->getShared('openAiHttpClient');
        if (!$client instanceof ClientInterface) {
            throw new ServiceException('The openAiHttpClient service must implement ' . ClientInterface::class . '.');
        }

        return $client;
    }

    /**
     * Normalize supported OpenAI provider config aliases.
     *
     * `apiKey`, `organization`, `project`, and `baseUri` are the canonical
     * provider-facing keys. The legacy bootstrap keys are still read as
     * fallbacks because applications may have copied the default `secretKey` or
     * `organizationId` names before the provider contract was clarified.
     *
     * @param array<string, mixed> $openAiConfig Raw `openai` config section.
     * @return array{
     *     apiKey: string,
     *     organization: string|null,
     *     project: string|null,
     *     baseUri: string
     * }
     */
    protected static function normalizeOpenAiConfig(array $openAiConfig): array
    {
        return [
            'apiKey' => self::stringConfigOption($openAiConfig, ['apiKey', 'secretKey']),
            'organization' => self::nullableStringConfigOption($openAiConfig, ['organization', 'organizationId']),
            'project' => self::nullableStringConfigOption($openAiConfig, ['project', 'projectId']),
            'baseUri' => self::stringConfigOption($openAiConfig, ['baseUri'], 'api.openai.com/v1'),
        ];
    }

    /**
     * Return the first non-empty string value from a list of config aliases.
     *
     * Canonical keys are passed first and legacy aliases follow them. Empty
     * strings are ignored so partially migrated environment files can leave a
     * canonical key blank while still relying on the older configured alias.
     *
     * @param array<string, mixed> $config Raw provider config.
     * @param list<string> $keys Ordered config keys to inspect.
     */
    private static function stringConfigOption(array $config, array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $config)) {
                continue;
            }

            $value = self::stringConfigValue($config[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Return an optional string config value from canonical and alias keys.
     *
     * @param array<string, mixed> $config Raw provider config.
     * @param list<string> $keys Ordered config keys to inspect.
     */
    private static function nullableStringConfigOption(array $config, array $keys): ?string
    {
        $value = self::stringConfigOption($config, $keys);
        return $value === '' ? null : $value;
    }

    /**
     * Cast a provider config value to the string expected by `openai-php/client`.
     */
    private static function stringConfigValue(mixed $value): string
    {
        if (!is_scalar($value) && $value !== null) {
            return '';
        }

        return trim((string)$value);
    }
}
