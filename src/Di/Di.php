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
 * Minimal PhalconKit DI container.
 *
 * Use this container in tests, lightweight bootstraps, or applications that do
 * not need the default MVC/CLI services pre-registered by FactoryDefault. It
 * keeps native Phalcon DI behavior while exposing PhalconKit typed helpers.
 */
class Di extends \Phalcon\Di\Di implements DiInterface
{
    use TypedServicesTrait;
}
