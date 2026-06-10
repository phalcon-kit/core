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

use Phalcon\Di\Di;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Exception\InvalidArgumentException;
use PhalconKit\Exception\LogicException;
use PhalconKit\Exception\RuntimeException;
use PhalconKit\Mvc\Model\EagerLoading\EagerLoad as EagerLoadNode;
use PhalconKit\Mvc\Model\EagerLoading\Loader;
use PhalconKit\Mvc\Model\Traits\EagerLoad as EagerLoadTrait;
use PhalconKit\Mvc\Model\Traits\Events;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EagerLoadForwardDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EagerLoadInvalidForwardDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EagerLoadInvalidHostDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EagerLoadParentModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeModelsManager;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EventsTraitResultsetDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\IntermediateDeleteModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\ModelBehaviorDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelatedDeleteModelDouble;
use ReflectionClass;
use ReflectionNamedType;

class EagerLoadTest extends AbstractUnit
{
    public function testNativeFinderAbstractContractsMatchPatchedPhalconSignatures(): void
    {
        $trait = new ReflectionClass(EagerLoadTrait::class);

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

        $this->assertModelAggregateSignature($trait, 'count', 'null', 'Phalcon\Mvc\Model\ResultsetInterface|int');
        $this->assertModelAggregateSignature($trait, 'sum', 'null', 'Phalcon\Mvc\Model\ResultsetInterface|float');
        $this->assertModelAggregateSignature($trait, 'average', 'array', 'Phalcon\Mvc\Model\ResultsetInterface|float');
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

    public function testLoaderBuildsNestedTreeAndAppliesOnlyTerminalConstraints(): void
    {
        $this->newEagerLoadModelsManager();
        $root = new ModelBehaviorDouble();
        $root->id = 1;

        $constraint = static fn (mixed $builder): mixed => $builder;
        $loader = new Loader($root, [
            'Children.Grandchildren' => $constraint,
        ]);

        $tree = $this->buildEagerLoadTree($loader);

        $this->assertSame(['Children', 'Children.Grandchildren'], array_keys($tree));
        $this->assertSame($loader, $this->readEagerLoadNodeProperty($tree['Children'], 'parent'));
        $this->assertNull($this->readEagerLoadNodeProperty($tree['Children'], 'constraints'));
        $this->assertSame($tree['Children'], $this->readEagerLoadNodeProperty($tree['Children.Grandchildren'], 'parent'));
        $this->assertSame(
            $constraint,
            $this->readEagerLoadNodeProperty($tree['Children.Grandchildren'], 'constraints')
        );
    }

    public function testLoaderRejectsMissingNestedRelationBeforeQueryExecution(): void
    {
        $manager = $this->newEagerLoadModelsManager(registerGrandchildren: false);
        $root = new ModelBehaviorDouble();
        $root->id = 1;
        $loader = new Loader($root, ['Children.Missing']);

        try {
            $this->buildEagerLoadTree($loader);
            $this->fail('Expected missing nested eager-load relation.');
        }
        catch (RuntimeException $exception) {
            $this->assertSame(
                'There is no defined relation for the model `'
                . ModelBehaviorDouble::class
                . '` using alias `Missing`',
                $exception->getMessage()
            );
        }

        $this->assertSame([], $manager->executeQueryCalls);
    }

    public function testFindFirstWithDeduplicatesThroughRelationTargets(): void
    {
        $db = $this->getDb();

        $db->execute('DROP TEMPORARY TABLE IF EXISTS eager_load_through_model_double');
        $db->execute('DROP TEMPORARY TABLE IF EXISTS eager_load_target_model_double');
        $db->execute('DROP TEMPORARY TABLE IF EXISTS eager_load_parent_model_double');

        $db->execute('
            CREATE TEMPORARY TABLE eager_load_parent_model_double (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            )
        ');
        $db->execute('
            CREATE TEMPORARY TABLE eager_load_target_model_double (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                label VARCHAR(64) NOT NULL,
                deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            )
        ');
        $db->execute('
            CREATE TEMPORARY TABLE eager_load_through_model_double (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                parentId INT UNSIGNED NOT NULL,
                targetId INT UNSIGNED NOT NULL,
                deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            )
        ');

        $db->execute('INSERT INTO eager_load_parent_model_double (id) VALUES (1)');
        $db->execute("INSERT INTO eager_load_target_model_double (id, label) VALUES (1, 'first'), (2, 'second')");
        $db->execute('
            INSERT INTO eager_load_through_model_double (parentId, targetId)
            VALUES (1, 1), (1, 1), (1, 2)
        ');

        $parent = EagerLoadParentModelDouble::findFirstWith(['TargetList'], [
            'id = :id:',
            'bind' => ['id' => 1],
            'bindTypes' => ['id' => \Phalcon\Db\Column::BIND_PARAM_INT],
        ]);

        $this->assertInstanceOf(EagerLoadParentModelDouble::class, $parent);
        $this->assertCount(2, $parent->targetlist);
        $this->assertSame([1, 2], array_map(
            static fn ($target): int => (int)$target->readAttribute('id'),
            $parent->targetlist
        ));
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

    /**
     * @return array<string, EagerLoadNode>
     */
    private function buildEagerLoadTree(Loader $loader): array
    {
        $method = new \ReflectionMethod(Loader::class, 'buildTree');

        return $method->invoke($loader);
    }

    private function readEagerLoadNodeProperty(EagerLoadNode $node, string $property): mixed
    {
        $reflectionProperty = new \ReflectionProperty($node, $property);

        return $reflectionProperty->getValue($node);
    }

    private function newEagerLoadModelsManager(bool $registerGrandchildren = true): FakeModelsManager
    {
        $manager = new FakeModelsManager();

        $manager->setRelationByAlias(
            ModelBehaviorDouble::class,
            'Children',
            new Relation(Relation::HAS_MANY, ModelBehaviorDouble::class, 'id', 'parentId', [
                'alias' => 'Children',
            ])
        );

        if ($registerGrandchildren) {
            $manager->setRelationByAlias(
                ModelBehaviorDouble::class,
                'Grandchildren',
                new Relation(Relation::HAS_MANY, ModelBehaviorDouble::class, 'id', 'parentId', [
                    'alias' => 'Grandchildren',
                ])
            );
        }

        $this->di->setShared('modelsManager', $manager);
        Di::setDefault($this->di);

        return $manager;
    }
}
