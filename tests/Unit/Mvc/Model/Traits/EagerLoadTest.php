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

namespace PhalconKit\Tests\Unit\Mvc\Model\Traits;

use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Exception\InvalidArgumentException;
use PhalconKit\Exception\LogicException;
use PhalconKit\Exception\RuntimeException;
use PhalconKit\Mvc\Model\EagerLoading\Loader;
use PhalconKit\Mvc\Model\Traits\EagerLoad;
use PhalconKit\Mvc\Model\Traits\Events;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EagerLoadForwardDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EagerLoadInvalidForwardDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EagerLoadInvalidHostDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EventsTraitResultsetDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\IntermediateDeleteModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelatedDeleteModelDouble;
use ReflectionClass;
use ReflectionNamedType;

class EagerLoadTest extends AbstractUnit
{
    public function testNativeFinderAbstractContractsMatchPatchedPhalconSignatures(): void
    {
        $trait = new ReflectionClass(EagerLoad::class);

        $find = $trait->getMethod('find');
        $findParameters = $find->getParameters();
        $this->assertTrue($find->isAbstract());
        $this->assertTrue($find->isStatic());
        $this->assertCount(1, $findParameters);
        $this->assertSame('mixed', (string)$findParameters[0]->getType());
        $this->assertTrue($findParameters[0]->isDefaultValueAvailable());
        $this->assertNull($findParameters[0]->getDefaultValue());
        $this->assertSame(ResultsetInterface::class, (string)$find->getReturnType());

        $findFirst = $trait->getMethod('findFirst');
        $findFirstParameters = $findFirst->getParameters();
        $findFirstReturnType = $findFirst->getReturnType();
        $this->assertTrue($findFirst->isAbstract());
        $this->assertTrue($findFirst->isStatic());
        $this->assertCount(1, $findFirstParameters);
        $this->assertSame('mixed', (string)$findFirstParameters[0]->getType());
        $this->assertTrue($findFirstParameters[0]->isDefaultValueAvailable());
        $this->assertNull($findFirstParameters[0]->getDefaultValue());
        $this->assertInstanceOf(ReflectionNamedType::class, $findFirstReturnType);
        $this->assertSame('mixed', $findFirstReturnType->getName());
    }

    public function testEventAggregateContractsMatchPatchedPhalconSignatures(): void
    {
        $trait = new ReflectionClass(Events::class);

        $this->assertModelAggregateSignature($trait, 'count', 'null', 'Phalcon\Mvc\Model\ResultsetInterface|int|false');
        $this->assertModelAggregateSignature($trait, 'sum', 'null', 'Phalcon\Mvc\Model\ResultsetInterface|float|false');
        $this->assertModelAggregateSignature($trait, 'average', 'array', 'Phalcon\Mvc\Model\ResultsetInterface|float|false');
        $this->assertModelAggregateSignature($trait, 'minimum', 'null', 'Phalcon\Mvc\Model\ResultsetInterface|float|false');
        $this->assertModelAggregateSignature($trait, 'maximum', 'null', 'Phalcon\Mvc\Model\ResultsetInterface|float|false');
    }

    public function testFindWithByRejectsUnexpectedNativeFinderReturn(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Expected "' . EagerLoadInvalidForwardDouble::class . '::findByBroken()" to return "'
            . ResultsetInterface::class
            . '" for eager loading; got "stdClass".'
        );

        EagerLoadInvalidForwardDouble::exposeFindWithByBroken();
    }

    public function testFindWithByAcceptsTraversableResultsetInterface(): void
    {
        $first = new RelatedDeleteModelDouble();
        $second = new RelatedDeleteModelDouble();
        EagerLoadForwardDouble::$findByCustomResult = new EventsTraitResultsetDouble([$first, $second]);

        $this->assertSame([$first, $second], EagerLoadForwardDouble::exposeFindWithByCustom());

        EagerLoadForwardDouble::$findByCustomResult = new EventsTraitResultsetDouble();

        $this->assertSame([], EagerLoadForwardDouble::exposeFindWithByCustom());
    }

    public function testGetParametersFromArgumentsNormalizesColumnSelections(): void
    {
        $arguments = [
            ['Author'],
            ['columns' => ['id', 'name'], 'conditions' => 'active = 1'],
        ];

        $parameters = EagerLoadInvalidForwardDouble::getParametersFromArguments($arguments);

        $this->assertSame([['Author']], $arguments);
        $this->assertSame([
            'columns' => ['*', 'id', 'name'],
            'conditions' => 'active = 1',
        ], $parameters);

        $arguments = [
            ['Author'],
            ['columns' => 'id, name'],
        ];

        $parameters = EagerLoadInvalidForwardDouble::getParametersFromArguments($arguments);

        $this->assertSame('*, id, name', $parameters['columns']);

        $arguments = [
            ['Author'],
            ['columns' => '*, id, name'],
        ];

        $parameters = EagerLoadInvalidForwardDouble::getParametersFromArguments($arguments);

        $this->assertSame('*, id, name', $parameters['columns']);
    }

    public function testFindWithAcceptsTraversableResultsetInterface(): void
    {
        $first = new RelatedDeleteModelDouble();
        $second = new RelatedDeleteModelDouble();
        RelatedDeleteModelDouble::$findResult = new EventsTraitResultsetDouble([$first, $second]);

        $this->assertSame([$first, $second], RelatedDeleteModelDouble::findWith());

        RelatedDeleteModelDouble::$findResult = new EventsTraitResultsetDouble();

        $this->assertSame([], RelatedDeleteModelDouble::findWith());
    }

    public function testLoaderFromResultsetAcceptsTraversableResultsetInterface(): void
    {
        $first = new RelatedDeleteModelDouble();
        $second = new RelatedDeleteModelDouble();

        $this->assertSame(
            [$first, $second],
            Loader::fromResultset(new EventsTraitResultsetDouble([$first, $second]))
        );
        $this->assertSame([], Loader::fromResultset(new EventsTraitResultsetDouble()));
    }

    public function testLoaderFromResultsetRejectsNonModelRows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected value of `subject` to be either a ModelInterface object'
        );

        Loader::fromResultset(new EventsTraitResultsetDouble([new \stdClass()]));
    }

    public function testLoaderFromResultsetRejectsMixedModelClasses(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected value of `subject` to be either a ModelInterface object'
        );

        Loader::fromResultset(new EventsTraitResultsetDouble([
            new RelatedDeleteModelDouble(),
            new IntermediateDeleteModelDouble(),
        ]));
    }

    public function testLoaderFromArrayAcceptsKeyedAndSparseModelArrays(): void
    {
        $first = new RelatedDeleteModelDouble();
        $second = new RelatedDeleteModelDouble();

        $this->assertSame(
            [$first, $second],
            Loader::fromArray([
                10 => $first,
                30 => $second,
            ])
        );
        $this->assertSame(
            [$first, $second],
            Loader::fromArray([
                'first' => $first,
                'empty' => null,
                'second' => $second,
            ])
        );
        $this->assertSame([], Loader::fromArray([]));
    }

    public function testLoadRejectsInvalidTraitHost(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Eager-loading model helpers require the trait host to implement "' . ModelInterface::class . '"'
        );

        (new EagerLoadInvalidHostDouble())->load();
    }

    private function assertModelAggregateSignature(
        ReflectionClass $trait,
        string $methodName,
        string $default,
        string $returnType
    ): void {
        $method = $trait->getMethod($methodName);
        $parameters = $method->getParameters();

        $this->assertTrue($method->isStatic());
        $this->assertCount(1, $parameters);
        $this->assertSame($methodName === 'average' ? 'array' : 'mixed', (string)$parameters[0]->getType());
        $this->assertTrue($parameters[0]->isDefaultValueAvailable());

        if ($default === 'array') {
            $this->assertSame([], $parameters[0]->getDefaultValue());
        }
        else {
            $this->assertNull($parameters[0]->getDefaultValue());
        }

        $this->assertSame($returnType, (string)$method->getReturnType());
    }
}
