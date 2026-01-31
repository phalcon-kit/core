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
use Phalcon\Di\DiInterface;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Provider\AbstractServiceProvider;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'openAi';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->get('config');
            assert($config instanceof Config);
            $openAiConfig = $config->pathToArray('openai') ?? [];

            $openAiFactory = OpenAI::factory()
                ->withApiKey($openAiConfig['apiKey'] ?? null)
                ->withOrganization($openAiConfig['organization'] ?? null) // default: null
                ->withProject($openAiConfig['project'] ?? null) // default: null
                ->withBaseUri($openAiConfig['baseUri'] ?? 'api.openai.com/v1') // default: api.openai.com/v1
                ->withHttpClient($httpClient = new \GuzzleHttp\Client([])) // default: HTTP client found using PSR-18 HTTP Client Discovery
                ->withStreamHandler(fn (RequestInterface $request): ResponseInterface => $httpClient->send($request, [
                    'stream' => true // Allows to provide a custom stream handler for the http client.
                ]));

            return $openAiFactory->make();
        });
    }
}
