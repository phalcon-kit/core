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

use Phalcon\Mvc\Model\ManagerInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Model\ManagerInterface as PhalconKitManagerInterface;

/**
 * Shared models-manager contract helpers for model traits.
 *
 * Phalcon model APIs expose the native manager interface, while PhalconKit
 * traits need the extended manager contract for named behavior storage. This
 * trait keeps that stricter check in one place and gives downstream consumers
 * a stable exception when a native-only manager is configured.
 */
trait AbstractModelsManager
{
    use AbstractInjectable;

    /**
     * Return the native model manager assigned to the model.
     *
     * Implementations are supplied by Phalcon's model base class. The protected
     * helper below narrows this native contract to the PhalconKit manager when
     * framework-specific behavior registry APIs are required.
     */
    abstract public function getModelsManager(): ManagerInterface;

    /**
     * Resolve the PhalconKit models manager extension from the current model.
     *
     * Native Phalcon exposes only `Phalcon\Mvc\Model\ManagerInterface`, but
     * several PhalconKit model traits need framework-specific behavior helpers
     * such as named behavior registration. This helper keeps that stricter
     * contract check in one place instead of repeating assertions in each
     * behavior helper.
     *
     * @throws ServiceException When the model manager does not expose the
     *     PhalconKit model manager contract.
     * @return PhalconKitManagerInterface Extended manager with PhalconKit
     *     behavior-registry helpers.
     */
    protected function getPhalconKitModelsManager(): PhalconKitManagerInterface
    {
        $modelsManager = $this->getModelsManager();
        if (!$modelsManager instanceof PhalconKitManagerInterface) {
            throw new ServiceException(sprintf(
                'Expected model manager for model behavior helpers to be an instance of "%s"; got "%s".',
                PhalconKitManagerInterface::class,
                get_debug_type($modelsManager)
            ));
        }

        return $modelsManager;
    }
}
