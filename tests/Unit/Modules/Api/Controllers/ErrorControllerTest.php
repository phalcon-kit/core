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

namespace PhalconKit\Tests\Unit\Modules\Api\Controllers;

use Phalcon\Events\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use PhalconKit\Exception\HttpException;
use PhalconKit\Http\ResponseInterface;
use PhalconKit\Modules\Admin\Controllers\ErrorController as AdminErrorController;
use PhalconKit\Modules\Api\Controllers\ErrorController as ApiErrorController;
use PhalconKit\Modules\Frontend\Controllers\ErrorController as FrontendErrorController;
use PhalconKit\Mvc\Controller\Restful;
use PhalconKit\Mvc\Dispatcher;
use PhalconKit\Mvc\Dispatcher\Error as DispatcherError;
use PhalconKit\Tests\Unit\AbstractUnit;

class ErrorControllerTest extends AbstractUnit
{
    /**
     * @param class-string<object> $controllerClass
     */
    #[DataProvider('errorControllerProvider')]
    public function testShippedErrorControllersDoNotExposeModelBackedRestActions(string $controllerClass): void
    {
        $controller = new $controllerClass();

        $this->assertNotInstanceOf(Restful::class, $controller);
        $this->assertFalse(method_exists($controller, 'saveAction'));
        $this->assertTrue(method_exists($controller, 'notFoundAction'));
        $this->assertTrue(method_exists($controller, 'fatalAction'));
    }

    /**
     * @return array<string, array{class-string<object>}>
     */
    public static function errorControllerProvider(): array
    {
        return [
            'api' => [ApiErrorController::class],
            'admin' => [AdminErrorController::class],
            'frontend' => [FrontendErrorController::class],
        ];
    }

    public function testApiHttpExceptionResponseUsesRestMessageContract(): void
    {
        $exception = new HttpException('tenant-not-allowed', 403);
        [$response, $payload] = $this->dispatchApiException($exception);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Forbidden', $response->getReasonPhrase());
        $this->assertSame(403, $payload['code']);
        $this->assertSame('Forbidden', $payload['status']);
        $this->assertSame([
            'field' => '',
            'message' => 'tenant-not-allowed',
            'type' => 'HttpException',
            'code' => 403,
            'metaData' => [],
        ], $payload['view']['messages'][0]);

        $json = $response->getContent();
        $this->assertStringNotContainsString($exception->getFile(), $json);
        $this->assertStringNotContainsString('previous', $json);
        $this->assertStringNotContainsString('trace', $json);
    }

    public function testApiFatalResponseDoesNotExposeUnexpectedExceptionDetails(): void
    {
        $exception = new \RuntimeException('private runtime detail', 403);
        [$response, $payload] = $this->dispatchApiException($exception);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Internal Server Error', $response->getReasonPhrase());
        $this->assertSame(500, $payload['code']);
        $this->assertSame('Internal Server Error', $payload['status']);
        $this->assertArrayNotHasKey('messages', $payload['view']);

        $json = $response->getContent();
        $this->assertStringNotContainsString('private runtime detail', $json);
        $this->assertStringNotContainsString($exception->getFile(), $json);
        $this->assertStringNotContainsString('trace', $json);
    }

    public function testApiHttpExceptionSupportsUnmappedInRangeStatus(): void
    {
        [$response, $payload] = $this->dispatchApiException(
            new HttpException('custom-client-error', 430)
        );

        $this->assertSame(430, $response->getStatusCode());
        $this->assertSame('Bad Request', $response->getReasonPhrase());
        $this->assertSame(430, $payload['code']);
        $this->assertSame('Bad Request', $payload['status']);
        $this->assertSame(430, $payload['view']['messages'][0]['code']);
        $this->assertSame('custom-client-error', $payload['view']['messages'][0]['message']);
    }

    public function testInvalidJwtRendersUnauthorizedWithoutReenteringIdentity(): void
    {
        $identity = new \PhalconKit\Identity\Manager();
        $identity->setDI($this->di);
        $this->di->setShared('identity', $identity);
        $this->di->getConfig()->identity->sessionFallback = false;
        $this->di->getConfig()->response->cache->enable = true;
        $this->di->setShared('request', new class extends \PhalconKit\Http\Request {
            public function getHeader(string $header): string
            {
                return $header === 'X-Authorization' ? 'Bearer invalid-private-token' : '';
            }
        });

        try {
            $identity->isLoggedIn();
            $this->fail('Malformed bearer token was accepted.');
        } catch (HttpException $exception) {
            [$response, $payload] = $this->dispatchApiException($exception, debug: true);
        }

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Unauthorized', $payload['status']);
        $this->assertSame('Invalid authentication token.', $payload['view']['messages'][0]['message']);
        $this->assertArrayNotHasKey('debug', $payload);
        $this->assertStringNotContainsString('invalid-private-token', $response->getContent());
        $this->assertSame('no-store, no-cache, must-revalidate', $response->getHeaders()->get('Cache-Control'));
    }

    #[DataProvider('invalidDispatchCredentials')]
    public function testNativeDispatchRendersInvalidCredentialAsUnauthorized(bool $refresh): void
    {
        $this->di->setShared('request', new class ($refresh) extends \PhalconKit\Http\Request {
            public function __construct(private bool $refresh)
            {
            }

            public function getHeader(string $header): string
            {
                return !$this->refresh && $header === 'X-Authorization' ? 'Bearer invalid-private-token' : '';
            }

            public function getJsonRawBody(bool $associative = false): \stdClass|array|bool
            {
                return (object)($this->refresh ? ['refreshToken' => 'invalid-private-token'] : []);
            }
        });
        $config = $this->di->getConfig();
        $config->app->debug = true;
        $config->debug->enable = true;
        $config->response->cache->enable = true;
        $config->permissions = new \PhalconKit\Config\Config(['roles' => ['everyone' => ['components' => [
            \PhalconKit\Modules\Api\Controllers\AuthController::class => ['*'],
            ApiErrorController::class => ['*'],
        ]]]]);
        $config->router->httpException = new \PhalconKit\Config\Config([
            'namespace' => 'PhalconKit\\Modules\\Api\\Controllers',
            'controller' => 'error',
            'action' => 'error',
        ]);
        $dispatcher = $this->di->getTyped('dispatcher', Dispatcher::class);
        $dispatcher->setNamespaceName('PhalconKit\\Modules\\Api\\Controllers');
        $dispatcher->setModuleName('api');
        $dispatcher->setControllerName('auth');
        $dispatcher->setActionName($refresh ? 'refresh' : 'getIdentity');
        $dispatcher->setParameters([]);

        $dispatcher->dispatch();

        $response = $dispatcher->getReturnedValue();
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringNotContainsString('invalid-private-token', $response->getContent());
        $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Invalid authentication token.', $payload['view']['messages'][0]['message']);
        $this->assertArrayNotHasKey('debug', $payload);
        $this->assertArrayNotHasKey('jwt', $payload['view']);
        $this->assertArrayNotHasKey('refreshToken', $payload['view']);
    }

    /** @return array<string, array{bool}> */
    public static function invalidDispatchCredentials(): array
    {
        return ['access' => [false], 'refresh' => [true]];
    }

    /**
     * Dispatch an exception through the listener and bundled API controller.
     *
     * @return array{ResponseInterface, array<string, mixed>}
     */
    private function dispatchApiException(\Exception $exception, bool $debug = false): array
    {
        $config = $this->di->getConfig();
        $config->app->debug = $debug;
        $config->debug->enable = $debug;

        $dispatcher = $this->di->getTyped('dispatcher', Dispatcher::class);
        $dispatcher->setNamespaceName('PhalconKit\\Modules\\Api\\Controllers');
        $dispatcher->setModuleName('api');
        $dispatcher->setControllerName('records');
        $dispatcher->setActionName('index');
        $dispatcher->setParameters([]);
        $dispatcher->setReturnedValue(null);

        $listener = new DispatcherError();
        $listener->setDI($this->di);
        $listener->beforeException(
            new Event('dispatch:beforeException', $listener),
            $dispatcher,
            $exception
        );

        $controller = new ApiErrorController();
        $controller->setDI($this->di);
        $controller->beforeExecuteRoute();

        if ($exception instanceof HttpException) {
            $controller->errorAction();
        } else {
            $controller->fatalAction();
        }
        $controller->afterExecuteRoute($dispatcher);

        $response = $dispatcher->getReturnedValue();
        $this->assertInstanceOf(ResponseInterface::class, $response);

        return [
            $response,
            json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR),
        ];
    }
}
