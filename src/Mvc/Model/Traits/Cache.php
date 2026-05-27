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

use Phalcon\Mvc\Model;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model\Behavior\Action;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractModelsCache;
use PhalconKit\Support\Models;

/**
 * Registers model-cache flushing behavior for mutable models.
 *
 * By default the trait installs after-event actions that clear the shared
 * `modelsCache` service when a save/update changed data, or when create,
 * delete, restore, and reorder events change record visibility/order.
 * Session and audit models are blacklisted during initialization to avoid
 * flushing global model caches for high-volume infrastructure records.
 *
 * Known limitation: cache invalidation is coarse-grained. Granular cache keys,
 * whitelist rules, and pre-warming need an explicit cache policy contract
 * before this trait can safely delete only selected entries.
 */
trait Cache
{
    use AbstractModelsCache;
    use AbstractBehavior;
    
    /**
     * Whether cache flushing is disabled for this model instance.
     *
     * Set this to true before initialization or before calling
     * `addFlushCacheBehavior()` to skip installing the flush behavior for the
     * current instance.
     */
    public bool $preventFlushCache = false;
    
    /**
     * Model classes that should not trigger global model-cache clearing.
     *
     * The list is populated with core session/audit classes during
     * initialization and can be extended by applications before calling
     * `addFlushCacheBehavior()`.
     *
     * @var array<int, class-string|object|string>
     */
    public array $flushModelsCacheBlackList = [];
    
    /**
     * Initialize model cache invalidation for the current model.
     *
     * The `models` service supplies framework model class mappings used for
     * the default blacklist. After the blacklist is prepared, the trait adds
     * the flush behavior unless this model instance or class is excluded.
     *
     * @throws ServiceException When the models service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function initializeCache(): void
    {
        $models = $this->getTypedService('models', Models::class, 'model cache helpers');
        
        $this->flushModelsCacheBlackList [] = $models->getSessionClass();
        $this->flushModelsCacheBlackList [] = $models->getAuditClass();
        $this->flushModelsCacheBlackList [] = $models->getAuditDetailClass();
        
        $this->addFlushCacheBehavior($this->flushModelsCacheBlackList);
    }
    
    /**
     * Add an after-event behavior that clears the shared models cache.
     *
     * The behavior is skipped when `preventFlushCache` is true or the current
     * model is an instance of one of the blacklisted classes. Cache clearing is
     * attempted only when snapshots indicate that persisted data changed.
     *
     * @param array<int, class-string|object|string>|null $flushModelsCacheBlackList
     *     Classes that should not receive the flush behavior. Defaults to the
     *     instance blacklist.
     * @throws ServiceException When the modelsCache service cannot be resolved
     *     through the PhalconKit DI contract.
     */
    public function addFlushCacheBehavior(?array $flushModelsCacheBlackList = null): void
    {
        $flushModelsCacheBlackList ??= $this->flushModelsCacheBlackList;
        
        // flush cache prevented by current instance
        if ($this->preventFlushCache) {
            return;
        }
        
        // flush cache prevented if current instance class is blacklisted
        if ($this->isInstanceOf($flushModelsCacheBlackList)) {
            return;
        }
        
        $modelsCache = $this->getModelsCache();
        $flushWhenChanged = function (Model $model) use ($modelsCache): bool {
            // New records have no old snapshot; existing records flush only
            // when Phalcon reports changed or updated fields.
            return (!$model->hasSnapshotData() || $model->hasUpdated() || $model->hasChanged())
                && $modelsCache->clear();
        };
        $flushAlways = static fn (Model $_model): bool => $modelsCache->clear();
        
        $this->addBehavior(new Action([
            'afterSave' => ['flush' => $flushWhenChanged],
            'afterCreate' => ['flush' => $flushAlways],
            'afterUpdate' => ['flush' => $flushWhenChanged],
            'afterDelete' => ['flush' => $flushAlways],
            'afterRestore' => ['flush' => $flushAlways],
            'afterReorder' => ['flush' => $flushAlways],
        ]));
    }
    
    /**
     * Check whether a model instance belongs to any configured class.
     *
     * This helper supports cache blacklist checks while allowing tests or
     * callers to pass an explicit instance. Invalid values are ignored instead
     * of being passed to `instanceof`, because a malformed application-provided
     * blacklist entry should not turn cache-behavior initialization into a PHP
     * fatal error.
     *
     * @param array<int, mixed> $classes Class names, objects, or ignored
     *     malformed values to compare against.
     * @param ModelInterface|null $that Optional model instance to inspect.
     *     Defaults to the current model.
     * @return bool True when the model is an instance of at least one class.
     */
    public function isInstanceOf(array $classes = [], ?ModelInterface $that = null): bool
    {
        $that ??= $this;
        
        // Prevent adding behavior to whiteListed models
        foreach ($classes as $class) {
            if (!is_string($class) && !is_object($class)) {
                continue;
            }

            if ($that instanceof $class) {
                return true;
            }
        }
        
        return false;
    }
}
