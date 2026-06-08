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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Mvc\Model\Manager;

class FakeModelsManager extends Manager
{
    /**
     * @var array<string, RelationInterface>
     */
    public array $relations = [];

    /**
     * @var array<class-string, ModelInterface>
     */
    public array $loadedModels = [];

    public array $relationRecordCalls = [];
    public array $executeQueryCalls = [];
    public FakeQueryResult $queryResult;

    public function __construct()
    {
        $this->queryResult = new FakeQueryResult();
    }

    #[\Override]
    public function getRelationByAlias(string $modelName, string $alias): RelationInterface|bool
    {
        return $this->relations[strtolower($modelName) . ':' . strtolower($alias)] ?? false;
    }

    public function setRelationByAlias(string $modelName, string $alias, RelationInterface $relation): void
    {
        $this->relations[strtolower($modelName) . ':' . strtolower($alias)] = $relation;
    }

    #[\Override]
    public function getRelationRecords(
        RelationInterface $relation,
        ModelInterface $record,
        mixed $parameters = null,
        ?string $method = null
    ): mixed {
        $this->relationRecordCalls[] = [$relation, $record, $parameters, $method];
        return $this->queryResult;
    }

    #[\Override]
    public function load(string $modelName): ModelInterface
    {
        return $this->loadedModels[$modelName] ?? new $modelName();
    }

    #[\Override]
    public function executeQuery(string $phql, mixed $placeholders = null, mixed $types = null): mixed
    {
        $this->executeQueryCalls[] = [$phql, $placeholders, $types];
        return $this->queryResult;
    }

    #[\Override]
    public function setBehavior(ModelInterface $model, string $behaviorName, BehaviorInterface $behavior): void
    {
        parent::setBehavior($model, $behaviorName, $behavior);
    }
}
