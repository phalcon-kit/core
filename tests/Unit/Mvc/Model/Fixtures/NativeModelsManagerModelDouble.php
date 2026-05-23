<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Mvc\Model\ManagerInterface;

class NativeModelsManagerModelDouble extends ModelBehaviorDouble
{
    public ?ManagerInterface $nativeModelsManager = null;

    #[\Override]
    public function getModelsManager(): ManagerInterface
    {
        return $this->nativeModelsManager ?? parent::getModelsManager();
    }
}
