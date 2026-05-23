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

use PhalconKit\Exception\LogicException;
use PhalconKit\Mvc\Model;
use PhalconKit\Mvc\Model\Behavior\Position as PositionBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractEventsManager;

/**
 * The Position trait is used to manage the position behavior of an object.
 * It provides methods to initialize the position behavior set and retrieve
 * the position behavior object, and reorder the object's position in a list.
 */
trait Position
{
    use AbstractEventsManager;
    use Behavior;
    use Options;
    
    /**
     * Initializes the position behavior for the current object.
     * Sets the position options and sets the position behavior accordingly.
     *
     * @param array|null $options The options for the position behavior.
     *                            If not provided, the default position behavior options will be used.
     *
     */
    public function initializePosition(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('position') ?? [];
        
        $this->setPositionBehavior(new PositionBehavior($options));
    }
    
    /**
     * Sets the position behavior for the current object.
     *
     * @param PositionBehavior $positionBehavior The position behavior to be set.
     *
     * @return void
     */
    public function setPositionBehavior(PositionBehavior $positionBehavior): void
    {
        $this->setBehavior('position', $positionBehavior);
    }
    
    /**
     * Retrieves the position behavior attached to the current object.
     *
     * @return PositionBehavior The position behavior object.
     * @throws LogicException if the position behavior is not found.
     */
    public function getPositionBehavior(): PositionBehavior
    {
        $behavior = $this->getBehavior('position');
        if (!$behavior instanceof PositionBehavior) {
            throw new LogicException(sprintf(
                'Expected position behavior to be an instance of "%s"; got "%s".',
                PositionBehavior::class,
                get_debug_type($behavior)
            ));
        }

        return $behavior;
    }
    
    /**
     * Reorders the current object's position in the list.
     * - Update position+1 done using afterSave event
     *
     * @param int|null $position The new position for the object. If not provided, the default behavior's position field will be used.
     * @param string|null $positionField The field on which the position is stored. If not provided, the default behavior's field will be used.
     *
     * @return bool Returns true if the reorder operation was successful, false otherwise.
     * @throws LogicException When the trait is used on an incompatible model.
     */
    public function reorder(?int $position = null, ?string $positionField = null): bool
    {
        $model = $this->requirePositionModel();
        
        $positionField ??= $this->getPositionBehavior()->getField();
        
        if ($model->fireEventCancel('beforeReorder') === false) {
            return false;
        }
        
        $model->assign([$positionField => $position], [$positionField]);
        $saved = $model->save() && (!$model->hasSnapshotData() || $model->hasUpdated($positionField));
        
        if ($saved) {
            $model->fireEvent('afterReorder');
        }
        
        return $saved;
    }

    /**
     * Require the trait host to be a PhalconKit model.
     *
     * Position reordering depends on model events, assignment, snapshots, and
     * persistence APIs. This helper keeps `reorder()` readable while producing
     * a deterministic PhalconKit exception if the trait is composed into an
     * incompatible class.
     *
     * @return Model
     *
     * @throws LogicException When the trait host is not a PhalconKit model.
     */
    protected function requirePositionModel(): Model
    {
        if ($this instanceof Model) {
            return $this;
        }

        throw new LogicException(sprintf(
            'Position behavior requires the trait host to be an instance of "%s"; got "%s".',
            Model::class,
            get_debug_type($this)
        ));
    }
}
