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

    /**
     * Dispatch an exception through the listener and bundled API controller.
     *
     * @return array{ResponseInterface, array<string, mixed>}
     */
    private function dispatchApiException(\Exception $exception): array
    {
        $config = $this->di->getConfig();
        $config->app->debug = false;
        $config->debug->enable = false;

        $dispatcher = $this->di->getTyped('dispatcher', Dispatcher::class);
        $dispatcher->setNamespaceName('PhalconKit\\Modules\\Api\\Controllers');
        $dispatcher->setModuleName('api');
        $dispatcher->setControllerName('records');
        $dispatcher->setActionName('index');
        $dispatcher->setParams([]);
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
