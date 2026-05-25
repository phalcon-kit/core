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

namespace PhalconKit\Mvc\Router;

use Phalcon\Mvc\Router\Group as RouterGroup;
use PhalconKit\Support\Slug;

/**
 * Route group for one MVC module, optionally scoped by hostname and locale.
 *
 * The group registers the conventional PhalconKit routes for:
 * - module root
 * - controller index
 * - controller/action/params
 *
 * When locales are provided, it also registers locale-prefixed variants using
 * both one regex route and concrete per-locale route names. Hostname groups use
 * host-derived route names so generated routes do not collide with path-based
 * module routes.
 *
 * @see https://docs.phalcon.io/5.13/routing/
 */
class ModuleRoute extends RouterGroup
{
    /**
     * Allowed locale prefixes for this module route group.
     *
     * @var list<string>
     */
    public array $locale;
    
    /**
     * Create a module route group.
     *
     * @param array<string, mixed>|string|null $paths Native Phalcon route
     *     paths. PhalconKit expects at least `module` in normal module usage.
     * @param list<string> $locale Locale prefixes to register.
     * @param string|null $hostname Optional hostname constraint.
     */
    public function __construct(array|string|null $paths = null, array $locale = [], ?string $hostname = null)
    {
        $this->locale = $locale;
        if (isset($hostname)) {
            $this->setHostname($hostname);
        }
        parent::__construct($paths);
    }
    
    /**
     * Register default, controller, action, and locale-aware routes.
     *
     * The method is called by Phalcon's router group lifecycle after
     * construction. Route names are deterministic so applications can generate
     * URLs for either plain module routes or locale/hostname-specific routes.
     */
    public function initialize(): void
    {
        $hostname = $this->getHostname();
        $path = $this->getPaths();
        $module = $path['module'];
        
        $mainRoutePrefix = $hostname ? '' : '/' . $module;
        $mainNamePrefix = $hostname ? Slug::generate($hostname) : $module;
        
        $routePrefix = $mainRoutePrefix;
        $namePrefix = $mainNamePrefix;
        
        $this->add($routePrefix ?: '/', [
        ])->setName($namePrefix);

        $this->add($routePrefix . '/:controller[/]{0,1}', [
            'controller' => 1,
        ])->setName($namePrefix . '-controller');

        $this->add($routePrefix . '/:controller/:action/:params', [
            'controller' => 1,
            'action' => 2,
            'params' => 3,
        ])->setName($namePrefix . '-controller-action');
        
        if (!empty($this->locale)) {
            $localeRegex = '{locale:(' . implode('|', array_unique($this->locale)) . ')}';
            
            $routePrefix = '/' . $localeRegex . $mainRoutePrefix;
            $namePrefix = 'locale-' . $mainNamePrefix;
            
            $this->add($routePrefix . '[/]{0,1}', [
                'locale' => 1,
            ])->setName($namePrefix);
            
            $this->add($routePrefix . '/:controller[/]{0,1}', [
                'locale' => 1,
                'controller' => 2,
            ])->setName($namePrefix . '-controller');
            
            $this->add($routePrefix . '/:controller/:action/:params', [
                'locale' => 1,
                'controller' => 2,
                'action' => 3,
                'params' => 4,
            ])->setName($namePrefix . '-controller-action');
            
            foreach ($this->locale as $locale) {
                $routePrefix = '/' . $locale . $mainRoutePrefix;
                $namePrefix = $locale . '-' . $mainNamePrefix;
                
                $this->add($routePrefix . '[/]{0,1}', [
                    'locale' => $locale,
                ])->setName($namePrefix);
                
                $this->add($routePrefix . '/:controller[/]{0,1}', [
                    'locale' => $locale,
                    'controller' => 1,
                ])->setName($namePrefix . '-controller');
                
                $this->add($routePrefix . '/:controller/:action/:params', [
                    'locale' => $locale,
                    'controller' => 1,
                    'action' => 2,
                    'params' => 3,
                ])->setName($namePrefix . '-controller-action');
            }
        }
    }
}
