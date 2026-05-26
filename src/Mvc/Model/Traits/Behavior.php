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

use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractModelsManager;

/**
 * Adds named behavior registration helpers to PhalconKit models.
 *
 * Phalcon's native behavior stack is event oriented and does not expose a
 * first-class named registry. PhalconKit's models manager adds that registry
 * so model traits can install and later retrieve behaviors such as `uuid`,
 * `softDelete`, or `blameable` without duplicating storage on each model.
 *
 * The model must be managed by `PhalconKit\Mvc\Model\ManagerInterface`; native
 * Phalcon-only managers cannot provide the named behavior methods and are
 * rejected by the shared helper in `AbstractModelsManager`.
 */
trait Behavior
{
    use AbstractModelsManager;
    
    /**
     * Retrieve a named behavior registered for the current model.
     *
     * This method returns null when no behavior is registered under the given
     * name. It does not inspect Phalcon's native event manager; it reads the
     * PhalconKit models-manager registry.
     *
     * @param string $behaviorName Registry key such as `uuid`, `security`, or
     *     `softDelete`.
     * @return BehaviorInterface|null Registered behavior, or null when the name
     *     is not present.
     * @throws \PhalconKit\Exception\ServiceException When the current models
     *     manager does not implement the PhalconKit manager contract.
     */
    public function getBehavior(string $behaviorName): ?BehaviorInterface
    {
        $modelsManager = $this->getPhalconKitModelsManager();
        return $modelsManager->getBehavior($this->getBehaviorModel(), $behaviorName);
    }

    /**
     * Retrieve a named behavior and require the expected behavior class.
     *
     * The named behavior registry returns the generic Phalcon behavior
     * interface. Public model helper methods usually promise a concrete
     * behavior type, so this helper centralizes the runtime check and produces
     * a stable framework exception when an initializer was skipped or a behavior
     * name was reused with the wrong implementation.
     *
     * @template TBehavior of BehaviorInterface
     * @param string $behaviorName Registry key such as `uuid` or `softDelete`.
     * @param class-string<TBehavior> $expectedClass Expected behavior class.
     * @return TBehavior Registered behavior narrowed to the expected class.
     *
     * @throws ServiceException When the behavior is missing or has the wrong
     *     type.
     */
    protected function getTypedBehavior(string $behaviorName, string $expectedClass): BehaviorInterface
    {
        $behavior = $this->getBehavior($behaviorName);
        if ($behavior instanceof $expectedClass) {
            return $behavior;
        }

        throw new ServiceException(sprintf(
            'Expected model behavior "%s" for "%s" to be an instance of "%s"; got "%s".',
            $behaviorName,
            $this->getBehaviorModel()::class,
            $expectedClass,
            get_debug_type($behavior)
        ));
    }
    
    /**
     * Register or replace a named behavior for the current model.
     *
     * The behavior remains associated with this model class in the PhalconKit
     * models manager. Callers that also need native Phalcon event notification
     * should continue to attach the behavior through the normal model behavior
     * APIs used by the specific trait.
     *
     * @param string $behaviorName Registry key used to retrieve the behavior
     *     later.
     * @param BehaviorInterface $behavior Behavior instance to register.
     * @throws \PhalconKit\Exception\ServiceException When the current models
     *     manager does not implement the PhalconKit manager contract.
     */
    public function setBehavior(string $behaviorName, BehaviorInterface $behavior): void
    {
        $modelsManager = $this->getPhalconKitModelsManager();
        $modelsManager->setBehavior($this->getBehaviorModel(), $behaviorName, $behavior);
    }
    
    /**
     * Determine whether a named behavior is registered for the current model.
     *
     * This is a lightweight registry check and does not instantiate or resolve
     * behavior services.
     *
     * @param string $behaviorName Registry key to inspect.
     * @return bool True when the behavior name exists for this model.
     * @throws \PhalconKit\Exception\ServiceException When the current models
     *     manager does not implement the PhalconKit manager contract.
     */
    public function hasBehavior(string $behaviorName): bool
    {
        $modelsManager = $this->getPhalconKitModelsManager();
        return $modelsManager->hasBehavior($this->getBehaviorModel(), $behaviorName);
    }
    
    /**
     * Remove a named behavior from the current model registry.
     *
     * Removing a missing behavior is treated as a no-op by the models manager.
     * This method updates the PhalconKit registry only; it does not detach
     * arbitrary listeners from a native Phalcon events manager.
     *
     * @param string $behaviorName Registry key to remove.
     * @throws \PhalconKit\Exception\ServiceException When the current models
     *     manager does not implement the PhalconKit manager contract.
     */
    public function removeBehavior(string $behaviorName): void
    {
        $modelsManager = $this->getPhalconKitModelsManager();
        $modelsManager->removeBehavior($this->getBehaviorModel(), $behaviorName);
    }

    /**
     * Require the trait host to be a Phalcon model instance.
     *
     * Model behavior registry APIs key entries by model class. If this trait is
     * accidentally composed into a non-model class, failing here gives
     * extension authors a deterministic PhalconKit exception even when PHP
     * assertions are disabled.
     *
     * @throws ServiceException When the trait host is not a Phalcon model.
     */
    private function getBehaviorModel(): ModelInterface
    {
        if ($this instanceof ModelInterface) {
            return $this;
        }

        throw new ServiceException(sprintf(
            'Model behavior helpers require the trait host to implement "%s"; got "%s".',
            ModelInterface::class,
            get_debug_type($this)
        ));
    }
}
