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

namespace PhalconKit\Provider\Filter;

use PhalconKit\Di\DiInterface;
use PhalconKit\Filter\Filter;
use PhalconKit\Filter\FilterFactory;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the PhalconKit filter locator.
 *
 * The provider starts from the package filter factory so the core sanitizer and
 * validator services are available, then applies any configured application
 * filters from `filters`. Applications can use that config path to add or
 * replace named filter services without replacing the provider itself.
 *
 * @see https://docs.phalcon.io/5.13/filter/
 */
class ServiceProvider extends AbstractServiceProvider
{
    /**
     * DI service name for the shared filter locator.
     */
    protected string $serviceName = 'filter';
    
    /**
     * Register the configured filter locator.
     *
     * @param DiInterface $di PhalconKit container used to read configured
     *     filter service definitions.
     *
     * @throws ServiceException When the factory does not return the expected
     *     PhalconKit filter implementation.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->set($this->getName(), function () use ($di) {

            $locator = (new FilterFactory())->newInstance();
            if (!$locator instanceof Filter) {
                throw new ServiceException('Filter factory did not create a PhalconKit filter locator.');
            }
            
            $config = $di->getConfig();
            $filterServices = $config->pathToArray('filters') ?? [];
            foreach ($filterServices as $key => $filter) {
                $locator->set($key, $filter);
            }
            
            return $locator;
        });
    }
}
