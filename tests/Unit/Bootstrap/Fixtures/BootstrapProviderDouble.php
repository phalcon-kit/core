<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Bootstrap\Fixtures;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class BootstrapProviderDouble extends AbstractServiceProvider
{
    protected string $serviceName = 'bootstrapProviderDouble';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), fn (): string => 'registered');
    }
}
