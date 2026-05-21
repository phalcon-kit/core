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

/**
 * Events
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
 * @link https://docs.phalcon.io/5.13/db-models/#events
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
     * Fix a phalcon bug, despite phalcon stub and core code state that dirtyRelated is defined
     * with an empty array, it seems that the dirtyRelated default value is not set at all.
     * This is why we are redefining it here, so we can make the static code analyzers happy.
     *
     * Fatal error: Phalcon\Mvc\Model and PhalconKit\Mvc\Model\Traits\Relationship
     * define the same property ($dirtyRelated) in the composition of PhalconKit\Mvc\Model.
     * However, the definition differs and is considered incompatible.
     * @var array
     */
    protected $dirtyRelated = [];

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
     * Enables/disables options in the ORM
     * - We do this here in order to keep behaviour consistencies between different environments
     * --------------------------------
     *  caseInsensitiveColumnMap - false - Case insensitive column map
     *  castLastInsertIdToInt - false - Casts the lastInsertId to an integer
     *  castOnHydrate - false - Automatic cast to original types on hydration
     *  columnRenaming - true - Column renaming
     *  disableAssignSetters - false - Disable setters
     *  enableImplicitJoins - true - Enable implicit joins
     *  events - true - Callbacks, hooks and event notifications from all the models
     *  exceptionOnFailedMetaDataSave - false - Throw an exception when there is a failed meta-data save
     *  exceptionOnFailedSave - false - Throw an exception when there is a failed save()
     *  ignoreUnknownColumns - false - Ignore unknown columns on the model
     *  lateStateBinding - false - Late state binding of the Phalcon\Mvc\Model::cloneResultMap() method
     *  notNullValidations - true - Automatically validate the not null columns present
     *  phqlLiterals - true - Literals in the PHQL parser
     *  prefetchRecords - 0 - The number of records to prefetch when getting data from the ORM
     *  updateSnapshotOnSave - true - Update snapshots on save()
     *  virtualForeignKeys - true - Virtual foreign keys
     * --------------------------------
     * @link https://docs.phalcon.io/5.13/db-models#model-features
     *
     * @param array|null $options
     */
    #[\Override]
    public static function setup(?array $options = null): void
    {
        parent::setup(array_merge([
            'caseInsensitiveColumnMap' => false,
            'castLastInsertIdToInt' => true, // changed from default
            'castOnHydrate' => true, // changed from default
//            'castOnHydrate' => false, // problems with binary when true
            'columnRenaming' => true,
            'disableAssignSetters' => false,
            'enableImplicitJoins' => true,
            'events' => true,
            'exceptionOnFailedMetaDataSave' => false,
            'exceptionOnFailedSave' => false,
            'ignoreUnknownColumns' => false,
            'lateStateBinding' => false,
            'notNullValidations' => false, // changed from default @todo see if we can
            'phqlLiterals' => true,
            'prefetchRecords' => 0,
            'updateSnapshotOnSave' => true,
            'virtualForeignKeys' => true,
        ], $options ?? []));
    }
}
