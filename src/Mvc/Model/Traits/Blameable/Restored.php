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

trait Restored
{
    use AbstractBehavior;
    use AbstractBlameable;
    use Options;
    use Identity;
    use Snapshot;
    use SoftDelete;
    use BlameAt;
    
    /**
     * Initializing Restored
     */
    public function initializeRestored(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('restored') ?? [];
        
        $fieldBy = $options['fieldBy'] ?? 'restoredBy';
        $fieldAs = $options['fieldAs'] ?? 'restoredAs';
        $fieldAt = $options['fieldAt'] ?? 'restoredAt';
        
        $this->addUserRelationship($fieldBy, 'RestoredBy');
        $this->addUserRelationship($fieldAs, 'RestoredAs');
        
        $this->setRestoredBehavior(new Transformable([
            'beforeRestore' => [
                $fieldBy => $this->getCurrentUserIdCallback(),
                $fieldAs => $this->getCurrentUserIdCallback(true),
                $fieldAt => $this->getDateCallback(Column::DATETIME_FORMAT),
            ],
        ]));
    }
    
    /**
     * Set Restored Behavior
     */
    public function setRestoredBehavior(Transformable $restoredBehavior): void
    {
        $this->setBehavior('restored', $restoredBehavior);
    }
    
    /**
     * Get Restored Behavior
     */
    public function getRestoredBehavior(): Transformable
    {
        return $this->getTypedBehavior('restored', Transformable::class);
    }
}
