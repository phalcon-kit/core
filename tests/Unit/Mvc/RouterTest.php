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

namespace PhalconKit\Tests\Unit\Mvc;

use PhalconKit\Bootstrap\Config;
use PhalconKit\Mvc\Router;
use PhalconKit\Tests\Unit\AbstractUnit;
use Phalcon\Mvc\Router\RouteInterface;

class RouterTest extends AbstractUnit
{
    public \PhalconKit\Mvc\Router $router;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->router = $this->di->get('router');
    }
    
    public function testRouterFromDi(): void
    {
        $this->assertInstanceOf(\PhalconKit\Router\RouterInterface::class, $this->router);
        $this->assertInstanceOf(\Phalcon\Mvc\Router::class, $this->router);
        $this->assertInstanceOf(\Phalcon\Mvc\RouterInterface::class, $this->router);
        $this->assertInstanceOf(\PhalconKit\Mvc\Router::class, $this->router);
    }
    
    public function testToArray(): void
    {
        $routerToArray = $this->router->toArray();
        $this->assertIsArray($routerToArray);
        $this->assertIsString($routerToArray['namespace']);
        $this->assertEmpty($routerToArray['namespace']);
        $this->assertIsString($routerToArray['module']);
        $this->assertEmpty($routerToArray['module']);
        $this->assertIsString($routerToArray['controller']);
        $this->assertEmpty($routerToArray['controller']);
        $this->assertIsString($routerToArray['action']);
        $this->assertEmpty($routerToArray['action']);
        $this->assertIsArray($routerToArray['params']);
        $this->assertIsArray($routerToArray['defaults']);
        $this->assertIsArray($routerToArray['matches']);
        $this->assertEmpty($routerToArray['matches']);
        $this->assertNull($routerToArray['matched']);

//        $this->assertIsString($routerToArray['matched']['id']);
//        $this->assertIsString($routerToArray['matched']['name']);
//        $this->assertIsString($routerToArray['matched']['hostname']);
//        $this->assertIsString($routerToArray['matched']['paths']);
//        $this->assertIsString($routerToArray['matched']['pattern']);
//        $this->assertIsString($routerToArray['matched']['httpMethod']);
//        $this->assertIsString($routerToArray['matched']['reversedPaths']);
    }
    
    public function testDefaultRoutes(): void
    {
        $routeNames = [
            'default',
            'default-controller',
            'default-controller-action',
        ];
        
        $application = $this->di->get('application');
        $applicationModules = $application->getModules();
        $modules = array_keys($applicationModules);
        $locales = [
            'locale',
            ...$this->bootstrap->config->pathToArray('locale.allowed') ?? [],
        ];
        
        $routePrefixes = [
            'default',
            ...$locales,
            ...$modules,
        ];
        
        $testRoutes = [];
        
        foreach ($routePrefixes as $routePrefix) {
            foreach ($routeNames as $routeName) {
                $testRoute = str_replace('default-', $routePrefix . '-', $routeName);
                $testRoutes[$testRoute] = ['/' . str_replace('-', '/', str_replace(['default-', 'default'], '', $testRoute))];
            }
            foreach ($modules as $module) {
                foreach ($locales as $locale) {
                    $testRoute = str_replace('default-', $locale . '-' . $module . '-', $routePrefix);
                    $testRoutes[$testRoute] = ['/' . str_replace('-', '/', $testRoute)];
                }
            }
        }
        
        foreach ($testRoutes as $name => $uris) {
            // add some testing for -action routes
            if (str_contains($name, '-action')) {
                $uris [] = $uris[0] . '/params';
                $uris [] = $uris[0] . '/params/1';
                $uris [] = $uris[0] . '/params/1/2';
            }
            
            // can't match locale route, it should match the locale route itself
            if (str_starts_with($name, 'locale')) {
                $uris = [];
            }
            
            $routeByName = $this->router->getRouteByName($name);
            
            $this->assertInstanceOf(RouteInterface::class, $routeByName, $name . ' : ' . (is_object($routeByName) ? get_class($routeByName) : $routeByName));
            
            foreach ($uris as $uri) {
                $this->router->handle($uri);
                $matchedRoute = $this->router->getMatchedRoute();
                $this->assertInstanceOf(RouteInterface::class, $matchedRoute, get_class($matchedRoute));
                
                $message = $uri . ' : ' . json_encode($matchedRoute->getPaths());
                $this->assertEquals($name, $matchedRoute->getName(), $message);
                
                foreach ($modules as $module) {
                    if (str_contains($name, $module)) {
                        $this->assertEquals($module, $this->router->getModuleName(), $message);
                    }
                }
                if (str_contains($name, '-controller')) {
                    $this->assertEquals('controller', $this->router->getControllerName(), $message);
                }
                if (str_contains($name, '-action')) {
                    $this->assertEquals('action', $this->router->getActionName(), $message);
                }
                $this->assertIsString($this->router->getNamespaceName(), $message);
                $this->assertIsArray($this->router->getParams(), $message);
            }
        }
    }

    public function testToArrayIncludesMatchedRouteMetadata(): void
    {
        $router = new Router(false, new Config());
        $router->setDI($this->di);
        $router->add('/records', [
            'module' => 'api',
            'controller' => 'records',
            'action' => 'index',
        ])->setName('records-index');

        $router->handle('/records');
        $routerToArray = $router->toArray();

        $this->assertSame('api', $routerToArray['module']);
        $this->assertSame('records', $routerToArray['controller']);
        $this->assertSame('index', $routerToArray['action']);
        $this->assertSame('records-index', $routerToArray['matched']['name']);
        $this->assertSame('/records', $routerToArray['matched']['pattern']);
        $this->assertSame([
            'module' => 'api',
            'controller' => 'records',
            'action' => 'index',
        ], $routerToArray['matched']['paths']);
    }

    public function testHostnamesRoutesMountsConfiguredHostnamesAndLocales(): void
    {
        $router = new Router(false, new Config([
            'locale' => [
                'allowed' => ['en', 'fr'],
            ],
        ]));

        $router->hostnamesRoutes([
            'api.example.test' => [
                'module' => 'api',
                'controller' => 'records',
            ],
        ], [
            'action' => 'index',
        ]);

        $route = $router->getRouteByName('api-example-test');
        $this->assertInstanceOf(RouteInterface::class, $route);
        $this->assertSame('api.example.test', $route->getHostname());
        $this->assertSame('api', $route->getPaths()['module']);
        $this->assertSame('records', $route->getPaths()['controller']);
        $this->assertSame('index', $route->getPaths()['action']);
        $this->assertInstanceOf(RouteInterface::class, $router->getRouteByName('locale-api-example-test'));
        $this->assertInstanceOf(RouteInterface::class, $router->getRouteByName('en-api-example-test-controller'));
        $this->assertInstanceOf(RouteInterface::class, $router->getRouteByName('fr-api-example-test-controller-action'));
    }

    public function testHostnamesRoutesRejectsMissingModuleName(): void
    {
        $router = new Router(false, new Config());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Router hostname config parameter "module" must be a string under "bad.example.test"');

        $router->hostnamesRoutes([
            'bad.example.test' => [
                'module' => 123,
            ],
        ]);
    }

    public function testModulesRoutesMountsApplicationModules(): void
    {
        $router = new Router(false, new Config([
            'locale' => [
                'allowed' => ['en'],
            ],
        ]));
        $application = new \Phalcon\Mvc\Application();
        $application->registerModules([
            'api' => [
                'className' => 'App\\Modules\\Api\\Module',
            ],
        ]);

        $router->modulesRoutes($application, [
            'controller' => 'index',
            'action' => 'index',
        ]);

        $route = $router->getRouteByName('api');
        $this->assertInstanceOf(RouteInterface::class, $route);
        $this->assertSame('App\\Modules\\Api\\Controllers', $route->getPaths()['namespace']);
        $this->assertSame('api', $route->getPaths()['module']);
        $this->assertSame('index', $route->getPaths()['controller']);
        $this->assertSame('index', $route->getPaths()['action']);
        $this->assertInstanceOf(RouteInterface::class, $router->getRouteByName('locale-api-controller'));
        $this->assertInstanceOf(RouteInterface::class, $router->getRouteByName('en-api-controller-action'));
    }

    public function testModulesRoutesRejectsMissingClassName(): void
    {
        $router = new Router(false, new Config());
        $application = new \Phalcon\Mvc\Application();
        $application->registerModules([
            'api' => [],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Module parameter "className" must be a string under "api"');

        $router->modulesRoutes($application);
    }
}
