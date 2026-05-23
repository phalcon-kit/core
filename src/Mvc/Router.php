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
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Mvc\Router\ModuleRoute;
use PhalconKit\Router\RouterInterface;

/**
 * {@inheritDoc}
 */
class Router extends \Phalcon\Mvc\Router implements RouterInterface
{
    public ConfigInterface $config;
    
    public function getConfig(): ConfigInterface
    {
        return $this->config;
    }
    
    public function setConfig(ConfigInterface $config): void
    {
        $this->config = $config;
    }
    
    /**
     * Router constructor.
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
     * Default routes
     * - Default namespace
     * - Default controller
     * - Default action
     * - Default notFound
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
     * @param array|null $hostnames
     * @param array|null $defaults
     * @return void
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
     * Defines our frontend routes
     * /controller/action/params
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
