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

namespace PhalconKit\Assets;

/**
 * PhalconKit asset manager wrapper.
 *
 * The class intentionally keeps native `Phalcon\Assets\Manager` behavior while
 * giving applications a stable PhalconKit service type to decorate, replace,
 * or type-check through DI. Provider code registers this class under the
 * `assets` service name.
 */
class Manager extends \Phalcon\Assets\Manager
{
}
