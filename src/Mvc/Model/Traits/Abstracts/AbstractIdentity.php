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

use PhalconKit\Identity\ManagerInterface;

/**
 * Abstract identity contract required by model traits that need user context.
 *
 * Traits such as blameable timestamp/user attribution depend on the consuming
 * model exposing identity helpers. Keeping the contract here avoids coupling
 * those traits to a concrete model class.
 */
trait AbstractIdentity
{
    /**
     * Resolve the identity manager used by blameable model helpers.
     *
     * Implementing traits provide the concrete lookup and should fail with a
     * stable PhalconKit service exception when the DI service is unavailable or
     * does not implement the identity manager contract.
     *
     * @return ManagerInterface Current identity manager service.
     * @throws \PhalconKit\Exception\ServiceException When the identity service
     *     cannot be resolved through the PhalconKit DI contract.
     */
    abstract public function getIdentityService(): ManagerInterface;
}
