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

namespace PhalconKit\Provider\Env;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Env;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'env';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), Env::class);
    }
}
