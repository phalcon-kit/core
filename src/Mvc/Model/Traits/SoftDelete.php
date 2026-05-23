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

use PhalconKit\Mvc\Model\Behavior\SoftDelete as SoftDeleteBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEntity;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEventsManager;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractModelsManager;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractOptions;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractSave;

/**
 * This trait provides soft delete functionality to a model class.
 * It allows you to mark a record as deleted without actually deleting it from the database.
 * When a record is marked as deleted, it won't be retrieved in queries unless explicitly requested.
 */
trait SoftDelete
{
    use AbstractBehavior;
    use AbstractEntity;
    use AbstractModelsManager;
    use AbstractEventsManager;
    use AbstractOptions;
    use AbstractSave;
    
    /**
     * @var bool $skipped
     */
    protected $skipped = false;
    
    /**
     * Initializing SoftDelete
     */
    public function initializeSoftDelete(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('softDelete') ?? [];
        
        $options['field'] ??= 'deleted';
        $options['value'] ??= 1;
        
        $this->setSoftDeleteBehavior(new SoftDeleteBehavior($options));
    }
    
    /**
     * Set the SoftDeleteBehavior variable
     * Attach the SoftDelete behavior class
     */
    public function setSoftDeleteBehavior(SoftDeleteBehavior $softDeleteBehavior): void
    {
        $this->setBehavior('softDelete', $softDeleteBehavior);
    }
    
    /**
     * Return the soft delete behavior instance
     */
    public function getSoftDeleteBehavior(): SoftDeleteBehavior
    {
        $behavior = $this->getBehavior('softDelete');
        assert($behavior instanceof SoftDeleteBehavior);
        return $behavior;
    }
    
    /**
     * Disable the soft delete for the current instance
     * Note: SoftDelete behavior must be attached
     */
    public function disableSoftDelete(): void
    {
        $this->getSoftDeleteBehavior()->disable();
    }
    
    /**
     * Enable the soft delete for the current instance
     * Note: SoftDelete behavior must be attached
     */
    public function enableSoftDelete(): void
    {
        $this->getSoftDeleteBehavior()->enable();
    }
    
    /**
     * Helper method to check if the row is soft deleted
     */
    public function isDeleted(?string $field = null, ?int $deletedValue = null): bool
    {
        $field ??= $this->getSoftDeleteBehavior()->getField();
        $deletedValue ??= $this->getSoftDeleteBehavior()->getValue();
        return $this->readAttribute($field) === $deletedValue;
    }
    
    /**
     * Restore a previously Soft-deleted entry and fire events
     * Events:
     * - beforeRestore
     * - notRestored
     * - afterRestore
     *
     * The native ORM events flag is read from INI here because the trait can be
     * used without access to the original model setup options.
     */
    public function restore(?string $field = null, ?int $notDeletedValue = null): bool
    {
        $ormEvents = (bool)ini_get('phalcon.orm.events');
        
        if ($ormEvents) {
            $this->skipped = false;
            
            // fire event, allowing to stop options or skip the current operation
            if ($this->fireEventCancel('beforeRestore') === false) {
                return false;
            }
            
            /**
             * Can be modified by the previous event
             * @psalm-suppress TypeDoesNotContainType
             * @phpstan-ignore-next-line
             */
            if ($this->skipped) {
                return true;
            }
        }
        
        $field ??= $this->getSoftDeleteBehavior()->getField();
        $notDeletedValue ??= 0;
        
        // restore (unset soft delete value and save)
        $this->writeAttribute($field, $notDeletedValue);
        $save = $this->save();
        
        // check if the entity is restored
        $value = $this->readAttribute($field);
        $restored = $save && $value === $notDeletedValue;
        
        // fire events
        if ($ormEvents) {
            if (!$restored) {
                $this->fireEvent('notRestored');
            }
            else {
                $this->fireEvent('afterRestore');
            }
        }
        
        return $restored;
    }
}
