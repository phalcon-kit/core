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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractDebug;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExport;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractExpose;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractFractal;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractRestResponse;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractStatusCode;
use PhalconKit\Mvc\Controller\Traits\Interfaces\BehaviorInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\DebugInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\ExportInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\ExposeInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\FractalInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\ModelInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\RestResponseInterface;
use PhalconKit\Mvc\Controller\Traits\Interfaces\StatusCodeInterface;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class InterfaceContractsTest extends AbstractUnit
{
    /**
     * @param class-string $interface
     * @param class-string $abstractTrait
     */
    #[DataProvider('controllerTraitContractProvider')]
    public function testControllerTraitInterfacesMatchTheirAbstractContracts(
        string $interface,
        string $abstractTrait
    ): void {
        $interfaceReflection = new ReflectionClass($interface);
        $traitReflection = new ReflectionClass($abstractTrait);

        foreach ($interfaceReflection->getMethods() as $interfaceMethod) {
            $this->assertTrue(
                $traitReflection->hasMethod($interfaceMethod->getName()),
                sprintf('%s is missing %s().', $abstractTrait, $interfaceMethod->getName())
            );

            $traitMethod = $traitReflection->getMethod($interfaceMethod->getName());

            $this->assertSameMethodSignature($interfaceMethod, $traitMethod);
        }
    }

    /**
     * @return iterable<string, array{0: class-string, 1: class-string}>
     */
    public static function controllerTraitContractProvider(): iterable
    {
        yield 'behavior' => [BehaviorInterface::class, AbstractBehavior::class];
        yield 'debug' => [DebugInterface::class, AbstractDebug::class];
        yield 'export' => [ExportInterface::class, AbstractExport::class];
        yield 'expose' => [ExposeInterface::class, AbstractExpose::class];
        yield 'fractal' => [FractalInterface::class, AbstractFractal::class];
        yield 'model' => [ModelInterface::class, AbstractModel::class];
        yield 'rest response' => [RestResponseInterface::class, AbstractRestResponse::class];
        yield 'status code' => [StatusCodeInterface::class, AbstractStatusCode::class];
    }

    private function assertSameMethodSignature(ReflectionMethod $interfaceMethod, ReflectionMethod $traitMethod): void
    {
        $methodName = $interfaceMethod->getDeclaringClass()->getName() . '::' . $interfaceMethod->getName();

        $this->assertSame(
            (string) $interfaceMethod->getReturnType(),
            (string) $traitMethod->getReturnType(),
            $methodName . ' return type differs from abstract trait contract.'
        );

        $interfaceParameters = $interfaceMethod->getParameters();
        $traitParameters = $traitMethod->getParameters();

        $this->assertCount(
            count($interfaceParameters),
            $traitParameters,
            $methodName . ' parameter count differs from abstract trait contract.'
        );

        foreach ($interfaceParameters as $index => $interfaceParameter) {
            $this->assertSameParameterSignature($methodName, $interfaceParameter, $traitParameters[$index]);
        }
    }

    private function assertSameParameterSignature(
        string $methodName,
        ReflectionParameter $interfaceParameter,
        ReflectionParameter $traitParameter
    ): void {
        $label = $methodName . '::$' . $interfaceParameter->getName();

        $this->assertSame($interfaceParameter->getName(), $traitParameter->getName(), $label . ' name differs.');
        $this->assertSame(
            (string) $interfaceParameter->getType(),
            (string) $traitParameter->getType(),
            $label . ' type differs.'
        );
        $this->assertSame(
            $interfaceParameter->isOptional(),
            $traitParameter->isOptional(),
            $label . ' optionality differs.'
        );
        $this->assertSame(
            $interfaceParameter->isVariadic(),
            $traitParameter->isVariadic(),
            $label . ' variadic flag differs.'
        );
        $this->assertSame(
            $interfaceParameter->isPassedByReference(),
            $traitParameter->isPassedByReference(),
            $label . ' by-reference flag differs.'
        );
        $this->assertSame(
            $this->defaultValue($interfaceParameter),
            $this->defaultValue($traitParameter),
            $label . ' default value differs.'
        );
    }

    private function defaultValue(ReflectionParameter $parameter): mixed
    {
        return $parameter->isDefaultValueAvailable()
            ? $parameter->getDefaultValue()
            : '__no_default__';
    }
}
