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

use Phalcon\Http\Request;
use Phalcon\Mvc\Micro;
use Phalcon\Mvc\Router\Route;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Cli\Router as CliRouter;
use PhalconKit\Di\FactoryDefault;
use PhalconKit\Mvc\Router;
use PhalconKit\Tests\Unit\AbstractUnit;

/**
 * Protect Core routing and diagnostic behavior across Phalcon 5.23 fast paths.
 */
class Phalcon523CompatibilityTest extends AbstractUnit
{
    public function testLaterMethodSpecificRegexTakesPrecedenceOverStaticRoute(): void
    {
        $router = $this->createRouter();
        $router->add('/records/42', ['controller' => 'static']);
        $expected = $router->addGet('/records/([0-9]+)', ['controller' => 'records', 'id' => 1]);

        $router->handle('/records/42');

        $this->assertSame($expected, $router->getMatchedRoute());
        $this->assertSame('records', $router->getControllerName());
        $this->assertSame(['id' => '42'], $router->getParams());
    }

    public function testDiagnosticsDoNotLeakRegexMarkersOrPreviousMatches(): void
    {
        $router = $this->createRouter();
        $regex = $router->add('/records/([0-9]+)', ['controller' => 'records', 'id' => 1]);
        $static = $router->add('/health', ['controller' => 'health']);

        $router->handle('/records/42');

        $this->assertSame($regex, $router->getMatchedRoute());
        $this->assertSame(['/records/42', '42'], $router->toArray()['matches']);

        $router->handle('/health');

        $this->assertSame($static, $router->getMatchedRoute());
        $this->assertSame([], $router->toArray()['matches']);

        $router->handle('/records/73');
        $this->assertSame(['/records/73', '73'], $router->toArray()['matches']);

        $router->handle('/missing');

        $this->assertFalse($router->wasMatched());
        $this->assertSame([], $router->toArray()['matches']);
        $this->assertNull($router->toArray()['matched']);
    }

    public function testStaticRouteVetoCallsOnceAndFallsBackToRegex(): void
    {
        $router = $this->createRouter();
        $expected = $router->add('/records/([0-9]+)', ['controller' => 'records', 'id' => 1]);
        $calls = 0;
        $router->add('/records/42', ['controller' => 'restricted'])
            ->beforeMatch(static function () use (&$calls): bool {
                ++$calls;

                return false;
            });

        $router->handle('/records/42');

        $this->assertSame(1, $calls);
        $this->assertSame($expected, $router->getMatchedRoute());
        $this->assertSame(['id' => '42'], $router->getParams());
    }

    public function testProviderRouterPreservesConstrainedStaticPrecedenceAndVeto(): void
    {
        $router = $this->di->getShared('router');
        $this->assertInstanceOf(Router::class, $router);
        $this->assertSame($this->di->getShared('eventsManager'), $router->getEventsManager());

        foreach ([true, false] as $allow) {
            $router->clear();
            $router->add('/records/42', ['controller' => 'old-static']);
            $regex = $router->add('/records/([0-9]+)', ['controller' => 'records', 'id' => 1]);
            $calls = 0;
            $static = $router->add('/records/42', ['controller' => 'restricted']);
            $static->beforeMatch(static function () use (&$calls, $allow): bool {
                ++$calls;

                return $allow;
            });

            $router->handle('/records/42');

            $this->assertSame(1, $calls);
            $this->assertSame($allow ? $static : $regex, $router->getMatchedRoute());
        }
    }

    public function testRegexRouteVetoCallsOnceAndFallsBackWithinItsChunk(): void
    {
        $router = $this->createRouter();
        $expected = $router->add('/records/(.+)', ['controller' => 'records', 'id' => 1]);
        $calls = 0;
        $router->add('/records/([0-9]+)', ['controller' => 'restricted', 'id' => 1])
            ->beforeMatch(static function () use (&$calls): bool {
                ++$calls;

                return false;
            });

        $router->handle('/records/42');

        $this->assertSame(1, $calls);
        $this->assertSame($expected, $router->getMatchedRoute());
        $this->assertSame(['id' => '42'], $router->getParams());
    }

    public function testCliDiagnosticsClearCapturesAfterStaticMatchAndNoMatch(): void
    {
        $router = new CliRouter(false);
        $router->add('records {id:[0-9]+}', ['task' => 'records']);
        $router->add('health', ['task' => 'health']);

        $router->handle('records 42');
        $this->assertSame(['records 42', '42'], $router->toArray()['matches']);

        $router->handle('health');
        $this->assertSame('health', $router->getTaskName());
        $this->assertSame([], $router->toArray()['matches']);

        $router->handle('records 73');
        $router->handle('missing');
        $this->assertFalse($router->wasMatched());
        $this->assertSame([], $router->toArray()['matches']);
        $this->assertNull($router->toArray()['matched']);
    }

    public function testTopLevelAlternationKeepsTheOriginalPatternMeaning(): void
    {
        $router = $this->createRouter();
        $router->add('/(.*)', ['controller' => 'fallback']);
        $expected = $router->addGet('#^/records|/health$#u', ['controller' => 'alternative']);

        foreach (['/records/42', '/prefix/health'] as $uri) {
            $router->handle($uri);
            preg_match($expected->getCompiledPattern(), $uri, $matches);

            $this->assertSame($expected, $router->getMatchedRoute());
            $this->assertSame($matches, $router->toArray()['matches']);
        }
    }

    public function testNamedCapturesRemainIsolatedAcrossRoutes(): void
    {
        $router = $this->createRouter();
        $first = $router->addGet('#^/records/(?P<id>[0-9]+)$#u', ['controller' => 'records', 'id' => 'id']);
        $second = $router->addGet('#^/entries/(?P<id>[0-9]+)$#u', ['controller' => 'entries', 'id' => 'id']);
        $plain = $router->addGet('/pages/([a-z]+)', ['controller' => 'pages', 'slug' => 1]);

        $router->handle('/records/42');
        $this->assertSame($first, $router->getMatchedRoute());
        $this->assertSame(['id' => '42'], $router->getParams());

        $router->handle('/entries/73');
        $this->assertSame($second, $router->getMatchedRoute());
        $this->assertSame(['/entries/73', 'id' => '73', 1 => '73'], $router->getMatches());

        $router->handle('/pages/example');
        $this->assertSame($plain, $router->getMatchedRoute());
        $this->assertSame(['/pages/example', 'example'], $router->getMatches());
        $this->assertSame(['slug' => 'example'], $router->getParams());
    }

    public function testMalformedRegexChunkFallsBackWithoutSelectingAnOlderRoute(): void
    {
        $router = $this->createRouter();
        $router->addGet('/{resource}/{id}', ['controller' => 'fallback']);
        for ($index = 0; $index < 9; ++$index) {
            $router->addGet('/unused' . $index . '/{id}', ['controller' => 'unused']);
        }
        $router->addGet('/(valid|broken#delimiter)/entry', ['controller' => 'broken']);
        $expected = $router->addGet('/records/{id}', ['controller' => 'records']);
        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            if ($severity !== E_WARNING || !str_contains($message, 'preg_match()')) {
                return false;
            }

            $warnings[] = $message;

            return true;
        }, E_WARNING);

        try {
            $router->handle('/records/42');
        } finally {
            restore_error_handler();
        }

        $this->assertSame($expected, $router->getMatchedRoute());
        $this->assertSame(['id' => '42'], $router->getParams());
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('Unknown modifier', $warnings[0]);
    }

    public function testHostnameMismatchFallsBackToRegexBeforeAnOlderStaticRoute(): void
    {
        $router = $this->createRouter();
        $router->add('/records/42', ['controller' => 'old-static']);
        $expected = $router->addGet('/records/([0-9]+)', ['controller' => 'records', 'id' => 1]);
        $router->add('/records/42', ['controller' => 'restricted'])->setHostname('admin.example.test');

        $router->handle('/records/42');

        $this->assertSame($expected, $router->getMatchedRoute());
        $this->assertSame(['id' => '42'], $router->getParams());
    }

    public function testMicroWithCoreFactoryCreatesOnlyExplicitRoutes(): void
    {
        $sentinel = new Route('/sentinel');
        $micro = new Micro(new FactoryDefault());
        $route = $micro->get('/health', static fn(): string => 'ok');

        $this->assertSame((int)$sentinel->getRouteId() + 1, (int)$route->getRouteId());
        $this->assertCount(1, $micro->getRouter()->getRoutes());
    }

    public function testMicroPreservesCustomCoreRouterServiceDefinition(): void
    {
        $router = $this->createRouter();
        $router->add('/old');
        $arguments = null;
        $di = new FactoryDefault();
        $di->setShared('router', function (...$parameters) use ($router, &$arguments): Router {
            $arguments = $parameters;

            return $router;
        });

        $micro = new Micro($di);

        $this->assertSame($router, $micro->getRouter());
        $this->assertSame([], $arguments);
        $this->assertSame([], $router->getRoutes());
    }

    private function createRouter(): Router
    {
        $request = $this->createStub(Request::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHttpHost')->willReturn('api.example.test');
        $this->di->setShared('request', $request);

        $router = new Router(false, new Config());
        $router->setDI($this->di);

        return $router;
    }
}
