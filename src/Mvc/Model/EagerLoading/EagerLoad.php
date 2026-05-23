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

namespace PhalconKit\Mvc\Model\EagerLoading;

use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\RelationInterface;
use PhalconKit\Exception\RuntimeException;
use PhalconKit\Mvc\Model\Interfaces\RelationshipInterface as PhalconKitRelationshipInterface;

/**
 * Represents a level in the relations tree to be eagerly loaded
 */
final class EagerLoad
{
    private RelationInterface $relation;

    /** @var null|callable */
    private $constraints;

    /** @var Loader|EagerLoad */
    private $parent;

    /** @var null|\Phalcon\Mvc\ModelInterface[] */
    private ?array $subject = null;

    /**
     * @param Relation $relation
     * @param null|callable $constraints
     * @param Loader|EagerLoad $parent
     */
    public function __construct(Relation $relation, $constraints, $parent)
    {
        $this->relation = $relation;
        $this->constraints = is_callable($constraints) ? $constraints : null;
        $this->parent = $parent;
    }

    /**
     * @return null|\Phalcon\Mvc\ModelInterface[]
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Executes each db query needed
     *
     * Note: The {$alias} property is set two times because Phalcon Model ignores
     * empty arrays when overloading property set.
     *
     * Also {@see https://github.com/stibiumz/phalcon.eager-loading/issues/1}
     *
     * @return $this
     */
    public function load()
    {
        $parentSubject = $this->parent->getSubject();

        if (empty($parentSubject)) {
            return $this;
        }

        $relation = $this->relation;

        $options = $relation->getOptions();
        $alias = strtolower($options['alias']);
        $relField = $relation->getFields();
        $relReferencedModel = $relation->getReferencedModel();
        $relReferencedField = $relation->getReferencedFields();

        // @todo support multiples fields with eager loading
        if (is_array($relField)) {
            throw new RuntimeException('Relation field must be a string, multiple fields are not supported yet.');
        }
        if (is_array($relReferencedField)) {
            throw new RuntimeException('Relation Referenced field must be a string, multiple fields are not supported yet.');
        }

        // PHQL has problems with this slash
        if ($relReferencedModel[0] === '\\') {
            $relReferencedModel = ltrim($relReferencedModel, '\\');
        }

        $bindValues = [];
        foreach ($parentSubject as $record) {
            assert($record instanceof EntityInterface);
            $relationKey = $this->getRelationKey($record, $relField);
            if ($relationKey !== null) {
                $bindValues[$relationKey] = true;
            }
            // @todo support multiples fields with eager loading
//            $relFieldAr = is_array($relField)? $relField : [$relField];
//            foreach ($relFieldAr as $relField) {
//                $bindValues[$record->readAttribute($relField)] = true;
//            }
        }
        unset($record);

        $bindValues = array_keys($bindValues);

        $subjectCount = count($parentSubject);
        $isManyToManyForMany = false;
        $isThrough = $relation->isThrough();
        $isSingle = $this->isSingleRelation($relation, $isThrough);

        if ($bindValues === []) {
            $this->assignMissingRelations($parentSubject, $alias, $isSingle);
            $this->subject = [];
            return $this;
        }

        $builder = new QueryBuilder();
        $builder->from($relReferencedModel);

        if ($isThrough) {
            $relIrModel = $relation->getIntermediateModel();
            $relIrField = $relation->getIntermediateFields();
            $relIrReferencedField = $relation->getIntermediateReferencedFields();

            if (is_array($relIrField)) {
                throw new RuntimeException('Relation Intermediate field must be a string, multiple fields are not supported yet.');
            }
            if (is_array($relIrReferencedField)) {
                throw new RuntimeException('Relation Intermediate Referenced field must be a string, multiple fields are not supported yet.');
            }

            if ($subjectCount === 1) {
                // The query is for a single model
                $builder
                    ->innerJoin(
                        $relIrModel,
                        sprintf(
                            '[%s].[%s] = [%s].[%s]',
                            $relIrModel,
                            $relIrReferencedField,
                            $relReferencedModel,
                            $relReferencedField
                        )
                    )
                    ->where('[' . $relIrModel . '].[deleted] <> 1') // @todo do this correctly
                    ->inWhere("[{$relIrModel}].[{$relIrField}]", $bindValues)
                ;

                // @todo see if we should enable this grouping by default or even add a configuration for this
//                $builder->groupBy("[{$relIrModel}].[{$relIrReferencedField}]");
            }
            else {
                // The query is for many models, so it's needed to execute an
                // extra query
                $isManyToManyForMany = true;

                $relIrValues = new QueryBuilder();
                $relIrValues = $relIrValues
                    ->from($relIrModel)
                    ->where('[' . $relIrModel . '].[deleted] <> 1') // @todo do this correctly
                    ->inWhere("[{$relIrModel}].[{$relIrField}]", $bindValues)
                    ->getQuery()
                    ->execute()
                ;

                $bindValues = [];
                $modelReferencedModelValues = [];
                foreach ($relIrValues as $row) {
                    assert($row instanceof EntityInterface);

                    $modelKey = $this->getRelationKey($row, $relIrField);
                    $referencedKey = $this->getRelationKey($row, $relIrReferencedField);
                    if ($modelKey === null || $referencedKey === null) {
                        continue;
                    }

                    $bindValues[$referencedKey] = true;
                    $modelReferencedModelValues[$modelKey][$referencedKey] = true;
                }
                unset($relIrValues);
                unset($row);

                if ($bindValues === []) {
                    $this->assignMissingRelations($parentSubject, $alias, false);
                    $this->subject = [];
                    return $this;
                }

                $builder->inWhere(
                    "[{$relReferencedModel}].[{$relReferencedField}]",
                    array_keys($bindValues)
                );
            }
        }
        else {
            $builder->inWhere(
                "[{$relReferencedModel}].[{$relReferencedField}]",
                $bindValues
            );
        }

        $constraint = $this->constraints;
        if (is_callable($constraint)) {
            $constraint($builder);
        }

        $records = [];

        if ($isManyToManyForMany) {
            foreach ($builder->getQuery()->execute() as $record) {
                assert($record instanceof EntityInterface);

                $referencedKey = $this->getRelationKey($record, $relReferencedField);
                if ($referencedKey !== null) {
                    $records[$referencedKey] = $record;
                }
            }
            unset($record);

            foreach ($parentSubject as $record) {
                assert($record instanceof EntityInterface);
                $referencedFieldValue = $this->getRelationKey($record, $relField);

                if ($referencedFieldValue !== null && isset($modelReferencedModelValues[$referencedFieldValue])) {
                    $referencedModels = [];

                    foreach ($modelReferencedModelValues[$referencedFieldValue] as $idx => $_) {
                        if (isset($records[$idx])) {
                            $referencedModels[] = $records[$idx];
                        }
                    }

                    $this->assignRelation($record, $alias, $referencedModels);
                }
                else {
                    $this->assignRelation($record, $alias, []);
                }
            }
            unset($record);

            $records = array_values($records);
        }
        else {
            // We expect a single object or a set of it
            if ($subjectCount === 1) {
                // Keep all records in memory
                foreach ($builder->getQuery()->execute() as $record) {
                    $records[] = $record;
                }
                unset($record);

                $record = $parentSubject[0];
                if ($isSingle) {
                    $this->assignRelation($record, $alias, empty($records) ? null : $records[0]);
                }
                elseif (empty($records)) {
                    $this->assignRelation($record, $alias, []);
                }
                else {
                    $this->assignRelation($record, $alias, $records);
                }
            }
            else {
                $indexedRecords = [];

                // Keep all records in memory
                foreach ($builder->getQuery()->execute() as $record) {
                    $records[] = $record;
                    assert($record instanceof EntityInterface);
                    $referencedKey = $this->getRelationKey($record, $relReferencedField);
                    if ($referencedKey === null) {
                        continue;
                    }

                    if ($isSingle) {
                        $indexedRecords[$referencedKey] = $record;
                    }
                    else {
                        $indexedRecords[$referencedKey][] = $record;
                    }
                }

                foreach ($parentSubject as $record) {
                    assert($record instanceof EntityInterface);
                    $referencedFieldValue = $this->getRelationKey($record, $relField);

                    if ($referencedFieldValue !== null && isset($indexedRecords[$referencedFieldValue])) {
                        $this->assignRelation($record, $alias, $indexedRecords[$referencedFieldValue]);
                    }
                    else {
                        $this->assignRelation($record, $alias, $isSingle ? null : []);
                    }
                }
                unset($record);
            }
        }

        $this->subject = $records;

        return $this;
    }

    /**
     * @param array<int, ModelInterface> $records
     */
    private function assignMissingRelations(array $records, string $alias, bool $isSingle): void
    {
        foreach ($records as $record) {
            $this->assignRelation($record, $alias, $isSingle ? null : []);
        }
    }

    private function getRelationKey(EntityInterface $record, string $field): int|string|null
    {
        $value = $record->readAttribute($field);
        if (
            $value === null ||
            $value === false ||
            $value === '' ||
            (is_string($value) && strcasecmp(trim($value), 'NULL') === 0)
        ) {
            return null;
        }

        return is_int($value) || is_string($value) ? $value : (string)$value;
    }

    private function isSingleRelation(RelationInterface $relation, bool $isThrough): bool
    {
        return !$isThrough && (
            $relation->getType() === Relation::HAS_ONE ||
            $relation->getType() === Relation::BELONGS_TO
        );
    }

    private function assignRelation(ModelInterface $record, string $alias, mixed $value): void
    {
        if ($record instanceof PhalconKitRelationshipInterface) {
            $record->setLoadedRelatedAlias($alias, $value);
            return;
        }

        if (is_array($value)) {
            if ($value !== []) {
                $record->{$alias} = $value;
            }

            $record->{$alias} = null;
            $record->{$alias} = $value;
            return;
        }

        $record->{$alias} = $value;
    }
}
