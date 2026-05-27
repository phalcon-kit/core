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

use OpenAI;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;
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
 * resolving the shared service while it migrates. A Guzzle client is supplied
 * explicitly so streaming responses use the same HTTP stack as normal requests.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'openAi';
    
    /**
     * Register the shared `openAi` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            $openAiConfig = self::normalizeOpenAiConfig($config->pathToArray('openai') ?? []);

            $openAiFactory = OpenAI::factory()
                ->withApiKey($openAiConfig['apiKey'])
                ->withOrganization($openAiConfig['organization'])
                ->withProject($openAiConfig['project'])
                ->withBaseUri($openAiConfig['baseUri'])
                ->withHttpClient($httpClient = new \GuzzleHttp\Client([]))
                ->withStreamHandler(fn (RequestInterface $request): ResponseInterface => $httpClient->send($request, [
                    'stream' => true,
                ]));

            return $openAiFactory->make();
        });
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
