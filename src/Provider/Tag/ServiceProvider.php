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

namespace PhalconKit\Provider\Tag;

use PhalconKit\Di\DiInterface;
use PhalconKit\Tag;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the PhalconKit tag helper service.
 *
 * Phalcon tag helpers are static, so the provider stores the active DI
 * container on `PhalconKit\Tag` and returns a shared helper instance for code
 * that still resolves `tag` from DI. This keeps static helpers and injected
 * helpers pointed at the same service container.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'tag';
    
    /**
     * Register the shared `tag` service and bind the static tag DI.
     *
     * Applications replacing this service should keep `Tag::setDI()` behavior
     * or static tag helpers will not be able to resolve assets, escaper, and
     * other DI-backed services.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $tag = new Tag();
            Tag::setDI($di);
            return $tag;
        });
    }
}
