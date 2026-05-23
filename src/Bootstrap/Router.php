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
 * Phalcon Kit Router
 * {@inheritDoc}
 */
class Router extends \PhalconKit\Mvc\Router
{
    public array $defaults = [
        'namespace' => \PhalconKit\Modules\Frontend\Controller::class,
        'module' => 'frontend',
        'controller' => 'index',
        'action' => 'index',
    ];
    
    public array $notFound = [
        'controller' => 'error',
        'action' => 'notFound',
    ];
    
    /**
     * Router constructor.
     */
    public function __construct(bool $defaultRoutes = true, ?ConfigInterface $config = null)
    {
        parent::__construct($defaultRoutes, $config);
    }
    
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
