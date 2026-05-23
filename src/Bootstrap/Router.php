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

namespace PhalconKit\Bootstrap;

use PhalconKit\Config\ConfigInterface;

/**
 * Bootstrap router with PhalconKit's default frontend routes.
 *
 * This router extends the framework router with application-facing defaults for
 * the bundled frontend module. It registers simple controller/action routes and
 * optional locale-prefixed variants based on the configured allowed locales.
 */
class Router extends \PhalconKit\Mvc\Router
{
    /**
     * Default route paths used when no explicit router config overrides them.
     *
     * @var array<string, string>
     */
    public array $defaults = [
        'namespace' => \PhalconKit\Modules\Frontend\Controller::class,
        'module' => 'frontend',
        'controller' => 'index',
        'action' => 'index',
    ];
    
    /**
     * Default not-found route target used by the bootstrap router.
     *
     * @var array<string, string>
     */
    public array $notFound = [
        'controller' => 'error',
        'action' => 'notFound',
    ];
    
    /**
     * Create the bootstrap router.
     *
     * @param bool $defaultRoutes Whether framework default routes should be
     *     registered immediately.
     * @param ConfigInterface|null $config Optional config service. When omitted
     *     the parent router resolves it from the default DI.
     */
    public function __construct(bool $defaultRoutes = true, ?ConfigInterface $config = null)
    {
        parent::__construct($defaultRoutes, $config);
    }
    
    /**
     * Register unprefixed and locale-prefixed frontend routes.
     *
     * Routes are named consistently (`default`, `default-controller`,
     * `default-controller-action`, and locale variants) so applications can
     * override or generate URLs against known route names.
     *
     * @return void
     */
    public function baseRoutes(): void
    {
        $this->add('/', [
        ])->setName('default');
        
        $this->add('/:controller', [
            'controller' => 1,
        ])->setName('default-controller');
        
        $this->add('/:controller/:action/:params', [
            'controller' => 1,
            'action' => 2,
            'params' => 3,
        ])->setName('default-controller-action');
        
        $localeConfig = $this->getConfig()->pathToArray('locale') ?? [];
        $allowedLocales = $localeConfig['allowed'] ?? [];
        
        if (!empty($allowedLocales)) {
            $localeRegex = '{locale:(' . implode('|', $allowedLocales) . ')}';
            
            $this->add('/' . $localeRegex, [
                'locale' => 1,
            ])->setName('locale');
            
            $this->add('/' . $localeRegex . '/:controller', [
                'locale' => 1,
                'controller' => 2,
            ])->setName('locale-controller');
            
            $this->add('/' . $localeRegex . '/:controller/:action/:params', [
                'locale' => 1,
                'controller' => 2,
                'action' => 3,
                'params' => 4,
            ])->setName('locale-controller-action');
        }
        
        foreach ($allowedLocales as $locale) {
            $localeRegex = $locale;
            
            $this->add('/' . $localeRegex, [
                'locale' => $locale,
            ])->setName($locale);
            
            $this->add('/' . $localeRegex . '/:controller', [
                'locale' => $locale,
                'controller' => 1,
            ])->setName($locale . '-controller');
            
            $this->add('/' . $localeRegex . '/:controller/:action/:params', [
                'locale' => $locale,
                'controller' => 1,
                'action' => 2,
                'params' => 3,
            ])->setName($locale . '-controller-action');
        }
    }
}
