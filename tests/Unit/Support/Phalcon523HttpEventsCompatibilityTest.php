<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Support;

use Phalcon\Contracts\Events\Event;
use Phalcon\Events\Manager;
use Phalcon\Http\Response\Headers;
use PhalconKit\Events\EventsAwareTrait;
use PhalconKit\Http\Request;
use PhalconKit\Http\Response;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Keep Core request, response and event behavior stable through native fast paths.
 */
class Phalcon523HttpEventsCompatibilityTest extends AbstractUnit
{
    public function testRequestMethodAndDiagnosticHeadersTrackCurrentServerValues(): void
    {
        $server = $_SERVER;
        $post = $_POST;
        $requestData = $_REQUEST;

        try {
            $_SERVER = [];
            $_POST = [];
            $_REQUEST = [];
            $request = new Request();
            $request->setDI($this->di);
            $this->assertSame('GET', $request->getMethod());

            $_SERVER = [
                'REQUEST_METHOD' => 'put',
                'HTTP_HOST' => 'api.example.test',
                'HTTP_ORIGIN' => 'https://client.example.test',
                'HTTP_X_REQUEST_ID' => 'sample-42',
                'CONTENT_TYPE' => 'application/json',
            ];
            $snapshot = $request->toArray();
            $this->assertSame('PUT', $snapshot['method']);
            $this->assertTrue($snapshot['isPut']);
            $this->assertTrue($snapshot['isCors']);
            $this->assertSame('sample-42', $snapshot['headers']['X-Request-Id']);
            $this->assertSame('application/json', $snapshot['contentType']);

            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = 'DELETE';
            $this->assertSame('DELETE', $request->getMethod());
            unset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            $_REQUEST['_method'] = 'PATCH';
            $request->setHttpMethodParameterOverride(true);
            $this->assertSame('PATCH', $request->getMethod());
            $request->setHttpMethodParameterOverride(false);
            $this->assertSame('POST', $request->getMethod());
        } finally {
            $_SERVER = $server;
            $_POST = $post;
            $_REQUEST = $requestData;
        }
    }

    /**
     * @return array<string, array{bool, bool, bool, list<string>}>
     */
    public static function headerSendingCases(): array
    {
        return [
            'without manager' => [false, true, false, []],
            'successful send' => [true, true, false, ['beforeSendHeaders', 'afterSendHeaders']],
            'veto before sending' => [true, true, true, ['beforeSendHeaders']],
            'failed send' => [true, false, false, ['beforeSendHeaders']],
        ];
    }

    /**
     * @param list<string> $expectedEvents
     */
    #[DataProvider('headerSendingCases')]
    public function testResponseHeaderEventsPreserveVetoAndSendResult(
        bool $withManager,
        bool $sendResult,
        bool $veto,
        array $expectedEvents
    ): void {
        $response = new Response();
        $headers = $this->createMock(Headers::class);
        $headers->expects($veto ? $this->never() : $this->once())->method('send')->willReturn($sendResult);
        // Native setHeaders() merges values; inject the sender to exercise failures
        // without emitting actual headers into the PHPUnit process.
        new \ReflectionProperty(\Phalcon\Http\Response::class, 'headers')->setValue($response, $headers);
        $events = [];

        if ($withManager) {
            $manager = new Manager();
            $manager->attach('response', function (Event $event, object $source) use (&$events, $response, $veto): bool {
                $this->assertSame($response, $source);
                $events[] = $event->getType();

                return !$veto;
            });
            $response->setEventsManager($manager);
        }

        $result = $response->sendHeaders();

        $this->assertSame($veto ? false : $response, $result);
        $this->assertSame($expectedEvents, $events);
    }

    /**
     * @return array<string, array{bool, bool, list<string>}>
     */
    public static function eventQueueCases(): array
    {
        return [
            'qualified listener only' => [false, false, ['qualified']],
            'both listener queues' => [true, false, ['type', 'qualified']],
            'type listener stops event' => [true, true, ['type']],
        ];
    }

    /**
     * @param list<string> $expectedCalls
     */
    #[DataProvider('eventQueueCases')]
    public function testCoreEventTraitPreservesTypeAndQualifiedQueueDispatch(
        bool $withTypeListener,
        bool $stop,
        array $expectedCalls
    ): void {
        $subject = new class {
            use EventsAwareTrait;
        };
        $subject::setEventsPrefix('upgrade');
        $manager = new Manager();
        $calls = [];
        if ($withTypeListener) {
            $manager->attach('upgrade', static function (Event $event) use (&$calls, $stop): string {
                $calls[] = 'type';
                if ($stop) {
                    $event->stop();
                }

                return 'type';
            });
        }
        $manager->attach('upgrade:run', static function () use (&$calls): string {
            $calls[] = 'qualified';

            return 'qualified';
        });
        $subject->setEventsManager($manager);

        $result = $subject->fire('run', cancelable: true);

        $this->assertSame($expectedCalls, $calls);
        $this->assertSame($stop ? 'type' : 'qualified', $result);
        $calls = [];
        $this->assertSame($expectedCalls, $manager->fireAll('upgrade:run', $subject));
        $this->assertSame($expectedCalls, $calls);
    }

    public function testCustomEventManagerHooksStillRunForEveryCoreEvent(): void
    {
        $subject = new class {
            use EventsAwareTrait;
        };
        $subject::setEventsPrefix('upgrade');
        $manager = new class extends Manager {
            public int $beforeCalls = 0;
            public int $afterCalls = 0;

            /**
             * Count every invocation and veto the explicit stop payload.
             */
            #[\Override]
            protected function beforeFire(string $eventType, mixed $source, mixed $data = null, bool $cancelable = true): bool
            {
                ++$this->beforeCalls;

                return $data !== 'stop';
            }

            /**
             * Count completed dispatches and preserve the listener result.
             */
            #[\Override]
            protected function afterFire(mixed $status, string $eventType, mixed $source, mixed $data = null, bool $cancelable = true): mixed
            {
                ++$this->afterCalls;

                return $status;
            }
        };
        $deliveries = 0;
        $manager->attach('upgrade:run', static function () use (&$deliveries): string {
            ++$deliveries;

            return 'handled';
        });
        $subject->setEventsManager($manager);

        $this->assertSame('handled', $subject->fire('run'));
        $this->assertSame('handled', $subject->fire('run'));
        $this->assertNull($subject->fire('run', 'stop'));
        $this->assertSame(3, $manager->beforeCalls);
        $this->assertSame(2, $manager->afterCalls);
        $this->assertSame(2, $deliveries);
    }
}
