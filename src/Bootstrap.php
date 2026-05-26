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

namespace PhalconKit;

use Phalcon\Application\AbstractApplication;
use Phalcon\Di\Di as PhalconDi;
use Phalcon\Events;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Cli\Console;
use PhalconKit\Di\DiInterface;
use PhalconKit\Di\FactoryDefault;
use PhalconKit\Di\FactoryDefault\Cli as FactoryDefaultCli;
use PhalconKit\Events\ConfiguredEventListeners;
use PhalconKit\Events\EventsAwareTrait;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Mvc\Application;
use PhalconKit\Provider\Config\ServiceProvider as ConfigServiceProvider;
use PhalconKit\Provider\Router\ServiceProvider as RouterServiceProvider;
use PhalconKit\Provider\ServiceProviderInterface;
use PhalconKit\Router\RouterInterface;
use PhalconKit\Support\Debug;
use PhalconKit\Support\Helper;
use PhalconKit\Support\Php;
use PhalconKit\Ws\WebSocket;
use Docopt;

/**
 * Coordinates PhalconKit runtime setup for MVC, CLI, and WebSocket entrypoints.
 *
 * The bootstrap owns the default startup sequence: select the runtime mode,
 * create and expose the PhalconKit DI container, register configuration,
 * register service providers, initialize core services, register modules, and
 * finally register the router. Applications may subclass this class to override
 * individual steps, but should preserve this ordering unless they fully own the
 * corresponding service wiring.
 */
class Bootstrap
{
    use EventsAwareTrait;
    
    public const string MODE_CLI = 'cli';
    public const string MODE_WS = 'ws';
    public const string MODE_MVC = 'mvc';
    
    /**
     * Active application container.
     *
     * Bootstrap always stores a PhalconKit DI implementation so framework and
     * app code can rely on `getTyped()` and `getConfig()` while services are
     * being registered.
     */
    public DiInterface $di;
    
    /**
     * Runtime mode handled by this bootstrap instance.
     *
     * Supported values are `mvc`, `cli`, and `ws`. A custom mode can be stored
     * by subclasses, but the default `run()` and module-registration logic only
     * know the three built-in modes.
     */
    public string $mode;
    
    /**
     * Optional argument bag exposed for custom CLI bootstraps.
     *
     * The default `getArgs()` implementation parses the current
     * `$_SERVER['argv']` value with Docopt. Subclasses that need pre-parsed
     * arguments can use this property as their own storage convention.
     */
    public ?array $args = null;
    
    /**
     * Registered framework configuration, available after `registerConfig()`.
     */
    public ?ConfigInterface $config = null;
    
    /**
     * Registered MVC or CLI router, available after `registerRouter()`.
     */
    public ?RouterInterface $router = null;
    
    /**
     * Last MVC response produced by `handleApplication()`.
     *
     * CLI and WebSocket modes do not populate this property.
     */
    public ?ResponseInterface $response = null;

    /**
     * Whether config-declared listeners were attached to the shared manager.
     *
     * `bootServices()` can be called directly in tests and custom bootstraps.
     * Tracking this state prevents duplicate configured listener registration
     * while keeping the default bootstrap sequence deterministic.
     */
    protected bool $configuredEventListenersAttached = false;
    
    /**
     * Docopt command specification used by the default CLI argument parser.
     *
     * Applications with custom CLI commands may override this string in a
     * bootstrap subclass before calling `getArgs()`.
     */
    public string $cliDoc = <<<DOC
Phalcon Kit CLI

Usage:
  phalcon-kit <module> <task> [<action>] [--help | --quiet | --verbose] [--debug] [--format=<format>] [<params>...]
  phalcon-kit (-h | --help)
  phalcon-kit (-v | --version)
  phalcon-kit (-i | --info)

Options:
  -h --help               show this help message
  -v --version            print version number
  -i --info               print information
  -q --quiet              suppress output
  -V --verbose            increase verbosity
  -d --debug              enable debug mode
  --format=<format>       change output returned value format (json, xml, serialized, raw, dump)

Tasks:
  cache                  Wipe the cache
  cron                   Run the scheduled task
  database               Create, optimize, truncate or drop tables within the database
  data-life-cycle        Delete old data based on the data life cycle definitions
  scaffold               Generating files and folders structure
  test                   Return the memory usage to see if the CLI works
  user                   Manage the users and passwords

DOC;
    
    /**
     * Builds a ready-to-run bootstrap and executes the core registration steps.
     *
     * Passing `null` lets PhalconKit detect CLI versus MVC mode. WebSocket
     * entrypoints should pass `Bootstrap::MODE_WS` explicitly.
     *
     * @param string|null $mode Runtime mode to initialize, or `null` to auto-detect.
     *
     * @throws ConfigurationException When configured service providers are
     *     invalid or the selected runtime mode cannot be handled.
     * @throws ConfigurationException When configuration cannot be resolved.
     */
    public function __construct(?string $mode = null)
    {
        $this->setMode($mode);
        $this->setEventsManager(new Events\Manager());
        $this->setDI();
        $this->initialize();
        $this->registerConfig();
        $this->registerServices();
        $this->bootServices();
        $this->registerModules();
        $this->registerRouter();
    }
    
    /**
     * Application hook executed before config and service registration.
     *
     * Override this method in an application bootstrap for very early setup that
     * does not require configured services. Services from `config.providers`
     * are not registered yet, so provider-level customization usually belongs
     * in application config instead.
     */
    public function initialize(): void
    {
    }
    
    /**
     * Sets the active DI container and exposes it as the global Phalcon default.
     *
     * When no container is provided, the bootstrap creates a PhalconKit default
     * container for the current mode. Custom containers must implement
     * `PhalconKit\Di\DiInterface`; native Phalcon containers do not expose the
     * typed helper methods used by bootstrap and service providers.
     *
     * The bootstrap instance is registered as the shared `bootstrap` service so
     * injectable classes can inspect runtime state when they need to.
     */
    public function setDI(?DiInterface $di = null): void
    {
        $di ??= $this->isCli()
            ? new FactoryDefaultCli()
            : new FactoryDefault();
        
        $this->di = $di;
        $this->di->setShared('bootstrap', $this);
        PhalconDi::setDefault($this->di);
    }
    
    /**
     * Sets the runtime mode for this bootstrap.
     *
     * Passing `null` auto-detects CLI mode from the PHP runtime and otherwise
     * falls back to MVC. WebSocket mode must be selected explicitly.
     */
    public function setMode(?string $mode = null): void
    {
        $this->mode = $mode ?? (
            Php::isCli()
            ? self::MODE_CLI
            : self::MODE_MVC
        );
    }
    
    /**
     * Returns the selected runtime mode.
     */
    public function getMode(): string
    {
        return $this->mode;
    }
    
    /**
     * Returns the active PhalconKit DI container.
     *
     * Consumers can use the returned container for native Phalcon DI access and
     * the PhalconKit-specific `getTyped()` and `getConfig()` helpers.
     */
    public function getDI(): DiInterface
    {
        return $this->di;
    }
    
    /**
     * Stores the resolved framework configuration.
     *
     * This method is primarily used by `registerConfig()` after the config
     * provider has created the `config` service. Application code normally
     * changes configuration through config files instead of calling this setter.
     */
    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }
    
    /**
     * Returns the registered framework configuration.
     *
     * @throws ConfigurationException When `registerConfig()` has not provided a
     *     valid config instance.
     */
    public function getConfig(): ConfigInterface
    {
        if (!$this->config instanceof ConfigInterface) {
            throw new ConfigurationException('Bootstrap config has not been registered.');
        }

        return $this->config;
    }
    
    /**
     * Stores the resolved MVC or CLI router.
     *
     * This is normally called by `registerRouter()` after the router service has
     * been registered in DI.
     */
    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }
    
    /**
     * Returns the registered MVC or CLI router, when one has been initialized.
     */
    public function getRouter(): ?RouterInterface
    {
        return $this->router;
    }
    
    /**
     * Registers and stores the framework configuration service.
     *
     * If a `config` service already exists in DI, it is reused. Otherwise the
     * built-in config service provider is registered first. This method must run
     * before provider registration because `config.providers` drives the rest of
     * the bootstrap service graph.
     */
    public function registerConfig(): void
    {
        if (!$this->di->has('config')) {
            $configService = new ConfigServiceProvider($this->di);
            $configService->register($this->di);
        }
        $this->config ??= $this->di->getConfig();
    }
    
    /**
     * Registers configured application and framework service providers.
     *
     * Provider values must be class-string names. Each provider is constructed
     * with the active PhalconKit DI container and must implement
     * `ServiceProviderInterface`; its `register()` method is then called
     * directly. This avoids relying on native Phalcon provider registration,
     * which cannot express PhalconKit's typed DI boundary.
     *
     * @param array<string, string>|null $providers Provider map. When `null`,
     *     `config.providers` is used.
     *
     * @throws ConfigurationException When a provider value is not a
     *     class-string, the class cannot be found, or the instance does not
     *     implement the provider contract.
     */
    public function registerServices(?array $providers = null): void
    {
        $providers ??= $this->getConfig()->pathToArray('providers') ?? [];
        
        foreach ($providers as $key => $provider) {
            if (!is_string($provider)) {
                throw new ConfigurationException("Service Provider `$key` class name must be a string.", 400);
            }
            
            if (!class_exists($provider)) {
                throw new ConfigurationException("Service Provider `$key` class `$provider` not found.", 404);
            }
            
            $instance = new $provider($this->di);
            if (!$instance instanceof ServiceProviderInterface) {
                throw new ConfigurationException("Service Provider `$provider` must implement ServiceProviderInterface.", 500);
            }
            
            $instance->register($this->di);
        }
    }
    
    /**
     * Registers and stores the router service for the current runtime.
     *
     * Existing DI router services are reused. Otherwise the built-in router
     * provider is registered, then the service is resolved through `getTyped()`
     * so invalid replacements fail with a clear service-contract error.
     */
    public function registerRouter(): void
    {
        if (!$this->di->has('router')) {
            $configService = new RouterServiceProvider($this->di);
            $configService->register($this->di);
        }
        $this->router ??= $this->di->getTyped('router', RouterInterface::class);
    }
    
    /**
     * Resolves early services that need to be initialized before modules run.
     *
     * At the moment this eagerly initializes the `debug` service and attaches
     * any configured shared event-manager listeners. The
     * `ServiceProviderInterface::boot()` hook remains available to provider
     * implementations, but the default bootstrap does not iterate provider
     * instances after registration.
     */
    public function bootServices(): void
    {
        $this->di->getTyped('debug', Debug::class);
        $this->attachConfiguredEventListeners();
    }

    /**
     * Attach listeners declared under `eventsManager.listeners`.
     *
     * This hook runs after providers are registered and before modules/router
     * setup. That timing lets application config add listeners for shared event
     * types such as `dispatch`, `db`, `model`, or `view` without replacing the
     * core providers that create those services.
     *
     * @throws ConfigurationException When listener config exists but no
     *     `eventsManager` service is registered.
     * @throws ConfigurationException When a configured listener definition is
     *     invalid.
     */
    protected function attachConfiguredEventListeners(): void
    {
        if ($this->configuredEventListenersAttached) {
            return;
        }

        $listeners = $this->getConfig()->pathToArray('eventsManager.listeners') ?? [];
        if ($listeners === []) {
            $this->configuredEventListenersAttached = true;
            return;
        }

        if (!$this->di->has('eventsManager')) {
            throw new ConfigurationException(
                'Configured event listeners require an "eventsManager" DI service.'
            );
        }

        ConfiguredEventListeners::attach(
            $this->di,
            $this->di->getTyped('eventsManager', Events\ManagerInterface::class),
            $listeners
        );
        $this->configuredEventListenersAttached = true;
    }
    
    /**
     * Registers configured modules on the selected application object.
     *
     * When no application is provided, the method resolves the mode-specific
     * console, WebSocket, or MVC application service from DI. Module definitions
     * default to `config.modules`, and the default module defaults to
     * `config.router.defaults.module`.
     *
     * @param AbstractApplication|null $application Application instance to
     *     mutate, or `null` to resolve the mode-specific service from DI.
     * @param array<string, array<string, mixed>>|null $modules Module
     *     definitions, or `null` to use config.
     * @param string|null $defaultModule Default module name, or `null` to use
     *     config.
     *
     * @throws ConfigurationException When the bootstrap mode cannot be mapped
     *     to an application service.
     */
    public function registerModules(
        ?AbstractApplication $application = null,
        ?array $modules = null,
        ?string $defaultModule = null
    ): void {
        $application ??= match ($this->getMode()) {
            self::MODE_CLI => $this->di->getTyped('console', Console::class),
            self::MODE_WS => $this->di->getTyped('webSocket', WebSocket::class),
            self::MODE_MVC => $this->di->getTyped('application', Application::class),
            default => throw new ConfigurationException(
                'Unable to register modules in bootstrap mode: `' . $this->getMode() . '`',
                400
            ),
        };
        
        $config = $this->getConfig();
        
        $modules ??= $config->pathToArray('modules') ?? [];
        $application->registerModules($modules);
        
        $defaultModule ??= $config->path('router.defaults.module') ?? '';
        $application->setDefaultModule($defaultModule);
    }

    /**
     * Dispatches the selected runtime and returns the produced content.
     *
     * The `beforeRun` event is fired before dispatch and `afterRun` is fired
     * with the produced content afterward. MVC mode returns response content,
     * CLI mode returns captured command output, and WebSocket mode returns
     * `null` after handing control to the server runtime.
     *
     * @throws ConfigurationException When the bootstrap mode cannot be handled.
     */
    public function run(): ?string
    {
        $this->fire('beforeRun');

        $content = match ($this->getMode()) {
            self::MODE_MVC => $this->handleApplication(
                $this->di->getTyped('application', Application::class)
            ),
            self::MODE_CLI => $this->handleConsole($this->di->getTyped('console', Console::class)),
            self::MODE_WS  => $this->handleWebSocket($this->di->getTyped('webSocket', WebSocket::class)),
            default => throw new ConfigurationException(
                'Unable to handle run application in bootstrap mode: `' . $this->getMode() . '`',
                400
            ),
        };

        $this->fire('afterRun', $content);

        return $content;
    }
    
    /**
     * Handles a CLI console request and returns captured output.
     *
     * Console exceptions are rendered through the CLI exception handler so CLI
     * users receive formatted output instead of raw PHP exception text.
     */
    public function handleConsole(Console $console): ?string
    {
        $response = null;
        try {
            ob_start();
            $console->handle($this->getArgs());
            $response = ob_get_clean() ?: null;
        }
        catch (\Throwable $e) {
            new Cli\ExceptionHandler($e)->write();
        }
        
        return $response;
    }

    /**
     * Handles a WebSocket/Swoole server request.
     *
     * WebSocket handling is long-running and does not produce an HTTP response
     * body for bootstrap callers, so this method always returns `null`.
     */
    public function handleWebSocket(WebSocket $webSocket): ?string
    {
        $webSocket->handle();
        return null;
    }
    
    /**
     * Handles an MVC HTTP request and stores the resulting response.
     *
     * The request URI is read from `$_SERVER['REQUEST_URI']`, defaulting to `/`
     * when unavailable. The returned string is the response body content, or
     * `null` if the application did not return a response object.
     *
     * @throws \Throwable Propagates failures from Phalcon MVC request handling
     *     unchanged so the application's configured error pipeline can decide
     *     how to render or log them.
     */
    public function handleApplication(Application $application): ?string
    {
        $this->response = $application->handle($_SERVER['REQUEST_URI'] ?? '/') ?: null;
        return $this->response ? $this->response->getContent() : null;
    }
    
    /**
     * Parses CLI arguments into PhalconKit's camelCase argument format.
     *
     * The parser uses `cliDoc` as its Docopt specification and reads the
     * current process arguments from `$_SERVER['argv']`. Non-CLI runtimes return
     * an empty array so shared code can call this method safely.
     *
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        // @codeCoverageIgnoreStart
        if (!Php::isCli()) {
            return [];
        }
        // @codeCoverageIgnoreEnd
        
        $args = [];
        $argv = array_slice($_SERVER['argv'] ?? [], 1);
        $response = (new Docopt())->handle($this->cliDoc, ['argv' => $argv, 'optionsFirst' => true]);
        foreach ($response as $key => $value) {
            if (!is_null($value) && preg_match('/(<(.*?)>|\-\-(.*))/', $key, $matches)) {
                $match = array_pop($matches);
                if (!empty($match)) {
                    $key = lcfirst(Helper::camelize(Helper::uncamelize($match)));
                    $args[$key] = $value;
                }
            }
        }
        
        return $args;
    }
    
    /**
     * Returns true when this bootstrap is running in CLI mode.
     */
    public function isCli(): bool
    {
        return $this->getMode() === self::MODE_CLI;
    }
    
    /**
     * Returns true when this bootstrap is running in WebSocket mode.
     */
    public function isWs(): bool
    {
        return $this->getMode() === self::MODE_WS;
    }
    
    /**
     * Returns true when this bootstrap is running in MVC mode.
     */
    public function isMvc(): bool
    {
        return $this->getMode() === self::MODE_MVC;
    }
}
