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

    public function setKeepMissingRelated(array $keepMissingRelated): void;
    
    public function getKeepMissingRelated(): array;
    
    public function getKeepMissingRelatedAlias(string $alias): bool;
    
    public function setKeepMissingRelatedAlias(string $alias, bool $keepMissing): void;
    
    public function getRelationshipContext(): string;
    
    public function setRelationshipContext(string $context): void;
    
    public function getDirtyRelated(): array;
    
    public function setDirtyRelated(array $dirtyRelated): void;
    
    public function getDirtyRelatedAlias(string $alias): mixed;
    
    public function setDirtyRelatedAlias(string $alias, mixed $value): void;
    
    public function hasDirtyRelated(): bool;
    
    public function hasDirtyRelatedAlias(string $alias): bool;

    public function getLoadedRelated(): array;

    public function setLoadedRelated(array $loadedRelated): void;

    public function getLoadedRelatedAlias(string $alias): mixed;

    public function setLoadedRelatedAlias(string $alias, mixed $value): void;

    public function hasLoadedRelatedAlias(string $alias): bool;

    public function assignRelated(array $data, ?array $whiteList = null, ?array $dataColumnMap = null): ModelInterface;
    
    public function postSaveRelatedRecordsAfter(RelationInterface $relation, array $relatedRecords, CollectionInterface $visited): ?bool;
    
    public function postSaveRelatedThroughAfter(RelationInterface $relation, array $relatedRecords, CollectionInterface $visited): ?bool;
    
    public function findFirstByPrimaryKeys(array $data, ?string $modelClass): ModelInterface|Row|null;
    
    public function getEntityFromData(array $data, array $configuration = []): ModelInterface|Row|null;
    
    public function appendMessages(array $messages = [], ?string $context = null, ?int $index = 0): void;
    
    public function appendMessagesFromRecord(?ModelInterface $record = null, ?string $context = null, ?int $index = 0): void;
    
    public function appendMessagesFromResultset(?ResultsetInterface $resultset = null, ?string $context = null, ?int $index = 0): void;
    
    public function appendMessagesFromRecordList(?iterable $recordList = null, ?string $context = null, ?int $index = 0): void;
    
    public function rebuildMessageContext(Message $message, string $context): ?string;
    
    public function rebuildMessageIndex(Message $message, ?int $index): ?string;
    
    public function relatedToArray(?array $columns = null, bool $useGetter = true): array;
}
