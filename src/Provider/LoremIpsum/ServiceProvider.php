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

namespace PhalconKit\Provider\LoremIpsum;

use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Di\DiInterface;
use joshtronic\LoremIpsum;

/**
 * Registers the lorem ipsum generator service.
 *
 * This service is primarily useful for scaffolding, demos, fixtures, and tests
 * that need deterministic access to the package's lorem ipsum generator.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'loremIpsum';
    
    /**
     * Register the shared `loremIpsum` service.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () {
            
            return new LoremIpsum();
        });
    }
}
