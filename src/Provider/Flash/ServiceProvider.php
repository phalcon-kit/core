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

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'flash';
    
    protected array $cssStyle = [
        'error' => 'alert alert-danger fade in',
        'success' => 'alert alert-success fade in',
        'notice' => 'alert alert-info fade in',
        'warning' => 'alert alert-warning fade in',
    ];
    
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
