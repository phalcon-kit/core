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

namespace PhalconKit\Provider\Translate;

use PhalconKit\Di\DiInterface;
use Phalcon\Translate\Adapter\Gettext;
use Phalcon\Translate\InterpolatorFactory;
use PhalconKit\Locale;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the translation adapter service.
 *
 * The provider creates Phalcon's Gettext adapter from `translate` config and
 * applies the current `locale` service value as the active locale. Translation
 * setup therefore depends on the locale provider being registered first.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'translate';
    
    /**
     * Register the shared `translate` service.
     *
     * @throws \PhalconKit\Exception\ServiceException When the `locale` service
     *     is missing or is not a PhalconKit locale instance.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            $translateConfig = $config->pathToArray('translate') ?? [];
            
            $locale = $di->getTyped('locale', Locale::class);
            
            $translate = new Gettext(new InterpolatorFactory(), $translateConfig);
            $translate->setLocale(LC_ALL, [$locale->getLocale() . '.UTF-8']);
            
            return $translate;
        });
    }
}
