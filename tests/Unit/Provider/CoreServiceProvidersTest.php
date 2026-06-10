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

namespace PhalconKit\Tests\Unit\Provider;

use Phalcon\Encryption\Security as PhalconSecurity;
use Phalcon\Events\Manager;
use Phalcon\Logger\Adapter\Noop;
use Phalcon\Logger\Formatter\Line;
use Phalcon\Logger\LoggerInterface;
use Phalcon\Mvc\Router as PhalconRouter;
use PhalconKit\Bootstrap\Config as BootstrapConfig;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Db\Profiler;
use PhalconKit\Filter\Filter;
use PhalconKit\Html\Escaper;
use PhalconKit\Http\Request;
use PhalconKit\Http\Response;
use PhalconKit\Logger\Loggers;
use PhalconKit\Mvc\Application;
use PhalconKit\Mvc\Url;
use PhalconKit\Mvc\View;
use PhalconKit\Provider\Application\ServiceProvider as ApplicationProvider;
use PhalconKit\Provider\Console\ServiceProvider as ConsoleProvider;
use PhalconKit\Provider\Escaper\ServiceProvider as EscaperProvider;
use PhalconKit\Provider\EventsManager\ServiceProvider as EventsManagerProvider;
use PhalconKit\Provider\Filter\ServiceProvider as FilterProvider;
use PhalconKit\Provider\Helper\ServiceProvider as HelperProvider;
use PhalconKit\Provider\Logger\ServiceProvider as LoggerProvider;
use PhalconKit\Provider\Loggers\ServiceProvider as LoggersProvider;
use PhalconKit\Provider\Models\ServiceProvider as ModelsProvider;
use PhalconKit\Provider\Profiler\ServiceProvider as ProfilerProvider;
use PhalconKit\Provider\Request\ServiceProvider as RequestProvider;
use PhalconKit\Provider\Response\ServiceProvider as ResponseProvider;
use PhalconKit\Provider\Security\ServiceProvider as SecurityProvider;
use PhalconKit\Provider\Tag\ServiceProvider as TagProvider;
use PhalconKit\Provider\Url\ServiceProvider as UrlProvider;
use PhalconKit\Provider\Utils\ServiceProvider as UtilsProvider;
use PhalconKit\Provider\Version\ServiceProvider as VersionProvider;
use PhalconKit\Provider\View\ServiceProvider as ViewProvider;
use PhalconKit\Provider\WebSocket\ServiceProvider as WebSocketProvider;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Support\Helper\Str\Slugify;
use PhalconKit\Support\Models;
use PhalconKit\Support\Utils;
use PhalconKit\Support\Version;
use PhalconKit\Tag;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Ws\WebSocket;

class CoreServiceProvidersTest extends AbstractUnit
{
    public function testEventsManagerProviderEnablesPriorities(): void
    {
        $di = $this->createDi();
        (new EventsManagerProvider($di))->register($di);

        $eventsManager = $di->get('eventsManager');

        $this->assertInstanceOf(Manager::class, $eventsManager);
        $this->assertTrue($eventsManager->arePrioritiesEnabled());
    }

    public function testSharedCoreProvidersReuseResolvedInstances(): void
    {
        $di = $this->createDi();

        (new EventsManagerProvider($di))->register($di);
        (new RequestProvider($di))->register($di);
        (new ResponseProvider($di))->register($di);

        $this->assertSame($di->get('eventsManager'), $di->get('eventsManager'));
        $this->assertSame($di->get('request'), $di->get('request'));
        $this->assertSame($di->get('response'), $di->get('response'));
    }

    public function testRequestProviderRegistersRequestWithDi(): void
    {
        $di = $this->createDi();
        (new RequestProvider($di))->register($di);

        $request = $di->get('request');

        $this->assertInstanceOf(Request::class, $request);
        $this->assertSame($di, $request->getDI());
    }

    public function testResponseProviderAppliesConfiguredHeaders(): void
    {
        $di = $this->createDi([
            'response' => [
                'headers' => [
                    'X-Test' => 'yes',
                ],
            ],
        ]);
        (new ResponseProvider($di))->register($di);

        $response = $di->get('response');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame($di, $response->getDI());
        $this->assertSame('yes', $response->getHeaders()->get('X-Test'));
    }

    public function testUrlProviderUsesConfiguredUrisAndRouter(): void
    {
        $di = $this->createDi([
            'url' => [
                'staticBaseUri' => '/static/',
                'baseUri' => '/app/',
                'basePath' => '/base/',
            ],
        ]);
        $di->set('router', new PhalconRouter(false));
        (new UrlProvider($di))->register($di);

        $url = $di->get('url');

        $this->assertInstanceOf(Url::class, $url);
        $this->assertSame('/static/', $url->getStaticBaseUri());
        $this->assertSame('/app/', $url->getBaseUri());
        $this->assertSame('/base/', $url->getBasePath());
        $this->assertSame($di, $url->getDI());
    }

    public function testViewProviderConfiguresMinifyEnginesAndEventsManager(): void
    {
        $di = $this->createDi([
            'view' => [
                'minify' => true,
            ],
        ]);
        $eventsManager = new Manager();
        $di->set('eventsManager', $eventsManager);
        (new ViewProvider($di))->register($di);

        $view = $di->get('view');

        $this->assertInstanceOf(View::class, $view);
        $this->assertTrue($view->getMinify());
        $this->assertSame($eventsManager, $view->getEventsManager());
        $this->assertArrayHasKey('.phtml', $view->getRegisteredEngines());
        $this->assertArrayHasKey('.volt', $view->getRegisteredEngines());
    }

    public function testSecurityProviderUsesConfiguredHashOptions(): void
    {
        $di = $this->createDi([
            'security' => [
                'workFactor' => 8,
                'hash' => PhalconSecurity::CRYPT_BCRYPT,
            ],
        ]);
        (new SecurityProvider($di))->register($di);

        $security = $di->get('security');

        $this->assertInstanceOf(\PhalconKit\Encryption\Security::class, $security);
        $this->assertSame(8, $security->getWorkFactor());
        $this->assertSame(PhalconSecurity::CRYPT_BCRYPT, $security->getDefaultHash());
        $this->assertSame($di, $security->getDI());
    }

    public function testFilterProviderRegistersPhalconKitFilters(): void
    {
        $di = $this->createDi();
        (new FilterProvider($di))->register($di);

        $filter = $di->get('filter');

        $this->assertInstanceOf(Filter::class, $filter);
        $this->assertSame('127.0.0.1', $filter->sanitize('127.0.0.1', [Filter::FILTER_IPV4]));
        $this->assertNull($filter->sanitize('{"bad":', [Filter::FILTER_JSON]));
    }

    public function testModelsProviderUsesConfiguredClassMap(): void
    {
        $di = $this->createDi([
            'models' => [
                \PhalconKit\Models\User::class => 'App\\Models\\User',
            ],
        ]);
        (new ModelsProvider($di))->register($di);

        $models = $di->get('models');

        $this->assertInstanceOf(Models::class, $models);
        $this->assertSame('App\\Models\\User', $models->getUserClass());
    }

    public function testLoggersAndLoggerProvidersUseConfiguredDefaultLogger(): void
    {
        $di = $this->createDi([
            'logger' => [
                'formatters' => [
                    'line' => Line::class,
                ],
                'drivers' => [
                    'noop' => Noop::class,
                ],
                'default' => [
                    'driver' => 'noop',
                    'formatter' => 'line',
                ],
            ],
            'loggers' => [
                'default' => [
                    'driver' => 'noop',
                ],
            ],
        ]);
        (new LoggersProvider($di))->register($di);
        (new LoggerProvider($di))->register($di);

        $this->assertInstanceOf(Loggers::class, $di->get('loggers'));
        $this->assertInstanceOf(LoggerInterface::class, $di->get('logger'));
    }

    public function testUtilityProvidersRegisterSimpleServices(): void
    {
        $di = $this->createDi();

        (new EscaperProvider($di))->register($di);
        (new HelperProvider($di))->register($di);
        (new ProfilerProvider($di))->register($di);
        (new TagProvider($di))->register($di);
        (new UtilsProvider($di))->register($di);
        (new VersionProvider($di))->register($di);

        $this->assertInstanceOf(Escaper::class, $di->get('escaper'));
        $this->assertInstanceOf(HelperFactory::class, $di->get('helper'));
        $this->assertInstanceOf(Profiler::class, $di->get('profiler'));
        $this->assertInstanceOf(Tag::class, $di->get('tag'));
        $this->assertInstanceOf(Utils::class, $di->get('utils'));
        $this->assertInstanceOf(Version::class, $di->get('version'));
    }

    public function testHelperProviderRegistersConfiguredHelperAliases(): void
    {
        $di = $this->createDi([
            'helpers' => [
                'unitSlug' => Slugify::class,
            ],
        ]);
        (new HelperProvider($di))->register($di);

        $helper = $di->get('helper');

        $this->assertInstanceOf(HelperFactory::class, $helper);
        $this->assertSame('hello-world', $helper->unitSlug('Hello World'));
    }

    public function testApplicationConsoleAndWebSocketProvidersRegisterApplications(): void
    {
        $di = $this->createDi();

        (new ApplicationProvider($di))->register($di);
        (new ConsoleProvider($di))->register($di);
        (new WebSocketProvider($di))->register($di);

        $this->assertInstanceOf(Application::class, $di->get('application'));
        $this->assertSame($di, $di->get('application')->getDI());
        $this->assertInstanceOf(\PhalconKit\Cli\Console::class, $di->get('console'));
        $this->assertSame($di, $di->get('console')->getDI());
        $this->assertInstanceOf(WebSocket::class, $di->get('webSocket'));
        $this->assertSame($di, $di->get('webSocket')->getDI());
    }

    private function createDi(array $config = []): Di
    {
        $di = new Di();
        $di->set('config', new Config($config));

        return $di;
    }
}
