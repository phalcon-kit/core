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

namespace PhalconKit\Mvc;

use AllowDynamicProperties;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Support\Collection\CollectionInterface;

/**
 * Base model combining Core persistence, relationships, validation, and lifecycle features.
 *
 * Requires the model manager, metadata, and connection services expected by Phalcon,
 * plus Core's config/helper services for feature options. Subclasses that override
 * initialize() should call parent::initialize() when they need Core's behaviors.
 * Persistence methods delegate transaction and event handling to Phalcon after
 * normalizing SQL NULL sentinels; they do not create a separate Core transaction.
 *
 * Supported lifecycle events include:
 * - afterCreate
 * - afterDelete
 * - afterFetch
 * - afterSave
 * - afterUpdate
 * - afterValidation
 * - afterValidationOnCreate
 * - afterValidationOnUpdate
 * - beforeDelete
 * - beforeCreate
 * - beforeSave
 * - beforeUpdate
 * - beforeValidation
 * - beforeValidationOnCreate
 * - beforeValidationOnUpdate
 * - notDeleted
 * - notSaved
 * - onValidationFails
 * - prepareSave
 * - validation
 * @link https://docs.phalcon.io/latest/db-models/#events
 *
 * {@inheritdoc} \Phalcon\Mvc\Model
 * @package PhalconKit\Mvc
 */
#[AllowDynamicProperties]
class Model extends \Phalcon\Mvc\Model implements ModelInterface
{
    // Model Feature Traits
    use Model\Traits\Attribute;
    use Model\Traits\Blameable;
    use Model\Traits\Cache;
    use Model\Traits\Count;
    use Model\Traits\EagerLoad;
    use Model\Traits\Events;
    use Model\Traits\Expose;
    use Model\Traits\FindIn;
    use Model\Traits\Hash;
    use Model\Traits\Identity;
    use Model\Traits\Instance;
    use Model\Traits\Json;
    use Model\Traits\LifeCycle;
    use Model\Traits\Locale;
    use Model\Traits\MetaData;
    use Model\Traits\Options;
    use Model\Traits\Position;
    use Model\Traits\Relationship;
    use Model\Traits\Replication;
    use Model\Traits\Security;
    use Model\Traits\Slug;
    use Model\Traits\Snapshot;
    use Model\Traits\SoftDelete;
    use Model\Traits\Uuid;
    use Model\Traits\Validate;

    /**
     * Insert or update this model using native Phalcon persistence and lifecycle events.
     *
     * Normalize case-insensitive "NULL" strings on nullable mapped attributes and
     * snapshots before persistence, and again after a successful write. Non-nullable
     * attributes retain their values for model/database validation.
     *
     * @return bool Whether persistence succeeded; inspect getMessages() on false.
     * @throws \Phalcon\Mvc\Model\Exception When native persistence rejects the operation by exception.
     */
    #[\Override]
    public function save(): bool
    {
        $this->normalizeNullableNullStrings();
        $saved = parent::save();
        if ($saved) {
            $this->normalizeNullableNullStrings();
        }

        return $saved;
    }

    /**
     * Insert this model using native Phalcon persistence and lifecycle events.
     *
     * Normalize case-insensitive "NULL" strings on nullable mapped attributes and
     * snapshots before persistence, and again after a successful write. Non-nullable
     * attributes retain their values for model/database validation.
     *
     * @return bool Whether persistence succeeded; inspect getMessages() on false.
     * @throws \Phalcon\Mvc\Model\Exception When native persistence rejects the operation by exception.
     */
    #[\Override]
    public function create(): bool
    {
        $this->normalizeNullableNullStrings();
        $created = parent::create();
        if ($created) {
            $this->normalizeNullableNullStrings();
        }

        return $created;
    }

    /**
     * Update this model using native Phalcon persistence and lifecycle events.
     *
     * Normalize case-insensitive "NULL" strings on nullable mapped attributes and
     * snapshots before persistence, and again after a successful write. Non-nullable
     * attributes retain their values for model/database validation.
     *
     * @return bool Whether persistence succeeded; inspect getMessages() on false.
     * @throws \Phalcon\Mvc\Model\Exception When native persistence rejects the operation by exception.
     */
    #[\Override]
    public function update(): bool
    {
        $this->normalizeNullableNullStrings();
        $updated = parent::update();
        if ($updated) {
            $this->normalizeNullableNullStrings();
        }

        return $updated;
    }

    /**
     * Apply the same NULL normalization during native recursive relationship saves.
     *
     * This is a Phalcon persistence hook. Pass the existing visited collection on
     * delegation so native cycle detection and transaction handling stay intact.
     *
     * @param CollectionInterface $visited Models visited in the current save traversal.
     * @return bool Whether native persistence succeeded.
     * @throws \Phalcon\Mvc\Model\Exception When native persistence rejects the operation by exception.
     */
    #[\Override]
    public function doSave(CollectionInterface $visited): bool
    {
        $this->normalizeNullableNullStrings();
        $saved = parent::doSave($visited);
        if ($saved) {
            $this->normalizeNullableNullStrings();
        }

        return $saved;
    }

    /**
     * Initialize feature options, ORM defaults, events, and model behaviors.
     *
     * Phalcon initializes each model class through its modelsManager. This method
     * installs a model events manager, enables dynamic updates, and registers the
     * enabled feature behaviors. Its setup options also affect the process-wide ORM.
     * Call the parent first before customizing Core's event manager or behaviors.
     */
    public function initialize(): void
    {
        // Initialize options manager
        $this->initializeOptions();

        // Initialize setup & events manager
        self::setup($this->getOptionsManager()->get('setup'));
        $this->setEventsManager(new EventsManager());
        $this->useDynamicUpdate(true);

        // Initialize features
        $this->initializeCache();
        $this->initializeSnapshot();
        $this->initializeReplication();
        $this->initializeSoftDelete();
        $this->initializePosition();
        $this->initializeSecurity();
        $this->initializeBlameable();
        $this->initializeCreated();
        $this->initializeUpdated();
        $this->initializeDeleted();
        $this->initializeRestored();
        $this->initializeSlug();
        $this->initializeUuid();
    }

    /**
     * Handles dynamic model writes before Phalcon sees them.
     */
    #[\Override]
    public function __set(string $property, mixed $value): void
    {
        if ($this->writeLocalizedProperty($property, $value)) {
            return;
        }

        if ($this->isModelRelationAlias($property)) {
            $this->setDirtyRelatedAlias($property, $value);
            return;
        }

        parent::__set($property, $value);
    }

    /**
     * Handles dynamic model reads before Phalcon sees them.
     */
    #[\Override]
    public function __get(string $property): mixed
    {
        $localizedFound = false;
        $localized = $this->readLocalizedProperty($property, $localizedFound);
        if ($localizedFound) {
            return $localized;
        }

        if ($this->hasDirtyRelatedAlias($property)) {
            return $this->getDirtyRelatedAlias($property);
        }

        if ($this->hasLoadedRelatedAlias($property)) {
            return $this->getLoadedRelatedAlias($property);
        }

        $declaredRelationFound = false;
        $declaredRelation = $this->readDeclaredRelationAlias($property, $declaredRelationFound);
        if ($declaredRelationFound) {
            return $declaredRelation;
        }

        return parent::__get($property);
    }

    private function writeLocalizedProperty(string $property, mixed $value): bool
    {
        $lang = $this->getCurrentLocale();
        if (empty($lang)) {
            return false;
        }

        $set = $property . ucfirst($lang);
        if (!property_exists($this, $set)) {
            return false;
        }

        $this->writeAttribute($set, $value);
        return true;
    }

    private function readLocalizedProperty(string $property, bool &$found): mixed
    {
        $found = false;
        $lang = $this->getCurrentLocale();
        if (empty($lang)) {
            return null;
        }

        $get = $property . ucfirst($lang);
        if (!property_exists($this, $get)) {
            return null;
        }

        $found = true;
        return $this->readAttribute($get);
    }

    private function getCurrentLocale(): ?string
    {
        try {
            $locale = $this->getDI()->get('locale');
        }
        catch (\Throwable) {
            return null;
        }

        return $locale instanceof \PhalconKit\Locale ? $locale->getLocale() : null;
    }

    private function isModelRelationAlias(string $property): bool
    {
        try {
            return (bool)$this
                ->getModelsManager()
                ->getRelationByAlias(get_class($this), $this->normalizeRelationAlias($property));
        }
        catch (\Throwable) {
            return false;
        }
    }

    private function readDeclaredRelationAlias(string $property, bool &$found): mixed
    {
        $found = false;
        $alias = $this->normalizeRelationAlias($property);

        if (!$this->isModelRelationAlias($alias)) {
            return null;
        }

        if (property_exists($this, $alias)) {
            $declaredProperty = $alias;
        }
        elseif (property_exists($this, $property)) {
            $declaredProperty = $property;
        }
        else {
            return null;
        }

        $reflection = new \ReflectionProperty($this, $declaredProperty);
        if ($reflection->isStatic()) {
            return null;
        }

        $found = true;
        return $reflection->isInitialized($this) ? $reflection->getValue($this) : null;
    }

    /**
     * Replace trimmed, case-insensitive SQL NULL strings in nullable attributes.
     *
     * Resolve property names through metadata and its column map, then normalize
     * current and old snapshots too, avoiding false dirty changes. If metadata is
     * unavailable, leave values untouched. This method does not write to the database.
     */
    protected function normalizeNullableNullStrings(): void
    {
        $nullableAttributes = $this->getNullableMappedAttributes();
        foreach ($nullableAttributes as $attribute) {
            if ($this->isSqlNullString($this->readAttribute($attribute))) {
                $this->writeAttribute($attribute, null);
            }
        }

        $this->normalizeNullableNullStringSnapshots($nullableAttributes);
    }

    /**
     * @return list<string>
     */
    private function getNullableMappedAttributes(): array
    {
        try {
            $metadata = $this->getModelsMetaData();
            $attributes = $metadata->getAttributes($this);
            $notNull = array_flip($metadata->getNotNullAttributes($this));
            $columnMap = $metadata->getColumnMap($this) ?? [];
        }
        catch (\Throwable) {
            return [];
        }

        $nullable = [];
        foreach ($attributes as $column) {
            if (isset($notNull[$column])) {
                continue;
            }

            $nullable[] = $columnMap[$column] ?? $column;
        }

        return $nullable;
    }

    private function isSqlNullString(mixed $value): bool
    {
        return is_string($value) && strcasecmp(trim($value), 'NULL') === 0;
    }

    /**
     * @param list<string> $nullableAttributes
     */
    private function normalizeNullableNullStringSnapshots(array $nullableAttributes): void
    {
        if (!$this->hasSnapshotData()) {
            return;
        }

        $snapshot = $this->normalizeNullableNullStringData(
            $this->getSnapshotData(),
            $nullableAttributes
        );
        $this->setSnapshotData($snapshot);

        $oldSnapshot = $this->normalizeNullableNullStringData(
            $this->getOldSnapshotData(),
            $nullableAttributes
        );
        $this->setOldSnapshotData($oldSnapshot);
    }

    /**
     * @param array<string, mixed> $data
     * @param list<string> $nullableAttributes
     * @return array<string, mixed>
     */
    private function normalizeNullableNullStringData(array $data, array $nullableAttributes): array
    {
        foreach ($nullableAttributes as $attribute) {
            if (array_key_exists($attribute, $data) && $this->isSqlNullString($data[$attribute])) {
                $data[$attribute] = null;
            }
        }

        return $data;
    }

    /**
     * Apply Core's defaults and caller overrides to Phalcon's process-wide ORM options.
     *
     * Core enables castLastInsertIdToInt and castOnHydrate, and disables
     * notNullValidations for compatibility with generated-model validators. The
     * remaining defaults are listed in the implementation below. Caller options win.
     * Each initialize() call reapplies these defaults plus the model's setup options;
     * these settings are not isolated per model or per request in persistent workers.
     *
     * @param array<string, mixed>|null $options Native Phalcon ORM options; null applies Core defaults.
     * @see \Phalcon\Mvc\Model::setup()
     */
    #[\Override]
    public static function setup(?array $options = null): void
    {
        parent::setup(array_merge([
            'caseInsensitiveColumnMap' => false,
            'castLastInsertIdToInt' => true, // changed from default
            'castOnHydrate' => true, // changed from default
            'columnRenaming' => true,
            'disableAssignSetters' => false,
            'enableImplicitJoins' => true,
            'events' => true,
            'exceptionOnFailedMetaDataSave' => false,
            'exceptionOnFailedSave' => false,
            'ignoreUnknownColumns' => false,
            'lateStateBinding' => false,
            'notNullValidations' => false, // changed from default; keep for generated-model compatibility
            'phqlLiterals' => true,
            'prefetchRecords' => 0,
            'updateSnapshotOnSave' => true,
            'virtualForeignKeys' => true,
        ], $options ?? []));
    }
}
