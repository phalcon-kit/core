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

namespace PhalconKit\Mvc;

use PhalconKit\Di\InjectableProperties;

/**
 * Base MVC controller for PhalconKit applications.
 *
 * The class keeps Phalcon's native controller behavior and adds typed injectable
 * properties used throughout the framework. Application controllers can extend
 * it when they want direct access to the PhalconKit DI helper surface without
 * re-declaring those service properties.
 */
class Controller extends \Phalcon\Mvc\Controller
{
    use InjectableProperties;
}
