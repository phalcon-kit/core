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

use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractBind;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractCache;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractColumn;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractConditions;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractDistinct;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractFields;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractGroup;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractHaving;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractJoins;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractLimit;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractOffset;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractOrder;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractSave;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractWith;
use PhalconKit\Mvc\Controller\Traits\Query\Bind;
use PhalconKit\Mvc\Controller\Traits\Query\Cache;
use PhalconKit\Mvc\Controller\Traits\Query\Column;
use PhalconKit\Mvc\Controller\Traits\Query\Conditions;
use PhalconKit\Mvc\Controller\Traits\Query\Distinct;
use PhalconKit\Mvc\Controller\Traits\Query\Fields;
use PhalconKit\Mvc\Controller\Traits\Query\Group;
use PhalconKit\Mvc\Controller\Traits\Query\Having;
use PhalconKit\Mvc\Controller\Traits\Query\Joins;
use PhalconKit\Mvc\Controller\Traits\Query\Limit;
use PhalconKit\Mvc\Controller\Traits\Query\Offset;
use PhalconKit\Mvc\Controller\Traits\Query\Order;
use PhalconKit\Mvc\Controller\Traits\Query\Save;
use PhalconKit\Mvc\Controller\Traits\Query\With;
use PhalconKit\Tests\Unit\AbstractUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class QueryAbstractContractsTest extends AbstractUnit
{
    /**
     * @param class-string $abstractTrait
     * @param class-string $implementationTrait
     */
    #[DataProvider('queryAbstractContractProvider')]
    public function testQueryAbstractTraitsMatchTheirConcreteContracts(
        string $abstractTrait,
        string $implementationTrait
    ): void {
        $abstractReflection = new ReflectionClass($abstractTrait);
        $implementationReflection = new ReflectionClass($implementationTrait);

        foreach ($abstractReflection->getMethods() as $abstractMethod) {
            $this->assertTrue(
                $implementationReflection->hasMethod($abstractMethod->getName()),
                sprintf('%s is missing %s().', $implementationTrait, $abstractMethod->getName())
            );

            $implementationMethod = $implementationReflection->getMethod($abstractMethod->getName());

            $this->assertSameMethodSignature($abstractMethod, $implementationMethod);
        }
    }

    /**
     * @return iterable<string, array{0: class-string, 1: class-string}>
     */
    public static function queryAbstractContractProvider(): iterable
    {
        yield 'bind' => [AbstractBind::class, Bind::class];
        yield 'cache' => [AbstractCache::class, Cache::class];
        yield 'column' => [AbstractColumn::class, Column::class];
        yield 'conditions' => [AbstractConditions::class, Conditions::class];
        yield 'distinct' => [AbstractDistinct::class, Distinct::class];
        yield 'fields' => [AbstractFields::class, Fields::class];
        yield 'group' => [AbstractGroup::class, Group::class];
        yield 'having' => [AbstractHaving::class, Having::class];
        yield 'joins' => [AbstractJoins::class, Joins::class];
        yield 'limit' => [AbstractLimit::class, Limit::class];
        yield 'offset' => [AbstractOffset::class, Offset::class];
        yield 'order' => [AbstractOrder::class, Order::class];
        yield 'save' => [AbstractSave::class, Save::class];
        yield 'with' => [AbstractWith::class, With::class];
    }

    private function assertSameMethodSignature(ReflectionMethod $abstractMethod, ReflectionMethod $implementationMethod): void
    {
        $methodName = $abstractMethod->getDeclaringClass()->getName() . '::' . $abstractMethod->getName();

        $this->assertSame(
            (string) $abstractMethod->getReturnType(),
            (string) $implementationMethod->getReturnType(),
            $methodName . ' return type differs from concrete trait contract.'
        );

        $abstractParameters = $abstractMethod->getParameters();
        $implementationParameters = $implementationMethod->getParameters();

        $this->assertCount(
            count($abstractParameters),
            $implementationParameters,
            $methodName . ' parameter count differs from concrete trait contract.'
        );

        foreach ($abstractParameters as $index => $abstractParameter) {
            $this->assertSameParameterSignature(
                $methodName,
                $abstractParameter,
                $implementationParameters[$index]
            );
        }
    }

    private function assertSameParameterSignature(
        string $methodName,
        ReflectionParameter $abstractParameter,
        ReflectionParameter $implementationParameter
    ): void {
        $label = $methodName . '::$' . $abstractParameter->getName();

        $this->assertSame($abstractParameter->getName(), $implementationParameter->getName(), $label . ' name differs.');
        $this->assertSame(
            (string) $abstractParameter->getType(),
            (string) $implementationParameter->getType(),
            $label . ' type differs.'
        );
        $this->assertSame(
            $abstractParameter->isOptional(),
            $implementationParameter->isOptional(),
            $label . ' optionality differs.'
        );
        $this->assertSame(
            $abstractParameter->isVariadic(),
            $implementationParameter->isVariadic(),
            $label . ' variadic flag differs.'
        );
        $this->assertSame(
            $abstractParameter->isPassedByReference(),
            $implementationParameter->isPassedByReference(),
            $label . ' by-reference flag differs.'
        );
        $this->assertSame(
            $this->defaultValue($abstractParameter),
            $this->defaultValue($implementationParameter),
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
