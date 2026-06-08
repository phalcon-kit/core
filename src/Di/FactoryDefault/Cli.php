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

namespace PhalconKit\Di\FactoryDefault;

use PhalconKit\Di\DiInterface;
use PhalconKit\Di\TypedServicesTrait;

/**
 * CLI default-service PhalconKit DI container.
 *
 * This container mirrors Phalcon's CLI FactoryDefault service registration and
 * adds PhalconKit typed lookup helpers. It is the default container for
 * PhalconKit CLI bootstraps unless an application passes a custom DiInterface.
 *
 * @see https://docs.phalcon.io/5.14/di/
 */
class Cli extends \Phalcon\Di\FactoryDefault\Cli implements DiInterface
{
    use TypedServicesTrait;
}
