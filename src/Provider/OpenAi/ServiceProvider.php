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
 * URI. A Guzzle client is supplied explicitly so streaming responses use the
 * same HTTP stack as normal requests.
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
            $openAiConfig = $config->pathToArray('openai') ?? [];

            $openAiFactory = OpenAI::factory()
                ->withApiKey($openAiConfig['apiKey'] ?? '')
                ->withOrganization($openAiConfig['organization'] ?? null)
                ->withProject($openAiConfig['project'] ?? null)
                ->withBaseUri($openAiConfig['baseUri'] ?? 'api.openai.com/v1')
                ->withHttpClient($httpClient = new \GuzzleHttp\Client([]))
                ->withStreamHandler(fn (RequestInterface $request): ResponseInterface => $httpClient->send($request, [
                    'stream' => true,
                ]));

            return $openAiFactory->make();
        });
    }
}
