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

namespace PhalconKit\Tests\Unit\Mvc\Dispatcher;

use Phalcon\Dispatcher\Exception as DispatchException;
use Phalcon\Events\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Exception\HttpException;
use PhalconKit\Http\Response;
use PhalconKit\Mvc\Dispatcher;
use PhalconKit\Mvc\Dispatcher\Error;
use PhalconKit\Tests\Unit\AbstractUnit;

class ErrorTest extends AbstractUnit
{
    #[DataProvider('validHttpStatusProvider')]
    public function testHttpExceptionPreservesValidStatus(int $status, string $reason): void
    {
        [$listener, $dispatcher, $response] = $this->createListener([
            'router' => [
                'httpException' => [
                    'module' => 'api',
                    'params' => [
                        'tenant' => 'acme',
                    ],
                ],
            ],
        ]);
        $exception = new HttpException('request-failure', $status);

        $handled = $listener->beforeException($this->createEvent($listener), $dispatcher, $exception);

        $this->assertFalse($handled);
        $this->assertSame($status, $response->getStatusCode());
        $this->assertSame($reason, $response->getReasonPhrase());
        $this->assertSame('api', $dispatcher->getModuleName());
        $this->assertSame('error', $dispatcher->getControllerName());
        $this->assertSame('error', $dispatcher->getActionName());
        $this->assertSame('acme', $dispatcher->getParam('tenant'));
        $this->assertSame($status, $dispatcher->getParam('code'));
        $this->assertSame($exception, $dispatcher->getParam('exception'));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function validHttpStatusProvider(): array
    {
        return [
            'bad request' => [400, 'Bad Request'],
            'unauthorized' => [401, 'Unauthorized'],
            'forbidden' => [403, 'Forbidden'],
            'not found' => [404, 'Not Found'],
            'conflict' => [409, 'Conflict'],
            'unprocessable entity' => [422, 'Unprocessable Entity'],
            'too many requests' => [429, 'Too Many Requests'],
            'unmapped client error' => [430, 'Bad Request'],
            'internal server error' => [500, 'Internal Server Error'],
            'service unavailable' => [503, 'Service Unavailable'],
            'unmapped server error' => [530, 'Internal Server Error'],
        ];
    }

    #[DataProvider('invalidHttpStatusProvider')]
    public function testHttpExceptionInvalidStatusFailsSafelyAs500(int $status): void
    {
        [$listener, $dispatcher, $response] = $this->createListener();
        $exception = new HttpException('invalid-status', $status);

        $handled = $listener->beforeException($this->createEvent($listener), $dispatcher, $exception);

        $this->assertFalse($handled);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getReasonPhrase());
        $this->assertSame('error', $dispatcher->getControllerName());
        $this->assertSame('error', $dispatcher->getActionName());
        $this->assertSame(500, $dispatcher->getParam('code'));
        $this->assertSame($exception, $dispatcher->getParam('exception'));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidHttpStatusProvider(): array
    {
        return [
            'missing' => [0],
            'successful' => [200],
            'negative' => [-1],
            'above range' => [600],
        ];
    }

    public function testRuntimeExceptionCodeDoesNotOwnHttpStatusAndUsesFatalRoute(): void
    {
        [$listener, $dispatcher, $response] = $this->createListener([
            'router' => [
                'error' => [
                    'controller' => 'wrong-error-controller',
                    'action' => 'wrong-error-action',
                ],
                'fatal' => [
                    'controller' => 'server-error',
                    'action' => 'crash',
                    'params' => [
                        'source' => 'listener',
                    ],
                ],
            ],
        ]);
        $exception = new \RuntimeException('private failure', 403);

        $handled = $listener->beforeException($this->createEvent($listener), $dispatcher, $exception);

        $this->assertFalse($handled);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getReasonPhrase());
        $this->assertSame('server-error', $dispatcher->getControllerName());
        $this->assertSame('crash', $dispatcher->getActionName());
        $this->assertSame('listener', $dispatcher->getParam('source'));
        $this->assertSame($exception, $dispatcher->getParam('exception'));
    }

    public function testDebugUnexpectedExceptionRethrowsIdenticalObject(): void
    {
        [$listener, $dispatcher] = $this->createListener([
            'app' => [
                'debug' => true,
            ],
        ]);
        $exception = new \RuntimeException('debug failure');

        try {
            $listener->beforeException($this->createEvent($listener), $dispatcher, $exception);
            $this->fail('The unexpected exception was not rethrown.');
        } catch (\RuntimeException $caught) {
            $this->assertSame($exception, $caught);
        }
    }

    public function testDebugHttpExceptionIsHandledWithoutRethrow(): void
    {
        [$listener, $dispatcher, $response] = $this->createListener([
            'app' => [
                'debug' => true,
            ],
            'debug' => [
                'enable' => true,
            ],
        ]);
        $exception = new HttpException('tenant-not-allowed', 403);

        $handled = $listener->beforeException($this->createEvent($listener), $dispatcher, $exception);

        $this->assertFalse($handled);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Forbidden', $response->getReasonPhrase());
        $this->assertSame($exception, $dispatcher->getParam('exception'));
    }

    #[DataProvider('missingDispatchProvider')]
    public function testNativeMissingDispatchExceptionsUseNotFoundRoute(int $code): void
    {
        [$listener, $dispatcher] = $this->createListener([
            'router' => [
                'notFound' => [
                    'controller' => 'missing',
                ],
            ],
        ]);
        $exception = new DispatchException('missing route target', $code);

        $handled = $listener->beforeException($this->createEvent($listener), $dispatcher, $exception);

        $this->assertFalse($handled);
        $this->assertSame('missing', $dispatcher->getControllerName());
        $this->assertSame('notFound', $dispatcher->getActionName());
        $this->assertSame($exception, $dispatcher->getParam('exception'));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function missingDispatchProvider(): array
    {
        return [
            'handler' => [DispatchException::EXCEPTION_HANDLER_NOT_FOUND],
            'action' => [DispatchException::EXCEPTION_ACTION_NOT_FOUND],
        ];
    }

    /**
     * Create a listener, active dispatcher, and isolated shared response.
     *
     * @param array<string, mixed> $config Listener config overrides.
     *
     * @return array{Error, Dispatcher, Response}
     */
    private function createListener(array $config = []): array
    {
        $config = array_replace_recursive([
            'app' => [
                'debug' => false,
            ],
            'debug' => [
                'enable' => false,
            ],
        ], $config);

        $di = new Di();
        $di->set('config', new Config($config));
        $response = new Response();
        $di->set('response', $response);

        $listener = new Error();
        $listener->setDI($di);

        $dispatcher = new Dispatcher();
        $dispatcher->setModuleName('frontend');
        $dispatcher->setControllerName('index');
        $dispatcher->setActionName('index');

        return [$listener, $dispatcher, $response];
    }

    private function createEvent(Error $listener): Event
    {
        return new Event('dispatch:beforeException', $listener);
    }
}
