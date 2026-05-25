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

namespace PhalconKit\Mvc\Model;

use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\Model\ManagerInterface as PhalconModelsManagerInterface;
use Phalcon\Mvc\ModelInterface;

interface ManagerInterface extends PhalconModelsManagerInterface
{
    public function getBehaviors(): array;
    
    public function setBehaviors(array $behaviors): void;
    
    public function getBehavior(ModelInterface $model, string $behaviorName): ?BehaviorInterface;
    
    public function setBehavior(ModelInterface $model, string $behaviorName, BehaviorInterface $behavior): void;
    
    public function hasBehavior(ModelInterface $model, string $behaviorName): bool;
    
    public function removeBehavior(ModelInterface $model, string $behaviorName): void;
}
