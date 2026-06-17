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

namespace PhalconKit\Mvc\Model\Interfaces;

use Phalcon\Messages\Message;
use Phalcon\Mvc\Model\RelationInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Support\Collection\CollectionInterface;

/**
 * Defines PhalconKit's relationship assignment and export contract.
 *
 * Models use this interface to distinguish two kinds of related data:
 * dirty relations that should be saved with the model, and loaded relations
 * that were attached for read/export purposes by eager loading. Implementors
 * may also opt into strict relationship assignment to convert malformed
 * relation payloads into framework exceptions instead of silently ignoring
 * them for legacy compatibility.
 */
interface RelationshipInterface
{
    /**
     * Enable or disable strict validation for relationship payloads.
     *
     * Strict mode is intentionally opt-in because `assignRelated()` receives the
     * full model assignment payload, including scalar model columns. When
     * enabled, relation-specific mistakes such as non-whitelisted relation
     * aliases, unknown complex relation payloads, and unsupported payload types
     * throw PhalconKit exceptions while normal scalar field assignment remains
     * delegated to Phalcon.
     */
    public function setStrictRelatedAssignment(bool $strictRelatedAssignment): void;

    /**
     * Check whether strict relationship assignment is enabled.
     *
     * @return bool True when relation payload mistakes should throw exceptions
     *     instead of using the legacy skip/ignore behavior.
     */
    public function isStrictRelatedAssignment(): bool;

    /**
     * Replace the relationship behavior option group.
     *
     * Supported options include:
     *  - `enforceDirectOwnership`: reject `HAS_ONE`/`HAS_MANY` records that
     *    already belong to another parent before the relation save rewrites
     *    their foreign key.
     *  - `allowUnownedDirectRelationAdoption`: when ownership enforcement is
     *    enabled, allow direct child records with empty relationship keys to be
     *    attached to the current parent.
     *  - `autoRestoreDirectRelations`: restore owned soft-deleted
     *    `HAS_ONE`/`HAS_MANY` children during relationship save.
     *
     * Callers may also provide an optional `aliases` array whose keys are
     * relationship aliases and whose values override these same options for
     * that alias only.
     */
    public function setRelationshipOptions(array $options): void;

    /**
     * Return merged relationship options.
     *
     * Passing an alias applies any configured per-alias override on top of the
     * global relationship options. The returned array contains only behavior
     * option keys, not the raw override map.
     */
    public function getRelationshipOptions(?string $alias = null): array;

    /**
     * Return one merged relationship option.
     *
     * @param string $option Relationship option key.
     * @param string|null $alias Optional relationship alias for per-alias
     *     overrides.
     * @param mixed $default Value returned when the option is unknown.
     *
     * @return mixed
     */
    public function getRelationshipOption(string $option, ?string $alias = null, mixed $default = null): mixed;

    /**
     * Replace keep-missing behavior for related aliases.
     *
     * Aliases are normalized case-insensitively. A false value means a
     * submitted authoritative relationship list should delete missing existing
     * children or intermediate nodes during save.
     *
     * @param array<string, bool> $keepMissingRelated Keep-missing flags keyed
     *     by relationship alias.
     */
    public function setKeepMissingRelated(array $keepMissingRelated): void;

    /**
     * Return keep-missing behavior keyed by normalized relationship alias.
     *
     * @return array<string, bool>
     */
    public function getKeepMissingRelated(): array;

    /**
     * Return whether missing records should be kept for one alias.
     *
     * Unknown aliases default to true for legacy append/merge behavior.
     */
    public function getKeepMissingRelatedAlias(string $alias): bool;

    /**
     * Set keep-missing behavior for one relationship alias.
     */
    public function setKeepMissingRelatedAlias(string $alias, bool $keepMissing): void;

    /**
     * Return the current nested relationship context used for messages.
     */
    public function getRelationshipContext(): string;

    /**
     * Set the current nested relationship context used for messages.
     */
    public function setRelationshipContext(string $context): void;

    /**
     * Return relationships assigned for persistence with the model.
     *
     * @return array<string, mixed>
     */
    public function getDirtyRelated(): array;

    /**
     * Replace relationships assigned for persistence with the model.
     *
     * Aliases are normalized case-insensitively.
     *
     * @param array<string, mixed> $dirtyRelated Dirty related values keyed by
     *     relationship alias.
     */
    public function setDirtyRelated(array $dirtyRelated): void;

    /**
     * Return one dirty relationship value by alias.
     */
    public function getDirtyRelatedAlias(string $alias): mixed;

    /**
     * Store one dirty relationship value by alias.
     *
     * Implementations also mirror the value to a declared relation property
     * when the model defines one.
     */
    public function setDirtyRelatedAlias(string $alias, mixed $value): void;

    /**
     * Return whether any dirty relationship is pending save.
     */
    public function hasDirtyRelated(): bool;

    /**
     * Return whether a dirty relationship exists for one alias.
     */
    public function hasDirtyRelatedAlias(string $alias): bool;

    /**
     * Return eager-loaded relationship values attached for read/export only.
     *
     * @return array<string, mixed>
     */
    public function getLoadedRelated(): array;

    /**
     * Replace eager-loaded relationship values attached for read/export only.
     *
     * @param array<string, mixed> $loadedRelated Loaded related values keyed by
     *     relationship alias.
     */
    public function setLoadedRelated(array $loadedRelated): void;

    /**
     * Return one eager-loaded relationship value by alias.
     */
    public function getLoadedRelatedAlias(string $alias): mixed;

    /**
     * Store one eager-loaded relationship value by alias.
     *
     * Implementations also mirror the value to a declared relation property
     * when the model defines one.
     */
    public function setLoadedRelatedAlias(string $alias, mixed $value): void;

    /**
     * Return whether an eager-loaded relationship exists for one alias.
     */
    public function hasLoadedRelatedAlias(string $alias): bool;

    /**
     * Assign nested relationship payloads and leave scalar fields to Phalcon.
     *
     * Known relation aliases can receive model instances, arrays, traversables,
     * scalar primary-key values, or list payloads. `$whiteList` and
     * `$dataColumnMap` follow Phalcon assignment conventions and can include
     * nested relation-specific field policies.
     *
     * @param array<string, mixed> $data Assignment payload.
     * @param array|null $whiteList Optional scalar/relation whitelist.
     * @param array|null $dataColumnMap Optional external-to-model field map.
     *
     * @return ModelInterface The assigned model instance.
     */
    public function assignRelated(array $data, ?array $whiteList = null, ?array $dataColumnMap = null): ModelInterface;

    /**
     * Save non-through related records after the parent model is saved.
     *
     * Implementations copy parent relationship keys into each child and then
     * save each child through Phalcon's visited graph.
     *
     * @param RelationInterface $relation Relation metadata being saved.
     * @param array<int, ModelInterface> $relatedRecords Related records to save.
     * @param CollectionInterface $visited Phalcon visited graph for recursive
     *     saves.
     *
     * @return bool|null False on failure, true on success, or null when the
     *     relation is a through relation.
     */
    public function postSaveRelatedRecordsAfter(RelationInterface $relation, array $relatedRecords, CollectionInterface $visited): ?bool;

    /**
     * Save through-relation target and intermediate records after parent save.
     *
     * Implementations save target records first and then create/update the
     * intermediate relationship node that points back to the parent.
     *
     * @param RelationInterface $relation Through relation metadata being saved.
     * @param array<int, ModelInterface> $relatedRecords Related target records.
     * @param CollectionInterface $visited Phalcon visited graph for recursive
     *     saves.
     *
     * @return bool|null False on failure, true on success, or null when the
     *     relation is not a through relation.
     */
    public function postSaveRelatedThroughAfter(RelationInterface $relation, array $relatedRecords, CollectionInterface $visited): ?bool;

    /**
     * Find one model row using the complete primary-key payload.
     *
     * @param array<string, mixed> $data Data that may contain primary-key
     *     values.
     * @param class-string|null $modelClass Model class to query, or the current
     *     implementation class when null.
     */
    public function findFirstByPrimaryKeys(array $data, ?string $modelClass): ModelInterface|Row|null;

    /**
     * Resolve, create, and assign a related entity from array data.
     *
     * Implementations first try primary-key lookup, then relation-key lookup,
     * then instantiate a new related model when no existing entity is found.
     *
     * @param array<string, mixed> $data Related entity data.
     * @param array<string, mixed> $configuration Relation assignment metadata.
     */
    public function getEntityFromData(array $data, array $configuration = []): ModelInterface|Row|null;

    /**
     * Append messages to the current model with relationship metadata.
     *
     * @param Message[] $messages Messages to append.
     * @param string|null $context Relationship context to prepend.
     * @param int|null $index Optional list index to prepend.
     */
    public function appendMessages(array $messages = [], ?string $context = null, ?int $index = 0): void;

    /**
     * Append validation/save messages from one related record.
     */
    public function appendMessagesFromRecord(?ModelInterface $record = null, ?string $context = null, ?int $index = 0): void;

    /**
     * Append validation/save messages from a related resultset.
     */
    public function appendMessagesFromResultset(?ResultsetInterface $resultset = null, ?string $context = null, ?int $index = 0): void;

    /**
     * Append validation/save messages from an iterable related record list.
     */
    public function appendMessagesFromRecordList(?iterable $recordList = null, ?string $context = null, ?int $index = 0): void;

    /**
     * Build a nested message context from an existing message and new context.
     */
    public function rebuildMessageContext(Message $message, string $context): ?string;

    /**
     * Build a nested message index from an existing message and new index.
     */
    public function rebuildMessageIndex(Message $message, ?int $index): ?string;

    /**
     * Export loaded and dirty related records to arrays.
     *
     * @param array|null $columns Optional column selection map.
     * @param bool $useGetter Whether related model `toArray()` should use
     *     getters.
     *
     * @return array<string, mixed>
     */
    public function relatedToArray(?array $columns = null, bool $useGetter = true): array;
}
