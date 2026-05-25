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

namespace PhalconKit\Mvc;

use Phalcon\Di\Di;
use Phalcon\Mvc\RouterInterface as PhalconMvcRouterInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Mvc\Router\ModuleRoute;
use PhalconKit\Router\RouterInterface;

/**
 * Framework router with config-backed module and locale route registration.
 *
 * The router reads defaults, not-found targets, hostnames, and locales from the
 * PhalconKit config service. It remains compatible with Phalcon's router API
 * while exposing a small `toArray()` diagnostic snapshot through
 * `RouterInterface`.
 *
 * @see https://docs.phalcon.io/5.13/routing/
 */
class Router extends \Phalcon\Mvc\Router implements PhalconMvcRouterInterface, RouterInterface
{
    /**
     * Config service used to build default, hostname, module, and locale routes.
     */
    public ConfigInterface $config;
    
    /**
     * Return the config service attached to the router.
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }
    
    /**
     * Attach the config service used by route registration helpers.
     */
    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }
    
    /**
     * Create a config-aware router.
     *
     * Native Phalcon default routes are disabled so PhalconKit can register its
     * own module-aware routes. When `$defaultRoutes` is true, route defaults are
     * read immediately from config.
     *
     * @param bool $defaultRoutes Whether PhalconKit default routes should be
     *     registered during construction.
     * @param ConfigInterface|null $config Optional config service. When omitted
     *     the default DI `config` service is used.
     */
    public function __construct(bool $defaultRoutes = true, ?ConfigInterface $config = null)
    {
        parent::__construct(false);
        
        // set the config
        $this->setConfig($config ?? Di::getDefault()->get('config'));
        
        // Set default routes
        if ($defaultRoutes) {
            $this->defaultRoutes();
        }
    }
    
    /**
     * Register the default module route group from config.
     *
     * The method applies configured defaults, configured not-found paths, extra
     * slash handling, and locale-aware module routes.
     *
     * @return void
     */
    public function defaultRoutes(): void
    {
        $this->removeExtraSlashes(true);
        
        $routerConfig = $this->getConfig()->pathToArray('router') ?? [];
        $localeConfig = $this->getConfig()->pathToArray('locale') ?? [];
        
        $this->setDefaults($routerConfig['defaults'] ?? $this->getDefaults());
        $this->notFound($routerConfig['notFound'] ?? $this->notFoundPaths ?? []);
        $this->mount(new ModuleRoute($this->getDefaults(), $localeConfig['allowed'] ?? []));
    }
    
    /**
     * Register hostname-specific module route groups.
     *
     * Each hostname entry must declare a string `module`; the remaining values
     * are merged into the route defaults for that hostname.
     *
     * @param array<string, array<string, mixed>>|null $hostnames Hostname route
     *     config. Defaults to `router.hostnames`.
     * @param array<string, mixed>|null $defaults Base route defaults.
     *
     * @return void
     *
     * @throws ConfigurationException When a hostname entry does not declare a
     *     string module name.
     */
    public function hostnamesRoutes(?array $hostnames = null, ?array $defaults = null): void
    {
        $routerConfig = $this->getConfig()->pathToArray('router') ?? [];
        $hostnames ??= $routerConfig['hostnames'] ?? [];
        $defaults ??= $this->getDefaults();
        
        foreach ($hostnames as $hostname => $hostnameRoute) {
            if (!isset($hostnameRoute['module']) || !is_string($hostnameRoute['module'])) {
                throw new ConfigurationException('Router hostname config parameter "module" must be a string under "' . $hostname . '"');
            }
            $localeConfig = $this->getConfig()->pathToArray('locale') ?? [];
            $this->mount((new ModuleRoute(array_merge($defaults, (array)$hostnameRoute), $localeConfig['allowed'] ?? [], $hostname))->setHostname($hostname));
        }
    }
    
    /**
     * Register route groups for modules known by the MVC application.
     *
     * The module namespace is inferred from the module class name, matching the
     * framework module structure. Applications with custom namespaces can mount
     * their own `ModuleRoute` instances when this convention is not appropriate.
     *
     * @param \Phalcon\Mvc\Application $application Application containing the
     *     registered module definitions.
     * @param array<string, mixed>|null $defaults Base route defaults.
     *
     * @return void
     *
     * @throws ConfigurationException When a module definition is missing
     *     `className`.
     */
    public function modulesRoutes(\Phalcon\Mvc\Application $application, ?array $defaults = null): void
    {
        $defaults ??= $this->getDefaults();
        foreach ($application->getModules() as $key => $module) {
            if (!isset($module['className'])) {
                throw new ConfigurationException('Module parameter "className" must be a string under "' . $key . '"');
            }
            $localeConfig = $this->getConfig()->pathToArray('locale') ?? [];
            $namespace = rtrim($module['className'], 'Module') . 'Controllers';
            $moduleDefaults = ['namespace' => $namespace, 'module' => $key];
            $this->mount(new ModuleRoute(array_merge($defaults, $moduleDefaults), $localeConfig['allowed'] ?? []));
        }
    }
    
    /**
     * Export the current router match state for diagnostics.
     *
     * @return array<string, mixed> Current namespace, module, controller,
     *     action, params, defaults, matches, and matched-route metadata.
     */
    #[\Override]
    public function toArray(): array
    {
        $matchedRoute = $this->getMatchedRoute();
        return [
            'namespace' => $this->getNamespaceName(),
            'module' => $this->getModuleName(),
            'controller' => $this->getControllerName(),
            'action' => $this->getActionName(),
            'params' => $this->getParams(),
            'defaults' => $this->getDefaults(),
            'matches' => $this->getMatches(),
            'matched' => $matchedRoute ? [
                'id' => $matchedRoute->getRouteId(),
                'name' => $matchedRoute->getName(),
                'hostname' => $matchedRoute->getHostname(),
                'paths' => $matchedRoute->getPaths(),
                'pattern' => $matchedRoute->getPattern(),
                'httpMethod' => $matchedRoute->getHttpMethods(),
            ] : null,
        ];
    }
}
