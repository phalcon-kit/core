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
            throw new \RuntimeException('Relation field must be a string, multiple fields are not supported yet.');
        }
        if (is_array($relReferencedField)) {
            throw new \RuntimeException('Relation Referenced field must be a string, multiple fields are not supported yet.');
        }

        // PHQL has problems with this slash
        if ($relReferencedModel[0] === '\\') {
            $relReferencedModel = ltrim($relReferencedModel, '\\');
        }

        $bindValues = [];
        foreach ($parentSubject as $record) {
            assert($record instanceof EntityInterface);
            $bindValues[$record->readAttribute($relField)] = true;
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

        $builder = new QueryBuilder();
        $builder->from($relReferencedModel);

        if ($isThrough = $relation->isThrough()) {
            $relIrModel = $relation->getIntermediateModel();
            $relIrField = $relation->getIntermediateFields();
            $relIrReferencedField = $relation->getIntermediateReferencedFields();

            if (is_array($relIrField)) {
                throw new \RuntimeException('Relation Intermediate field must be a string, multiple fields are not supported yet.');
            }
            if (is_array($relIrReferencedField)) {
                throw new \RuntimeException('Relation Intermediate Referenced field must be a string, multiple fields are not supported yet.');
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
                    $bindValues[$row->readAttribute($relIrReferencedField)] = true;
                    $modelReferencedModelValues[$row->readAttribute($relIrField)][$row->readAttribute($relIrReferencedField)] = true;
                }
                unset($relIrValues);
                unset($row);

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
                $records[$record->readAttribute($relReferencedField)] = $record;
            }
            unset($record);

            foreach ($parentSubject as $record) {
                assert($record instanceof EntityInterface);
                $referencedFieldValue = $record->readAttribute($relField);

                if (isset($modelReferencedModelValues[$referencedFieldValue])) {
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
            $isSingle = !$isThrough && (
                $relation->getType() === Relation::HAS_ONE ||
                $relation->getType() === Relation::BELONGS_TO
            );

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

                    if ($isSingle) {
                        $indexedRecords[$record->readAttribute($relReferencedField)] = $record;
                    }
                    else {
                        $indexedRecords[$record->readAttribute($relReferencedField)][] = $record;
                    }
                }

                foreach ($parentSubject as $record) {
                    assert($record instanceof EntityInterface);
                    $referencedFieldValue = $record->readAttribute($relField);

                    if (isset($indexedRecords[$referencedFieldValue])) {
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
