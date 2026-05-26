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

use Phalcon\Mvc\Model\Relation;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model\Behavior\Blameable as BlameableBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractIdentity;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractOptions;
use PhalconKit\Support\Models;

/**
 * Installs audit/user attribution behavior and user relationships on models.
 *
 * The trait wires the `Blameable` behavior with model classes resolved from
 * the shared `models` service, so consuming applications can override core
 * model classes through the normal model-map configuration. It also adds a
 * belongs-to relationship to the configured user model when the target field
 * exists on the model.
 */
trait Blameable
{
    use AbstractBehavior;
    use AbstractIdentity;
    use AbstractInjectable;
    use AbstractOptions;
    
    use Blameable\BlameAt;
    use Blameable\Created;
    use Blameable\Updated;
    use Blameable\Deleted;
    use Blameable\Restored;
    
    /**
     * Initialize the blameable behavior and user relationship.
     *
     * When no options are provided, the trait reads `blameable` options from
     * the model options manager. Missing audit, audit-detail, and user classes
     * are filled from the PhalconKit `models` service so applications using
     * custom model classes keep one central mapping.
     *
     * @param array<array-key, mixed>|null $options Behavior options. Common
     *     keys include `auditClass`, `auditDetailClass`, `userClass`, and
     *     `userField`.
     * @throws ServiceException When the models service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function initializeBlameable(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('blameable') ?? [];
        
        $models = $this->getTypedService('models', Models::class, 'model blameable helpers');
        
        $options['auditClass'] ??= $models->getAuditClass();
        $options['auditDetailClass'] ??= $models->getAuditDetailClass();
        $options['userClass'] ??= $models->getUserClass();
        
        $this->setBlameableBehavior(new BlameableBehavior($options));
        
        $userField = $options['userField'] ?? 'userId';
        $this->addUserRelationship($userField, 'User');
    }
    
    /**
     * Register the blameable behavior under the standard behavior name.
     *
     * The behavior is stored in the PhalconKit model behavior registry as
     * `blameable`, which lets other traits and application code retrieve the
     * same instance later.
     *
     * @param BlameableBehavior $blameableBehavior Configured behavior instance.
     * @throws ServiceException When the current models manager does not expose
     *     the PhalconKit behavior registry.
     */
    public function setBlameableBehavior(BlameableBehavior $blameableBehavior): void
    {
        $this->setBehavior('blameable', $blameableBehavior);
    }
    
    /**
     * Retrieve the registered blameable behavior.
     *
     * @throws ServiceException When the current models manager does not expose
     *     the PhalconKit behavior registry.
     */
    public function getBlameableBehavior(): BlameableBehavior
    {
        return $this->getTypedBehavior('blameable', BlameableBehavior::class);
    }
    
    /**
     * Add a user relationship when the configured attribution field exists.
     *
     * @param string $alias The alias name for the user entity. Default is 'UserEntity'.
     * @param array<array-key, mixed> $params Additional relationship
     *     parameters.
     * @param string $ref The reference field in the user entity. Default is 'id'.
     * @param string $type Relationship method to call on the model, usually
     *     `belongsTo`.
     * @param string|null $class User model class. When null, the class is
     *     resolved from the PhalconKit `models` service.
     * @return Relation|null Created relationship, or null when the model does
     *     not expose the configured attribution field.
     * @throws ServiceException When the models service cannot be resolved while
     *     deriving the default user class.
     */
    public function addUserRelationship(
        string $field = 'userId',
        string $alias = 'UserEntity',
        array $params = [],
        string $ref = 'id',
        string $type = 'belongsTo',
        ?string $class = null
    ): ?Relation {
        if (property_exists($this, $field)) {
            if (empty($class)) {
                $models = $this->getTypedService('models', Models::class, 'model blameable helpers');
                $class = $models->getUserClass();
            }
            
            return $this->$type($field, $class, $ref, ['alias' => $alias, 'params ' => $params]);
        }
        
        return null;
    }
}
