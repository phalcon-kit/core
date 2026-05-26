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

trait Created
{
    use AbstractBehavior;
    use AbstractBlameable;
    use Options;
    use Identity;
    use Snapshot;
    use SoftDelete;
    use BlameAt;
    
    /**
     * Initializing Created
     */
    public function initializeCreated(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('created') ?? [];
        
        $fieldBy = $options['fieldBy'] ?? 'createdBy';
        $fieldAs = $options['fieldAs'] ?? 'createdAs';
        $fieldAt = $options['fieldAt'] ?? 'createdAt';
        
        $this->addUserRelationship($fieldBy, 'CreatedBy');
        $this->addUserRelationship($fieldAs, 'CreatedAs');
        
        $this->setCreatedBehavior(new Transformable([
            'beforeValidationOnCreate' => [
                $fieldBy => $this->getCurrentUserIdCallback(),
                $fieldAs => $this->getCurrentUserIdCallback(true),
                $fieldAt => $this->getDateCallback(Column::DATETIME_FORMAT)
            ],
        ]));
    }
    
    /**
     * Set Created Behavior
     */
    public function setCreatedBehavior(Transformable $createdBehavior): void
    {
        $this->setBehavior('created', $createdBehavior);
    }
    
    /**
     * Get Created Behavior
     */
    public function getCreatedBehavior(): Transformable
    {
        return $this->getTypedBehavior('created', Transformable::class);
    }
}
