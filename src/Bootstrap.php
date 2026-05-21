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
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault;
use Phalcon\Di\ServiceProviderInterface;
use Phalcon\Events;
use Phalcon\Http\ResponseInterface;
use PhalconKit\Support\Helper;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Cli\Console;
use PhalconKit\Events\EventsAwareTrait;
use PhalconKit\Mvc\Application;
use PhalconKit\Provider\Config\ServiceProvider as ConfigServiceProvider;
use PhalconKit\Provider\Router\ServiceProvider as RouterServiceProvider;
use PhalconKit\Router\RouterInterface;
use PhalconKit\Support\Php;
use PhalconKit\Ws\WebSocket;
use Docopt;

/**
 * Phalcon Kit's Bootstrap for the MVC Application & CLI Console mode
 */
class Bootstrap
{
    use EventsAwareTrait;
    
    public const string MODE_CLI = 'cli';
    public const string MODE_WS = 'ws';
    public const string MODE_MVC = 'mvc';
    
    public DiInterface $di;
    
    public string $mode;
    
    public ?array $args = null;
    
    public ?ConfigInterface $config = null;
    
    public ?RouterInterface $router = null;
    
    public ?ResponseInterface $response = null;
    
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
     * @throws Exception
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
     * Initialisation
     */
    public function initialize(): void
    {
    }
    
    /**
     * Set the default DI
     */
    public function setDI(?DiInterface $di = null): void
    {
        $di ??= $this->isCli()
            ? new FactoryDefault\Cli()
            : new FactoryDefault();
        
        $this->di = $di;
        $this->di->setShared('bootstrap', $this);
        Di::setDefault($this->di);
    }
    
    public function setMode(?string $mode = null): void
    {
        $this->mode = $mode ?? (
            Php::isCli()
            ? self::MODE_CLI
            : self::MODE_MVC
        );
    }
    
    public function getMode(): string
    {
        return $this->mode;
    }
    
    /**
     * Get the default DI
     */
    public function getDI(): DiInterface
    {
        return $this->di;
    }
    
    /**
     * Set the Config
     */
    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }
    
    /**
     * Get the Config
     */
    public function getConfig(): ConfigInterface
    {
        assert($this->config instanceof ConfigInterface);
        return $this->config;
    }
    
    /**
     * Set the MVC or CLI Router
     */
    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }
    
    /**
     * Get the MVC or CLI Router
     */
    public function getRouter(): ?RouterInterface
    {
        return $this->router;
    }
    
    /**
     * Register Config
     */
    public function registerConfig(): void
    {
        if (!$this->di->has('config')) {
            $configService = new ConfigServiceProvider($this->di);
            $configService->register($this->di);
        }
        $this->config ??= $this->di->get('config');
    }
    
    /**
     * Register Service Providers
     * @throws Exception
     */
    public function registerServices(?array $providers = null): void
    {
        $providers ??= $this->getConfig()->pathToArray('providers') ?? [];
        
        foreach ($providers as $key => $provider) {
            if (!is_string($provider)) {
                throw new Exception("Service Provider `$key` class name must be a string.", 400);
            }
            
            if (!class_exists($provider)) {
                throw new Exception("Service Provider `$key` class `$provider` not found.", 404);
            }
            
            $instance = new $provider($this->di);
            if (!$instance instanceof ServiceProviderInterface) {
                throw new Exception("Service Provider `$provider` must implement ServiceProviderInterface.", 500);
            }
            
            if ($this->di instanceof Di) {
                $this->di->register($instance);
            }
        }
    }
    
    /**
     * Register Router
     */
    public function registerRouter(): void
    {
        if (!$this->di->has('router')) {
            $configService = new RouterServiceProvider($this->di);
            $configService->register($this->di);
        }
        $this->router ??= $this->di->get('router');
    }
    
    /**
     * Boot Service Providers
     */
    public function bootServices(): void
    {
        $this->di->get('debug');
    }
    
    /**
     * Register modules
     * @throws \Exception
     */
    public function registerModules(?AbstractApplication $application = null, ?array $modules = null, ?string $defaultModule = null): void
    {
        $application ??= match ($this->getMode()) {
            self::MODE_CLI => $this->di->get('console'),
            self::MODE_WS => $this->di->get('webSocket'),
            self::MODE_MVC => $this->di->get('application'),
            default => throw new \Exception('Unable to register modules in bootstrap mode: `' . $this->getMode() . '`', 400),
        };
        assert($application instanceof AbstractApplication);
        
        $config = $this->getConfig();
        
        $modules ??= $config->pathToArray('modules') ?? [];
        $application->registerModules($modules);
        
        $defaultModule ??= $config->path('router.defaults.module') ?? '';
        $application->setDefaultModule($defaultModule);
    }

    /**
     * Handle cli or mvc application
     * @throws \Exception
     */
    public function run(): ?string
    {
        $this->fire('beforeRun');

        $content = match ($this->getMode()) {
            self::MODE_MVC => $this->handleApplication($this->di->get('application')),
            self::MODE_CLI => $this->handleConsole($this->di->get('console')),
            self::MODE_WS  => $this->handleWebSocket($this->di->get('webSocket')),
            default => throw new \Exception(
                'Unable to handle run application in bootstrap mode: `' . $this->getMode() . '`',
                400
            ),
        };

        $this->fire('afterRun', $content);

        return $content;
    }
    
    /**
     * Handle Console (For CLI only)
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
     * Handle Swoole (For WebSocket only)
     */
    public function handleWebSocket(WebSocket $webSocket): ?string
    {
        $webSocket->handle();
        return null;
    }
    
    /**
     * Handle Application (For MVC only)
     * @throws \Exception
     */
    public function handleApplication(Application $application): ?string
    {
        $this->response = $application->handle($_SERVER['REQUEST_URI'] ?? '/') ?: null;
        return $this->response ? $this->response->getContent() : null;
    }
    
    /**
     * Get & format args from the $this->args property
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
     * Return true if the bootstrap mode is set to 'cli'
     */
    public function isCli(): bool
    {
        return $this->getMode() === self::MODE_CLI;
    }
    
    /**
     * Return true if the bootstrap mode is set to 'ws'
     */
    public function isWs(): bool
    {
        return $this->getMode() === self::MODE_WS;
    }
    
    /**
     * Return true if the bootstrap mode is set to 'mvc'
     */
    public function isMvc(): bool
    {
        return $this->getMode() === self::MODE_MVC;
    }
}
