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

namespace PhalconKit\Tests\Unit\OpenAi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use OpenAI;
use PhalconKit\OpenAi\UsageCapture;
use PhalconKit\OpenAi\UsageCaptureHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class UsageCaptureTest extends TestCase
{
    public function testRealSdkDecodesResponseWhileCapturePreservesCompleteBillingUsage(): void
    {
        $payload = $this->fixture();
        $payload['usage']['private_note'] = 'synthetic-private-note';
        $capture = new UsageCapture();
        $http = new UsageCaptureHttpClient(new Client(['handler' => new MockHandler([$this->response($payload)])]), $capture);
        $client = OpenAI::factory()->withApiKey('synthetic-key')->withHttpClient($http)->make();
        $response = $client->responses()->create(['model' => 'model-fixture', 'input' => 'Synthetic input.']);

        $this->assertSame('resp_fixture', $response->id);
        $this->assertSame(40, $response->toArray()['usage']['input_tokens_details']['cached_tokens']);
        $this->assertSame([
            'usage' => $this->fixture()['usage'], 'usage_valid' => true,
            'model' => 'model-fixture', 'service_tier' => 'default',
        ], $capture->takeForResponseId($response->id));
        $this->assertSame([], $capture->takeForResponseId($response->id));
    }

    #[DataProvider('bodyReadModes')]
    public function testCaptureIsLazyAndPreservesBodyForSeekableAndNonseekableReads(string $mode, bool $seekable): void
    {
        $capture = new UsageCapture();
        $original = $this->response($this->fixture());
        if (!$seekable) {
            $original = $original->withBody(new NoSeekStream($original->getBody()));
        }
        $response = $capture->wrapResponse($this->request(), $original);
        $body = $response->getBody();
        $this->assertSame(0, $body->tell());
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
        $this->assertSame($seekable, $body->isSeekable());
        $expected = json_encode($this->fixture(), JSON_THROW_ON_ERROR);
        if ($mode === 'string') {
            $actual = (string) $body;
        } elseif ($mode === 'contents') {
            $actual = $body->getContents();
        } else {
            $actual = '';
            while (!$body->eof()) {
                $actual .= $body->read(7);
            }
        }
        $this->assertSame($expected, $actual);
        $this->assertSame($this->fixture()['usage'], $capture->takeForResponseId('resp_fixture')['usage']);
        if ($seekable) {
            $body->rewind();
            $this->assertSame($expected, $body->getContents());
        }
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
    }

    public static function bodyReadModes(): iterable
    {
        foreach (['string', 'contents', 'read'] as $mode) {
            foreach ([true, false] as $seekable) {
                yield [$mode, $seekable];
            }
        }
    }

    public function testTerminalStreamingUsageIsCapturedIncrementallyWithoutRetainingOtherEvents(): void
    {
        $capture = new UsageCapture();
        $first = $this->event('response.completed', $this->fixture('resp_first'));
        $second = $this->event('response.incomplete', $this->fixture('resp_second'));
        $nonterminal = $this->fixture('resp_first');
        $nonterminal['usage'] = null;
        $tail = $this->event('response.in_progress', $nonterminal) . $first . "data: [DONE]\n\n";
        $stream = new NoSeekStream(Utils::streamFor($first . $second . $tail));
        $handler = $capture->wrapStreamHandler(fn () => new Response(200, ['Content-Type' => 'text/event-stream'], $stream));
        $body = $handler($this->request())->getBody();

        $this->assertSame(0, $body->tell());
        $this->assertSame($first, $body->read(strlen($first)));
        $this->assertSame($this->fixture()['usage'], $capture->takeForResponseId('resp_first')['usage']);
        $this->assertSame([], $capture->takeForResponseId('resp_second'));
        $this->assertSame($second . $tail, $body->getContents());
        $this->assertSame($this->fixture()['usage'], $capture->takeForResponseId('resp_second')['usage']);
        $this->assertSame([], $capture->takeForResponseId('resp_first'));
    }

    public function testStringConversionAfterPartialPeekCapturesFullBodyWithoutRecreatingConsumedUsage(): void
    {
        $capture = new UsageCapture();
        $wire = json_encode($this->fixture(), JSON_THROW_ON_ERROR);
        $body = $capture->wrapResponse($this->request(), $this->response($this->fixture()))->getBody();
        $this->assertSame(substr($wire, 0, 10), $body->read(10));
        $this->assertSame($wire, (string) $body);
        $this->assertSame($this->fixture()['usage'], $capture->takeForResponseId('resp_fixture')['usage']);
        $this->assertSame($wire, (string) $body);
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
    }

    public function testSplitCrLfSseLinesAndFailedTerminalEventsCaptureUsage(): void
    {
        $capture = new UsageCapture();
        $wire = str_replace("\n", "\r\n", $this->event('response.failed', $this->fixture()));
        $response = new Response(200, ['Content-Type' => 'text/event-stream; charset=UTF-8'], $wire);
        $body = $capture->wrapResponse($this->request(), $response)->getBody();
        $actual = '';
        while (!$body->eof()) {
            $actual .= $body->read(1);
        }
        $this->assertSame($wire, $actual);
        $this->assertSame($this->fixture()['usage'], $capture->takeForResponseId('resp_fixture')['usage']);
    }

    public function testRetrieveAssociationMissingUsageAndNonterminalEventsDoNotReturnStaleCounters(): void
    {
        $capture = new UsageCapture();
        $this->consume($capture, $this->fixture());
        $missing = $this->fixture();
        unset($missing['usage']);
        $this->consume($capture, $missing, new Request('GET', 'https://example.invalid/v1/responses/resp_fixture'));
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));

        $this->consume($capture, $this->fixture(), new Request('GET', 'https://example.invalid/v1/responses/resp_other'));
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
        $this->assertSame([], $capture->takeForResponseId('resp_other'));

        $this->consume($capture, $this->fixture());
        $stream = new Response(200, ['Content-Type' => 'text/event-stream'], $this->event('response.created', $missing));
        $capture->wrapResponse($this->request(), $stream)->getBody()->getContents();
        $this->assertSame($this->fixture()['usage'], $capture->takeForResponseId('resp_fixture')['usage']);
    }

    #[DataProvider('invalidUsageCases')]
    public function testMalformedCountersCannotBecomeTrustedBillingNumbers(mixed $usage): void
    {
        $capture = new UsageCapture();
        $payload = $this->fixture();
        $payload['usage'] = $usage;
        $payload['model'] = 'synthetic private model text';
        $payload['service_tier'] = ['private' => 'metadata'];
        $this->consume($capture, $payload);
        $captured = $capture->takeForResponseId('resp_fixture');
        $this->assertFalse($captured['usage_valid']);
        $this->assertArrayNotHasKey('model', $captured);
        $this->assertArrayNotHasKey('service_tier', $captured);
        $this->assertStringNotContainsString('private', json_encode($captured, JSON_THROW_ON_ERROR));
    }

    public static function invalidUsageCases(): iterable
    {
        yield ['private'];
        yield [['input_tokens' => '12']];
        yield [['output_tokens' => -1]];
        yield [['total_tokens' => 1.5]];
        yield [['input_tokens_details' => ['cached_tokens' => true]]];
        yield [['input_tokens_details' => ['cache_write_tokens' => ['private' => 'metadata']]]];
        yield [['input_tokens_details' => ['cache_write_tokens_details' => ['ttl_12h_tokens' => 'private']]]];
        yield [['input_tokens_details' => ['cache_write_tokens_details' => null]]];
        yield [['output_tokens_details' => ['private']]];
    }

    public function testMissingAndZeroCountersRemainDistinct(): void
    {
        $capture = new UsageCapture();
        $payload = $this->fixture();
        $payload['usage'] = ['input_tokens_details' => ['cache_write_tokens' => 0]];
        $this->consume($capture, $payload);
        $this->assertSame($payload['usage'], $capture->takeForResponseId('resp_fixture')['usage']);
        $payload['usage'] = null;
        $this->consume($capture, $payload);
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
    }

    public function testCapacityEvictsOldestAndResetClearsUnconsumedCounters(): void
    {
        $capture = new UsageCapture(2);
        foreach (['resp_one', 'resp_two', 'resp_three'] as $id) {
            $this->consume($capture, $this->fixture($id));
        }
        $this->assertSame([], $capture->takeForResponseId('resp_one'));
        $this->assertNotEmpty($capture->takeForResponseId('resp_two'));
        $capture->reset();
        $this->assertSame([], $capture->takeForResponseId('resp_three'));
        $this->expectException(InvalidArgumentException::class);
        new UsageCapture(0);
    }

    public function testUnrelatedEndpointsErrorsAndUnreadableBodiesPassThrough(): void
    {
        $capture = new UsageCapture();
        $response = $this->response($this->fixture());
        foreach ([['POST', '/files'], ['DELETE', '/responses/resp_fixture'], ['POST', '/chat/completions']] as [$method, $path]) {
            $this->assertSame($response, $capture->wrapResponse(new Request($method, 'https://example.invalid/v1' . $path), $response));
        }
        foreach ([400, 429, 500] as $status) {
            $error = $response->withStatus($status);
            $this->assertSame($error, $capture->wrapResponse($this->request(), $error));
        }
        $text = $response->withHeader('Content-Type', 'text/plain');
        $this->assertSame($text, $capture->wrapResponse($this->request(), $text));
        $unreadable = $response->withBody(FnStream::decorate($response->getBody(), [
            'isReadable' => fn () => false,
            'read' => fn () => throw new RuntimeException('must not read'),
        ]));
        $this->assertSame($unreadable, $capture->wrapResponse($this->request(), $unreadable));
    }

    public function testMalformedBodiesAndNonresponseObjectsLeaveBodyBytesUnchanged(): void
    {
        $capture = new UsageCapture();
        $payload = $this->fixture();
        $payload['object'] = 'file';
        foreach (['broken json', 'null', '[]', json_encode($payload, JSON_THROW_ON_ERROR)] as $wire) {
            $response = new Response(200, ['Content-Type' => 'application/json'], $wire);
            $this->assertSame($wire, (string) $capture->wrapResponse($this->request(), $response)->getBody());
        }
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
    }

    public function testOversizedJsonIsIgnoredAndOversizedSseLineDoesNotHideLaterUsage(): void
    {
        $capture = new UsageCapture();
        $large = str_repeat('x', 8 * 1024 * 1024 + 1);
        $response = new Response(200, ['Content-Type' => 'application/json'], $large);
        $this->assertSame($large, $capture->wrapResponse($this->request(), $response)->getBody()->getContents());
        $wire = 'data: ' . $large . "\n\n" . $this->event('response.completed', $this->fixture());
        $response = new Response(200, ['Content-Type' => 'text/event-stream'], $wire);
        $this->assertSame($wire, $capture->wrapResponse($this->request(), $response)->getBody()->getContents());
        $this->assertSame($this->fixture()['usage'], $capture->takeForResponseId('resp_fixture')['usage']);
    }

    public function testTransportAndBodyReadExceptionsPropagateUnchanged(): void
    {
        $capture = new UsageCapture();
        $failure = new ConnectException('Synthetic transport failure.', $this->request());
        $http = new UsageCaptureHttpClient(new Client(['handler' => new MockHandler([$failure])]), $capture);
        try {
            $http->sendRequest($this->request());
            $this->fail('Transport exception was swallowed.');
        } catch (ConnectException $exception) {
            $this->assertSame($failure, $exception);
        }
        $readFailure = new RuntimeException('Synthetic read failure.');
        $stream = FnStream::decorate(Utils::streamFor(''), ['read' => fn () => throw $readFailure]);
        $response = new Response(200, ['Content-Type' => 'application/json'], $stream);
        try {
            $capture->wrapResponse($this->request(), $response)->getBody()->read(1);
            $this->fail('Read exception was swallowed.');
        } catch (RuntimeException $exception) {
            $this->assertSame($readFailure, $exception);
        }
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
    }

    public function testSeekingClosingAndDetachingPreserveNativeSemantics(): void
    {
        $capture = new UsageCapture();
        $body = $capture->wrapResponse($this->request(), $this->response($this->fixture()))->getBody();
        $body->read(10);
        $body->seek(3);
        $this->assertSame(3, $body->tell());
        $body->getContents();
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
        $body->rewind();
        $resource = $body->detach();
        $this->assertIsResource($resource);
        $this->assertSame(json_encode($this->fixture(), JSON_THROW_ON_ERROR), stream_get_contents($resource));
        fclose($resource);

        $body = $capture->wrapResponse($this->request(), $this->response($this->fixture()))->getBody();
        $body->read(10);
        $body->close();
        $this->assertFalse($body->isReadable());
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
    }

    public function testReadFailureDiscardsTransientBufferEvenIfTheBodyLaterRecovers(): void
    {
        $capture = new UsageCapture();
        $wire = json_encode($this->fixture(), JSON_THROW_ON_ERROR);
        $underlying = Utils::streamFor($wire);
        $reads = 0;
        $stream = FnStream::decorate($underlying, [
            'read' => function (int $length) use ($underlying, &$reads): string {
                if (++$reads === 2) {
                    throw new RuntimeException('Synthetic interrupted read.');
                }
                return $underlying->read($length);
            },
        ]);
        $response = new Response(200, ['Content-Type' => 'application/json'], $stream);
        $body = $capture->wrapResponse($this->request(), $response)->getBody();
        $this->assertSame(substr($wire, 0, 10), $body->read(10));
        try {
            $body->read(10);
            $this->fail('Read exception was swallowed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic interrupted read.', $exception->getMessage());
        }
        $this->assertSame(substr($wire, 10), $body->getContents());
        $this->assertSame([], $capture->takeForResponseId('resp_fixture'));
    }

    private function consume(UsageCapture $capture, array $payload, ?Request $request = null): void
    {
        $capture->wrapResponse($request ?? $this->request(), $this->response($payload))->getBody()->getContents();
    }

    private function request(): Request
    {
        return new Request('POST', 'https://example.invalid/v1/responses');
    }

    private function response(array $payload): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function event(string $type, array $payload): string
    {
        return 'event: ' . $type . "\ndata: " . json_encode(['type' => $type, 'response' => $payload], JSON_THROW_ON_ERROR) . "\n\n";
    }

    private function fixture(string $id = 'resp_fixture'): array
    {
        return [
            'id' => $id, 'object' => 'response', 'created_at' => 1700000000, 'status' => 'completed',
            'model' => 'model-fixture', 'service_tier' => 'default', 'parallel_tool_calls' => true,
            'tool_choice' => 'auto', 'tools' => [], 'metadata' => ['note' => 'synthetic-private-note'],
            'output' => [],
            'usage' => [
                'input_tokens' => 100, 'output_tokens' => 20, 'total_tokens' => 120,
                'input_tokens_details' => [
                    'cached_tokens' => 40, 'cache_write_tokens' => 30,
                    'cache_write_tokens_details' => ['ttl_30m_tokens' => 20, 'ttl_12h_tokens' => 10],
                ],
                'output_tokens_details' => ['reasoning_tokens' => 10],
            ],
        ];
    }
}
