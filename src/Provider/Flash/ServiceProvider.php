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

namespace PhalconKit\Provider\Flash;

use Phalcon\Flash\Direct;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the direct flash messaging service.
 *
 * The default service uses Phalcon's direct flash adapter, enables auto-escape,
 * and applies Bootstrap-compatible CSS classes. Applications that need session
 * flash behavior should register their own provider intentionally because that
 * changes message lifetime and session requirements.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'flash';
    
    /**
     * Default CSS classes used for direct flash message types.
     *
     * @var array<string, string>
     */
    protected array $cssStyle = [
        'error' => 'alert alert-danger fade in',
        'success' => 'alert alert-success fade in',
        'notice' => 'alert alert-info fade in',
        'warning' => 'alert alert-warning fade in',
    ];
    
    /**
     * Register the shared `flash` service.
     *
     * The flash adapter receives DI and auto-escaping before being returned, so
     * controller output can use the service without repeating those safety
     * settings.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $cssStyle = $this->cssStyle;
        $di->setShared($this->getName(), function () use ($di, $cssStyle) {
            $flash = new Direct();
            $flash->setAutoescape(true);
            $flash->setDI($di);
            $flash->setCssClasses($cssStyle);
            
            return $flash;
        });
    }
}
