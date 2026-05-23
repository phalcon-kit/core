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

namespace PhalconKit\Di;

/**
 * MVC/default-service PhalconKit DI container.
 *
 * This container mirrors Phalcon's FactoryDefault service registration and adds
 * PhalconKit typed lookup helpers. It is the default container for non-CLI
 * PhalconKit bootstraps unless an application passes a custom DiInterface.
 */
class FactoryDefault extends \Phalcon\Di\FactoryDefault implements DiInterface
{
    use TypedServicesTrait;
}
