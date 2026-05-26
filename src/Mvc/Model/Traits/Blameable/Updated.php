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

namespace PhalconKit\Mvc\Model\Traits\Blameable;

use PhalconKit\Db\Column;
use PhalconKit\Mvc\Model\Behavior\Transformable;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBlameable;
use PhalconKit\Mvc\Model\Traits\Identity;
use PhalconKit\Mvc\Model\Traits\Options;
use PhalconKit\Mvc\Model\Traits\Snapshot;
use PhalconKit\Mvc\Model\Traits\SoftDelete;

trait Updated
{
    use AbstractBehavior;
    use AbstractBlameable;
    use Options;
    use Identity;
    use Snapshot;
    use SoftDelete;
    use BlameAt;
    
    /**
     * Initializing Updated
     */
    public function initializeUpdated(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('updated') ?? [];
        
        $fieldBy = $options['fieldBy'] ?? 'updatedBy';
        $fieldAs = $options['fieldAs'] ?? 'updatedAs';
        $fieldAt = $options['fieldAt'] ?? 'updatedAt';
        
        $this->addUserRelationship($fieldBy, 'UpdatedBy');
        $this->addUserRelationship($fieldAs, 'UpdatedAs');
        
        $this->setUpdatedBehavior(new Transformable([
            'beforeValidationOnUpdate' => [
                $fieldBy => $this->hasChangedCallback(function (): ?int {
                    return $this->getCurrentUserIdCallback(false)();
                }),
                $fieldAs => $this->hasChangedCallback(function (): ?int {
                    return $this->getCurrentUserIdCallback(true)();
                }),
                $fieldAt => $this->hasChangedCallback(function (): ?string {
                    return $this->getDateCallback(Column::DATETIME_FORMAT)();
                }),
            ],
        ]));
    }
    
    /**
     * Set Updated Behavior
     */
    public function setUpdatedBehavior(Transformable $updatedBehavior): void
    {
        $this->setBehavior('updated', $updatedBehavior);
    }
    
    /**
     * Get Updated Behavior
     */
    public function getUpdatedBehavior(): Transformable
    {
        return $this->getTypedBehavior('updated', Transformable::class);
    }
}
