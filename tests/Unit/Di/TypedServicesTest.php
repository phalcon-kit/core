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

namespace PhalconKit\Tests\Unit\Di;

use PhalconKit\Config\Config;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Di\Di;
use PhalconKit\Di\DiInterface;
use PhalconKit\Di\FactoryDefault;
use PhalconKit\Di\FactoryDefault\Cli as CliFactoryDefault;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Tests\Unit\AbstractUnit;

class TypedServicesTest extends AbstractUnit
{
    public function testDiImplementationsExposeTypedHelpers(): void
    {
        $this->assertInstanceOf(DiInterface::class, new Di());
        $this->assertInstanceOf(DiInterface::class, new FactoryDefault());
        $this->assertInstanceOf(DiInterface::class, new CliFactoryDefault());
    }

    public function testGetTypedReturnsServiceWhenTypeMatches(): void
    {
        $di = new Di();
        $service = new \stdClass();

        $di->set('service', $service);

        $this->assertSame($service, $di->getTyped('service', \stdClass::class));
    }

    public function testGetConfigUsesRequestedServiceName(): void
    {
        $di = new Di();
        $config = new Config();

        $di->set('customConfig', $config);

        $this->assertSame($config, $di->getConfig('customConfig'));
    }

    public function testGetConfigReturnsTypedDefaultConfigService(): void
    {
        $di = new Di();
        $config = new Config();

        $di->set('config', $config);

        $this->assertSame($config, $di->getConfig());
        $this->assertInstanceOf(ConfigInterface::class, $di->getConfig());
    }

    public function testGetTypedRejectsWrongServiceTypeWithClearMessage(): void
    {
        $di = new Di();
        $di->set('config', new \stdClass());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Expected DI service "config" to be an instance of');
        $this->expectExceptionMessage(ConfigInterface::class);
        $this->expectExceptionMessage('got "stdClass"');

        $di->getConfig();
    }

    public function testGetTypedWrapsMissingServiceResolutionFailures(): void
    {
        $di = new Di();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Could not resolve DI service "missing".');

        $di->getTyped('missing', \stdClass::class);
    }
}
