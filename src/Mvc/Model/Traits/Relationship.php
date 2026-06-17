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

namespace PhalconKit\Mvc\Model\Traits;

use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\Column;
use Phalcon\Messages\Message;
use Phalcon\Mvc\EntityInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Support\Collection\CollectionInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Exception\InvalidArgumentException;
use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model\Interfaces\RelationshipInterface;
use PhalconKit\Mvc\Model\Interfaces\SoftDeleteInterface;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEntity;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractMetaData;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractModelsManager;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractOptions;

/**
 * Adds relationship-aware assignment, persistence, and export helpers.
 *
 * PhalconKit models call `assignRelated()` before native model assignment so
 * request payloads can contain nested relationship data. The default behavior
 * remains permissive for backward compatibility: unknown relation-looking
 * payloads and non-whitelisted aliases are ignored so scalar model assignment
 * can continue through Phalcon. Applications that validate request payloads
 * more tightly can enable strict relationship assignment per model instance.
 */
trait Relationship
{
    use AbstractEntity;
    use AbstractInjectable;
    use AbstractMetaData;
    use AbstractModelsManager;
    use AbstractOptions;
    
    abstract public function appendMessage(\Phalcon\Messages\MessageInterface $message): ModelInterface;
    
    private array $keepMissingRelated = [];

    /**
     * @var array<string, mixed>
     */
    private const array DEFAULT_RELATIONSHIP_OPTIONS = [
        'enforceDirectOwnership' => false,
        'allowUnownedDirectRelationAdoption' => true,
        'autoRestoreDirectRelations' => false,
    ];

    /**
     * Whether relation-specific assignment mistakes should throw exceptions.
     *
     * This flag does not make normal scalar model assignment strict. It only
     * affects payloads that are clearly intended for relationship handling,
     * such as known relation aliases, complex nested values, and relation list
     * items.
     */
    private bool $strictRelatedAssignment = false;
    
    private string $relationshipContext = '';
    
    /**
     * @var ModelInterface[]
     */
    protected $dirtyRelated = [];
    
    /**
     * Eager-loaded relationship values that should be readable/exportable
     * without being treated as pending related records to save.
     *
     * @var array<string, mixed>
     */
    protected array $loadedRelated = [];

    /**
     * Enable or disable strict validation for relationship payloads.
     *
     * Leave this disabled for legacy forms that may send extra nested data.
     * Enable it in API/resource layers where relation aliases are controlled by
     * explicit save-field policies and a malformed relation should fail loudly.
     */
    public function setStrictRelatedAssignment(bool $strictRelatedAssignment): void
    {
        $this->strictRelatedAssignment = $strictRelatedAssignment;
    }

    /**
     * Return whether malformed relationship payloads should throw exceptions.
     */
    public function isStrictRelatedAssignment(): bool
    {
        return $this->strictRelatedAssignment;
    }

    /**
     * Replace the configured relationship behavior options.
     *
     * The option group is intentionally stored in the shared model options
     * manager so applications can opt into stricter behavior per model without
     * changing generated relationship declarations.
     */
    public function setRelationshipOptions(array $options): void
    {
        $this->getOptionsManager()->set('relationship', $options);
    }

    /**
     * Return relationship options, optionally including a per-alias override.
     */
    public function getRelationshipOptions(?string $alias = null): array
    {
        $configured = $this->getConfiguredRelationshipOptions();
        $instance = $this->getOptionsManager()->get('relationship') ?? [];
        if (!is_array($instance)) {
            $instance = [];
        }

        $options = array_replace(
            self::DEFAULT_RELATIONSHIP_OPTIONS,
            array_intersect_key($configured, self::DEFAULT_RELATIONSHIP_OPTIONS),
            array_intersect_key($instance, self::DEFAULT_RELATIONSHIP_OPTIONS)
        );

        if ($alias === null) {
            return $options;
        }

        $aliasOptions = array_replace(
            $this->getRelationshipAliasOptions($configured, $alias),
            $this->getRelationshipAliasOptions($instance, $alias)
        );

        return array_replace(
            $options,
            array_intersect_key($aliasOptions, $options)
        );
    }

    /**
     * Read relationship defaults from bootstrap config when available.
     *
     * The config path is intentionally feature-specific (`model.relationship`)
     * rather than part of the generic model options manager.
     *
     * @return array<string, mixed>
     */
    private function getConfiguredRelationshipOptions(): array
    {
        try {
            $config = $this->getDI()->get('config');
        }
        catch (\Throwable) {
            return [];
        }

        if (!$config instanceof ConfigInterface) {
            return [];
        }

        return $config->pathToArray('model.relationship', []) ?? [];
    }

    /**
     * Return one configured relationship behavior option.
     */
    public function getRelationshipOption(string $option, ?string $alias = null, mixed $default = null): mixed
    {
        return $this->getRelationshipOptions($alias)[$option] ?? $default;
    }

    private function getRelationshipAliasOptions(array $configured, string $alias): array
    {
        $aliases = $configured['aliases'] ?? [];
        if (!is_array($aliases)) {
            return [];
        }

        $normalizedAlias = $this->normalizeRelationAlias($alias);
        foreach ($aliases as $configuredAlias => $options) {
            if (
                is_string($configuredAlias)
                && $this->normalizeRelationAlias($configuredAlias) === $normalizedAlias
                && is_array($options)
            ) {
                return $options;
            }
        }

        return [];
    }

    /**
     * Set the missing related configuration list
     */
    public function setKeepMissingRelated(array $keepMissingRelated): void
    {
        $this->keepMissingRelated = $this->normalizeRelationAliases($keepMissingRelated);
    }
    
    /**
     * Return the missing related configuration list
     *
     * @return array<string, bool>
     */
    public function getKeepMissingRelated(): array
    {
        return $this->keepMissingRelated;
    }
    
    /**
     * Return the keepMissing configuration for a specific relationship alias
     */
    public function getKeepMissingRelatedAlias(string $alias): bool
    {
        return (bool)($this->keepMissingRelated[$this->normalizeRelationAlias($alias)] ?? true);
    }
    
    /**
     * Set the keepMissing configuration for a specific relationship alias
     */
    public function setKeepMissingRelatedAlias(string $alias, bool $keepMissing): void
    {
        $this->keepMissingRelated[$this->normalizeRelationAlias($alias)] = $keepMissing;
    }
    
    /**
     * Get the current relationship context
     */
    public function getRelationshipContext(): string
    {
        return $this->relationshipContext;
    }
    
    /**
     * Set the current relationship context
     */
    public function setRelationshipContext(string $context): void
    {
        $this->relationshipContext = $context;
    }
    
    /**
     * Return the dirtyRelated entities
     *
     * @return array<string, mixed>
     */
    public function getDirtyRelated(): array
    {
        return $this->dirtyRelated;
    }
    
    /**
     * Set the dirtyRelated entities
     */
    public function setDirtyRelated(array $dirtyRelated): void
    {
        $this->dirtyRelated = $this->normalizeRelationAliases($dirtyRelated);
    }
    
    /**
     * Return the dirtyRelated entities
     */
    public function getDirtyRelatedAlias(string $alias): mixed
    {
        return $this->dirtyRelated[$this->normalizeRelationAlias($alias)];
    }
    
    /**
     * Return the dirtyRelated entities
     */
    public function setDirtyRelatedAlias(string $alias, mixed $value): void
    {
        $alias = $this->normalizeRelationAlias($alias);
        $this->dirtyRelated[$alias] = $value;
        $this->writeDeclaredRelatedAlias($alias, $value);
    }
    
    /**
     * Check whether the current entity has dirty related or not
     */
    public function hasDirtyRelated(): bool
    {
        return (bool)count($this->dirtyRelated);
    }
    
    /**
     * Check whether the current entity has dirty related or not
     */
    public function hasDirtyRelatedAlias(string $alias): bool
    {
        return array_key_exists($this->normalizeRelationAlias($alias), $this->dirtyRelated);
    }

    /**
     * Return the eager-loaded related entities
     *
     * @return array<string, mixed>
     */
    public function getLoadedRelated(): array
    {
        return $this->loadedRelated;
    }

    /**
     * Set the eager-loaded related entities
     */
    public function setLoadedRelated(array $loadedRelated): void
    {
        $this->loadedRelated = $this->normalizeRelationAliases($loadedRelated);
    }

    /**
     * Return eager-loaded related entities for one alias
     */
    public function getLoadedRelatedAlias(string $alias): mixed
    {
        return $this->loadedRelated[$this->normalizeRelationAlias($alias)] ?? null;
    }

    /**
     * Set eager-loaded related entities for one alias
     */
    public function setLoadedRelatedAlias(string $alias, mixed $value): void
    {
        $alias = $this->normalizeRelationAlias($alias);
        $this->loadedRelated[$alias] = $value;
        $this->writeDeclaredRelatedAlias($alias, $value);
    }

    /**
     * Check whether an eager-loaded relation alias exists
     */
    public function hasLoadedRelatedAlias(string $alias): bool
    {
        return array_key_exists($this->normalizeRelationAlias($alias), $this->loadedRelated);
    }

    private function normalizeRelationAlias(string $alias): string
    {
        return mb_strtolower($alias);
    }

    private function normalizeRelationAliases(array $related): array
    {
        $normalized = [];
        foreach ($related as $alias => $value) {
            $normalized[is_string($alias) ? $this->normalizeRelationAlias($alias) : $alias] = $value;
        }

        return $normalized;
    }

    private function writeDeclaredRelatedAlias(string $alias, mixed $value): void
    {
        if (!property_exists($this, $alias)) {
            return;
        }

        $property = new \ReflectionProperty($this, $alias);
        if (!$property->isStatic()) {
            $property->setValue($this, $value);
        }
    }
    
    /**
     * Assigns values to the model from an array, with options to control which fields are assigned.
     * Handles related records using `assignRelated` method and passes remaining values to the parent's assign method.
     *
     * @param array $data The array of data to assign to the model.
     * @param array|null $whiteList An optional array specifying which fields in the model can be assigned.
     * @param array|null $dataColumnMap An optional column map to transform external keys into internal model field names.
     *
     * @return ModelInterface Returns the updated ModelInterface instance.
     * @throws InvalidArgumentException
     */
    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        $this->assignRelated($data, $whiteList, $dataColumnMap);
        return parent::assign($data, $whiteList, $dataColumnMap);
    }
    
    /**
     * Assign related
     *
     * Single
     * [alias => new Alias()] // create new alias
     *
     * Many
     * [alias => [new Alias()]] // create new alias
     * [alias => [1, 2, 3, 4]] // append / merge 1, 2, 3, 4
     * [alias => [false, 1, 2, 4]]; // delete 3
     *
     * @param array $data
     * @param array|null $whiteList
     * @param array|null $dataColumnMap
     *
     * @return ModelInterface
     * @throws InvalidArgumentException
     */
    public function assignRelated(array $data, ?array $whiteList = null, ?array $dataColumnMap = null): ModelInterface
    {
        assert($this instanceof Model);
        
        // no data, nothing to do
        if (empty($data)) {
            return $this;
        }
        
        // Get the current model class name
        $modelClass = get_class($this);
        
        $modelsManager = $this->getModelsManager();
        
        foreach ($data as $alias => $relationData) {
            if (!is_string($alias)) {
                throw new LogicException('Invalid relation alias `' . $alias . '` on model `' . $modelClass . '`', 400);
            }

            $relation = $modelsManager->getRelationByAlias($modelClass, $alias);

            // Alias is not whitelisted. Keep permissive legacy behavior unless
            // strict relation assignment explicitly opts into exceptions.
            if (!is_null($whiteList) && !$this->isRelatedAssignmentWhiteListed($alias, $whiteList)) {
                if ($relation && $this->isStrictRelatedAssignment()) {
                    throw new InvalidArgumentException(
                        'Relationship alias `' . $alias . '` on model `' . $modelClass .
                        '` is not allowed by the current assignment whitelist.',
                        400
                    );
                }

                continue;
            }

            // Unknown relationship aliases are skipped for backward
            // compatibility. In strict mode, only complex payloads that are
            // not known model columns are treated as relation mistakes because
            // scalar keys still need to pass through native model assignment.
            if (!$relation) {
                if (
                    $this->isStrictRelatedAssignment()
                    && $this->isRelationPayload($relationData)
                    && !$this->isModelAssignmentField($alias, $dataColumnMap)
                ) {
                    throw new InvalidArgumentException(
                        'Unknown relationship alias `' . $alias . '` on model `' . $modelClass .
                        '` while strict relationship assignment is enabled.',
                        400
                    );
                }

                continue;
            }

            $type = $relation->getType();

            $fields = $relation->getFields();
            $fields = is_array($fields) ? $fields : [$fields];

            $referencedFields = $relation->getReferencedFields();
            $referencedFields = is_array($referencedFields) ? $referencedFields : [$referencedFields];

            $referencedModel = $relation->getReferencedModel();
            $assign = null;

            if (is_int($relationData) || is_string($relationData)) {
                $relationData = [$referencedFields[0] => $relationData];
            }

            if ($relationData instanceof ModelInterface) {
                if ($relationData instanceof $referencedModel) {
                    $assign = $relationData;
                }
                else {
                    throw new InvalidArgumentException('Instance of `' . get_class($relationData) . '` received on model `' . $modelClass . '` in alias `' . $alias . ', expected instance of `' . $referencedModel . '`', 400);
                }
            }

            // array | traversable | resultset
            elseif (is_array($relationData) || $relationData instanceof \Traversable) {
                $assign = [];

                $getEntityParams = [
                    'alias' => $alias,
                    'fields' => $referencedFields,
                    'modelClass' => $referencedModel,
                    'readFields' => $fields,
                    'type' => $type,
                    'whiteList' => $whiteList,
                    'dataColumnMap' => $dataColumnMap,
                ];

                if (empty($relationData) && !in_array($type, [Relation::HAS_MANY_THROUGH, Relation::HAS_MANY])) {
                    $assign = $this->getEntityFromData($relationData, $getEntityParams);
                }
                else {
                    foreach ($relationData as $traversedKey => $traversedData) {
                        // Array of things
                        if (is_int($traversedKey)) {
                            $entity = null;

                            // Legacy payloads use boolean sentinels to choose
                            // whether missing related records are kept.
                            // if [alias => [true, ...]
                            if ($traversedData === 'false') {
                                $traversedData = false;
                            }
                            if ($traversedData === 'true') {
                                $traversedData = true;
                            }

                            if (is_bool($traversedData)) {
                                $this->setKeepMissingRelatedAlias($alias, $traversedData);
                                continue;
                            }

                            // if [alias => [1, 2, 3, ...]]
                            if (is_int($traversedData) || is_string($traversedData)) {
                                $traversedData = [$referencedFields[0] => $traversedData];
                            }

                            // if [alias => AliasModel]
                            if ($traversedData instanceof ModelInterface) {
                                if ($traversedData instanceof $referencedModel) {
                                    $entity = $traversedData;
                                }
                                else {
                                    throw new InvalidArgumentException('Instance of `' . get_class($traversedData) . '` received on model `' . $modelClass . '` in alias `' . $alias . ', expected instance of `' . $referencedModel . '`', 400);
                                }
                            }

                            // if [alias => [[id => 1], [id => 2], [id => 3], ....]]
                            elseif (is_array($traversedData) || $traversedData instanceof \Traversable) {
                                $entity = $this->getEntityFromData((array)$traversedData, $getEntityParams);
                            }
                            elseif ($this->isStrictRelatedAssignment()) {
                                throw new InvalidArgumentException(
                                    'Unsupported relationship payload item for alias `' . $alias .
                                    '` on model `' . $modelClass . '` at index `' . $traversedKey .
                                    '`. Expected model, array, traversable, integer, string, or boolean keep-missing sentinel; received `' .
                                    get_debug_type($traversedData) . '`.',
                                    400
                                );
                            }

                            if ($entity) {
                                $assign [] = $entity;
                            }
                        }

                        // if [alias => [id => 1]]
                        else {
                            $assign = $this->getEntityFromData((array)$relationData, $getEntityParams);
                            break;
                        }
                    }
                }
            }
            elseif ($this->isStrictRelatedAssignment()) {
                throw new InvalidArgumentException(
                    'Unsupported relationship payload for alias `' . $alias .
                    '` on model `' . $modelClass .
                    '`. Expected model, array, traversable, integer, or string; received `' .
                    get_debug_type($relationData) . '`.',
                    400
                );
            }

            // we got something to assign
            if (!empty($assign) || !$this->getKeepMissingRelatedAlias($alias)) {
                $this->{$alias} = $assign;

                // fix to force recursive parent save from children entities within _preSaveRelatedRecords method
                if ($this->{$alias} && $this->{$alias} instanceof ModelInterface) {
                    $this->{$alias}->setDirtyState(Model::DIRTY_STATE_TRANSIENT);
                }

                $this->dirtyRelated[$this->normalizeRelationAlias($alias)] = $this->{$alias} ?? false;
                if (empty($assign)) {
                    $this->dirtyRelated[$this->normalizeRelationAlias($alias)] = [];
                }
            }
        }
        
        return $this;
    }

    /**
     * Check whether a relation alias is allowed by a nested assignment whitelist.
     *
     * The whitelist can contain relation aliases as plain values or as keys that
     * point to nested allowed fields. This mirrors existing PhalconKit save-field
     * payloads without forcing callers to choose one representation.
     */
    private function isRelatedAssignmentWhiteListed(string $alias, array $whiteList): bool
    {
        return array_key_exists($alias, $whiteList) || in_array($alias, $whiteList, true);
    }

    /**
     * Determine whether an unknown key carries relationship-shaped data.
     *
     * Scalar unknown keys are left to native model assignment. Complex values
     * are the only safe candidates for strict relationship-alias validation
     * because they are how REST/save payloads express nested relations.
     */
    private function isRelationPayload(mixed $value): bool
    {
        return $value instanceof ModelInterface
            || $value instanceof \Traversable
            || is_array($value);
    }

    /**
     * Check whether a non-relation assignment key is a known model field.
     *
     * Strict relationship assignment must not reject JSON/array columns or
     * mapped model attributes just because their values look like nested
     * relation payloads. The optional data column map is checked first because
     * callers may use external request keys that Phalcon maps before writing.
     */
    private function isModelAssignmentField(string $field, ?array $dataColumnMap = null): bool
    {
        if ($dataColumnMap !== null) {
            if (array_key_exists($field, $dataColumnMap) || in_array($field, $dataColumnMap, true)) {
                return true;
            }
        }

        assert($this instanceof ModelInterface);
        $metaData = $this->getModelsMetaData();

        if (in_array($field, $metaData->getAttributes($this), true)) {
            return true;
        }

        $columnMap = $metaData->getColumnMap($this) ?? [];
        if (array_key_exists($field, $columnMap) || in_array($field, $columnMap, true)) {
            return true;
        }

        $reverseColumnMap = $metaData->getReverseColumnMap($this) ?? [];
        return array_key_exists($field, $reverseColumnMap) || in_array($field, $reverseColumnMap, true);
    }

    private function isDirectOwnedRelationType(?int $type): bool
    {
        return in_array($type, [Relation::HAS_ONE, Relation::HAS_MANY], true);
    }

    private function assertDirectRelatedRecordCanBeAssigned(
        ?string $alias,
        ?int $type,
        array $relationFields,
        array $referencedFields,
        EntityInterface $record
    ): void {
        if (
            $alias === null
            || !$this->isDirectOwnedRelationType($type)
            || !$this->getRelationshipOption('enforceDirectOwnership', $alias, false)
        ) {
            return;
        }

        $ownershipState = $this->getDirectRelatedOwnershipState($relationFields, $referencedFields, $record);
        if ($ownershipState === 'owned') {
            return;
        }

        if (
            $ownershipState === 'unowned'
            && $this->getRelationshipOption('allowUnownedDirectRelationAdoption', $alias, true)
        ) {
            return;
        }

        $reason = $ownershipState === 'unowned'
            ? 'is not attached to the current model'
            : 'belongs to another model';

        throw new InvalidArgumentException(
            'Related record `' . get_class($record) . '` for alias `' . $alias . '` ' . $reason .
            ' and cannot be assigned through a direct relationship.',
            400
        );
    }

    private function getDirectRelatedOwnershipState(
        array $relationFields,
        array $referencedFields,
        EntityInterface $record
    ): string {
        $hasReferencedValue = false;

        foreach ($relationFields as $key => $relationField) {
            if (!array_key_exists($key, $referencedFields)) {
                return 'foreign';
            }

            $referencedValue = $record->readAttribute($referencedFields[$key]);
            if ($this->isEmptyRelationValue($referencedValue)) {
                continue;
            }

            $hasReferencedValue = true;
            if (!$this->isSameRelationValue($this->readAttribute($relationField), $referencedValue)) {
                return 'foreign';
            }
        }

        return $hasReferencedValue ? 'owned' : 'unowned';
    }

    private function isEmptyRelationValue(mixed $value): bool
    {
        return $value === null || $value === '';
    }

    private function isSameRelationValue(mixed $expected, mixed $actual): bool
    {
        if ($expected === $actual) {
            return true;
        }

        if ($expected === null || $actual === null) {
            return false;
        }

        return (string)$expected === (string)$actual;
    }

    private function prepareDirectRelatedRecordForSave(
        RelationInterface $relation,
        EntityInterface $record,
        ?string $alias,
        ?int $index = null
    ): bool {
        if (!$this->isDirectOwnedRelationType($relation->getType())) {
            return true;
        }

        $relationFields = $relation->getFields();
        $relationFields = is_array($relationFields) ? $relationFields : [$relationFields];

        $referencedFields = $relation->getReferencedFields();
        $referencedFields = is_array($referencedFields) ? $referencedFields : [$referencedFields];

        $this->assertDirectRelatedRecordCanBeAssigned(
            $alias,
            $relation->getType(),
            $relationFields,
            $referencedFields,
            $record
        );

        if (!$this->getRelationshipOption('autoRestoreDirectRelations', $alias, false)) {
            return true;
        }

        if (
            $this->getDirectRelatedOwnershipState($relationFields, $referencedFields, $record) !== 'owned'
            || !$record instanceof SoftDeleteInterface
            || !$record->isDeleted()
        ) {
            return true;
        }

        if ($record->restore()) {
            return true;
        }

        assert($record instanceof ModelInterface);
        $messageAlias = $alias ?? '';
        $this->appendMessagesFromRecord($record, $messageAlias, $index);
        $this->appendMessage(new Message(
            'Unable to restore previously deleted related entity `' . get_class($record) . '`',
            $messageAlias,
            'Bad Request',
            400
        ));

        return false;
    }
    
    /**
     *  Saves related records that must be stored prior to save the master record
     *  Refactored based on the native cphalcon version, so we can support :
     *  - combined keys on relationship definition
     *  - relationship context within the model messages based on the alias definition
     *
     * @param AdapterInterface $connection
     * @param ModelInterface[] $related
     * @param CollectionInterface $visited
     * @return bool
     * @throws InvalidArgumentException
     */
    protected function preSaveRelatedRecords(AdapterInterface $connection, $related, CollectionInterface $visited): bool
    {
        $nesting = false;
        
        $connection->begin($nesting);
        $className = get_class($this);
        
        $modelsManager = $this->getModelsManager();
        
        foreach ($related as $alias => $record) {
            $relation = $modelsManager->getRelationByAlias($className, $alias);
            
            if ($relation) {
                $type = $relation->getType();
                
                // Only belongsTo are stored before save the master record
                if ($type === Relation::BELONGS_TO) {
                    // Belongs-to relation: We only support model interface
                    if (!($record instanceof ModelInterface)) {
                        $connection->rollback($nesting);
                        throw new InvalidArgumentException(
                            'Instance of `' . get_class($record) . '` received on model `' . $className . '` in alias `' . $alias .
                            ', expected instance of `' . ModelInterface::class . '` as part of the belongs-to relation',
                            400
                        );
                    }
                    
                    $relationFields = $relation->getFields();
                    $relationFields = is_array($relationFields) ? $relationFields : [$relationFields];
                    
                    $referencedFields = $relation->getReferencedFields();
                    $referencedFields = is_array($referencedFields) ? $referencedFields : [$referencedFields];
                    
                    // Set the relationship context
                    if ($record instanceof RelationshipInterface) {
                        $currentRelationshipContext = $this->getRelationshipContext();
                        $relationshipPrefix = !empty($currentRelationshipContext) ? $currentRelationshipContext . '.' : '';
                        $record->setRelationshipContext($relationshipPrefix . $alias);
                    }
                    
                    assert($record instanceof Model);
                    if (!$record->doSave($visited)) {
                        $this->appendMessagesFromRecord($record, $alias);
                        $connection->rollback($nesting);
                        return false;
                    }
                    
                    // assign referenced value to the current model
                    foreach ($referencedFields as $key => $referencedField) {
                        $this->{$relationFields[$key]} = $record->readAttribute($referencedField);
                    }
                }
            }
        }
        
        return true;
    }
    
    /**
     * Processes the saving of related records for the current model.
     * Performs operations based on relationship types such as HAS_MANY, HAS_ONE, HAS_MANY_THROUGH, etc.
     * Handles automatic deletion of missing related records and ensures correct binding and transaction management.
     *
     * NOTE: we need this, this behavior only happens:
     * - in many-to-many nodes
     * Fix uniqueness on combined keys in node entities, and possibly more...
     * @link https://forum.phalconphp.com/discussion/2190/many-to-many-expected-behaviour
     * @link http://stackoverflow.com/questions/23374858/update-a-records-n-n-relationships
     * @link https://github.com/phalcon/cphalcon/issues/2871
     *
     * @param AdapterInterface $connection Database connection instance used for transactions.
     * @param array|object[]|ModelInterface[] $related Related records to be saved, provided as arrays or objects.
     * @param CollectionInterface $visited A collection of already visited models to prevent recursion.
     * @return bool Returns true on successful processing of related records, false if an error occurs.
     * @throws InvalidArgumentException Throws an exception if there are no defined relations for a given alias or if invalid data types are provided.
     */
    protected function postSaveRelatedRecords(AdapterInterface $connection, $related, CollectionInterface $visited): bool
    {
        assert($this instanceof ModelInterface);
        $nesting = false;
        
        if ($related) {
            foreach ($related as $lowerCaseAlias => $assign) {
                $modelsManager = $this->getModelsManager();
                $relation = $modelsManager->getRelationByAlias(get_class($this), $lowerCaseAlias);
                
                if (!$relation) {
                    if (is_array($assign)) {
                        $connection->rollback($nesting);
                        throw new InvalidArgumentException("There are no defined relations for the model '" . get_class($this) . "' using alias '" . $lowerCaseAlias . "'");
                    }
                }
                assert($relation instanceof RelationInterface);
                
                /**
                 * Discard belongsTo relations
                 */
                if ($relation->getType() === Relation::BELONGS_TO) {
                    continue;
                }
                
                if (!is_array($assign) && !is_object($assign)) {
                    $connection->rollback($nesting);
                    throw new InvalidArgumentException('Only objects/arrays can be stored as part of has-many/has-one/has-one-through/has-many-to-many relations');
                }
                
                /**
                 * Custom logic for single-to-many relationships
                 */
                if ($relation->getType() === Relation::HAS_MANY) {
                    // auto-delete missing related if keepMissingRelated is false
                    if (!$this->getKeepMissingRelatedAlias($lowerCaseAlias)) {
                        $originFields = $relation->getFields();
                        $originFields = is_array($originFields) ? $originFields : [$originFields];
                        
                        $referencedFields = $relation->getReferencedFields();
                        $referencedFields = is_array($referencedFields) ? $referencedFields : [$referencedFields];
                        
                        $referencedModelClass = $relation->getReferencedModel();
                        $referencedModel = $modelsManager->load($referencedModelClass);
                        
                        $referencedPrimaryKeyAttributes = $referencedModel->getModelsMetaData()->getPrimaryKeyAttributes($referencedModel);
                        $referencedBindTypes = $referencedModel->getModelsMetaData()->getBindTypes($referencedModel);
                        
                        $originBind = [];
                        foreach ($originFields as $originField) {
                            $originBind [] = $this->readAttribute($originField);
                        }
                    
                        $idBindType = count($referencedPrimaryKeyAttributes) === 1 ? $referencedBindTypes[$referencedPrimaryKeyAttributes[0]] : Column::BIND_PARAM_STR;
                        
                        $idListToKeep = [0];
                        foreach ($assign as $entity) {
                            $buildPrimaryKey = [];
                            foreach ($referencedPrimaryKeyAttributes as $referencedPrimaryKey => $referencedPrimaryKeyAttribute) {
                                $buildPrimaryKey [] = $entity->readAttribute($referencedPrimaryKeyAttribute);
                            }
                            $idListToKeep [] = implode('.', $buildPrimaryKey);
                        }
                        
                        // fetch missing related entities
                        $referencedEntityToDeleteResultset = $referencedModel::find([
                            'conditions' => implode_sprintf(array_merge($referencedFields), ' and ', '[' . $referencedModelClass . '].[%s] = ?%s') .
                            ' and concat(' . implode_sprintf($referencedPrimaryKeyAttributes, ', \'.\', ', '[' . $referencedModelClass . '].[%s]') . ') not in ({id:array})',
                            'bind' => [...$originBind, 'id' => $idListToKeep],
                            'bindTypes' => [...array_fill(0, count($referencedFields), Column::BIND_PARAM_STR), 'id' => $idBindType],
                        ]);
                        
                        // delete missing related entities
                        if (!$referencedEntityToDeleteResultset->delete()) {
                            $this->appendMessagesFromResultset($referencedEntityToDeleteResultset, $lowerCaseAlias);
                            $this->appendMessage(new Message('Unable to delete node entity `' . $referencedModelClass . '`', $lowerCaseAlias, 'Bad Request', 400));
                            $connection->rollback($nesting);
                            return false;
                        }
                    }
                }
                
                /**
                 * Custom logic for many-to-many relationships
                 */
                elseif ($relation->getType() === Relation::HAS_MANY_THROUGH) {
                    $originFields = $relation->getFields();
                    $originFields = is_array($originFields) ? $originFields : [$originFields];
                    
                    $intermediateModelClass = $relation->getIntermediateModel();
                    $intermediateModel = $modelsManager->load($intermediateModelClass);
                    
                    $intermediateFields = $relation->getIntermediateFields();
                    $intermediateFields = is_array($intermediateFields) ? $intermediateFields : [$intermediateFields];
                    
                    $intermediateReferencedFields = $relation->getIntermediateReferencedFields();
                    $intermediateReferencedFields = is_array($intermediateReferencedFields) ? $intermediateReferencedFields : [$intermediateReferencedFields];
                    
                    $referencedFields = $relation->getReferencedFields();
                    $referencedFields = is_array($referencedFields) ? $referencedFields : [$referencedFields];
                    
                    $intermediatePrimaryKeyAttributes = $intermediateModel->getModelsMetaData()->getPrimaryKeyAttributes($intermediateModel);
                    $intermediateBindTypes = $intermediateModel->getModelsMetaData()->getBindTypes($intermediateModel);
                    
                    // get current model bindings
                    $originBind = [];
                    foreach ($originFields as $originField) {
                        $originBind [] = $this->readAttribute($originField);
                    }
                    
                    $nodeIdListToKeep = [];
                    foreach ($assign as $key => $entity) {
                        // get referenced model bindings
                        $referencedBind = [];
                        foreach ($referencedFields as $referencedField) {
                            assert($entity instanceof EntityInterface);
                            $referencedBind [] = $entity->readAttribute($referencedField);
                        }
                        
                        $nodeEntity = $intermediateModel::findFirst([
                            'conditions' => implode_sprintf(array_merge($intermediateFields, $intermediateReferencedFields), ' and ', '[' . $intermediateModelClass . '].[%s] = ?%s'),
                            'bind' => [...$originBind, ...$referencedBind],
                            'bindTypes' => array_fill(0, count($intermediateFields) + count($intermediateReferencedFields), Column::BIND_PARAM_STR),
                        ]);
                        
                        if ($nodeEntity) {
                            $buildPrimaryKey = [];
                            foreach ($intermediatePrimaryKeyAttributes as $intermediatePrimaryKey => $intermediatePrimaryKeyAttribute) {
                                $buildPrimaryKey [] = $nodeEntity->readAttribute($intermediatePrimaryKeyAttribute);
                            }
                            $nodeIdListToKeep [] = implode('.', $buildPrimaryKey);
                            
                            // Restoring node entities if previously soft deleted
                            if ($nodeEntity instanceof SoftDeleteInterface && $nodeEntity->isDeleted() && !$nodeEntity->restore()) {
                                assert($nodeEntity instanceof ModelInterface);
                                $this->appendMessagesFromRecord($nodeEntity, $lowerCaseAlias, $key);
                                $this->appendMessage(new Message('Unable to restored previously deleted related node `' . $intermediateModelClass . '`', $lowerCaseAlias, 'Bad Request', 400));
                                $connection->rollback($nesting);
                                return false;
                            }
                            
                            // save edge record
                            assert($entity instanceof Model);
                            if (!$entity->doSave($visited)) {
                                $this->appendMessagesFromRecord($entity, $lowerCaseAlias, $key);
                                $this->appendMessage(new Message('Unable to save related entity `' . $intermediateModelClass . '`', $lowerCaseAlias, 'Bad Request', 400));
                                $connection->rollback($nesting);
                                return false;
                            }
                            
                            // remove it
                            unset($related[$lowerCaseAlias][$key]);
                            
                            // Keep the assignment array in sync after the edge
                            // record has been persisted.
                            if (is_array($assign)) {
                                unset($assign[$key]);
                            }
                        }
                    }
                    
                    if (!$this->getKeepMissingRelatedAlias($lowerCaseAlias)) {
                        $idBindType = count($intermediatePrimaryKeyAttributes) === 1 ? $intermediateBindTypes[$intermediatePrimaryKeyAttributes[0]] : Column::BIND_PARAM_STR;
                        $nodeIdListToKeep = empty($nodeIdListToKeep) ? [0] : array_keys(array_flip($nodeIdListToKeep));
                        $nodeEntityToDeleteResultset = $intermediateModel::find([
                            'conditions' => implode_sprintf(array_merge($intermediateFields), ' and ', '[' . $intermediateModelClass . '].[%s] = ?%s')
                                . ' and concat(' . implode_sprintf($intermediatePrimaryKeyAttributes, ', \'.\', ', '[' . $intermediateModelClass . '].[%s]') . ') not in ({id:array})',
                            'bind' => [...$originBind, 'id' => $nodeIdListToKeep],
                            'bindTypes' => [...array_fill(0, count($intermediateFields), Column::BIND_PARAM_STR), 'id' => $idBindType],
                        ]);
                        
                        // delete missing related
                        if ($nodeEntityToDeleteResultset instanceof Model\Resultset && $nodeEntityToDeleteResultset->count() && !$nodeEntityToDeleteResultset->delete()) {
                            $this->appendMessagesFromResultset($nodeEntityToDeleteResultset, $lowerCaseAlias);
                            $this->appendMessage(new Message('Unable to delete node entity `' . $intermediateModelClass . '`', $lowerCaseAlias, 'Bad Request', 400));
                            $connection->rollback($nesting);
                            return false;
                        }
                    }
                }
                
                $relationFields = $relation->getFields();
                $relationFields = is_array($relationFields) ? $relationFields : [$relationFields];
                
                foreach ($relationFields as $relationField) {
                    if (!property_exists($this, $relationField)) {
                        $connection->rollback($nesting);
                        throw new InvalidArgumentException("The column '" . $relationField . "' needs to be present in the model");
                    }
                }
                
                $relatedRecords = is_array($assign) ? $assign : [$assign];
                
                if ($this->postSaveRelatedThroughAfter($relation, $relatedRecords, $visited) === false) {
                    $this->appendMessage(new Message('Unable to save related through after', $lowerCaseAlias, 'Bad Request', 400));
                    $connection->rollback($nesting);
                    return false;
                }
                
                if ($this->postSaveRelatedRecordsAfter($relation, $relatedRecords, $visited) === false) {
                    $this->appendMessage(new Message('Unable to save related records after', $lowerCaseAlias, 'Bad Request', 400));
                    $connection->rollback($nesting);
                    return false;
                }
            }
        }
        
        /**
         * Commit the implicit transaction
         */
        $connection->commit($nesting);
        return true;
    }
    
    /**
     * Handles the saving process of related records after the parent record's save operation.
     * It assigns referenced fields to the related records and ensures they are saved with proper relationships maintained.
     * If the relation is defined as `Through`, this method skips further processing.
     *
     * @param RelationInterface $relation The relation instance that provides information about the relationship.
     * @param array|object[]|ModelInterface[] $relatedRecords An array of related records to be saved.
     * @param CollectionInterface $visited A collection to track visited records to prevent infinite recursion.
     *
     * @return bool|null Returns `true` if all related records are saved successfully, `false` if an error occurs during saving,
     *                   and `null` if the relation is of type `Through`.
     *
     * @throws InvalidArgumentException If there is an error during the save operation for a related record.
     */
    public function postSaveRelatedRecordsAfter(RelationInterface $relation, array $relatedRecords, CollectionInterface $visited): ?bool
    {
        if ($relation->isThrough()) {
            return null;
        }
        
        $lowerCaseAlias = $relation->getOption('alias');
        
        $relationFields = $relation->getFields();
        $relationFields = is_array($relationFields) ? $relationFields : [$relationFields];
        
        $referencedFields = $relation->getReferencedFields();
        $referencedFields = is_array($referencedFields) ? $referencedFields : [$referencedFields];
        
        foreach ($relatedRecords as $recordAfter) {
            assert($recordAfter instanceof EntityInterface);
            assert($recordAfter instanceof Model);
            if (!$this->prepareDirectRelatedRecordForSave($relation, $recordAfter, $lowerCaseAlias)) {
                return false;
            }

            foreach ($relationFields as $key => $relationField) {
                $recordAfter->writeAttribute($referencedFields[$key], $this->readAttribute($relationField));
            }
            
            try {
                // Save the record and get messages
                if (!$recordAfter->doSave($visited)) {
                    $this->appendMessagesFromRecord($recordAfter, $lowerCaseAlias);
                    return false;
                }
            } catch (\Exception $e) {
                $this->appendMessages([
                    new Message($e->getMessage() . ' - ' . $e->getCode(), $lowerCaseAlias, 'Exception', (int)$e->getCode()),
                ]);
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Handles saving related records for through relationships after the primary records have been saved.
     * Primarily used to manage intermediate models and ensure proper linkage and saving of related records
     * in many-to-many or has-one-through relationships.
     *
     * @param RelationInterface $relation The relation object defining the association details.
     * @param array|object[]|ModelInterface[] $relatedRecords An array of related records to be processed and saved.
     * @param CollectionInterface $visited A collection of visited records to maintain state and prevent circular references.
     *
     * @return bool|null Returns true if all related records and intermediate records were successfully saved.
     *                   Returns false if any save operation failed.
     *                   Returns null if the relation is not a through relationship.
     *
     * @throws InvalidArgumentException If the intermediate model or related records cannot be properly saved.
     */
    public function postSaveRelatedThroughAfter(RelationInterface $relation, array $relatedRecords, CollectionInterface $visited): ?bool
    {
        assert($this instanceof RelationshipInterface);
        assert($this instanceof EntityInterface);
        assert($this instanceof ModelInterface);
        
        if (!$relation->isThrough()) {
            return null;
        }
        
        $modelsManager = $this->getModelsManager();
        $lowerCaseAlias = $relation->getOption('alias');
        
        $relationFields = $relation->getFields();
        $relationFields = is_array($relationFields) ? $relationFields : [$relationFields];
        
        $referencedFields = $relation->getReferencedFields();
        $referencedFields = is_array($referencedFields) ? $referencedFields : [$referencedFields];
        
        $intermediateModelClass = $relation->getIntermediateModel();
        
        $intermediateFields = $relation->getIntermediateFields();
        $intermediateFields = is_array($intermediateFields) ? $intermediateFields : [$intermediateFields];
        
        $intermediateReferencedFields = $relation->getIntermediateReferencedFields();
        $intermediateReferencedFields = is_array($intermediateReferencedFields) ? $intermediateReferencedFields : [$intermediateReferencedFields];
        
        foreach ($relatedRecords as $relatedAfterKey => $recordAfter) {
            assert($recordAfter instanceof Model);
            
            // Save the record and get messages
            if (!$recordAfter->doSave($visited)) {
                $this->appendMessagesFromRecord($recordAfter, $lowerCaseAlias, $relatedAfterKey);
                return false;
            }
            
            // Create a new instance of the intermediate model
            $intermediateModel = $modelsManager->load($intermediateModelClass);
            
            /**
             *  Has-one-through relations can only use one intermediate model.
             *  If it already exists, it can be updated with the new referenced key.
             */
            if ($relation->getType() === Relation::HAS_ONE_THROUGH) {
                $bind = [];
                foreach ($relationFields as $relationField) {
                    $bind[] = $this->readAttribute($relationField);
                }
                
                $existingIntermediateModel = $intermediateModel::findFirst([
                    'conditions' => implode_sprintf($intermediateFields, ' and ', '[' . $intermediateModelClass . '].[%s] = ?%s'),
                    'bind' => $bind,
                    'bindTypes' => array_fill(0, count($bind), Column::BIND_PARAM_STR),
                ]);
                
                if ($existingIntermediateModel) {
                    $intermediateModel = $existingIntermediateModel;
                }
            }
            
            // Set intermediate model columns values
            foreach ($relationFields as $relationFieldKey => $relationField) {
                $intermediateModel->writeAttribute($intermediateFields[$relationFieldKey], $this->readAttribute($relationField));
                $intermediateValue = $recordAfter->readAttribute($referencedFields[$relationFieldKey]);
                $intermediateModel->writeAttribute($intermediateReferencedFields[$relationFieldKey], $intermediateValue);
            }
            
            // Save the record and get messages
            if (!$intermediateModel->doSave($visited)) {
                $this->appendMessagesFromRecord($intermediateModel, $lowerCaseAlias);
                $this->appendMessage(new Message('Unable to save intermediate model `' . $intermediateModelClass . '`', $lowerCaseAlias, 'Bad Request', 400));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Find the first record by its primary key attributes.
     *
     * @param array $data The data containing the primary key values.
     * @param string|null $modelClass The class name of the model to search for. If not provided, the current model class will be used.
     * 
     * @return ModelInterface|Model\Row|null The found record entity.
     */
    public function findFirstByPrimaryKeys(array $data, ?string $modelClass): ModelInterface|Row|null
    {
        assert($this instanceof ModelInterface);
        
        $modelClass ??= self::class;
        
        $modelsManager = $this->getModelsManager();
        $modelsMetaData = $this->getModelsMetaData();
        
        $relatedModel = $modelsManager->load($modelClass);
        $relatedPrimaryKeys = $modelsMetaData->getPrimaryKeyAttributes($relatedModel);
        $relatedPrimaryValues = array_intersect_key($data, array_flip($relatedPrimaryKeys));
        
        if (count($relatedPrimaryKeys) === count($relatedPrimaryValues)) {
            return $relatedModel::findFirst([
                'conditions' => implode_sprintf($relatedPrimaryKeys, ' and ', '[' . $relatedModel::class . '].[%s] = ?%s'),
                'bind' => array_values($relatedPrimaryValues),
                'bindTypes' => array_fill(0, count($relatedPrimaryValues), Column::BIND_PARAM_STR),
            ]);
        }
        
        return null;
    }
    
    /**
     * Get the entity object from the given data.
     * It will try to find the existing record and then assign the new data.
     * - Will first try using the primary key of the related record
     * - Then will try using the defined relationship fields using the relationship alias
     *
     * @param array $data The data array.
     * @param array $configuration The configuration options.
     *                                - alias: The alias name.
     *                                - fields: The fields array.
     *                                - modelClass: The model class.
     *                                - readFields: The read fields array.
     *                                - type: The relationship type.
     *                                - whiteList: The whitelist array.
     *                                - dataColumnMap: The data column map array.
     *
     * @return ModelInterface|Model\Row|null The entity object or null if not found.
     */
    public function getEntityFromData(array $data, array $configuration = []): ModelInterface|Row|null
    {
        assert($this instanceof ModelInterface);
        assert($this instanceof EntityInterface);
        
        $alias = $configuration['alias'] ?? null;
        $fields = $configuration['fields'] ?? [];
        $modelClass = $configuration['modelClass'] ?? null;
        $readFields = $configuration['readFields'] ?? null;
        $type = $configuration['type'] ?? null;
        $whiteList = $configuration['whiteList'] ?? null;
        $dataColumnMap = $configuration['dataColumnMap'] ?? null;
        
        if (!is_array($fields)) {
            throw new InvalidArgumentException('Parameter `fields` must be an array');
        }
        
        if (!isset($modelClass)) {
            throw new InvalidArgumentException('Parameter `modelClass` is mandatory');
        }
        
        // using primary key first
        $entity = $this->findFirstByPrimaryKeys($data, $modelClass);
        if ($entity instanceof EntityInterface) {
            $this->assertDirectRelatedRecordCanBeAssigned(
                $alias,
                is_int($type) ? $type : null,
                is_array($readFields) ? $readFields : [],
                $fields,
                $entity
            );
        }
        
        // not found, using the relationship fields instead
        if (!$entity) {
            if ($type === Relation::HAS_ONE || $type === Relation::BELONGS_TO) {
                // Sparse single-relation payloads can omit the foreign key when
                // it is already available on the parent record.
                if (!empty($readFields)) {
                    foreach ($readFields as $key => $field) {
                        if (empty($data[$fields[$key]])) {
                            $value = $this->readAttribute($field);
                            if (!empty($value)) {
                                $data [$fields[$key]] = $value;
                            }
                        }
                    }
                }
            }
            
            // array_keys_exists (if $referencedFields keys exists)
            $dataKeys = array_intersect_key($data, array_flip($fields));
            
            // all keys were found
            if (count($dataKeys) === count($fields)) {
                if ($type === Relation::HAS_MANY) {
                    $modelsMetaData = $this->getModelsMetaData();
                    $primaryKeys = $modelsMetaData->getPrimaryKeyAttributes($this);
                    
                    // Force primary keys for single to many
                    foreach ($primaryKeys as $primaryKey) {
                        if (!in_array($primaryKey, $fields, true)) {
                            $dataKeys [$primaryKey] = $data[$primaryKey] ?? null;
                            $fields [] = $primaryKey;
                        }
                    }
                }
                
                $modelsManager = $this->getModelsManager();
                $relatedModel = $modelsManager->load($modelClass);
                
                $entity = $relatedModel::findFirst([
                    'conditions' => implode_sprintf($fields, ' and ', '[' . $relatedModel::class . '].[%s] = ?%s'),
                    'bind' => array_values($dataKeys),
                    'bindTypes' => array_fill(0, count($dataKeys), Column::BIND_PARAM_STR),
                ]);
            }
        }
        
        // not found, we will create a new related entity
        if (!$entity) {
            $entity = new $modelClass();
        }
        
        assert($entity instanceof ModelInterface);
        
        // assign new values
        // can be null to bypass, empty array for nothing or filled array
        $whiteListAlias = isset($whiteList, $alias) ? $whiteList[$alias] ?? [] : null;
        $dataColumnMapAlias = isset($dataColumnMap, $alias) ? $dataColumnMap[$alias] ?? [] : null;
        if ($entity instanceof RelationshipInterface) {
            $entity->setStrictRelatedAssignment($this->isStrictRelatedAssignment());
        }

        $entity->assign($data, $whiteListAlias, $dataColumnMapAlias);
        
        return $entity;
    }
    
    public function appendMessages(array $messages = [], ?string $context = null, ?int $index = null): void
    {
        assert($this instanceof ModelInterface);
        foreach ($messages as $message) {
            assert($message instanceof Message);
            
            $message->setMetaData([
                'index' => $this->rebuildMessageIndex($message, $index),
                'context' => $this->rebuildMessageContext($message, $context),
            ]);
            
            $this->appendMessage($message);
        }
    }
    
    /**
     * Appends messages from a record to the current messages container.
     *
     * @param ModelInterface|null $record The record from which to append the messages.
     * @param string|null $context The context in which the messages should be added. Defaults to null.
     * @param int|null $index The index at which the messages should be added. Defaults to 0.
     * 
     * @return void
     */
    public function appendMessagesFromRecord(?ModelInterface $record = null, ?string $context = null, ?int $index = null): void
    {
        if (isset($record)) {
            $this->appendMessages($record->getMessages(), $context, $index);
        }
    }
    
    /**
     * Append messages from a resultset to the current message container.
     *
     * @param ResultsetInterface|null $resultset The resultset containing the messages to be appended. If not provided, no messages will be appended.
     * @param string|null $context The context to assign to the appended messages. If not provided, the default context will be used.
     * @param int|null $index The index at which the messages should be inserted in the messages array. If not provided, the messages will be appended at the end.
     */
    public function appendMessagesFromResultset(?ResultsetInterface $resultset = null, ?string $context = null, ?int $index = null): void
    {
        if (isset($resultset)) {
            $this->appendMessages($resultset->getMessages(), $context, $index);
        }
    }
    
    /**
     * Appends messages from a record list to the current message container.
     *
     * @param iterable|null $recordList The list of records to append messages from.
     * @param string|null $context The context to associate with the messages.
     * @param int|null $index The index to use for the messages.
     * 
     * @return void
     */
    public function appendMessagesFromRecordList(?iterable $recordList = null, ?string $context = null, ?int $index = null): void
    {
        if (isset($recordList)) {
            $indexStr = $index !== null ? (string) $index : '';
            foreach ($recordList as $key => $record) {
                $this->appendMessagesFromRecord($record, $context . '[' . $indexStr . ']', $key);
            }
        }
    }
    
    /**
     * Rebuilds the message context.
     *
     * This method appends the given context to the previous context stored in the message metadata.
     * If there is no previous context, only the given context is returned.
     *
     * @param Message $message The message object whose context needs to be rebuilt.
     * @param string|null $context The context to be appended.
     *
     * @return string The rebuilt context
     */
    public function rebuildMessageContext(Message $message, ?string $context = null): string
    {
        $metaData = $message->getMetaData();
        $previousContext = $metaData['context'] ?? '';
        return $context . (empty($previousContext) ? '' : '.' . $previousContext);
    }
    
    /**
     * Rebuilds the message index.
     *
     * This method constructs the new message index based on the provided $index argument
     * and the previous index stored in the message's metadata. It returns the new index
     * as a string.
     *
     * @param Message $message The message object for which the index is being rebuilt.
     * @param int|null $index The new index to be assigned to the message. Can be null.
     * @return string The new index as a string
     */
    public function rebuildMessageIndex(Message $message, ?int $index = null): string
    {
        $metaData = $message->getMetaData();
        $previousIndex = $metaData['index'] ?? '';
        $indexStr = $index !== null ? (string) $index : '';
        return $indexStr . (empty($previousIndex) ? '' : '.' . $previousIndex);
    }
    
    /**
     * Retrieves the related records as an array.
     *
     * If $columns is provided, only the specified columns will be included in the array.
     * If $useGetter is set to true, it will use the getter methods of the related records.
     *
     * @param array|null $columns (optional) The columns to include in the array for each related record
     * @param bool $useGetter (optional) Whether to use getter methods of the related records (default: true)
     * 
     * @return array<string, mixed> The related records as an array
     */
    public function relatedToArray(?array $columns = null, bool $useGetter = true): array
    {
        $ret = [];
        
        assert($this instanceof ModelInterface);
        $columnMap = $this->getModelsMetaData()->getColumnMap($this);
        
        foreach (array_merge($this->getLoadedRelated(), $this->getDirtyRelated()) as $attribute => $related) {
            // Map column if defined
            if ($columnMap && isset($columnMap[$attribute])) {
                $attributeField = $columnMap[$attribute];
            }
            else {
                $attributeField = $attribute;
            }
            
            // Skip or set the related columns
            if ($columns) {
                if (!key_exists($attributeField, $columns) && !in_array($attributeField, $columns)) {
                    continue;
                }
            }
            $relatedColumns = $columns[$attributeField] ?? null;
            
            // Run toArray on related records
            if ($related instanceof ModelInterface && method_exists($related, 'toArray')) {
                $ret[$attributeField] = $related->toArray($relatedColumns, $useGetter);
            }
            elseif (is_iterable($related)) {
                $ret[$attributeField] = [];
                foreach ($related as $entity) {
                    if ($entity instanceof ModelInterface && method_exists($entity, 'toArray')) {
                        $ret[$attributeField][] = $entity->toArray($relatedColumns, $useGetter);
                    }
                    elseif (is_array($entity)) {
                        $ret[$attributeField][] = $entity;
                    }
                }
            }
            else {
                $ret[$attributeField] = null;
            }
        }
        
        return $ret;
    }
    
    /**
     * Overriding default phalcon getRelated in order to fix an important issue
     * where the related record is being stored into the "related" property and then
     * passed from the collectRelatedToSave and is mistakenly saved without the user consent
     *
     * @param string $alias
     * @param mixed $arguments
     * @return false|int|Model\Resultset\Simple
     * @throws InvalidArgumentException
     */
    public function getRelated(string $alias, $arguments = null)
    {
        $className = get_class($this);
        $manager = $this->getModelsManager();
        $lowerAlias = strtolower($alias);
        
        $relation = $manager->getRelationByAlias($className, $lowerAlias);
        if (!$relation) {
            throw new InvalidArgumentException(
                "There is no defined relations for the model '"
                . $className . "' using alias '" . $alias . "'"
            );
        }

        assert($relation instanceof RelationInterface);
        assert($this instanceof ModelInterface);
        
        return $manager->getRelationRecords($relation, $this, $arguments);
    }
    
    /**
     * Returns the instance as an array representation
     *
     * @param array $columns
     * @param bool $useGetter
     * @return array
     */
    public function toArray($columns = null, $useGetter = true): array
    {
        return array_merge(parent::toArray($columns, $useGetter), $this->relatedToArray($columns, $useGetter));
    }
}
