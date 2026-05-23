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

use Phalcon\Di\Di as PhalconDi;
use Phalcon\Di\DiInterface as NativeDiInterface;
use PhalconKit\Di\Di;
use PhalconKit\Di\DiInterface;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Tests\Unit\AbstractUnit;

class ServiceResolverTest extends AbstractUnit
{
    private ?NativeDiInterface $previousDefault = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousDefault = PhalconDi::getDefault();
    }

    protected function tearDown(): void
    {
        if ($this->previousDefault instanceof NativeDiInterface) {
            PhalconDi::setDefault($this->previousDefault);
        }
        else {
            PhalconDi::reset();
        }

        parent::tearDown();
    }

    public function testRequirePhalconKitContainerReturnsTypedContainer(): void
    {
        $di = new Di();

        $this->assertSame(
            $di,
            ServiceResolver::requirePhalconKitContainer($di, 'create a test component', 'the test DI')
        );
    }

    public function testRequirePhalconKitContainerRejectsNativeContainer(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(sprintf(
            'Could not create a test component because the test DI must implement "%s"; got "Phalcon\Di\Di".',
            DiInterface::class
        ));

        ServiceResolver::requirePhalconKitContainer(new PhalconDi(), 'create a test component', 'the test DI');
    }

    public function testFromContainerReturnsTypedService(): void
    {
        $di = new Di();
        $service = new \stdClass();

        $di->set('service', $service);

        $this->assertSame($service, ServiceResolver::fromContainer($di, 'service', \stdClass::class));
    }

    public function testFromContainerRequiresPhalconKitDi(): void
    {
        $di = new PhalconDi();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(sprintf(
            'because the provided DI must implement "%s"; got "Phalcon\Di\Di".',
            DiInterface::class
        ));

        ServiceResolver::fromContainer($di, 'service', \stdClass::class, context: 'static helpers');
    }

    public function testFromContainerReportsMissingServiceWithContext(): void
    {
        $di = new Di();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Could not resolve DI service "missing" for static helpers.');

        ServiceResolver::fromContainer($di, 'missing', \stdClass::class, context: 'static helpers');
    }

    public function testFromContainerOrDefaultReturnsRegisteredService(): void
    {
        $di = new Di();
        $service = new \stdClass();
        $fallback = new \stdClass();
        $di->set('service', $service);

        $this->assertSame(
            $service,
            ServiceResolver::fromContainerOrDefault(
                $di,
                'service',
                \stdClass::class,
                static fn () => $fallback,
                context: 'optional services'
            )
        );
    }

    public function testFromContainerOrDefaultReturnsTypedDefaultWhenServiceIsMissing(): void
    {
        $di = new Di();
        $fallback = new \stdClass();

        $this->assertSame(
            $fallback,
            ServiceResolver::fromContainerOrDefault(
                $di,
                'service',
                \stdClass::class,
                static fn () => $fallback,
                context: 'optional services'
            )
        );
    }

    public function testFromContainerOrDefaultRejectsInvalidDefaultFactoryOutput(): void
    {
        $di = new Di();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected default DI service "service" for optional services to be an instance of "DateTimeImmutable"; got "stdClass".'
        );

        ServiceResolver::fromContainerOrDefault(
            $di,
            'service',
            \DateTimeImmutable::class,
            static fn () => new \stdClass(),
            context: 'optional services'
        );
    }

    public function testFromContainerDelegatesTypeValidationToPhalconKitDi(): void
    {
        $di = new Di();
        $di->set('service', new \stdClass());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "service" to be an instance of "DateTimeImmutable"; got "stdClass".'
        );

        ServiceResolver::fromContainer($di, 'service', \DateTimeImmutable::class);
    }

    public function testFromDefaultReturnsTypedService(): void
    {
        $di = new Di();
        $service = new \stdClass();
        $di->set('service', $service);
        PhalconDi::setDefault($di);

        $this->assertSame($service, ServiceResolver::fromDefault('service', \stdClass::class));
    }

    public function testFromDefaultRequiresADefaultDi(): void
    {
        PhalconDi::reset();

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Could not resolve DI service "service" for static helpers because no default DI is available.'
        );

        ServiceResolver::fromDefault('service', \stdClass::class, context: 'static helpers');
    }

    public function testFromDefaultRequiresPhalconKitDi(): void
    {
        PhalconDi::setDefault(new PhalconDi());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(sprintf(
            'because the default DI must implement "%s"; got "Phalcon\Di\Di".',
            DiInterface::class
        ));

        ServiceResolver::fromDefault('service', \stdClass::class, context: 'static helpers');
    }
}
