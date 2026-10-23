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

namespace PhalconKit\Tests\Unit\Provider\OpenAi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use OpenAI\Contracts\ClientContract;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Exceptions\RateLimitException;
use OpenAI\Exceptions\ServerException;
use OpenAI\Exceptions\TransporterException;
use OpenAI\Exceptions\UnserializableResponse;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Exception\ServiceException;
use PhalconKit\OpenAi\UsageCapture;
use PhalconKit\OpenAi\UsageCaptureHttpClient;
use PhalconKit\Provider\OpenAi\ServiceProvider;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/** Exercise the real SDK transport and decoders using offline HTTP responses. */
final class ServiceProviderTest extends AbstractUnit
{
    public function testDefaultsRegisterSharedClientAndTransportWithoutNetworkCalls(): void
    {
        $di = $this->createDi();
        (new ServiceProvider($di))->register($di);

        self::assertInstanceOf(Client::class, $di->getShared('openAiHttpClient'));
        self::assertSame($di->getShared('openAiHttpClient'), $di->getShared('openAiHttpClient'));
        self::assertIsCallable($di->getShared('openAiStreamHandler'));
        self::assertInstanceOf(ClientContract::class, $di->getShared('openAi'));
        self::assertSame($di->getShared('openAi'), $di->getShared('openAi'));
    }

    public function testPreRegisteredPsr18ClientReceivesNormalResponsesRequests(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects(self::once())->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request): bool {
                self::assertSame('POST', $request->getMethod());
                self::assertSame('https://api.example.test/v1/responses', (string)$request->getUri());
                self::assertSame([
                    'model' => 'synthetic-model',
                    'input' => 'Synthetic input',
                ], json_decode((string)$request->getBody(), true, flags: JSON_THROW_ON_ERROR));

                return true;
            }))
            ->willReturn($this->jsonResponse($this->responsePayload()));
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', $httpClient);
        (new ServiceProvider($di))->register($di);

        $response = $di->getShared('openAi')->responses()->create($this->requestPayload());

        self::assertSame($httpClient, $di->getShared('openAiHttpClient'));
        self::assertSame('resp_synthetic', $response->id);
        self::assertSame('completed', $response->status);
        self::assertSame(12, $response->usage->totalTokens);
    }

    public function testDefaultStreamHandlerUsesInjectedGuzzleClientWithStreamingEnabled(): void
    {
        $history = [];
        $httpClient = $this->guzzle([$this->streamResponse()], $history);
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', $httpClient);
        (new ServiceProvider($di))->register($di);

        $events = iterator_to_array($di->getShared('openAi')->responses()->createStreamed($this->requestPayload()));

        self::assertCount(1, $events);
        self::assertSame('response.output_text.delta', $events[0]->event);
        self::assertSame('Synthetic output', $events[0]->response->delta);
        self::assertTrue($history[0]['options']['stream']);
        self::assertTrue(json_decode((string)$history[0]['request']->getBody(), true)['stream']);
    }

    public function testInjectedStreamHandlerIsPreservedAndSupportsNonSeekableBodies(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects(self::never())->method('sendRequest');
        $response = $this->streamResponse();
        $response = $response->withBody(new NoSeekStream($response->getBody()));
        $requests = new \ArrayObject();
        $handler = new class ($response, $requests) {
            public function __construct(
                private readonly ResponseInterface $response,
                private readonly \ArrayObject $requests
            ) {
            }

            public function __invoke(RequestInterface $request): ResponseInterface
            {
                $this->requests->append($request);

                return $this->response;
            }
        };
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', $httpClient);
        $di->setShared('openAiStreamHandler', fn () => $handler);
        (new ServiceProvider($di))->register($di);

        $events = iterator_to_array($di->getShared('openAi')->responses()->createStreamed($this->requestPayload()));

        self::assertSame($handler, $di->getShared('openAiStreamHandler'));
        self::assertCount(1, $requests);
        self::assertSame('Synthetic output', $events[0]->response->delta);
    }

    public function testTransportsCanBeReplacedAfterRegistrationBeforeClientResolution(): void
    {
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects(self::once())->method('sendRequest')
            ->willReturn($this->jsonResponse($this->responsePayload()));
        $di = $this->createDi();
        (new ServiceProvider($di))->register($di);
        $di->setShared('openAiHttpClient', $httpClient);
        $response = $this->streamResponse();
        $handler = fn (RequestInterface $request): ResponseInterface => $response;
        $di->setShared('openAiStreamHandler', fn () => $handler);

        $client = $di->getShared('openAi');
        self::assertSame('resp_synthetic', $client->responses()->create($this->requestPayload())->id);
        self::assertCount(1, iterator_to_array($client->responses()->createStreamed($this->requestPayload())));
    }

    public function testPsr18OnlyTransportRequiresExplicitHandlerOnlyWhenStreaming(): void
    {
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', $this->createStub(ClientInterface::class));
        (new ServiceProvider($di))->register($di);
        $client = $di->getShared('openAi');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Register an openAiStreamHandler callable');
        $client->responses()->createStreamed($this->requestPayload());
    }

    #[DataProvider('invalidTransportServices')]
    public function testInvalidTransportServicesFailWithClearErrors(string $service, string $message): void
    {
        $di = $this->createDi();
        $di->setShared($service, new \stdClass());
        (new ServiceProvider($di))->register($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage($message);
        $di->getShared('openAi');
    }

    public static function invalidTransportServices(): array
    {
        return [
            ['openAiHttpClient', 'openAiHttpClient service must implement Psr\\Http\\Client\\ClientInterface'],
            ['openAiStreamHandler', 'openAiStreamHandler service must be callable'],
        ];
    }

    public function testUsageCaptureIntegratesWithBothInjectedTransports(): void
    {
        $capture = new UsageCapture();
        $payload = $this->responsePayload();
        $payload['usage']['input_tokens_details'] += [
            'cache_write_tokens' => 5,
            'cache_write_tokens_details' => ['ttl_30m_tokens' => 2, 'ttl_12h_tokens' => 3],
        ];
        $streamPayload = array_replace($payload, ['id' => 'resp_streamed']);
        $event = json_encode([
            'type' => 'response.completed',
            'response' => $streamPayload,
            'sequence_number' => 1,
        ], JSON_THROW_ON_ERROR);
        $download = "Synthetic file\0bytes\n";
        $history = [];
        $baseClient = $this->guzzle([
            $this->jsonResponse($payload),
            new Response(200, ['Content-Type' => 'text/event-stream'], "data: {$event}\n\ndata: [DONE]\n\n"),
            new Response(200, ['Content-Type' => 'application/octet-stream'], $download),
        ], $history);
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', new UsageCaptureHttpClient($baseClient, $capture));
        $di->setShared('openAiStreamHandler', fn () => $capture->wrapStreamHandler(
            fn (RequestInterface $request): ResponseInterface => $baseClient->send($request, ['stream' => true])
        ));
        (new ServiceProvider($di))->register($di);
        $client = $di->getShared('openAi');

        $response = $client->responses()->create($this->requestPayload());
        self::assertEqualsCanonicalizing($payload['usage'], $capture->takeForResponseId($response->id)['usage']);
        self::assertSame([], $capture->takeForResponseId($response->id));

        $stream = $client->responses()->createStreamed($this->requestPayload());
        self::assertSame([], $capture->takeForResponseId('resp_streamed'));
        $events = iterator_to_array($stream);
        $streamedResponse = $events[0]->response->response;
        self::assertSame('resp_streamed', $streamedResponse->id);
        self::assertEqualsCanonicalizing(
            $streamPayload['usage'],
            $capture->takeForResponseId($streamedResponse->id)['usage']
        );
        self::assertSame([], $capture->takeForResponseId($streamedResponse->id));
        self::assertTrue($history[1]['options']['stream']);
        self::assertSame($download, $client->files()->download('file_synthetic'));
        self::assertSame('/v1/files/file_synthetic/content', $history[2]['request']->getUri()->getPath());
    }

    public function testFileUploadDownloadAndDeleteUseInjectedHttpTransport(): void
    {
        $history = [];
        $httpClient = $this->guzzle([
            $this->jsonResponse([
                'id' => 'file_synthetic',
                'object' => 'file',
                'bytes' => 22,
                'created_at' => 1,
                'filename' => 'synthetic.txt',
                'purpose' => 'user_data',
                'status' => 'processed',
            ]),
            new Response(200, ['Content-Type' => 'application/octet-stream'], "Synthetic file content\0\n"),
            $this->jsonResponse(['id' => 'file_synthetic', 'object' => 'file', 'deleted' => true]),
        ], $history);
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', $httpClient);
        (new ServiceProvider($di))->register($di);
        $file = Utils::streamFor('Synthetic file content')->detach();

        try {
            $client = $di->getShared('openAi');
            $uploaded = $client->files()->upload(['purpose' => 'user_data', 'file' => $file]);
            $downloaded = $client->files()->download($uploaded->id);
            $deleted = $client->files()->delete($uploaded->id);

            self::assertSame('file_synthetic', $uploaded->id);
            self::assertSame("Synthetic file content\0\n", $downloaded);
            self::assertTrue($deleted->deleted);
            self::assertSame('POST', $history[0]['request']->getMethod());
            self::assertSame('/v1/files', $history[0]['request']->getUri()->getPath());
            self::assertStringStartsWith('multipart/form-data;', $history[0]['request']->getHeaderLine('Content-Type'));
            self::assertStringContainsString('Synthetic file content', (string)$history[0]['request']->getBody());
            self::assertStringContainsString('user_data', (string)$history[0]['request']->getBody());
            self::assertSame('GET', $history[1]['request']->getMethod());
            self::assertSame('/v1/files/file_synthetic/content', $history[1]['request']->getUri()->getPath());
            self::assertSame('DELETE', $history[2]['request']->getMethod());
            self::assertSame('/v1/files/file_synthetic', $history[2]['request']->getUri()->getPath());
        }
        finally {
            if (is_resource($file)) {
                fclose($file);
            }
        }
    }

    #[DataProvider('configurationAliases')]
    public function testConfigurationAliasesReachSdkHeadersAndUri(array $config, array $expected): void
    {
        $history = [];
        $di = $this->createDi($config, false);
        $di->setShared('openAiHttpClient', $this->guzzle([$this->jsonResponse($this->responsePayload())], $history));
        (new ServiceProvider($di))->register($di);
        $di->getShared('openAi')->responses()->create($this->requestPayload());
        $request = $history[0]['request'];

        self::assertSame($expected[0], $request->getHeaderLine('Authorization'));
        self::assertSame($expected[1], $request->getHeaderLine('OpenAI-Organization'));
        self::assertSame($expected[2], $request->getHeaderLine('OpenAI-Project'));
        self::assertSame($expected[3], (string)$request->getUri());
    }

    public static function configurationAliases(): array
    {
        return [
            'canonical keys take precedence' => [[
                'apiKey' => ' canonical-key ', 'secretKey' => 'legacy-key',
                'organization' => ' canonical-org ', 'organizationId' => 'legacy-org',
                'project' => ' canonical-project ', 'projectId' => 'legacy-project',
                'baseUri' => ' https://gateway.example.test/api ',
            ], ['Bearer canonical-key', 'canonical-org', 'canonical-project', 'https://gateway.example.test/api/responses']],
            'empty canonical keys fall back to aliases' => [[
                'apiKey' => ' ', 'secretKey' => 'legacy-key',
                'organization' => '', 'organizationId' => 'legacy-org',
                'project' => null, 'projectId' => 'legacy-project', 'baseUri' => '',
            ], ['Bearer legacy-key', 'legacy-org', 'legacy-project', 'https://api.openai.com/v1/responses']],
            'absent optional keys omit headers' => [[
                'secretKey' => 'legacy-key',
            ], ['Bearer legacy-key', '', '', 'https://api.openai.com/v1/responses']],
        ];
    }

    #[DataProvider('sdkHttpErrors')]
    public function testSdkHttpErrorsPropagateForNormalAndStreamedRequests(
        int $status,
        string $body,
        string $exception,
        bool $streamed
    ): void {
        $history = [];
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', $this->guzzle([
            new Response($status, ['Content-Type' => 'application/json'], $body),
        ], $history));
        (new ServiceProvider($di))->register($di);
        $responses = $di->getShared('openAi')->responses();

        $this->expectException($exception);
        if ($streamed) {
            $responses->createStreamed($this->requestPayload());
        } else {
            $responses->create($this->requestPayload());
        }
    }

    public static function sdkHttpErrors(): array
    {
        $cases = [];
        foreach ([false, true] as $streamed) {
            $cases[] = [
                400,
                '{"error":{"message":"Invalid synthetic input","type":"invalid_request_error","code":"invalid"}}',
                ErrorException::class,
                $streamed,
            ];
            // Preserve Guzzle send() HTTP-error behavior for the default stream handler.
            $cases[] = [
                429,
                '{"error":{"message":"Rate limit","type":"rate_limit_error","code":"rate_limit"}}',
                $streamed ? ErrorException::class : RateLimitException::class,
                $streamed,
            ];
            $cases[] = [
                500,
                '{"error":{"message":"Synthetic server error","type":"server_error","code":"server_error"}}',
                $streamed ? TransporterException::class : ServerException::class,
                $streamed,
            ];
            $cases[] = [400, 'invalid-json', UnserializableResponse::class, $streamed];
        }
        $cases[] = [200, 'invalid-json', UnserializableResponse::class, false];

        return $cases;
    }

    #[DataProvider('requestModes')]
    public function testTransportFailureRetainsUnderlyingException(bool $streamed): void
    {
        $failure = new ConnectException('Synthetic connection failure', new Request('POST', 'https://example.test'));
        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($streamed ? self::never() : self::once())
            ->method('sendRequest')->willThrowException($failure);
        $di = $this->createDi();
        $di->setShared('openAiHttpClient', $httpClient);
        $di->setShared('openAiStreamHandler', fn () => fn (RequestInterface $request): never => throw $failure);
        (new ServiceProvider($di))->register($di);

        try {
            $responses = $di->getShared('openAi')->responses();
            if ($streamed) {
                $responses->createStreamed($this->requestPayload());
            } else {
                $responses->create($this->requestPayload());
            }
            self::fail('Expected the SDK transport exception.');
        }
        catch (TransporterException $exception) {
            self::assertSame($failure, $exception->getPrevious());
        }
    }

    public static function requestModes(): array
    {
        return [[false], [true]];
    }

    public function testInjectedStreamHandlerPreservesSdkRateLimitError(): void
    {
        $di = $this->createDi();
        $response = new Response(429, ['Content-Type' => 'application/json'], '{}');
        $di->setShared('openAiStreamHandler', fn () => fn (RequestInterface $request): ResponseInterface => $response);
        (new ServiceProvider($di))->register($di);

        $this->expectException(RateLimitException::class);
        $di->getShared('openAi')->responses()->createStreamed($this->requestPayload());
    }

    #[DataProvider('streamErrors')]
    public function testStreamDecodingErrorsPropagate(string $data, string $exception): void
    {
        $di = $this->createDi();
        $response = new Response(200, ['Content-Type' => 'text/event-stream'], "data: {$data}\n\n");
        $di->setShared('openAiStreamHandler', fn () => fn (RequestInterface $request): ResponseInterface => $response);
        (new ServiceProvider($di))->register($di);

        $this->expectException($exception);
        iterator_to_array($di->getShared('openAi')->responses()->createStreamed($this->requestPayload()));
    }

    public static function streamErrors(): array
    {
        return [
            ['invalid-json', \JsonException::class],
            [
                '{"error":{"message":"Synthetic stream error","type":"server_error","code":"stream_error"}}',
                ErrorException::class,
            ],
        ];
    }

    private function createDi(array $config = [], bool $defaults = true): Di
    {
        $di = new Di();
        $di->setShared('config', new Config(['openai' => array_replace($defaults ? [
            'apiKey' => 'synthetic-key',
            'baseUri' => 'https://api.example.test/v1',
        ] : [], $config)]));

        return $di;
    }

    private function guzzle(array $responses, array &$history): Client
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        return new Client(['handler' => $stack]);
    }

    private function jsonResponse(array $payload): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function streamResponse(): Response
    {
        $data = json_encode([
            'type' => 'response.output_text.delta',
            'content_index' => 0,
            'delta' => 'Synthetic output',
            'item_id' => 'msg_synthetic',
            'output_index' => 0,
            'sequence_number' => 0,
        ], JSON_THROW_ON_ERROR);

        return new Response(200, ['Content-Type' => 'text/event-stream'], "data: {$data}\n\ndata: [DONE]\n\n");
    }

    private function requestPayload(): array
    {
        return ['model' => 'synthetic-model', 'input' => 'Synthetic input'];
    }

    private function responsePayload(): array
    {
        return [
            'id' => 'resp_synthetic',
            'object' => 'response',
            'created_at' => 1,
            'status' => 'completed',
            'model' => 'synthetic-model',
            'output' => [],
            'parallel_tool_calls' => false,
            'tool_choice' => 'auto',
            'tools' => [],
            'usage' => [
                'input_tokens' => 10,
                'input_tokens_details' => ['cached_tokens' => 3],
                'output_tokens' => 2,
                'output_tokens_details' => ['reasoning_tokens' => 1],
                'total_tokens' => 12,
            ],
        ];
    }
}
