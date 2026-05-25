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

namespace PhalconKit\Provider\Assets;

use PhalconKit\Di\DiInterface;
use Phalcon\Html\Escaper\EscaperInterface;
use PhalconKit\Html\TagFactory;
use PhalconKit\Assets\Manager;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the assets manager service.
 *
 * The assets manager requires a native-style HTML tag factory. PhalconKit keeps
 * that dependency local to this provider so the public `tag` service can remain
 * the static helper facade while assets rendering receives the factory shape it
 * expects.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'assets';
    
    /**
     * Register the shared `assets` service.
     *
     * @throws \PhalconKit\Exception\ServiceException When the `escaper` service
     *     is missing or does not implement Phalcon's escaper contract.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $escaper = $di->getTyped('escaper', EscaperInterface::class);
        
        // The assets manager expects a native-style TagFactory. The public
        // `tag` service still exposes PhalconKit's static helper facade, so
        // unifying both services is a compatibility discussion rather than a
        // provider-local swap.
        $tag = new TagFactory($escaper);
        
        $di->setShared($this->getName(), function () use ($tag) {
            return new Manager($tag);
        });
    }
}
