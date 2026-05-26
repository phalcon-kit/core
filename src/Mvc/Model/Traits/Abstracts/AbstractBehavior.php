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

namespace PhalconKit\Mvc\Model\Traits\Abstracts;

use Phalcon\Mvc\Model\BehaviorInterface;

trait AbstractBehavior
{
    abstract public function addBehavior(BehaviorInterface $behavior): void;
    
    abstract public function getBehavior(string $behaviorName): ?BehaviorInterface;

    /**
     * Retrieve a named behavior and require a specific behavior implementation.
     *
     * Model feature traits use this helper for public getters such as
     * `getUuidBehavior()` and `getSoftDeleteBehavior()`. The concrete
     * implementation lives in the shared behavior trait so each feature trait
     * can return a stable PhalconKit exception when a behavior was not
     * initialized, instead of relying on PHP assertions or late return-type
     * errors.
     *
     * @template TBehavior of BehaviorInterface
     * @param string $behaviorName Registry key used by the initializer.
     * @param class-string<TBehavior> $expectedClass Expected behavior class.
     * @return TBehavior Registered behavior narrowed to the expected type.
     *
     * @throws \PhalconKit\Exception\ServiceException When the behavior is
     *     missing or does not match the expected class.
     */
    abstract protected function getTypedBehavior(string $behaviorName, string $expectedClass): BehaviorInterface;
    
    abstract public function setBehavior(string $behaviorName, BehaviorInterface $behavior): void;
    
    abstract public function hasBehavior(string $behaviorName): bool;
    
    abstract public function removeBehavior(string $behaviorName): void;
}
